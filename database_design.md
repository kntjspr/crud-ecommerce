# Shoepee Database Design

This document outlines the database design for the Shoepee e-commerce system. The design is critical as it handles sensitive customer data, financial transactions, and inventory management.

## 1. Conceptual Design
High-level view of main entities and their relationships.

```mermaid
erDiagram
    CUSTOMER ||--o{ ORDER : "places"
    CUSTOMER ||--o{ REVIEW : "writes"
    CUSTOMER ||--o{ CART : "has"
    CUSTOMER ||--|| CUSTOMER_ADDRESS : "has"
    EMPLOYEE ||--o{ ORDER : "processes"
    EMPLOYEE ||--|| EMPLOYEE_ADDRESS : "has"
    EMPLOYEE }|--|| DEPARTMENT : "belongs_to"
    EMPLOYEE }|--|| JOB_POSITION : "has"
    EMPLOYEE }|--|| ISSUE_TRACKER : "manages"
    PRODUCT ||--o{ REVIEW : "receives"
    PRODUCT ||--o{ ORDERITEM : "contains"
    PRODUCT ||--o{ PRODUCTIMAGE : "has"
    PRODUCT ||--o{ CART : "in"
    PRODUCT }|--|| CATEGORY : "belongs_to"
    ORDER ||--|{ ORDERITEM : "includes"
    ORDER ||--|| PAYMENT : "has"
    ORDER ||--|| TRANSACTION : "generates"
    SHIPPING ||--|| TRANSACTION : "has"
    SHIPPING ||--|| SHIPPING_ADDRESS : "delivers_to"
    SHIPPING }|--|| SHIPPING_METHOD : "uses"
    PAYMENT_METHOD ||--|| PAYMENT : "uses"
    TRANSACTION ||--|| RECEIPT : "generates"
```

## 2. Logical Design
Entity relationships with attributes.

```mermaid
erDiagram
    CUSTOMER {
        int CustomerID PK
        string Username UK
        string FirstName
        string LastName
        string Email
        string Password
        string PhoneNumber
        int CustomerAddress FK
        string Gender
        datetime Birthday
    }
    EMPLOYEE {
        int EmployeeID PK
        string FirstName
        string LastName
        string PhoneNumber
        int EmployeeAddress FK
        string Gender
        datetime Birthday
        string Email
        string Password
        int Department FK
        decimal Salary
        string SSSNumber
        string PagIBIG
        string PhilHealth
        string TIN
        int IssueTrackerID FK
        int PositionID FK
        boolean IsAdmin
        boolean IsActive
    }
    PRODUCT {
        int ProductID PK
        string ProductName
        string Description
        decimal Price
        int Stock
        int CategoryID FK
    }
    ORDER {
        int OrderID PK
        int CustomerID FK
        datetime OrderDate
        decimal TotalAmount
        int EmployeeID FK
    }
    TRANSACTION {
        int TransactionID PK
        int OrderID FK
        int ShippingID FK
        int ReceiptID FK
        int ProductID FK
        int PaymentID FK
        int Quantity
    }
    SHIPPING {
        int ShippingID PK
        string ShippingStatus
        int ShippingAddressID FK
        int ShippingMethodID FK
    }
```

## 3. Physical Design
Complete database schema with data types and constraints.

```mermaid
erDiagram
    CUSTOMER {
        int Customer_ID "PK"
        varchar50 Username "UK"
        varchar50 First_Name "NOT NULL"
        varchar50 Last_Name "NOT NULL"
        varchar50 Email "NOT NULL"
        varchar255 Password "NOT NULL"
        varchar20 Phone_Number
        int Customer_Address "FK"
        varchar10 Gender
        datetime Birthday
    }
    EMPLOYEE {
        int Employee_ID "PK"
        varchar25 First_Name "NOT NULL"
        varchar25 Last_Name "NOT NULL"
        varchar20 Phone_Number
        int Employee_Address "FK"
        varchar10 Gender
        datetime Birthday
        varchar50 Email
        varchar255 Password "NOT NULL"
        int Department "FK"
        decimal10_2 Salary
        varchar20 SSS_Number
        varchar20 Pag_IBIG
        varchar20 PhilHealth
        varchar20 TIN
        int Issue_Tracker_ID "FK"
        int Position_ID "FK"
        boolean Is_Admin "DEFAULT FALSE"
        boolean Is_Active "DEFAULT TRUE"
    }
    PRODUCT {
        int Product_ID "PK"
        varchar100 Product_Name "NOT NULL"
        varchar500 Description
        decimal10_2 Price "NOT NULL"
        int Stock "NOT NULL"
        int Category_ID "FK"
    }
    CART {
        int Cart_ID "PK"
        int Customer_ID "FK NOT NULL"
        int Product_ID "FK NOT NULL"
        int Quantity "DEFAULT 1"
        timestamp Added_At "DEFAULT CURRENT_TIMESTAMP"
    }
    ORDERITEM {
        int OrderItem_ID "PK"
        int Order_ID "FK NOT NULL"
        int Product_ID "FK NOT NULL"
        int Quantity "NOT NULL"
        decimal10_2 Price "NOT NULL"
    }
    SETTINGS {
        int id "PK"
        varchar100 store_name "DEFAULT 'Shoepee'"
        varchar100 store_email
        varchar20 store_phone
        text store_address
        decimal5_2 tax_rate "DEFAULT 0.00"
        decimal10_2 shipping_fee "DEFAULT 0.00"
        decimal10_2 free_shipping_threshold "DEFAULT 0.00"
        boolean maintenance_mode "DEFAULT FALSE"
        timestamp created_at "DEFAULT CURRENT_TIMESTAMP"
        timestamp updated_at "DEFAULT CURRENT_TIMESTAMP"
    }
```

## Key Features

1. Full employee management system with HR data (SSS, PhilHealth, TIN, PagIBIG)
2. Complete order processing system with order items tracking
3. Multi-address support (Employee, Customer, Shipping addresses as separate entities)
4. Product management with categories and images
5. Review and rating system with 1-5 scale validation
6. Multiple payment methods (Credit Card, E-Wallet, Cash on Delivery)
7. Multiple shipping methods with cost and delivery time estimates
8. Cart system with unique customer-product combinations
9. Store settings management with tax rates and shipping thresholds
10. Comprehensive transaction tracking with receipts
11. Issue tracking system for employees
12. Default admin account system

## Design Principles

1. Referential integrity through foreign key constraints
2. Data normalization (separate address entities)
3. Proper data type selection (VARCHAR lengths optimized)
4. Security features (password hashing)
5. Audit capabilities (timestamps on critical tables)
6. Default values for critical fields
7. Unique constraints where necessary (Username, Email)
8. Check constraints (Rating 1-5)
``` 