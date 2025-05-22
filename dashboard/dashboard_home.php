<?php
$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$vehicle_total = $conn->query("SELECT COUNT(*) AS total FROM vehiclemanagement")->fetch_assoc()['total'];
$monthly_data = [];
$month = date('m');
$year = date('Y');

$total_revenue = $conn->query("SELECT SUM(amount) AS total FROM tbl_reciept")->fetch_assoc()['total'] ?? 0;
$pending_amount_total = $conn->query("SELECT SUM(amount) AS total FROM tblgenerate WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$collected_amount = max(0, $total_revenue - $pending_amount_total);

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
  <title>Modern Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom right, #eaf3fb, #ffffff);
      overflow: hidden;
    }
    .wrapper {
      max-width: 1200px;
      margin: auto;
      padding: 30px 25px;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .topbar h2 {
      color: #333;
      font-size: 24px;
    }
    .icons {
      display: flex;
      gap: 10px;
    }
    .icons a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 45px;
      height: 45px;
      background: #007bff;
      color: white;
      font-size: 18px;
      text-decoration: none;
      border-radius: 10px;
      transition: 0.3s ease-in-out;
    }
    .icons a:hover {
      background: #0056b3;
      transform: translateY(-2px);
    }
    .stats {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 25px;
    }
    .card {
      flex: 1;
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
      text-align: center;
    }
    .card h4 {
      font-size: 16px;
      color: #666;
      margin-bottom: 8px;
    }
    .card p {
      font-size: 28px;
      color: #007bff;
      font-weight: bold;
    }
    .charts {
      display: flex;
      justify-content: space-between;
      gap: 20px;
    }
    .chart-box {
      flex: 1;
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    .chart-box h5 {
      text-align: center;
      margin-bottom: 10px;
      font-size: 16px;
      color: #333;
    }
    canvas {
      width: 100% !important;
      height: 250px !important;
    }
  </style>
</head>
<body>

<div class="wrapper">
  <div class="topbar">
    <h2>📊 Dashboard Overview</h2>
    <div class="icons">
      <a href="settings" title="Settings">⚙️</a>
    <!--  <a href="../generate/generate_report" title="Reports">📄</a> ----->
      <a href="add_vehicle_type" title="Add Type">🚘</a>
      <a href="manage_users" title="Users">👥</a>
      <a href="profile_admin" title="Profile"><img src="img/logo3.PNG" style="width: 35px; height: 35px; border-radius: 50%;"></a>
    </div>
  </div>

  <div class="stats">
    <div class="card">
      <h4>🚗 Total Vehicles</h4>
      <p><?php echo $vehicle_total; ?></p>
    </div>
    <div class="card">
      <h4>💰 Total Revenue</h4>
      <p><?php echo $total_revenue; ?> USD</p>
    </div>
    <div class="card">
      <h4>⏳ Pending Amount</h4>
      <p><?php echo $pending_amount_total; ?> USD</p>
    </div>
  </div>

  <div class="charts">
    <div class="chart-box">
      <h5>Monthly Revenue (Radar)</h5>
      <canvas id="radarChart"></canvas>
    </div>
    <div class="chart-box">
      <h5>Vehicle Types (Bar)</h5>
      <canvas id="barChart"></canvas>
    </div>
    <div class="chart-box">
      <h5>Pending vs Collected (Stacked)</h5>
      <canvas id="pendingChart"></canvas>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('radarChart'), {
  type: 'radar',
  data: {
    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [{
      label: 'Monthly Revenue',
      data: <?php echo json_encode($monthly_data); ?>,
      backgroundColor: 'rgba(0,123,255,0.2)',
      borderColor: '#007bff',
      pointBackgroundColor: '#007bff',
      fill: true
    }]
  },
  options: {
    plugins: { legend: { position: 'top' }},
    maintainAspectRatio: false
  }
});

new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($pie_labels); ?>,
    datasets: [{
      label: 'Vehicles',
      data: <?php echo json_encode($pie_counts); ?>,
      backgroundColor: ['#007bff', '#28a745', '#ffc107', '#17a2b8', '#dc3545']
    }]
  },
  options: {
    plugins: { legend: { display: false }},
    scales: { y: { beginAtZero: true }},
    maintainAspectRatio: false
  }
});

new Chart(document.getElementById('pendingChart'), {
  type: 'bar',
  data: {
    labels: ['Amount Status'],
    datasets: [{
      label: 'Pending',
      data: [<?php echo $pending_amount_total; ?>],
      backgroundColor: '#ff4c4c'
    }, {
      label: 'Collected',
      data: [<?php echo $collected_amount; ?>],
      backgroundColor: '#28a745'
    }]
  },
  options: {
    plugins: { legend: { position: 'top' }},
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: { stacked: true },
      y: { stacked: true, beginAtZero: true }
    }
  }
});
</script>

</body>
</html>
