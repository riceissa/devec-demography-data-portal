<?php

// See
// https://github.com/vipulnaik/donations/blob/106b938087ad0fd2bbe93d891d1bf8513d54b10c/access-portal/backend/yearlyGraph.inc
// for code on which this function is based.

// Serialize $data by converting it into a $sep-separated string. For example,
// suppose $data is the following:
//     Array
//     (
//         [dataset1] => Array
//             (
//                 [20050000] => 1
//                 [20060000] => 2
//             )
//
//         [dataset2] => Array
//             (
//                 [20050000] => 3
//                 [20060000] => 4
//             )
//
//     )
//
// Then serializeData($data) will look like:
//     Year|dataset1|dataset2
//     20050000|1|2
//     20060000|3|4
function serializeData(array $data, string $sep = '|', string $heading = "Year") {
  $result = $heading;

  // Gather all row and column names
  $rowNames = array();
  $colNames = array();
  foreach ($data as $rowName => $row) {
    if (!in_array($rowName, $rowNames)) {
      $rowNames[] = $rowName;
    }
    foreach ($row as $colName => $val) {
      if (!in_array($colName, $colNames)) {
        $colNames[] = $colName;
      }
    }
  }
  sort($rowNames);
  sort($colNames);

  foreach ($rowNames as $rowName) {
    $result .= "|$rowName";
  }
  $result .= "\n";

  foreach ($colNames as $colName) {
    $result .= $colName;
    foreach ($rowNames as $rowName) {
      $result .= "|" . ($data[$rowName][$colName] ?? 0);
    }
    $result .= "\n";
  }

  return $result;
}


// Write the data stored as a string in $csv to $filePath. Return true if the
// data has changed and false if the data has not changed since the last time
// the data was stored.
function storeData(string $csv, string $filePath) {
  $dataHasChanged= false;

  if (file_exists(! $filePath)) {
    file_put_contents($filePath, $csv);
    $dataHasChanged = true;
  } else {
    $previousOutput = file_get_contents($filePath);
    if ($previousOutput != $csv) {
      file_put_contents($filePath, $csv);
      $dataHasChanged = true;
    }
  }

  return $dataHasChanged;
}

function metricGraph($data, $generateGraphCmdBase, $imagesPath,
    $permalinkUrlBase, $graphIdentifier) {

  $dataForGraph = serializeData($data);
  $filePathBase = $imagesPath . $fileName;
  $dataHasChanged = storeData($dataForGraph, $filePathBase . ".csv");

  $permalinkUrl = $permalinkUrlBase . $graphIdentifier;
  $fileName = hash("md5", $permalinkUrl);

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
