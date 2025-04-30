<?php require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT html FROM snapshots WHERE id = ?');
$stmt->execute([$id]);
if (!$row = $stmt->fetch()) die('Not found');
header('Content-Type: text/html; charset=utf-8');
echo $row['html'];