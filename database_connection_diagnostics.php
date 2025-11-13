<?php
/**
 * Production Database Connection Diagnostics
 * This script helps diagnose and fix database connection issues in production
 */

echo "=== PRODUCTION DATABASE CONNECTION DIAGNOSTICS ===\n\n";

// Step 1: Check if .env file exists
echo "1. Checking .env file...\n";
if (file_exists('.env')) {
    echo "✅ .env file found\n";
    
    // Read .env file
    $envContent = file_get_contents('.env');
    
    // Check for database configuration
    $dbConfigs = [
        'DB_CONNECTION' => 'Database connection type',
        'DB_HOST' => 'Database host',
        'DB_PORT' => 'Database port',
        'DB_DATABASE' => 'Database name',
        'DB_USERNAME' => 'Database username',
        'DB_PASSWORD' => 'Database password'
    ];
    
    echo "\n2. Checking database configuration in .env...\n";
    $missingConfigs = [];
    
    foreach ($dbConfigs as $config => $description) {
        if (strpos($envContent, $config) !== false) {
            // Extract the value
            if (preg_match("/^{$config}=(.*)$/m", $envContent, $matches)) {
                $value = trim($matches[1]);
                if ($config === 'DB_PASSWORD') {
                    echo "✅ {$config}: [HIDDEN]\n";
                } else {
                    echo "✅ {$config}: {$value}\n";
                }
            }
        } else {
            echo "❌ {$config}: Missing\n";
            $missingConfigs[] = $config;
        }
    }
    
    if (!empty($missingConfigs)) {
        echo "\n⚠️  Missing database configurations:\n";
        foreach ($missingConfigs as $config) {
            echo "   - {$config}\n";
        }
    }
    
} else {
    echo "❌ .env file not found!\n";
    echo "\n🔧 SOLUTION: Create .env file with database configuration:\n";
    echo "   cp .env.example .env\n";
    echo "   # Then edit .env with your database credentials\n";
    exit(1);
}

// Step 3: Test basic PHP PDO availability
echo "\n3. Checking PHP PDO MySQL extension...\n";
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension is available\n";
} else {
    echo "❌ PDO MySQL extension is not loaded\n";
    echo "🔧 SOLUTION: Install PHP PDO MySQL extension\n";
    exit(1);
}

// Step 4: Try to parse .env manually and test connection
echo "\n4. Testing database connection...\n";

// Simple .env parser
function parseEnv($filepath) {
    $config = [];
    if (file_exists($filepath)) {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
                list($key, $value) = explode('=', $line, 2);
                $config[trim($key)] = trim($value, '"\'');
            }
        }
    }
    return $config;
}

$config = parseEnv('.env');

// Check if we have required database config
$required = ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
$hasAllConfig = true;

foreach ($required as $key) {
    if (!isset($config[$key]) || empty($config[$key])) {
        echo "❌ Missing or empty: {$key}\n";
        $hasAllConfig = false;
    }
}

if (!$hasAllConfig) {
    echo "\n🔧 SOLUTION: Update your .env file with correct database credentials\n";
    exit(1);
}

// Try to connect to database
try {
    $host = $config['DB_HOST'] ?? 'localhost';
    $port = $config['DB_PORT'] ?? '3306';
    $dbname = $config['DB_DATABASE'];
    $username = $config['DB_USERNAME'];
    $password = $config['DB_PASSWORD'] ?? '';
    
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    echo "Attempting connection to: {$host}:{$port}/{$dbname} as {$username}\n";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ Database connection successful!\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers LIMIT 1");
    $result = $stmt->fetch();
    echo "✅ Database query test successful - found {$result['count']} customers\n";
    
    echo "\n🎉 DATABASE CONNECTION IS WORKING!\n";
    echo "You can now run the ledger analysis and fix scripts.\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    
    echo "\n🔧 COMMON SOLUTIONS:\n";
    echo "1. Check database credentials in .env file\n";
    echo "2. Ensure MySQL server is running\n";
    echo "3. Verify database name exists\n";
    echo "4. Check user permissions for the database\n";
    echo "5. Verify host/port accessibility\n";
    
    // Try to give more specific advice based on error
    $errorMsg = $e->getMessage();
    
    if (strpos($errorMsg, 'Access denied') !== false) {
        echo "\n🎯 SPECIFIC ISSUE: Invalid username/password\n";
        echo "   - Check DB_USERNAME and DB_PASSWORD in .env\n";
    } elseif (strpos($errorMsg, 'Unknown database') !== false) {
        echo "\n🎯 SPECIFIC ISSUE: Database doesn't exist\n";
        echo "   - Check DB_DATABASE name in .env\n";
        echo "   - Create the database if needed\n";
    } elseif (strpos($errorMsg, 'Connection refused') !== false) {
        echo "\n🎯 SPECIFIC ISSUE: Can't connect to MySQL server\n";
        echo "   - Check if MySQL service is running\n";
        echo "   - Verify DB_HOST and DB_PORT in .env\n";
    }
    
    exit(1);
}

echo "\n=== DIAGNOSTICS COMPLETE ===\n";
?>