<?php

require_once 'ohana.php';

hardReset();

$tests = array(
    /*
    array(
        'mint', 'description',
        'type', 'year', 'collection', 'expected accession number',
    ),
    */

    array(
        'mint', 'first interview',
        'test', '1900', 'a', '1900test001_a001',
    ),

    array(
        'mint', 'second interview - same inputs',
        'test', '1900', 'a', '1900test002_a002',
    ),

    array(
        'mint', 'new type gets its own counters',
        'oh', '1900', 'a', '1900oh001_a001',
    ),

    array(
        'mint', 'collection and year counters are tracked separately',
        'oh', '1900', 'b', '1900oh002_b001',
    ),

    array(
        'mint', 'second interview in a collection',
        'oh', '1900', 'b', '1900oh003_b002',
    ),

    array(
        'mint', 'new year and collection',
        'oh', '1901', 'c', '1901oh001_c001',
    ),

    array(
        'mint', 'reusing collection and year that had not been used together',
        'oh', '1901', 'a', '1901oh002_a002',
    ),

    array(
        'mint', 'back to an old collection-year combination',
        'oh', '1900', 'a', '1900oh004_a003',
    ),

    /*
    array(
        'record', 'description',
        'accession number', array(expected result),
    ),
     */

    array(
        'record', "can't reuse a year counter even if other parts change",
        '1900test001_a003', array('error' => 'The year and collection counters cannot be reused.'),
    ),

    array(
        'record', 'can record a number that could be minted',
        '1902test002_a004', array('canonical' => '1902test002_a004'),
    ),

    array(
        'mint', 'minting skips past all used counter values',
        'test', '1902', 'a', '1902test003_a005',
    ),

    array(
        'record', 'canonicalization works',
        '1899 OH/ 151 AB 9 Sess 3', array(
            'canonical'    => '1899oh151_ab009',
            'as_submitted' => '1899 OH/ 151 AB 9 Sess 3',
         ),
    ),

    array(
        'record', '5-digit years are not truncated',
        '11902test004_d001', array(
            'canonical'    => '11902test004_d001',
            'as_submitted' => '11902test004_d001',
        ),
    ),

    array(
        'revoke', 'revoking works',
        '1902test002_a004', array('circulating' => 0),
    ),

    array(
        'revoke', 'revoking twice works',
        '1902test002_a004', array('circulating' => 0),
    ),

    array(
        'revoke', 'revoking a nonexistent identifier returns an error',
        '1900test001_a003', array('error' => 'No such accession number exists.'),
    ),

    array(
        'circulate', 'circulating a nonexistent identifier returns an error',
        '1900test001_a003', array('error' => 'No such accession number exists.'),
    ),

    array(
        'circulate', 'circulating works',
        '1899oh151_ab009', array('circulating' => 1),
    ),

    array(
        'circulate', 'circulating twice works',
        '1899oh151_ab009', array('circulating' => 1),
    ),
);

foreach ($tests as $test)
{
    if ($test[0] === 'mint') {
        $description = $test[1];
        $options = array(
            'type'       => $test[2],
            'year'       => $test[3],
            'collection' => $test[4],
        );
        $expected = array(
            'canonical'  => $test[5],
        );
        performMintTest($description, $options, $expected);
    }
    elseif ($test[0] === 'record') {
        $description = $test[1];
        $accessionNumber = $test[2];
        $expected = $test[3];
        performRecordTest($description, $accessionNumber, $expected);
    }
    elseif ($test[0] === 'revoke') {
        $description = $test[1];
        $accessionNumber = $test[2];
        $expected = $test[3];
        performRevokeTest($description, $accessionNumber, $expected);
    }
    elseif ($test[0] === 'circulate') {
        $description = $test[1];
        $accessionNumber = $test[2];
        $expected = $test[3];
        performCirculateTest($description, $accessionNumber, $expected);
    }
}
