<?php
// single book detail page
require_once __DIR__ . '/lib/layout.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM books WHERE slug = ? AND status <> 'draft'");
$stmt->execute([$slug]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    render_header(['title' => 'Not found — Adrian Cole', 'noindex' => true]);
    echo '<main class="section"><div class="container container--narrow"><h1 class="display">Book not found</h1><p class="body"><a href="/books" class="link-arrow">Back to the library</a></p></div></main>';
    render_footer();
    exit;
}

// custom detail page (e.g. flagship) wins over the generic layout — 301 so search
// engines consolidate ranking signals onto the canonical destination.
if (trim($book['detail_url']) !== '') {
    header('Location: ' . $book['detail_url'], true, 301);
    exit;
}

$canonical = site_url() . '/books/' . $book['slug'];
$ogImage = $book['cover_image'] !== '' ? $book['cover_image'] : '/assets/img/book-through-the-storm.jpg';

$structured = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Book',
            'name' => $book['title'],
            'author' => ['@type' => 'Person', 'name' => 'Adrian Cole', 'url' => site_url() . '/'],
            'publisher' => ['@type' => 'Organization', 'name' => 'Northwind Press'],
            'image' => site_url() . $ogImage,
            'description' => $book['description'],
            'url' => $canonical,
            'inLanguage' => 'en',
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_url() . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Books', 'item' => site_url() . '/books'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $book['title'], 'item' => $canonical],
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

render_header([
    'title' => $book['title'] . ' — Adrian Cole',
    'description' => $book['description'],
    'active' => 'books.html',
    'ogType' => 'book',
    'ogImage' => $ogImage,
    'canonical' => $canonical,
    'structuredData' => $structured,
]);

$meta = [];
if ($book['meta_foreword']) $meta[] = ['Foreword', $book['meta_foreword']];
if ($book['meta_format'])   $meta[] = ['Format', $book['meta_format']];
if ($book['meta_price'])    $meta[] = ['Price', $book['meta_price']];
if ($book['meta_edition'])  $meta[] = ['Edition', $book['meta_edition']];
if ($book['meta_genre'])    $meta[] = ['Genre', $book['meta_genre']];
if ($book['meta_themes'])   $meta[] = ['Themes', $book['meta_themes']];
?>
<main>
<div class="container" style="padding-top:clamp(96px,12vh,150px)">
  <p class="eyebrow" data-reveal><a href="/books" class="post__back">← The Library</a></p>

  <article class="book-feature" style="padding-top:clamp(18px,3vw,30px)">
    <div class="split">
      <div class="spotlight__cover" data-reveal="scale">
        <span class="glow" aria-hidden="true"></span>
        <img src="<?= e($book['cover_image']) ?>" alt="<?= e($book['title']) ?> by Adrian Cole" loading="lazy" />
      </div>
      <div>
        <h1 class="headline-lg" data-reveal data-reveal-delay="1"><?= e($book['title']) ?></h1>
        <?php if (trim($book['subtitle']) !== ''): ?>
        <p class="spotlight__sub" data-reveal data-reveal-delay="1"><?= e($book['subtitle']) ?></p>
        <?php endif; ?>
        <p class="body" data-reveal data-reveal-delay="2"><?= e($book['description']) ?></p>
        <?php if ($meta): ?>
        <div class="bookmeta" data-reveal data-reveal-delay="2">
          <?php foreach ($meta as $m): ?>
          <div><div class="bookmeta__k"><?= e($m[0]) ?></div><div class="bookmeta__v"><?= e($m[1]) ?></div></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:1rem;flex-wrap:wrap" data-reveal data-reveal-delay="3">
          <a href="<?= e($book['buy_url']) ?>" target="_blank" rel="noopener" class="btn btn--primary">Order your copy <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
      </div>
    </div>
  </article>

  <?php if (trim($book['body_html']) !== ''): ?>
  <section class="section" style="padding-top:0">
    <div class="container container--narrow">
      <div class="post__body body" data-reveal>
        <?= $book['body_html'] /* admin content */ ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
</div>

<section class="section bg-surface">
  <div class="container container--narrow newsletter">
    <p class="eyebrow eyebrow--center" data-reveal>Be the First to Know</p>
    <h2 class="headline-md sec-head__title" data-reveal data-reveal-delay="1" style="margin-top:1rem">New releases, signed editions<br>and reflections worth reading</h2>
    <form class="form-inline" data-newsletter data-reveal data-reveal-delay="2" novalidate>
      <input type="email" name="email" placeholder="Your email address" aria-label="Email address" required />
      <button type="submit" class="btn btn--primary">Subscribe</button>
    </form>
  </div>
</section>
</main>
<?php render_footer(); ?>
