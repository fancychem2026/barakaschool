<?php
session_start();
require __DIR__ . '/db.php';

function require_login() {
  if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
}
function require_admin() {
  require_login();
  if ($_SESSION['user']['role'] !== 'admin') { http_response_code(403); exit('Admins only.'); }
}
function is_admin() { return ($_SESSION['user']['role'] ?? '') === 'admin'; }
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf() {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function check_csrf() {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) exit('Invalid request. Go back and try again.');
}