<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM tbl_auto_charges WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: auto_charge_report");
exit;
?>
