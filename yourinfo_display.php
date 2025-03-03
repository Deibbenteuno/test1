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