
<?php
require_once "config.php";
require_once "functions.php";
requireAdmin();

$total = (int) $pdo->query(
    "SELECT COUNT(*) FROM learners"
)->fetchColumn();

$stmt = $pdo->query(
    "SELECT admission_number, upi_number, nemis_number,
            first_name, last_name, grade, guardian_name,
            guardian_phone
     FROM learners
     ORDER BY grade, last_name, first_name"
);

$learners = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Learner Reports | Baraka Academy</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .container { width: 100%; margin: 0; }
        }
    </style>
</head>
<body>
<main class="container">
    <section class="panel">
        <h1>Baraka Academy</h1>
        <h2>CBC Learners Registration Report</h2>
        <p>Total learners: <strong><?= $total ?></strong></p>

        <div class="no-print">
            <button class="button" onclick="window.print()">
                Print Report
            </button>
            <a class="button" href="dashboard.php">Dashboard</a>
        </div>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Admission No.</th>
                    <th>UPI</th>
                    <th>NEMIS</th>
                    <th>Learner Name</th>
                    <th>Grade</th>
                    <th>Guardian</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$learners): ?>
                <tr><td colspan="7">No learner records available.</td></tr>
            <?php else: ?>
                <?php foreach ($learners as $l): ?>
                <tr>
                    <td><?= e($l["admission_number"]) ?></td>
                    <td><?= e($l["upi_number"] ?? "") ?></td>
                    <td><?= e($l["nemis_number"] ?? "") ?></td>
                    <td><?= e($l["first_name"] . " " . $l["last_name"]) ?></td>
                    <td><?= e($l["grade"]) ?></td>
                    <td><?= e($l["guardian_name"]) ?></td>
                    <td><?= e($l["guardian_phone"]) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </section>
</main>
</body>
</html>