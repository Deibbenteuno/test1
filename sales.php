<?php
// Database connection details
$sname = "localhost";
$uname = "root";
$password = "";
$dbname = "inventory";  // Ensure this matches your database in phpMyAdmin

// Create the connection
$conn = new mysqli($sname, $uname, $password, $dbname);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include the Barcode library
require_once 'vendor/autoload.php';  // Assuming you're using Composer for dependency management
use Picqer\Barcode\BarcodeGeneratorHTML;  // Updated to use the Picqer Barcode library

// SQL query to fetch sales data along with associated product details (no sale date)
$sql = "SELECT p.id, p.name, p.description, p.price, p.stock, 
                COALESCE(SUM(s.quantity), 0) AS quantity_sold
        FROM products p
        LEFT JOIN sales s ON p.id = s.product_id
        GROUP BY p.id"; // GROUP BY is used to sum up sales per product

$sql = "SELECT p.id, p.name, p.description, p.price, p.stock, 
                COALESCE(SUM(s.quantity), 0) AS quantity_sold
        FROM products p
        LEFT JOIN receipt_items s ON p.id = s.product_id
        GROUP BY p.id"; // GROUP BY is used to sum up sales per product

$result = $conn->query($sql);

// Function to generate barcode with standard size
function generateBarcode($productId) {
    // Create an instance of the BarcodeGenerator class
    $generator = new BarcodeGeneratorHTML();
    
    // Generate the barcode in Code 128 format with standard size (adjusted width and height)
    $barcode = $generator->getBarcode($productId, BarcodeGeneratorHTML::TYPE_CODE_128, 2, 50);  // Adjusted width (2) and height (50)
    
    return $barcode;
}

// Function to ensure product ID is 13 characters
function formatProductId($productId) {
    // Pad the product ID with leading zeros to make it 13 characters long
    return str_pad($productId, 13, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="pro.css">
    <title>Inventory</title>
</head>
<body>

<h1>Inventory</h1>
<nav class="navbar">
    <ul>
        <li><a href="ho.php">Home</a></li>
        <li><a href="userinfo.php">User Info</a></li>
        <li><a href="product.php">Product</a></li>
        <li><a href="sales.php">Sales</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="logout.php">Log Out</a></li>
    </ul>
</nav>

<h2>Sales List</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Sales Quantity</th>
            <th>Total Sales Amount</th>
            <th>Barcode</th> <!-- Column for Barcode -->
        </tr>
    </thead>
    <tbody>

    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Debugging: Check the raw data
            // var_dump($row);

            // Use the product ID and ensure it's 13 characters long
            $productId = formatProductId($row['id']);
            
            // Calculate total sales amount: price * quantity sold
            $totalSalesAmount = $row['price'] * $row['quantity_sold'];

            // Generate the barcode for the current product
            $barcodeHtml = generateBarcode($productId);

            echo "<tr>";
            echo "<td>".$productId."</td>";  // Display the padded product ID
            echo "<td>".$row['name']."</td>";
            echo "<td>".$row['description']."</td>";
            echo "<td>".$row['stock']."</td>";
            echo "<td>".$row['price']."</td>";
            echo "<td>".$row['quantity_sold']."</td>";
            echo "<td> ₱ ".number_format($totalSalesAmount, 2)."</td>"; // Display the calculated total sales amount
            // Embed the barcode HTML
            echo "<td>".$barcodeHtml."</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='8'>No sales found</td></tr>";
        
    }
    ?>
    </tbody>
</table>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
