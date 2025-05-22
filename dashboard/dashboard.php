<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f9ff;
    }
    .sidebar {
      width: 250px;
      background-color: #007bff;
      position: fixed;
      height: 100%;
      color: white;
      padding-top: 30px;
      box-shadow: 2px 0 8px rgba(0,0,0,0.1);
      overflow-y: auto;
    }
    .sidebar a {
      padding: 14px 25px;
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
      text-decoration: none;
      font-weight: 500;
      font-size: 15px;
      transition: 0.3s;
      border-left: 4px solid transparent;
    }
    .sidebar a:hover {
      background-color: rgba(255,255,255,0.1);
      border-left: 4px solid #fff;
    }
    .sidebar .main-link::before {
      content: "◉";
      font-size: 10px;
      margin-right: 10px;
    }
    .dropdown-container a::before {
      content: "▹";
      font-size: 10px;
      margin-right: 10px;
    }
    .dropdown-container {
      display: none;
      background-color: #3399ff;
      animation: slideIn 0.3s ease;
    }
    .dropdown-container a {
      padding-left: 45px;
      font-weight: normal;
      font-size: 14px;
      color: white;
    }
    .dropdown-container a:hover {
      background-color: #007bff;
    }
    .main {
      margin-left: 250px;
    }
    iframe {
      width: 100%;
      height: 100vh;
      border: none;
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(-5px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .logo-box {
      text-align: center;
      margin-bottom: 30px;
    }
    .logo-box img {
      width: 85px;
      height: 85px;
      border-radius: 50%;
      border: 2px solid white;
    }
    .logo-box div {
      margin-top: 5px;
      font-size: 15px;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="logo-box">
    <img src="img/logo2.png" alt="Logo">
    <div style="font-weight: bold;">ROAD-TAX MS</div>
    <div style="font-size: 12px;">SSC-KHAATUMO MOF</div>
  </div>

  <a onclick="loadPage('dashboard_home')" class="main-link">Dashboard</a>

  <a onclick="toggleDropdown('vehicleDropdown')" class="main-link">Vehicle Management</a>
  <div class="dropdown-container" id="vehicleDropdown">
    <a onclick="loadPage('form')">Register Form</a>
  </div>

  <a onclick="toggleDropdown('paymentDropdown')" class="main-link">Payment Recording</a>
  <div class="dropdown-container" id="paymentDropdown">
    <a onclick="loadPage('../generate/generate_payment')">Generate Payment</a>
    <a onclick="loadPage('../reciept/reciept_payment')">Receipt Payment</a>
  </div>

  <a onclick="toggleDropdown('reportDropdown')" class="main-link">Reports</a>
  <div class="dropdown-container" id="reportDropdown">
    <a onclick="loadPage('reports')">Report Vehicle</a>
    <a onclick="loadPage('../generate/generate_report')">Generate Report</a>
    <a onclick="loadPage('../reciept/reciept_report')">Receipt Report</a>
  </div>

  <a onclick="loadPage('Vehiclestatement')" class="main-link">Vehicle Statement</a>

  <a onclick="toggleDropdown('settingsDropdown')" class="main-link">Settings</a>
  <div class="dropdown-container" id="settingsDropdown">
   <!--- <a onclick="loadPage('register_user')">User</a> ---->
    <a onclick="loadPage('settings')">Role</a>
  </div>

  <a href="../logout" class="main-link">Logout</a>
</div>

<div class="main">
  <iframe id="contentFrame" src="dashboard_home.php"></iframe>
</div>

<script>
function toggleDropdown(id) {
  const dropdowns = document.getElementsByClassName("dropdown-container");
  for (let i = 0; i < dropdowns.length; i++) {
    if (dropdowns[i].id !== id) {
      dropdowns[i].style.display = "none";
    }
  }
  const el = document.getElementById(id);
  el.style.display = (el.style.display === "block") ? "none" : "block";
}

function loadPage(page) {
  document.getElementById('contentFrame').src = page;
}
</script>

</body>
</html>
