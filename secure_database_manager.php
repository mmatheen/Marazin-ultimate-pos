<?php
/**
 * Secure Database Connection Manager for Production
 * Uses Laravel's .env configuration and proper error handling
 */

class SecureDatabaseManager 
{
    private static $instance = null;
    private $connection = null;
    private $config = [];
    
    private function __construct() 
    {
        $this->loadConfiguration();
    }
    
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfiguration() 
    {
        // Check if .env file exists
        if (!file_exists('.env')) {
            throw new Exception("❌ .env file not found! Please create .env file with database configuration.\n" .
                              "🔧 Solution: cp .env.example .env (then edit with your database credentials)");
        }
        
        // Load from Laravel's .env file
        $envContent = file_get_contents('.env');
        $lines = explode("\n", $envContent);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value, '"\'');
            }
        }
        
        $this->config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'database' => $_ENV['DB_DATABASE'] ?? '',
            'username' => $_ENV['DB_USERNAME'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'port' => $_ENV['DB_PORT'] ?? '3306'
        ];
        
        // Validate required configuration
        $required = ['database', 'username'];
        $missing = [];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $missing[] = "DB_" . strtoupper($key);
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("❌ Missing database configuration in .env file: " . implode(', ', $missing) . "\n" .
                              "🔧 Solution: Edit .env file and set these values with your database credentials");
        }
    }
    
    public function getConnection() 
    {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset=utf8mb4";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"
                ];
                
                $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
                
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $this->connection;
    }
    
    public function testConnection() 
    {
        try {
            $conn = $this->getConnection();
            $conn->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getDatabaseInfo() 
    {
        return [
            'host' => $this->config['host'],
            'database' => $this->config['database'],
            'username' => $this->config['username'],
            'password_set' => !empty($this->config['password'])
        ];
    }
    
    public function beginTransaction() 
    {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit() 
    {
        return $this->getConnection()->commit();
    }
    
    public function rollback() 
    {
        return $this->getConnection()->rollback();
    }
}

/**
 * Security and Backup Manager
 */
class SecurityManager 
{
    public static function createBackup($tableName) 
    {
        $db = SecureDatabaseManager::getInstance()->getConnection();
        $backupTable = $tableName . '_backup_' . date('Ymd_His');
        
        try {
            $db->exec("CREATE TABLE {$backupTable} AS SELECT * FROM {$tableName}");
            return $backupTable;
        } catch (Exception $e) {
            throw new Exception("Backup creation failed: " . $e->getMessage());
        }
    }
    
    public static function verifyBackup($originalTable, $backupTable) 
    {
        $db = SecureDatabaseManager::getInstance()->getConnection();
        
        $originalCount = $db->query("SELECT COUNT(*) as count FROM {$originalTable}")->fetch()['count'];
        $backupCount = $db->query("SELECT COUNT(*) as count FROM {$backupTable}")->fetch()['count'];
        
        return $originalCount === $backupCount;
    }
    
    public static function confirmAction($message) 
    {
        echo "\n⚠️  PRODUCTION SAFETY CHECK:\n";
        echo $message . "\n";
        echo "Do you want to proceed? (yes/no): ";
        
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        return strtolower($line) === 'yes';
    }
    
    public static function logAction($action, $details = []) 
    {
        $logFile = 'ledger_operations.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$action}";
        
        if (!empty($details)) {
            $logEntry .= " - " . json_encode($details);
        }
        
        file_put_contents($logFile, $logEntry . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>