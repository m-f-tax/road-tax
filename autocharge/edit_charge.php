<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

// Get existing data
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM tbl_auto_charges WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $interval = $_POST['interval'];
    $amount = $_POST['amount'];
    $vehicle_type = $_POST['vehicle_type'];
    $due_date = $_POST['due_date'];
    $source = $_POST['source'];

    $update = $conn->prepare("UPDATE tbl_auto_charges SET interval_months=?, amount=?, vehicle_type=?, due_date=?, source=? WHERE id=?");
    $update->bind_param("idsssi", $interval, $amount, $vehicle_type, $due_date, $source, $id);

    if ($update->execute()) {
        header("Location: auto_charge_report.php");
        exit;
    } else {
        echo "Error updating record.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Auto Charge</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4faff;
            padding: 40px;
        }
        form {
            background: #fff;
            padding: 25px;
            max-width: 500px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        input, select {
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            width: 100%;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Edit Auto Charge</h2>

<form method="POST">
    <label>Xilliga (Bilood)</label>
    <select name="interval" required>
        <option value="3" <?= $data['interval_months'] == 3 ? 'selected' : '' ?>>3 Bilood</option>
        <option value="6" <?= $data['interval_months'] == 6 ? 'selected' : '' ?>>6 Bilood</option>
        <option value="12" <?= $data['interval_months'] == 12 ? 'selected' : '' ?>>12 Bilood</option>
    </select>

    <label>Lacagta ($)</label>
    <input type="number" step="0.01" name="amount" value="<?= $data['amount'] ?>" required>

    <label>Nooca Gawadhida</label>
    <input type="text" name="vehicle_type" value="<?= $data['vehicle_type'] ?>" required>

    <label>Due Date</label>
    <input type="text" name="due_date" value="<?= $data['due_date'] ?>" required>

    <label>Isha Lacagta</label>
    <input type="text" name="source" value="<?= $data['source'] ?>" required>

    <button type="submit">Update</button>
</form>

</body>
</html>
