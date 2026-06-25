<?php
// add/edit a book
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_upload.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$book = null;
if ($id) {
    $stmt = db()->prepare("SELECT * FROM books WHERE id=?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if (!$book) { http_response_code(404); admin_header('Not found'); echo '<p class="muted">Book not found.</p>'; admin_footer(); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = fn($k) => trim($_POST[$k] ?? '');
    $title = $f('title');
    $now = gmdate('Y-m-d H:i:s');

    $errors = [];
    if ($title === '') $errors[] = 'A title is required.';

    $cover = $book['cover_image'] ?? '';
    if (!empty($_FILES['cover']['name'])) {
        [$ok, $res] = handle_upload('cover');
        if ($ok) { $cover = $res; } else { $errors[] = $res; }
    }

    $fields = [
        'title' => $title,
        'subtitle' => $f('subtitle'),
        'description' => $f('description'),
        'body_html' => $_POST['body_html'] ?? '',
        'cover_image' => $cover,
        'buy_url' => $f('buy_url') ?: 'https://northwindpress.example/shop/',
        'detail_url' => $f('detail_url'),
        'status' => in_array($f('status'), ['published','coming_soon','draft'], true) ? $f('status') : 'published',
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'sort_order' => (int) $f('sort_order'),
        'meta_foreword' => $f('meta_foreword'),
        'meta_format' => $f('meta_format'),
        'meta_price' => $f('meta_price'),
        'meta_edition' => $f('meta_edition'),
        'meta_genre' => $f('meta_genre'),
        'meta_themes' => $f('meta_themes'),
        'updated_at' => $now,
    ];

    if ($errors) {
        foreach ($errors as $er) flash($er, 'err');
    } else {
        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k=:$k", array_keys($fields)));
            $stmt = db()->prepare("UPDATE books SET $set WHERE id=:id");
            $stmt->execute($fields + ['id' => $id]);
            flash('Book updated.');
        } else {
            $fields['slug'] = unique_slug($title, 'books');
            $fields['created_at'] = $now;
            $cols = implode(', ', array_keys($fields));
            $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($fields)));
            $stmt = db()->prepare("INSERT INTO books ($cols) VALUES ($ph)");
            $stmt->execute($fields);
            $id = (int) db()->lastInsertId();
            flash('Book created.');
        }
        redirect('/admin/book-edit.php?id=' . $id);
    }
}

$v = fn($k) => e($book[$k] ?? '');
$sel = fn($k, $val) => (($book[$k] ?? '') === $val ? ' selected' : '');
admin_header($id ? 'Edit book' : 'New book');
?>
<p><a class="btn btn--sm" href="/admin/books.php">← All books</a>
   <?php if ($id): ?><a class="btn btn--sm" href="/books/<?= $v('slug') ?>" target="_blank" rel="noopener">View ↗</a><?php endif; ?></p>

<form class="form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="field"><label>Title</label><input type="text" name="title" value="<?= $v('title') ?>" required></div>
  <div class="field"><label>Subtitle / tagline</label><input type="text" name="subtitle" value="<?= $v('subtitle') ?>"></div>
  <div class="field"><label>Short description <span class="muted">(shown on the books page card)</span></label><textarea name="description"><?= $v('description') ?></textarea></div>

  <div class="field">
    <label>Cover image</label>
    <?php if (!empty($book['cover_image'])): ?><p><img src="<?= $v('cover_image') ?>" class="cover-thumb" style="width:90px;height:auto"></p><?php endif; ?>
    <input type="file" name="cover" accept="image/*">
    <p class="field__hint">Recommended: a portrait book cover (e.g. 600×900). Leave empty to keep the current image.</p>
  </div>

  <div class="grid-2">
    <div class="field"><label>Status</label>
      <select name="status">
        <option value="published"<?= $sel('status','published') ?>>Published</option>
        <option value="coming_soon"<?= $sel('status','coming_soon') ?>>Coming soon</option>
        <option value="draft"<?= $sel('status','draft') ?>>Draft (hidden)</option>
      </select>
    </div>
    <div class="field"><label>Sort order <span class="muted">(lower = first)</span></label><input type="text" name="sort_order" value="<?= e($book['sort_order'] ?? 0) ?>"></div>
  </div>

  <div class="field"><label><input type="checkbox" name="featured" value="1"<?= ((int)($book['featured'] ?? 0)===1)?' checked':'' ?>> Feature this book (large spotlight layout on the books page)</label></div>

  <div class="field"><label>Buy / order URL</label><input type="url" name="buy_url" value="<?= e($book['buy_url'] ?? 'https://northwindpress.example/shop/') ?>"></div>

  <div class="field">
    <label>Custom detail page <span class="muted">(optional)</span></label>
    <input type="text" name="detail_url" value="<?= e($book['detail_url'] ?? '') ?>" placeholder="/through-the-storm/">
    <p class="field__hint">Leave blank to use this book's automatic detail page (<code>/books/<?= e($book['slug'] ?? 'slug') ?></code>). Set a path here to send “Discover the Book” to a bespoke page instead — e.g. the flagship's hand-designed page.</p>
  </div>

  <h3 style="margin:26px 0 6px;font-size:1rem;color:var(--ink-soft)">Detail metadata <span class="muted" style="font-weight:400">— each filled field shows as a labelled cell on the page</span></h3>
  <div class="grid-2">
    <div class="field"><label>Foreword</label><input type="text" name="meta_foreword" value="<?= $v('meta_foreword') ?>"></div>
    <div class="field"><label>Format</label><input type="text" name="meta_format" value="<?= $v('meta_format') ?>" placeholder="Paperback"></div>
    <div class="field"><label>Price</label><input type="text" name="meta_price" value="<?= $v('meta_price') ?>" placeholder="USD 18"></div>
    <div class="field"><label>Edition</label><input type="text" name="meta_edition" value="<?= $v('meta_edition') ?>" placeholder="First · May 2026"></div>
    <div class="field"><label>Genre</label><input type="text" name="meta_genre" value="<?= $v('meta_genre') ?>"></div>
    <div class="field"><label>Themes</label><input type="text" name="meta_themes" value="<?= $v('meta_themes') ?>"></div>
  </div>

  <div class="field">
    <label>Long-form detail content <span class="muted">(optional — appears on the book's own page)</span></label>
    <input type="hidden" name="body_html" id="body_html" value="<?= $v('body_html') ?>">
    <div id="editor" style="background:#fff"></div>
  </div>

  <div class="btn-row">
    <button class="btn btn--primary" type="submit">Save</button>
    <a class="btn" href="/admin/books.php">Cancel</a>
  </div>
</form>

<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
  var quill = new Quill('#editor', { theme: 'snow', modules: { toolbar: [
    [{ header: [2, 3, false] }], ['bold','italic','link','blockquote'],
    [{ list:'ordered' },{ list:'bullet' }], ['clean']
  ]}});
  var hidden = document.getElementById('body_html');
  quill.root.innerHTML = hidden.value;
  quill.root.style.minHeight = '260px';
  quill.root.style.fontSize = '16px';
  document.querySelector('form').addEventListener('submit', function () { hidden.value = quill.root.innerHTML; });
</script>
<?php admin_footer(); ?>
