<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$success = $error = "";
$vehicle_type = $amount = $owner = "";
$plate = "";

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

// Auto-fill data if plate or phone selected
if (isset($_GET['plate_number']) || isset($_GET['phone'])) {
    $plate = $_GET['plate_number'] ?? '';
    $phone = $_GET['phone'] ?? '';

    // Try to get plate from phone
    if ($phone && !$plate) {
        $stmt0 = $conn->prepare("SELECT platenumber FROM vehiclemanagement WHERE phone = ? LIMIT 1");
        $stmt0->bind_param("s", $phone);
        $stmt0->execute();
        $stmt0->bind_result($fetched_plate);
        $stmt0->fetch();
        $stmt0->close();
        $plate = $fetched_plate ?? '';
    }

    if ($plate) {
        $stmt1 = $conn->prepare("SELECT SUM(amount) FROM tblgenerate WHERE platenumber = ?");
        $stmt1->bind_param("s", $plate);
        $stmt1->execute();
        $stmt1->bind_result($generated_amount);
        $stmt1->fetch();
        $stmt1->close();

        $stmt2 = $conn->prepare("SELECT SUM(amount) FROM tbl_reciept WHERE plate_number = ?");
        $stmt2->bind_param("s", $plate);
        $stmt2->execute();
        $stmt2->bind_result($paid_amount);
        $stmt2->fetch();
        $stmt2->close();

        $generated_amount = $generated_amount ?? 0;
        $paid_amount = $paid_amount ?? 0;
        $amount = $generated_amount - $paid_amount;

        $stmt3 = $conn->prepare("SELECT vehicletype FROM tblgenerate WHERE platenumber = ? ORDER BY id DESC LIMIT 1");
        $stmt3->bind_param("s", $plate);
        $stmt3->execute();
        $stmt3->bind_result($vehicle_type);
        $stmt3->fetch();
        $stmt3->close();

        $stmt4 = $conn->prepare("SELECT owner FROM vehiclemanagement WHERE platenumber = ? LIMIT 1");
        $stmt4->bind_param("s", $plate);
        $stmt4->execute();
        $stmt4->bind_result($owner);
        $stmt4->fetch();
        $stmt4->close();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_receipt'])) {
    $vehicle_type = $_POST['vehicle_type'];
    $plate_number = $_POST['plate_number'];
    $owner = $_POST['owner'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];

    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_name = basename($_FILES["receipt_image"]["name"]);
    $target_file = $target_dir . $file_name;
    $upload_ok = move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file);

    if ($upload_ok) {
        $stmt = $conn->prepare("INSERT INTO tbl_reciept (vehicle_type, plate_number, owner, amount, due_date, receipt_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddss", $vehicle_type, $plate_number, $owner, $amount, $due_date, $file_name, $status);

        if ($stmt->execute()) {
            $success = "✅ Receipt recorded successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $error = "❌ Failed to upload image.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt Payment</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4faff;
            padding: 40px;
        }
        .back-link {
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            font-weight: bold;
            color: #007bff;
        }
        .wrapper {
            display: flex;
            justify-content: center;
            gap: 50px;
            flex-wrap: wrap;
        }
        .box {
            background: white;
            padding: 35px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.07);
            width: 380px;
        }
        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 25px;
        }
        label {
            font-weight: 600;
            margin-top: 10px;
            display: block;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button, input[type="submit"] {
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s ease-in-out;
        }
        button:hover, input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>


<div class="wrapper">
    <div class="box">
        <h2>🔍 Search by Plate or Phone</h2>
        <form method="GET">
            <label for="plate_number">Plate Number (optional):</label>
            <input type="text" name="plate_number">

            <label for="phone">Phone Number (optional):</label>
            <input type="text" name="phone">

            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (!empty($plate)): ?>
        <?php if (($generated_amount ?? 0) <= 0): ?>
            <div class="box">
                <div class="message error">
                    ❌ Gaadhigan lacag hore looma dalacin.
                </div>
            </div>
        <?php else: ?>
            <div class="box">
                <h2>🧾 Record Receipt Payment</h2>

                <?php if ($success) echo "<div class='message success'>$success</div>"; ?>
                <?php if ($error) echo "<div class='message error'>$error</div>"; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="plate_number" value="<?= htmlspecialchars($plate); ?>">

                    <label for="vehicle_type">Vehicle Type:</label>
                    <input type="text" name="vehicle_type" value="<?= htmlspecialchars($vehicle_type); ?>" readonly>

                    <label for="owner">Owner:</label>
                    <input type="text" name="owner" value="<?= htmlspecialchars($owner); ?>" readonly>

                    <label for="amount">Amount (USD):</label>
                    <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($amount); ?>" readonly>

                    <label for="due_date">Due Date:</label>
                    <input type="text" name="due_date" id="due_date" readonly>

                    <label for="status">Status:</label>
                    <select name="status" required>
                        <option value="On Time">On Time</option>
                        <option value="Overdue">Overdue</option>
                    </select>

                    <label for="receipt_image">Upload Receipt Image:</label>
                    <input type="file" name="receipt_image" accept="image/*" required>

                    <input type="submit" name="submit_receipt" value="Submit Receipt">
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const now = new Date();
    const formatted = now.getFullYear() + "-" +
        String(now.getMonth() + 1).padStart(2, '0') + "-" +
        String(now.getDate()).padStart(2, '0') + " " +
        String(now.getHours()).padStart(2, '0') + ":" +
        String(now.getMinutes()).padStart(2, '0') + ":" +
        String(now.getSeconds()).padStart(2, '0');
    const due = document.getElementById('due_date');
    if (due) due.value = formatted;
});
</script>

</body>
</html>
