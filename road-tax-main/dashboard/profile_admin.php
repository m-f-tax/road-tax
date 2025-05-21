<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #cceeff,rgb(214, 214, 255));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .profile-card {
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 420px;
            width: 100%;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-5px);
        }

        .profile-card img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 3px solidrgb(51, 40, 167);
            box-shadow: 0 0 10px rgba(0, 128, 0, 0.3);
        }

        .profile-card h2 {
            margin-top: 15px;
            font-size: 24px;
            color: #007bff;
        }

        .profile-card p {
            font-size: 16px;
            margin: 8px 0;
            color: #444;
        }

        .profile-card p strong {
            color:rgb(55, 40, 167);
        }

        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="profile-card">
    <img src="img/logo3.PNG" alt="Admin">
    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
    <p><strong>Registered:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
    <a class="back-button" href="dashboard">← Back to Dashboard</a>
</div>

</body>
</html>
