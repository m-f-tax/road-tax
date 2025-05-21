
<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM tbl_reciept WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: reciept_report.php?deleted=1");
exit;
?>
