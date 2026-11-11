<?php
require __DIR__ . '/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $pdo->prepare('SELECT * FROM users WHERE username = ?');
  $s->execute([trim($_POST['username'] ?? '')]);
  $u = $s->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']];
    header('Location: index.php'); exit;
  }
  $error = 'Wrong username or password.';
}
include 'header.php';
?>
<div class="card" style="max-width:380px;margin:40px auto">
  <h2>Login</h2>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <p><label>Username</label><input name="username" required autofocus></p>
    <p><label>Password</label><input type="password" name="password" required></p>
    <button class="btn">Login</button>
  </form>
</div>
<?php include 'footer.php'; ?>