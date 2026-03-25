<?php
$mysqli = new mysqli('127.0.0.1', 'root', 'root', 'legacygest', 3306);
if ($mysqli->connect_error) {
    die("Connect error: " . $mysqli->connect_error);
}
$res = $mysqli->query("SELECT * FROM ps_bookingbridge_vendor_data");
if ($res) {
    echo "Found " . $res->num_rows . " rows.\n";
    while ($row = $res->fetch_assoc()) {
        echo "Product ID: " . $row['id_product'] . "\n";
    }
} else {
    echo "Query error: " . $mysqli->error;
}
$mysqli->close();
