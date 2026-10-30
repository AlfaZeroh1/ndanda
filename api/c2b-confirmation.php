<?php
/** C2B confirmation: a customer paid the Paybill directly. Match the account number to an order. */
define('NO_CSRF', true);
define('NO_SESSION', true);
require __DIR__ . '/../includes/bootstrap.php';
$raw = file_get_contents('php://input');
Mpesa::log('c2b_confirmation', $raw);
$ack = ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
if (!Mpesa::verifyCallbackKey()) json_out($ack);

$d = json_decode($raw, true) ?: [];
$receipt = $d['TransID'] ?? null;
if (!$receipt || val('SELECT id FROM mpesa_transactions WHERE mpesa_receipt = ?', [$receipt])) json_out($ack); // duplicate

$ref = strtoupper(trim($d['BillRefNumber'] ?? ''));
$order = $ref !== '' ? row('SELECT * FROM orders WHERE UPPER(reference) = ?', [$ref]) : null;
$amount = (float) ($d['TransAmount'] ?? 0);
$name = trim(($d['FirstName'] ?? '') . ' ' . ($d['MiddleName'] ?? '') . ' ' . ($d['LastName'] ?? ''));

insert('mpesa_transactions', [
    'order_id'      => $order['id'] ?? null,
    'type'          => 'c2b',
    'phone'         => substr((string) ($d['MSISDN'] ?? ''), 0, 20),
    'amount'        => $amount,
    'account_ref'   => substr($ref, 0, 40),
    'result_code'   => 0,
    'result_desc'   => $order ? 'Matched to order' : 'Unmatched – reconcile in admin',
    'mpesa_receipt' => $receipt,
    'trans_time'    => (string) ($d['TransTime'] ?? ''),
    'payer_name'    => substr(preg_replace('/\s+/', ' ', $name), 0, 120),
    'status'        => 'success',
    'raw_callback'  => $raw,
    'is_test'       => test_flag(),
]);

if ($order && $order['status'] !== 'paid' && $amount + 0.01 >= (float) $order['amount']) {
    mark_order_paid((int) $order['id'], $receipt, (string) ($d['MSISDN'] ?? ''));
}
json_out($ack);
