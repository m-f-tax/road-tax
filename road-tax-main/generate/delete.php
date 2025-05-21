<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'] ?? '';
if ($id) {
    $stmt = $conn->prepare("DELETE FROM tblgenerate WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: generate_report.php");
exit;
?>
