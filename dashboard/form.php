<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

$success = $error = "";
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $platenumber   = $_POST['platenumber'];
    $vehicletype   = $_POST['carname'];
    $owner         = $_POST['owner'];
    $model         = $_POST['model'];
    $phone         = $_POST['phone'];
    $registration  = $_POST['registration'];
    $user_id       = $_SESSION['user_id'];

    if (!preg_match("/^[a-zA-Z\s]+$/", $owner)) {
        $error = "❌ Owner name must contain only letters.";
    } elseif (!preg_match("/^[0-9]+$/", $phone)) {
        $error = "❌ Phone number must contain only digits.";
    } else {
        $stmt = $conn->prepare("INSERT INTO vehiclemanagement (platenumber, vehicletype, carname, owner, model, phone, registration_date, user_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $platenumber, $vehicletype, $vehicletype, $owner, $model, $phone, $registration, $user_id);

        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("SELECT amount FROM vehicle_types WHERE name = ? LIMIT 1");
            $stmt2->bind_param("s", $vehicletype);
            $stmt2->execute();
            $stmt2->bind_result($three_month_amount);
            $stmt2->fetch();
            $stmt2->close();

            if (!empty($three_month_amount)) {
                $amount_type = '3bilood';
                $due_date = date("Y-m-d", strtotime($registration . " +3 months"));

                $stmt3 = $conn->prepare("INSERT INTO tblgenerate (fullname, platenumber, vehicletype, amount, amount_type, due_date) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
                $stmt3->bind_param("sssdds", $owner, $platenumber, $vehicletype, $three_month_amount, $amount_type, $due_date);
                if ($stmt3->execute()) {
                    $success = "✅ Vehicle registered and charged 3-bilood successfully!";
                } else {
                    $error = "❌ Failed to charge vehicle.";
                }
                $stmt3->close();
            } else {
                $error = "❌ Vehicle type not found.";
            }
        } else {
            $error = "❌ Error saving vehicle: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Vehicle</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f9ff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .form-container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #007bff;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        input.error-border {
            border: 2px solid red;
        }
        input[type="submit"] {
            margin-top: 20px;
            background: #007bff;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
        .message {
            text-align: center;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 6px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Register Vehicle</h2>

    <?php if (!empty($success)) echo "<div class='message success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='message error'>$error</div>"; ?>

    <form method="POST" onsubmit="return validateInputs();">
        <label for="platenumber">Plate Number</label>
        <input type="text" id="platenumber" name="platenumber" required>

        <label for="carname">Vehicle Type</label>
        <select id="carname" name="carname" required>
            <option value="">-- Select Type --</option>
            <?php
            $conn = new mysqli("localhost", "root", "", "roadtaxsystem");
            $types = $conn->query("SELECT name FROM vehicle_types");
            while ($row = $types->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
            }
            $conn->close();
            ?>
        </select>

        <label for="owner">Owner Name</label>
        <input type="text" id="owner" name="owner" required>

        <label for="model">Vehicle Model</label>
        <input type="text" id="model" name="model" required>

        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" required>

        <label for="registration">Registration Date</label>
        <input type="date" id="registration" name="registration" required>

        <input type="submit" value="Save">
    </form>
</div>

<script>
function validateInputs() {
    const owner = document.getElementById('owner');
    const phone = document.getElementById('phone');

    const ownerValid = /^[A-Za-z\s]+$/.test(owner.value);
    const phoneValid = /^[0-9]+$/.test(phone.value);

    owner.classList.toggle('error-border', !ownerValid);
    phone.classList.toggle('error-border', !phoneValid);

    return ownerValid && phoneValid;
}
</script>

</body>
</html>
