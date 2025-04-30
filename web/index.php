<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Mini‑Archive</title></head>
<body>
<h1>Archive a page</h1>
<form action="archive.php" method="post">
   <input type="url" name="url" placeholder="https://example.com" required>
   <button type="submit">Save now</button>
</form>
<hr>
<h2>Existing snapshots</h2>
<?php
$stmt = $pdo->query("SELECT p.url, s.id, s.fetched_at FROM snapshots s JOIN pages p ON p.id=s.page_id ORDER BY s.fetched_at DESC LIMIT 50");
foreach ($stmt as $row) {
  printf('<p><a href="snapshot.php?id=%d">%s</a> (%s)</p>',
     $row['id'], htmlspecialchars($row['url']), $row['fetched_at']);
}
?>
</body></html>