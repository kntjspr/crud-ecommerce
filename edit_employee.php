<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get employee ID
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get positions and departments
$positions = $pdo->query("SELECT * FROM Job_Position")->fetchAll();
$departments = $pdo->query("SELECT * FROM Department")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update employee address if it exists
        if ($employee['Employee_Address']) {
            $stmt = $pdo->prepare("
                UPDATE Employee_Address SET
                Street = :street,
                Barangay = :barangay,
                Town_City = :city,
                Province = :province,
                Region = :region,
                Postal_Code = :postal_code
                WHERE Employee_Address_ID = :address_id
            ");
            $stmt->execute([
                ':street' => $_POST['street'],
                ':barangay' => $_POST['barangay'],
                ':city' => $_POST['city'],
                ':province' => $_POST['province'],
                ':region' => $_POST['region'],
                ':postal_code' => $_POST['postal_code'],
                ':address_id' => $employee['Employee_Address']
            ]);
        } else {
            // Create new address
            $stmt = $pdo->prepare("
                INSERT INTO Employee_Address (
                    Street, Barangay, Town_City, Province, Region, Postal_Code
                ) VALUES (
                    :street, :barangay, :city, :province, :region, :postal_code
                )
            ");
            $stmt->execute([
                ':street' => $_POST['street'],
                ':barangay' => $_POST['barangay'],
                ':city' => $_POST['city'],
                ':province' => $_POST['province'],
                ':region' => $_POST['region'],
                ':postal_code' => $_POST['postal_code']
            ]);
            $address_id = $pdo->lastInsertId();
        }

        // Update employee
        $stmt = $pdo->prepare("
            UPDATE Employee SET
            First_Name = :first_name,
            Last_Name = :last_name,
            Email = :email,
            Phone_Number = :phone,
            Gender = :gender,
            Birthday = :birthday,
            Department = :department,
            Position_ID = :position,
            Salary = :salary,
            SSS_Number = :sss,
            Pag_IBIG = :pagibig,
            PhilHealth = :philhealth,
            TIN = :tin,
            Employee_Address = :address
            WHERE Employee_ID = :id
        ");

        $stmt->execute([
            ':first_name' => $_POST['first_name'],
            ':last_name' => $_POST['last_name'],
            ':email' => $_POST['email'],
            ':phone' => $_POST['phone'],
            ':gender' => $_POST['gender'],
            ':birthday' => $_POST['birthday'],
            ':department' => $_POST['department'],
            ':position' => $_POST['position'],
            ':salary' => $_POST['salary'],
            ':sss' => $_POST['sss'],
            ':pagibig' => $_POST['pagibig'],
            ':philhealth' => $_POST['philhealth'],
            ':tin' => $_POST['tin'],
            ':address' => isset($address_id) ? $address_id : $employee['Employee_Address'],
            ':id' => $employee_id
        ]);

        $pdo->commit();
        $_SESSION['success_message'] = "Employee updated successfully!";
        header("Location: admin_dashboard.php");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to update employee: " . $e->getMessage();
    }
}

// Get employee data with address
$stmt = $pdo->prepare("
    SELECT e.*, ea.*
    FROM Employee e
    LEFT JOIN Employee_Address ea ON e.Employee_Address = ea.Employee_Address_ID
    WHERE e.Employee_ID = ?
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - Shoepee</title>
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
                        <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
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
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h3>Edit Employee</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <h4>Personal Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" 
                                           value="<?php echo htmlspecialchars($employee['First_Name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" 
                                           value="<?php echo htmlspecialchars($employee['Last_Name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($employee['Email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($employee['Phone_Number']); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="Male" <?php echo $employee['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $employee['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $employee['Gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Birthday</label>
                                    <input type="date" class="form-control" name="birthday" 
                                           value="<?php echo $employee['Birthday'] ? date('Y-m-d', strtotime($employee['Birthday'])) : ''; ?>" required>
                                </div>
                            </div>

                            <h4 class="mt-4">Employment Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Position</label>
                                    <select class="form-select" name="position" required>
                                        <?php foreach($positions as $position): ?>
                                            <option value="<?php echo $position['Position_ID']; ?>"
                                                <?php echo $position['Position_ID'] == $employee['Position_ID'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($position['Title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department" required>
                                        <?php foreach($departments as $department): ?>
                                            <option value="<?php echo $department['Department_ID']; ?>"
                                                <?php echo $department['Department_ID'] == $employee['Department'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($department['Department_Name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Salary</label>
                                    <input type="number" step="0.01" class="form-control" name="salary" 
                                           value="<?php echo $employee['Salary']; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SSS Number</label>
                                    <input type="text" class="form-control" name="sss" 
                                           value="<?php echo htmlspecialchars($employee['SSS_Number']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Pag-IBIG</label>
                                    <input type="text" class="form-control" name="pagibig" 
                                           value="<?php echo htmlspecialchars($employee['Pag_IBIG']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">PhilHealth</label>
                                    <input type="text" class="form-control" name="philhealth" 
                                           value="<?php echo htmlspecialchars($employee['PhilHealth']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">TIN</label>
                                    <input type="text" class="form-control" name="tin" 
                                           value="<?php echo htmlspecialchars($employee['TIN']); ?>" required>
                                </div>
                            </div>

                            <h4 class="mt-4">Address Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Street</label>
                                    <input type="text" class="form-control" name="street" 
                                           value="<?php echo htmlspecialchars($employee['Street']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay</label>
                                    <input type="text" class="form-control" name="barangay" 
                                           value="<?php echo htmlspecialchars($employee['Barangay']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" 
                                           value="<?php echo htmlspecialchars($employee['Town_City']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Province</label>
                                    <input type="text" class="form-control" name="province" 
                                           value="<?php echo htmlspecialchars($employee['Province']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Region</label>
                                    <input type="text" class="form-control" name="region" 
                                           value="<?php echo htmlspecialchars($employee['Region']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code" 
                                           value="<?php echo htmlspecialchars($employee['Postal_Code']); ?>" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">Update Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 