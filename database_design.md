# Shoepee Database Design

## 1. Conceptual Design

### Main Entities and Relationships

#### User Management
- **Customer** (manages their own profile, places orders)
- **Employee** (manages store operations)
- **Address** (locations for customers, employees, shipping)

#### Product Management
- **Product** (main product information)
- **Category** (product categorization)
- **Review** (customer feedback)
- **ProductImage** (product visuals)

#### Order Processing
- **Order** (main order information)
- **OrderItem** (individual items in order)
- **Cart** (temporary storage for items)
- **Payment** (payment information)
- **Shipping** (delivery information)

#### Store Management
- **Department** (organizational structure)
- **Job_Position** (employee roles)
- **Settings** (store configuration)
- **Issue_Tracker** (system issues)

### Key Relationships
- Customer places Orders
- Employee manages Orders
- Product belongs to Category
- Order contains OrderItems
- Customer writes Reviews
- Product has ProductImages
- Employee belongs to Department
- Employee has Job_Position

## 2. Logical Design

### Entity Sets and Attributes

#### User Entities
1. Customer
   - CustomerID (identifier)
   - Username (unique)
   - Name (first, last)
   - Contact (email, phone)
   - Authentication
   - Demographics

2. Employee
   - EmployeeID (identifier)
   - Name (first, last)
   - Contact (email, phone)
   - Authentication
   - Position
   - Department
   - Government IDs
   - Status

3. Address Types
   - AddressID (identifier)
   - Location Details
   - Region
   - Postal Info

#### Product Entities
1. Product
   - ProductID (identifier)
   - Basic Info
   - Pricing
   - Inventory
   - Category

2. Category
   - CategoryID (identifier)
   - Name

3. Review
   - ReviewID (identifier)
   - Rating
   - Content
   - Timestamp

#### Transaction Entities
1. Order
   - OrderID (identifier)
   - Customer
   - Employee
   - Timing
   - Amount

2. Payment
   - PaymentID (identifier)
   - Method
   - Status
   - Amount
   - Timing

3. Shipping
   - ShippingID (identifier)
   - Method
   - Status
   - Address

### Relationships and Constraints
- One-to-Many: Customer to Orders
- One-to-Many: Category to Products
- Many-to-Many: Products to Orders (via OrderItems)
- One-to-One: Order to Payment
- One-to-One: Employee to Department

## 3. Physical Design

### Table Structures

#### User Tables
```sql
CREATE TABLE Customer (
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
);

CREATE TABLE Employee (
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
);
```

#### Product Tables
```sql
CREATE TABLE Product (
    Product_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
    Product_Name VARCHAR(100) NOT NULL,
    Description VARCHAR(500),
    Price DECIMAL(10,2) NOT NULL,
    Stock INT(5) NOT NULL,
    Category_ID INT(5),
    FOREIGN KEY (Category_ID) REFERENCES Category(Category_ID)
);

CREATE TABLE Category (
    Category_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
    Category_Name VARCHAR(100) NOT NULL
);

CREATE TABLE ProductImage (
    Image_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
    Product_ID INT(5) NOT NULL,
    Image_Path VARCHAR(255) NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID) ON DELETE CASCADE
);
```

#### Transaction Tables
```sql
CREATE TABLE `Order` (
    Order_ID INT(5) PRIMARY KEY AUTO_INCREMENT,
    Customer_ID INT(5),
    Order_Date DATETIME NOT NULL,
    Total_Amount DECIMAL(10,2) NOT NULL,
    Employee_ID INT(5),
    FOREIGN KEY (Customer_ID) REFERENCES Customer(Customer_ID),
    FOREIGN KEY (Employee_ID) REFERENCES Employee(Employee_ID)
);

CREATE TABLE OrderItem (
    OrderItem_ID INT PRIMARY KEY AUTO_INCREMENT,
    Order_ID INT NOT NULL,
    Product_ID INT NOT NULL,
    Quantity INT NOT NULL,
    Price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (Order_ID) REFERENCES `Order`(Order_ID),
    FOREIGN KEY (Product_ID) REFERENCES Product(Product_ID)
);
```

### Indexes and Optimization
1. Primary Keys
   - Auto-incrementing integers for all IDs
   - Clustered indexes on primary keys

2. Foreign Keys
   - Index on all foreign key columns
   - Cascading deletes where appropriate

3. Performance Indexes
   - Username in Customer table
   - Email in Customer and Employee tables
   - Product_Name in Product table
   - Category_ID in Product table

### Data Types and Constraints
1. Numeric Types
   - INT(5) for IDs
   - DECIMAL(10,2) for monetary values
   - INT for quantities

2. String Types
   - VARCHAR(255) for passwords (hashed)
   - VARCHAR(100) for names
   - VARCHAR(500) for descriptions
   - VARCHAR(20) for phone numbers

3. Date/Time Types
   - DATETIME for specific points in time
   - TIMESTAMP for record tracking

4. Boolean Types
   - BOOLEAN for flags (Is_Admin, Is_Active)

### Security Measures
1. Password Storage
   - Hashed using PHP's password_hash()
   - 255 characters to accommodate future hash algorithms

2. Data Integrity
   - Foreign key constraints
   - NOT NULL constraints on critical fields
   - UNIQUE constraints where needed

3. Audit Trails
   - Created_At timestamps
   - Updated_At timestamps where relevant

### Performance Considerations
1. Table Partitioning
   - Consider partitioning large tables (Orders, Products) by date

2. Query Optimization
   - Indexed columns for frequent searches
   - Optimized joins through proper indexing

3. Data Types
   - Appropriate field lengths to minimize storage
   - Efficient numeric types for calculations
``` 