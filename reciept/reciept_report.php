<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'All';
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? '';
$years = range(date("Y"), date("Y") - 10);

// Fetch all vehicle types
$types_result = $conn->query("SELECT DISTINCT vehicle_type FROM tbl_reciept");
$vehicle_types = [];
while ($row = $types_result->fetch_assoc()) {
    $vehicle_types[] = $row['vehicle_type'];
}

// Build WHERE clause
$where = "WHERE (r.plate_number LIKE ? OR v.phone LIKE ? OR r.vehicle_type LIKE ?)";
$params = ["sss", "%$search%", "%$search%", "%$search%"];

if ($type_filter !== 'All') {
    $where .= " AND r.vehicle_type = ?";
    $params[0] .= "s";
    $params[] = $type_filter;
}
if (!empty($month_filter)) {
    $where .= " AND MONTH(r.due_date) = ?";
    $params[0] .= "i";
    $params[] = (int)$month_filter;
}
if (!empty($year_filter)) {
    $where .= " AND YEAR(r.due_date) = ?";
    $params[0] .= "i";
    $params[] = (int)$year_filter;
}

$sql = "SELECT r.*, v.phone FROM tbl_reciept r 
        LEFT JOIN vehiclemanagement v ON r.plate_number = v.platenumber 
        $where ORDER BY r.due_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt Payment Report</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f9ff;
            padding: 40px;
            text-align: center;
        }
        h2 {
            color: #007bff;
            font-size: 30px;
            margin-bottom: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            max-width: 1100px;
            margin: auto;
            margin-bottom: 20px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .search-box form {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        input[type="text"], select {
            padding: 10px;
            width: 180px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            max-width: 1100px;
            margin: auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2>🧾 Receipt Payment Report</h2>
    <div>
        <button class="btn" onclick="downloadPDF()">PDF</button>
        <button class="btn" onclick="downloadExcel()">Excel</button>
        <a href="reciept_payment.php" class="btn">➕ Add Payment</a>
    </div>
</div>

<div class="search-box">
    <form method="GET">
        <input type="text" name="search" placeholder="Search plate or phone..." value="<?= htmlspecialchars($search) ?>">
        <select name="type">
            <option value="All">All Types</option>
            <?php foreach ($vehicle_types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>" <?= ($type === $type_filter) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="month">
            <option value="">Month</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= ($m == $month_filter) ? 'selected' : '' ?>>
                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                </option>
            <?php endfor; ?>
        </select>
        <select name="year">
            <option value="">Year</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= ($y == $year_filter) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <input class="btn" type="submit" value="Filter">
    </form>
</div>

<table id="receiptTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Plate</th>
            <th>Phone</th>
            <th>Owner</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Due Date</th>
        </tr>
    </thead>
    <tbody>
        <?php $counter = 1; ?>
        <?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($row['plate_number']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['owner']) ?></td>
                <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                <td>$<?= number_format($row['amount'], 2) ?></td>
                <td><?= date('d M Y - H:i', strtotime($row['due_date'])) ?></td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="7">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="footer">
    © 2025 All Rights Reserved – MOF.SSC-KHAATUMO
</div>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Receipt Payment Report", 14, 15);
    const headers = [["#", "Plate", "Phone", "Owner", "Type", "Amount", "Due Date"]];
    const data = [];
    document.querySelectorAll("#receiptTable tbody tr").forEach(row => {
        const cells = row.querySelectorAll("td");
        if (cells.length === 7) {
            data.push([
                cells[0].innerText,
                cells[1].innerText,
                cells[2].innerText,
                cells[3].innerText,
                cells[4].innerText,
                cells[5].innerText,
                cells[6].innerText
            ]);
        }
    });
    doc.autoTable({ head: headers, body: data, startY: 20 });
    doc.save("receipt_report.pdf");
}

function downloadExcel() {
    const table = document.getElementById("receiptTable").cloneNode(true);
    const wb = XLSX.utils.table_to_book(table, { sheet: "Receipts" });
    XLSX.writeFile(wb, "receipt_report.xlsx");
}
</script>

</body>
</html>

<?php $conn->close(); ?>
