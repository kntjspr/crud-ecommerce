# Shoepee Database Design

This document outlines the database design for the Shoepee e-commerce system. The design is critical as it handles sensitive customer data, financial transactions, and inventory management.

## 1. Conceptual Design
High-level view of main entities and their relationships.

```mermaid
erDiagram
    %% Customer Relations
    CUSTOMER ||--o{ ORDER : places
    CUSTOMER ||--o{ REVIEW : writes
    CUSTOMER ||--o{ CART : has
    CUSTOMER ||--|| CUSTOMER_ADDRESS : has_address
    
    %% Employee Relations
    EMPLOYEE ||--o{ ORDER : processes
    EMPLOYEE ||--|| EMPLOYEE_ADDRESS : has_address
    EMPLOYEE }|--|| DEPARTMENT : belongs_to
    EMPLOYEE }|--|| JOB_POSITION : has_position
    EMPLOYEE }|--|| ISSUE_TRACKER : has_tracker
    
    %% Product Relations
    PRODUCT ||--o{ REVIEW : receives
    PRODUCT ||--o{ ORDERITEM : contains
    PRODUCT ||--o{ PRODUCTIMAGE : has_images
    PRODUCT ||--o{ CART : in_cart
    PRODUCT }|--|| CATEGORY : belongs_to
    
    %% Order Relations
    ORDER ||--|{ ORDERITEM : contains
    ORDER ||--|| PAYMENT : has_payment
    ORDER ||--|| TRANSACTION : has_transaction
    
    %% Shipping Relations
    SHIPPING ||--|| TRANSACTION : has_shipping
    SHIPPING ||--|| SHIPPING_ADDRESS : delivers_to
    SHIPPING }|--|| SHIPPING_METHOD : uses_method
    
    %% Payment Relations
    PAYMENT_METHOD ||--|| PAYMENT : uses_method
    PAYMENT ||--|| TRANSACTION : has_payment
    
    %% Transaction Relations
    TRANSACTION ||--|| RECEIPT : has_receipt
    TRANSACTION ||--|| PRODUCT : involves_product
```

## 2. Logical Design
Entity relationships with attributes and constraints.

```mermaid
erDiagram
    %% Independent Tables
    JOB_POSITION {
        Position_ID INT PK "AUTO_INCREMENT"
        Title VARCHAR50 "NOT NULL"
        Description VARCHAR100
    }
    
    DEPARTMENT {
        Department_ID INT PK "AUTO_INCREMENT"
        Department_Name VARCHAR25 "NOT NULL"
        Description VARCHAR100
    }
    
    ISSUE_TRACKER {
        Issue_Tracker_ID INT PK "AUTO_INCREMENT"
        Description VARCHAR100
        Status VARCHAR20
    }
    
    %% Address Tables
    EMPLOYEE_ADDRESS {
        Employee_Address_ID INT PK "AUTO_INCREMENT"
        Street VARCHAR25 "NOT NULL"
        Barangay VARCHAR25 "NOT NULL"
        Town_City VARCHAR25 "NOT NULL"
        Province VARCHAR25 "NOT NULL"
        Region VARCHAR25 "NOT NULL"
        Postal_Code INT4 "NOT NULL"
    }
    
    CUSTOMER_ADDRESS {
        Customer_Address_ID INT PK "AUTO_INCREMENT"
        Street VARCHAR25 "NOT NULL"
        Barangay VARCHAR25 "NOT NULL"
        Town_City VARCHAR25 "NOT NULL"
        Province VARCHAR25 "NOT NULL"
        Region VARCHAR25 "NOT NULL"
        Postal_Code INT4 "NOT NULL"
    }
    
    %% Core Tables
    EMPLOYEE {
        Employee_ID INT PK "AUTO_INCREMENT"
        First_Name VARCHAR25 "NOT NULL"
        Last_Name VARCHAR25 "NOT NULL"
        Phone_Number VARCHAR20
        Employee_Address INT FK
        Gender VARCHAR10
        Birthday DATETIME
        Email VARCHAR50
        Password VARCHAR255 "NOT NULL"
        Department INT FK
        Salary DECIMAL10_2
        SSS_Number VARCHAR20
        Pag_IBIG VARCHAR20
        PhilHealth VARCHAR20
        TIN VARCHAR20
        Issue_Tracker_ID INT FK
        Position_ID INT FK
        Is_Admin BOOLEAN "DEFAULT FALSE"
        Is_Active BOOLEAN "DEFAULT TRUE"
    }
    
    CUSTOMER {
        Customer_ID INT PK "AUTO_INCREMENT"
        Username VARCHAR50 "NOT NULL UNIQUE"
        First_Name VARCHAR50 "NOT NULL"
        Last_Name VARCHAR50 "NOT NULL"
        Email VARCHAR50 "NOT NULL"
        Password VARCHAR255 "NOT NULL"
        Phone_Number VARCHAR20
        Customer_Address INT FK
        Gender VARCHAR10
        Birthday DATETIME
    }
```

