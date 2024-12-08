<?php
session_start();
require_once 'config/database.php';

// Check if user is admin
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get departments and positions
$departments = $pdo->query("SELECT * FROM Department")->fetchAll();
$positions = $pdo->query("SELECT * FROM Job_Position")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert address first
        $stmt = $pdo->prepare("
            INSERT INTO Employee_Address (
                Street, Barangay, Town_City, Province, Region, Postal_Code
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['street'],
            $_POST['barangay'],
            $_POST['city'],
            $_POST['province'],
            $_POST['region'],
            $_POST['postal_code']
        ]);
        $address_id = $pdo->lastInsertId();

        // Hash the password
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insert employee
        $stmt = $pdo->prepare("
            INSERT INTO Employee (
                First_Name, Last_Name, Email, Password, Phone_Number,
                Employee_Address, Gender, Birthday, Department, Position_ID,
                Salary, SSS_Number, Pag_IBIG, PhilHealth, TIN
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $hashed_password,
            $_POST['phone'],
            $address_id,
            $_POST['gender'],
            $_POST['birthday'],
            $_POST['department'],
            $_POST['position'],
            $_POST['salary'],
            $_POST['sss'],
            $_POST['pagibig'],
            $_POST['philhealth'],
            $_POST['tin']
        ]);

        $pdo->commit();
        $success_message = "Employee registered successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to register employee: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Employee Registration - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .register-page .register-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .register-page .register-header {
            background-color: #f05537;
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .register-page .register-form {
            padding: 2rem;
        }
        .register-page .form-label {
            color: #666;
            font-weight: normal;
            margin-bottom: 0.5rem;
        }
        .register-page .form-control {
            border: 1px solid #ddd;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .register-page .btn-register {
            background-color: #f05537;
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 4px;
            color: white;
        }
        .register-page .btn-register:hover {
            background-color: #e04527;
        }
        .register-page .section-title {
            color: #f05537;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="register-page">
        <div class="register-container">
            <div class="register-header">
                <h2 class="m-0">Register New Employee</h2>
            </div>

            <div class="register-form">
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <h4 class="section-title">Personal Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-control" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Birthday</label>
                        <input type="date" class="form-control" name="birthday" required>
                    </div>

                    <h4 class="section-title">Address Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Street</label>
                                <input type="text" class="form-control" name="street" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Barangay</label>
                                <input type="text" class="form-control" name="barangay" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Province</label>
                                <input type="text" class="form-control" name="province" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Region</label>
                                <input type="text" class="form-control" name="region" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="number" class="form-control" name="postal_code" required>
                            </div>
                        </div>
                    </div>

                    <h4 class="section-title">Employment Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-control" name="department" required>
                                    <option value="">Select Department</option>
                                    <?php foreach($departments as $department): ?>
                                        <option value="<?php echo $department['Department_ID']; ?>">
                                            <?php echo htmlspecialchars($department['Department_Name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <select class="form-control" name="position" required>
                                    <option value="">Select Position</option>
                                    <?php foreach($positions as $position): ?>
                                        <option value="<?php echo $position['Position_ID']; ?>">
                                            <?php echo htmlspecialchars($position['Title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Salary</label>
                        <input type="number" step="0.01" class="form-control" name="salary" required>
                    </div>

                    <h4 class="section-title">Government Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">SSS Number</label>
                                <input type="text" class="form-control" name="sss" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Pag-IBIG Number</label>
                                <input type="text" class="form-control" name="pagibig" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">PhilHealth Number</label>
                                <input type="text" class="form-control" name="philhealth" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">TIN</label>
                                <input type="text" class="form-control" name="tin" required>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-register">Register Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 