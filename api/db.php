<?php

$config_file = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
               'config' . DIRECTORY_SEPARATOR .
               'database.yml';

$config = yaml_parse(file_get_contents($config_file));
$config = $config['default'];

function fail($message)
{
    $error = array(
        'error' => 'Invalid request',
        'message' => $message,
    );
    print json_encode($error);
    exit;
}

$link = new \PDO(
    "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
    $config['username'],
    $config['password'],
    array(
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_PERSISTENT => true,
    )
);
