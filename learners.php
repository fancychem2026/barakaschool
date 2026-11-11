<?php
require __DIR__ . '/auth.php'; require_login();

$q = trim($_GET['q'] ?? '');
$grade = (int)($_GET['grade'] ?? 0);
$status = $_GET['status'] ?? 'Active';

$where = []; $params = [];
if ($q !== '') {
  $where[] = "(first_name LIKE :q OR middle_name LIKE :q OR last_name LIKE :q OR admission_no LIKE :q OR upi LIKE :q OR guardian_phone LIKE :q)";
  $params['q'] = "%$q%";
}
if ($grade >= 1 && $grade <= 9) { $where[] = 'grade = :g'; $params['g'] = $grade; }
if (in_array($status, ['Active', 'Transferred', 'Completed'], true)) { $where[] = 'status = :s'; $params['s'] = $status; }

$sql = 'SELECT * FROM learners' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY grade, stream, first_name, last_name';
$st = $pdo->prepare($sql); $st->execute($params);
$rows = $st->fetchAll();

include 'header.php';
?>
<div class="card">
  <h2>Learners (<?= count($rows) ?>)</h2>
  <?php if (isset($_GET['saved'])): ?><div class="ok">Saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="ok">Learner deleted.</div><?php endif; ?>

  <form method="get" class="filters noprint">
    <div><label>Search</label><input name="q" value="<?= e($q) ?>" placeholder="Name, admission no, UPI, phone"></div>
    <div><label>Grade</label>
      <select name="grade"><option value="0">All</option>
        <?php for ($g = 1; $g <= 9; $g++): ?><option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>>Grade <?= $g ?></option><?php endfor; ?>
      </select></div>
    <div><label>Status</label>
      <select name="status"><option value="">All</option>
        <?php foreach (['Active', 'Transferred', 'Completed'] as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
      </select></div>
    <div style="flex:0"><button class="btn">Filter</button></div>
    <div style="flex:0"><a class="btn gray" href="#" onclick="window.print();return false;">Print</a></div>
  </form>

  <div style="overflow-x:auto">
  <table>
    <tr><th>Adm No</th><th>UPI</th><th>Name</th><th>Gender</th><th>Grade</th><th>Guardian</th><th>Phone</th><th>Status</th><th class="noprint">Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['admission_no']) ?></td>
      <td><?= $r['upi'] ? e($r['upi']) : '<i style="color:#b60">Pending</i>' ?></td>
      <td><?= e(trim($r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name'])) ?></td>
      <td><?= e($r['gender']) ?></td>
      <td><?= (int)$r['grade'] ?><?= $r['stream'] ? ' ' . e($r['stream']) : '' ?></td>
      <td><?= e($r['guardian_name']) ?></td>
      <td><?= e($r['guardian_phone']) ?></td>
      <td><?= e($r['status']) ?></td>
      <td class="noprint" style="white-space:nowrap">
        <a class="btn small" href="learner_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
        <?php if (is_admin()): ?>
        <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('Permanently delete this learner? This cannot be undone.');">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn small red">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="9">No learners found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php include 'footer.php'; ?>