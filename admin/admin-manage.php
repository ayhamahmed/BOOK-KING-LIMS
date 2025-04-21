<?php
// Start the session
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session information
error_log("Session data: " . print_r($_SESSION, true));

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Admin not logged in. Redirecting to login page.");
    header('Location: admin-login.php');
    exit();
}

// Include the database connection
try {
    $pdo = require '../database/db_connection.php';
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new admin
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO admin (username, password, FirstName, LastName, email, Status)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['username'],
                        $_POST['password'],  // Store password directly without hashing
                        $_POST['firstname'],
                        $_POST['lastname'],
                        $_POST['email'],
                        $_POST['status']
                    ]);
                    $success_message = "Admin added successfully!";
                } catch (PDOException $e) {
                    // Check for duplicate username
                    if ($e->getCode() == 23000) {
                        $error_message = "Username already exists. Please choose a different username.";
                    } else {
                        $error_message = "Error adding admin: " . $e->getMessage();
                    }
                    error_log("Error adding admin: " . $e->getMessage());
                }
                break;

            case 'edit':
                // Edit existing admin
                try {
                    if (!empty($_POST['password'])) {
                        // If password is provided, update with plain password (no hashing)
                        $stmt = $pdo->prepare("
                            UPDATE admin 
                            SET username = ?, 
                                password = ?,
                                FirstName = ?,
                                LastName = ?,
                                email = ?,
                                Status = ?
                            WHERE admin_id = ?
                        ");
                        $params = [
                            $_POST['username'],
                            $_POST['password'],  // Store password directly without hashing
                            $_POST['firstname'],
                            $_POST['lastname'],
                            $_POST['email'],
                            $_POST['status'],
                            $_POST['admin_id']
                        ];
                    } else {
                        // If no password provided, update without changing password
                        $stmt = $pdo->prepare("
                            UPDATE admin 
                            SET username = ?, 
                                FirstName = ?,
                                LastName = ?,
                                email = ?,
                                Status = ?
                            WHERE admin_id = ?
                        ");
                        $params = [
                            $_POST['username'],
                            $_POST['firstname'],
                            $_POST['lastname'],
                            $_POST['email'],
                            $_POST['status'],
                            $_POST['admin_id']
                        ];
                    }
                    $stmt->execute($params);
                    $success_message = "Admin updated successfully!";
                } catch (PDOException $e) {
                    // Check for duplicate username
                    if ($e->getCode() == 23000) {
                        $error_message = "Username already exists. Please choose a different username.";
                    } else {
                        $error_message = "Error updating admin: " . $e->getMessage();
                    }
                    error_log("Error updating admin: " . $e->getMessage());
                }
                break;

            case 'delete':
                // Delete admin
                try {
                    $stmt = $pdo->prepare("DELETE FROM admin WHERE admin_id = ?");
                    $stmt->execute([$_POST['admin_id']]);
                    $success_message = "Admin deleted successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error deleting admin: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all admins
$admins = $pdo->query("SELECT * FROM admin ORDER BY username")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - Book King</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: #F8F8F8;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #B07154;
            margin: 30px 0;
            font-size: 32px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: #B07154;
            border-radius: 2px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .success {
            background: #F4DECB;
            color: #B07154;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            border: 1px solid rgba(176, 113, 84, 0.2);
            box-shadow: 0 2px 4px rgba(176, 113, 84, 0.05);
        }

        .error {
            background: #FFE8E6;
            color: #D8000C;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #B07154;
            color: white;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(176, 113, 84, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: #95604A;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(176, 113, 84, 0.25);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #FDE8E8;
            color: #9B1C1C;
            border: 1px solid #F8B4B4;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #FBD5D5;
            color: #9B1C1C;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(155, 28, 28, 0.1);
        }

        .btn-danger:active {
            transform: translateY(0);
            box-shadow: none;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 25px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        th {
            background: #F4DECB;
            color: #B07154;
            font-weight: 600;
            padding: 16px 20px;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #F4DECB;
            color: #4B5563;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #FDF8F6;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 20px;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #B07154;
        }

        .header-actions {
            margin-bottom: 30px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: #F4DECB;
            color: #B07154;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(176, 113, 84, 0.1);
        }

        .back-btn:hover {
            background: #E4C4A9;
            transform: translateX(-4px);
            box-shadow: 0 4px 6px rgba(176, 113, 84, 0.15);
        }

        .back-btn svg {
            width: 22px;
            height: 22px;
            transition: transform 0.3s ease;
        }

        .back-btn:hover svg {
            transform: translateX(-4px);
        }

        /* Delete Confirmation Modal */
        #deleteConfirmModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .delete-modal-content {
            background: white;
            width: 90%;
            max-width: 400px;
            margin: 120px auto;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            animation: slideIn 0.3s ease;
            box-shadow: 0 20px 25px -5px rgba(176, 113, 84, 0.1), 0 10px 10px -5px rgba(176, 113, 84, 0.04);
        }

        .delete-warning-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 24px;
            color: #DC2626;
            animation: pulseWarning 1s ease-in-out;
        }

        .delete-modal-title {
            color: #B07154;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .delete-modal-message {
            color: #6B7280;
            margin-bottom: 40px;
            line-height: 1.6;
            font-size: 16px;
            padding: 0 20px;
        }

        .delete-modal-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn-cancel {
            background: #F4DECB;
            color: #B07154;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 140px;
        }

        .btn-cancel:hover {
            background: #E4C4A9;
            transform: translateY(-2px);
        }

        .btn-confirm-delete {
            background: #B07154;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 140px;
        }

        .btn-confirm-delete:hover {
            background: #95604A;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(176, 113, 84, 0.2);
        }

        @media (max-width: 640px) {
            .delete-modal-content {
                width: 95%;
                margin: 60px auto;
                padding: 30px 20px;
            }

            .delete-modal-actions {
                flex-direction: column;
                gap: 16px;
            }

            .btn-cancel,
            .btn-confirm-delete {
                width: 100%;
                padding: 16px;
            }
        }

        /* Add these new styles */
        .edit-form {
            padding: 20px 0;
        }

        .edit-form .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .edit-form label {
            color: #333;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .edit-form input,
        .edit-form select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #F9FAFB;
        }

        .edit-form input:focus,
        .edit-form select:focus {
            border-color: #B07154;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(176, 113, 84, 0.1);
        }

        .edit-form input::placeholder {
            color: #9CA3AF;
        }

        .edit-form .password-field {
            position: relative;
        }

        .edit-form .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .edit-form .password-toggle:hover {
            color: #B07154;
        }

        .edit-form .status-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
            padding-right: 48px;
        }

        .edit-form .btn-update {
            width: 100%;
            padding: 14px;
            background: #B07154;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .edit-form .btn-update:hover {
            background: #95604A;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(176, 113, 84, 0.1);
        }

        .edit-form .btn-update:active {
            transform: translateY(0);
        }

        .modal-content h2 {
            color: #1F2937;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #F4DECB;
            color: #B07154;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background: #E4C4A9;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #FDE8E8;
            color: #9B1C1C;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #FBD5D5;
            transform: translateY(-1px);
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.active {
            background: #DFF2BF;
            color: #4F8A10;
        }

        .status-badge.deactivate {
            background: #FFE8E6;
            color: #D8000C;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <a href="../admin/admin-dashboard.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
        <h1>Manage Administrators</h1>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <button class="btn btn-primary" onclick="showAddModal()">Add New Admin</button>

        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['FirstName'] . ' ' . $admin['LastName']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower($admin['Status']); ?>">
                                <?php echo htmlspecialchars($admin['Status']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <button class="btn-edit" onclick="showEditModal(<?php echo htmlspecialchars(json_encode($admin)); ?>)">Edit</button>
                            <button class="btn-delete" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Admin Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2>Add New Admin</h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('password')"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" placeholder="Enter first name" required>
                </div>
                
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" placeholder="Enter last name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required class="status-select">
                        <option value="active" class="status-option">Active</option>
                        <option value="deactivate" class="status-option">Deactivate</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-update">Add Admin</button>
            </form>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit Admin</h2>
            <form method="POST" class="edit-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" value="test" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_password">Password (leave blank to keep current)</label>
                    <div class="password-field">
                        <input type="password" id="edit_password" name="password">
                        <i class="fas fa-eye password-toggle" onclick="togglePasswordVisibility('edit_password')"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_firstname">First Name</label>
                    <input type="text" id="edit_firstname" name="firstname" value="test" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_lastname">Last Name</label>
                    <input type="text" id="edit_lastname" name="lastname" value="test" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" value="test@gmail.com" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required class="status-select">
                        <option value="active" class="status-option">Active</option>
                        <option value="deactivate" class="status-option">Deactivate</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-update">Update Admin</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="delete-modal-content">
            <h3 class="delete-modal-title">Delete Administrator</h3>
            <p class="delete-modal-message">Are you sure you want to delete this administrator? This action cannot be undone.</p>
            <div class="delete-modal-actions">
                <button class="btn-cancel" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <button class="btn-confirm-delete" onclick="confirmDelete()">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function showEditModal(admin) {
            document.getElementById('edit_admin_id').value = admin.admin_id;
            document.getElementById('edit_username').value = admin.username;
            document.getElementById('edit_firstname').value = admin.FirstName;
            document.getElementById('edit_lastname').value = admin.LastName;
            document.getElementById('edit_email').value = admin.email;
            document.getElementById('edit_status').value = admin.Status;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        let adminToDelete = null;

        function deleteAdmin(adminId) {
            adminToDelete = adminId;
            document.getElementById('deleteConfirmModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
            adminToDelete = null;
        }

        function confirmDelete() {
            if (!adminToDelete) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="admin_id" value="${adminToDelete}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.id === 'deleteConfirmModal') {
                closeDeleteModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
            }
        });

        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 