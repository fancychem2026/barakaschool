<?php
require __DIR__ . '/auth.php'; require_admin();

$done = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $pdo->beginTransaction();
  $completed = $pdo->exec("UPDATE learners SET status='Completed' WHERE status='Active' AND grade=9");
  $promoted = $pdo->exec("UPDATE learners SET grade = grade + 1 WHERE status='Active' AND grade < 9");
  $pdo->commit();
  $done = "$promoted learners promoted to the next grade. $completed Grade 9 learners marked Completed.";
}
$counts = $pdo->query("SELECT grade, COUNT(*) c FROM learners WHERE status='Active' GROUP BY grade")->fetchAll(PDO::FETCH_KEY_PAIR);
include 'header.php';
?>
<div class="card" style="max-width:560px">
  <h2>Year-end promotion</h2>
  <?php if ($done): ?><div class="ok"><?= e($done) ?></div><?php endif; ?>
  <p>This moves every <b>Active</b> learner up one grade (Grade 1 to 2, and so on). Grade 9 learners are marked <b>Completed</b>.
     <b>Export a CSV backup first</b>, and run this only once per year.</p>
  <table>
    <tr><th>Grade</th><th>Active learners now</th></tr>
    <?php for ($g = 1; $g <= 9; $g++): ?><tr><td>Grade <?= $g ?></td><td><?= (int)($counts[$g] ?? 0) ?></td></tr><?php endfor; ?>
  </table>
  <form method="post" onsubmit="return confirm('Promote ALL active learners now? Make sure you have a backup.');" style="margin-top:14px">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <button class="btn red">Promote all learners</button>
  </form>
</div>
<?php include 'footer.php'; ?>