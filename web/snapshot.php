<?php require 'config.php';
$id=(int)($_GET['id']??0);
$st=$pdo->prepare('SELECT status,message,html FROM snapshots WHERE id=?');
$st->execute([$id]);
if(!$row=$st->fetch()) die('Not found');
if($row['status']!=='ok') die('Capture unavailable: '.$row['message']);
header('Content-Type:text/html;charset=utf-8');
echo $row['html'];