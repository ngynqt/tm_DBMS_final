<?php
session_start();

$master_host = "127.0.0.1";
$master_port = 3308;
$master_user = "root";
$master_pass = "rootpassword";
$master_dbname = "my_store";

$slave_host = "127.0.0.1";
$slave_port = 3307;
$slave_user = "root";
$slave_pass = "rootpassword";
$slave_dbname = "my_store";

$conn_master = @mysqli_connect($master_host, $master_user, $master_pass, $master_dbname, $master_port);

if (!$conn_master) {
    $conn_master = @mysqli_connect("127.0.0.1", "root", "", "my_store");
    $conn_slave = $conn_master;
    $conn = $conn_master;
} else {
    mysqli_set_charset($conn_master, "utf8mb4");
    $conn_slave = @mysqli_connect($slave_host, $slave_user, $slave_pass, $slave_dbname, $slave_port);
    if (!$conn_slave) {
        $conn_slave = $conn_master;
    } else {
        mysqli_set_charset($conn_slave, "utf8mb4");
    }
    $conn = $conn_master;
}

function getWriteConnection()
{
    global $conn_master;
    return $conn_master;
}

function getReadConnection()
{
    global $conn_slave;
    return $conn_slave;
}

function executeQuery($query, $use_master = null)
{
    global $conn_master, $conn_slave;
    if ($use_master === null) {
        $query_upper = strtoupper(trim($query));
        $use_master = (strpos($query_upper, 'INSERT') === 0 || strpos($query_upper, 'UPDATE') === 0 || strpos($query_upper, 'DELETE') === 0);
    }
    $conn = $use_master ? $conn_master : $conn_slave;
    return mysqli_query($conn, $query);
}
?>