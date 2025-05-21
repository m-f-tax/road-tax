<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: vehicle_type_page.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id']);
$conn->query("DELETE FROM vehicle_types WHERE id=$id");
$conn->close();

header("Location: vehicle_type_page.php");
exit;
