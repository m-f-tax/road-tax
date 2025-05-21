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
    }
    .sidebar a {
      padding: 15px 25px;
      display: block;
      color: white;
      text-decoration: none;
      font-weight: bold;
      cursor: pointer;
    }
    .sidebar a:hover {
      background-color: #0056b3;
    }
    .dropdown-container {
      display: none;
      background-color: #3399ff;
    }
    .dropdown-container a {
      padding-left: 40px;
      display: block;
      font-weight: normal;
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
  </style>
</head>
<body>

<div class="sidebar">
  <div style="text-align: center; margin-bottom: 30px;">
    <img src="img/logo2.png" alt="Logo" style="width: 90px; height: 90px; border-radius: 50%;">
    <div style="font-size: 18px; font-weight: bold; margin-top: 10px;">ROAD-TAX MS</div>
    <div style="font-size: 13px;">SSC-KHAATUMO MOF</div>
  </div>

  <a onclick="loadPage('dashboard_home')">› Dashboard</a>

  <a onclick="toggleDropdown('vehicleDropdown')">› Vehicle Management ▾</a>
  <div class="dropdown-container" id="vehicleDropdown">
    <a onclick="loadPage('form')">› Register Form</a>
  </div>

  <a onclick="toggleDropdown('paymentDropdown')">› Payment Recording ▾</a>
  <div class="dropdown-container" id="paymentDropdown">
    <a onclick="loadPage('../generate/generate_payment')">› Generate Payment</a>
    <a onclick="loadPage('../reciept/reciept_payment')">› Receipt Payment</a>
    <a onclick="loadPage('generateonebyone')">› Generate One By One</a>
  </div>

  <a onclick="toggleDropdown('reportDropdown')">› Reports ▾</a>
  <div class="dropdown-container" id="reportDropdown">
    <a onclick="loadPage('reports')">› Report Vehicle</a>
    <a onclick="loadPage('../generate/generate_report')">› Generate Report</a>
    <a onclick="loadPage('../reciept/reciept_report')">› Receipt Report</a>
  </div>

  <a onclick="loadPage('Vehiclestatement')">› Vehicle Statement</a>

  <a onclick="toggleDropdown('settingsDropdown')">› Settings ▾</a>
  <div class="dropdown-container" id="settingsDropdown">
    <a onclick="loadPage('register_user')">› User</a>
    <a onclick="loadPage('settings')">› Role</a>
  </div>

  <a href="../logout">› Logout</a>
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
  var el = document.getElementById(id);
  el.style.display = (el.style.display === "block") ? "none" : "block";
}

function loadPage(page) {
  document.getElementById('contentFrame').src = page;
}
</script>

</body>
</html>
