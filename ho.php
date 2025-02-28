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
                    <li><a href="userinfo.php">User Info</a></li>
                    <li><a href="product.php">Product</a></li>
                    <li><a href="sales.php">Sales</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="logout.php">Log Out</a></li>
                </ul>
            </nav>

            <main>
                <section class="grid-container">
                    <article class="grid-item">
                        <h2>"Discover More, Shop More Your Perfect Product is Here!"</h2>
                        With our vast and constantly updated inventory, 
                        you’re bound to find exactly what you’re looking for.
                        Whether it’s something trendy, essential, or unique, 
                        we have it all at your fingertips. 
                        Our collection is carefully curated to meet the ever-changing needs and tastes of our customers, 
                        ensuring that there’s always something new to explore. 
                        From the latest fashion trends to everyday essentials and hard-to-find gems, 
                        we bring you a wide variety of high-quality products that cater to every lifestyle. 
                        Plus, our intuitive online platform makes it easy to search, filter, and discover products that perfectly match your preferences. Every time you visit, you’re sure to find something fresh and exciting that enhances your shopping experience. So why wait? Start browsing now and experience the joy of finding your perfect product in our comprehensive and ever-evolving inventory!
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
