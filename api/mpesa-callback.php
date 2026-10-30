<?php
/** Daraja STK push result callback (CallBackURL). */
define('NO_CSRF', true);
define('NO_SESSION', true);
require __DIR__ . '/../includes/bootstrap.php';

$raw = file_get_contents('php://input');
Mpesa::log('stk_callback', $raw);
$ack = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];

if (!Mpesa::verifyCallbackKey()) {
    Mpesa::log('stk_callback_rejected', 'bad key from ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    json_out($ack);
}

$data = json_decode($raw, true);
$cb = $data['Body']['stkCallback'] ?? null;
if (!$cb || empty($cb['CheckoutRequestID'])) json_out($ack);

$tx = row('SELECT * FROM mpesa_transactions WHERE checkout_request_id = ?', [$cb['CheckoutRequestID']]);
if (!$tx) json_out($ack);

$meta = [];
foreach ($cb['CallbackMetadata']['Item'] ?? [] as $item) {
    if (isset($item['Name'])) $meta[$item['Name']] = $item['Value'] ?? null;
}
try {
    Mpesa::applyStkResult($tx, $cb['ResultCode'] ?? 1, (string) ($cb['ResultDesc'] ?? ''), $meta, $raw);
} catch (Throwable $e) {
    Mpesa::log('stk_callback_error', $e->getMessage());
}
json_out($ack);
