<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$type_result = $conn->query("SELECT DISTINCT vehicletype FROM tblgenerate");

$search = $type_filter = $month_filter = $year_filter = "";
$where = [];

if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where[] = "(g.platenumber LIKE '%$search%' OR g.fullname LIKE '%$search%')";
}
if (isset($_GET['type']) && $_GET['type'] !== "all") {
    $type_filter = $conn->real_escape_string($_GET['type']);
    $where[] = "g.vehicletype = '$type_filter'";
}
if (!empty($_GET['month'])) {
    $month_filter = $conn->real_escape_string($_GET['month']);
    $where[] = "MONTH(g.due_date) = '$month_filter'";
}
if (!empty($_GET['year'])) {
    $year_filter = $conn->real_escape_string($_GET['year']);
    $where[] = "YEAR(g.due_date) = '$year_filter'";
}
$where_clause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM tblgenerate g $where_clause ORDER BY g.id DESC";
$result = $conn->query($sql);
$years = range(date("Y"), date("Y") - 10);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generate Report</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f9ff; padding: 30px; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #007bff; margin-bottom: 25px; }
        form { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-bottom: 20px; }
        input[type="text"], select { padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; }
        input[type="submit"] { padding: 10px 20px; background: #007bff; border: none; color: white; border-radius: 8px; font-weight: bold; cursor: pointer; }
        .export-buttons { text-align: right; margin-bottom: 15px; }
        .export-buttons button { padding: 8px 14px; margin-left: 8px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .pdf-btn { background: #28a745; color: white; }
        .excel-btn { background: #ffc107; color: black; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #007bff; color: white; padding: 12px; }
        td { padding: 10px; text-align: center; border-bottom: 1px solid #eee; }
        .no-data { text-align: center; color: #999; padding: 20px; }
        .action-links a { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: bold; margin: 0 3px; }
        .edit { background: #ffc107; color: black; }
        .delete { background: #dc3545; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h2>📊 Vehicle Payment Report</h2>

    <div class="export-buttons">
        <button class="pdf-btn" onclick="downloadPDF()">Download PDF</button>
        <button class="excel-btn" onclick="downloadExcel()">Export Excel</button>
    </div>

    <form method="GET">
        <input type="text" name="search" placeholder="Search name or plate..." value="<?= htmlspecialchars($search) ?>">
        <select name="type">
            <option value="all">-- All Types --</option>
            <?php while ($row = $type_result->fetch_assoc()): ?>
                <option value="<?= $row['vehicletype'] ?>" <?= $type_filter == $row['vehicletype'] ? 'selected' : '' ?>><?= $row['vehicletype'] ?></option>
            <?php endwhile; ?>
        </select>
        <select name="month">
            <option value="">-- Month --</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $month_filter == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,10)) ?></option>
            <?php endfor; ?>
        </select>
        <select name="year">
            <option value="">-- Year --</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $year_filter == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <input type="submit" value="Filter">
    </form>

    <table id="reportTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Plate</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Duration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $counter = 1;
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['platenumber']) ?></td>
                    <td><?= htmlspecialchars($row['vehicletype']) ?></td>
                    <td>$<?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['due_date']) ?></td>
                    <td><?= htmlspecialchars($row['amount_type']) ?></td>
                    <td class="action-links">
                        <a href="edit.php?id=<?= $row['id'] ?>" class="edit">Edit</a>
                        <a href="delete.php?id=<?= $row['id'] ?>" class="delete" onclick="return confirm('Delete this?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8" class="no-data">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Vehicle Payment Report", 14, 15);
    const headers = [["#", "Name", "Plate", "Type", "Amount", "Due Date", "Duration"]];
    const data = [];
    document.querySelectorAll("#reportTable tbody tr").forEach(row => {
        const cells = row.querySelectorAll("td");
        if (cells.length >= 7) {
            data.push([
                cells[0].innerText, cells[1].innerText, cells[2].innerText,
                cells[3].innerText, cells[4].innerText, cells[5].innerText,
                cells[6].innerText
            ]);
        }
    });
    doc.autoTable({ head: headers, body: data, startY: 20 });
    doc.save("payment_report.pdf");
}

function downloadExcel() {
    const table = document.getElementById("reportTable").cloneNode(true);
    for (let row of table.rows) row.deleteCell(-1); // remove Actions column
    const wb = XLSX.utils.table_to_book(table, { sheet: "Report" });
    XLSX.writeFile(wb, "payment_report.xlsx");
}
</script>

</body>
</html>

<?php $conn->close(); ?>
