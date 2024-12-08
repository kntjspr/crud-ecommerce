<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Initial connection to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create fresh database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS shoepee_db");
    echo "Database created/verified successfully<br>";
    
    // Switch to the new database
    $pdo->exec("USE shoepee_db");
    
    // Create tables in correct order
    $queries = [
        // Independent tables first
        "CREATE TABLE IF NOT EXISTS Job_Position (
            Position_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Title VARCHAR(50) NOT NULL,
            Description VARCHAR(100)
        )",

        "CREATE TABLE IF NOT EXISTS Department (
            Department_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Department_Name VARCHAR(25) NOT NULL,
            Description VARCHAR(100)
        )",

        "CREATE TABLE IF NOT EXISTS Issue_Tracker (
            Issue_Tracker_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Description VARCHAR(100),
            Status VARCHAR(20)
        )",

        "CREATE TABLE IF NOT EXISTS Employee_Address (
            Employee_Address_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Street VARCHAR(25) NOT NULL,
            Barangay VARCHAR(25) NOT NULL,
            Town_City VARCHAR(25) NOT NULL,
            Province VARCHAR(25) NOT NULL,
            Region VARCHAR(25) NOT NULL,
            Postal_Code INT(4) NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS Customer_Address (
            Customer_Address_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Street VARCHAR(25) NOT NULL,
            Barangay VARCHAR(25) NOT NULL,
            Town_City VARCHAR(25) NOT NULL,
            Province VARCHAR(25) NOT NULL,
            Region VARCHAR(25) NOT NULL,
            Postal_Code INT(4) NOT NULL
        )",

        // Tables with dependencies
        "CREATE TABLE IF NOT EXISTS Employee (
            Employee_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            First_Name VARCHAR(25) NOT NULL,
            Last_Name VARCHAR(25) NOT NULL,
            Phone_Number VARCHAR(20),
            Employee_Address INT(5),
            Gender VARCHAR(10),
            Birthday DATETIME,
            Email VARCHAR(50),
            Password VARCHAR(255) NOT NULL,
            Department INT(5),
            Salary DECIMAL(10,2),
            SSS_Number VARCHAR(20),
            Pag_IBIG VARCHAR(20),
            PhilHealth VARCHAR(20),
            TIN VARCHAR(20),
            Issue_Tracker_ID INT(5),
            Position_ID INT(5),
            Is_Admin BOOLEAN DEFAULT FALSE,
            Is_Active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (Employee_Address) REFERENCES Employee_Address(Employee_Address_ID),
            FOREIGN KEY (Department) REFERENCES Department(Department_ID),
            FOREIGN KEY (Issue_Tracker_ID) REFERENCES Issue_Tracker(Issue_Tracker_ID),
            FOREIGN KEY (Position_ID) REFERENCES Job_Position(Position_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Customer (
            Customer_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Username VARCHAR(50) NOT NULL UNIQUE,
            First_Name VARCHAR(50) NOT NULL,
            Last_Name VARCHAR(50) NOT NULL,
            Email VARCHAR(50) NOT NULL,
            Password VARCHAR(255) NOT NULL,
            Phone_Number VARCHAR(20),
            Customer_Address INT(5),
            Gender VARCHAR(10),
            Birthday DATETIME,
            FOREIGN KEY (Customer_Address) REFERENCES Customer_Address(Customer_Address_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Category (
            Category_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Category_Name VARCHAR(100) NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS Product (
            Product_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Product_Name VARCHAR(100) NOT NULL,
            Description VARCHAR(500),
            Price DECIMAL(10,2) NOT NULL,
            Stock INT(5) NOT NULL,
            Category_ID INT(5),
            FOREIGN KEY (Category_ID) REFERENCES Category(Category_ID)
        )",

        "CREATE TABLE IF NOT EXISTS ProductImage (
            Image_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Product_ID INT(5) NOT NULL,
            Image_Path VARCHAR(255) NOT NULL,
            Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS `Order` (
            Order_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Customer_ID INT(5),
            Order_Date DATETIME NOT NULL,
            Total_Amount DECIMAL(10,2) NOT NULL,
            Employee_ID INT(5),
            FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
            FOREIGN KEY (Employee_ID) REFERENCES Employee(Employee_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Payment_Method (
            Payment_Method_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Method_Name VARCHAR(100) NOT NULL,
            Provider VARCHAR(100) NOT NULL,
            Transaction_Fee DECIMAL(10,2)
        )",

        "CREATE TABLE IF NOT EXISTS Shipping_Method (
            Shipping_Method_ID INT PRIMARY KEY AUTO_INCREMENT,
            Method_Name VARCHAR(50) UNIQUE,
            Cost DECIMAL(10,2),
            Estimated_Delivery_Time VARCHAR(50)
        )",

        "CREATE TABLE IF NOT EXISTS Shipping_Address (
            Shipping_Address_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Street VARCHAR(25) NOT NULL,
            Barangay VARCHAR(25) NOT NULL,
            Town_City VARCHAR(25) NOT NULL,
            Province VARCHAR(25) NOT NULL,
            Region VARCHAR(10) NOT NULL,
            Postal_Code INT(4) NOT NULL
        )",

        "CREATE TABLE IF NOT EXISTS Shipping (
            Shipping_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Shipping_Status VARCHAR(20),
            Shipping_Address_ID INT(5),
            Shipping_Method_ID INT(5),
            FOREIGN KEY (Shipping_Address_ID) REFERENCES Shipping_Address(Shipping_Address_ID),
            FOREIGN KEY (Shipping_Method_ID) REFERENCES Shipping_Method(Shipping_Method_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Payment (
            Payment_ID INT PRIMARY KEY AUTO_INCREMENT,
            Order_ID INT,
            Payment_Method_ID INT,
            Payment_Status VARCHAR(50) DEFAULT 'Pending',
            Payment_Date DATETIME,
            Amount DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID),
            FOREIGN KEY (Payment_Method_ID) REFERENCES Payment_Method(Payment_Method_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Receipt (
            Receipt_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Tax_Amount DECIMAL(10,2),
            Total_Amount DECIMAL(10,2),
            Type VARCHAR(20)
        )",

        "CREATE TABLE IF NOT EXISTS Review (
            Review_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Product_ID INT(5),
            Customer_ID INT(5),
            Rating INT(1) NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
            Review_Text VARCHAR(500),
            Review_Date DATETIME NOT NULL,
            FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID),
            FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Transaction (
            Transaction_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Order_ID INT(5),
            Shipping_ID INT(5),
            Receipt_ID INT(5),
            Product_ID INT(5),
            Payment_ID INT(5),
            Quantity INT(5) DEFAULT 1,
            FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID),
            FOREIGN KEY (Shipping_ID) REFERENCES Shipping(Shipping_ID),
            FOREIGN KEY (Receipt_ID) REFERENCES Receipt(Receipt_ID),
            FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID),
            FOREIGN KEY (Payment_ID) REFERENCES Payment(Payment_ID)
        )",

        "CREATE TABLE IF NOT EXISTS Settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            store_name VARCHAR(100) NOT NULL DEFAULT 'Shoepee',
            store_email VARCHAR(100),
            store_phone VARCHAR(20),
            store_address TEXT,
            tax_rate DECIMAL(5,2) DEFAULT 0.00,
            shipping_fee DECIMAL(10,2) DEFAULT 0.00,
            free_shipping_threshold DECIMAL(10,2) DEFAULT 0.00,
            maintenance_mode BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS Cart (
            Cart_ID INT PRIMARY KEY AUTO_INCREMENT,
            Customer_ID INT NOT NULL,
            Product_ID INT NOT NULL,
            Quantity INT NOT NULL DEFAULT 1,
            Added_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
            FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID),
            UNIQUE KEY unique_customer_product (Customer_ID, Product_ID)
        )",

        "CREATE TABLE IF NOT EXISTS OrderItem (
            OrderItem_ID INT PRIMARY KEY AUTO_INCREMENT,
            Order_ID INT NOT NULL,
            Product_ID INT NOT NULL,
            Quantity INT NOT NULL,
            Price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID),
            FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID)
        )"
    ];

    // Execute table creation queries
    foreach ($queries as $query) {
        $pdo->exec($query);
        echo "Table created/verified successfully<br>";
    }

    // Insert initial data if not exists
    // Insert default department
    $stmt = $pdo->prepare("INSERT INTO Department (Department_Name, Description) 
                          SELECT 'Executive', 'Executive Department' 
                          WHERE NOT EXISTS (SELECT 1 FROM Department WHERE Department_Name = 'Executive')");
    $stmt->execute();

    // Insert default position
    $stmt = $pdo->prepare("INSERT INTO Job_Position (Title, Description) 
                          SELECT 'Administrator', 'System Administrator' 
                          WHERE NOT EXISTS (SELECT 1 FROM Job_Position WHERE Title = 'Administrator')");
    $stmt->execute();

    // Insert sample categories if they don't exist
    $categories = [
        'Running Shoes',
        'Casual Shoes',
        'Formal Shoes',
        'Sports Shoes',
        'Sandals'
    ];

    $stmt = $pdo->prepare("INSERT INTO Category (Category_Name) 
                          SELECT ? WHERE NOT EXISTS (SELECT 1 FROM Category WHERE Category_Name = ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category, $category]);
    }

    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Clear existing shipping methods first
    $pdo->exec("TRUNCATE TABLE Shipping_Method");

    // Add default shipping methods
    $pdo->exec("
        INSERT INTO Shipping_Method (Method_Name, Cost, Estimated_Delivery_Time) VALUES
        ('Standard Delivery', 100.00, '3-5 business days'),
        ('Express Delivery', 200.00, '1-2 business days'),
        ('Same Day Delivery', 350.00, 'Within 24 hours')
    ");

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Create default admin address if not exists
    $stmt = $pdo->prepare("INSERT INTO Employee_Address (Street, Barangay, Town_City, Province, Region, Postal_Code) 
                          SELECT 'Default Street', 'Default Barangay', 'Default City', 'Default Province', 'Default Region', 1234
                          WHERE NOT EXISTS (
                              SELECT 1 FROM Employee_Address 
                              WHERE Street = 'Default Street' 
                              AND Barangay = 'Default Barangay'
                          )");
    $stmt->execute();
    
    // Get the address ID (either newly inserted or existing)
    $stmt = $pdo->prepare("SELECT Employee_Address_ID FROM Employee_Address 
                          WHERE Street = 'Default Street' AND Barangay = 'Default Barangay'");
    $stmt->execute();
    $address_id = $stmt->fetchColumn();

    // Get the first department and position IDs
    $dept_stmt = $pdo->query("SELECT Department_ID FROM Department LIMIT 1");
    $department_id = $dept_stmt->fetchColumn();

    $pos_stmt = $pdo->query("SELECT Position_ID FROM Job_Position LIMIT 1");
    $position_id = $pos_stmt->fetchColumn();

    // Create default admin account if not exists
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO Employee (
        First_Name, 
        Last_Name, 
        Email, 
        Password, 
        Employee_Address,
        Department,
        Position_ID,
        Is_Admin,
        Is_Active
    ) SELECT 
        'Admin',
        'User',
        'admin@shoepee.com',
        ?,
        ?,
        ?,
        ?,
        TRUE,
        TRUE
    WHERE NOT EXISTS (
        SELECT 1 FROM Employee WHERE Email = 'admin@shoepee.com'
    )");
    $stmt->execute([$admin_password, $address_id, $department_id, $position_id]);

    // Insert default settings if not exists
    $pdo->exec("
        INSERT INTO Settings (id, store_name, store_email, store_phone, store_address)
        SELECT 1, 'Shoepee', 'contact@shoepee.com', '+1234567890', '123 Shoe Street, Fashion District'
        WHERE NOT EXISTS (SELECT 1 FROM Settings WHERE id = 1)
    ");

    // After creating Payment_Method table, add default methods
    $pdo->exec("
        INSERT INTO Payment_Method (Method_Name, Provider, Transaction_Fee) VALUES
        ('Credit Card', 'Visa/Mastercard', 5.00),
        ('E-Wallet', 'GCash', 0.00),
        ('E-Wallet', 'Maya', 0.00),
        ('Cash on Delivery', 'Direct', 50.00)
        ON DUPLICATE KEY UPDATE Method_Name = VALUES(Method_Name)
    ");

    echo "<div class='alert alert-success mt-3'>Database initialization completed successfully!</div>";
    echo "<div class='alert alert-info mt-3'>Default Admin Credentials:<br>";
    echo "Email: admin@shoepee.com<br>";
    echo "Password: admin123</div>";

} catch(PDOException $e) {
    die("<div class='alert alert-danger mt-3'>Database initialization failed: " . $e->getMessage() . "</div>");
}
?> 