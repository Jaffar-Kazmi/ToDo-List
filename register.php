<?php
require_once 'config.php';

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT user_id FROM users WHERE username = :username";
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            $param_username = trim($_POST["username"]);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $username_err = "This username is already taken.";
            } else {
                $username = trim($_POST["username"]);
            }
        } catch(PDOException $e) {
            die("Error: " . $e->getMessage());
        }
        unset($stmt);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $sql = "SELECT user_id FROM users WHERE email = :email";
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            $param_email = trim($_POST["email"]);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $email_err = "This email is already registered.";
            } else {
                $email = trim($_POST["email"]);
            }
        } catch(PDOException $e) {
            die("Error: " . $e->getMessage());
        }
        unset($stmt);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check errors before inserting
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
        
        try {
            $conn = getDbConnection();
            $stmt = $conn->prepare($sql);
            
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":password_hash", $param_password, PDO::PARAM_STR);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($stmt->execute()) {
                header("location: login.php");
                exit();
            }
        } catch(PDOException $e) {
            die("Error: " . $e->getMessage());
        }
        unset($stmt);
    }
    unset($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Todo List Application</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Register Page -->
    <div id="register-page">
        <header>
            <div class="navbar container">
                <h1>Task Manager</h1>
                <ul class="nav-menu">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="active">Register</a></li>
                </ul>
            </div>
        </header>
        
        <main class="container">
            <div class="register-container">
                <h2>Create New Account</h2>
                
                <?php 
                if(!empty($register_err)){
                    echo '<div class="alert alert-danger">' . $register_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="register-username">Username</label>
                        <input type="text" name="username" id="register-username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email</label>
                        <input type="email" name="email" id="register-email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" name="password" id="register-password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <label for="register-confirm-password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="register-confirm-password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                    </div>
                    <button type="submit" class="btn btn-block">Register</button>
                </form>
                <p style="margin-top: 1rem; text-align: center;">
                    Already have an account? <a href="login.php">Login now</a>
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