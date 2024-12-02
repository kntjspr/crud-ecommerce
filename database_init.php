<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=shoepee_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
            Shipping_Method_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Method_Name VARCHAR(100) NOT NULL,
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
            Payment_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
            Order_ID INT(5),
            Payment_Date DATETIME,
            Payment_Status VARCHAR(20),
            Payment_Method_ID INT(5),
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
            Rating DECIMAL(10,2) NOT NULL,
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
        )"
    ];

    // Execute table creation queries
    foreach ($queries as $query) {
        $pdo->exec($query);
        echo "Table created successfully<br>";
    }

    // Insert initial data
    // Insert default department
    $stmt = $pdo->prepare("INSERT INTO Department (Department_Name, Description) VALUES ('Administration', 'Administrative Department')");
    $stmt->execute();
    echo "Default department created<br>";

    // Insert default position
    $stmt = $pdo->prepare("INSERT INTO Job_Position (Title, Description) VALUES ('Administrator', 'System Administrator')");
    $stmt->execute();
    echo "Default position created<br>";

    // Insert sample categories
    $categories = [
        ['Category_Name' => 'Running Shoes'],
        ['Category_Name' => 'Casual Shoes'],
        ['Category_Name' => 'Formal Shoes'],
        ['Category_Name' => 'Sports Shoes'],
        ['Category_Name' => 'Sandals']
    ];

    $stmt = $pdo->prepare("INSERT INTO Category (Category_Name) VALUES (:Category_Name)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "Categories added successfully<br>";

    // Insert sample shipping methods
    $shipping_methods = [
        [
            'Method_Name' => 'Standard Shipping',
            'Cost' => 5.99,
            'Estimated_Delivery_Time' => '5-7 business days'
        ],
        [
            'Method_Name' => 'Express Shipping',
            'Cost' => 15.99,
            'Estimated_Delivery_Time' => '2-3 business days'
        ],
        [
            'Method_Name' => 'Next Day Delivery',
            'Cost' => 25.99,
            'Estimated_Delivery_Time' => '1 business day'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO Shipping_Method (Method_Name, Cost, Estimated_Delivery_Time) VALUES (:Method_Name, :Cost, :Estimated_Delivery_Time)");
    foreach ($shipping_methods as $method) {
        $stmt->execute($method);
    }
    echo "Shipping methods added successfully<br>";

    // Insert sample payment methods
    $payment_methods = [
        [
            'Method_Name' => 'Credit Card',
            'Provider' => 'Visa/Mastercard',
            'Transaction_Fee' => 0.00
        ],
        [
            'Method_Name' => 'PayPal',
            'Provider' => 'PayPal',
            'Transaction_Fee' => 1.99
        ],
        [
            'Method_Name' => 'Cash on Delivery',
            'Provider' => 'Internal',
            'Transaction_Fee' => 2.99
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO Payment_Method (Method_Name, Provider, Transaction_Fee) VALUES (:Method_Name, :Provider, :Transaction_Fee)");
    foreach ($payment_methods as $method) {
        $stmt->execute($method);
    }
    echo "Payment methods added successfully<br>";

    echo "Database initialization completed successfully!";

} catch (PDOException $e) {
    die("Database initialization failed: " . $e->getMessage());
}
?> 