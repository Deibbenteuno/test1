<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['id']) || !isset($_SESSION['usertype'])) {
    header("Location: index.php?error=You must log in first");
    exit();
}
?>

<?php

require __DIR__ . '/vendor/autoload.php';

use PHPJasper\PHPJasper;    

// $input = __DIR__ . '/report/receipts.jrxml';   

// $jasper = new PHPJasper;
// $jasper->compile($input)->execute();

if (isset($_GET['id'])) {
    // Fetch only the selected receipt
    $receipt_id = $_GET['id'];
    $result = "SELECT * FROM receipts WHERE id = ? AND user_id = ?;";
    $result = $conn->prepare($result);
    $result->bind_param("ii", $receipt_id, $_SESSION['id']);
}
else{
    echo("d iyo to");
}

if (isset($result)) {
    $result->execute();
    
}

$input = __DIR__ . '/report/Final.jrxml';   
$output = __DIR__ . '/report';
$options = [
    'format' => ['pdf'],
    'locale' => 'en',
    'params' => [
        'Staff' => 'Harold',
        'Customer' => 'Vivo',
        'Barcode' => '',
        'receipt_id' => $_GET['id']
    ],
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
@readfile(__DIR__ . '/report/Final.pdf');