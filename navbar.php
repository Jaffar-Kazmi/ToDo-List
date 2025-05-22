<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">Todo App</a>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="index.php" class="nav-link">Tasks</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="logout.php" class="nav-link">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>