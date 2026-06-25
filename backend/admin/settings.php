<?php
// change password
require_once __DIR__ . '/_layout.php';
require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $row = db()->prepare("SELECT password_hash FROM users WHERE id=?");
    $row->execute([$user['id']]);
    $hash = $row->fetch()['password_hash'] ?? '';

    if (!password_verify($current, $hash)) {
        flash('Your current password is incorrect.', 'err');
    } elseif (strlen($new) < 8) {
        flash('New password must be at least 8 characters.', 'err');
    } elseif ($new !== $confirm) {
        flash('New password and confirmation do not match.', 'err');
    } else {
        $upd = db()->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $upd->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        flash('Password updated.');
    }
    redirect('/admin/settings.php');
}

admin_header('Settings');
?>
<div class="panel" style="padding:24px;max-width:560px">
  <h2 style="margin-top:0;font-size:1.1rem">Account</h2>
  <p class="muted">Signed in as <strong style="color:var(--ink)"><?= e($user['email']) ?></strong></p>

  <h3 style="margin:24px 0 10px;font-size:.95rem">Change password</h3>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label>Current password</label><input type="password" name="current" required></div>
    <div class="field"><label>New password</label><input type="password" name="new" required></div>
    <div class="field"><label>Confirm new password</label><input type="password" name="confirm" required></div>
    <button class="btn btn--primary">Update password</button>
  </form>
</div>

<div class="panel" style="padding:24px;max-width:560px;margin-top:22px">
  <h2 style="margin-top:0;font-size:1.1rem">Email &amp; database</h2>
  <p class="muted">Email recipient, SMTP, and database credentials are set in <code style="color:var(--ink-soft)">backend/config.php</code> on the server. See the README for details.</p>
</div>
<?php admin_footer(); ?>
