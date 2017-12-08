<!DOCTYPE html>
<html lang="en">
<?php
include_once("backend/globalVariables/passwordFile.inc");

$title = "";
if ($region = ($_REQUEST['region'] ?? '')) {
  $title = htmlspecialchars($region);
}
if ($start_date = ($_REQUEST['start_date'] ?? '')) {
  $title .= " from " . htmlspecialchars($start_date);
}
if ($end_date = ($_REQUEST['end_date'] ?? '')) {
  $title .= " to " . htmlspecialchars($end_date);
}
?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <meta property="og:title" content="<?= $title ? $title : "Devec/Demography Data Portal" ?>" />
  <meta property="og:site_name" content="Devec/Demography Data Portal" />
  <meta property="og:locale" content="en_US" />
  <title><?= $title ? $title . " - Devec/Demography Data Portal" : "Devec/Demography Data Portal" ?></title>
  <link rel="stylesheet" href="/tablesorter.css">
  <script src="/jquery.min.js"></script>
  <script src="/jquery.tablesorter.js"></script>
  <script src="/anchor.min.js"></script>
</head>
<body>
<nav>
  <a href="/">Home</a>,
  <a href="/about/">About</a>,
  <a href="/region.php">Region</a>
</nav>

<h2>GDP</h2>

<?php
if ($stmt = $mysqli->prepare("select *,(select shortname from datasets where datasets.url = database_url) as shortname from data where region = ? and odate between ? and ? and metric = 'GDP'")) {
  $stmt->bind_param("sss", $region, $start_date, $end_date);
  $stmt->execute();
  $result = $stmt->get_result();
}

// dataset by year
$data = array();
$datasets = array();
$odates = array();

while ($row = $result->fetch_assoc()) {
  if (!in_array($row['shortname'], $datasets)) {
    $datasets[] = $row['shortname'];
  }
  if (!in_array($row['odate'], $odates)) {
    $odates[] = $row['odate'];
  }
  $data[$row['shortname']][$row['odate']] = $row['value'];
}

sort($odates);
sort($datasets);
?>

<table>
  <thead>
    <tr>
      <th>Dataset</th>
      <?php foreach ($odates as $odate) {
              echo '<th>' . $odate . '</th>';
            }
      ?>
    </tr>
  </thead>
  <tbody>

<?php foreach (($datasets) as $dataset) { ?>
  <tr>
    <td><?= $dataset ?></td>
    <?php foreach (($odates) as $odate) { ?>
      <td><?= $data[$dataset][$odate] ?></td>
    <?php } ?>
  </tr>
<?php } ?>

</tbody>
</table>

<script>
    $(function(){$("table").tablesorter();});
    anchors.add();
</script>
</body>
</html>
