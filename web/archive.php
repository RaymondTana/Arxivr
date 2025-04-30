<?php require 'config.php';
$url = trim($_POST['url'] ?? '');
if (!filter_var($url, FILTER_VALIDATE_URL)) {
  die('Invalid URL');
}
$pdo->beginTransaction();
$pageId = $pdo->prepare('INSERT INTO pages(url) VALUES(?) ON CONFLICT (url) DO UPDATE SET url = EXCLUDED.url RETURNING id');
$pageId->execute([$url]);
$pageId = $pageId->fetchColumn();
$pdo->commit();

// queue job (naive, for now)
file_put_contents('/queue/jobs', json_encode(['page_id'=>$pageId,'url'=>$url])."\n", FILE_APPEND);
header('Location: index.php');