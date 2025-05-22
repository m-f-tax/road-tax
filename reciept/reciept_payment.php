<?php
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$success = $error = "";
$vehicle_type = $amount = $owner = "";
$plate = "";

if (isset($_GET['search_plate']) || isset($_GET['search_phone'])) {
    $plate = $_GET['search_plate'] ?? '';
    $phone = $_GET['search_phone'] ?? '';

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
        $stmt = $conn->prepare("SELECT SUM(amount) FROM tblgenerate WHERE platenumber = ?");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($gen_amount);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT SUM(amount) FROM tbl_reciept WHERE plate_number = ?");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($paid_amount);
        $stmt->fetch();
        $stmt->close();

        $gen_amount = $gen_amount ?? 0;
        $paid_amount = $paid_amount ?? 0;
        $amount = $gen_amount - $paid_amount;

        $stmt = $conn->prepare("SELECT vehicletype FROM tblgenerate WHERE platenumber = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($vehicle_type);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("SELECT owner FROM vehiclemanagement WHERE platenumber = ? LIMIT 1");
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $stmt->bind_result($owner);
        $stmt->fetch();
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_receipt'])) {
    $vehicle_type = $_POST['vehicle_type'];
    $plate_number = $_POST['plate_number'];
    $owner = $_POST['owner'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $file_name = basename($_FILES["receipt_image"]["name"]);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["receipt_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO tbl_reciept (vehicle_type, plate_number, owner, amount, due_date, receipt_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddss", $vehicle_type, $plate_number, $owner, $amount, $due_date, $file_name, $status);
        if ($stmt->execute()) $success = "✅ Receipt recorded successfully!";
        else $error = "❌ Error: " . $stmt->error;
        $stmt->close();
    } else {
        $error = "❌ Failed to upload receipt image.";
    }
}

$receipts = $conn->query("SELECT r.*, v.phone FROM tbl_reciept r LEFT JOIN vehiclemanagement v ON r.plate_number = v.platenumber ORDER BY r.id DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Receipt Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body { background: #eef3f9; font-family: 'Segoe UI', sans-serif; padding: 30px; }
    .modal-body input, .modal-body select { margin-bottom: 10px; }
    .table th { background: #0d6efd; color: white; }
    .btn-sm { margin: 2px; }
    .filter-bar { margin-bottom: 20px; display: flex; justify-content: end; gap: 10px; }
    .table td { vertical-align: middle; }
    .actions-buttons { display: flex; gap: 5px; justify-content: center; }
  </style>
</head>
<body>
<div class="container bg-white shadow rounded p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary">🧾 Receipt Report</h4>
    <div class="filter-bar">
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="search_plate" class="form-control" placeholder="Search Plate" value="<?= htmlspecialchars($_GET['search_plate'] ?? '') ?>">
        <input type="text" name="search_phone" class="form-control" placeholder="Search Phone" value="<?= htmlspecialchars($_GET['search_phone'] ?? '') ?>">
        <button type="submit" class="btn btn-primary">🔍</button>
      </form>
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receiptModal"> Reciept Payment</button>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success text-center"><?= $success ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger text-center"><?= $error ?></div><?php endif; ?>

  <table class="table table-bordered text-center">
    <thead>
      <tr>
        <th>#</th><th>Plate</th><th>Phone</th><th>Owner</th><th>Type</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; while($row=$receipts->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= $row['plate_number'] ?></td>
          <td><?= $row['phone'] ?></td>
          <td><?= $row['owner'] ?></td>
          <td><?= $row['vehicle_type'] ?></td>
          <td>$<?= number_format($row['amount'], 2) ?></td>
          <td><?= $row['status'] ?></td>
          <td><?= date('d M Y - H:i', strtotime($row['due_date'])) ?></td>
          <td class="actions-buttons">
            <a href="edit_receipt.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
            <a href="delete_receipt.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete this?')" class="btn btn-danger btn-sm">🗑️</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<div class="modal fade <?= !empty($plate) ? 'show d-block' : '' ?>" id="receiptModal" tabindex="-1" aria-modal="true" role="dialog" <?= !empty($plate) ? 'style="background: rgba(0,0,0,0.5);"' : '' ?>>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">🔍 Search Vehicle</h5>
        <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn-close"></a>
      </div>
      <div class="modal-body">
        <form method="GET">
          <input type="text" name="search_plate" class="form-control" placeholder="Plate Number">
          <div class="text-center my-2">OR</div>
          <input type="text" name="search_phone" class="form-control" placeholder="Phone Number">
          <button class="btn btn-primary mt-2 w-100">Search</button>
        </form>
        <?php if (!empty($plate)): ?>
          <?php if ($amount <= 0): ?>
            <div class="alert alert-danger mt-3">❌ No unpaid charges found for this vehicle.</div>
          <?php else: ?>
            <hr>
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="plate_number" value="<?= htmlspecialchars($plate) ?>">
              <input type="text" name="vehicle_type" class="form-control" value="<?= htmlspecialchars($vehicle_type) ?>" readonly>
              <input type="text" name="owner" class="form-control" value="<?= htmlspecialchars($owner) ?>" readonly>
              <input type="number" step="0.01" name="amount" class="form-control" value="<?= htmlspecialchars($amount) ?>" readonly>
              <input type="hidden" name="due_date" value="<?= date('Y-m-d H:i:s') ?>">
              <select name="status" class="form-control" required>
                <option value="On Time">On Time</option>
                <option value="Overdue">Overdue</option>
              </select>
              <input type="file" name="receipt_image" class="form-control" required>
              <button type="submit" name="submit_receipt" class="btn btn-success w-100 mt-2">💾 Save Receipt</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
