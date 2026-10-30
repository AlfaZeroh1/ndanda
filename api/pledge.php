<?php
/** Public pledge form on the tribute page (harambee contributions). */
require __DIR__ . '/../includes/bootstrap.php';
if (!is_post()) redirect(url());
$f = row("SELECT * FROM funerals WHERE id = ? AND status = 'active' AND accept_pledges = 1", [(int) input('funeral_id')]);
if (!$f) redirect(url());
$name = trim(input('name'));
$amount = (float) input('amount');
if (input('website') !== '' || $name === '' || $amount <= 0) {
    flash('danger', 'Please enter your name and a pledge amount.');
    redirect(tribute_url($f) . '#contribute');
}
if (!throttle('pledge_' . $f['id'], 30)) {
    flash('warning', 'Please wait a moment before pledging again.');
    redirect(tribute_url($f) . '#contribute');
}
insert('contributions', [
    'funeral_id' => $f['id'], 'name' => mb_substr($name, 0, 120), 'phone' => mb_substr(input('phone'), 0, 30),
    'amount' => $amount, 'status' => 'pledged', 'source' => 'public', 'note' => mb_substr(input('note'), 0, 250),
]);
flash('success', 'Thank you, ' . $name . '. Your pledge has been recorded and the family has been notified.');
redirect(tribute_url($f) . '#contribute');
