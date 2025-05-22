<?php
require_once 'config.php';
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Todo List Application</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Dashboard -->
    <div id="dashboard-page">
        <header>
            <div class="navbar container">
                <h1>Task Manager</h1>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </div>
        </header>
        
        <main class="container">
            <div class="task-stats">
                <div class="stat-card stat-total">
                    <h3 id="total-tasks">0</h3>
                    <p>Total Tasks</p>
                </div>
                <div class="stat-card stat-completed">
                    <h3 id="completed-tasks">0</h3>
                    <p>Completed</p>
                </div>
                <div class="stat-card stat-pending">
                    <h3 id="pending-tasks">0</h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card stat-overdue">
                    <h3 id="overdue-tasks">0</h3>
                    <p>Overdue</p>
                </div>
            </div>
            
            <div class="dashboard">
                <div class="sidebar">
                    <h3>Categories</h3>
                    <ul class="category-list" id="category-list">
                        <li><a href="#" class="active" data-category="all">All Tasks</a></li>
                        <li><a href="#" data-category="today">Today</a></li>
                        <li><a href="#" data-category="upcoming">Upcoming</a></li>
                        <li><a href="#" data-category="overdue">Overdue</a></li>
                        <li><a href="#" data-category="completed">Completed</a></li>
                    </ul>
                    
                    <h3>My Categories</h3>
                    <ul class="category-list" id="user-categories">
                        <!-- User categories will be loaded here -->
                    </ul>
                    
                    <button class="btn btn-block" id="add-category-btn">Add Category</button>
                </div>
                
                <div class="task-panel">
                    <div class="panel-header">
                        <h2 id="current-category-title">All Tasks</h2>
                        <div class="filter-options">
                            <div class="search-box">
                                <input type="text" id="search-input" placeholder="Search tasks...">
                                <button id="search-btn"><i class="fas fa-search"></i></button>
                            </div>
                            <select id="priority-filter" class="form-control">
                                <option value="">All Priorities</option>
                                <option value="1">Low</option>
                                <option value="2">Medium</option>
                                <option value="3">High</option>
                                <option value="4">Urgent</option>
                            </select>
                            <button class="btn" id="add-task-btn">New Task</button>
                        </div>
                    </div>
                    
                    <ul class="task-list" id="task-list">
                        <!-- Tasks will be loaded here -->
                    </ul>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Task Modal -->
    <div class="modal" id="task-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="task-modal-title">Add New Task</h2>
                <button class="modal-close" id="close-task-modal">&times;</button>
            </div>
            <form id="task-form">
                <input type="hidden" id="task-id">
                <div class="form-group">
                    <label for="task-title">Title</label>
                    <input type="text" id="task-title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="task-description">Description</label>
                    <textarea id="task-description" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="task-due-date">Due Date</label>
                    <input type="date" id="task-due-date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="task-priority">Priority</label>
                    <select id="task-priority" class="form-control">
                        <option value="1">Low</option>
                        <option value="2">Medium</option>
                        <option value="3">High</option>
                        <option value="4">Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="task-categories">Categories</label>
                    <select id="task-categories" class="form-control select-multiple" multiple>
                        <!-- Categories will be loaded here -->
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" id="cancel-task-btn">Cancel</button>
                    <button type="submit" class="btn">Save Task</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal" id="category-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="category-modal-title">Add New Category</h2>
                <button class="modal-close" id="close-category-modal">&times;</button>
            </div>
            <form id="category-form">
                <input type="hidden" id="category-id">
                <div class="form-group">
                    <label for="category-name">Name</label>
                    <input type="text" id="category-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="category-color">Color</label>
                    <input type="color" id="category-color" class="form-control" value="#4a6fa5">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background-color: #6c757d;" id="cancel-category-btn">Cancel</button>
                    <button type="submit" class="btn">Save Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>&copy; 2025 Task Manager Application. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="js/script.js"></script>
</body>
</html>