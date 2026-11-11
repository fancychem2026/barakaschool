<?php
require __DIR__ . '/auth.php'; require_login();

$exams = ['Opener', 'Mid Term', 'End Term'];
$grade      = (int)($_GET['grade'] ?? 0);
$stream     = trim($_GET['stream'] ?? '');
$year       = (int)($_GET['year'] ?? date('Y'));
$term       = (int)($_GET['term'] ?? 0);
$exam       = $_GET['exam'] ?? '';
$subject_id = (int)($_GET['subject_id'] ?? 0);

$subjects = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();
$subjectName = '';
foreach ($subjects as $s) { if ((int)$s['id'] === $subject_id) $subjectName = $s['name']; }

$ready = $grade >= 1 && $grade <= 9 && $term >= 1 && $term <= 3
      && in_array($exam, $exams, true) && $subjectName !== '' && $year >= 2000 && $year <= 2100;

$learners = []; $existing = []; $errors = []; $typed = null;

if ($ready) {
  $sql = "SELECT id, admission_no, first_name, middle_name, last_name FROM learners WHERE status='Active' AND grade = ?";
  $p = [$grade];
  if ($stream !== '') { $sql .= ' AND stream = ?'; $p[] = $stream; }
  $sql .= ' ORDER BY first_name, last_name';
  $st = $pdo->prepare($sql); $st->execute($p);
  $learners = $st->fetchAll();

  $st = $pdo->prepare('SELECT learner_id, score FROM marks WHERE subject_id = ? AND exam_year = ? AND term = ? AND exam = ?');
  $st->execute([$subject_id, $year, $term, $exam]);
  foreach ($st->fetchAll() as $m) { $existing[(int)$m['learner_id']] = $m['score']; }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $typed = $_POST['score'] ?? [];
    if (!is_array($typed)) $typed = [];

    $todo = [];
    foreach ($learners as $l) {
      $lid = (int)$l['id'];
      $raw = isset($typed[$lid]) && is_scalar($typed[$lid]) ? trim((string)$typed[$lid]) : '';
      $label = trim($l['first_name'] . ' ' . $l['last_name']) . ' (' . $l['admission_no'] . ')';
      if ($raw === '') {
        if (isset($existing[$lid])) $todo[$lid] = null;
        continue;
      }
      if (!is_numeric($raw) || (float)$raw < 0 || (float)$raw > 100) {
        $errors[] = "$label: marks must be a number from 0 to 100.";
        continue;
      }
      $todo[$lid] = round((float)$raw, 2);
    }

    if (!$errors) {
      try {
        $pdo->beginTransaction();
        foreach ($todo as $lid => $val) {
          if ($val === null) {
            $pdo->prepare('DELETE FROM marks WHERE learner_id = ? AND subject_id = ? AND exam_year = ? AND term = ? AND exam = ?')
                ->execute([$lid, $subject_id, $year, $term, $exam]);
          } elseif (isset($existing[$lid])) {
            $pdo->prepare('UPDATE marks SET score = ? WHERE learner_id = ? AND subject_id = ? AND exam_year = ? AND term = ? AND exam = ?')
                ->execute([$val, $lid, $subject_id, $year, $term, $exam]);
          } else {
            $pdo->prepare('INSERT INTO marks (learner_id, subject_id, exam_year, term, exam, score) VALUES (?,?,?,?,?,?)')
                ->execute([$lid, $subject_id, $year, $term, $exam, $val]);
          }
        }
        $pdo->commit();
        header('Location: marks_entry.php?' . http_build_query([
          'grade' => $grade, 'stream' => $stream, 'year' => $year, 'term' => $term,
          'exam' => $exam, 'subject_id' => $subject_id, 'saved' => 1
        ]));
        exit;
      } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = 'Database error: marks were not saved.';
      }
    }
  }
}

include 'header.php';
?>
<div class="card">
  <h2>Enter marks</h2>
  <?php if (isset($_GET['saved'])): ?><div class="ok">Marks saved.</div><?php endif; ?>
  <form method="get" class="filters noprint">
    <div><label>Grade</label>
      <select name="grade" required><option value="">Select</option>
        <?php for ($g = 1; $g <= 9; $g++): ?><option value="<?= $g ?>" <?= $grade === $g ? 'selected' : '' ?>>Grade <?= $g ?></option><?php endfor; ?>
      </select></div>
    <div><label>Stream (optional)</label><input name="stream" value="<?= e($stream) ?>" placeholder="All streams"></div>
    <div><label>Year</label><input type="number" name="year" value="<?= (int)$year ?>" min="2000" max="2100"></div>
    <div><label>Term</label>
      <select name="term" required><option value="">Select</option>
        <?php for ($t = 1; $t <= 3; $t++): ?><option value="<?= $t ?>" <?= $term === $t ? 'selected' : '' ?>>Term <?= $t ?></option><?php endfor; ?>
      </select></div>
    <div><label>Exam</label>
      <select name="exam" required><option value="">Select</option>
        <?php foreach ($exams as $x): ?><option <?= $exam === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?>
      </select></div>
    <div><label>Subject</label>
      <select name="subject_id" required><option value="">Select</option>
        <?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= $subject_id === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
      </select></div>
    <div style="flex:0"><button class="btn">Load</button></div>
  </form>
</div>

<?php if ($ready): ?>
<div class="card">
  <h3><?= e($subjectName) ?> - Grade <?= (int)$grade ?><?= $stream !== '' ? ' ' . e($stream) : '' ?>, Term <?= (int)$term ?> <?= (int)$year ?>, <?= e($exam) ?></h3>
  <?php if ($errors): ?><div class="err"><?php foreach ($errors as $er) echo '&bull; ' . e($er) . '<br>'; ?></div><?php endif; ?>
  <?php if (!$learners): ?>
    <p>No active learners found for this selection.</p>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <table>
      <tr><th>#</th><th>Adm No</th><th>Name</th><th style="width:140px">Marks (0-100)</th></tr>
      <?php foreach ($learners as $i => $l):
        $lid = (int)$l['id'];
        if ($typed !== null) { $val = isset($typed[$lid]) && is_scalar($typed[$lid]) ? (string)$typed[$lid] : ''; }
        else { $val = isset($existing[$lid]) ? rtrim(rtrim(number_format((float)$existing[$lid], 2, '.', ''), '0'), '.') : ''; }
      ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e($l['admission_no']) ?></td>
        <td><?= e(trim($l['first_name'] . ' ' . $l['middle_name'] . ' ' . $l['last_name'])) ?></td>
        <td><input type="number" name="score[<?= $lid ?>]" value="<?= e($val) ?>" min="0" max="100" step="0.01"></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p>Leave a box empty if the learner did not sit the exam.</p>
    <p><button class="btn">Save marks</button></p>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>