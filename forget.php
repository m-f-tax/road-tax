<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $token = bin2hex(random_bytes(50));
        $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // Send email
        $subject = "Reset your password";
        $message = "Click the link to reset your password: http://yourdomain.com/reset_password.php?token=$token";
        $headers = "From: no-reply@yourdomain.com";

        mail($email, $subject, $message, $headers);
        echo "<script>alert('Reset link sent to your email!');</script>";
    } else {
        echo "<script>alert('Email not found!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
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
        input[type=text], input[type=email] {
            width: 90%;
            padding: 10px;
            margin: 10px;
            border-radius: 25px;
            border: 1px solid #00f;
            outline: none;
        }
        .send-btn {
            background: rgb(23, 171, 224);
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
        <h2>Reset Password</h2>
        <form method="post">
            <input type="email" name="email" placeholder="Enter your Gmail" required><br>
            <button type="submit" class="send-btn">Send Reset Link</button>
        </form>
        <a href="login"><b>Back to Login</b></a>
    </div>
</body>
</html>
