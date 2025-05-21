<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

// Filter logic
$where = "1";
if (isset($_GET['interval']) && $_GET['interval'] != "") {
    $interval = intval($_GET['interval']);
    $where .= " AND a.interval_months = $interval";
}
if (isset($_GET['vehicle_type']) && $_GET['vehicle_type'] != "") {
    $vtype = $_GET['vehicle_type'];
    $where .= " AND a.vehicle_type = '$vtype'";
}

// Fetch data
$query = "SELECT a.*, GROUP_CONCAT(m.month_name ORDER BY m.month_name) AS months 
          FROM tbl_auto_charges a 
          LEFT JOIN tbl_charge_months m ON a.interval_months = m.interval_months 
          WHERE $where 
          GROUP BY a.id ORDER BY a.created_at DESC";
$result = $conn->query($query);

// Get vehicle types
$vehicle_types = $conn->query("SELECT DISTINCT vehicle_type FROM tbl_auto_charges");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Auto Charge Report</title>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f4faff; padding: 40px; }
    h2 { color: #007bff; margin-top: 60px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
    th { background-color: #007bff; color: white; }
    .actions a {
      margin: 0 5px;
      text-decoration: none;
      color: #007bff;
      font-weight: bold;
    }
    .actions a:hover {
      text-decoration: underline;
    }
    .top-bar {
      position: absolute;
      top: 20px;
      left: 20px;
    }
    .top-bar a {
      background: #007bff;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      font-weight: bold;
      text-decoration: none;
    }
    .top-bar a:hover {
      background: #0056b3;
    }
    form {
      margin-bottom: 20px;
    }
    select, button {
      padding: 8px;
      margin-right: 10px;
    }
  </style>
</head>
<body>

<div class="top-bar">
</div>

  <h2 style="text-align:center;">Warbixinta Dalacaadda Gawaadhida</h2>

  <form method="GET" style="text-align:center;">
    <select name="interval">
      <option value="">-- Door Interval --</option>
      <option value="3">3 Bilood</option>
      <option value="6">6 Bilood</option>
      <option value="12">12 Bilood</option>
    </select>

    <select name="vehicle_type">
      <option value="">-- Door Nooca Gawadhida --</option>
      <?php while($v = $vehicle_types->fetch_assoc()) {
        echo "<option value='".$v['vehicle_type']."'>".$v['vehicle_type']."</option>";
      } ?>
    </select>

    <button type="submit">Filter</button>
  </form>

  <table id="reportTable">
    <thead>
      <tr>
        <th>#</th>
        <th>Interval</th>
        <th>Bilaha</th>
        <th>Lacagta</th>
        <th>Nooca Gawadhida</th>
        <th>Isha</th>
        <th>Last Charged</th>
        <th>Next Due</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $i = 1;
      while ($row = $result->fetch_assoc()) {
        echo "<tr>
          <td>".$i++."</td>
          <td>".$row['interval_months']." Bilood</td>
          <td>".$row['months']."</td>
          <td>$".$row['amount']."</td>
          <td>".$row['vehicle_type']."</td>
          <td>".$row['source']."</td>
          <td>".$row['last_charged_date']."</td>
          <td>".$row['next_due_date']."</td>
          <td class='actions'>
            <a href='edit_charge?id=".$row['id']."'>✏️ Edit</a>
            <a href='delete_charge?id=".$row['id']."' onclick=\"return confirm('Ma hubtaa in aad tirtirto?')\">🗑️ Delete</a>
          </td>
        </tr>";
      }
      ?>
    </tbody>
  </table>

  <!-- DataTables Scripts -->
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
        buttons: ['excelHtml5', 'pdfHtml5'],
        pageLength: 10
      });
    });
  </script>
</body>
</html>
