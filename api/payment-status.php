<?php
/**
 * Polled by checkout.php. Returns the order status; if the callback has not
 * arrived after ~20s it asks Daraja directly (STK Query) so payments never get stuck.
 */
require __DIR__ . '/../includes/bootstrap.php';
$user = current_user();
if (!$user) json_out(['status' => 'unknown'], 401);
$order = row('SELECT * FROM orders WHERE reference = ?', [input('ref')]);
if (!$order || ((int) $order['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) json_out(['status' => 'unknown'], 404);

if ($order['status'] === 'paid') json_out(['status' => 'paid', 'receipt' => $order['mpesa_receipt']]);
if (input('passive')) json_out(['status' => $order['status']]);

$tx = row("SELECT * FROM mpesa_transactions WHERE order_id = ? AND type = 'stk' ORDER BY id DESC LIMIT 1", [$order['id']]);
if (!$tx) json_out(['status' => 'pending']);

if ($tx['status'] === 'success') {
    $order = row('SELECT * FROM orders WHERE id = ?', [$order['id']]);
    json_out(['status' => $order['status'] === 'paid' ? 'paid' : 'pending', 'receipt' => $tx['mpesa_receipt']]);
}
if (in_array($tx['status'], ['failed', 'cancelled', 'timeout'], true)) {
    json_out(['status' => 'failed', 'message' => Mpesa::friendlyMessage($tx['result_code'], (string) $tx['result_desc'])]);
}

// Still pending: query Daraja at most every 10s, starting 20s after the push
$age = time() - strtotime($tx['created_at']);
$lastCheck = $tx['updated_at'] ? time() - strtotime($tx['updated_at']) : $age;
if ($age > 20 && $lastCheck >= 10 && $tx['checkout_request_id'] && Mpesa::isConfigured()) {
    try {
        $r = Mpesa::stkQuery($tx['checkout_request_id']);
        q('UPDATE mpesa_transactions SET updated_at = NOW() WHERE id = ?', [$tx['id']]);
        if (isset($r['ResultCode'])) {
            // Query results carry no receipt; the callback fills it in later if it arrives
            Mpesa::applyStkResult($tx, $r['ResultCode'], (string) ($r['ResultDesc'] ?? ''), [], json_encode($r));
            if ((string) $r['ResultCode'] === '0') json_out(['status' => 'paid', 'receipt' => '']);
            json_out(['status' => 'failed', 'message' => Mpesa::friendlyMessage($r['ResultCode'], (string) ($r['ResultDesc'] ?? ''))]);
        }
    } catch (Throwable $e) {
        // "transaction is being processed" or network hiccup – keep waiting
    }
}
json_out(['status' => 'pending']);
