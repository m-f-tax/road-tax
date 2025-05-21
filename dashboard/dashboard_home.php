<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$vehicle_total = $conn->query("SELECT COUNT(*) AS total FROM vehiclemanagement")->fetch_assoc()['total'];

$month = date('m');
$year = date('Y');

$monthly_revenue = $conn->query("SELECT SUM(amount) AS total FROM tbl_reciept WHERE MONTH(due_date) = $month AND YEAR(due_date) = $year")->fetch_assoc()['total'] ?? 0;
$monthly_revenue_display = $monthly_revenue >= 1000 ? round($monthly_revenue / 1000, 1) . "K" : $monthly_revenue;

$pending_amount_total = $conn->query("SELECT SUM(amount) AS total FROM tblgenerate WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;

$monthly_data = [];
for ($m = 1; $m <= 12; $m++) {
  $row = $conn->query("SELECT SUM(amount) AS total FROM tbl_reciept WHERE MONTH(due_date) = $m AND YEAR(due_date) = $year")->fetch_assoc();
  $monthly_data[] = $row['total'] ?? 0;
}

$pie_result = $conn->query("SELECT carname, COUNT(*) as count FROM vehiclemanagement GROUP BY carname");
$pie_labels = $pie_counts = [];
while ($row = $pie_result->fetch_assoc()) {
  $pie_labels[] = $row['carname'];
  $pie_counts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f9ff;
      margin: 0;
      padding: 20px;
    }
    h2 {
      color: #007bff;
      margin-bottom: 10px;
    }
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    .emoji-buttons {
      display: flex;
      gap: 15px;
    }
    .emoji {
      font-size: 24px;
      text-decoration: none;
      background-color: #007bff;
      color: white;
      padding: 12px 14px;
      border-radius: 50%;
      position: relative;
      transition: all 0.2s ease;
    }
    .emoji:hover {
      background-color: #0056b3;
      transform: scale(1.1);
    }
    .emoji::after {
      content: attr(data-title);
      position: absolute;
      bottom: -28px;
      left: 50%;
      transform: translateX(-50%);
      background: #222;
      color: #fff;
      padding: 4px 8px;
      font-size: 12px;
      border-radius: 4px;
      white-space: nowrap;
      opacity: 0;
      transition: opacity 0.2s ease-in-out;
      pointer-events: none;
    }
    .emoji:hover::after {
      opacity: 1;
    }
    .profile-box {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .profile-box img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid #007bff;
    }
    .cards {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      justify-content: space-between;
    }
    .card {
      flex: 1;
      min-width: 250px;
      background: #fff;
      border: 2px solid #007bff;
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      color: #007bff;
      font-weight: bold;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .chart-box {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 15px;
      margin-top: 20px;
    }
    canvas {
      width: 100% !important;
      max-height: 250px !important;
    }
  </style>
</head>
<body>

<div class="top-bar">
<<<<<<< HEAD
  <h2>📊 Dashboard Summary</h2>
=======
  <h2>📊 Dashboard summery</h2>
>>>>>>> 959c197 (Initial commit)
  <div class="profile-box">
    <a href="settings" class="emoji" data-title="Setting">⚙️</a>
    <a href="../generate/generate_report" class="emoji" data-title="Report">📊</a>
    <a href="add_vehicle_type" class="emoji" data-title="Add Type">🚘</a>
    <a href="manage_users" class="emoji" data-title="Users">👥</a>
    <a href="profile_admin">
      <img src="img/logo3.PNG" alt="Admin" title="Admin Profile">
    </a>
  </div>
</div>

<div class="cards">
  <div class="card">
    🚗 Total Vehicles<br><br>
    <span style="font-size: 28px;"><?php echo $vehicle_total; ?></span>
  </div>
  <div class="card">
    💰 This Month<br><br>
    <span style="font-size: 28px;"><?php echo $monthly_revenue_display; ?> USD</span>
  </div>
  <div class="card">
    ⏳ Pending<br><br>
    <span style="font-size: 28px;"><?php echo $pending_amount_total; ?> USD</span>
  </div>
</div>

<div class="chart-box">
  <canvas id="lineChart"></canvas>
</div>

<div class="cards" style="margin-top: 10px;">
  <div class="chart-box" style="flex: 1;">
    <h4 style="text-align:center; color:#007bff;">Vehicle Types</h4>
    <canvas id="pieChart"></canvas>
  </div>
  <div class="chart-box" style="flex: 1;">
    <h4 style="text-align:center; color:#007bff;">Pending Amount</h4>
    <canvas id="pendingChart"></canvas>
  </div>
</div>

<script>
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [{
      label: 'Monthly Revenue (<?php echo $year; ?>)',
      data: <?php echo json_encode($monthly_data); ?>,
      borderColor: '#007bff',
      backgroundColor: 'rgba(0,123,255,0.1)',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});

new Chart(document.getElementById('pieChart'), {
  type: 'pie',
  data: {
    labels: <?php echo json_encode($pie_labels); ?>,
    datasets: [{
      data: <?php echo json_encode($pie_counts); ?>,
      backgroundColor: ['#00bcd4', '#2196f3', '#ff9800', '#8bc34a', '#607d8b']
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
  }
});

new Chart(document.getElementById('pendingChart'), {
  type: 'bar',
  data: {
    labels: ['Pending'],
    datasets: [{
      label: 'Amount ($)',
      data: [<?php echo $pending_amount_total; ?>],
      backgroundColor: '#ff6384',
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>

</body>
</html>
