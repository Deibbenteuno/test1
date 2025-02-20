<?php

$sname = "localhost";
$uname = "root";
$dbpassword = "";
$db_name = "inventory";
$conn = mysqli_connect($sname, $uname, $dbpassword, $db_name);



// if(!$conn) {

//     echo "Connection Failed"; 

// }

if (mysqli_connect_errno()) {

    echo "Failed to connect to MySQL: " . mysqli_connect_error();

    exit();

  }