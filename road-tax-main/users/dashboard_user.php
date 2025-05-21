<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'User') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
$user_id = $_SESSION['user_id'];

$sql = "SELECT page_name FROM tbl_user_pages WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$pages = [];
while ($row = $result->fetch_assoc()) {
    $pages[] = $row['page_name'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f7fa;
            display: flex;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg,rgb(46, 144, 249), #0069d9);
            color: white;
            padding-top: 30px;
            position: fixed;
            box-shadow: 3px 0 12px rgba(13, 59, 222, 0.1);
        }
        .sidebar .logo-box {
            text-align: center;
            margin-bottom: 30px;
        }
        .sidebar .logo-box img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 2px solid white;
        }
        .sidebar .logo-box .title {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .sidebar .logo-box .sub {
            font-size: 12px;
            color: #d4e3ff;
        }
        .sidebar a {
            display: block;
            padding: 12px 30px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            padding-left: 40px;
            border-left: 4px solid #ffc107;
        }

        .content {
            margin-left: 250px;
            padding: 40px;
            flex: 1;
            background: #ffffff;
            min-height: 100vh;
            background-image: url('img/dashboard-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #1e2d3b;
        }
        .overlay {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
            margin: 50px auto;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .overlay-text {
            max-width: 60%;
        }
        .overlay-text h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .overlay-text p {
            font-size: 16px;
            color: #444;
        }
        .overlay img {
            width: 320px;
            height: 420px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo-box">
        <img src="img/logo2.png" alt="Logo">
        <div class="title">ROAD-TAX MS</div>
        <div class="sub">SSC-KHAATUMO MOF</div>
    </div>

    <?php foreach ($pages as $page): ?>
        <a href="/<?php echo $page; ?>">
            &bull; <?php echo ucfirst(str_replace([".php", "_", "-"], ["", " ", " "], basename($page))); ?>
        </a>
    <?php endforeach; ?>
    
    <a href="../logout.php">🚪 Logout</a>
</div>

<div class="content">
    <div class="overlay">
        <div class="overlay-text">
            <h1>Welcome to Your Dashboard</h1>
            <p>You have access to your assigned features. Use the left menu to manage your tasks efficiently.</p>
        </div>
        <img src="img/user.PNG" alt="User Image">
    </div>
</div>

</body>
</html>
