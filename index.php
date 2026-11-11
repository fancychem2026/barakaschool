<?php
require __DIR__ . '/auth.php'; require_login();
$total = (int)$pdo->query("SELECT COUNT(*) FROM learners WHERE status='Active'")->fetchColumn();
$noUpi = (int)$pdo->query("SELECT COUNT(*) FROM learners WHERE status='Active' AND upi IS NULL")->fetchColumn();
$gender = $pdo->query("SELECT gender, COUNT(*) c FROM learners WHERE status='Active' GROUP BY gender")->fetchAll(PDO::FETCH_KEY_PAIR);
$byGrade = $pdo->query("SELECT grade, COUNT(*) c FROM learners WHERE status='Active' GROUP BY grade")->fetchAll(PDO::FETCH_KEY_PAIR);
include 'header.php';
?>
<div class="grid">
  <div class="card"><div class="stat"><?= $total ?></div>Active learners</div>
  <div class="card"><div class="stat"><?= (int)($gender['Male'] ?? 0) ?></div>Boys</div>
  <div class="card"><div class="stat"><?= (int)($gender['Female'] ?? 0) ?></div>Girls</div>
  <div class="card"><div class="stat"><?= $noUpi ?></div>Without UPI yet</div>
</div>
<div class="card">
  <h2>Learners per grade</h2>
  <table>
    <tr><th>Grade</th><th>Active learners</th><th></th></tr>
    <?php for ($g = 1; $g <= 9; $g++): ?>
      <tr><td>Grade <?= $g ?></td><td><?= (int)($byGrade[$g] ?? 0) ?></td>
      <td><a href="learners.php?grade=<?= $g ?>">View list</a></td></tr>
    <?php endfor; ?>
  </table>
</div>
<?php include 'footer.php'; ?>