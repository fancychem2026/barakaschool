<?php
require __DIR__ . '/auth.php'; require_login();

$exams = ['Opener', 'Mid Term', 'End Term'];
$grade  = (int)($_GET['grade'] ?? 0);
$stream = trim($_GET['stream'] ?? '');
$year   = (int)($_GET['year'] ?? date('Y'));
$term   = (int)($_GET['term'] ?? 0);
$exam   = $_GET['exam'] ?? '';

$ready = $grade >= 1 && $grade <= 9 && $term >= 1 && $term <= 3
      && in_array($exam, $exams, true) && $year >= 2000 && $year <= 2100;

$rows = []; $subjectNames = []; $subjectMeans = [];

if ($ready) {
  $sql = "SELECT id, admission_no, first_name, middle_name, last_name FROM learners WHERE status='Active' AND grade = ?";
  $p = [$grade];
  if ($stream !== '') { $sql .= ' AND stream = ?'; $p[] = $stream; }
  $st = $pdo->prepare($sql); $st->execute($p);
  $learners = $st->fetchAll();

  $marksBy = [];
  if ($learners) {
    $ids = array_map(fn($l) => (int)$l['id'], $learners);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT m.learner_id, m.subject_id, m.score, s.name FROM marks m JOIN subjects s ON s.id = m.subject_id
                         WHERE m.exam_year = ? AND m.term = ? AND m.exam = ? AND m.learner_id IN ($in)");
    $st->execute(array_merge([$year, $term, $exam], $ids));
    foreach ($st->fetchAll() as $m) {
      $marksBy[(int)$m['learner_id']][(int)$m['subject_id']] = (float)$m['score'];
      $subjectNames[(int)$m['subject_id']] = $m['name'];
    }
  }
  asort($subjectNames, SORT_NATURAL | SORT_FLAG_CASE);

  foreach ($learners as $l) {
    $lid = (int)$l['id'];
    $m = $marksBy[$lid] ?? [];
    $count = count($m);
    $total = array_sum($m);
    $rows[] = [
      'adm' => $l['admission_no'],
      'name' => trim($l['first_name'] . ' ' . $l['middle_name'] . ' ' . $l['last_name']),
      'marks' => $m, 'count' => $count, 'total' => $total,
      'mean' => $count ? $total / $count : null,
      'pos' => null,
    ];
  }

  usort($rows, function ($a, $b) {
    if (($a['count'] > 0) !== ($b['count'] > 0)) return $a['count'] > 0 ? -1 : 1;
    if ($a['total'] != $b['total']) return $a['total'] > $b['total'] ? -1 : 1;
    return strcasecmp($a['name'], $b['name']);
  });

  $pos = 0; $prevTotal = null;
  foreach ($rows as $i => &$r) {
    if ($r['count'] > 0) {
      if ($prevTotal === null || $r['total'] != $prevTotal) { $pos = $i + 1; $prevTotal = $r['total']; }
      $r['pos'] = $pos;
    }
  }
  unset($r);

  foreach ($subjectNames as $sid => $n) {
    $vals = [];
    foreach ($rows as $r) { if (isset($r['marks'][$sid])) $vals[] = $r['marks'][$sid]; }
    $subjectMeans[$sid] = $vals ? array_sum($vals) / count($vals) : null;
  }
}

function fmt($v) { return $v === null ? '-' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.'); }

include 'header.php';
?>
<div class="card noprint">
  <h2>Class list with positions</h2>
  <form method="get" class="filters">
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
    <div style="flex:0"><button class="btn">Show</button></div>
    <?php if ($ready): ?><div style="flex:0"><a class="btn gray" href="#" onclick="window.print();return false;">Print</a></div><?php endif; ?>
  </form>
</div>

<?php if ($ready): ?>
<div class="card">
  <h3>Baraka Academy - Grade <?= (int)$grade ?><?= $stream !== '' ? ' ' . e($stream) : '' ?>, Term <?= (int)$term ?> <?= (int)$year ?>, <?= e($exam) ?></h3>
  <?php if (!$rows): ?>
    <p>No active learners found for this selection.</p>
  <?php elseif (!$subjectNames): ?>
    <p>No marks have been entered for this selection yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table>
    <tr>
      <th>Pos</th><th>Adm No</th><th>Name</th>
      <?php foreach ($subjectNames as $n): ?><th><?= e($n) ?></th><?php endforeach; ?>
      <th>Total</th><th>Mean</th>
    </tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><b><?= $r['pos'] === null ? '-' : (int)$r['pos'] ?></b></td>
      <td><?= e($r['adm']) ?></td>
      <td><?= e($r['name']) ?></td>
      <?php foreach ($subjectNames as $sid => $n): ?><td><?= fmt($r['marks'][$sid] ?? null) ?></td><?php endforeach; ?>
      <td><b><?= $r['count'] ? fmt($r['total']) : '-' ?></b></td>
      <td><?= fmt($r['mean']) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td colspan="3"><b>Subject mean</b></td>
      <?php foreach ($subjectNames as $sid => $n): ?><td><b><?= fmt($subjectMeans[$sid]) ?></b></td><?php endforeach; ?>
      <td></td><td></td>
    </tr>
  </table>
  </div>
  <p><small>Position is by total marks. Learners with the same total share a position. A "-" means no mark was entered.</small></p>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php include 'footer.php'; ?>