## 3. Physical Design
Complete database schema with relationships and constraints.

```mermaid
erDiagram
    %% Core Product Tables
    CATEGORY ||--o{ PRODUCT : has
    PRODUCT ||--o{ PRODUCTIMAGE : has
    PRODUCT ||--o{ ORDERITEM : contains
    PRODUCT ||--o{ CART : contains
    PRODUCT ||--o{ REVIEW : has
    
    CATEGORY {
        Category_ID INT PK "AUTO_INCREMENT"
        Category_Name VARCHAR100 "NOT NULL"
    }
    
    PRODUCT {
        Product_ID INT PK "AUTO_INCREMENT"
        Product_Name VARCHAR100 "NOT NULL"
        Description VARCHAR500
        Price DECIMAL10_2 "NOT NULL"
        Stock INT5 "NOT NULL"
        Category_ID INT FK
    }
    
    PRODUCTIMAGE {
        Image_ID INT PK "AUTO_INCREMENT"
        Product_ID INT FK "NOT NULL"
        Image_Path VARCHAR255 "NOT NULL"
        Created_At TIMESTAMP "DEFAULT CURRENT_TIMESTAMP"
    }
    
    %% Order Processing Tables
    CUSTOMER ||--o{ ORDER : places
    EMPLOYEE ||--o{ ORDER : processes
    ORDER ||--|{ ORDERITEM : contains
    ORDER ||--|| PAYMENT : has
    ORDER ||--|| TRANSACTION : has
    
    ORDER {
        Order_ID INT5 PK "AUTO_INCREMENT"
        Customer_ID INT5 FK "NOT NULL"
        Order_Date DATETIME "NOT NULL"
        Total_Amount DECIMAL10_2 "NOT NULL"
        Employee_ID INT5 FK
    }
    
    ORDERITEM {
        OrderItem_ID INT PK "AUTO_INCREMENT"
        Order_ID INT FK "NOT NULL"
        Product_ID INT FK "NOT NULL"
        Quantity INT "NOT NULL"
        Price DECIMAL10_2 "NOT NULL"
    }
    
    %% Payment Processing Tables
    PAYMENT_METHOD ||--o{ PAYMENT : used_by
    PAYMENT ||--|| TRANSACTION : has
    
    PAYMENT_METHOD {
        Payment_Method_ID INT5 PK "AUTO_INCREMENT"
        Method_Name VARCHAR100 "NOT NULL"
        Provider VARCHAR100 "NOT NULL"
        Transaction_Fee DECIMAL10_2
    }
    
    PAYMENT {
        Payment_ID INT PK "AUTO_INCREMENT"
        Order_ID INT FK
        Payment_Method_ID INT FK
        Payment_Status VARCHAR50 "DEFAULT 'Pending'"
        Payment_Date DATETIME
        Amount DECIMAL10_2 "NOT NULL"
    }
    
    %% Shipping Tables
    SHIPPING_METHOD ||--o{ SHIPPING : used_by
    SHIPPING_ADDRESS ||--o{ SHIPPING : delivers_to
    SHIPPING ||--|| TRANSACTION : has
    
    SHIPPING_METHOD {
        Shipping_Method_ID INT PK "AUTO_INCREMENT"
        Method_Name VARCHAR50 "NOT NULL UNIQUE"
        Cost DECIMAL10_2
        Estimated_Delivery_Time VARCHAR50
    }
    
    SHIPPING_ADDRESS {
        Shipping_Address_ID INT5 PK "AUTO_INCREMENT"
        Street VARCHAR25 "NOT NULL"
        Barangay VARCHAR25 "NOT NULL"
        Town_City VARCHAR25 "NOT NULL"
        Province VARCHAR25 "NOT NULL"
        Region VARCHAR10 "NOT NULL"
        Postal_Code INT4 "NOT NULL"
    }
    
    SHIPPING {
        Shipping_ID INT5 PK "AUTO_INCREMENT"
        Shipping_Status VARCHAR20
        Shipping_Address_ID INT5 FK
        Shipping_Method_ID INT5 FK
    }
    
    %% Transaction and Receipt Tables
    TRANSACTION ||--|| RECEIPT : generates
    
    TRANSACTION {
        Transaction_ID INT5 PK "AUTO_INCREMENT"
        Order_ID INT5 FK
        Shipping_ID INT5 FK
        Receipt_ID INT5 FK
        Product_ID INT5 FK
        Payment_ID INT5 FK
        Quantity INT5 "DEFAULT 1"
    }
    
    RECEIPT {
        Receipt_ID INT5 PK "AUTO_INCREMENT"
        Tax_Amount DECIMAL10_2
        Total_Amount DECIMAL10_2
        Type VARCHAR20
    }
    
    %% Customer Interaction Tables
    CUSTOMER ||--o{ CART : has
    CUSTOMER ||--o{ REVIEW : writes
    
    CART {
        Cart_ID INT PK "AUTO_INCREMENT"
        Customer_ID INT FK "NOT NULL"
        Product_ID INT FK "NOT NULL"
        Quantity INT "NOT NULL DEFAULT 1"
        Added_At TIMESTAMP "DEFAULT CURRENT_TIMESTAMP"
        unique_customer_product_idx VARCHAR "UNIQUE(Customer_ID,Product_ID)"
    }
    
    REVIEW {
        Review_ID INT5 PK "AUTO_INCREMENT"
        Product_ID INT5 FK
        Customer_ID INT5 FK
        Rating INT1 "NOT NULL CHECK(1-5)"
        Review_Text VARCHAR500
        Review_Date DATETIME "NOT NULL"
    }
    
    %% System Configuration
    SETTINGS {
        id INT PK "AUTO_INCREMENT"
        store_name VARCHAR100 "NOT NULL DEFAULT 'Shoepee'"
        store_email VARCHAR100
        store_phone VARCHAR20
        store_address TEXT
        tax_rate DECIMAL5_2 "DEFAULT 0.00"
        shipping_fee DECIMAL10_2 "DEFAULT 0.00"
        free_shipping_threshold DECIMAL10_2 "DEFAULT 0.00"
        maintenance_mode BOOLEAN "DEFAULT FALSE"
        created_at TIMESTAMP "DEFAULT CURRENT_TIMESTAMP"
        updated_at TIMESTAMP "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    }
```

