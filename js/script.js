// ============================
// DOM References
// ============================
const loginPage = document.getElementById("login-page");
const registerPage = document.getElementById("register-page");
const dashboardPage = document.getElementById("dashboard-page");

const addTaskBtn = document.getElementById("add-task-btn");
const taskModal = document.getElementById("task-modal");
const closeTaskModalBtn = document.getElementById("close-task-modal");
const cancelTaskBtn = document.getElementById("cancel-task-btn");
const taskForm = document.getElementById("task-form");

const addCategoryBtn = document.getElementById("add-category-btn");
const categoryModal = document.getElementById("category-modal");
const categoryForm = document.getElementById("category-form");

let currentUser = null;

// ============================
// Helper Functions
// ============================
function escapeHtml(unsafe) {
  return (
    unsafe
      ?.toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;") || ""
  );
}

// ============================
// API Calls
// ============================

// TASKS
async function fetchTasks() {
  try {
    console.log("Fetching tasks from API...");
    const response = await fetch("api/tasks.php");
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log("API response:", data);
    
    if (data.success) {
      return data.data || [];
    } else {
      console.error("Error fetching tasks:", data.error);
      return [];
    }
  } catch (error) {
    console.error("Error fetching tasks:", error);
    return [];
  }
}

async function saveTask(taskData) {
  try {
    console.log("Saving task:", taskData);
    
    const response = await fetch("api/tasks.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(taskData),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const textResponse = await response.text();
    console.log("Raw response:", textResponse);
    
    try {
      const data = JSON.parse(textResponse);
      console.log("Parsed response:", data);
      return data;
    } catch (e) {
      console.error("Invalid JSON response:", textResponse);
      return { success: false, error: "Invalid server response" };
    }
  } catch (error) {
    console.error("Network error:", error);
    return { success: false, error: "Network error: " + error.message };
  }
}

async function deleteTask(taskId) {
  try {
    const response = await fetch("api/tasks.php", {
      method: "DELETE",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `task_id=${taskId}`,
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error("Error:", error);
    return { success: false, error: error.message };
  }
}

async function toggleTaskComplete(taskId) {
  try {
    // First, fetch the current task to get its current status
    const tasks = await fetchTasks();
    const task = tasks.find(t => t.task_id == taskId);
    
    if (!task) {
      throw new Error('Task not found');
    }
    
    // Toggle the completed status
    const updatedTask = {
      task_id: taskId,
      title: task.title,
      description: task.description,
      due_date: task.due_date,
      priority_id: task.priority_id,
      completed: task.completed ? 0 : 1, // Toggle completion
      categories: task.categories ? task.categories.map(cat => cat.category_id) : []
    };
    
    const result = await saveTask(updatedTask);
    
    if (result.success) {
      loadTasks();
      updateTaskStatistics();
    } else {
      alert("Error updating task: " + result.error);
    }
  } catch (error) {
    console.error("Error toggling task completion:", error);
    alert("Error updating task");
  }
}


function renderTasks(tasks) {
  const taskListElement = document.getElementById("task-list");
  if (!taskListElement) {
    console.error("Task list element not found");
    return;
  }

  console.log("Rendering tasks:", tasks);
  taskListElement.innerHTML = "";

  if (!tasks || tasks.length === 0) {
    taskListElement.innerHTML = '<li class="no-tasks">No tasks found</li>';
    return;
  }

  tasks.forEach((task) => {
    const taskElement = document.createElement("li");
    taskElement.className = `task-item ${task.completed ? "completed" : ""}`;
    taskElement.innerHTML = `
            <div class="task-content">
                <h3 class="task-title">${escapeHtml(task.title)}</h3>
                ${
                  task.description
                    ? `<p>${escapeHtml(task.description)}</p>`
                    : ""
                }
                <div class="task-meta">
                    ${
                      task.due_date
                        ? `<span><i class="far fa-calendar-alt"></i> ${task.due_date}</span>`
                        : ""
                    }
                    <span class="priority-badge priority-${task.priority_name ? task.priority_name.toLowerCase() : 'medium'}">${
      task.priority_name || 'Medium'
    }</span>
                </div>
                ${renderTaskCategories(task.categories)}
            </div>
            <div class="task-actions">
                <button class="btn-complete" data-task-id="${task.task_id}">
                    <i class="fas fa-${task.completed ? "undo" : "check"}"></i>
                </button>
                <button class="btn-edit" data-task-id="${task.task_id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-delete" data-task-id="${task.task_id}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

    taskListElement.appendChild(taskElement);
  });

  // Event listeners for action buttons
  document.querySelectorAll(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const taskId = e.currentTarget.getAttribute("data-task-id");
      openTaskModal(taskId);
    });
  });

  document.querySelectorAll(".btn-delete").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      const taskId = e.currentTarget.getAttribute("data-task-id");
      if (confirm("Are you sure you want to delete this task?")) {
        const result = await deleteTask(taskId);
        if (result.success) {
          loadTasks();
          updateTaskStatistics();
        } else {
          alert("Error deleting task: " + result.error);
        }
      }
    });
  });

  // Fixed complete button event listener
  document.querySelectorAll(".btn-complete").forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      const taskId = e.currentTarget.getAttribute("data-task-id");
      await toggleTaskComplete(taskId);
    });
  });
}

// Categories
async function fetchCategories() {
  try {
    console.log("Fetching categories from API...");
    const response = await fetch("api/categories.php");
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log("Categories API response:", data);
    
    if (data.success) {
      return data.data || [];
    } else {
      console.error("Error fetching categories:", data.error);
      return [];
    }
  } catch (error) {
    console.error("Error fetching categories:", error);
    return [];
  }
}

async function saveCategory(categoryData) {
  try {
    const response = await fetch("api/categories.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(categoryData),
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error("Error:", error);
    return { success: false, error: error.message };
  }
}

async function deleteCategory(categoryId) {
  try {
    const response = await fetch("api/categories.php", {
      method: "DELETE",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `category_id=${categoryId}`,
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error("Error:", error);
    return { success: false, error: error.message };
  }
}

// Stats
async function fetchStats() {
  try {
    console.log("Fetching stats from API...");
    const response = await fetch("api/stats.php");
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log("Stats API response:", data);
    
    if (data.success) {
      return data.data;
    } else {
      console.error("Error fetching stats:", data.error);
      return {
        total: 0,
        completed: 0,
        pending: 0,
        overdue: 0
      };
    }
  } catch (error) {
    console.error("Error fetching stats:", error);
    return {
      total: 0,
      completed: 0,
      pending: 0,
      overdue: 0
    };
  }
}

// ============================
// UI Utility Functions
// ============================
function renderTaskCategories(categories) {
  if (!categories || categories.length === 0) return "";
  return `
        <div class="task-categories">
            ${categories
              .map(
                (cat) => `
                <span class="category-tag" style="background-color: ${
                  cat.color || '#4a6fa5'
                }">
                    ${escapeHtml(cat.name)}
                </span>
            `
              )
              .join("")}
        </div>
    `;
}

// Task Modal Functions
function openTaskModal(taskId = null) {
  console.log("Opening task modal for taskId:", taskId);

  const modalTitle = document.getElementById("task-modal-title");

  // Reset form and set title
  taskForm.reset();
  modalTitle.textContent = taskId ? "Edit Task" : "Add New Task";

  // Clear task ID field
  document.getElementById("task-id").value = taskId || "";

  // If editing, load task data
  if (taskId) {
    loadTaskData(taskId);
  }

  taskModal.classList.add("active");
}

function closeTaskModal() {
  document.getElementById("task-modal").classList.remove("active");
}

async function loadTaskData(taskId) {
  try {
    const tasks = await fetchTasks();
    const task = tasks.find((t) => t.task_id == taskId);

    if (task) {
      document.getElementById("task-id").value = task.task_id;
      document.getElementById("task-title").value = task.title;
      document.getElementById("task-description").value = task.description || "";
      document.getElementById("task-due-date").value = task.due_date || "";
      document.getElementById("task-priority").value = task.priority_id || "2";

      // Select categories
      const categorySelect = document.getElementById("task-categories");
      if (task.categories && categorySelect) {
        Array.from(categorySelect.options).forEach((option) => {
          option.selected = task.categories.some(
            (cat) => cat.category_id == option.value
          );
        });
      }
    }
  } catch (error) {
    console.error("Error loading task data:", error);
  }
}

function openCategoryModal() {
  const modal = document.getElementById("category-modal");
  const form = document.getElementById("category-form");
  
  if (form) {
    form.reset();
  }
  
  if (modal) {
    modal.classList.add("active");
  }
}

function closeCategoryModal() {
  const modal = document.getElementById("category-modal");
  if (modal) {
    modal.classList.remove("active");
  }
}

function showLogin() {
  loginPage.style.display = "block";
  registerPage.style.display = "none";
  dashboardPage.style.display = "none";
}

function showDashboard() {
  loginPage.style.display = "none";
  registerPage.style.display = "none";
  dashboardPage.style.display = "block";
}

// ============================
// Loaders
// ============================
async function loadTasks() {
  try {
    console.log("Loading tasks...");
    const tasks = await fetchTasks();
    console.log("Fetched tasks:", tasks);
    
    if (tasks) {
      renderTasks(tasks);
    } else {
      const taskListElement = document.getElementById("task-list");
      if (taskListElement) {
        taskListElement.innerHTML = '<li class="no-tasks">Error loading tasks</li>';
      }
    }
  } catch (error) {
    console.error("Error loading tasks:", error);
    const taskListElement = document.getElementById("task-list");
    if (taskListElement) {
      taskListElement.innerHTML = '<li class="no-tasks">Error loading tasks</li>';
    }
  }
}

async function loadCategories() {
  try {
    console.log("Loading categories...");
    const categories = await fetchCategories();
    console.log("Fetched categories:", categories);
    
    // Update category dropdown in task modal
    const categorySelect = document.getElementById("task-categories");
    if (categorySelect && categories) {
      categorySelect.innerHTML = '';
      categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.category_id;
        option.textContent = category.name;
        categorySelect.appendChild(option);
      });
    }
    
    // Update sidebar categories
    const userCategoriesElement = document.getElementById("user-categories");
    if (userCategoriesElement && categories) {
      userCategoriesElement.innerHTML = '';
      categories.forEach(category => {
        const li = document.createElement('li');
        li.innerHTML = `
          <a href="#" data-category="${category.category_id}">
            <span class="category-color" style="background-color: ${category.color || '#4a6fa5'}"></span>
            ${escapeHtml(category.name)}
          </a>
        `;
        userCategoriesElement.appendChild(li);
      });
    }
  } catch (error) {
    console.error("Error loading categories:", error);
  }
}

async function updateTaskStatistics() {
  try {
    console.log("Updating task statistics...");
    const stats = await fetchStats();
    console.log("Fetched stats:", stats);
    
    const totalElement = document.getElementById("total-tasks");
    const completedElement = document.getElementById("completed-tasks");
    const pendingElement = document.getElementById("pending-tasks");
    const overdueElement = document.getElementById("overdue-tasks");
    
    if (totalElement) totalElement.textContent = stats.total || 0;
    if (completedElement) completedElement.textContent = stats.completed || 0;
    if (pendingElement) pendingElement.textContent = stats.pending || 0;
    if (overdueElement) overdueElement.textContent = stats.overdue || 0;
  } catch (error) {
    console.error("Error updating statistics:", error);
  }
}

// ============================
// Event Listeners
// ============================
// Add Task Button
document
  .getElementById("add-task-btn")
  ?.addEventListener("click", () => openTaskModal());
// Close Modal Buttons
document
  .getElementById("close-task-modal")
  ?.addEventListener("click", closeTaskModal);
document
  .getElementById("cancel-task-btn")
  ?.addEventListener("click", closeTaskModal);

// Task Form Submit
document
  .getElementById("task-form")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = {
      task_id: document.getElementById("task-id").value || null,
      title: document.getElementById("task-title").value,
      description: document.getElementById("task-description").value,
      due_date: document.getElementById("task-due-date").value,
      priority_id: document.getElementById("task-priority").value,
      categories: Array.from(
        document.getElementById("task-categories").selectedOptions
      ).map((option) => option.value),
    };

    console.log("Submitting task data:", formData); // Debug

    try {
      const result = await saveTask(formData);
      console.log("Save result:", result); // Debug

      if (result.success) {
        closeTaskModal();
        loadTasks(); // Refresh the task list
        updateTaskStatistics();
      } else {
        alert("Error saving task: " + (result.error || "Unknown error"));
      }
    } catch (error) {
      console.error("Error:", error);
      alert("Failed to save task");
    }
  });

// Add Category Button
addCategoryBtn?.addEventListener("click", () => {
  if (categoryModal) {
    categoryForm?.reset();
    categoryModal.classList.add("active");
  }
});

// Close category modal buttons
  const closeCategoryModalButton = document.getElementById("close-category-modal");
  if (closeCategoryModalButton) {
    closeCategoryModalButton.addEventListener("click", (e) => {
      e.preventDefault();
      console.log("Close category modal button clicked");
      closeCategoryModal();
    });
  }

   const cancelCategoryButton = document.getElementById("cancel-category-btn");
  if (cancelCategoryButton) {
    cancelCategoryButton.addEventListener("click", (e) => {
      e.preventDefault();
      console.log("Cancel category button clicked");
      closeCategoryModal();
    });
  }

const categoryFormElement = document.getElementById("category-form");
  if (categoryFormElement) {
    categoryFormElement.addEventListener("submit", async function (e) {
      e.preventDefault();
      console.log("Category form submitted");

      const formData = {
        category_id: document.getElementById("category-id").value || null,
        name: document.getElementById("category-name").value,
        color: document.getElementById("category-color").value,
      };

      try {
        const result = await saveCategory(formData);
        console.log("Save category result:", result);

        if (result.success) {
          closeCategoryModal();
          await loadCategories(); // Refresh the list
        } else {
          alert("Error: " + (result.error || "Unknown error"));
        }
      } catch (error) {
        console.error("Error:", error);
        alert("Failed to save category");
      }
    });
  }


// ============================
// App Initialization
// ============================
async function initApp() {
  console.log("Initializing app...");
  
  // Check if user is logged in
  try {
    const response = await fetch("api/check-auth.php");
    const data = await response.json();
    console.log("Auth check response:", data);
    
    if (data.success && data.loggedIn) {
      currentUser = data.user;
      console.log("User is logged in:", currentUser);
      
      showDashboard();
      
      // Load initial data in the correct order
      console.log("Loading initial data...");
      await Promise.all([
        loadTasks(),
        loadCategories(),
        updateTaskStatistics()
      ]);
      
      console.log("Initial data loaded successfully");
      
    } else {
      console.log("User is not logged in");
      showLogin();
    }
  } catch (error) {
    console.error("Error checking auth:", error);
    showLogin();
  }
}

// Debug logging
console.log("Script loaded, waiting for DOM...");

// Start the app when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}