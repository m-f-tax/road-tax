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

$success = $error = "";

// Save form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_vehicle'])) {
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
            $success = "✅ Vehicle registered successfully!";
        } else {
            $error = "❌ Error saving vehicle: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Report data
$carname_result = $conn->query("SELECT DISTINCT carname FROM vehiclemanagement ORDER BY carname");
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

// For dropdown inside modal
$types = $conn->query("SELECT name FROM vehicle_types");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vehicle Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="text-primary text-center mb-4">🚗 Vehicle Reports & Registration</h2>

    <?php if (!empty($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <!-- Button trigger modal -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2" method="GET">
            <input class="form-control" type="text" name="search" placeholder="Search Plate or Phone" value="<?= htmlspecialchars($search) ?>">
            <select class="form-control" name="carname">
                <option value="">-- Filter by Car Name --</option>
                <?php while ($row = $carname_result->fetch_assoc()): ?>
                    <option value="<?= $row['carname'] ?>" <?= $car_filter == $row['carname'] ? 'selected' : '' ?>>
                        <?= $row['carname'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select class="form-control" name="month">
                <option value="">-- Filter by Month --</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month_filter == $m ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                    </option>
                <?php endfor; ?>
            </select>
            <button class="btn btn-primary">Filter</button>
        </form>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerModal"> Register Vehicle</button>
    </div>

    <div class="alert alert-info">Total Vehicles Found: <strong><?= $total ?></strong></div>

    <table class="table table-bordered bg-white">
        <thead class="table-primary">
            <tr>
                <th>#</th>
                <th>Plate</th>
                <th>Owner</th>
                <th>Phone</th>
                <th>Car Name</th>
                <th>Registration</th>
                <th>User ID</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['platenumber']) ?></td>
                    <td><?= htmlspecialchars($row['owner']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['carname']) ?></td>
                    <td><?= htmlspecialchars($row['registration_date']) ?></td>
                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-primary" id="registerModalLabel">Register New Vehicle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="register_vehicle" value="1">
        <div class="mb-2">
            <label>Plate Number</label>
            <input type="text" name="platenumber" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Vehicle Type</label>
            <select name="carname" class="form-control" required>
                <option value="">-- Select Type --</option>
                <?php while ($row = $types->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($row['name']) ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="mb-2">
            <label>Owner</label>
            <input type="text" name="owner" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Model</label>
            <input type="text" name="model" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Registration Date</label>
            <input type="date" name="registration" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">💾 Save Vehicle</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
