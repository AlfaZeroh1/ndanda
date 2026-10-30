<?php
/** Starts an M-Pesa STK push for a pending order (called by checkout.php via AJAX). */
require __DIR__ . '/../includes/bootstrap.php';
if (!is_post()) json_out(['ok' => false, 'message' => 'Method not allowed'], 405);
$user = current_user();
if (!$user) json_out(['ok' => false, 'message' => 'Please log in again.'], 401);

$order = row('SELECT * FROM orders WHERE reference = ?', [input('ref')]);
if (!$order || ((int) $order['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) json_out(['ok' => false, 'message' => 'Order not found.'], 404);
if ($order['status'] === 'paid') json_out(['ok' => true, 'paid' => true]);

$phone = normalize_phone(input('phone'));
if (!$phone) json_out(['ok' => false, 'message' => 'Enter a valid Safaricom number, e.g. 0712 345 678.']);
if (!throttle('stk_' . $order['id'], 15)) json_out(['ok' => false, 'message' => 'Please wait a few seconds before requesting another prompt.']);
if (!Mpesa::isConfigured()) {
    json_out(['ok' => false, 'message' => 'M-Pesa is not configured yet. ' . (test_flag() ? 'Use “Simulate successful payment” while testing, or add Daraja keys in Admin → Settings.' : 'Please pay via Paybill or contact us.')]);
}

try {
    $res = Mpesa::stkPush($phone, (float) $order['amount'], $order['reference'], 'Order ' . $order['reference']);
    insert('mpesa_transactions', [
        'order_id'            => $order['id'],
        'type'                => 'stk',
        'merchant_request_id' => $res['MerchantRequestID'] ?? null,
        'checkout_request_id' => $res['CheckoutRequestID'] ?? null,
        'phone'               => $phone,
        'amount'              => $order['amount'],
        'account_ref'         => $order['reference'],
        'status'              => 'pending',
        'raw_request'         => json_encode($res['_request'] ?? []),
        'is_test'             => test_flag(),
    ]);
    update('orders', ['phone' => $phone], 'id = ?', [$order['id']]);
    json_out(['ok' => true, 'checkout_request_id' => $res['CheckoutRequestID'] ?? null, 'message' => $res['CustomerMessage'] ?? 'Prompt sent']);
} catch (Throwable $e) {
    Mpesa::log('stk_push_error', $e->getMessage());
    json_out(['ok' => false, 'message' => 'Could not send the M-Pesa prompt: ' . $e->getMessage()]);
}
