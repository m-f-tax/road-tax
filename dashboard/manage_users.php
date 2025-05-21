<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (strlen($new_pass) < 8) {
        echo "<script>alert('❌ Password must be at least 8 characters.');</script>";
    } elseif ($new_pass !== $confirm_pass) {
        echo "<script>alert('❌ Passwords do not match.');</script>";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hashed_pass' WHERE id = $id");
        echo "<script>alert('✅ Password updated successfully.');</script>";
    }
}

$result = $conn->query("SELECT id, username, email, role, reset_requested FROM users");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f9ff;
            padding: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        h2 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px;
        }
        input[type="password"] {
            padding: 6px;
            width: 130px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <h2>🧑‍💻 Admin: Manage User Accounts</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Reset</th>
            <th>New Password</th>
            <th>Confirm</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <form method="POST">
                <td>
                    <?php echo $row['id']; ?>
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                </td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo $row['role']; ?></td>
                <td><?php echo $row['reset_requested']; ?></td>
                <td><input type="password" name="new_password" placeholder="New Password"></td>
                <td><input type="password" name="confirm_password" placeholder="Confirm"></td>
                <td><button type="submit">Update</button></td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
