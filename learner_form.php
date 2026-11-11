<?php
require __DIR__ . '/auth.php'; require_login();

$id = (int)($_GET['id'] ?? 0);
$fields = ['admission_no','upi','first_name','middle_name','last_name','gender','dob','birth_cert_no','grade','stream',
           'admission_date','previous_school','special_needs','county','sub_county','guardian_name','guardian_relationship',
           'guardian_phone','guardian_id_no','status'];

$l = array_fill_keys($fields, '');
$l['status'] = 'Active';
$l['admission_date'] = date('Y-m-d');

if ($id) {
  $s = $pdo->prepare('SELECT * FROM learners WHERE id = ?'); $s->execute([$id]);
  $row = $s->fetch();
  if (!$row) exit('Learner not found.');
  $l = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  foreach ($fields as $f) $l[$f] = trim($_POST[$f] ?? '');

  $labels = ['admission_no'=>'Admission number','first_name'=>'First name','last_name'=>'Last name','gender'=>'Gender','dob'=>'Date of birth',
             'grade'=>'Grade','admission_date'=>'Admission date','guardian_name'=>'Guardian name','guardian_phone'=>'Guardian phone'];
  foreach ($labels as $f => $label) if ($l[$f] === '') $errors[] = "$label is required.";

  if ($l['grade'] !== '' && !in_array((int)$l['grade'], range(1, 9), true)) $errors[] = 'Grade must be 1 to 9.';
  if (!in_array($l['gender'], ['Male', 'Female'], true)) $errors[] = 'Choose a gender.';
  if (!in_array($l['status'], ['Active', 'Transferred', 'Completed'], true)) $errors[] = 'Invalid status.';
  if ($l['dob'] !== '' && $l['dob'] > date('Y-m-d')) $errors[] = 'Date of birth cannot be in the future.';
  if ($l['guardian_phone'] !== '' && !preg_match('/^[0-9+ ]{9,15}$/', $l['guardian_phone'])) $errors[] = 'Guardian phone looks invalid.';

  if (!$errors) {
    $params = [];
    foreach ($fields as $f) $params[$f] = ($l[$f] === '') ? null : $l[$f];
    try {
      if ($id) {
        $set = implode(',', array_map(fn($f) => "$f=:$f", $fields));
        $params['id'] = $id;
        $pdo->prepare("UPDATE learners SET $set WHERE id=:id")->execute($params);
      } else {
        $cols = implode(',', $fields);
        $ph = ':' . implode(',:', $fields);
        $pdo->prepare("INSERT INTO learners ($cols) VALUES ($ph)")->execute($params);
      }
      header('Location: learners.php?saved=1'); exit;
    } catch (PDOException $ex) {
      $errors[] = ($ex->getCode() == 23000) ? 'This admission number or UPI already exists.' : 'Database error: could not save.';
    }
  }
}

function field($name, $label, $l, $type = 'text', $req = false) {
  echo '<div><label>' . $label . ($req ? ' *' : '') . '</label><input type="' . $type . '" name="' . $name . '" value="' . e($l[$name]) . '"' . ($req ? ' required' : '') . '></div>';
}

include 'header.php';
?>
<div class="card">
  <h2><?= $id ? 'Edit Learner' : 'Register New Learner' ?></h2>
  <?php if ($errors): ?><div class="err"><?php foreach ($errors as $er) echo '&bull; ' . e($er) . '<br>'; ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

    <h3>Learner details</h3>
    <div class="grid">
      <?php field('admission_no', 'Admission No', $l, 'text', true); ?>
      <?php field('upi', 'UPI (NEMIS) - leave blank if pending', $l); ?>
      <?php field('first_name', 'First name', $l, 'text', true); ?>
      <?php field('middle_name', 'Middle name', $l); ?>
      <?php field('last_name', 'Last name', $l, 'text', true); ?>
      <div><label>Gender *</label>
        <select name="gender" required><option value="">Select</option>
          <?php foreach (['Male', 'Female'] as $g): ?><option <?= $l['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?>
        </select></div>
      <?php field('dob', 'Date of birth', $l, 'date', true); ?>
      <?php field('birth_cert_no', 'Birth certificate No', $l); ?>
      <div><label>Grade *</label>
        <select name="grade" required><option value="">Select</option>
          <?php for ($g = 1; $g <= 9; $g++): ?><option value="<?= $g ?>" <?= (string)$l['grade'] === (string)$g ? 'selected' : '' ?>>Grade <?= $g ?></option><?php endfor; ?>
        </select></div>
      <?php field('stream', 'Stream (e.g. East)', $l); ?>
      <?php field('admission_date', 'Admission date', $l, 'date', true); ?>
      <?php field('previous_school', 'Previous school', $l); ?>
      <?php field('county', 'County', $l); ?>
      <?php field('sub_county', 'Sub-county', $l); ?>
      <div><label>Status</label>
        <select name="status">
          <?php foreach (['Active', 'Transferred', 'Completed'] as $s): ?><option <?= $l['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <p><label>Special needs / health notes</label><textarea name="special_needs" rows="3"><?= e($l['special_needs']) ?></textarea></p>

    <h3>Parent / Guardian</h3>
    <div class="grid">
      <?php field('guardian_name', 'Guardian name', $l, 'text', true); ?>
      <?php field('guardian_relationship', 'Relationship', $l); ?>
      <?php field('guardian_phone', 'Phone', $l, 'text', true); ?>
      <?php field('guardian_id_no', 'ID number', $l); ?>
    </div>

    <p><button class="btn">Save</button> <a class="btn gray" href="learners.php">Cancel</a></p>
  </form>
</div>
<?php include 'footer.php'; ?>