<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'toggle_active':
                    $stmt = $pdo->prepare("
                        UPDATE Employee 
                        SET Is_Active = NOT Is_Active 
                        WHERE Employee_ID = ?
                    ");
                    $stmt->execute([$_POST['employee_id']]);
                    $_SESSION['success_message'] = "Employee status updated successfully!";
                    break;

                case 'toggle_admin':
                    $stmt = $pdo->prepare("
                        UPDATE Employee 
                        SET Is_Admin = NOT Is_Admin 
                        WHERE Employee_ID = ?
                    ");
                    $stmt->execute([$_POST['employee_id']]);
                    $_SESSION['success_message'] = "Employee admin privileges updated successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Operation failed: " . $e->getMessage();
    }
    
    // Redirect to refresh the page
    header("Location: edit_employee.php");
    exit();
}

// Get all employees with their positions and departments
$stmt = $pdo->prepare("
    SELECT e.*, p.Title as Position, d.Department_Name
    FROM Employee e
    LEFT JOIN Job_Position p ON e.Position_ID = p.Position_ID
    LEFT JOIN Department d ON e.Department = d.Department_ID
    ORDER BY e.Employee_ID DESC
");
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .employee-page {
            margin: 2rem auto;
        }
        .page-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 500;
        }
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .badge-active {
            background-color: #198754;
            color: white;
        }
        .badge-inactive {
            background-color: #dc3545;
            color: white;
        }
        .badge-admin {
            background-color: #0d6efd;
            color: white;
        }
        .badge-employee {
            background-color: #6c757d;
            color: white;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="employee-page">
        <div class="container">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="m-0">Manage Employees</h2>
                    <p class="mb-0">View and manage employee accounts</p>
                </div>
                <a href="employee_register.php" class="btn btn-light"><i class="bi bi-plus-lg"></i> Add New Employee</a>
            </div>

            <?php if(isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($employees as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['Employee_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($employee['First_Name'] . ' ' . $employee['Last_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['Position']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['Department_Name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $employee['Is_Active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $employee['Is_Active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $employee['Is_Admin'] ? 'badge-admin' : 'badge-employee'; ?>">
                                            <?php echo $employee['Is_Admin'] ? 'Admin' : 'Employee'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <form method="POST" class="d-inline me-1">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $employee['Is_Active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?> btn-action">
                                                    <?php echo $employee['Is_Active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline me-1">
                                                <input type="hidden" name="action" value="toggle_admin">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $employee['Is_Admin'] ? 'btn-outline-warning' : 'btn-outline-primary'; ?> btn-action">
                                                    <?php echo $employee['Is_Admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 