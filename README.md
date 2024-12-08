# Shoepee - Online Shoe Store

Final project for Fundamentals of Database Systems - Team Ciderella

## Team Members
- Kent Jasper C. Sisi
- Harvie C. Babuyo
- Precious Gamalo
- Richter Anthony Yap
- Thomas Gabriel Martinez

## Overview
Shoepee is a comprehensive e-commerce platform specializing in footwear. The system includes both customer-facing features for shopping and administrative tools for managing the store.

[Insert Screenshot of Homepage]

## Features

### Customer Features
- User registration and authentication
- Product browsing with search and filtering
- Shopping cart management
- Secure checkout process
- Order tracking
- Profile management

### Admin Features
- Product management (CRUD operations)
- Order management
- Employee management
- Sales tracking and reporting
- Store settings configuration
- Database maintenance tools

## Technology Stack
- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5.3
- HTML5/CSS3
- JavaScript

## Installation

1. Clone the repository:

```bash
git clone https://github.com/kntjspr/crud-ecommerce.git
```

2. Set up your web server (e.g., XAMPP, WAMP) and place the files in the web root directory.

3. Create the database:
   - Navigate to `database_reset.php` in your browser
   - Click "Initialize Database" to set up the database structure
   - The system will create all necessary tables

4. Configure database connection:
   - Open `config/database.php`
   - Update the database credentials if needed:
     ```php
     $host = 'localhost';
     $username = 'root';
     $password = '';
     $database = 'shoepee_db';
     ```

5. Set up initial admin account:
   - Use the employee registration page
   - Default admin credentials will be created

## Database Structure

### Entity Relationship Diagram
```mermaid
erDiagram
    Customer ||--o{ Order : places
    Customer ||--o{ Cart : has
    Customer ||--o{ Review : writes
    Customer ||--|| Customer_Address : has
    Customer {
        int Customer_ID PK
        varchar Username UK
        varchar First_Name
        varchar Last_Name
        varchar Email
        varchar Password
        varchar Phone_Number
        int Customer_Address FK
        varchar Gender
        datetime Birthday
    }

    Employee ||--o{ Order : manages
    Employee ||--|| Employee_Address : has
    Employee ||--|| Department : belongs_to
    Employee ||--|| Job_Position : has
    Employee ||--o| Issue_Tracker : has
    Employee {
        int Employee_ID PK
        varchar First_Name
        varchar Last_Name
        varchar Email
        varchar Password
        int Employee_Address FK
        int Department FK
        int Position_ID FK
        int Issue_Tracker_ID FK
        boolean Is_Admin
        boolean Is_Active
    }

    Product ||--o{ OrderItem : contains
    Product ||--o{ Cart : in
    Product ||--o{ Review : has
    Product ||--|| Category : belongs_to
    Product ||--o{ ProductImage : has
    Product {
        int Product_ID PK
        varchar Product_Name
        varchar Description
        decimal Price
        int Stock
        int Category_ID FK
    }

    Category {
        int Category_ID PK
        varchar Category_Name
    }

    Order ||--o{ OrderItem : contains
    Order ||--o{ Transaction : has
    Order ||--|| Customer : placed_by
    Order ||--o| Employee : managed_by
    Order {
        int Order_ID PK
        int Customer_ID FK
        int Employee_ID FK
        datetime Order_Date
        decimal Total_Amount
    }

    OrderItem {
        int OrderItem_ID PK
        int Order_ID FK
        int Product_ID FK
        int Quantity
        decimal Price
    }

    Payment ||--|| Payment_Method : uses
    Payment ||--|| Order : belongs_to
    Payment {
        int Payment_ID PK
        int Order_ID FK
        int Payment_Method_ID FK
        varchar Payment_Status
        datetime Payment_Date
        decimal Amount
    }

    Transaction {
        int Transaction_ID PK
        int Order_ID FK
        int Product_ID FK
        int Shipping_ID FK
        int Receipt_ID FK
        int Payment_ID FK
        int Quantity
    }

    Shipping ||--|| Shipping_Method : uses
    Shipping ||--|| Shipping_Address : delivers_to
    Shipping {
        int Shipping_ID PK
        varchar Shipping_Status
        int Shipping_Address_ID FK
        int Shipping_Method_ID FK
    }

    Cart {
        int Cart_ID PK
        int Customer_ID FK
        int Product_ID FK
        int Quantity
        timestamp Added_At
    }

    Settings {
        int id PK
        varchar store_name
        varchar store_email
        varchar store_phone
        text store_address
        decimal tax_rate
        decimal shipping_fee
        decimal free_shipping_threshold
        boolean maintenance_mode
    }

    ProductImage {
        int Image_ID PK
        int Product_ID FK
        varchar Image_Path
        timestamp Created_At
    }

    Review {
        int Review_ID PK
        int Product_ID FK
        int Customer_ID FK
        int Rating
        varchar Review_Text
        datetime Review_Date
    }
```

### Main Tables
1. **Users**
   - Customer
   - Employee
   - Authentication data

2. **Products**
   - Product details
   - Categories
   - Images
   - Stock management

3. **Orders**
   - Order information
   - Order items
   - Payment details
   - Shipping information

4. **System**
   - Store settings
   - Payment methods
   - Shipping methods

## Screenshots
[Insert key screenshots of the system]
- Homepage
- Product Listing
- Shopping Cart
- Admin Dashboard
- Order Management
- Product Management

## Contributing
This project is part of an academic requirement. While we appreciate feedback, we are not accepting direct contributions at this time.

## License
This project is created for educational purposes. All rights reserved.

## Acknowledgments
Special thanks to our instructor and the Fundamentals of Database Systems course for guiding us through this project.
