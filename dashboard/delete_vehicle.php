<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $sql = "DELETE FROM vehiclemanagement WHERE id = $id";
    $conn->query($sql);
}

$conn->close();
header("Location: reports");
exit;
