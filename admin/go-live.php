<?php
/**
 * Go Live: removes test data and switches the platform to production.
 *  - "Remove test data" deletes every record flagged is_test (the sample data plus anything
 *    created while test mode was on), with their uploads.
 *  - "Full reset" additionally wipes ALL funerals, orders, transactions, messages and planner users.
 * Settings, services/prices, CMS pages and admin users are always kept.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
$me = require_admin();

function test_counts(): array
{
    return [
        'Planner accounts' => (int) val("SELECT COUNT(*) FROM users WHERE is_test = 1 AND role <> 'admin'"),
        'Funeral accounts' => (int) val('SELECT COUNT(*) FROM funerals WHERE is_test = 1'),
        'Tributes'         => (int) val('SELECT COUNT(*) FROM tributes WHERE is_test = 1'),
        'Orders'           => (int) val('SELECT COUNT(*) FROM orders WHERE is_test = 1'),
        'M-Pesa transactions' => (int) val('SELECT COUNT(*) FROM mpesa_transactions WHERE is_test = 1'),
        'Blog posts'       => (int) val('SELECT COUNT(*) FROM posts WHERE is_test = 1'),
        'Inbox messages'   => (int) val('SELECT COUNT(*) FROM messages WHERE is_test = 1'),
    ];
}

function delete_funeral_files(array $ids): void
{
    foreach ($ids as $id) {
        foreach ([UPLOAD_DIR . '/f' . (int) $id, UPLOAD_DIR . '/private/f' . (int) $id] as $dir) {
            rrmdir($dir);
            @rmdir($dir);
        }
    }
}

$report = null;
if (is_post()) {
    $mode = input('mode');
    if (!password_verify((string) ($_POST['password'] ?? ''), $me['password_hash'])) {
        flash('danger', 'Incorrect admin password.');
        redirect(url('admin/go-live.php'));
    }
    if (strtoupper(trim(input('confirm'))) !== ($mode === 'full' ? 'RESET EVERYTHING' : 'GO LIVE')) {
        flash('danger', 'Type the confirmation phrase exactly.');
        redirect(url('admin/go-live.php'));
    }
    $pdo = db();
    try {
        if ($mode === 'full') {
            $ids = array_column(rows('SELECT id FROM funerals'), 'id');
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['funerals', 'funeral_services', 'tributes', 'gallery', 'programme_events', 'order_of_service', 'prep_documents', 'prep_notes', 'prep_tasks', 'prep_budget', 'contributions', 'order_items', 'orders', 'mpesa_transactions', 'mpesa_logs', 'messages'] as $t) {
                $pdo->exec("TRUNCATE TABLE `$t`");
            }
            $pdo->exec("DELETE FROM users WHERE role <> 'admin'");
            $pdo->exec('DELETE FROM posts WHERE is_test = 1');
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            delete_funeral_files($ids);
            foreach (glob(UPLOAD_DIR . '/f*', GLOB_ONLYDIR) ?: [] as $d) { rrmdir($d); @rmdir($d); }
            foreach (glob(UPLOAD_DIR . '/private/f*', GLOB_ONLYDIR) ?: [] as $d) { rrmdir($d); @rmdir($d); }
            $report = 'Full reset complete: all funeral accounts, orders, transactions, logs, messages and planner users were removed.';
        } else {
            $ids = array_column(rows('SELECT id FROM funerals WHERE is_test = 1 OR user_id IN (SELECT id FROM users WHERE is_test = 1)'), 'id');
            $pdo->beginTransaction();
            // Remove uploaded images of test tributes on real funerals and test blog covers
            foreach (rows('SELECT photo FROM tributes WHERE is_test = 1 AND photo IS NOT NULL') as $r) delete_upload($r['photo']);
            foreach (rows('SELECT cover FROM posts WHERE is_test = 1 AND cover IS NOT NULL') as $r) delete_upload($r['cover']);
            q('DELETE FROM mpesa_transactions WHERE is_test = 1');
            q('DELETE FROM orders WHERE is_test = 1');
            if ($ids) q('DELETE FROM funerals WHERE id IN (' . implode(',', array_map('intval', $ids)) . ')');
            q('DELETE FROM tributes WHERE is_test = 1');
            q("DELETE FROM users WHERE is_test = 1 AND role <> 'admin'");
            q('DELETE FROM posts WHERE is_test = 1');
            q('DELETE FROM messages WHERE is_test = 1');
            q('DELETE FROM mpesa_logs');
            $pdo->commit();
            delete_funeral_files($ids);
            $report = 'Test data removed (' . count($ids) . ' funeral accounts and their content).';
        }
        set_setting('test_mode', '0');
        set_setting('allow_simulation', '0');
        if (input('switch_live')) {
            set_setting('mpesa_env', 'live');
            set_setting('mpesa_token_cache', '');
        }
        flash('success', $report . ' Test mode is now OFF' . (input('switch_live') ? ' and M-Pesa is set to LIVE.' : '.'));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        flash('danger', 'Clean-up failed: ' . $e->getMessage());
    }
    redirect(url('admin/go-live.php'));
}

if (input('reenable') === '1' && hash_equals(csrf_token(), (string) input('t'))) {
    set_setting('test_mode', '1');
    set_setting('allow_simulation', '1');
    flash('warning', 'Test mode re-enabled. New records will be flagged as test data.');
    redirect(url('admin/go-live.php'));
}

$counts = test_counts();
$totalTest = array_sum($counts);
admin_header('Go live', 'golive');
?>
<div class="row g-4">
  <div class="col-xl-5">
    <div class="admin-card card p-4 mb-4">
      <h2 class="h5"><i class="bi bi-rocket-takeoff text-gold"></i> Platform status</h2>
      <div class="d-flex gap-2 mb-3">
        <span class="badge p-2 <?= test_flag() ? 'text-bg-warning' : 'text-bg-success' ?>"><?= test_flag() ? 'TEST MODE' : 'LIVE' ?></span>
        <span class="badge p-2 text-bg-<?= setting('mpesa_env') === 'live' ? 'success' : 'secondary' ?>">M-Pesa <?= e(strtoupper(setting('mpesa_env'))) ?></span>
        <span class="badge p-2 text-bg-<?= Mpesa::isConfigured() ? 'success' : 'danger' ?>">Daraja keys <?= Mpesa::isConfigured() ? 'set' : 'missing' ?></span>
      </div>
      <p class="small text-muted">While test mode is on, every account, funeral, order, payment, tribute and message created is flagged as test data — so you can test freely and remove it all in one click.</p>
      <h3 class="h6 mt-3">Test records currently in the database</h3>
      <table class="table table-sm small mb-0"><tbody>
        <?php foreach ($counts as $l => $c): ?><tr><td><?= e($l) ?></td><td class="text-end fw-semibold"><?= $c ?></td></tr><?php endforeach; ?>
        <tr class="table-light"><td><strong>Total</strong></td><td class="text-end fw-bold"><?= $totalTest ?></td></tr>
      </tbody></table>
      <?php if (!test_flag()): ?><a href="?reenable=1&t=<?= e(csrf_token()) ?>" class="btn btn-sm btn-outline-warning mt-3" onclick="return confirm('Turn test mode back on?')">Re-enable test mode</a><?php endif; ?>
    </div>
    <div class="admin-card card p-4 small bg-light">
      <h3 class="h6">Before you go live</h3>
      <ul class="mb-0 ps-3">
        <li>Set <code>APP_DEBUG</code> to <code>false</code> and the correct <code>BASE_URL</code> (https) in <code>config.php</code>.</li>
        <li>Enter live Daraja credentials in <a href="settings.php?tab=mpesa">Settings → M-Pesa</a> and register C2B URLs.</li>
        <li>Update contacts, prices and page content.</li>
        <li>Take a database backup (cPanel → phpMyAdmin → Export).</li>
      </ul>
    </div>
  </div>
  <div class="col-xl-7">
    <form method="post" class="admin-card card p-4 mb-4 border-success" data-confirm="Remove all test data and go live?">
      <?= csrf_field() ?><input type="hidden" name="mode" value="test">
      <h2 class="h5 text-success"><i class="bi bi-check2-circle"></i> Remove test data & go live <span class="badge text-bg-success">recommended</span></h2>
      <p class="small">Deletes the <?= $totalTest ?> test records listed (including sample tributes, test payments and their uploads), clears M-Pesa API logs, turns test mode and payment simulation off. Keeps settings, services, pages, real data and admin users.</p>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="switch_live" value="1" id="sl1" <?= Mpesa::isConfigured() ? '' : 'disabled' ?>><label class="form-check-label small" for="sl1">Also switch M-Pesa to <strong>LIVE</strong> environment<?= Mpesa::isConfigured() ? '' : ' (enter Daraja keys first)' ?></label></div>
      <div class="row g-2">
        <div class="col-md-6"><input name="confirm" class="form-control" placeholder="Type: GO LIVE" required autocomplete="off"></div>
        <div class="col-md-6"><input name="password" type="password" class="form-control" placeholder="Your admin password" required autocomplete="current-password"></div>
      </div>
      <button class="btn btn-success mt-3 px-4"><i class="bi bi-rocket-takeoff"></i> Clean test data & go live</button>
    </form>

    <form method="post" class="admin-card card p-4 border-danger" data-confirm="This permanently deletes ALL funeral accounts, payments and planner users. Continue?">
      <?= csrf_field() ?><input type="hidden" name="mode" value="full">
      <h2 class="h5 text-danger"><i class="bi bi-exclamation-octagon"></i> Full reset (truncate everything)</h2>
      <p class="small">Use if you created test accounts while test mode was off. Truncates <strong>all</strong> funerals, tributes, galleries, programmes, preparations, contributions, orders, M-Pesa transactions and logs, messages and every non-admin user, and empties the uploads folder. Keeps settings, services & prices, CMS pages, non-test blog posts and admin users.</p>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="switch_live" value="1" id="sl2" <?= Mpesa::isConfigured() ? '' : 'disabled' ?>><label class="form-check-label small" for="sl2">Also switch M-Pesa to LIVE</label></div>
      <div class="row g-2">
        <div class="col-md-6"><input name="confirm" class="form-control" placeholder="Type: RESET EVERYTHING" required autocomplete="off"></div>
        <div class="col-md-6"><input name="password" type="password" class="form-control" placeholder="Your admin password" required autocomplete="current-password"></div>
      </div>
      <button class="btn btn-outline-danger mt-3 px-4"><i class="bi bi-trash3"></i> Full reset</button>
    </form>
  </div>
</div>
<?php admin_footer();
