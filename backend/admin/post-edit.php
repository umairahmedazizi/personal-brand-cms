<?php
// add/edit a post (quill editor)
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_upload.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$post = null;
if ($id) {
    $stmt = db()->prepare("SELECT * FROM posts WHERE id=?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) { http_response_code(404); admin_header('Not found'); echo '<p class="muted">Post not found.</p>'; admin_footer(); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title   = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body    = $_POST['body_html'] ?? '';
    $status  = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
    $now     = gmdate('Y-m-d H:i:s');

    $errors = [];
    if ($title === '') $errors[] = 'A title is required.';

    // cover image
    $cover = $post['cover_image'] ?? '';
    if (!empty($_FILES['cover']['name'])) {
        [$ok, $res] = handle_upload('cover');
        if ($ok) { $cover = $res; } else { $errors[] = $res; }
    }

    if ($errors) {
        foreach ($errors as $er) flash($er, 'err');
    } else {
        if ($id) {
            $slug = $post['slug']; // keep stable slug for existing URLs
            $publishedAt = $post['published_at'];
            if ($status === 'published' && $publishedAt === '') $publishedAt = $now;
            $stmt = db()->prepare("UPDATE posts SET title=?, excerpt=?, body_html=?, cover_image=?, status=?, published_at=?, updated_at=? WHERE id=?");
            $stmt->execute([$title, $excerpt, $body, $cover, $status, $publishedAt, $now, $id]);
            flash('Post updated.');
        } else {
            $slug = unique_slug($title, 'posts');
            $publishedAt = $status === 'published' ? $now : '';
            $stmt = db()->prepare("INSERT INTO posts (title, slug, excerpt, body_html, cover_image, status, published_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $slug, $excerpt, $body, $cover, $status, $publishedAt, $now, $now]);
            $id = (int) db()->lastInsertId();
            flash('Post created.');
        }
        redirect('/admin/post-edit.php?id=' . $id);
    }
}

$v = fn($k) => e($post[$k] ?? '');
admin_header($id ? 'Edit post' : 'New post');
?>
<p><a class="btn btn--sm" href="/admin/posts.php">← All posts</a>
   <?php if ($id && $post['status']==='published'): ?><a class="btn btn--sm" href="/blog/<?= $v('slug') ?>" target="_blank" rel="noopener">View live ↗</a><?php endif; ?></p>

<form class="form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="field">
    <label>Title</label>
    <input type="text" name="title" value="<?= $v('title') ?>" required>
  </div>
  <div class="field">
    <label>Excerpt <span class="muted">(short summary shown on the blog list)</span></label>
    <textarea name="excerpt" style="min-height:70px"><?= $v('excerpt') ?></textarea>
  </div>
  <div class="field">
    <label>Body</label>
    <input type="hidden" name="body_html" id="body_html" value="<?= $v('body_html') ?>">
    <div id="editor" style="background:#fff"></div>
    <p class="field__hint">Use the toolbar for headings, bold, links, quotes and lists — the styling matches the public site automatically.</p>
  </div>
  <div class="field">
    <label>Cover image <span class="muted">(optional)</span></label>
    <?php if (!empty($post['cover_image'])): ?><p><img src="<?= $v('cover_image') ?>" class="cover-thumb" style="width:120px;height:auto"></p><?php endif; ?>
    <input type="file" name="cover" accept="image/*">
  </div>
  <div class="field">
    <label>Status</label>
    <select name="status">
      <option value="draft"<?= ($post['status'] ?? 'draft')==='draft'?' selected':'' ?>>Draft (not visible on site)</option>
      <option value="published"<?= ($post['status'] ?? '')==='published'?' selected':'' ?>>Published (live on site)</option>
    </select>
  </div>
  <div class="btn-row">
    <button class="btn btn--primary" type="submit">Save</button>
    <a class="btn" href="/admin/posts.php">Cancel</a>
  </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
  var quill = new Quill('#editor', {
    theme: 'snow',
    modules: { toolbar: [
      [{ header: [2, 3, false] }],
      ['bold', 'italic', 'link', 'blockquote'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['clean']
    ]},
  });
  var hidden = document.getElementById('body_html');
  quill.root.innerHTML = hidden.value;
  quill.root.style.minHeight = '320px';
  quill.root.style.fontSize = '16px';
  document.querySelector('form').addEventListener('submit', function () {
    hidden.value = quill.root.innerHTML;
  });
</script>
<?php admin_footer(); ?>
