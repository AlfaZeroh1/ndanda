<?php
/* ---------------------------------------------------------------
 * Orders & revenue pipeline
 *   create_order()   -> pending order with line items
 *   mark_order_paid()-> idempotent: activates services + funeral
 * ------------------------------------------------------------- */

function new_order_ref(): string
{
    do {
        // Short and M-Pesa friendly (AccountReference max 12 chars)
        $ref = 'ND' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    } while (val('SELECT id FROM orders WHERE reference = ?', [$ref]));
    return $ref;
}

/**
 * Creates an order for the given services on a funeral. Services already owned
 * (or standard/free ones) are skipped. A zero total is marked paid straight away.
 */
function create_order(int $userId, int $funeralId, array $serviceIds): ?array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
    if (!$ids) return null;

    $in = implode(',', array_fill(0, count($ids), '?'));
    $services = rows("SELECT * FROM services WHERE is_active = 1 AND is_standard = 0 AND id IN ($in) ORDER BY sort", $ids);
    $owned = array_map('intval', array_column(rows('SELECT service_id FROM funeral_services WHERE funeral_id = ?', [$funeralId]), 'service_id'));
    $services = array_values(array_filter($services, fn($s) => !in_array((int) $s['id'], $owned, true)));
    if (!$services) return null;

    $total = array_sum(array_map(fn($s) => (float) $s['price'], $services));

    db()->beginTransaction();
    $orderId = insert('orders', [
        'reference'  => new_order_ref(),
        'user_id'    => $userId,
        'funeral_id' => $funeralId,
        'amount'     => $total,
        'status'     => 'pending',
        'is_test'    => test_flag(),
    ]);
    foreach ($services as $s) {
        insert('order_items', ['order_id' => $orderId, 'service_id' => $s['id'], 'name' => $s['name'], 'price' => $s['price']]);
    }
    db()->commit();

    if ($total <= 0) mark_order_paid($orderId, 'FREE');
    return row('SELECT * FROM orders WHERE id = ?', [$orderId]);
}

/** Grants standard (free) services to a funeral, e.g. the preparations workspace. */
function grant_standard_services(int $funeralId): void
{
    q('INSERT IGNORE INTO funeral_services (funeral_id, service_id) SELECT ?, id FROM services WHERE is_standard = 1 AND is_active = 1', [$funeralId]);
}

/** Marks an order paid and unlocks its services. Safe to call more than once. */
function mark_order_paid(int $orderId, ?string $receipt = null, ?string $phone = null): bool
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $o = row('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId]);
        if (!$o) {
            $pdo->rollBack();
            return false;
        }
        if ($o['status'] === 'paid') {
            $pdo->commit();
            return true;
        }
        update('orders', [
            'status'        => 'paid',
            'mpesa_receipt' => $receipt,
            'phone'         => $phone ?: $o['phone'],
            'paid_at'       => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);

        if ($o['funeral_id']) {
            q('INSERT IGNORE INTO funeral_services (funeral_id, service_id, order_id) SELECT ?, service_id, ? FROM order_items WHERE order_id = ? AND service_id IS NOT NULL', [$o['funeral_id'], $orderId, $orderId]);
            q("UPDATE funerals SET status = 'active' WHERE id = ? AND status IN ('draft','pending_payment')", [$o['funeral_id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    // Notifications (best effort)
    $user = $o['user_id'] ? row('SELECT * FROM users WHERE id = ?', [$o['user_id']]) : null;
    if ($user) {
        send_mail($user['email'], 'Payment received – ' . $o['reference'],
            '<h2>Thank you, ' . e($user['name']) . '</h2><p>We have received your payment of <strong>' . e(money($o['amount'])) . '</strong> for order <strong>' . e($o['reference']) . '</strong>' . ($receipt && $receipt !== 'FREE' ? ' (M-Pesa ' . e($receipt) . ')' : '') . '.</p><p>Your services are now active. <a href="' . e(url('account/')) . '">Open your funeral account</a>.</p>');
    }
    send_mail(setting('admin_notify_email'), 'New payment ' . money($o['amount']), '<p>Order ' . e($o['reference']) . ' was paid. Receipt: ' . e((string) $receipt) . '</p>');
    return true;
}

function order_items(int $orderId): array
{
    return rows('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
}
