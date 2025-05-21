<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $interval = $_POST['interval'];
    $amount = $_POST['amount'];
    $vehicle_type = $_POST['vehicle_type'];
    $last_charged_date = $_POST['last_charged_date'];
    $next_due_date = $_POST['next_due_date'];
    $source = $_POST['source'];

    // Check if already charged within a year
    $stmt = $conn->prepare("SELECT last_charged_date FROM tbl_auto_charges WHERE vehicle_type = ? ORDER BY last_charged_date DESC LIMIT 1");
    $stmt->bind_param("s", $vehicle_type);
    $stmt->execute();
    $result = $stmt->get_result();

    $skipInsert = false;
    if ($row = $result->fetch_assoc()) {
        $last_charged = new DateTime($row['last_charged_date']);
        $next_allowed = clone $last_charged;
        $next_allowed->modify("+1 year");

        $current_input = new DateTime($last_charged_date);

        if ($current_input < $next_allowed) {
            $skipInsert = true;
            $error = "❌ Gaariga ($vehicle_type) hore ayaa loogu dalacay sanad gudihiisa. Waxaad ku dalaci kartaa kadib: " . $next_allowed->format("Y-m-d");
        }
    }

    if (!$skipInsert) {
        $stmt = $conn->prepare("INSERT INTO tbl_auto_charges (interval_months, amount, vehicle_type, due_date, source, created_at, last_charged_date, next_due_date)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->bind_param("idsssss", $interval, $amount, $vehicle_type, $next_due_date, $source, $last_charged_date, $next_due_date);

        if ($stmt->execute()) {
            $success = "✅ Gaariga $vehicle_type si guul leh ayaa loogu dalacay. OK! Sanad walba bishaan otomatik ayaa loogu dalaci doonaa!";
        } else {
            $error = "❌ Fashil: " . $stmt->error;
        }
    }

    // ✅ Start Auto-Charge if today matches allowed months
    $month_name = date('F');
    $year = date('Y');

    // fetch all auto charge configs
    $configs = $conn->query("SELECT * FROM tbl_auto_charges GROUP BY vehicle_type, interval_months, amount, source");
    while ($config = $configs->fetch_assoc()) {
        $vtype = $config['vehicle_type'];
        $iv = (int)$config['interval_months'];
        $amt = $config['amount'];
        $src = $config['source'];

        $check_month = $conn->prepare("SELECT * FROM tbl_charge_months WHERE interval_months=? AND month_name=?");
        $check_month->bind_param("is", $iv, $month_name);
        $check_month->execute();
        $res = $check_month->get_result();

        if ($res->num_rows > 0) {
            // check if already charged this year
            $chk = $conn->prepare("SELECT id FROM tbl_auto_charges WHERE vehicle_type=? AND interval_months=? AND YEAR(last_charged_date)=?");
            $chk->bind_param("sii", $vtype, $iv, $year);
            $chk->execute();
            $exists = $chk->get_result();

            if ($exists->num_rows == 0) {
                $last = date('Y-m-d');
                $next = date('Y-m-d', strtotime("+$iv months"));

                $save = $conn->prepare("INSERT INTO tbl_auto_charges (vehicle_type, interval_months, amount, last_charged_date, next_due_date, due_date, source, created_at)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $save->bind_param("sidssss", $vtype, $iv, $amt, $last, $next, $next, $src);
                $save->execute();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Auto Charge Save</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #eef;
      padding: 40px;
    }
    .container {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #007bff;
    }
    .message {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
    }
    .success { background: #e6ffee; color: #007700; }
    .error { background: #ffe6e6; color: #cc0000; }
    label { font-weight: bold; }
    input, select {
      width: 100%; padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
    }
  </style>
  <script>
    function calculateNextDue() {
      let last = document.getElementById("last_charged_date").value;
      let interval = document.getElementById("interval").value;
      if (last && interval) {
        let date = new Date(last);
        date.setMonth(date.getMonth() + parseInt(interval));
        let y = date.getFullYear();
        let m = ("0" + (date.getMonth() + 1)).slice(-2);
        let d = ("0" + date.getDate()).slice(-2);
        document.getElementById("next_due_date").value = `${y}-${m}-${d}`;
      }
    }
  </script>
</head>
<body>
  <div class="container">
    <h2>Dalacaadda Gawaarida (Auto Charge)</h2>

    <?php if ($success): ?>
      <div class="message success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <label>Xilliga Dalacaadda (Bilood):</label>
      <select name="interval" id="interval" onchange="calculateNextDue()" required>
        <option value="">-- Door interval --</option>
        <option value="3">3 Bilood</option>
        <option value="6">6 Bilood</option>
        <option value="12">12 Bilood</option>
      </select>

      <label>Lacagta ($):</label>
      <input type="number" step="0.01" name="amount" required>

      <label>Nooca Gawadhida:</label>
      <input type="text" name="vehicle_type" required placeholder="Tusaale: Bus, Taxi, iwm">

      <label>Last Charged Date:</label>
      <input type="date" id="last_charged_date" name="last_charged_date" onchange="calculateNextDue()" required>

      <label>Next Due Date:</label>
      <input type="date" id="next_due_date" name="next_due_date" readonly required>

      <label>Source:</label>
      <input type="text" name="source" required placeholder="Tusaale: zaad, cash, iwm">

      <button type="submit">💾 Save</button>
    </form>
  </div>
</body>
</html>
