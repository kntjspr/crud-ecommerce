# Shoepee Database Design

This document outlines the database design for the Shoepee e-commerce system. The design is critical as it handles sensitive customer data, financial transactions, and inventory management.

## 1. Conceptual Design
High-level view of main entities and their relationships.

```mermaid
erDiagram
    CUSTOMER ||--o{ ORDER : places
    CUSTOMER ||--o{ REVIEW : writes
    CUSTOMER ||--o{ CART : has
    CUSTOMER ||--|| CUSTOMER_ADDRESS : has
    EMPLOYEE ||--o{ ORDER : processes
    EMPLOYEE ||--|| EMPLOYEE_ADDRESS : has
    EMPLOYEE }|--|| DEPARTMENT : belongs_to
    EMPLOYEE }|--|| JOB_POSITION : has
    EMPLOYEE }|--|| ISSUE_TRACKER : manages
    PRODUCT ||--o{ REVIEW : receives
    PRODUCT ||--o{ ORDERITEM : contains
    PRODUCT ||--o{ PRODUCTIMAGE : has
    PRODUCT ||--o{ CART : in
    PRODUCT }|--|| CATEGORY : belongs_to
    ORDER ||--|{ ORDERITEM : includes
    ORDER ||--|| PAYMENT : has
    ORDER ||--|| TRANSACTION : generates
    SHIPPING ||--|| TRANSACTION : has
    SHIPPING ||--|| SHIPPING_ADDRESS : delivers_to
    SHIPPING }|--|| SHIPPING_METHOD : uses
    PAYMENT_METHOD ||--|| PAYMENT : uses
    TRANSACTION ||--|| RECEIPT : generates
```

## 2. Logical Design
Entity relationships with attributes.

```mermaid
erDiagram
    CUSTOMER {
        CustomerID PK
        Username UK
        FirstName
        LastName
        Email
        Password
        PhoneNumber
        CustomerAddress FK
        Gender
        Birthday
    }
    EMPLOYEE {
        EmployeeID PK
        FirstName
        LastName
        PhoneNumber
        EmployeeAddress FK
        Gender
        Birthday
        Email
        Password
        Department FK
        Salary
        SSSNumber
        PagIBIG
        PhilHealth
        TIN
        IssueTrackerID FK
        PositionID FK
        IsAdmin
        IsActive
    }
    PRODUCT {
        ProductID PK
        ProductName
        Description
        Price
        Stock
        CategoryID FK
    }
    ORDER {
        OrderID PK
        CustomerID FK
        OrderDate
        TotalAmount
        EmployeeID FK
    }
    TRANSACTION {
        TransactionID PK
        OrderID FK
        ShippingID FK
        ReceiptID FK
        ProductID FK
        PaymentID FK
        Quantity
    }
    SHIPPING {
        ShippingID PK
        ShippingStatus
        ShippingAddressID FK
        ShippingMethodID FK
    }
```

## 3. Physical Design
Complete database schema with data types and constraints.

```mermaid
erDiagram
    CUSTOMER {
        INT5 Customer_ID PK
        VARCHAR50 Username UK
        VARCHAR50 First_Name "NOT NULL"
        VARCHAR50 Last_Name "NOT NULL"
        VARCHAR50 Email "NOT NULL"
        VARCHAR255 Password "NOT NULL"
        VARCHAR20 Phone_Number
        INT5 Customer_Address FK
        VARCHAR10 Gender
        DATETIME Birthday
    }
    EMPLOYEE {
        INT5 Employee_ID PK
        VARCHAR25 First_Name "NOT NULL"
        VARCHAR25 Last_Name "NOT NULL"
        VARCHAR20 Phone_Number
        INT5 Employee_Address FK
        VARCHAR10 Gender
        DATETIME Birthday
        VARCHAR50 Email
        VARCHAR255 Password "NOT NULL"
        INT5 Department FK
        DECIMAL10_2 Salary
        VARCHAR20 SSS_Number
        VARCHAR20 Pag_IBIG
        VARCHAR20 PhilHealth
        VARCHAR20 TIN
        INT5 Issue_Tracker_ID FK
        INT5 Position_ID FK
        BOOLEAN Is_Admin "DEFAULT FALSE"
        BOOLEAN Is_Active "DEFAULT TRUE"
    }
    PRODUCT {
        INT5 Product_ID PK
        VARCHAR100 Product_Name "NOT NULL"
        VARCHAR500 Description
        DECIMAL10_2 Price "NOT NULL"
        INT5 Stock "NOT NULL"
        INT5 Category_ID FK
    }
    CART {
        INT Cart_ID PK
        INT Customer_ID FK "NOT NULL"
        INT Product_ID FK "NOT NULL"
        INT Quantity "DEFAULT 1"
        TIMESTAMP Added_At "DEFAULT CURRENT_TIMESTAMP"
    }
    ORDERITEM {
        INT OrderItem_ID PK
        INT Order_ID FK "NOT NULL"
        INT Product_ID FK "NOT NULL"
        INT Quantity "NOT NULL"
        DECIMAL10_2 Price "NOT NULL"
    }
    SETTINGS {
        INT id PK
        VARCHAR100 store_name "DEFAULT 'Shoepee'"
        VARCHAR100 store_email
        VARCHAR20 store_phone
        TEXT store_address
        DECIMAL5_2 tax_rate "DEFAULT 0.00"
        DECIMAL10_2 shipping_fee "DEFAULT 0.00"
        DECIMAL10_2 free_shipping_threshold "DEFAULT 0.00"
        BOOLEAN maintenance_mode "DEFAULT FALSE"
        TIMESTAMP created_at "DEFAULT CURRENT_TIMESTAMP"
        TIMESTAMP updated_at "DEFAULT CURRENT_TIMESTAMP"
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