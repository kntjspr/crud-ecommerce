# Shoepee Database Design

## 1. Conceptual Design

### Entity-Relationship Diagram (High Level)
```mermaid
graph TB
    subgraph User Management
        Customer
        Employee
        Address
    end
    
    subgraph Product Management
        Product
        Category
        Review
        ProductImage
    end
    
    subgraph Order Processing
        Order
        OrderItem
        Cart
        Payment
        Shipping
    end
    
    subgraph Store Management
        Department
        JobPosition[Job Position]
        Settings
        IssueTracker[Issue Tracker]
    end
    
    %% User Management Relations
    Customer -->|has| Address
    Employee -->|has| Address
    Employee -->|belongs to| Department
    Employee -->|has| JobPosition
    Employee -->|tracks| IssueTracker
    
    %% Product Management Relations
    Product -->|belongs to| Category
    Product -->|has many| ProductImage
    Product -->|has many| Review
    Customer -->|writes| Review
    
    %% Order Processing Relations
    Customer -->|places| Order
    Employee -->|manages| Order
    Order -->|contains| OrderItem
    Product -->|included in| OrderItem
    Customer -->|has| Cart
    Product -->|stored in| Cart
    Order -->|has| Payment
    Order -->|has| Shipping
```

### Detailed Entity Relationships
```mermaid
erDiagram
    %% User Management
    Customer ||--o{ Order : places
    Customer ||--o{ Cart : has
    Customer ||--o{ Review : writes
    Customer ||--|| Customer_Address : has
    
    Employee ||--o{ Order : manages
    Employee ||--|| Employee_Address : has
    Employee ||--|| Department : belongs_to
    Employee ||--|| Job_Position : has
    Employee ||--o| Issue_Tracker : has
    
    %% Product Management
    Product ||--o{ OrderItem : contains
    Product ||--o{ Cart : in
    Product ||--o{ Review : has
    Product ||--|| Category : belongs_to
    Product ||--o{ ProductImage : has
    
    %% Order Processing
    Order ||--o{ OrderItem : contains
    Order ||--o{ Transaction : has
    Order ||--|| Customer : placed_by
    Order ||--o| Employee : managed_by
    Order ||--|| Payment : has
    Order ||--|| Shipping : requires
    
    %% Shipping & Payment
    Payment ||--|| Payment_Method : uses
    Shipping ||--|| Shipping_Method : uses
    Shipping ||--|| Shipping_Address : delivers_to
```

## 2. Logical Design

### Data Structure Diagram
```mermaid
classDiagram
    class Customer {
        +CustomerID: INT
        +Username: VARCHAR
        +FirstName: VARCHAR
        +LastName: VARCHAR
        +Email: VARCHAR
        +Password: VARCHAR
        +PhoneNumber: VARCHAR
        +CustomerAddressID: INT
        +Gender: VARCHAR
        +Birthday: DATETIME
    }
    
    class Employee {
        +EmployeeID: INT
        +FirstName: VARCHAR
        +LastName: VARCHAR
        +PhoneNumber: VARCHAR
        +EmployeeAddressID: INT
        +Gender: VARCHAR
        +Birthday: DATETIME
        +Email: VARCHAR
        +Password: VARCHAR
        +DepartmentID: INT
        +Salary: DECIMAL
        +SSSNumber: VARCHAR
        +PagIBIG: VARCHAR
        +PhilHealth: VARCHAR
        +TIN: VARCHAR
        +IssueTrackerID: INT
        +PositionID: INT
        +IsAdmin: BOOLEAN
        +IsActive: BOOLEAN
    }
    
    class Product {
        +ProductID: INT
        +ProductName: VARCHAR
        +Description: VARCHAR
        +Price: DECIMAL
        +Stock: INT
        +CategoryID: INT
    }
    
    class Order {
        +OrderID: INT
        +CustomerID: INT
        +OrderDate: DATETIME
        +TotalAmount: DECIMAL
        +EmployeeID: INT
    }
    
    Customer "1" -- "0..*" Order
    Employee "1" -- "0..*" Order
    Product "1" -- "0..*" OrderItem
    Order "1" -- "1..*" OrderItem
```

## 3. Physical Design

### Database Schema
```mermaid
classDiagram
    class Customer {
        <<Table>>
        +Customer_ID INT(5) PK
        +Username VARCHAR(50) UK
        +First_Name VARCHAR(50)
        +Last_Name VARCHAR(50)
        +Email VARCHAR(50)
        +Password VARCHAR(255)
        +Phone_Number VARCHAR(20)
        +Customer_Address INT(5) FK
        +Gender VARCHAR(10)
        +Birthday DATETIME
    }
    
    class Employee {
        <<Table>>
        +Employee_ID INT(5) PK
        +First_Name VARCHAR(25)
        +Last_Name VARCHAR(25)
        +Phone_Number VARCHAR(20)
        +Employee_Address INT(5) FK
        +Gender VARCHAR(10)
        +Birthday DATETIME
        +Email VARCHAR(50)
        +Password VARCHAR(255)
        +Department INT(5) FK
        +Salary DECIMAL(10,2)
        +SSS_Number VARCHAR(20)
        +Pag_IBIG VARCHAR(20)
        +PhilHealth VARCHAR(20)
        +TIN VARCHAR(20)
        +Issue_Tracker_ID INT(5) FK
        +Position_ID INT(5) FK
        +Is_Admin BOOLEAN
        +Is_Active BOOLEAN
    }
    
    class Product {
        <<Table>>
        +Product_ID INT(5) PK
        +Product_Name VARCHAR(100)
        +Description VARCHAR(500)
        +Price DECIMAL(10,2)
        +Stock INT(5)
        +Category_ID INT(5) FK
    }
    
    class Order {
        <<Table>>
        +Order_ID INT(5) PK
        +Customer_ID INT(5) FK
        +Order_Date DATETIME
        +Total_Amount DECIMAL(10,2)
        +Employee_ID INT(5) FK
    }
    
    class OrderItem {
        <<Table>>
        +OrderItem_ID INT PK
        +Order_ID INT FK
        +Product_ID INT FK
        +Quantity INT
        +Price DECIMAL(10,2)
    }
    
    class Payment {
        <<Table>>
        +Payment_ID INT PK
        +Order_ID INT FK
        +Payment_Method_ID INT FK
        +Payment_Status VARCHAR(50)
        +Payment_Date DATETIME
        +Amount DECIMAL(10,2)
    }
    
    class Shipping {
        <<Table>>
        +Shipping_ID INT(5) PK
        +Shipping_Status VARCHAR(20)
        +Shipping_Address_ID INT(5) FK
        +Shipping_Method_ID INT(5) FK
    }
    
    Customer "1" -- "0..*" Order
    Employee "1" -- "0..*" Order
    Product "1" -- "0..*" OrderItem
    Order "1" -- "1..*" OrderItem
    Order "1" -- "1" Payment
    Order "1" -- "1" Shipping
```

### Table Structures

[Previous SQL CREATE TABLE statements remain the same...]

### Indexes and Optimization
[Previous content remains the same...]

### Data Types and Constraints
[Previous content remains the same...]

### Security Measures
[Previous content remains the same...]

### Performance Considerations
[Previous content remains the same...]
``` 