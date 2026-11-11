<?php
require __DIR__ . '/auth.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: learners.php'); exit; }
check_csrf();
$pdo->prepare('DELETE FROM learners WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
header('Location: learners.php?deleted=1');