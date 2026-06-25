<?php
// books index
require_once __DIR__ . '/lib/layout.php';

$books = db()->query(
    "SELECT * FROM books WHERE status <> 'draft'
     ORDER BY sort_order ASC, id ASC"
)->fetchAll();

$itemListElements = [];
foreach ($books as $i => $b) {
    $url = trim($b['detail_url']) !== '' ? (site_url() . $b['detail_url']) : (site_url() . '/books/' . $b['slug']);
    $itemListElements[] = [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'url' => $url,
        'name' => $b['title'],
    ];
}

$structured = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'CollectionPage',
            '@id' => site_url() . '/books',
            'url' => site_url() . '/books',
            'name' => 'Books by Adrian Cole',
            'description' => 'Books by Adrian Cole — published by Northwind Press.',
            'inLanguage' => 'en',
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $itemListElements,
            ],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_url() . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Books', 'item' => site_url() . '/books'],
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

render_header([
    'title' => 'Books by Adrian Cole — Through the Storm, Leading in Practice, The Ladder',
    'description' => "Books by Adrian Cole — published by Northwind Press.",
    'active' => 'books.html',
    'ogTitle' => 'Books by Adrian Cole',
    'ogImage' => '/assets/img/book-through-the-storm.jpg',
    'canonical' => site_url() . '/books',
    'structuredData' => $structured,
]);
?>
<main>
<section class="page-hero">
  <div class="container">
    <div class="page-hero__inner" style="max-width:920px">
      <p class="eyebrow" data-reveal>The Library</p>
      <h1 class="display page-hero__title" data-reveal data-reveal-delay="1">The Books</h1>
      <p class="lead page-hero__intro" data-reveal data-reveal-delay="2">Each one written from the inside out — from experience, from observation, from the conviction that an honest idea, clearly expressed, is worth more than a polished one that says nothing.</p>
    </div>
  </div>
</section>

<div class="container">
  <?php foreach ($books as $i => $b):
      $featured = (int) $b['featured'] === 1;
      $mediaLeft = ($i % 2 === 1);  // alternate sides
      $no = str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
      // flagship can point to a custom page, else the dynamic one
      $discover = trim($b['detail_url']) !== '' ? $b['detail_url'] : '/books/' . $b['slug'];
  ?>
  <article class="book-feature" id="<?= e($b['slug']) ?>">
    <div class="split<?= $mediaLeft ? ' split--media-left' : '' ?>">
      <?php if ($featured): ?>
      <div class="spotlight__cover" data-reveal="scale">
        <span class="glow" aria-hidden="true"></span>
        <img src="<?= e($b['cover_image']) ?>" alt="<?= e($b['title']) ?> by Adrian Cole" loading="lazy" />
      </div>
      <?php else: ?>
      <div class="book-cover" data-reveal="scale">
        <span class="glow" aria-hidden="true"></span>
        <span class="book-cover__no" aria-hidden="true"><?= e($no) ?></span>
        <img src="<?= e($b['cover_image']) ?>" alt="<?= e($b['title']) ?> by Adrian Cole" loading="lazy" />
      </div>
      <?php endif; ?>
      <div>
        <h2 class="headline-lg" data-reveal data-reveal-delay="1"><?= e($b['title']) ?></h2>
        <p class="spotlight__sub" data-reveal data-reveal-delay="1"><?= e($b['subtitle']) ?></p>
        <p class="body" data-reveal data-reveal-delay="2"><?= e($b['description']) ?></p>
        <div class="bookmeta" data-reveal data-reveal-delay="2">
          <?php
          $meta = [];
          if ($b['meta_foreword']) $meta[] = ['Foreword', $b['meta_foreword']];
          if ($b['meta_format'])   $meta[] = ['Format', $b['meta_format']];
          if ($b['meta_price'])    $meta[] = ['Price', $b['meta_price']];
          if ($b['meta_edition'])  $meta[] = ['Edition', $b['meta_edition']];
          if ($b['meta_genre'])    $meta[] = ['Genre', $b['meta_genre']];
          if ($b['meta_themes'])   $meta[] = ['Themes', $b['meta_themes']];
          if ($b['status'] === 'coming_soon') $meta[] = ['Status', 'Coming Soon'];
          foreach ($meta as $m): ?>
          <div><div class="bookmeta__k"><?= e($m[0]) ?></div><div class="bookmeta__v"><?= e($m[1]) ?></div></div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap" data-reveal data-reveal-delay="3">
          <?php if ($featured): ?>
          <a href="<?= e($discover) ?>" class="btn btn--primary">Discover the Book <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          <a href="<?= e($b['buy_url']) ?>" target="_blank" rel="noopener" class="btn btn--ghost">Order your copy</a>
          <?php else: ?>
          <a href="<?= e($discover) ?>" class="btn btn--ghost">Discover the Book</a>
          <a href="<?= e($b['buy_url']) ?>" target="_blank" rel="noopener" class="btn btn--primary">Order your copy</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </article>
  <?php endforeach; ?>
</div>

<section class="section">
  <div class="container">
    <div class="imprint" data-reveal>
      <img class="imprint__badge" src="/assets/img/publisher-logo.png" alt="Northwind Press — Northwind Press logo" loading="lazy" />
      <div>
        <p class="eyebrow" style="margin-bottom:0.8rem">Publishing Imprint</p>
        <p class="body">All titles are published under <b style="color:var(--ink-soft)">Northwind Press</b> — an independent imprint based in London. Worldwide delivery. Visit <a href="https://northwindpress.example" target="_blank" rel="noopener" class="text-gold">northwindpress.example</a> for orders.</p>
      </div>
    </div>
  </div>
</section>

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
