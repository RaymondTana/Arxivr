<?php require 'config.php'; ?>
<!doctype html><html><head><meta charset="utf-8"><title>Mini‑Archive</title></head>
<body>
<h1>Mini‑Archive</h1>
<?php
if (!empty($_GET['queued']) && isset($_GET['url'])):
  $st = $pdo->prepare('SELECT id FROM pages WHERE url = ?');
  $st->execute([$_GET['url']]);
  $pid = $st->fetchColumn();
?>
  <p style="color: green;">
    Successfully queued snapshot of <strong><?=htmlspecialchars($_GET['url'])?></strong>.<br>
    It may take a few seconds to appear. <a href="timeline.php?page=<?=$pid?>">View timeline</a>
  </p>
<?php endif; ?>
<form action="archive.php" method="post">
  <input type="url" name="url" placeholder="https://example.com" required style="width:60%">
  <button>Archive now</button>
</form>
<hr>
<h2>Tracked URLs</h2>
<?php
foreach($pdo->query("SELECT id,url FROM pages ORDER BY created_at DESC LIMIT 50") as $row){
  printf('<p><a href="timeline.php?page=%d">%s</a></p>', $row['id'], htmlspecialchars($row['url']));
}
?>
</body></html>