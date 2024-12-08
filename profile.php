<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in (either customer or employee)
if (!isset($_SESSION['customer_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

// Determine user type
$is_customer = isset($_SESSION['customer_id']);
$user_id = $is_customer ? $_SESSION['customer_id'] : $_SESSION['employee_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        if ($is_customer) {
            // Update customer basic info
            $stmt = $pdo->prepare("
                UPDATE Customer SET
                First_Name = :first_name,
                Last_Name = :last_name,
                Email = :email,
                Phone_Number = :phone
                WHERE Customer_ID = :id
            ");

            $stmt->execute([
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'],
                ':id' => $user_id
            ]);

            // Update password if provided
            if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE Customer SET
                        Password = :password
                        WHERE Customer_ID = :id
                    ");
                    $stmt->execute([
                        ':password' => $hashed_password,
                        ':id' => $user_id
                    ]);
                } else {
                    throw new Exception("New passwords do not match");
                }
            }
        } else {
            // Handle employee updates (existing employee code)
            if (isset($_POST['street'])) {
                if ($_POST['address_id']) {
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
                        ':address_id' => $_POST['address_id']
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

                    // Update employee with new address
                    $stmt = $pdo->prepare("
                        UPDATE Employee SET
                        Employee_Address = :address_id
                        WHERE Employee_ID = :employee_id
                    ");
                    $stmt->execute([
                        ':address_id' => $address_id,
                        ':employee_id' => $user_id
                    ]);
                }
            }

            // Update employee basic info
            $stmt = $pdo->prepare("
                UPDATE Employee SET
                First_Name = :first_name,
                Last_Name = :last_name,
                Email = :email,
                Phone_Number = :phone
                WHERE Employee_ID = :id
            ");

            $stmt->execute([
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':email' => $_POST['email'],
                ':phone' => $_POST['phone'],
                ':id' => $user_id
            ]);

            // Update password if provided
            if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE Employee SET
                        Password = :password
                        WHERE Employee_ID = :id
                    ");
                    $stmt->execute([
                        ':password' => $hashed_password,
                        ':id' => $user_id
                    ]);
                } else {
                    throw new Exception("New passwords do not match");
                }
            }
        }

        $pdo->commit();
        $_SESSION['success_message'] = "Profile updated successfully!";
        $_SESSION['user_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Failed to update profile: " . $e->getMessage();
    }
    
    header("Location: profile.php");
    exit();
}

// Get user data
if ($is_customer) {
    $stmt = $pdo->prepare("
        SELECT * FROM Customer WHERE Customer_ID = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("
        SELECT e.*, ea.*,
               p.Title as Position_Title,
               d.Department_Name
        FROM Employee e
        LEFT JOIN Employee_Address ea ON e.Employee_Address = ea.Employee_Address_ID
        LEFT JOIN Job_Position p ON e.Position_ID = p.Position_ID
        LEFT JOIN Department d ON e.Department = d.Department_ID
        WHERE e.Employee_ID = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'favicon.php'; ?>
    <title>Profile - Shoepee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f0f0f0;
        }
        .profile-page {
            margin: 2rem auto;
        }
        .profile-header {
            background-color: #f05537;
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .profile-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #f05537;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .profile-info {
            margin-bottom: 1rem;
        }
        .info-label {
            font-weight: 500;
            color: #666;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #333;
            font-size: 1.1rem;
        }
        .edit-form label {
            font-weight: 500;
            color: #666;
        }
        .edit-form .form-control:focus {
            border-color: #f05537;
            box-shadow: 0 0 0 0.2rem rgba(240, 85, 55, 0.25);
        }
        .btn-primary {
            background-color: #f05537;
            border-color: #f05537;
        }
        .btn-primary:hover {
            background-color: #d64426;
            border-color: #d64426;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="profile-page">
        <div class="container">
            <div class="profile-header">
                <h2 class="m-0">My Profile</h2>
                <p class="mb-0">View and edit your profile information</p>
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

            <div class="row">
                <!-- User Information -->
                <div class="col-md-4">
                    <div class="profile-card">
                        <h4 class="section-title">Account Information</h4>
                        <div class="profile-info">
                            <div class="info-label">Account Type</div>
                            <div class="info-value"><?php echo $is_customer ? 'Customer' : 'Employee'; ?></div>
                        </div>
                        <div class="profile-info">
                            <div class="info-label">ID</div>
                            <div class="info-value"><?php echo str_pad($user_id, 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <?php if(!$is_customer): ?>
                            <div class="profile-info">
                                <div class="info-label">Position</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['Position_Title']); ?></div>
                            </div>
                            <div class="profile-info">
                                <div class="info-label">Department</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['Department_Name']); ?></div>
                            </div>
                            <?php if($user['Is_Admin']): ?>
                                <div class="profile-info">
                                    <span class="badge bg-primary">Administrator</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="col-md-8">
                    <div class="profile-card">
                        <h4 class="section-title">Edit Profile</h4>
                        <form method="POST" class="edit-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['First_Name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['Last_Name']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($user['Phone_Number']); ?>">
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">Change Password</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password">
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>

                            <?php if(!$is_customer): ?>
                                <h5 class="mt-4 mb-3">Address Information</h5>
                                <input type="hidden" name="address_id" value="<?php echo $user['Employee_Address']; ?>">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Street</label>
                                        <input type="text" class="form-control" name="street" 
                                               value="<?php echo htmlspecialchars($user['Street'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Barangay</label>
                                        <input type="text" class="form-control" name="barangay" 
                                               value="<?php echo htmlspecialchars($user['Barangay'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city" 
                                               value="<?php echo htmlspecialchars($user['Town_City'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Province</label>
                                        <input type="text" class="form-control" name="province" 
                                               value="<?php echo htmlspecialchars($user['Province'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Region</label>
                                        <input type="text" class="form-control" name="region" 
                                               value="<?php echo htmlspecialchars($user['Region'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" name="postal_code" 
                                               value="<?php echo htmlspecialchars($user['Postal_Code'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
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