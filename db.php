<?php
session_start();
$host = "127.0.0.1";
$user = "root";
$pass = ""; 
$dbname = "my_store";

// Connect
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection before setting charset
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// If connection successful, set charset
mysqli_set_charset($conn, "utf8mb4");
?>