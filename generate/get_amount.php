<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$type = $_GET['type'] ?? '';
$response = ['amount' => '', 'amount_type' => ''];

if ($type) {
    $stmt = $conn->prepare("SELECT amount, amount_type FROM vehicle_types WHERE name = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $response = $row;
    }
}

echo json_encode($response);
?>
