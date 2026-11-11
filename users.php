<?php
require __DIR__ . '/auth.php'; require_admin();

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'add') {
      $name = trim($_POST['full_name'] ?? ''); $user = trim($_POST['username'] ?? ''); $pass = $_POST['password'] ?? '';
      $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'teacher';
      if ($name === '' || $user === '' || strlen($pass) < 8) { $err = 'Fill all fields. Password must be at least 8 characters.'; }
      else {
        $pdo->prepare('INSERT INTO users (full_name, username, password, role) VALUES (?,?,?,?)')
            ->execute([$name, $user, password_hash($pass, PASSWORD_DEFAULT), $role]);
        $msg = 'User added.';
      }
    } elseif ($action === 'reset') {
      $pass = $_POST['password'] ?? '';
      if (strlen($pass) < 8) { $err = 'Password must be at least 8 characters.'; }
      else {
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$_POST['id']]);
        $msg = 'Password changed.';
      }
    } elseif ($action === 'delete') {
      if ((int)$_POST['id'] === (int)$_SESSION['user']['id']) { $err = 'You cannot delete your own account.'; }
      else { $pdo->prepare('DELETE FROM users WHERE id=?')->execute([(int)$_POST['id']]); $msg = 'User deleted.'; }
    }
  } catch (PDOException $ex) {
    $err = ($ex->getCode() == 23000) ? 'That username already exists.' : 'Database error.';
  }
}
$users = $pdo->query('SELECT id, full_name, username, role FROM users ORDER BY role, full_name')->fetchAll();
include 'header.php';
?>
<div class="card">
  <h2>Users</h2>
  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
  <table>
    <tr><th>Name</th><th>Username</th><th>Role</th><th>Change password</th><th></th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['full_name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['role']) ?></td>
      <td>
        <form method="post" style="display:flex;gap:6px">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="password" name="password" placeholder="New password" style="width:150px"><button class="btn small">Change</button>
        </form>
      </td>
      <td>
        <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
        <form method="post" onsubmit="return confirm('Delete this user?');">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button class="btn small red">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<div class="card" style="max-width:520px">
  <h3>Add user</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="add">
    <p><label>Full name</label><input name="full_name" required></p>
    <p><label>Username</label><input name="username" required></p>
    <p><label>Password (min 8)</label><input type="password" name="password" required></p>
    <p><label>Role</label><select name="role"><option value="teacher">Teacher</option><option value="admin">Admin</option></select></p>
    <button class="btn">Add user</button>
  </form>
</div>
<?php include 'footer.php'; ?>