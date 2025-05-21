<?php
include "db.php";

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $new_pass, $token);

    if ($stmt->execute()) {
        echo "<script>alert('Password updated successfully'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Error updating password');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Set New Password</title>
    <style>
        body {
            background: url('your-background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial;
        }
        .container {
            background: white;
            border-radius: 25px;
            width: 400px;
            margin: 100px auto;
            padding: 40px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }
        input[type=password] {
            width: 90%;
            padding: 10px;
            margin: 10px;
            border-radius: 25px;
            border: 1px solid #00f;
            outline: none;
        }
        .reset-btn {
            background: rgb(0, 123, 255);
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="img/logo.png" width="60"><br><br>
        <h2>Set New Password</h2>
        <form method="post">
            <input type="password" name="password" placeholder="Enter new password" required><br>
            <button type="submit" class="reset-btn">Reset Password</button>
        </form>
    </div>
</body>
</html>
