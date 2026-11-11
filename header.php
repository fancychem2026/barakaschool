<?php require_once __DIR__ . '/auth.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Baraka Academy - Learner Registration</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <b>Baraka Academy</b>
  <?php if (!empty($_SESSION['user'])): ?>
    <a href="index.php">Dashboard</a>
    <a href="learners.php">Learners</a>
    <a href="learner_form.php">Register Learner</a>
    <a href="marks_entry.php">Enter Marks</a>
    <a href="class_list.php">Class List</a>
    <a href="export.php">Export CSV</a>
    <?php if (is_admin()): ?>
      <a href="subjects.php">Subjects</a>
      <a href="promote.php">Promotion</a>
      <a href="users.php">Users</a>
    <?php endif; ?>
    <span class="right">
      <?= e($_SESSION['user']['full_name']) ?> (<?= e($_SESSION['user']['role']) ?>)
      &nbsp; <a href="logout.php">Logout</a>
    </span>
  <?php endif; ?>
</nav>
<main>