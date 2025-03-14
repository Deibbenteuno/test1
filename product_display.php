<?php
session_start();

// Check if the user is logged in and has the correct user type
if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
    // Redirect to home page if the user is logged in as 'user'
    header("location:index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle product deletion
if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'delete') {
    $product_id = intval($_GET['id']);
    if ($product_id > 0) {
        $sql = "DELETE FROM products WHERE id = $product_id";
        if ($conn->query($sql) === TRUE) {
            header("Location: product_display.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Handle product update
if (isset($_POST['update_product'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $stock = max(0, intval($_POST['Stock']));
    $price = max(0, floatval($_POST['price']));

    $sql = "UPDATE products SET name='$name', description='$description', Stock='$stock', price='$price' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header("Location: product_display.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle new product insertion
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $stock = max(0, intval($_POST['Stock']));
    $price = max(0, floatval($_POST['price']));

    $sql = "INSERT INTO products (name, description, Stock, price) VALUES ('$name', '$description', '$stock', '$price')";
    if ($conn->query($sql) === TRUE) {
        header("Location: product_display.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch product data
$sql = "SELECT id, name, description, Stock, price FROM products";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" type="text/css" href="pro.css">
</head>
<body>
<nav class="navbar">
<?php include_once('header.php') ?>
</nav>
<h2>Product Inventory Management</h2>

<?php
// Check if editing a product
if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'edit') {
    $product_id = intval($_GET['id']);
    $edit_sql = "SELECT * FROM products WHERE id = $product_id";
    $edit_result = $conn->query($edit_sql);
    if ($edit_result->num_rows == 1) {
        $edit_row = $edit_result->fetch_assoc();
?>
<h3>Edit Product</h3>
<form action="product_display.php" method="POST">
    <input type="hidden" name="update_product" value="1">
    <input type="hidden" name="id" value="<?php echo $edit_row['id']; ?>">
    <label for="name">Product Name:</label>
    <input type="text" id="name" name="name" value="<?php echo $edit_row['name']; ?>" required>
    <label for="description">Description:</label>
    <textarea id="description" name="description"><?php echo $edit_row['description']; ?></textarea>
    <label for="Stock">Stock:</label>
    <input type="number" id="Stock" name="Stock" value="<?php echo $edit_row['Stock']; ?>" min="0" required>
    <label for="price">Price:</label>
    <input type="number" id="price" name="price" value="<?php echo $edit_row['price']; ?>" min="0" step="0.01" required>
    <button type="submit">Update Product</button>
</form>
<?php
    }
}
?>

<h3>Add New Product</h3>
<form action="product_display.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="add_product" value="1">
    <label for="name">Product Name:</label>
    <input type="text" id="name" name="name" required>
    <label for="description">Description:</label>
    <textarea id="description" name="description"></textarea>
    <label for="Stock">Stock:</label>
    <input type="number" id="Stock" name="Stock" min="0" required>
    <label for="price">Price:</label>
    <input type="number" id="price" name="price" min="0" step="0.01" required>
    <button type="submit">Add Product</button>
</form>

<h4>Product List</h4>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>".$row['id']."</td>";
                echo "<td>".$row['name']."</td>";
                echo "<td>".$row['description']."</td>";
                echo "<td>".$row['Stock']."</td>";
                echo "<td>".$row['price']."</td>";
                echo "<td>";
                echo "<a href='product_display.php?id=".$row['id']."&action=edit'>Edit</a> | ";
                echo "<a href='product_display.php?id=".$row['id']."&action=delete' onclick='return confirm(\"Are you sure you want to delete this product?\")'>Delete</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No products found</td></tr>";
        }
        $conn->close();
        ?>
    </tbody>
</table>
</body>
</html>
