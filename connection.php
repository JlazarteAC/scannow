<?php
    $servername = "localhost";
    $username   = "scannow";
    $password   = "123";
    $db_name    = "scannow";
    $conn       = new mysqli($servername, $username, $password, $db_name);
    if($conn->connect_error){
        die("Connection failed".$conn->connect_error);
    }
?>