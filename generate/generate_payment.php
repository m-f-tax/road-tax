<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$success = "";
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$types = $conn->query("SELECT name FROM vehicle_types");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicletype = $_POST['vehicletype'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $due_date = $_POST['due_date'] ?? date("Y-m-d H:i:s");

    if ($vehicletype && $duration) {
        $vehicles = $vehicletype === "all"
            ? $conn->query("SELECT * FROM vehiclemanagement")
            : $conn->query("SELECT * FROM vehiclemanagement WHERE vehicletype = '$vehicletype'");

        $inserted = 0;
        while ($v = $vehicles->fetch_assoc()) {
            $plate = $v['platenumber'];
            $owner = $v['owner'];
            $raw_type = $v['vehicletype'];

            $lookup_type = strtolower(trim($raw_type));
            $stmt = $conn->prepare("SELECT amount, amount_type FROM vehicle_types WHERE LOWER(TRIM(name)) = ?");
            $stmt->bind_param("s", $lookup_type);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                $monthly = $row['amount'];
                $type_duration = strtolower(str_replace([' ', '-'], '', trim($row['amount_type'])));
                $expected_duration = strtolower($duration . "bilood");

                if ($type_duration !== $expected_duration) continue;

                $total = ($duration / 3) * $monthly;

                $insert = $conn->prepare("INSERT INTO tblgenerate (fullname, vehicletype, platenumber, amount, amount_type, due_date)
                                          VALUES (?, ?, ?, ?, ?, ?)");
                $insert->bind_param("sssdds", $owner, $raw_type, $plate, $total, $expected_duration, $due_date);
                if ($insert->execute()) $inserted++;
            }
        }

        $success = "✅ $inserted vehicle(s) charged successfully for $duration bilood.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #e8f0fe;
            font-family: 'Segoe UI', sans-serif;
        }
        .form-box {
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            max-width: 550px;
            margin: 50px auto;
        }
        h2 { text-align: center; color: rgb(36, 57, 160); margin-bottom: 25px; font-weight: bold; }
        label { font-weight: 500; margin-top: 15px; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>💳 Generate Payment</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Vehicle Type</label>
        <select name="vehicletype" class="form-control" required>
            <option value="">-- Select --</option>
            <option value="all">All Vehicles</option>
            <?php while ($row = $types->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['name']) ?>"><?= htmlspecialchars($row['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Duration</label>
        <select name="duration" class="form-control" required>
            <option value="3">3 bilood</option>
            <option value="6">6 bilood</option>
            <option value="9">9 bilood</option>
            <option value="12">12 bilood</option>
        </select>

        <label>Due Date</label>
        <input type="datetime-local" name="due_date" class="form-control" required>

        <button type="submit" class="btn btn-primary mt-4 w-100">🚀 Generate</button>
    </form>
</div>

</body>
</html>
