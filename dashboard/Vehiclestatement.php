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

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";

$vehicle = null;
$payments = [];
$charges = [];
$total_paid = 0;
$total_charged = 0;
$balance = 0;

if (!empty($search)) {
    $vehicle_sql = "SELECT * FROM vehiclemanagement WHERE platenumber LIKE '%$search%' OR owner LIKE '%$search%' LIMIT 1";
    $vehicle_result = $conn->query($vehicle_sql);

    if ($vehicle_result && $vehicle_result->num_rows > 0) {
        $vehicle = $vehicle_result->fetch_assoc();
        $plate = $vehicle['platenumber'];

        $charged_sql = "SELECT SUM(amount) AS total_charged FROM tblgenerate WHERE platenumber = '$plate'";
        $charged_result = $conn->query($charged_sql);
        if ($charged_result) {
            $row = $charged_result->fetch_assoc();
            $total_charged = $row['total_charged'] ?? 0;
        }

        $charge_sql = "SELECT amount, due_date FROM tblgenerate WHERE platenumber = '$plate' ORDER BY due_date DESC";
        $charge_result = $conn->query($charge_sql);
        if ($charge_result) {
            while ($row = $charge_result->fetch_assoc()) {
                $charges[] = $row;
            }
        }

        $payment_sql = "SELECT amount, due_date FROM tbl_reciept WHERE plate_number = '$plate' ORDER BY due_date DESC";
        $payment_result = $conn->query($payment_sql);
        if ($payment_result) {
            while ($row = $payment_result->fetch_assoc()) {
                $total_paid += $row['amount'];
                $payments[] = $row;
            }
        }

        $balance = $total_charged - $total_paid;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vehicle Statement</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(145deg, #f0f9ff, #ffffff);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 980px;
            margin: auto;
            background: #fff;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .back-link {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        .back-link:hover {
            background: #0056b3;
        }
        h2, h3 {
            color: #007bff;
            margin-top: 0;
        }
        form {
            margin-bottom: 30px;
        }
        input[type="text"] {
            padding: 12px;
            width: 360px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        input[type="submit"] {
            padding: 12px 22px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            margin-left: 10px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #0056b3;
        }
        .info p {
            font-size: 16px;
            margin: 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover td {
            background: #f9f9f9;
        }
        .highlight {
            color: #007bff;
            font-weight: bold;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f3f9ff;
            border: 1px solid #cce5ff;
            border-radius: 8px;
        }
        .summary p {
            font-size: 18px;
            margin: 10px 0;
        }
        .charges, .payments {
            margin-top: 30px;
        }
        .no-result {
            margin-top: 20px;
            font-size: 16px;
            color: red;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Vehicle Statement</h2>
        <form method="get">
            <input type="text" name="search" placeholder="Enter Plate Number or Owner Name" value="<?= htmlspecialchars($search) ?>">
            <input type="submit" value="Search">
        </form>

        <?php if ($vehicle): ?>
            <div class="info">
                <p><strong>Owner:</strong> <?= htmlspecialchars($vehicle['owner']) ?></p>
                <p><strong>Plate Number:</strong> <?= htmlspecialchars($vehicle['platenumber']) ?></p>
                <p><strong>Vehicle Type:</strong> <?= htmlspecialchars($vehicle['carname']) ?></p>
            </div>

            <div class="charges">
                <h3>Charge History</h3>
                <table>
                    <tr>
                        <th>Month</th>
                        <th>Amount Charged</th>
                        <th>Due Date</th>
                    </tr>
                    <?php foreach ($charges as $c): ?>
                        <tr>
                            <td><?= date("F Y", strtotime($c['due_date'])) ?></td>
                            <td>$<?= number_format($c['amount'], 2) ?></td>
                            <td><?= date("Y-m-d h:i A", strtotime($c['due_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="payments">
                <h3>Payment History</h3>
                <table>
                    <tr>
                        <th>Month Paid</th>
                        <th>Amount Paid</th>
                        <th>Paid On (Time & Date)</th>
                    </tr>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= date("F Y", strtotime($p['due_date'])) ?></td>
                            <td>$<?= number_format($p['amount'], 2) ?></td>
                            <td><?= date("h:i A, Y-m-d", strtotime($p['due_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="summary">
                <p>Total Charged: <span class="highlight">$<?= number_format($total_charged, 2) ?></span></p>
                <p>Total Paid: <span class="highlight">$<?= number_format($total_paid, 2) ?></span></p>
                <p>Remaining Balance: <span class="highlight">$<?= number_format($balance, 2) ?></span></p>
            </div>
        <?php elseif (!empty($search)): ?>
            <p class="no-result">No vehicle data found for your search.</p>
        <?php endif; ?>
    </div>
</body>
</html>
