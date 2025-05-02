<?php
require 'config.php';

$pageId = (int)($_GET['page'] ?? 0);

// Try to load the URL of the page
$stmt = $pdo->prepare('SELECT url FROM pages WHERE id = ?');
$stmt->execute([$pageId]);
$url = $stmt->fetchColumn();
if (!$url) {
  die('Unknown page');
}

// Load all snapshots for this page
$data = $pdo->prepare('SELECT id, fetched_at FROM snapshots WHERE page_id = ? ORDER BY fetched_at');
$data->execute([$pageId]);
$items = $data->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Timeline – <?= htmlspecialchars($url) ?></title>
  <script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
  <style>
    body { font-family: sans-serif; padding: 2rem; }
    #timeline-chart { height: 500px; max-width: 1000px; margin: auto; }
  </style>
</head>
<body>

<h1>Snapshot Timeline</h1>
<p><strong><?= htmlspecialchars($url) ?></strong></p>

<?php if (!$items): ?>
  <p>No snapshots yet — try submitting a new URL or wait for the fetcher to process jobs.</p>
<?php else: ?>

  <!-- Embed the snapshot data safely -->
  <script id="snapshot-data" type="application/json">
<?= json_encode($items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
  </script>

  <div id="timeline-chart"></div>

  <script>
    const snapshots = JSON.parse(
      document.getElementById("snapshot-data").textContent
    );

    const trace = {
      x: snapshots.map(s => s.fetched_at),
      y: snapshots.map(() => 1),
      mode: 'markers',
      type: 'scatter',
      marker: {
        size: 12,
        color: 'steelblue',
      },
      text: snapshots.map(s => `ID ${s.id} — ${s.fetched_at}`),
      hovertemplate: '%{text}<extra></extra>',
    };

    const layout = {
      title: 'Snapshot History (click any dot)',
      xaxis: { title: 'Timestamp', type: 'date' },
      yaxis: { visible: false },
      margin: { t: 50, l: 30, r: 30, b: 80 }
    };

    Plotly.newPlot('timeline-chart', [trace], layout, { responsive: true });

    document.getElementById('timeline-chart').on('plotly_click', function(data) {
      const index = data.points[0].pointIndex;
      const snapshot = snapshots[index];
      if (snapshot) {
        window.location.href = 'snapshot.php?id=' + snapshot.id;
      }
    });
  </script>
<?php endif; ?>

</body>
</html>
