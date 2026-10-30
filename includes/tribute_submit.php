<?php
/** Handles a tribute submitted from the tribute page or the shared form link. */
function handle_tribute_submission(array $f, string $returnUrl, ?string $errorUrl = null): void
{
    $errorUrl = $errorUrl ?? $returnUrl;
    $name = trim((string) ($_POST['author_name'] ?? ''));
    $msg = trim((string) ($_POST['message'] ?? ''));
    if (($_POST['website'] ?? '') !== '') redirect($returnUrl); // honeypot
    if ($name === '' || mb_strlen($msg) < 3) {
        flash('danger', 'Please enter your name and a message.');
        redirect($errorUrl);
    }
    if (!throttle('tribute_' . $f['id'], 20)) {
        flash('warning', 'Please wait a few seconds before posting another tribute.');
        redirect($errorUrl);
    }
    try {
        $photo = upload_file($_FILES['photo'] ?? null, 'f' . $f['id'] . '/tributes');
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
        redirect($errorUrl);
    }
    $status = $f['moderate_tributes'] ? 'pending' : 'approved';
    insert('tributes', [
        'funeral_id'   => $f['id'],
        'author_name'  => mb_substr($name, 0, 120),
        'relationship' => mb_substr(trim((string) ($_POST['relationship'] ?? '')), 0, 80) ?: null,
        'location'     => mb_substr(trim((string) ($_POST['location'] ?? '')), 0, 120) ?: null,
        'email'        => filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
        'message'      => mb_substr($msg, 0, 5000),
        'photo'        => $photo,
        'status'       => $status,
        'is_test'      => test_flag(),
    ]);
    $owner = row('SELECT email, name FROM users WHERE id = ?', [$f['user_id']]);
    if ($owner && $status === 'pending') {
        send_mail($owner['email'], 'New tribute for ' . $f['deceased_name'], '<p>' . e($name) . ' has written a tribute. <a href="' . e(url('account/funeral.php?id=' . $f['id'] . '&tab=tributes')) . '">Review and approve it</a>.</p>');
    }
    flash('success', $status === 'pending' ? 'Thank you. Your tribute has been sent to the family and will appear once approved.' : 'Thank you. Your tribute has been posted.');
    redirect($returnUrl);
}
