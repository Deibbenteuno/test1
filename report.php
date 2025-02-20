<?php

require __DIR__ . '/vendor/autoload.php';

use PHPJasper\PHPJasper;    

// $input = __DIR__ . '/report/receipts.jrxml';   

// $jasper = new PHPJasper;
// $jasper->compile($input)->execute();


$input = __DIR__ . '/report/finaltest.jrxml';   
$output = __DIR__ . '/report';
$options = [
    'format' => ['pdf'],
    'locale' => 'en',
    'params' => [],
    'db_connection' => [
        'driver' => 'mysql', //mysql, ....
        'username' => 'root',
        'host' => 'localhost',
        'database' => 'inventory',
    ]
];

$jasper = new PHPJasper;

$jasper->process(
        $input,
        $output,
        $options
)->execute();

header('Content-type: application/pdf');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');

// Read the file
@readfile(__DIR__ . '/report/finaltest.pdf');