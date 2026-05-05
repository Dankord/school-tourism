<?php

$env = parse_ini_file(__DIR__. '/.env');

$connection = new mysqli(
    $env['DB_HOST'],
    $env['DB_USER'],
    $env['DB_PASSWORD'],
    $env['DB_NAME']
);

if($connection->connect_error) {
    die("DATABASE CONNECTION FAILED");
}