<?php
/**
 * Simple Database Manager - No Connection Testing
 * Uses basic .env loading without database checks
 */

class SimpleDatabaseManager 
{
    private static $instance = null;
    private $connection = null;
    private $config = [];
    
    private function __construct() 
    {
        $this->loadBasicConfiguration();
    }
    
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadBasicConfiguration() 
    {
        // Simple .env loading without validation
        $this->config = [
            'host' => $this->getEnvValue('DB_HOST', 'localhost'),
            'database' => $this->getEnvValue('DB_DATABASE', ''),
            'username' => $this->getEnvValue('DB_USERNAME', ''),
            'password' => $this->getEnvValue('DB_PASSWORD', ''),
            'port' => $this->getEnvValue('DB_PORT', '3306')
        ];
    }
    
    private function getEnvValue($key, $default = '') 
    {
        // First check environment variables
        if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // Then try to read from .env file if exists
        if (file_exists('.env')) {
            $envContent = file_get_contents('.env');
            if (preg_match("/^{$key}=(.*)$/m", $envContent, $matches)) {
                return trim($matches[1], '"\'');
            }
        }
        
        return $default;
    }
    
    public function getConnection() 
    {
        if ($this->connection === null) {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        }
        
        return $this->connection;
    }
    
    public function getConfig() 
    {
        return $this->config;
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
 * Simple Security Manager - Basic Operations Only
 */
class SimpleSecurityManager 
{
    private $db;
    
    public function __construct($databaseManager) 
    {
        $this->db = $databaseManager;
    }
    
    public function createBackup($tableName) 
    {
        $timestamp = date('Ymd_His');
        $backupTableName = $tableName . '_backup_' . $timestamp;
        
        $sql = "CREATE TABLE `{$backupTableName}` AS SELECT * FROM `{$tableName}`";
        $this->db->getConnection()->exec($sql);
        
        return $backupTableName;
    }
    
    public function confirmAction($message) 
    {
        echo $message . " (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        return trim($line) === 'y' || trim($line) === 'Y';
    }
    
    public function logOperation($message) 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents('ledger_operations.log', $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }
}

?>