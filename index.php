<?php
session_start();

switch (@$_SESSION['usertype']) {
    case 'admin':
        header('location: ho.php');
        break;
    case 'user':
        header('location: users.php');
    default:
        
        break;
}

?>

<!DOCTYPE html>

<html>

    <head>

        <title> LOGIN </title>

        <link rel="stylesheet" type="text/css" href="style.css">

    </head>

    <body>

        <form action="login.php" method="post">

            <h2>LOGIN</h2>

            <?php if(isset($_GET['error'])) { ?>

            <p class="error"> <?php echo $_GET['error']; ?></p>

        <?php }?>

        <label> User Name</label>

        <input type="text" name="uname" placeholder="Username"><br>

        <label>Password</label>

        <input type="password" name="password" placeholder="Password"><br> 



        <button type="submit">Login</button>

        

        <div class="link">
            <a href="registration_display.php">Sign here</a>
        </div>
            </form>
    </body>

</html>
