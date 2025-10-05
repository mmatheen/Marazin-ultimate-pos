<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnosticController extends Controller
{
    public function checkSystem(Request $request)
    {
        try {
            // Clear any output buffer
            if (ob_get_level()) {
                ob_clean();
            }
            
            header('Content-Type: application/json');
            
            $diagnostics = [
                'timestamp' => now()->toDateTimeString(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024 . 'MB',
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug'),
                'database' => [],
                'extensions' => [],
                'headers_sent' => headers_sent(),
            ];
            
            // Test database connection
            try {
                DB::connection()->getPdo();
                $diagnostics['database']['connection'] = 'OK';
                $diagnostics['database']['driver'] = DB::connection()->getDriverName();
                
                // Test a simple query
                $productCount = DB::table('products')->count();
                $diagnostics['database']['product_count'] = $productCount;
            } catch (\Exception $e) {
                $diagnostics['database']['connection'] = 'FAILED';
                $diagnostics['database']['error'] = $e->getMessage();
            }
            
            // Check required PHP extensions
            $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json'];
            foreach ($requiredExtensions as $ext) {
                $diagnostics['extensions'][$ext] = extension_loaded($ext) ? 'OK' : 'MISSING';
            }
            
            // Check if output buffering is enabled
            $diagnostics['output_buffering'] = ob_get_level() > 0 ? 'ENABLED' : 'DISABLED';
            $diagnostics['ob_level'] = ob_get_level();
            
            return response()->json([
                'status' => 'success',
                'data' => $diagnostics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Diagnostic check failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'basic_info' => [
                    'php_version' => PHP_VERSION,
                    'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                ]
            ], 500);
        }
    }
}