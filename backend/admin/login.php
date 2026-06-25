<?php
require_once __DIR__ . '/../lib/auth.php';

auth_start();
if (current_user()) {
    redirect('/admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';
    if (auth_login($email, $pass)) {
        redirect('/admin/index.php');
    }
    $error = 'Incorrect email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>Sign in · Admin</title>
<link rel="icon" type="image/png" href="/assets/img/favicon-32.png" />
<link rel="stylesheet" href="/admin/admin.css" />
</head>
<body>
<div class="login">
  <form class="login__box" method="post" action="/admin/login.php">
    <h1 class="login__title">Adrian Cole</h1>
    <p class="login__sub">Admin panel — please sign in</p>
    <?php if ($error): ?><div class="flash flash--err"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus />
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />
    </div>
    <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center">Sign in</button>
  </form>
</div>
</body>
</html>
