<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
        window.onload = function() {
            document.getElementById('barcode').focus();
        }
    </script>
</head>
<body>
<form action="" method="POST" id="form">
    <label for="barcode">Scan your barcode:</label>
    <input type="text" name="barcode" id="barcode" required>
    <button type="submit">Submit</button>
</form>

<form action="" method="GET" id="resetForm">
    <button type="submit" name="reset" value="1">Reset Cart</button>
</form>

<div>
    <h2>Shopping Cart</h2>

    <?php
    // Start the session
    session_start();

    // Check if the reset parameter is present in the URL to reset the cart
    if (isset($_GET['reset']) && $_GET['reset'] == '1') {
        // Reset total bill and session data
        $_SESSION['total_bill'] = 0;
        echo "<p>Your cart has been reset.</p>";
        // Redirect to the same page to refresh the cart state
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;  // Ensure no further code is executed after the redirect
    }

    // Database connection details
    $servername = "localhost"; // or your server address
    $username = "root";        // your database username
    $password = "";            // your database password
    $dbname = "inventory";     // your database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Initialize total_bill in session if not already set
    if (!isset($_SESSION['total_bill'])) {
        $_SESSION['total_bill'] = 0;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['barcode'])) {
        $barcode = trim($_POST['barcode']);

        if ($barcode == "endcode") {
            echo "<p>Shopping done</p>";
        } else {
            // Prepare SQL query to fetch product details from the database
            // Assuming 'barcode' is actually referring to the 'id' column in the database
            $sql = "SELECT name, price FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);

            // Check if the statement preparation was successful
            if ($stmt === false) {
                echo "<p>Error preparing query: " . $conn->error . "</p>";
            } else {
                // Bind the barcode (now using 'id') to the query
                $stmt->bind_param("i", $barcode); // 'i' for integer, assuming 'id' is an integer
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($name, $price);

                if ($stmt->num_rows > 0) {
                    // Item found, display details
                    $stmt->fetch();
                    echo "<p>This is: $name, price is: $price</p>";
                    // Add the price to the total bill stored in the session
                    $_SESSION['total_bill'] += $price;
                } else {
                    echo "<p>Item not found: $barcode</p>";
                }

                $stmt->close();
            }
        }
    }

    // Display the total bill stored in session
    echo "<p>Your total bill is: " . $_SESSION['total_bill'] . "</p>";

    // Close the database connection
    $conn->close();
    ?>

</div>
</body>
</html>
