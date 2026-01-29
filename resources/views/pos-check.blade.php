<!DOCTYPE html>
<html>
<head>
    <title>POS System Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 5px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
        }
        h1 {
            color: #333;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <h1>🔍 POS System Status Check</h1>

    <div class="check-item success">
        <strong>✅ Feature Flag:</strong> USE_MODULAR_POS =
        <?php echo env('USE_MODULAR_POS', 'not set') ? 'true (ENABLED)' : 'false (DISABLED)'; ?>
    </div>

    <div class="check-item info">
        <strong>📁 View Selected:</strong>
        <?php
            $useModular = env('USE_MODULAR_POS', true);
            echo $useModular ? 'sell.pos_modular (NEW SYSTEM)' : 'sell.pos (OLD SYSTEM)';
        ?>
    </div>

    <div class="check-item <?php echo file_exists(resource_path('views/sell/pos_modular.blade.php')) ? 'success' : 'error'; ?>">
        <strong>📄 Modular View File:</strong>
        <?php
            if (file_exists(resource_path('views/sell/pos_modular.blade.php'))) {
                echo '✅ EXISTS at resources/views/sell/pos_modular.blade.php';
            } else {
                echo '❌ MISSING - File not found!';
            }
        ?>
    </div>

    <div class="check-item <?php echo file_exists(public_path('build/assets/main-3065d9f1.js')) ? 'success' : 'error'; ?>">
        <strong>📦 Built Assets:</strong>
        <?php
            if (file_exists(public_path('build/manifest.json'))) {
                $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
                $posMain = isset($manifest['resources/js/pos/main.js']);
                if ($posMain) {
                    echo '✅ Modular POS assets compiled';
                } else {
                    echo '⚠️ Assets compiled but pos/main.js not found in manifest';
                }
            } else {
                echo '❌ Build manifest not found - Run: npm run build';
            }
        ?>
    </div>

    <div class="check-item info">
        <strong>🔄 Next Steps:</strong>
        <ol>
            <li>Clear your browser cache (Ctrl+F5)</li>
            <li>Navigate to: <a href="/pos-create">/pos-create</a></li>
            <li>Open browser console (F12)</li>
            <li>Look for: <code>"🚀 Initializing Modular POS System..."</code></li>
        </ol>
    </div>

    <div class="check-item info">
        <strong>🔧 Troubleshooting:</strong>
        <div class="code">
            # If issues occur, rollback instantly:
            # Edit .env:
            USE_MODULAR_POS=false

            # Then:
            php artisan config:clear

            # Refresh browser
        </div>
    </div>

    <hr style="margin: 30px 0;">

    <div style="text-align: center;">
        <a href="/pos-create" style="display: inline-block; padding: 15px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 18px;">
            🚀 Open POS System
        </a>
    </div>
</body>
</html>
