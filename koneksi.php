<?php
$host     = "127.0.0.1";
$port     = "3307"; // Port khusus MariaDB/MySQL XAMPP kamu
$username = "root";
$password = "";
$dbname   = "db_akademik";

try {
    $koneksi = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>