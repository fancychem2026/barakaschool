<?php
require __DIR__ . '/auth.php'; require_admin();

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'add') {
      $name = trim($_POST['name'] ?? '');
      if ($name === '') { $err = 'Type a subject name.'; }
      else {
        $pdo->prepare('INSERT INTO subjects (name) VALUES (?)')->execute([$name]);
        $msg = 'Subject added.';
      }
    } elseif ($action === 'delete') {
      $pdo->prepare('DELETE FROM subjects WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
      $msg = 'Subject deleted.';
    }
  } catch (PDOException $ex) {
    $err = ($ex->getCode() == 23000) ? 'That subject already exists.' : 'Database error.';
  }
}
$subjects = $pdo->query('SELECT s.id, s.name, (SELECT COUNT(*) FROM marks m WHERE m.subject_id = s.id) AS n FROM subjects s ORDER BY s.name')->fetchAll();
include 'header.php';
?>
<div class="card" style="max-width:640px">
  <h2>Subjects</h2>
  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
  <table>
    <tr><th>Subject</th><th>Marks saved</th><th></th></tr>
    <?php foreach ($subjects as $s): ?>
    <tr>
      <td><?= e($s['name']) ?></td>
      <td><?= (int)$s['n'] ?></td>
      <td>
        <form method="post" onsubmit="return confirm('Delete this subject<?= (int)$s['n'] > 0 ? ' and all its marks' : '' ?>?');">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <button class="btn small red">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$subjects): ?><tr><td colspan="3">No subjects yet.</td></tr><?php endif; ?>
  </table>
</div>
<div class="card" style="max-width:640px">
  <h3>Add subject</h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <input type="hidden" name="action" value="add">
    <p><label>Subject name</label><input name="name" required></p>
    <button class="btn">Add subject</button>
  </form>
</div>
<?php include 'footer.php'; ?>