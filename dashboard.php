
<?php
require_once "config.php";
require_once "functions.php";

requireAdmin();

$total = (int) $pdo->query(
    "SELECT COUNT(*) FROM learners"
)->fetchColumn();

$stmt = $pdo->query(
    "SELECT grade, COUNT(*) AS total
     FROM learners
     GROUP BY grade
     ORDER BY grade"
);
$grades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baraka Academy Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="school-mark">BA</div>
        <div>
            <h1>Baraka Academy</h1>
            <p>Learners Management Dashboard</p>
        </div>
    </div>
</header>

<main class="container">
    <section class="panel">
        <h2>Welcome, <?= e($_SESSION["admin_username"]) ?></h2>
        <h3>Total Registered Learners: <?= $total ?></h3>

        <p><a class="button" href="learner_form.php">Register Learner</a></p>
        <p><a class="button" href="learners.php">View Learners</a></p>
        <p><a class="button" href="reports.php">Reports</a></p>
        <p><a class="button" href="logout.php">Log Out</a></p>
    </section>

    <section class="panel" style="margin-top:20px">
        <h2>Learners by Grade</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Grade</th><th>Learner Count</th></tr>
                </thead>
                <tbody>
                <?php if (!$grades): ?>
                    <tr><td colspan="2">No learners registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><?= e($grade["grade"]) ?></td>
                        <td><?= (int) $grade["total"] ?></td>
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