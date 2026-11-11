<?php
require __DIR__ . '/auth.php'; require_login();

if (isset($_GET['download'])) {
  $grade = (int)($_GET['grade'] ?? 0);
  $status = $_GET['status'] ?? 'Active';
  $where = []; $params = [];
  if ($grade >= 1 && $grade <= 9) { $where[] = 'grade = ?'; $params[] = $grade; }
  if (in_array($status, ['Active', 'Transferred', 'Completed'], true)) { $where[] = 'status = ?'; $params[] = $status; }
  $st = $pdo->prepare('SELECT upi, admission_no, first_name, middle_name, last_name, gender, dob, birth_cert_no, grade, stream, admission_date,
      county, sub_county, guardian_name, guardian_relationship, guardian_phone, guardian_id_no, special_needs, status
      FROM learners' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY grade, stream, first_name');
  $st->execute($params);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="baraka_learners_' . date('Y-m-d') . '.csv"');
  echo "\xEF\xBB\xBF";
  $out = fopen('php://output', 'w');
  fputcsv($out, ['UPI','Admission No','First Name','Middle Name','Last Name','Gender','Date of Birth','Birth Cert No','Grade','Stream','Admission Date',
                 'County','Sub-county','Guardian Name','Relationship','Guardian Phone','Guardian ID','Special Needs','Status']);
  while ($r = $st->fetch(PDO::FETCH_NUM)) fputcsv($out, $r);
  fclose($out);
  exit;
}
include 'header.php';
?>
<div class="card" style="max-width:480px">
  <h2>Export learners to CSV</h2>
  <p>Opens in Excel. Use it to capture or update learners in NEMIS.</p>
  <form method="get">
    <input type="hidden" name="download" value="1">
    <p><label>Grade</label><select name="grade"><option value="0">All grades</option>
      <?php for ($g = 1; $g <= 9; $g++): ?><option value="<?= $g ?>">Grade <?= $g ?></option><?php endfor; ?></select></p>
    <p><label>Status</label><select name="status"><option value="">All</option><option selected>Active</option><option>Transferred</option><option>Completed</option></select></p>
    <button class="btn">Download CSV</button>
  </form>
</div>
<?php include 'footer.php'; ?>