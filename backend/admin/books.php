<?php
// books list
require_once __DIR__ . '/_layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        db()->prepare("DELETE FROM books WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
        flash('Book deleted.');
    }
    redirect('/admin/books.php');
}

$rows = db()->query("SELECT * FROM books ORDER BY sort_order ASC, id ASC")->fetchAll();

admin_header('Books');
?>
<div class="toolbar">
  <span class="muted"><?= count($rows) ?> book(s)</span>
  <a class="btn btn--primary" href="/admin/book-edit.php">+ New book</a>
</div>
<div class="panel">
  <?php if ($rows): ?>
  <table>
    <thead><tr><th></th><th>Title</th><th>Status</th><th>Order</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php if ($r['cover_image']): ?><img class="cover-thumb" src="<?= e($r['cover_image']) ?>" alt=""><?php endif; ?></td>
        <td><strong><?= e($r['title']) ?></strong><br><span class="muted"><?= e($r['subtitle']) ?></span><?= ((int)$r['featured']===1)?' <span class="badge badge--new">featured</span>':'' ?></td>
        <td><?php if ($r['status']==='published'): ?><span class="badge badge--green">published</span><?php elseif ($r['status']==='coming_soon'): ?><span class="badge badge--muted">coming soon</span><?php else: ?><span class="badge badge--draft">draft</span><?php endif; ?></td>
        <td class="muted"><?= (int)$r['sort_order'] ?></td>
        <td class="t-actions">
          <a class="btn btn--sm" href="/books/<?= e($r['slug']) ?>" target="_blank" rel="noopener">View</a>
          <a class="btn btn--sm" href="/admin/book-edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this book?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn--sm btn--danger">Delete</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty">No books yet. Click “New book” to add one.</div>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
