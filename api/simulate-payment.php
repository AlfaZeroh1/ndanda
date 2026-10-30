<?php
/** TEST MODE ONLY: marks an order paid without M-Pesa so the full flow can be tested before Daraja keys exist. */
require __DIR__ . '/../includes/bootstrap.php';
if (!is_post()) json_out(['ok' => false], 405);
if (!(test_flag() && setting('allow_simulation') === '1' && setting('mpesa_env') !== 'live')) {
    json_out(['ok' => false, 'message' => 'Simulation is disabled.'], 403);
}
$user = current_user();
$order = $user ? row('SELECT * FROM orders WHERE reference = ?', [input('ref')]) : null;
if (!$order || ((int) $order['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin')) json_out(['ok' => false, 'message' => 'Order not found.'], 404);

$receipt = 'SIM' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));
insert('mpesa_transactions', [
    'order_id' => $order['id'], 'type' => 'simulated', 'phone' => $order['phone'] ?: $user['phone'],
    'amount' => $order['amount'], 'account_ref' => $order['reference'], 'result_code' => 0,
    'result_desc' => 'Simulated payment (test mode)', 'mpesa_receipt' => $receipt, 'status' => 'success', 'is_test' => 1,
]);
mark_order_paid((int) $order['id'], $receipt);
json_out(['ok' => true, 'receipt' => $receipt]);
