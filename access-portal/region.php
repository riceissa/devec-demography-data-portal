<!DOCTYPE html>
<html lang="en">
<?php
include_once("backend/globalVariables/passwordFile.inc");
include_once("backend/metricGraph.inc");

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
  <style type="text/css">
    table {
        background-color: #f9f9f9;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 12px;
    }
    table th {
        background-color: #f2f2f2;
        border: 1px solid #aaaaaa;
    }
    table td {
        border: 1px solid #aaaaaa;
    }
  </style>
</head>
<body>
<nav>
  <a href="/">Home</a>,
  <a href="/about/">About</a>,
  <a href="/region.php">Region</a>
</nav>

<h1><?= $title ?></h1>

<?php
// Read data about a metric/region/date range combination from MySQL into a 2D
// array, data, which stores datasets in rows and dates in columns so that
// $data[$dataset][$date] gives the value corresponding to the given dataset
// and date. In addition, return two arrays, odates and datasets. The former
// contains all dates appearing in the dataset and the latter contains all
// datasets.
function dataDatasetByYearForMetric($mysqli, $metric, $region, $start_date,
    $end_date) {

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

  return array($data, $odates, $datasets);
}

// Given data, odates, and datasets, return a string of an HTML table that has
// datasets in the rows and odates in the columns, and where the values of the
// table are data accessed at the given date/dataset combination. The three
// input arrays are intended to be the output of dataDatasetByYearForMetric.
function metricTable($data, $odates, $datasets) {
  $ret = "<table>\n";
  $ret .= "  <thead>\n";
  $ret .= "    <tr>\n";
  $ret .= "      <th>Dataset</th>\n";
  foreach ($odates as $odate) {
    $ret .= '      <th>' . $odate . '</th>' . "\n";
  }
  $ret .= "    </tr>\n";
  $ret .= "  </thead>\n";
  $ret .= "  <tbody>\n";

  foreach ($datasets as $dataset) {
    $ret .= "    <tr>\n";
    $ret .= "      <td>$dataset</td>\n";
      foreach ($odates as $odate) {
        $ret .= '      <td style="text-align: right;">'
          . $data[$dataset][$odate] . "</td>\n";
      }
    $ret .= "    </tr>\n";
  }

  $ret .= "  </tbody>\n";
  $ret .= "</table>\n";

  return $ret;
}

// Print information for the given metric/region/date range combination. This
// will print an HTML table and also display an image graphing the data.
function printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, $metric,
    $region, $start_date, $end_date) {
  $result = dataDatasetByYearForMetric($mysqli, $metric, $region, $start_date,
    $end_date);
  echo "<h2>$metric</h2>\n";
  echo metricTable(...$result);
  $permalinkUrlBase = "https://???/region.php#" . $metric;
  $graphIdentifier = "???";
  $fileName = metricGraph($result[0], $generateGraphCmdBase, $imagesPath,
    $permalinkUrlBase, $graphIdentifier);
  print '<img src="/images/' . $fileName . '-timeseries.png" alt="Graph should be here"></img>';
}

printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, "GDP",
  $region, $start_date, $end_date);
printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, "GDP per capita",
  $region, $start_date, $end_date);
printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, "Population",
  $region, $start_date, $end_date);
?>

<script>
    $(function(){$("table").tablesorter();});
    anchors.add();
</script>
</body>
</html>
