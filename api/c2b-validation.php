<?php
/** C2B validation: only accept Paybill payments whose account number is a known order (or allow all). */
define('NO_CSRF', true);
define('NO_SESSION', true);
require __DIR__ . '/../includes/bootstrap.php';
$raw = file_get_contents('php://input');
Mpesa::log('c2b_validation', $raw);
$d = json_decode($raw, true) ?: [];
if (!Mpesa::verifyCallbackKey()) json_out(['ResultCode' => 'C2B00016', 'ResultDesc' => 'Rejected']);

// Accept by default so customers paying for other things are never blocked;
// reject only when strict mode is on and the account number is unknown.
if (setting('mpesa_c2b_strict') === '1') {
    $order = row("SELECT id FROM orders WHERE reference = ? AND status <> 'paid'", [strtoupper(trim($d['BillRefNumber'] ?? ''))]);
    if (!$order) json_out(['ResultCode' => 'C2B00012', 'ResultDesc' => 'Invalid Account Number']);
}
json_out(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
