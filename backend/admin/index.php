<?php
// dashboard
require_once __DIR__ . '/_layout.php';
require_login();

$d = db();
$newContacts   = (int) $d->query("SELECT COUNT(*) c FROM contacts WHERE status='new'")->fetch()['c'];
$totalContacts = (int) $d->query("SELECT COUNT(*) c FROM contacts")->fetch()['c'];
$subscribers   = (int) $d->query("SELECT COUNT(*) c FROM subscribers WHERE status='active'")->fetch()['c'];
$published     = (int) $d->query("SELECT COUNT(*) c FROM posts WHERE status='published'")->fetch()['c'];
$drafts        = (int) $d->query("SELECT COUNT(*) c FROM posts WHERE status='draft'")->fetch()['c'];
$books         = (int) $d->query("SELECT COUNT(*) c FROM books")->fetch()['c'];

$recent = $d->query("SELECT id, name, subject, status, created_at FROM contacts ORDER BY id DESC LIMIT 5")->fetchAll();

admin_header('Dashboard');
?>
<div class="cards">
  <div class="card"><div class="card__n"><?= $newContacts ?></div><div class="card__l">New messages · <a href="/admin/contacts.php">view all</a></div></div>
  <div class="card"><div class="card__n"><?= $subscribers ?></div><div class="card__l">Newsletter subscribers · <a href="/admin/subscribers.php">view</a></div></div>
  <div class="card"><div class="card__n"><?= $published ?></div><div class="card__l"><?= $drafts ?> draft(s) · <a href="/admin/posts.php">blog posts</a></div></div>
  <div class="card"><div class="card__n"><?= $books ?></div><div class="card__l">Books listed · <a href="/admin/books.php">manage</a></div></div>
</div>

<div class="toolbar"><h2 style="margin:0;font-size:1.05rem">Recent messages</h2><a class="btn btn--sm" href="/admin/contacts.php">All contacts</a></div>
<div class="panel">
  <?php if ($recent): ?>
  <table>
    <thead><tr><th>From</th><th>Subject</th><th>Status</th><th>Received</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($recent as $r): ?>
      <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['subject']) ?></td>
        <td><?php if ($r['status']==='new'): ?><span class="badge badge--new">new</span><?php else: ?><span class="badge badge--muted"><?= e($r['status']) ?></span><?php endif; ?></td>
        <td class="muted"><?= e(readable_date($r['created_at'])) ?></td>
        <td class="t-actions"><a class="btn btn--sm" href="/admin/contact-view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty">No messages yet.</div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
