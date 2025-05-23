<?php
require_once 'config.php';
requireLogin();

// Create database connection
$conn = getDbConnection();

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Remove the direct stats query - we'll fetch via JavaScript instead
// $statsStmt = $conn->prepare("...");

// Handle form submission (keep existing form handling code)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email already exists for another user
            $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $checkStmt->execute([$email, $_SESSION['user_id']]);
            
            if ($checkStmt->fetch()) {
                $error = "Email already exists";
            } else {
                $updateFields = ["email = ?"];
                $updateParams = [$email];
                
                // Handle password change
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error = "Current password is required to change password";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "New passwords don't match";
                    } elseif (strlen($new_password) < 6) {
                        $error = "New password must be at least 6 characters";
                    } else {
                        // Verify current password using password_hash column
                        if (password_verify($current_password, $user['password_hash'])) {
                            $updateFields[] = "password_hash = ?";
                            $updateParams[] = password_hash($new_password, PASSWORD_DEFAULT);
                        } else {
                            $error = "Current password is incorrect";
                        }
                    }
                }
                
                if (!isset($error)) {
                    // Update user data
                    $updateParams[] = $_SESSION['user_id'];
                    $updateStmt = $conn->prepare("UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?");
                    $updateStmt->execute($updateParams);
                    
                    $success = "Profile updated successfully";
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Todo App</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .total { color: #3498db; }
        .completed { color: #27ae60; }
        .pending { color: #f39c12; }
        .overdue { color: #e74c3c; }
        
        .profile-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .password-toggle input[type="checkbox"] {
            width: auto;
        }
        
        .member-since {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Loading state for stats */
        .stat-loading {
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar container">
            <h1>Task Manager</h1>
            <ul class="nav-menu">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>
    
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['username'], 0, 2)) ?>
            </div>
            <h1><?= htmlspecialchars($user['username']) ?></h1>
            <p class="member-since">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card stat-loading">
                <div class="stat-number total" id="total-tasks">Loading...</div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card stat-loading">
                <div class="stat-number completed" id="completed-tasks">Loading...</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card stat-loading">
                <div class="stat-number pending" id="pending-tasks">Loading...</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card stat-loading">
                <div class="stat-number overdue" id="overdue-tasks">Loading...</div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="profile-form">
            <form method="POST">
                <div class="form-section">
                    <h3>Account Information</h3>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Change Password</h3>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" id="current_password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" id="new_password" minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" minlength="6">
                    </div>
                    <div class="password-toggle">
                        <input type="checkbox" id="show_passwords" onchange="togglePasswordVisibility()">
                        <label for="show_passwords">Show passwords</label>
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>
        </div>
    </div>
    
    <script>
        // Load stats using the same API as dashboard
        async function loadStats() {
            try {
                const response = await fetch('api/stats.php');
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.data;
                    
                    // Update the stat numbers
                    document.getElementById('total-tasks').textContent = stats.total;
                    document.getElementById('completed-tasks').textContent = stats.completed;
                    document.getElementById('pending-tasks').textContent = stats.pending;
                    document.getElementById('overdue-tasks').textContent = stats.overdue;
                    
                    // Remove loading state
                    document.querySelectorAll('.stat-card').forEach(card => {
                        card.classList.remove('stat-loading');
                    });
                } else {
                    throw new Error(result.error || 'Failed to load stats');
                }
            } catch (error) {
                console.error('Error loading stats:', error);
                
                // Show error state
                document.querySelectorAll('.stat-number').forEach(el => {
                    el.textContent = 'Error';
                });
                
                document.querySelectorAll('.stat-card').forEach(card => {
                    card.classList.remove('stat-loading');
                });
            }
        }
        
        function togglePasswordVisibility() {
            const checkbox = document.getElementById('show_passwords');
            const passwordFields = ['current_password', 'new_password', 'confirm_password'];
            
            passwordFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                field.type = checkbox.checked ? 'text' : 'password';
            });
        }
        
        // Add some interactivity to stats cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-5px)';
                }, 150);
            });
        });
        
        // Load stats when page loads
        document.addEventListener('DOMContentLoaded', loadStats);
    </script>
</body>
</html>