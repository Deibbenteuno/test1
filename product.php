<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo("Connection failed: " . $conn->connect_error);
}

// Handle product deletion
if (isset($_GET['id'])) {
    $productId = $_GET['id'];

    // Delete product from the database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);

    if ($stmt->execute()) {
        echo "Product deleted successfully!";
    } else {
        echo "Error deleting product: " . $stmt->error;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $Stock = $_POST['Stock']; 
    $price = $_POST['price'];
    
    // Handle file upload
    $targetDir = "uploads/";  // Directory to save the image
    $imageName = basename($_FILES["my_image"]["name"]);
    $targetFile = $targetDir . $imageName;
    $imagePath = $targetFile;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // var_dump($_FILES, $targetFile);
    // echo();

    // Check if file is a valid image
    if (isset($_FILES["my_image"])) {
        $check = getimagesize($_FILES["my_image"]["tmp_name"]);
        if ($check === false) {
            echo("File is not an image.");
            $uploadOk = 0;
        }
    }

    // Check if the file already exists
    if (file_exists($targetFile)) {
        echo("Sorry, file already exists.");
        $uploadOk = 0;
    }

    // Check file size (optional)
    if ($_FILES["my_image"]["size"] > 5000000) {  // Example size limit (500KB)
        echo("Sorry, your file is too large.");
        $uploadOk = 0;
    }

    // Allow certain file formats (optional)
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        $uploadOk = 0;
    }

    // If everything is ok, try to upload the file
    if ($uploadOk == 0) {
        echo("Sorry, your file was not uploaded.");
        // echo('TESTsasa');
    } else {
        if (move_uploaded_file($_FILES["my_image"]["tmp_name"], $targetFile)) {
            echo("The file ". htmlspecialchars($imageName). " has been uploaded.");

            // Prepare and execute the database insertion
            $stmt = $conn->prepare("INSERT INTO products (name, description, Stock, price, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $name, $description, $Stock, $price, $imagePath); 

            if ($stmt->execute()) {
                // header("Location: product.php");
                // exit(); 
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo("Sorry, there was an error uploading your file.");
        }
    }
}


$sql = "SELECT * FROM products";
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
<h1>Inventory</h1>
<nav class="navbar">
        <ul>
            <li><a href="ho.php">Home</a></li>
            <li><a href="userinfo.php">User Info</a></li>
            <li><a href="product_display.php">Product</a></li>
            <li><a href="sales.php">Sales</a></li>
            <li><a href="#">About</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
    <h2>Product Inventory Management</h2>
    <h3>Add New Product</h3>
    <form action="product.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_product" value="1">
        <label for="name">Product Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="description">Description:</label>
        <textarea id="description" name="description"></textarea>
        <label for="Stock">Stock:</label>
        <input type="number" id="Stock" name="Stock" required> 
        <label for="price">Price:</label>
        <input type="number" id="price" name="price" step="0.01" required>
        <button type="submit">Add Product</button>
        <input type="file" name ="my_image">
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
                    echo "<td><a href='product.php?id=".$row['id']."' onclick = 'return confirm(\"Are You Sure?\")'>Delete</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No products found</td></tr>";
            }0
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>