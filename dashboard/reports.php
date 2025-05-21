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

// Get unique car names for dropdown
$carname_result = $conn->query("SELECT DISTINCT carname FROM vehiclemanagement ORDER BY carname");

// Filters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";
$car_filter = isset($_GET['carname']) ? $conn->real_escape_string($_GET['carname']) : "";
$month_filter = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : "";

$where = [];
if (!empty($search)) {
    $where[] = "(platenumber LIKE '%$search%' OR phone LIKE '%$search%')";
}
if (!empty($car_filter)) {
    $where[] = "carname = '$car_filter'";
}
if (!empty($month_filter)) {
    $where[] = "MONTH(registration_date) = '$month_filter'";
}

$where_clause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM vehiclemanagement $where_clause ORDER BY id DESC";
$result = $conn->query($sql);
$total = $result->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vehicle Reports</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background-color: #eef3f9;
        }
        .main {
            max-width: 1100px;
            margin: auto;
            padding: 40px 20px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 10px;
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        h2 {
            text-align: center;
            font-size: 32px;
            color: #007bff;
            margin-bottom: 20px;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .filters input, .filters select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 16px;
            border-radius: 20px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        .add-btn { background-color: #007bff; }
        .pdf-btn { background-color: #28a745; }
        .excel-btn { background-color: #ffc107; color: black; }

        .summary-box {
            background: #dff0d8;
            padding: 12px 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            color: #3c763d;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        th {
            background-color: #007bff;
            color: white;
            padding: 14px;
        }
        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #f1f1f1;
        }
        .actions-cell {
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .edit-btn, .delete-btn {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
        }
        .edit-btn { background-color: #ffc107; }
        .delete-btn { background-color: #dc3545; }
    </style>
</head>
<body>
<div class="main">
    <h2>🚗 Vehicle Reports</h2>

    <form class="filters" method="GET">
        <input type="text" name="search" placeholder="Search by Plate or Phone" value="<?= htmlspecialchars($search) ?>">
        <select name="carname">
            <option value="">-- Filter by Car Name --</option>
            <?php while ($row = $carname_result->fetch_assoc()): ?>
                <option value="<?= $row['carname'] ?>" <?= $car_filter == $row['carname'] ? 'selected' : '' ?>>
                    <?= $row['carname'] ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="month">
            <option value="">-- Filter by Month --</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $month_filter == $m ? 'selected' : '' ?>>
                    <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                </option>
            <?php endfor; ?>
        </select>
        <input type="submit" value="Filter">
    </form>

    <div class="summary-box">
        Total Vehicles Found: <?= $total ?>
    </div>

    <div class="action-buttons">
        <div>
            <button class="btn pdf-btn" onclick="downloadPDF()">🧾 PDF</button>
            <button class="btn excel-btn" onclick="downloadExcel()">📊 Excel</button>
        </div>
        <a class="btn add-btn" href="form.php">➕ Add New Vehicle</a>
    </div>

   <table id="vehicleTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Plate Number</th>
            <th>Owner</th>
            <th>Phone</th>
            <th>Car Name</th>
            <th>Registration Date</th>
            <th>Registered By</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $counter = 1; while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= $counter++ ?></td>
                <td><?= htmlspecialchars($row['platenumber']) ?></td>
                <td><?= htmlspecialchars($row['owner']) ?></td>
                <td>'<?= htmlspecialchars($row['phone']) ?>'</td>
                <td><?= htmlspecialchars($row['carname']) ?></td>
                <td><?= htmlspecialchars($row['registration_date']) ?></td>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td class="actions-cell">
                    <a href="edit_vehicle.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                    <a href="delete_vehicle.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this vehicle?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Vehicle Report", 14, 15);
    const headers = [["Plate", "Owner", "Phone", "Car Name", "Date"]];
    const data = [];

    document.querySelectorAll("#vehicleTable tbody tr").forEach(row => {
        const cells = row.querySelectorAll("td");
        if (cells.length >= 5) {
            data.push([
                cells[0].innerText,
                cells[1].innerText,
                cells[2].innerText,
                cells[3].innerText,
                cells[4].innerText
            ]);
        }
    });

    doc.autoTable({ head: headers, body: data, startY: 20 });
    doc.save("vehicle_report.pdf");
}

function downloadExcel() {
    const table = document.getElementById("vehicleTable").cloneNode(true);
    for (let row of table.rows) row.deleteCell(-1);
    const wb = XLSX.utils.table_to_book(table, { sheet: "Vehicle Report" });
    XLSX.writeFile(wb, "vehicle_report.xlsx");
}
</script>
</body>
</html>

<?php $conn->close(); ?>
