<?php
// admin shell: sidebar + header. own css, public styles not loaded here
require_once __DIR__ . '/../lib/auth.php';

// one-shot flash message
function flash(string $msg, string $type = 'ok'): void
{
    auth_start();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function admin_nav_item(string $href, string $label, string $current): string
{
    $file = basename(parse_url($href, PHP_URL_PATH));
    $active = ($file === $current) ? ' class="is-active"' : '';
    return "<a href=\"$href\"$active>" . e($label) . '</a>';
}

function admin_header(string $pageTitle): void
{
    auth_start();
    $user = current_user();
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');

    // sidebar badge count
    $newContacts = (int) db()->query("SELECT COUNT(*) c FROM contacts WHERE status='new'")->fetch()['c'];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title><?= e($pageTitle) ?> · Admin</title>
<link rel="icon" type="image/png" href="/assets/img/favicon-32.png" />
<link rel="stylesheet" href="/admin/admin.css" />
</head>
<body>
<div class="adm">
  <aside class="adm__side">
    <div class="adm__brand">
      <span class="adm__brand-mark">Adrian Cole</span>
      <span class="adm__brand-sub">Admin Panel</span>
    </div>
    <nav class="adm__nav">
      <?= admin_nav_item('/admin/index.php', 'Dashboard', $current) ?>
      <?= admin_nav_item('/admin/contacts.php', 'Contacts' . ($newContacts ? " ($newContacts)" : ''), $current) ?>
      <?= admin_nav_item('/admin/subscribers.php', 'Subscribers', $current) ?>
      <?= admin_nav_item('/admin/posts.php', 'Blog Posts', $current) ?>
      <?= admin_nav_item('/admin/books.php', 'Books', $current) ?>
      <?= admin_nav_item('/admin/chatbot.php', 'AI Assistant', $current) ?>
      <?= admin_nav_item('/admin/settings.php', 'Settings', $current) ?>
    </nav>
    <div class="adm__side-foot">
      <a href="/" target="_blank" rel="noopener">View site ↗</a>
      <a href="/admin/logout.php">Sign out</a>
    </div>
  </aside>
  <main class="adm__main">
    <header class="adm__top">
      <h1><?= e($pageTitle) ?></h1>
      <span class="adm__who">Signed in as <?= e($user['name'] ?: $user['email']) ?></span>
    </header>
    <div class="adm__body">
    <?php
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $cls = $f['type'] === 'err' ? 'flash flash--err' : 'flash';
        echo '<div class="' . $cls . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function admin_footer(): void
{
    ?>
    </div>
  </main>
</div>
</body>
</html>
<?php
}
