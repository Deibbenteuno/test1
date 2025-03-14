<?php
session_start();

if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
    // Redirect to index page if user is logged in
    header("location:index.php");
    exit;
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Checking</title>
    <link rel="stylesheet" href="pro.css">
    <script>
        window.onload = function() {
            document.getElementById('barcode').focus();
        }
    </script>
</head>
<body>
    <nav class="navbar">
    <?php include_once('header.php') ?>
    </nav>
    <?php
if (isset($_SESSION['error_message'])) {
    echo "<div class='error-message'>" . $_SESSION['error_message'] . "</div>";
    unset($_SESSION['error_message']); // Clear the error message after displaying it
}
?>

    <form action="" method="POST">
        <label for="barcode">Scan your barcode:</label>
        <input type="text" name="barcode" id="barcode" required>
        <button type="submit">Submit</button>
    </form>

    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['barcode'])) {
                $barcode = trim($_POST['barcode']);

                if ($barcode == "endcode") {
                    echo "<p>Shopping done</p>";
                } else {
                    // Prepare SQL query to fetch product details from the database
                    $sql = "SELECT id, name, price, Stock FROM products WHERE id = ?";
                    $stmt = $conn->prepare($sql);

                    // Check if the statement preparation was successful
                    if ($stmt === false) {
                        echo "<p>Error preparing query: " . $conn->error . "</p>";
                    } else {
                        // Bind the barcode (now using 'id') to the query
                        $stmt->bind_param("i", $barcode); // 'i' for integer, assuming 'id' is an integer
                        $stmt->execute();
                        $stmt->store_result();
                        $stmt->bind_result($id, $name, $price, $stock);

                        if ($stmt->num_rows > 0) {
                            // Item found, display details
                            while ($stmt->fetch()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($name) . "</td>";  // Display product name safely
                                echo "<td>" . number_format($price, 2) . "</td>";  // Display formatted price
                                echo "<td>" . $stock . "</td>";  // Display stock
                                echo "</tr>";
                            }
                        } else {
                            $_SESSION['error_message'] = "Product not found! $barcode" ;
                            header("Location: price_checking.php");
                            exit();
                        }

                        $stmt->close();
                    }
                }
            }
            ?>
        </tbody>
    </table>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>