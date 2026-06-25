<?php
// view one message
require_once __DIR__ . '/_layout.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $s = db()->prepare("DELETE FROM contacts WHERE id=?"); $s->execute([$id]);
        flash('Message deleted.');
        redirect('/admin/contacts.php');
    }
    if ($action === 'replied') {
        $s = db()->prepare("UPDATE contacts SET status='replied' WHERE id=?"); $s->execute([$id]);
        flash('Marked as replied.');
        redirect('/admin/contact-view.php?id=' . $id);
    }
}

$stmt = db()->prepare("SELECT * FROM contacts WHERE id=?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { http_response_code(404); admin_header('Not found'); echo '<p class="muted">Message not found.</p>'; admin_footer(); exit; }

// mark read on open
if ($c['status'] === 'new') {
    db()->prepare("UPDATE contacts SET status='read' WHERE id=?")->execute([$id]);
    $c['status'] = 'read';
}

$mailto = 'mailto:' . rawurlencode($c['email'])
    . '?subject=' . rawurlencode('Re: ' . $c['subject']);

admin_header('Message from ' . $c['name']);
?>
<p><a class="btn btn--sm" href="/admin/contacts.php">← Back to contacts</a></p>
<div class="panel" style="padding:24px;max-width:760px">
  <table style="margin-bottom:18px">
    <tr><th style="width:120px">From</th><td><?= e($c['name']) ?></td></tr>
    <tr><th>Email</th><td><a href="<?= e($mailto) ?>" style="color:var(--gold)"><?= e($c['email']) ?></a></td></tr>
    <tr><th>Subject</th><td><?= e($c['subject']) ?></td></tr>
    <tr><th>Received</th><td><?= e(readable_date($c['created_at'])) ?> · <span class="badge badge--muted"><?= e($c['status']) ?></span></td></tr>
  </table>
  <div style="white-space:pre-wrap;line-height:1.7;color:var(--ink-soft);border-top:1px solid var(--line);padding-top:18px"><?= e($c['message']) ?></div>

  <div class="btn-row" style="margin-top:24px">
    <a class="btn btn--primary" href="<?= e($mailto) ?>">Reply by email</a>
    <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="replied"><button class="btn">Mark replied</button></form>
    <form method="post" style="display:inline" onsubmit="return confirm('Delete this message permanently?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn--danger">Delete</button></form>
  </div>
</div>
<?php admin_footer(); ?>
