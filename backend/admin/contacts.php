<?php
// contacts list
require_once __DIR__ . '/_layout.php';
require_login();

// row actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id && $action === 'read') {
        $s = db()->prepare("UPDATE contacts SET status='read' WHERE id=?"); $s->execute([$id]);
        flash('Marked as read.');
    } elseif ($id && $action === 'delete') {
        $s = db()->prepare("DELETE FROM contacts WHERE id=?"); $s->execute([$id]);
        flash('Message deleted.');
    }
    redirect('/admin/contacts.php');
}

$rows = db()->query("SELECT * FROM contacts ORDER BY id DESC")->fetchAll();

admin_header('Contacts');
?>
<div class="toolbar">
  <span class="muted"><?= count($rows) ?> message(s)</span>
</div>
<div class="panel">
  <?php if ($rows): ?>
  <table>
    <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Status</th><th>Received</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['name']) ?></strong><br><span class="muted"><?= e($r['email']) ?></span></td>
        <td><?= e($r['subject']) ?></td>
        <td class="msg-cell"><?= e(mb_strimwidth($r['message'], 0, 90, '…')) ?></td>
        <td><?php if ($r['status']==='new'): ?><span class="badge badge--new">new</span><?php else: ?><span class="badge badge--muted"><?= e($r['status']) ?></span><?php endif; ?></td>
        <td class="muted"><?= e(readable_date($r['created_at'])) ?></td>
        <td class="t-actions"><a class="btn btn--sm" href="/admin/contact-view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty">No messages yet. Submissions from the website contact form will appear here.</div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
