<?php
// local dev router for the php built-in server, mirrors the .htaccess routing
//   php -S localhost:8000 backend/router.php   (run from project root)

$root = dirname(__DIR__);
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri  = rawurldecode($uri);

// serve a static file with the right content type
$serveFile = function (string $path) {
    if (!is_file($path)) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'css'=>'text/css','js'=>'application/javascript','json'=>'application/json',
        'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
        'webp'=>'image/webp','svg'=>'image/svg+xml','ico'=>'image/x-icon',
        'webmanifest'=>'application/manifest+json','txt'=>'text/plain','xml'=>'application/xml',
        'woff'=>'font/woff','woff2'=>'font/woff2','html'=>'text/html',
    ];
    if (isset($types[$ext])) header('Content-Type: ' . $types[$ext]);
    readfile($path);
    return true;
};

// static assets from the source folders
$assetMap = [
    '/css/'    => $root . '/src/css/',
    '/js/'     => $root . '/src/js/',
    '/assets/' => $root . '/assets/',
    '/uploads/'=> $root . '/backend/uploads/',
];
foreach ($assetMap as $prefix => $dir) {
    if (strpos($uri, $prefix) === 0) {
        $rel = substr($uri, strlen($prefix));
        if ($serveFile($dir . $rel)) return true;
        http_response_code(404); echo 'Not found'; return true;
    }
}

// legacy .html URLs -> clean URLs (the site moved to extensionless paths).
// Mirrors the redirect block in backend/deploy/.htaccess.
$legacy = [
    '/index.html'            => '/',
    '/about.html'            => '/about/',
    '/contact.html'          => '/contact/',
    '/through-the-storm.html' => '/through-the-storm/',
    '/blog.html'             => '/blog',
    '/books.html'            => '/books',
];
if (isset($legacy[$uri])) {
    header('Location: ' . $legacy[$uri], true, 301);
    return true;
}

// admin
if ($uri === '/admin' || $uri === '/admin/') { require $root . '/backend/admin/index.php'; return true; }
if (strpos($uri, '/admin/') === 0) {
    $file = $root . '/backend' . $uri;
    if (is_file($file)) { require $file; return true; }
    http_response_code(404); echo 'Not found'; return true;
}

// api  (/api/x -> backend/api/x.php)
if (strpos($uri, '/api/') === 0) {
    $file = $root . '/backend' . $uri . '.php';
    if (!is_file($file)) $file = $root . '/backend' . $uri;
    if (is_file($file)) { require $file; return true; }
    http_response_code(404); echo 'Not found'; return true;
}

// blog
if ($uri === '/blog' || $uri === '/blog/') { require $root . '/backend/blog.php'; return true; }
if (preg_match('#^/blog/([a-z0-9-]+)/?$#i', $uri, $m)) {
    $_GET['slug'] = $m[1]; require $root . '/backend/blog-post.php'; return true;
}

// books
if ($uri === '/books' || $uri === '/books/') { require $root . '/backend/books.php'; return true; }
if (preg_match('#^/books/([a-z0-9-]+)/?$#i', $uri, $m)) {
    $_GET['slug'] = $m[1]; require $root . '/backend/book.php'; return true;
}

// static pages from the _site build
$candidate = $root . '/_site' . ($uri === '/' ? '/index.html' : $uri);
if (is_dir($candidate)) $candidate = rtrim($candidate, '/') . '/index.html';
if ($serveFile($candidate)) return true;
if ($serveFile($candidate . '.html')) return true;

// site not built yet
if ($uri === '/' || $uri === '/index.html') {
    header('Content-Type: text/html');
    echo '<!doctype html><meta charset="utf-8"><title>Local review</title>'
       . '<body style="font-family:system-ui;background:#111;color:#eee;padding:40px;line-height:1.6">'
       . '<h1>Backend is running</h1>'
       . '<p>Run <code>npm run build</code> to see the static pages. The dynamic pages already work:</p><ul>'
       . '<li><a style="color:#d4af37" href="/blog">/blog</a></li>'
       . '<li><a style="color:#d4af37" href="/books">/books</a></li>'
       . '<li><a style="color:#d4af37" href="/admin/">/admin/</a></li>'
       . '</ul></body>';
    return true;
}

http_response_code(404);
header('Content-Type: text/html');
$notFound = $root . '/_site/404.html';
if (is_file($notFound)) { readfile($notFound); }
else { echo '<h1>404</h1><p><a href="/">Home</a></p>'; }
return true;
