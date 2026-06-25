<?php
// =====================================================================
//  Dynamic XML sitemap.
//  Generated from the database so newly published blog posts (and books)
//  appear automatically — no site rebuild or re-upload required.
//  Routed here by .htaccess:  /sitemap.xml -> backend/sitemap.php
// =====================================================================
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: application/xml; charset=UTF-8');

$base = site_url();

// Hand-curated canonical pages (clean URLs, no duplicates, no 404).
$urls = [
    ['loc' => '/',             'priority' => '1.0', 'changefreq' => 'monthly', 'lastmod' => null],
    ['loc' => '/about/',       'priority' => '0.8', 'changefreq' => 'yearly',  'lastmod' => null],
    ['loc' => '/books',        'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => null],
    ['loc' => '/blog',         'priority' => '0.7', 'changefreq' => 'weekly',  'lastmod' => null],
    ['loc' => '/contact/',     'priority' => '0.6', 'changefreq' => 'yearly',  'lastmod' => null],
];

// Dynamic entries from the database. Wrapped so a DB hiccup still yields a
// valid sitemap of the static pages rather than a 500 error.
try {
    // Books — use the custom detail_url when set (e.g. the flagship page),
    // otherwise the generic /books/<slug> route.
    $books = db()->query(
        "SELECT slug, detail_url, featured, updated_at
         FROM books WHERE status <> 'draft'
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll();
    foreach ($books as $b) {
        $loc = trim($b['detail_url']) !== '' ? $b['detail_url'] : '/books/' . $b['slug'];
        $urls[] = [
            'loc' => $loc,
            'priority' => ((int) $b['featured'] === 1) ? '0.9' : '0.7',
            'changefreq' => 'monthly',
            'lastmod' => iso_date((string) ($b['updated_at'] ?? '')),
        ];
    }

    // Published blog posts.
    $posts = db()->query(
        "SELECT slug, updated_at, published_at
         FROM posts WHERE status = 'published'
         ORDER BY published_at DESC, id DESC"
    )->fetchAll();
    foreach ($posts as $p) {
        $urls[] = [
            'loc' => '/blog/' . $p['slug'],
            'priority' => '0.6',
            'changefreq' => 'monthly',
            'lastmod' => iso_date((string) ($p['updated_at'] ?: $p['published_at'])),
        ];
    }
} catch (Throwable $e) {
    // Ignore — fall back to the static page list above.
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url>' . "\n";
    echo '    <loc>' . e($base . $u['loc']) . '</loc>' . "\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . e($u['lastmod']) . '</lastmod>' . "\n";
    }
    echo '    <changefreq>' . e($u['changefreq']) . '</changefreq>' . "\n";
    echo '    <priority>' . e($u['priority']) . '</priority>' . "\n";
    echo '  </url>' . "\n";
}
echo '</urlset>' . "\n";
