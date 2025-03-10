<?php
session_start();

// Check if user is already logged in as a regular user
if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'user') {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

if (isset($_POST['Stock'])) {
    $stock = intval($_POST['Stock']);
    
    // Ensure stock is non-negative
    if ($stock < 0) {
        $stock = 0; // Set stock to 0 if negative value is provided
    }
}

// Handle product addition via POST
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

    // Check if file is a valid image
    if (isset($_FILES["my_image"])) {
        $check = getimagesize($_FILES["my_image"]["tmp_name"]);
        if ($check === false) {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    }

    // Check if the file already exists
    if (file_exists($targetFile)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size (optional)
    if ($_FILES["my_image"]["size"] > 5000000) {  // Example size limit (5MB)
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats (optional)
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // If everything is ok, try to upload the file
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    } else {
        if (move_uploaded_file($_FILES["my_image"]["tmp_name"], $targetFile)) {
            echo "The file ". htmlspecialchars($imageName) . " has been uploaded.";

            // Prepare and execute the database insertion
            $stmt = $conn->prepare("INSERT INTO products (name, description, Stock, price, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $name, $description, $Stock, $price, $imagePath); 

            if ($stmt->execute()) {
                // Redirect to the product display page after successful insertion
                header("Location: product_display.php");
                exit();  // Important to stop further script execution
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch products to display (if needed for listing or managing)
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

// Close the database connection
$conn->close();
?>
