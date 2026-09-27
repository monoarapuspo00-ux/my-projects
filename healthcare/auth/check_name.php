<?php
// auth/check_name.php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['exists'=>false]); exit; }
require_once __DIR__ . '/../config/database.php';
$name = trim($_POST['name'] ?? '');
if (strlen($name) < 3) { echo json_encode(['exists'=>false]); exit; }
$s = $pdo->prepare("SELECT id FROM users WHERE name=? LIMIT 1");
$s->execute([$name]);
echo json_encode(['exists' => (bool)$s->fetch()]);
