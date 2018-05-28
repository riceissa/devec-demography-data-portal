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
      $result .= "|" . ($data[$rowName][$colName] ?? "NA");
    }
    $result .= "\n";
  }

  return $result;
}


// Write the data stored as a string in $csv to $filePath. Return true if the
// data has changed (or the file didn't exist) and false if the data has not
// changed since the last time the data was stored.
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

// Plot the graph for the metric. Return the md5 filename, which can be used to
// display the image.
function metricGraph($data, $generateGraphCmdBase, $imagesPath,
    $permalinkUrlBase, $graphIdentifier) {

  $dataForGraph = serializeData($data);
  $permalinkUrl = $permalinkUrlBase . $graphIdentifier;
  $fileName = hash("md5", $permalinkUrl);
  $filePathBase = $imagesPath . $fileName;
  $dataHasChanged = storeData($dataForGraph, $filePathBase . ".csv");

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

// Get a 1D metric. Here $csv is a string that looks like:
//     maddison2010 (People)|1128007424.0
//     pwt80 (People)|1182378560.0
//     pwt81 (People)|1182378560.0
//     pwt90 (People)|1188056149.3333333
//     ted201505ollp ()|1138053.8333333333
//     ted201509ollp ()|1138053.8333333333
//     ted201605ollp ()|1138053.8333333333
// In other words, the dataset is given in the first column, and the values are
// given on the second column, separated by a pipe.
function getWholeCsv(string $csv, string $sep = "|") {
  $csv = trim($csv);
  $lines = explode("\n", $csv);
  $result = array();
  foreach ($lines as $line) {
    $cols = explode($sep, $line);
    $result[trim($cols[0])] = trim($cols[1]);
  }
  return $result;
}

// Calculate the average and growth rate (linear fit) for each dataset in $data.
// Here $data is organized like as follows:
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
// $permalinkUrlBase and $graphIdentifier are used to see if the stats need to
// be recalculated. Otherwise the same calculation is used again.
function calculateStats($data, $imagesPath, $permalinkUrlBase, $graphIdentifier, $pythonDir) {
  # print "data is as follows:\n<br/>";
  # print_r($data);
  $csv = serializeData($data);
  # print "\n<br/>csv = $csv\n<br/>";
  $permalinkUrl = $permalinkUrlBase . $graphIdentifier;
  # print "permalinkUrl = $permalinkUrl\n<br/>";
  $fileName = hash("md5", $permalinkUrl);
  # print "fileName = $fileName\n<br/>";
  $filePathBase = $imagesPath . $fileName;
  $dataHasChanged = storeData($csv, $filePathBase . ".csv");

  if ($dataHasChanged or !file_exists($filePathBase . "-stats.txt")) {
    if (file_exists($filePathBase . "-stats.txt")) {
      exec("rm " . $filePathBase . "-stats.txt");
    }
    $output = shell_exec("$pythonDir/compute_stats.py " . $filePathBase . ".csv");
    file_put_contents($filePathBase . "-stats.txt", $output);
  } else {
    $output = file_get_contents($filePathBase . "-stats.txt");
    // On at least one occasion, the "-stats.txt" file existed but was empty.
    // I'm not sure how this happened, but in case it happens again, here is a
    // fix to run the command and store the results.
    if (!trim($output)) {
      $output = shell_exec("$pythonDir/compute_stats.py " . $filePathBase . ".csv");
      file_put_contents($filePathBase . "-stats.txt", $output);
    }
  }

  # print "output = $output\n<br/>";
  $outlist = explode("======", $output);
  # print "outlist is as follows:\n<br/>";
  # print_r($outlist);
  $avg = getWholeCsv($outlist[0]);
  # print "avg = $avg\n<br/>";
  $growth = getWholeCsv($outlist[1]);
  return array($avg, $growth);
}

?>
