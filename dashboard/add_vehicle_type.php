<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login");
    exit;
}

$success = $error = "";
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_type'])) {
    $name = trim($_POST['name']);
    $amount = trim($_POST['amount']);
    $amount_type = trim($_POST['amount_type']);

    if (!empty($name) && is_numeric($amount) && !empty($amount_type)) {
        $stmt = $conn->prepare("INSERT INTO vehicle_types (name, amount, amount_type) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $name, $amount, $amount_type);
        if ($stmt->execute()) {
            $success = "✅ Vehicle type added successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "❌ Please fill in all fields correctly.";
    }
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
$sql = "SELECT * FROM vehicle_types";
if (!empty($search)) {
    $sql .= " WHERE name LIKE '%$search%' OR amount_type LIKE '%$search%'";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vehicle Type Management</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f9ff;
            margin: 0;
            padding: 30px;
        }
        .back-btn {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
        .form-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #007bff;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        button {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .toggle-btn {
            background-color: #28a745;
            color: white;
            width: auto;
            margin: 20px auto;
            display: block;
        }
        .message {
            text-align: center;
            font-weight: bold;
        }
        .success { color: green; }
        .error { color: red; }

        .report-box {
            display: none;
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin: 30px auto;
            max-width: 1000px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .report-header input {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 250px;
        }
        .btn-pdf, .btn-excel {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-pdf { background: #28a745; color: white; }
        .btn-excel { background: #ffc107; color: black; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #007bff;
            color: white;
            padding: 10px;
        }
        td {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .edit-btn, .delete-btn {
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .edit-btn { background: #ffc107; color: black; }
        .delete-btn { background: #dc3545; color: white; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Add Vehicle Type</h2>
    <?php if ($success) echo "<div class='message success'>$success</div>"; ?>
    <?php if ($error) echo "<div class='message error'>$error</div>"; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Vehicle type (e.g. Bajaaj, Car)" required>
        <input type="number" step="0.01" name="amount" placeholder="Amount (USD)" required>
        <input type="text" name="amount_type" placeholder="Amount type (e.g. 3 bilood)" required>
        <button type="submit" name="submit_type">Save Vehicle Type</button>
    </form>
</div>

<button class="toggle-btn" onclick="toggleReport()">📄 View Vehicle Type Report</button>

<div class="report-box" id="reportBox">
    <div class="report-header">
        <form method="GET">
            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        </form>
        <div>
            <button class="btn-pdf" onclick="downloadPDF()">PDF</button>
            <button class="btn-excel" onclick="downloadExcel()">Excel</button>
        </div>
    </div>
    <table id="vehicleTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Amount</th>
                <th>Amount Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>$<?= number_format($row['amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['amount_type']) ?></td>
                <td>
                    <a href="edit_type.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                    <a href="delete_type.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function toggleReport() {
    const box = document.getElementById("reportBox");
    box.style.display = (box.style.display === "none" || box.style.display === "") ? "block" : "none";
}

function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Vehicle Types Report", 14, 15);
    const headers = [["Name", "Amount", "Amount Type"]];
    const data = [];
    document.querySelectorAll("#vehicleTable tbody tr").forEach(row => {
        const cells = row.querySelectorAll("td");
        data.push([
            cells[0].innerText,
            cells[1].innerText,
            cells[2].innerText
        ]);
    });
    doc.autoTable({ head: headers, body: data, startY: 20 });
    doc.save("vehicle_types.pdf");
}

function downloadExcel() {
    const table = document.getElementById("vehicleTable").cloneNode(true);
    for (let row of table.rows) {
        row.deleteCell(-1); // remove Actions column
    }
    const wb = XLSX.utils.table_to_book(table, { sheet: "Vehicle Types" });
    XLSX.writeFile(wb, "vehicle_types.xlsx");
}
</script>

</body>
</html>

<?php $conn->close(); ?>
