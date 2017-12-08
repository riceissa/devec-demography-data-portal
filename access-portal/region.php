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

<h1><?= $title ?></h1>

<?php
function datasets_by_year_for_metric($mysqli, $metric, $region, $start_date, $end_date) {
  $ret = "<h2>$metric</h2>";

  if ($stmt = $mysqli->prepare("select *,(select shortname from datasets where datasets.url = database_url) as shortname from data where region = ? and odate between ? and ? and metric = ?")) {
    $stmt->bind_param("ssss", $region, $start_date, $end_date, $metric);
    $stmt->execute();
    $result = $stmt->get_result();
  }

  // Stores table data in dataset(units) by year format. For example,
  // $data['maddison2010 (1990 international dollar)']['20050000'] would
  // access the value for Maddison 2010 for the year 2005 for some metric
  // measured in 1990 international dollar.
  $data = array();

  $datasets = array();
  $odates = array();

  while ($row = $result->fetch_assoc()) {
    $rowname = $row['shortname'] . " (" . $row['units'] . ")";
    if (!in_array($rowname, $datasets)) {
      $datasets[] = $rowname;
    }
    if (!in_array($row['odate'], $odates)) {
      $odates[] = $row['odate'];
    }
    $data[$rowname][$row['odate']] = $row['value'];
  }

  sort($odates);
  sort($datasets);

  $ret .= "<table>";
  $ret .= "<thead>";
  $ret .= "<tr>";
  $ret .= "<th>Dataset</th>";
  foreach ($odates as $odate) {
    $ret .= '<th>' . $odate . '</th>';
  }
  $ret .= "</tr>";
  $ret .= "</thead>";
  $ret .= "<tbody>";

  foreach ($datasets as $dataset) {
    $ret .= "<tr>";
    $ret .= "<td>$dataset</td>";
      foreach (($odates) as $odate) {
        $ret .= "<td>" . $data[$dataset][$odate] . "</td>";
      }
    $ret .= "</tr>";
  }

  $ret .= "</tbody>";
  $ret .= "</table>";

  return $ret;
}
?>

<?= datasets_by_year_for_metric($mysqli, "GDP", $region, $start_date, $end_date) ?>
<?= datasets_by_year_for_metric($mysqli, "GDP per capita", $region, $start_date, $end_date) ?>
<?= datasets_by_year_for_metric($mysqli, "Population", $region, $start_date, $end_date) ?>

<script>
    $(function(){$("table").tablesorter();});
    anchors.add();
</script>
</body>
</html>
