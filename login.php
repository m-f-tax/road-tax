<?php
session_start();

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'Admin') {
            header("Location: dashboard/dashboard");
            exit;
        } elseif ($user['role'] === 'User') {
            header("Location: users/dashboard_user");
            exit;
        }
    } else {
        $error = "Login failed. Username or password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 1.5s ease-in-out;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: url('img/blurr.png') no-repeat center center fixed;
            background-size: cover;
            filter: blur(8px);
            z-index: -2;
        }

        body::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
            width: 100%;
            position: relative;
            z-index: 1;
            animation: fadeIn 1s ease;
        }

        .login-box img {
            width: 80px;
            margin-bottom: 20px;
        }

        .login-box h2 {
            color: #007bff;
            margin-bottom: 30px;
        }

        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            margin: 10px 0;
            border: 2px solid #007bff;
            border-radius: 25px;
        }

        .login-box button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            border: none;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            font-size: 16px;
            margin-top: 20px;
            cursor: pointer;
        }

        .login-box button:hover {
            background: #0056b3;
        }

        .error-message {
            color: red;
        }

        .rejected-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <form method="POST" class="login-box">
        <img src="img/logo.png" alt="Logo">
        <h2>Login</h2>

        <?php if (isset($_GET['rejected'])): ?>
            <div class="rejected-message">
                ❌ Your password reset request was rejected by the admin.
            </div>
        <?php endif; ?>

        <?php if (isset($error)) echo "<p class='error-message'>$error</p>"; ?>

        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</body>
</html>
