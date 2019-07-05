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

if ($pass = ($_REQUEST['include_private'] ?? '')) {
  if (hash("md5", $pass) == "d6430e381d7ca1ead4c25bd1600ffc41") {
    $include_private = true;
  }
}
$include_private = $include_private ?? false;

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
  <?php
    include_once("analytics.inc");
  ?>
</head>
<body>
<nav>
  <a href="/">Home</a>,
  <a href="/region.php">Region</a>
</nav>

<h1><?= $title ?></h1>

<?php
include_once("regionblurb.inc");
// Read data about a metric/region/date range combination from MySQL into a 2D
// array, data, which stores datasets in rows and dates in columns so that
// $data[$dataset][$date] gives the value corresponding to the given dataset
// and date. In addition, return two arrays, odates and datasets. The former
// contains all dates appearing in the dataset and the latter contains all
// datasets.
function dataDatasetByYearForMetric($mysqli, $metric, $region, $start_date,
    $end_date, $include_private) {

  if (is_array($region)) {
    $region_part = "(";
    for ($i = 0; $i < count($region); $i++) {
      if ($i == 0) {
        $region_part .= "region = ?";
      } else {
        $region_part .= " or region = ?";
      }
    }
    $region_part .= ")";
  } else {
    $region_part = "region = ?";
  }

  if ($include_private) {
    $query = "select *,(select concat(shortname, ':', metric) from datasets where datasets.url = database_url) as shortname from data where " . $region_part . " and odate between ? and ? and (case when metric in ('Real Gross Domestic Product per Capita, current price', 'Real GDP per capita (Constant Prices: Laspeyres), derived from growth rates of c, g, i', 'Real GDP per capita (Constant Prices: Laspeyres), derived from growth rates of domestic absorption', 'Real GDP per capita (Constant Prices: Chain series)','PPP Converted GDP Per Capita, G-K method, at current prices','PPP Converted Domestic Absorption Per Capita, average GEKS-CPDW, at current prices') then 'GDP per capita' else metric end) = ? and ((units != 'constant LCU' and units != 'current LCU') or units is null)";
  } else {
    $query = "select *,(select concat(shortname, ':', metric) from datasets where datasets.url = database_url) as shortname from data where " . $region_part . " and odate between ? and ? and (case when metric in ('Real Gross Domestic Product per Capita, current price', 'Real GDP per capita (Constant Prices: Laspeyres), derived from growth rates of c, g, i', 'Real GDP per capita (Constant Prices: Laspeyres), derived from growth rates of domestic absorption', 'Real GDP per capita (Constant Prices: Chain series)','PPP Converted GDP Per Capita, G-K method, at current prices','PPP Converted Domestic Absorption Per Capita, average GEKS-CPDW, at current prices') then 'GDP per capita' else metric end) = ? having not (shortname REGEXP '^ted') and ((units != 'constant LCU' and units != 'current LCU') or units is null)";
  }

  # print "query = $query\n<br/>";
  # print "parameters to query: region = $region, start_date = $start_date, end_date = $end_date, metric = $metric\n<br/>";
  if ($stmt = $mysqli->prepare($query)) {
    $param_str = "";
    $params = array();
    if (is_array($region)) {
      foreach ($region as $r) {
        $param_str .= "s";
        $params[] = $r;
      }
    } else {
      $param_str .= "s";
      $params[] = $region;
    }
    $param_str .= "s";
    $params[] = $start_date;
    $param_str .= "s";
    $params[] = $end_date;
    $param_str .= "s";
    $params[] = $metric;
    $stmt->bind_param($param_str, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
  } else {
    echo "<pre>\n";
    echo $mysqli->error;
    echo "</pre>\n";
  }

  // Stores table data in "region: dataset(units)" by year format. For example,
  // $data['India: maddison2010 (1990 international dollar)']['20050000'] would
  // access the value for India in Maddison 2010 for the year 2005 for some metric
  // measured in 1990 international dollar.
  $data = array();

  $datasets = array();
  $odates = array();

  while ($row = $result->fetch_assoc()) {
    # print "Reading a row from data\n<br/>";
    $rowname = $row['region'] . ": " . $row['shortname'] . " (" . $row['units'] . ")";
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

// Given $data, $odates, and $datasets, return a string of an HTML table that
// has datasets in the rows and odates in the columns, and where the values of
// the table are data accessed at the given date/dataset combination. The first
// three input arrays are intended to be the output of
// dataDatasetByYearForMetric. $averages and $growthRates are the average for
// each dataset and the growth rate for each dataset, respectively. These are
// intended to be the output of calculateStats.
function metricTable($precision, $data, $odates, $datasets, $averages, $growthRates) {
  $ret = "<table>\n";
  $ret .= "  <thead>\n";
  $ret .= "    <tr>\n";
  $ret .= "      <th>Dataset and exact metric name</th>\n";
  $ret .= "      <th>Average</th>\n";
  $ret .= "      <th>Growth (start to end, using linear fit on logarithm)</th>\n";
  foreach ($odates as $odate) {
    $ret .= '      <th>' . $odate . '</th>' . "\n";
  }
  $ret .= "    </tr>\n";
  $ret .= "  </thead>\n";
  $ret .= "  <tbody>\n";

  foreach ($datasets as $dataset) {
    $ret .= "    <tr>\n";
    $ret .= "      <td>$dataset</td>\n";
    $ret .= '      <td style="text-align: right;">' . number_format($averages[$dataset], $precision) . "</td>\n";
    $ret .= '      <td style="text-align: right;">' . number_format(100.0 * $growthRates[$dataset], 2) . "%</td>\n";
      foreach ($odates as $odate) {
        $cleanedDataPoint = "--";
        if (array_key_exists($odate, $data[$dataset])) {
          $cleanedDataPoint = number_format($data[$dataset][$odate], $precision);
        }
        $ret .= '      <td style="text-align: right;">'
          . $cleanedDataPoint . "</td>\n";
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
    $region, $start_date, $end_date, $include_private, $pythonDir, $precision) {

  $permalinkUrlBase = "https://devec.vipulnaik.com/region.php#" . $metric . ($include_private ? 'include_private' : 'no_include_private');
  $graphIdentifier = "?region=" . $region . "&start_date=" . $start_date . "&end_date=" . $end_date;
  $result = dataDatasetByYearForMetric($mysqli, $metric, $region, $start_date,
    $end_date, $include_private);
  $data = $result[0];
  $stats = calculateStats($data, $imagesPath, $permalinkUrlBase,
    $graphIdentifier, $pythonDir);

  echo "<h2>$metric</h2>\n";
  echo metricTable($precision, ...$result, ...$stats);
  $fileName = metricGraph($result[0], $generateGraphCmdBase, $imagesPath,
    $permalinkUrlBase, $graphIdentifier);
  print '<img src="/images/' . $fileName . '-timeseries.png" alt="Graph should be here"></img>';
}

printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, "GDP",
  $region, $start_date, $end_date, $include_private, $pythonDir, 0);
printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, "GDP per capita",
  $region, $start_date, $end_date, $include_private, $pythonDir, 0);
printMetricInfo($mysqli, $generateGraphCmdBase, $imagesPath, "Population",
  $region, $start_date, $end_date, $include_private, $pythonDir, 0);
?>

<script>
    $(function(){$("table").tablesorter();});
    anchors.add();
</script>
</body>
</html>
