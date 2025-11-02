<?php
/**
 * DATABASE BACKUP SCRIPT
 * Creates a backup of your database before making any changes
 */

// Configuration
$host = 'localhost';
$dbname = 'marazin_pos_db';
$username = 'root';
$password = '';
$backup_dir = 'database_backups';

// Create backup directory if it doesn't exist
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

echo "==========================================\n";
echo "DATABASE BACKUP SCRIPT\n";
echo "==========================================\n";
echo "Database: {$dbname}\n";
echo "Backup file: {$backup_file}\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $backup_content = "-- Database Backup: {$dbname}\n";
    $backup_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    $backup_content .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    foreach ($tables as $table) {
        echo "Backing up table: {$table}...\n";
        
        // Get table structure
        $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $backup_content .= "-- Table: {$table}\n";
        $backup_content .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $backup_content .= $create_table['Create Table'] . ";\n\n";
        
        // Get table data
        $stmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $backup_content .= "-- Data for table: {$table}\n";
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                
                $backup_content .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup_content .= "\n";
        }
    }
    
    $backup_content .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Write backup file
    file_put_contents($backup_file, $backup_content);
    
    echo "\n✅ BACKUP COMPLETED SUCCESSFULLY!\n";
    echo "📁 Backup saved to: {$backup_file}\n";
    echo "📊 File size: " . number_format(filesize($backup_file) / 1024, 2) . " KB\n\n";
    
    echo "To restore this backup later, run:\n";
    echo "mysql -u{$username} -p{$password} {$dbname} < {$backup_file}\n\n";
    
} catch (PDOException $e) {
    echo "❌ BACKUP ERROR: " . $e->getMessage() . "\n";
}
?>