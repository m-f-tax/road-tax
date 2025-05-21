<?php if (isset($_GET['error'])): ?>
  <div style="background:#ffe6e6; color:#cc0000; padding:10px; border-radius:5px; margin-bottom:20px; text-align:center;">
    <?= htmlspecialchars($_GET['error']) ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
  <div style="background:#e6ffee; color:#007700; padding:10px; border-radius:5px; margin-bottom:20px; text-align:center;">
    <?= htmlspecialchars($_GET['success']) ?>
  </div>
<?php endif; ?>

<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

// Fetch vehicle types
$vehicle_types = $conn->query("SELECT * FROM vehicle_types");

// Fetch interval month mapping
$months_result = $conn->query("SELECT * FROM tbl_charge_months");
$months_data = [];
while ($row = $months_result->fetch_assoc()) {
    $months_data[$row['interval_months']][] = $row['month_name'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Auto Charge Vehicles</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #e6f0ff;
      padding: 20px;
    }
    .container {
      background: #fff;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      max-width: 900px;
      margin: auto;
    }
    h2 {
      color: #007bff;
      text-align: center;
    }
    label {
      font-weight: bold;
    }
    select, input {
      width: 100%;
      padding: 8px;
      margin-bottom: 20px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }
    button {
      background: #007bff;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
    }
    .report-button {
      background: #17a2b8;
      margin-top: 20px;
    }
    ul#months_display {
      background: #eef;
      padding: 10px 20px;
      border-radius: 10px;
      margin-top: -10px;
      margin-bottom: 20px;
    }
    ul#months_display li {
      display: inline-block;
      margin-right: 10px;
      font-weight: bold;
      color: #333;
    }
    .charge-link {
      position: absolute;
      top: 15px;
      right: 20px;
      text-decoration: none;
      background: #007bff;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: bold;
      transition: background 0.3s ease;
    }
    .charge-link:hover {
      background: #0056b3;
    }
    .back-link {
      position: absolute;
      top: 15px;
      left: 20px;
      text-decoration: none;
      background: #007bff;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: bold;
      transition: background 0.3s ease;
    }
    .back-link:hover {
      background: #0056b3;
    }
  </style>
  <script>
    const intervalMonths = <?php echo json_encode($months_data); ?>;

    function fillMonths() {
      let interval = document.getElementById("interval").value;
      let monthBox = document.getElementById("months_display");
      monthBox.innerHTML = "";

      if (intervalMonths[interval]) {
        intervalMonths[interval].forEach(function(m) {
          let li = document.createElement("li");
          li.textContent = m;
          monthBox.appendChild(li);
        });
      }

      calculateNextDue(); // update next due when interval changes
    }

    function calculateNextDue() {
      let lastDate = document.getElementById("last_charged_date").value;

      if (lastDate) {
        let parts = lastDate.split("-");
        let year = parseInt(parts[0]) + 1; // sanad ku dar
        let month = parts[1];
        let day = parts[2];
        let nextDue = year + "-" + month + "-" + day;
        document.getElementById("next_due_date").value = nextDue;
      }
    }
  </script>
</head>
<body>
  <div class="container">
    <a class="charge-link" href="charge_months_config">← Charge Months</a>
    <h2>Dalacaadda Gawaadhida (Automatic Charging)</h2>
    <form action="auto_charge_save.php" method="POST">
      <label>Xilliga Dalacaadda (Bilood):</label>
      <select name="interval" id="interval" onchange="fillMonths()" required>
        <option value="">-- Door interval --</option>
        <option value="3">3 Bilood</option>
        <option value="6">6 Bilood</option>
        <option value="12">12 Bilood</option>
      </select>

      <ul id="months_display"></ul>

      <label>Lacagta ($):</label>
      <input type="number" step="0.01" name="amount" required>

      <label>Nooca Gawadhida:</label>
      <select name="vehicle_type" required>
        <option value="">-- Dooro Nooca --</option>
        <?php while ($row = $vehicle_types->fetch_assoc()) {
          echo "<option value='".$row['name']."'>".$row['name']."</option>";
        } ?>
      </select>

      <label>Last Charged Date:</label>
      <input type="date" id="last_charged_date" name="last_charged_date" onchange="calculateNextDue()" required>

      <label>Next Due Date:</label>
      <input type="date" id="next_due_date" name="next_due_date" readonly required>

      <label>Source:</label>
      <input type="text" name="source" required placeholder="Tusaale: zaad, zahal, iwm.">

      <button type="submit">Save</button>
    </form>

    <form action="auto_charge_report" method="GET">
      <button type="submit" class="report-button">➤ Eeg Report-Tiga</button>
    </form>
  </div>
</body>
</html>
