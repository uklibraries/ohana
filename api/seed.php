<?php

require_once 'ohana.php';

hardReset();

$handle = fopen('seed.txt', 'r');
if ($handle) {
    while (!feof($handle)) {
        $accessionNumber = trim(fgets($handle));
        if (isset($accessionNumber) and strlen($accessionNumber) > 0) {
            $response = recordAccessionNumber($accessionNumber);
            $response['original'] = $accessionNumber;
            print json_encode($response) . "\n";
        }
    }
}
