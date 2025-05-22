<?php
require_once 'config.php';

// Initialize variables
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Process login form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Check input errors before querying the database
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT user_id, username, password_hash FROM users WHERE username = :username";
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = $username;
            
            // Execute the statement
            $stmt->execute();
            
            // Check if username exists
            if ($stmt->rowCount() == 1) {
                if ($row = $stmt->fetch()) {
                    $id = $row["user_id"];
                    $username = $row["username"];
                    $hashed_password = $row["password_hash"];
                    
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["username"] = $username;
                        
                        // Update last login time
                        $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bindParam(":user_id", $id, PDO::PARAM_INT);
                        $update_stmt->execute();
                        
                        // Redirect to dashboard
                        header("location: index.php");
                        exit;
                    } else {
                        // Password is not valid
                        $login_err = "Invalid username or password.";
                    }
                }
            } else {
                // Username doesn't exist
                $login_err = "Invalid username or password.";
            }
        } catch(PDOException $e) {
            $login_err = "Oops! Something went wrong. Please try again later. " . $e->getMessage();
        }
        
        // Close statement
        unset($stmt);
    }
    
    // Close connection
    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Todo List Application</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Login Page -->
    <div id="login-page">
        <header>
            <div class="navbar container">
                <h1>Task Manager</h1>
                <ul class="nav-menu">
                    <li><a href="login.php" class="active">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </div>
        </header>
        
        <main class="container">
            <div class="login-container">
                <h2>Login to Your Account</h2>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="login-username">Username</label>
                        <input type="text" name="username" id="login-username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" name="password" id="login-password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <button type="submit" class="btn btn-block">Login</button>
                </form>
                <p style="margin-top: 1rem; text-align: center;">
                    Don't have an account? <a href="register.php">Register now</a>
                </p>
            </div>
        </main>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; 2025 Task Manager Application. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>