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
    header("Location: admin_dashboard.php");
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
    <title>Admin Dashboard - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Shoepee</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">Admin Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Manage Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employee_register.php">Register Employee</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
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

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Employee Management</h3>
                        <a href="employee_register.php" class="btn btn-primary">Register New Employee</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Admin</th>
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
                                                <span class="badge bg-<?php echo $employee['Is_Active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $employee['Is_Active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $employee['Is_Admin'] ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $employee['Is_Admin'] ? 'Admin' : 'Employee'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $employee['Is_Active'] ? 'danger' : 'success'; ?>">
                                                        <?php echo $employee['Is_Active'] ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="toggle_admin">
                                                    <input type="hidden" name="employee_id" value="<?php echo $employee['Employee_ID']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $employee['Is_Admin'] ? 'warning' : 'info'; ?>">
                                                        <?php echo $employee['Is_Admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                                    </button>
                                                </form>
                                                <a href="edit_employee.php?id=<?php echo $employee['Employee_ID']; ?>" 
                                                   class="btn btn-sm btn-primary">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Products</h5>
                        <p class="card-text">Manage your product inventory</p>
                        <a href="manage_products.php" class="btn btn-primary">Manage Products</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Employees</h5>
                        <p class="card-text">Register and manage employees</p>
                        <a href="employee_register.php" class="btn btn-primary">Register Employee</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Orders</h5>
                        <p class="card-text">View and manage customer orders</p>
                        <a href="manage_orders.php" class="btn btn-primary">Manage Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 