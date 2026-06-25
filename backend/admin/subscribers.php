<?php
// subscribers list
require_once __DIR__ . '/_layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $name  = trim($_POST['name'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $exists = db()->prepare("SELECT id FROM subscribers WHERE email=?");
            $exists->execute([$email]);
            if ($exists->fetch()) {
                flash('That email is already on the list.', 'err');
            } else {
                $s = db()->prepare("INSERT INTO subscribers (email,name,source,status,created_at) VALUES (?,?,?,?,?)");
                $s->execute([$email, $name, 'manual', 'active', gmdate('Y-m-d H:i:s')]);
                flash('Subscriber added.');
            }
        } else {
            flash('Please enter a valid email.', 'err');
        }
    } elseif ($action === 'delete') {
        $s = db()->prepare("DELETE FROM subscribers WHERE id=?"); $s->execute([(int)($_POST['id'] ?? 0)]);
        flash('Subscriber removed.');
    }
    redirect('/admin/subscribers.php');
}

$rows = db()->query("SELECT * FROM subscribers ORDER BY id DESC")->fetchAll();
$active = count(array_filter($rows, fn($r) => $r['status'] === 'active'));

admin_header('Subscribers');
?>
<div class="toolbar">
  <span class="muted"><?= $active ?> active · <?= count($rows) ?> total</span>
  <a class="btn btn--sm" href="/admin/subscribers-export.php">Export CSV</a>
</div>

<div class="panel" style="padding:18px 20px;margin-bottom:22px">
  <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="field" style="margin:0;flex:1;min-width:220px"><label>Add email</label><input type="email" name="email" required placeholder="name@example.com"></div>
    <div class="field" style="margin:0;flex:1;min-width:180px"><label>Name (optional)</label><input type="text" name="name"></div>
    <button class="btn btn--primary">Add subscriber</button>
  </form>
</div>

<div class="panel">
  <?php if ($rows): ?>
  <table>
    <thead><tr><th>Email</th><th>Name</th><th>Source</th><th>Status</th><th>Joined</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['email']) ?></td>
        <td><?= e($r['name']) ?: '<span class="muted">—</span>' ?></td>
        <td class="muted"><?= e($r['source']) ?></td>
        <td><?php if ($r['status']==='active'): ?><span class="badge badge--green">active</span><?php else: ?><span class="badge badge--muted"><?= e($r['status']) ?></span><?php endif; ?></td>
        <td class="muted"><?= e(readable_date($r['created_at'])) ?></td>
        <td class="t-actions">
          <form method="post" onsubmit="return confirm('Remove this subscriber?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn--sm btn--danger">Remove</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty">No subscribers yet. Signups from the website newsletter form will appear here.</div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
