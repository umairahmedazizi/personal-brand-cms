<?php
// blog index
require_once __DIR__ . '/lib/layout.php';

$posts = db()->query(
    "SELECT title, slug, excerpt, published_at
     FROM posts WHERE status = 'published'
     ORDER BY published_at DESC, id DESC"
)->fetchAll();

$structured = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Blog',
            '@id' => site_url() . '/blog',
            'url' => site_url() . '/blog',
            'name' => 'Blog — Adrian Cole',
            'description' => 'Essays and reflections on leadership, resilience, and the art of being human by author Adrian Cole.',
            'inLanguage' => 'en',
            'author' => ['@type' => 'Person', 'name' => 'Adrian Cole', 'url' => site_url() . '/'],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_url() . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => site_url() . '/blog'],
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

render_header([
    'title' => 'Blog — Adrian Cole',
    'description' => 'Essays and reflections on leadership, resilience, and the art of being human by author Adrian Cole.',
    'active' => 'blog.html',
    'canonical' => site_url() . '/blog',
    'structuredData' => $structured,
]);
?>
<main>
<section class="page-hero">
  <div class="container">
    <div class="page-hero__inner" style="max-width:920px">
      <p class="eyebrow" data-reveal>The Journal</p>
      <h1 class="display page-hero__title" data-reveal data-reveal-delay="1">Blog</h1>
      <p class="lead page-hero__intro" data-reveal data-reveal-delay="2">Essays and reflections on leadership, resilience, and the art of being human — written between the books.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <?php if ($posts): ?>
    <div class="post-grid">
      <?php foreach ($posts as $p): ?>
      <article class="post-card" data-reveal>
        <p class="post-card__date"><time datetime="<?= e(iso_date($p['published_at'])) ?>"><?= e(readable_date($p['published_at'])) ?></time></p>
        <h2 class="post-card__title"><a href="/blog/<?= e($p['slug']) ?>"><?= e($p['title']) ?></a></h2>
        <p class="post-card__desc"><?= e($p['excerpt']) ?></p>
        <a href="/blog/<?= e($p['slug']) ?>" class="link-arrow">Read more <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="body">New reflections are on the way — check back soon.</p>
    <?php endif; ?>
  </div>
</section>
</main>
<?php render_footer(); ?>
