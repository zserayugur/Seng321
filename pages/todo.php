<?php
session_start();
$page = 'todo';

// Initialize Session for Mock Data if empty
if (!isset($_SESSION['todos'])) {
    $_SESSION['todos'] = [
        ['id' => 1, 'task' => 'Complete Reading Unit 5', 'priority' => 'High', 'completed' => false],
        ['id' => 2, 'task' => 'Watch English Movie without subs', 'priority' => 'Medium', 'completed' => true],
        ['id' => 3, 'task' => 'Practice Speaking with AI', 'priority' => 'High', 'completed' => false],
    ];
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $newTask = [
            'id' => time(),
            'task' => htmlspecialchars($_POST['task_text']),
            'priority' => $_POST['priority'],
            'completed' => false
        ];
        $_SESSION['todos'][] = $newTask;
    } elseif (isset($_POST['delete'])) {
        $idToDelete = $_POST['delete_id'];
        $_SESSION['todos'] = array_filter($_SESSION['todos'], function ($t) use ($idToDelete) {
            return $t['id'] != $idToDelete;
        });
    } elseif (isset($_POST['toggle'])) {
        $idToToggle = $_POST['toggle_id'];
        foreach ($_SESSION['todos'] as &$t) {
            if ($t['id'] == $idToToggle) {
                $t['completed'] = !$t['completed'];
                break;
            }
        }
    } elseif (isset($_POST['edit'])) {
        // Edit logic: Usually would redirect or fill form. 
        // For this single-file demo, we might just update directly if an ID is passed with new text
        // Simplifying to "Update" if 'update_id' is present
    } elseif (isset($_POST['update'])) {
        $idToUpdate = $_POST['update_id'];
        foreach ($_SESSION['todos'] as &$t) {
            if ($t['id'] == $idToUpdate) {
                $t['task'] = htmlspecialchars($_POST['task_text']);
                $t['priority'] = $_POST['priority'];
                break;
            }
        }
    }
}

$path_prefix = '../';
require_once '../includes/header.php';

// Check if we are in edit mode
$editHeader = "Add New Task";
$editTask = "";
$editPriority = "Medium";
$editId = "";

if (isset($_GET['edit'])) {
    $editHeader = "Edit Task";
    $idToEdit = $_GET['edit'];
    foreach ($_SESSION['todos'] as $t) {
        if ($t['id'] == $idToEdit) {
            $editTask = $t['task'];
            $editPriority = $t['priority'];
            $editId = $t['id'];
            break;
        }
    }
}
?>

<div class="dashboard-grid">
    <!-- Task Form -->
    <section class="card">
        <h2>
            <?php echo $editHeader; ?>
        </h2>
        <form method="POST" action="todo.php">
            <input type="text" name="task_text" placeholder="What do you need to study?" required
                value="<?php echo $editTask; ?>">
            <select name="priority">
                <option value="High" <?php echo ($editPriority == 'High') ? 'selected' : ''; ?>>High Priority</option>
                <option value="Medium" <?php echo ($editPriority == 'Medium') ? 'selected' : ''; ?>>Medium Priority
                </option>
                <option value="Low" <?php echo ($editPriority == 'Low') ? 'selected' : ''; ?>>Low Priority</option>
            </select>

            <?php if ($editId): ?>
                <input type="hidden" name="update_id" value="<?php echo $editId; ?>">
                <button type="submit" name="update" class="btn btn-primary" style="width: 100%;">Update Task</button>
                <a href="todo.php" class="btn"
                    style="width: 100%; text-align: center; margin-top: 10px; background: rgba(255,255,255,0.1);">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add" class="btn btn-primary" style="width: 100%;">Add Task</button>
            <?php endif; ?>
        </form>
    </section>

    <!-- Task List -->
    <section class="card">
        <h2>Your Responsibilities</h2>
        <div class="todo-list">
            <?php if (empty($_SESSION['todos'])): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No tasks yet. Good job!</p>
            <?php else: ?>
                <?php foreach ($_SESSION['todos'] as $todo): ?>
                    <div class="todo-item <?php echo $todo['completed'] ? 'completed' : ''; ?>">
                        <div style="flex-grow: 1;">
                            <span style="display: block; font-weight: 500; font-size: 1.05rem;">
                                <?php echo $todo['task']; ?>
                            </span>
                            <span class="badge <?php echo ($todo['priority'] == 'High') ? 'badge-a1' : 'badge-b2'; ?>"
                                style="font-size: 0.7rem; margin-top: 5px; display: inline-block;">
                                <?php echo $todo['priority']; ?>
                            </span>
                        </div>

                        <div class="todo-actions">
                            <!-- Complete Button -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="toggle_id" value="<?php echo $todo['id']; ?>">
                                <button type="submit" name="toggle" class="btn-icon btn-check" title="Toggle Complete">
                                    &#10003;
                                </button>
                            </form>

                            <!-- Edit Button -->
                            <a href="todo.php?edit=<?php echo $todo['id']; ?>" class="btn-icon" title="Edit"
                                style="text-decoration: none;">
                                &#9998;
                            </a>

                            <!-- Delete Button -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $todo['id']; ?>">
                                <button type="submit" name="delete" class="btn-icon btn-delete" title="Delete">
                                    &times;
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>