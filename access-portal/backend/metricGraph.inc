<?php

// See
// https://github.com/vipulnaik/donations/blob/106b938087ad0fd2bbe93d891d1bf8513d54b10c/access-portal/backend/yearlyGraph.inc
// for code on which this function is based.

function metricGraph($data, $generateGraphCmdBase, $imagesPath,
    $permalinkUrlBase, $graphIdentifier) {

  // Both of these will (if the starting year is 2005) look like:
  //     Year|dataset1|dataset2|...
  //     20050000|val1|val2|...
  //     20060000|val3|val4|...
  //     ...
  $dataForGraph = "Year";

  // Gather all odates and dataset names
  $datasets = array();
  $odates = array();
  foreach ($data as $dataset => $row) {
    if (!in_array($dataset, $datasets)) {
      $datasets[] = $dataset;
    }
    foreach ($row as $odate => $val) {
      if (!in_array($odate, $odates)) {
        $odates[] = $odate;
      }
    }
  }
  sort($datasets);
  sort($odates);

  foreach ($datasets as $dataset) {
    $dataForGraph .= "|$dataset";
  }
  $dataForGraph .= "\n";

  foreach ($odates as $odate) {
    $dataForGraph .= $odate;
    foreach ($datasets as $dataset) {
      $dataForGraph .= "|" . ($data[$dataset][$odate] ?? 0);
    }
    $dataForGraph .= "\n";
  }

  $permalinkUrl = $permalinkUrlBase . $graphIdentifier;
  $fileName = hash("md5", $permalinkUrl);
  $filePathBase = $imagesPath . $fileName;
  $dataHasChanged= false;
  if (file_exists(! $filePathBase . ".csv")) {
    file_put_contents($filePathBase . ".csv", $dataForGraph);
    $dataHasChanged = true;
  } else {
    $previousOutput = file_get_contents($filePathBase . ".csv");
    if ($previousOutput != $dataForGraph) {
      $dataHasChanged = true;
      file_put_contents($filePathBase . ".csv", $dataForGraph);
    }
  }

  $cmdToExecute = $generateGraphCmdBase . " " . $filePathBase . ".csv " .
    $filePathBase . "-timeseries.png";
  if ($dataHasChanged or ! file_exists($filePathBase . "-timeseries.png")) {
    if (file_exists($filePathBase . "-timeseries.png")) {
      exec("rm " . $filePathBase . "-timeseries.png");
    }
    exec($cmdToExecute);
  }

  return $fileName;
}

?>
