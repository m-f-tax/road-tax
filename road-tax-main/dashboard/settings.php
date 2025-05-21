<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo "<div style='padding:20px;color:red;font-family:sans-serif;'>Access denied. Admin only.</div>";
    exit;
}

$conn = new mysqli("localhost", "root", "", "roadtaxsystem");

$pages = [
    'Dashboard' => ['roadtaxsystem/dashboard/dashboard.php'],
    'Vehicle Management' => ['roadtaxsystem/dashboard/form'],
    'Payments' => [
        'roadtaxsystem/dashboard/payment_recording',
        'roadtaxsystem/reciept/reciept_payment',
        'roadtaxsystem/generate/generate_payment'
    ],
    'Reports' => [
        'roadtaxsystem/dashboard/reports',
        'roadtaxsystem/reciept/reciept_report',
        'roadtaxsystem/generate/generate_report',
        'roadtaxsystem/dashboard/Vehiclestatement'
    ],
    'User Report' => [
        'roadtaxsystem/users/form/form_user_report'
    ],
    'User Form' => [
        'roadtaxsystem/users/form/form'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pages'])) {
    $user_id = $_POST['user_id'];
    $selected_pages = $_POST['pages'];

    foreach ($selected_pages as $page) {
        $check = $conn->prepare("SELECT id FROM tbl_user_pages WHERE user_id = ? AND page_name = ?");
        $check->bind_param("is", $user_id, $page);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO tbl_user_pages (user_id, page_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $page);
            $stmt->execute();
        }
    }

    $message = "Pages assigned successfully.";
}

if (isset($_GET['delete']) && isset($_GET['uid'])) {
    $pageToDelete = $_GET['delete'];
    $uid = $_GET['uid'];
    $del = $conn->prepare("DELETE FROM tbl_user_pages WHERE user_id = ? AND page_name = ?");
    $del->bind_param("is", $uid, $pageToDelete);
    $del->execute();
    $message = "Page deleted successfully.";
}

$users = $conn->query("SELECT id, username FROM users WHERE role = 'User'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
  <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #e3f2fd, #f0f4ff);
        padding: 40px;
        margin: 0;
    }
    .back-link {
        margin-bottom: 25px;
        display: inline-block;
        background: #007bff;
        color: white;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        transition: background 0.3s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .back-link:hover {
        background: #0056b3;
    }
    .container {
        max-width: 980px;
        margin: auto;
        background: white;
        padding: 35px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    h2 {
        text-align: center;
        color: #007bff;
        margin-bottom: 25px;
        font-size: 26px;
    }
    .form-group {
        margin-bottom: 25px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 8px;
        color: #333;
    }
    select {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 16px;
    }
    .section-title {
        font-size: 17px;
        margin: 20px 0 10px;
        color: #007bff;
        border-left: 4px solid #007bff;
        padding-left: 10px;
    }
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
        margin-bottom: 15px;
    }
    .checkbox-group label {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        transition: background 0.3s;
        font-size: 15px;
    }
    .checkbox-group label:hover {
        background: #e1ecf8;
    }
    .checkbox-group input[type="checkbox"] {
        margin-right: 10px;
        transform: scale(1.1);
    }
    button {
        padding: 12px 28px;
        background: #007bff;
        border: none;
        color: white;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    button:hover {
        background: #0056b3;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 35px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    th, td {
        border: 1px solid #dee2e6;
        padding: 12px;
        font-size: 15px;
    }
    th {
        background-color: #007bff;
        color: white;
        text-align: left;
    }
    tr:hover td {
        background-color: #f8f9fa;
    }
    .delete-btn {
        color: red;
        font-weight: bold;
        text-decoration: none;
    }
    .delete-btn:hover {
        text-decoration: underline;
    }
    .message {
        color: green;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
        background: #e0ffe0;
        padding: 10px;
        border-radius: 6px;
    }
</style>
</head>
<body>

    <div class="container">
        <h2>Assign Pages to Users</h2>
        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

        <!-- Add Pages Form -->
        <form method="POST">
            <div class="form-group">
                <label for="user_id">Select User:</label>
                <select name="user_id" required onchange="this.form.submit()">
                    <option value="">-- Select User --</option>
                    <?php 
                    $selected_user = $_POST['user_id'] ?? $_GET['uid'] ?? "";
                    while ($user = $users->fetch_assoc()): 
                        $sel = ($user['id'] == $selected_user) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $sel; ?>>
                            <?php echo $user['username']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php if (!empty($selected_user)): ?>
            <div class="form-group">
                <label>Select Pages:</label>
                <?php foreach ($pages as $section => $pageList): ?>
                    <div class="section-title"><?php echo $section; ?></div>
                    <div class="checkbox-group">
                        <?php foreach ($pageList as $page): ?>
                            <label>
                                <input type="checkbox" name="pages[]" value="<?php echo $page; ?>">
                                <?php echo ucfirst(str_replace('.php', '', basename($page))); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit">Add Selected Pages</button>
            <?php endif; ?>
        </form>

        <!-- View & Delete Assigned Pages -->
        <?php if (!empty($selected_user)): 
            $assigned = $conn->query("SELECT * FROM tbl_user_pages WHERE user_id = $selected_user");
        ?>
        <h3 style="margin-top: 40px;">Assigned Pages:</h3>
        <table>
            <tr><th>Page Name</th><th>Action</th></tr>
            <?php while ($row = $assigned->fetch_assoc()): ?>
                <tr>
                    <td><?php echo ucfirst(str_replace('.php', '', basename($row['page_name']))); ?></td>
                    <td>
                        <a class="delete-btn" href="?delete=<?php echo urlencode($row['page_name']); ?>&uid=<?php echo $selected_user; ?>" onclick="return confirm('Are you sure you want to delete this page?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>
</body>
</html>
