# 🔧 PRODUCTION DATABASE CONNECTION FIX GUIDE

## 🚨 **ISSUE**: Database connection failed in production

Based on your error message:
```
❌ Error: Failed to connect to database. Please check your .env configuration.
```

## 🔍 **STEP-BY-STEP DIAGNOSIS**

### **1. Run Diagnostics Script**
```bash
php database_connection_diagnostics.php
```
This will check your .env file and test the database connection.

### **2. Check .env File Exists**
```bash
ls -la .env
```
If missing, copy from example:
```bash
cp .env.example .env
```

### **3. Verify Database Configuration**
Edit your `.env` file and ensure these values are correct:
```bash
# Production Database Settings
DB_CONNECTION=mysql
DB_HOST=localhost           # or your database server IP
DB_PORT=3306               # default MySQL port
DB_DATABASE=your_production_db_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### **4. Common Production Issues & Solutions**

#### **Issue A: Wrong Database Credentials**
```bash
# Test connection manually
mysql -u your_username -p your_database
```

#### **Issue B: Database Doesn't Exist**
```bash
# Create database if needed
mysql -u root -p
CREATE DATABASE your_production_db_name;
GRANT ALL PRIVILEGES ON your_production_db_name.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
```

#### **Issue C: PHP Extensions Missing**
```bash
# Check if PDO MySQL is installed
php -m | grep pdo
php -m | grep mysql
```

#### **Issue D: Permissions Issue**
```bash
# Check file permissions
chmod 644 .env
chown www-data:www-data .env  # if using Apache/Nginx
```

## ⚡ **QUICK FIXES FOR COMMON SCENARIOS**

### **Scenario 1: Fresh Server Setup**
```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit database settings
nano .env

# 3. Test connection
php database_connection_diagnostics.php

# 4. Run analysis
php production_safe_analysis.php
```

### **Scenario 2: Shared Hosting**
```bash
# Check hosting control panel for:
# - Database name (usually prefixed with username)
# - Database username (usually prefixed)
# - Database password
# - Database host (might not be localhost)

# Example for shared hosting:
DB_HOST=localhost
DB_DATABASE=username_dbname
DB_USERNAME=username_dbuser
DB_PASSWORD=your_password
```

### **Scenario 3: Cloud Server (AWS/DigitalOcean)**
```bash
# Check cloud provider dashboard for:
# - RDS endpoint (if using managed database)
# - Security groups/firewall rules
# - Database credentials

# Example for RDS:
DB_HOST=your-rds-endpoint.amazonaws.com
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 🎯 **AFTER FIXING CONNECTION**

Once database connection works:

### **1. Run Analysis**
```bash
php production_safe_analysis.php
```

### **2. Review Results**
```bash
# Check generated report
ls -la ledger_analysis_*.json
```

### **3. Apply Fixes (if needed)**
```bash
# Test first
php production_safe_fix.php --dry-run

# Apply with backups
php production_safe_fix.php
```

## 📞 **NEED HELP?**

### **Run This Command for Full Diagnostics:**
```bash
php database_connection_diagnostics.php
```

### **Check Laravel Connection:**
```bash
php artisan tinker
DB::connection()->getPdo();
```

### **Test Raw Connection:**
```bash
php -r "
\$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'pass');
echo 'Connection OK';
"
```

---

**Once database connection is fixed, your ledger scripts will work perfectly!** 🎉