## Key Features

1. Full employee management system with HR data (SSS, PhilHealth, TIN, PagIBIG)
2. Complete order processing system with order items tracking
3. Multi-address support (Employee, Customer, Shipping addresses as separate entities)
4. Product management with categories and images (ON DELETE CASCADE for images)
5. Review and rating system with 1-5 scale validation
6. Multiple payment methods (Credit Card, E-Wallet, Cash on Delivery) with transaction fees
7. Multiple shipping methods with cost and delivery time estimates
8. Cart system with unique customer-product combinations
9. Store settings management with tax rates and shipping thresholds
10. Comprehensive transaction tracking with receipts
11. Issue tracking system for employees
12. Default admin account system with secure password hashing

## Design Principles

1. Referential integrity enforced through foreign key constraints
2. Third normal form (3NF) with separate address entities
3. Precise data type selection with optimized lengths
4. Security features (password hashing, 255 chars for hashed passwords)
5. Audit capabilities (timestamps with automatic updates)
6. Appropriate default values for critical fields
7. Unique constraints (Username, Email, Shipping Method names)
8. Check constraints (Rating 1-5 validation)
9. Cascade deletes where appropriate (ProductImage)
10. Proper indexing on foreign keys and unique constraints
11. Data validation through NOT NULL constraints
12. Decimal precision for financial data (10,2)
``` 