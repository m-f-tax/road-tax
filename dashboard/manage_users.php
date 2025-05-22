<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$success = $error = "";

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_user'])) {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = $_POST['role'];

    if (strlen($password) < 8) {
        $error = "❌ Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "❌ Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed, $role);

        if ($stmt->execute()) {
            $success = "✅ User registered successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $id = $_POST['id'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (strlen($new_pass) < 8) {
        $error = "❌ Password must be at least 8 characters.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "❌ Passwords do not match.";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hashed_pass' WHERE id = $id");
        $success = "✅ Password updated successfully.";
    }
}

$result = $conn->query("SELECT id, username, email, role, reset_requested FROM users");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage & Register Users</title>
  <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: #f4f9ff;
        margin: 0;
        padding: 40px;
    }
    h2 {
        text-align: center;
        color: #007bff;
        margin-bottom: 20px;
    }
    .btn {
        background: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        font-weight: bold;
        border-radius: 6px;
        cursor: pointer;
        margin-bottom: 20px;
    }
    .btn:hover {
        background: #0056b3;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #ccc;
        text-align: left;
    }
    th {
        background: #007bff;
        color: white;
    }
    input[type="password"] {
        width: 130px;
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    button[type="submit"] {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
    }
    button[type="submit"]:hover {
        background: #218838;
    }

    .modal {
        display: none;
        position: fixed;
        left: 0; top: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.4);
        justify-content: center;
        align-items: center;
    }
    .modal-content {
        background: white;
        padding: 30px;
        width: 400px;
        border-radius: 10px;
        position: relative;
    }
    .modal h3 {
        margin-top: 0;
        text-align: center;
        color: #007bff;
    }
    .modal input, .modal select {
        width: 100%;
        padding: 10px;
        margin: 10px 0 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }
    .close {
        position: absolute;
        top: 10px; right: 15px;
        font-size: 20px;
        color: #333;
        cursor: pointer;
    }
    .msg {
        text-align: center;
        font-weight: bold;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .success { background: #e6ffee; color: #1a7f2b; border: 1px solid #b2e2c4; }
    .error   { background: #ffe6e6; color: #b30000; border: 1px solid #f5b2b2; }
  </style>
</head>
<body>

<h2>🧑‍💻 Admin Panel: User Management</h2>

<?php if ($success) echo "<p class='msg success'>$success</p>"; ?>
<?php if ($error) echo "<p class='msg error'>$error</p>"; ?>

<button class="btn" onclick="document.getElementById('registerModal').style.display='flex'">➕ Add New User</button>

<!-- Modal Form -->
<div class="modal" id="registerModal">
  <div class="modal-content">
    <span class="close" onclick="document.getElementById('registerModal').style.display='none'">&times;</span>
    <h3>Register New User</h3>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password (min 8 chars)" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <select name="role" required>
            <option value="">-- Select Role --</option>
            <option value="Admin">Admin</option>
            <option value="User">User</option>
        </select>
        <input type="submit" name="register_user" value="Register" class="btn" style="width:100%;">
    </form>
  </div>
</div>

<!-- User Table -->
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
      <td><button type="submit" name="update_password">Update</button></td>
    </form>
  </tr>
  <?php endwhile; ?>
</table>

<script>
  // Close modal when clicking outside
  window.onclick = function(event) {
    var modal = document.getElementById('registerModal');
    if (event.target == modal) {
      modal.style.display = "none";
    }
  }
</script>

</body>
</html>
