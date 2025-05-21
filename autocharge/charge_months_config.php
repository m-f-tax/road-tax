<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $interval = $_POST['interval'];
    $months = $_POST['months'];

    $conn->query("DELETE FROM tbl_charge_months WHERE interval_months = $interval");
    foreach ($months as $month) {
        $stmt = $conn->prepare("INSERT INTO tbl_charge_months (interval_months, month_name) VALUES (?, ?)");
        $stmt->bind_param("is", $interval, $month);
        $stmt->execute();
    }
    echo "<script>alert('Bilaha si guul ah ayaa loo kaydiyay!'); window.location='charge_months_config.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Deji Bilaha Dalacaadda</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #eaf7ff; padding: 40px; }
    .container { background: white; padding: 30px; border-radius: 12px; max-width: 900px; margin: auto; }
    label, h2 { font-weight: bold; }
    .months-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 15px; }
    button, .back-btn {
      background: #007bff; color: white; padding: 10px 15px;
      border: none; border-radius: 5px; font-weight: bold;
      cursor: pointer;
    }
    .back-btn { position: absolute; top: 20px; left: 30px; text-decoration: none; }
    table.dataTable thead th {
      background-color: #007bff;
      color: white;
    }
    table.dataTable tbody tr:hover {
      background-color: #f1f1f1;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Deji Bilaha Xilliga Dalacaadda</h2>
    <form method="POST">
      <label>Xilliga Dalacaadda:</label>
      <select name="interval" required>
        <option value="3">3 Bilood</option>
        <option value="6">6 Bilood</option>
        <option value="12">12 Bilood</option>
      </select>

      <label>Bilaha:</label>
      <div class="months-grid">
        <?php
        $months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        foreach ($months as $m) {
          echo "<label><input type='checkbox' name='months[]' value='$m'> $m</label>";
        }
        ?>
      </div>
      <button type="submit">Kaydi Bilaha</button>
    </form>
  </div>

  <div class="container" style="margin-top:40px;">
    <h2>Warbixinta Bilaha la Kaydiyay</h2>
    <table id="reportTable" class="display" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>Interval (Bilood)</th>
          <th>Bil Magaceeda</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $res = $conn->query("SELECT * FROM tbl_charge_months ORDER BY interval_months, month_name");
        $i = 1;
        while ($row = $res->fetch_assoc()) {
          echo "<tr>
                  <td>{$i}</td>
                  <td>{$row['interval_months']}</td>
                  <td>{$row['month_name']}</td>
                  <td>
                    <a href='edit_charge_month.php?id={$row['id']}'>✏️ Edit</a> |
                    <a href='delete_charge_month.php?id={$row['id']}' onclick=\"return confirm('Ma hubtaa in aad tirtirto?')\">🗑️ Delete</a>
                  </td>
                </tr>";
          $i++;
        }
        ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script>
    $(document).ready(function() {
      $('#reportTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf']
      });
    });
  </script>
</body>
</html>
