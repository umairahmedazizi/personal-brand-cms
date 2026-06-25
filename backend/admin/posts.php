<?php
// posts list
require_once __DIR__ . '/_layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare("DELETE FROM posts WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
        flash('Post deleted.');
    }
    redirect('/admin/posts.php');
}

$rows = db()->query("SELECT id, title, slug, status, published_at FROM posts ORDER BY published_at DESC, id DESC")->fetchAll();

admin_header('Blog Posts');
?>
<div class="toolbar">
  <span class="muted"><?= count($rows) ?> post(s)</span>
  <a class="btn btn--primary" href="/admin/post-edit.php">+ New post</a>
</div>
<div class="panel">
  <?php if ($rows): ?>
  <table>
    <thead><tr><th>Title</th><th>Status</th><th>Date</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['title']) ?></strong><br><span class="muted">/blog/<?= e($r['slug']) ?></span></td>
        <td><?php if ($r['status']==='published'): ?><span class="badge badge--green">published</span><?php else: ?><span class="badge badge--draft">draft</span><?php endif; ?></td>
        <td class="muted"><?= e(readable_date($r['published_at'])) ?></td>
        <td class="t-actions">
          <a class="btn btn--sm" href="/blog/<?= e($r['slug']) ?>" target="_blank" rel="noopener">View</a>
          <a class="btn btn--sm" href="/admin/post-edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this post?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn--sm btn--danger">Delete</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty">No posts yet. Click “New post” to write your first reflection.</div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
