<?php

require_once 'db.php';

/* Mint a new accession number
 *
 * Input:
 *   - type abbreviation (default: 'oh')
 *   - year
 *   - collection
 * Output:
 *   - a new accession number with the given properties
 * Errors:
 *   - "An accession number cannot be minted."
 * Side effects:
 *   - The year and collection counters for the given type abbreviation
 *     are incremented.
 *   - The accession number is marked issued and circulating.
 */
function mintAccessionNumber($type, $year, $collection)
{
    lockTables();
    $result = array(
        'error' => 'An accession number cannot be minted.',
    );
    try {
        $typeId = ensureTypeId($type);
        $yearCounter = incrementYearCounter($type, $year);
        $collectionCounter = incrementCollectionCounter($type, $collection);
        $canonical = canonicalAccessionNumber($type, $year, $yearCounter['value'], $collection, $collectionCounter['value']);
        insertAccessionNumber($canonical, $canonical, $yearCounter['year_id'], $collectionCounter['collection_id'], $typeId, TRUE);
        $result = getAccessionNumber($canonical);
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
    return $result;
    unlockTables();
}

/* Check the state of an accession number
 *
 * Input: accession number
 * Output:
 *   - the canonical rendering of the accession number
 *   - a non-canonical rendering, if different
 *   - issued (true or false)
 *   - circulating (true or false)
 * Errors:
 *   - "The accession number submitted cannot be parsed."
 * Side effects: none
 */
function getAccessionNumberState($accessionNumber)
{
    lockTables();
    $result = getAccessionNumber($accessionNumber);
    unlockTables();
    return $result;
}

/* Record a preexisting accession number
 *
 * Input: accession number
 * Output:
 *   - the canonical rendering of the accession number
 *   - the non-canonical submitted rendering
 * Errors:
 *   - "That accession number already exists."
 *   - "The accession number submitted cannot be parsed."
 * Side effects:
 *   - The accession number is parsed and canonicalized.
 *   - If necessary, year and collection counters for the given
 *     type abbreviation are created and initialized to 0.
 *   - The year counter is set to the maximum of its current value
 *     plus 1 and the year counter in the submitted accession number.
 *   - Similarly for the collection counter.
 *   - The accession number is marked as issued and circulating.
 *   - If the accession number is accepted at all, the submitted string
 *     (with leading and trailing whitespace trimmed) is stored as the
 *     non-canonical rendering of the accession number.
 */
function recordAccessionNumber($accessionNumber)
{
    global $link;
    lockTables();
    $anHash = getAccessionNumber($accessionNumber);
    if (array_key_exists('error', $anHash)) {
        /*
         * Only record a new accession number if it doesn't exist
         * yet and can be parsed.
         */
        if ($anHash['error'] === 'No such accession number exists.') {
            $anHash = parseAccessionNumber($accessionNumber);
            $typeId = ensureTypeId($anHash['type']);

            $yearCounter = getYearCounter($anHash['type'], $anHash['year'], $anHash['year_count']);
            $collectionCounter = getCollectionCounter($anHash['type'], $anHash['collection'], $anHash['collection_count']);

            if (isset($yearCounter['id']) || isset($collectionCounter['id'])) {
                $result = array(
                    'error' => 'The year and collection counters cannot be reused.',
                );
            }
            else {
                $yearCounter = insertYearCounter($anHash['type'], $anHash['year'], $anHash['year_count']);
                $collectionCounter = insertCollectionCounter($anHash['type'], $anHash['collection'], $anHash['collection_count']);
                $canonical = canonicalAccessionNumber($anHash['type'], $anHash['year'], $yearCounter['value'], $anHash['collection'], $collectionCounter['value']);
                insertAccessionNumber($canonical, $anHash['as_submitted'], $yearCounter['year_id'], $collectionCounter['collection_id'], $typeId, TRUE);
                $result = getAccessionNumber($canonical);
            }
        }
        else {
            $result = $anHash;
        }
    }
    else {
        $result = array(
            'error' => 'That accession number already exists.',
        );
    }
    unlockTables();
    return $result;
}

/* Circulate an accession number
 *
 * Input: accession number
 * Output:
 *   - the canonical rendering of the accession number
 *   - circulating (true or false)
 * Errors:
 *   - "The accession number does not exist."
 *   - "The accession number submitted cannot be parsed."
 * Side effects:
 *   - The accession number is marked as circulating.  If it was already
 *     recorded as circulating, this has no effect.
 */
function circulateAccessionNumber($accessionNumber)
{
    global $link;
    lockTables();
    $anHash = getAccessionNumber($accessionNumber);
    if (array_key_exists('error', $anHash)) {
        $result = $anHash;
    }
    else {
        try {
            $handle = $link->prepare(
                'UPDATE identifier SET circulating = TRUE WHERE canonical = ?'
            );
            $handle->bindValue(1, $anHash['canonical']);
            $handle->execute();
            $result = getAccessionNumber($accessionNumber);
        }
        catch (\PDOException $ex) {
            fail($ex);
        }
    }
    unlockTables();
    return $result;
}

/* Revoke an accession number
 *
 * Input: accession number
 * Output:
 *   - the canonical rendering of the accession number
 *   - circulating (true or false)
 * Errors:
 *   - "The accession number does not exist."
 *   - "The accession number submitted cannot be parsed."
 * Side effects:
 *   - The accession number is marked as not circulating.  If it was already
 *     circulating, this has no effect.
 */
function revokeAccessionNumber($accessionNumber)
{
    global $link;
    lockTables();
    $anHash = getAccessionNumber($accessionNumber);
    if (array_key_exists('error', $anHash)) {
        $result = $anHash;
    }
    else {
        try {
            $handle = $link->prepare(
                'UPDATE identifier SET circulating = FALSE WHERE canonical = ?'
            );
            $handle->bindValue(1, $anHash['canonical']);
            $handle->execute();
            $result = getAccessionNumber($accessionNumber);
        }
        catch (\PDOException $ex) {
            fail($ex);
        }
    }
    unlockTables();
    return $result;
}

function insertAccessionNumber($canonical, $as_submitted, $yearId, $collectionId, $typeId, $circulating) {
    global $link;
    try {
        $handle = $link->prepare(
            'INSERT INTO identifier (canonical, as_submitted, year_id, collection_id, type_id, circulating) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $handle->bindValue(1, $canonical);
        $handle->bindValue(2, $as_submitted);
        $handle->bindValue(3, $yearId);
        $handle->bindValue(4, $collectionId);
        $handle->bindValue(5, $typeId);
        $handle->bindValue(6, $circulating);
        $handle->execute();
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function getAccessionNumber($accessionNumber)
{
    global $link;
    try {
        $anHash = parseAccessionNumber($accessionNumber);
        if (array_key_exists('error', $anHash)) {
            return $anHash;
        }
        else {
            $handle = $link->prepare(
                'SELECT * FROM identifier WHERE canonical = ?'
            );
            $handle->bindValue(1, $anHash['canonical']);
            $handle->execute();
            $result = $handle->fetchAll(\PDO::FETCH_OBJ);
            if (count($result) > 0) {
                $anHash['year_id'] = intval($result[0]->year_id);
                $anHash['collection_id'] = intval($result[0]->collection_id);
                $anHash['type_id'] = intval($result[0]->type_id);
                $anHash['circulating'] = intval($result[0]->circulating);
                $anHash['canonical'] = $result[0]->canonical;
                $anHash['as_submitted'] = $result[0]->as_submitted;
                return $anHash;
            }
            else {
                return array(
                    'error' => 'No such accession number exists.',
                );
            }
        }
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function parseAccessionNumber($accessionNumber)
{
    $strippedAccessionNumber = preg_replace(
        '/[^a-z0-9]/',
        '',
        strtolower($accessionNumber)
    );
    if (preg_match(
        '/^(\d+)([a-z]+)(\d+)([a-z]+)(\d+)/',
        $strippedAccessionNumber,
        $matches)) {
        $year            = $matches[1];
        $type            = $matches[2];
        $yearCount       = $matches[3];
        $collection      = $matches[4];
        $collectionCount = $matches[5];
        $canonical         = canonicalAccessionNumber($type, $year, $yearCount, $collection, $collectionCount);
        return array(
            'canonical'        => $canonical,
            'as_submitted'     => $accessionNumber,
            'type'             => $type,
            'year'             => $year,
            'year_count'       => $yearCount,
            'collection'       => $collection,
            'collection_count' => $collectionCount,
        );
    }
    else {
        return array(
            'error' => 'The accession number submitted cannot be parsed.',
        );
    }
}

function getYearCounter($type, $year, $value)
{
    global $link;
    $result = array();
    try {
        $typeId = ensureTypeId($type);
        $yearId = ensureYearId($year);
        $select = $link->prepare(
            'SELECT * FROM year_counter WHERE type_id = ? AND year_id = ? AND value = ?'
        );
        $select->bindValue(1, $typeId);
        $select->bindValue(2, $yearId);
        $select->bindValue(3, $value);
        $select->execute();
        $years = $select->fetchAll(\PDO::FETCH_OBJ);
        if (count($years) > 0) {
            $result = array(
                'id' => $years[0]->id,
                'value' => $years[0]->value,
                'year_id' => $years[0]->year_id,
                'type_id' => $years[0]->type_id,
            );
        }
        else {
            $result = array(
                'error' => 'No such year counter exists.',
            );
        }
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
    return $result;
}

function getCollectionCounter($type, $collection, $value)
{
    global $link;
    $result = array();
    try {
        $typeId = ensureTypeId($type);
        $collectionId = ensureCollectionId($collection);
        $select = $link->prepare(
            'SELECT * FROM collection_counter WHERE type_id = ? AND collection_id = ? AND value = ?'
        );
        $select->bindValue(1, $typeId);
        $select->bindValue(2, $collectionId);
        $select->bindValue(3, $value);
        $select->execute();
        $collections = $select->fetchAll(\PDO::FETCH_OBJ);
        if (count($collections) > 0) {
            $result = array(
                'id' => $collections[0]->id,
                'value' => $collections[0]->value,
                'collection_id' => $collections[0]->collection_id,
                'type_id' => $collections[0]->type_id,
            );
        }
        else {
            $result = array(
                'error' => 'No such collection counter exists.',
            );
        }
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
    return $result;
}

function insertYearCounter($type, $year, $value)
{
    global $link;
    $yearCounter = getYearCounter($type, $year, $value);
    if (isset($yearCounter['id'])) {
        $result = array(
            'error' => 'Year counters cannot be reused.',
        );
    }
    else {
        try {
            $typeId = ensureTypeId($type);
            $yearId = ensureYearId($year);
            $insertion = $link->prepare(
                'INSERT INTO year_counter (type_id, year_id, value) VALUES (?, ?, ?)'
            );
            $insertion->bindValue(1, $typeId);
            $insertion->bindValue(2, $yearId);
            $insertion->bindValue(3, $value);
            $insertion->execute();
            return getYearCounter($type, $year, $value);
        }
        catch (\PDOException $ex) {
            fail($ex);
        }
    }
    return $result;
}

function insertCollectionCounter($type, $collection, $value)
{
    global $link;
    $collectionCounter = getCollectionCounter($type, $collection, $value);
    if (isset($collectionCounter['id'])) {
        $result = array(
            'error' => 'Collection counters cannot be reused.',
        );
    }
    else {
        try {
            $typeId = ensureTypeId($type);
            $collectionId = ensureCollectionId($collection);
            $insertion = $link->prepare(
                'INSERT INTO collection_counter (type_id, collection_id, value) VALUES (?, ?, ?)'
            );
            $insertion->bindValue(1, $typeId);
            $insertion->bindValue(2, $collectionId);
            $insertion->bindValue(3, $value);
            $insertion->execute();
            return getCollectionCounter($type, $collection, $value);
        }
        catch (\PDOException $ex) {
            fail($ex);
        }
    }
    return $result;
}

function incrementYearCounter($type, $year)
{
    global $link;
    try {
        $typeId = ensureTypeId($type);
        $yearId = ensureYearId($year);
        $select = $link->prepare(
            'SELECT value FROM year_counter WHERE type_id = ? AND year_id = ? ORDER BY value DESC'
        );
        $select->bindValue(1, $typeId);
        $select->bindValue(2, $yearId);
        $select->execute();
        $counter = $select->fetchAll(\PDO::FETCH_OBJ);
        /* Does the counter already exist? */
        if (count($counter) > 0) {
            $value = $counter[0]->value + 1;
        }
        else {
            $value = 1;
        }
        return insertYearCounter($type, $year, $value);
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function incrementCollectionCounter($type, $collection)
{
    global $link;
    try {
        $typeId = ensureTypeId($type);
        $collectionId = ensureCollectionId($collection);
        $select = $link->prepare(
            'SELECT value FROM collection_counter WHERE type_id = ? AND collection_id = ? ORDER BY value DESC'
        );
        $select->bindValue(1, $typeId);
        $select->bindValue(2, $collectionId);
        $select->execute();
        $counter = $select->fetchAll(\PDO::FETCH_OBJ);
        /* Does the counter already exist? */
        if (count($counter) > 0) {
            $value = $counter[0]->value + 1;
        }
        else {
            $value = 1;
        }
        return insertCollectionCounter($type, $collection, $value);
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function canonicalAccessionNumber($type, $year, $yearCounter, $collection, $collectionCounter)
{
    return sprintf('%04d%s%03d_%s%03d', $year, $type, $yearCounter, $collection, $collectionCounter);
}

function ensureTypeId($type)
{
    return ensureId('type', 'type', $type);
}

function ensureYearId($year)
{
    return ensureId('year', 'year', $year);
}

function ensureCollectionId($collection)
{
    return ensureId('collection', 'collection', $collection);
}

function ensureId($table, $column, $value)
{
    global $link;
    try {
        $handle = $link->prepare(
            "SELECT id FROM $table WHERE $column = ?"
        );
        $handle->bindValue(1, $value);
        $handle->execute();
        $result = $handle->fetchAll(\PDO::FETCH_OBJ);
        if (count($result) > 0) {
            return intval($result[0]->id);
        }
        else {
            $insertion = $link->prepare(
                "INSERT INTO $table ($column) VALUES (?)"
            );
            $insertion->bindValue(1, $value);
            $insertion->execute();
            return intval($link->lastInsertId());
        }
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function lockTables()
{
    global $link;
    try {
        $link->exec('LOCK TABLES `identifier` WRITE, `year` WRITE, `type` WRITE, `collection` WRITE, `year_counter` WRITE, `collection_counter` WRITE');
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function unlockTables()
{
    global $link;
    try {
        $link->exec('UNLOCK TABLES');
    }
    catch (\PDOException $ex) {
        fail($ex);
    }
}

function hardReset()
{
    global $link;
    lockTables();
    $link->exec('DELETE FROM year_counter');
    $link->exec('DELETE FROM collection_counter');
    $link->exec('DELETE FROM identifier');
    $link->exec('DELETE FROM year');
    $link->exec('DELETE FROM type');
    $link->exec('DELETE FROM collection');
    unlockTables();
}

function announceMintTest($options)
{
    print "Test: mintAccessionNumber(" . $options['type'] . ", " . $options['year'] . ", " . $options['collection'] . ")\n";
}

function checkExpectations($description, $expected, $got)
{
    $ok = true;
    foreach ($expected as $key => $value) {
        if (!array_key_exists($key, $got)) {
            $ok = false;
            print "NOT ok $description: expected result to have key $key, but it does not exist\n";
        }
        elseif ($got[$key] !== $value) {
            $ok = false;
            print "NOT ok $description: expected value of $key to be $value, got " . $got[$key] . "\n";
            return;
        }
    }
    if ($ok) {
        print "ok $description\n";
    }
}

function performMintTest($description, $options, $expected)
{
    $got = mintAccessionNumber($options['type'], $options['year'], $options['collection']);
    checkExpectations($description, $expected, $got);
}

function performRecordTest($description, $accessionNumber, $expected)
{
    $got = recordAccessionNumber($accessionNumber);
    checkExpectations($description, $expected, $got);
}

function performRevokeTest($description, $accessionNumber, $expected)
{
    $got = revokeAccessionNumber($accessionNumber);
    checkExpectations($description, $expected, $got);
}

function performCirculateTest($description, $accessionNumber, $expected)
{
    $got = circulateAccessionNumber($accessionNumber);
    checkExpectations($description, $expected, $got);
}
