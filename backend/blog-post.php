<?php
// single blog post
require_once __DIR__ . '/lib/layout.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    render_header(['title' => 'Not found — Adrian Cole', 'noindex' => true]);
    echo '<main class="section"><div class="container container--narrow"><h1 class="display">Post not found</h1><p class="body"><a href="/blog" class="link-arrow">Back to the journal</a></p></div></main>';
    render_footer();
    exit;
}

$canonical = site_url() . '/blog/' . $post['slug'];
$ogImage = $post['cover_image'] !== '' ? $post['cover_image'] : '/assets/img/hero-1280.jpg';

$structured = json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'BlogPosting',
            'headline' => $post['title'],
            'datePublished' => iso_date($post['published_at']),
            'dateModified' => iso_date($post['updated_at'] ?? $post['published_at']),
            'author' => ['@type' => 'Person', 'name' => 'Adrian Cole', 'url' => site_url() . '/'],
            'publisher' => ['@type' => 'Organization', 'name' => 'Northwind Press'],
            'image' => site_url() . $ogImage,
            'mainEntityOfPage' => $canonical,
            'url' => $canonical,
            'description' => $post['excerpt'],
            'inLanguage' => 'en',
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => site_url() . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => site_url() . '/blog'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $canonical],
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

render_header([
    'title' => $post['title'] . ' — Adrian Cole',
    'description' => $post['excerpt'],
    'active' => 'blog.html',
    'ogType' => 'article',
    'ogImage' => $ogImage,
    'canonical' => $canonical,
    'structuredData' => $structured,
]);
?>
<main class="section">
  <article class="container container--narrow post">
    <p class="eyebrow" data-reveal><a href="/blog" class="post__back">← The Journal</a></p>
    <h1 class="display post__title" data-reveal data-reveal-delay="1"><?= e($post['title']) ?></h1>
    <p class="muted post__meta" data-reveal data-reveal-delay="2">
      <time datetime="<?= e(iso_date($post['published_at'])) ?>"><?= e(readable_date($post['published_at'])) ?></time> · by Adrian Cole
    </p>

    <?php if (trim($post['cover_image']) !== ''): ?>
    <figure class="post__cover" data-reveal data-reveal-delay="2">
      <img src="<?= e($post['cover_image']) ?>" alt="<?= e($post['title']) ?>" />
    </figure>
    <?php endif; ?>

    <div class="post__body body" data-reveal data-reveal-delay="2">
      <?= $post['body_html'] /* admin content */ ?>
    </div>

    <div class="post__foot">
      <a href="/blog" class="link-arrow">More reflections <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>
  </article>

  <section class="section bg-surface" style="margin-top:clamp(48px,7vw,88px)">
    <div class="container container--narrow newsletter">
      <p class="eyebrow eyebrow--center" data-reveal>Stay Close to the Work</p>
      <h2 class="headline-md sec-head__title" data-reveal data-reveal-delay="1" style="margin-top:1rem">New reflections, delivered occasionally</h2>
      <form class="form-inline" data-newsletter data-reveal data-reveal-delay="2" novalidate>
        <input type="email" name="email" placeholder="Your email address" aria-label="Email address" required />
        <button type="submit" class="btn btn--primary">Subscribe</button>
      </form>
    </div>
  </section>
</main>
<?php render_footer(); ?>
