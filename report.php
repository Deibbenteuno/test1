<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['usertype'])) {
    header("Location: index.php?error=You must log in first");
    exit();
}

require __DIR__ . '/vendor/autoload.php';
use PHPJasper\PHPJasper;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$receipt_id = intval($_GET['id']);
$user_id = $_SESSION['id'];

// Fetch receipt data
$query = "SELECT * FROM receipts WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $receipt_id, $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("BAWAL HIHIHI!!");
}

// Define Jasper parameters
$input = __DIR__ . '/report/Final.jrxml';
$output = __DIR__ . '/report';
$options = [
    'format' => ['pdf'],
    'locale' => 'en',
    'params' => [
        'Staff' => 'Harold', // Should come from session or DB
        'Customer' => 'Vivo', // Fetch from DB if possible
        'Barcode' => '',
        'receipt_id' => $receipt_id
    ],
    'db_connection' => [
        'driver' => 'mysql',
        'username' => 'root',
        'host' => 'localhost',
        'database' => 'inventory',
    ]
];

$jasper = new PHPJasper();
$jasper->process($input, $output, $options)->execute();

$pdf_file = __DIR__ . '/report/Final.pdf';
if (!file_exists($pdf_file)) {
    die("Error generating PDF.");
}

// Output PDF to browser
header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="receipt.pdf"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
readfile($pdf_file);

// Close database connection
$conn->close();
?>
