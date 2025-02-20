<?php

session_start();

if (isset($_SESSION['id']) && isset($_SESSION['username'])) {
    // Store user name in a variable
    $username = $_SESSION['username'];
    ?>

    <!DOCTYPE html>
    <html>
        <head>
            <title>HOME</title>
            <link rel="stylesheet" type="text/css" href="iba.css">
            <style>
                .greeting {
                    position: absolute;
                    top: 10px;
                    left: 10px;
                    font-size: 18px;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
   
            <div class="greeting">Hi, <?php echo htmlspecialchars($username); ?>!</div>

            <h1>Inventory</h1>

            <nav class="navbar">
                <ul>
                    <li><a href="ho.php">Home</a></li>
                    <li><a href="userinfo.php">User_info</a></li>
                    <li><a href="product.php">Product</a></li>
                    <li><a href="sales.php">Sales</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="logout.php">Log Out</a></li>
                </ul>
            </nav>

            <main>
                <section class="grid-container">
                    <article class="grid-item">
                        <h2>Lorem Ipsum</h2>
                        Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
                        Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, 
                        when an unknown printer took a galley of type and scrambled it to make a type specimen 
                        book. It has survived not only five centuries, but also the leap into electronic 
                        typesetting, remaining essentially unchanged. It was popularised in the 1960s with the 
                        release of Letraset sheets containing Lorem Ipsum passages, and more recently with 
                        desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
                    </article>
                </section>
            </main>

        </body>
    </html>

    <?php
} else {
    header("Location: index.php");
    exit();
}
