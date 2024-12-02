<?php
session_start();
require_once 'config/database.php';

// Only admin employees can register new employees
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: login.php");
    exit();
}

// Get positions and departments
$positions = $pdo->query("SELECT * FROM Job_Position")->fetchAll();
$departments = $pdo->query("SELECT * FROM Department")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Create employee address
        $stmt = $pdo->prepare("
            INSERT INTO Employee_Address (Street, Barangay, Town_City, Province, Region, Postal_Code)
            VALUES (:street, :barangay, :city, :province, :region, :postal_code)
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

        // Create employee
        $stmt = $pdo->prepare("
            INSERT INTO Employee (
                First_Name, Last_Name, Email, Password, Phone_Number,
                Employee_Address, Gender, Birthday, Department,
                Position_ID, Salary, SSS_Number, Pag_IBIG,
                PhilHealth, TIN, Is_Admin
            ) VALUES (
                :first_name, :last_name, :email, :password, :phone,
                :address, :gender, :birthday, :department,
                :position, :salary, :sss, :pagibig,
                :philhealth, :tin, 0
            )
        ");

        $stmt->execute([
            ':first_name' => $_POST['first_name'],
            ':last_name' => $_POST['last_name'],
            ':email' => $_POST['email'],
            ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            ':phone' => $_POST['phone'],
            ':address' => $address_id,
            ':gender' => $_POST['gender'],
            ':birthday' => $_POST['birthday'],
            ':department' => $_POST['department'],
            ':position' => $_POST['position'],
            ':salary' => $_POST['salary'],
            ':sss' => $_POST['sss'],
            ':pagibig' => $_POST['pagibig'],
            ':philhealth' => $_POST['philhealth'],
            ':tin' => $_POST['tin']
        ]);

        $pdo->commit();
        $success_message = "Employee registered successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Registration failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Employee - Shoepee</title>
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
                        <a class="nav-link active" href="employee_register.php">Register Employee</a>
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
                        <h3>Register New Employee</h3>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        <?php if(isset($error_message)): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <h4>Personal Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Birthday</label>
                                <input type="date" class="form-control" name="birthday" required>
                            </div>

                            <h4 class="mt-4">Employment Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Position</label>
                                    <select class="form-select" name="position" required>
                                        <?php foreach($positions as $position): ?>
                                            <option value="<?php echo $position['Position_ID']; ?>">
                                                <?php echo htmlspecialchars($position['Title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department" required>
                                        <?php foreach($departments as $department): ?>
                                            <option value="<?php echo $department['Department_ID']; ?>">
                                                <?php echo htmlspecialchars($department['Department_Name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Salary</label>
                                    <input type="number" step="0.01" class="form-control" name="salary" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SSS Number</label>
                                    <input type="text" class="form-control" name="sss" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Pag-IBIG</label>
                                    <input type="text" class="form-control" name="pagibig" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">PhilHealth</label>
                                    <input type="text" class="form-control" name="philhealth" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">TIN</label>
                                    <input type="text" class="form-control" name="tin" required>
                                </div>
                            </div>

                            <h4 class="mt-4">Address Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Street</label>
                                    <input type="text" class="form-control" name="street" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay</label>
                                    <input type="text" class="form-control" name="barangay" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Province</label>
                                    <input type="text" class="form-control" name="province" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Region</label>
                                    <input type="text" class="form-control" name="region" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" name="postal_code" required>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Register Employee</button>
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