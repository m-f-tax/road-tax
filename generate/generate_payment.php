<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// SAVE ONE BY ONE CHARGE
$response = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['charge'])) {
    $plate = $_POST['plate'];
    $owner = $_POST['owner'];
    $type = $_POST['type'];
    $amount = $_POST['amount'];
    $now = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO tblgenerate (vehicletype, platenumber, fullname, amount, due_date, status) VALUES (?, ?, ?, ?, ?, 'completed')");
    $stmt->bind_param("sssds", $type, $plate, $owner, $amount, $now);
    if ($stmt->execute()) $response = "✅ Vehicle $plate charged successfully.";
    else $response = "❌ Error: " . $stmt->error;
}

// BULK GENERATE PAYMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_payment'])) {
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
        $response .= " ✅ $inserted vehicle(s) charged in bulk.";
    }
}

// HANDLE AJAX SEARCH
if (isset($_GET['ajax']) && $_GET['ajax'] == "1") {
    $plate = $_GET['plate'] ?? '';
    $phone = $_GET['phone'] ?? '';
    $data = [];

    if ($phone && !$plate) {
        $stmt = $conn->prepare("SELECT platenumber FROM vehiclemanagement WHERE phone = ? LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->bind_result($found_plate);
        $stmt->fetch();
        $stmt->close();
        $plate = $found_plate ?? '';
    }

    if ($plate) {
        $stmt = $conn->prepare("SELECT owner, carname, phone FROM vehiclemanagement WHERE platenumber = ? LIMIT 1");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($owner, $type, $phone);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT SUM(amount) FROM tblgenerate WHERE platenumber = ?");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($generated);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT SUM(amount) FROM tbl_reciept WHERE plate_number = ?");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($paid);
        $stmt->fetch();
        $stmt->close();

        $data = [
            'plate' => $plate,
            'owner' => $owner,
            'type' => $type,
            'phone' => $phone,
            'amount_due' => number_format(($generated ?? 0) - ($paid ?? 0), 2)
        ];
        echo json_encode($data);
    }
    exit;
}

// REPORT FILTERS
$type_result = $conn->query("SELECT DISTINCT vehicletype FROM tblgenerate");
$types = $conn->query("SELECT name FROM vehicle_types");

$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all';
$month_filter = $_GET['month'] ?? '';
$year_filter = $_GET['year'] ?? '';
$years = range(date("Y"), date("Y") - 10);
$where = [];

if ($search) $where[] = "(g.platenumber LIKE '%$search%' OR g.fullname LIKE '%$search%')";
if ($type_filter !== "all") $where[] = "g.vehicletype = '$type_filter'";
if ($month_filter) $where[] = "MONTH(g.due_date) = '$month_filter'";
if ($year_filter) $where[] = "YEAR(g.due_date) = '$year_filter'";
$where_clause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM tblgenerate g $where_clause ORDER BY g.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vehicle Payment Report</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background: #f1f9ff; font-family: 'Segoe UI', sans-serif; padding: 30px; }
        .container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h3 class="text-primary">📊 Vehicle Payment Report</h3>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#generateOneByOne">➕ Generate One by One</button>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">Bulk Generate</button>
        </div>
    </div>

    <?php if ($response): ?>
        <div class="alert alert-info"><?= $response ?></div>
    <?php endif; ?>

    <!-- FILTER FORM -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"></div>
        <div class="col-md-2">
            <select name="type" class="form-control">
                <option value="all">All Types</option>
                <?php while ($row = $type_result->fetch_assoc()): ?>
                    <option value="<?= $row['vehicletype'] ?>" <?= $type_filter == $row['vehicletype'] ? 'selected' : '' ?>><?= $row['vehicletype'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="month" class="form-control">
                <option value="">Month</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month_filter == $m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,10)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="year" class="form-control">
                <option value="">Year</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $year_filter == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>

    <!-- REPORT TABLE -->
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Owner</th>
                <th>Plate</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Duration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= $row['fullname'] ?></td>
                <td><?= $row['platenumber'] ?></td>
                <td><?= $row['vehicletype'] ?></td>
                <td>$<?= number_format($row['amount'], 2) ?></td>
                <td><?= $row['due_date'] ?></td>
                <td><?= $row['amount_type'] ?></td>
                <td>
                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete?')" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8" class="text-center text-muted">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- BULK GENERATE MODAL -->
<div class="modal fade" id="bulkGenerateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="register_payment" value="1">
        <div class="modal-header"><h5 class="modal-title">Bulk Generate Payment</h5></div>
        <div class="modal-body">
            <label>Vehicle Type</label>
            <select name="vehicletype" class="form-control mb-2" required>
                <option value="all">All Vehicles</option>
                <?php $types->data_seek(0); while ($row = $types->fetch_assoc()): ?>
                    <option value="<?= $row['name'] ?>"><?= $row['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <label>Duration</label>
            <select name="duration" class="form-control mb-2" required>
                <option value="3">3 bilood</option>
                <option value="6">6 bilood</option>
                <option value="9">9 bilood</option>
                <option value="12">12 bilood</option>
            </select>
            <label>Due Date</label>
            <input type="datetime-local" name="due_date" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success" type="submit">Generate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ONE BY ONE MODAL -->
<div class="modal fade" id="generateOneByOne" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Generate One by One</h5></div>
        <div class="modal-body">
            <input type="text" id="plate" class="form-control mb-2" placeholder="Plate Number">
            <input type="text" id="phone" class="form-control mb-2" placeholder="Phone">
            <button type="button" id="searchBtn" class="btn btn-primary mb-3">Search</button>

            <div id="vehicleInfo" style="display:none;">
                <input type="hidden" name="plate" id="formPlate">
                <input type="hidden" name="owner" id="formOwner">
                <input type="hidden" name="type" id="formType">
                <p><strong>Plate:</strong> <span id="showPlate"></span></p>
                <p><strong>Owner:</strong> <span id="showOwner"></span></p>
                <p><strong>Type:</strong> <span id="showType"></span></p>
                <p><strong>Phone:</strong> <span id="showPhone"></span></p>
                <p><strong>Due:</strong> $<span id="showDue"></span></p>
                <input type="number" name="amount" step="0.01" class="form-control mb-2" placeholder="Amount to charge" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="charge" class="btn btn-success">Charge</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$('#searchBtn').on('click', function() {
    let plate = $('#plate').val();
    let phone = $('#phone').val();

    $.ajax({
        url: '?ajax=1',
        method: 'GET',
        data: { plate: plate, phone: phone },
        success: function(res) {
            let data = JSON.parse(res);
            if (data.plate) {
                $('#vehicleInfo').show();
                $('#showPlate').text(data.plate);
                $('#showOwner').text(data.owner);
                $('#showType').text(data.type);
                $('#showPhone').text(data.phone);
                $('#showDue').text(data.amount_due);

                $('#formPlate').val(data.plate);
                $('#formOwner').val(data.owner);
                $('#formType').val(data.type);
            } else {
                $('#vehicleInfo').hide();
                alert("No matching record.");
            }
        }
    });
});
</script>

</body>
</html>
<?php $conn->close(); ?>
