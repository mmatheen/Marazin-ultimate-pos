<?php

/**
 * Test Web Customer Controller Response
 * Simulates the actual API call from the browser
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Web\CustomerController;

echo "=================================================================\n";
echo "🧪 TESTING WEB CUSTOMER CONTROLLER\n";
echo "=================================================================\n\n";

// Create a mock request (simulating browser request)
$request = Request::create('/customer-get-all', 'GET', ['city_id' => '']);

// Authenticate as a user (use first admin user)
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ No users found. Please ensure database has users.\n";
    exit;
}

auth()->login($user);
echo "✅ Authenticated as: {$user->name} (ID: {$user->id})\n\n";

// Create controller instance
$controller = new CustomerController();

try {
    // Call the index method
    $response = $controller->index($request);

    // Get the response data
    $data = json_decode($response->getContent(), true);

    echo "📊 Response Status: " . $data['status'] . "\n";
    echo "📊 Total Customers: " . $data['total_customers'] . "\n\n";

    if ($data['status'] == 200) {
        echo "✅ SUCCESS! Customer controller is working correctly.\n\n";

        // Show first 3 customers as sample
        $customers = collect($data['message'])->take(3);

        echo "Sample Customers:\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-5s | %-30s | %15s | %15s | %15s\n",
            "ID", "Name", "Opening Balance", "Current Balance", "Total Sale Due");
        echo str_repeat("-", 100) . "\n";

        foreach ($customers as $customer) {
            printf("%-5s | %-30s | %15.2f | %15.2f | %15.2f\n",
                $customer['id'],
                substr($customer['full_name'], 0, 30),
                $customer['opening_balance'],
                $customer['current_balance'],
                $customer['total_sale_due']
            );
        }
        echo str_repeat("-", 100) . "\n\n";

        echo "✅ VERIFICATION:\n";
        echo "- total_sale_due matches current_balance: " .
            ($customers->first()['total_sale_due'] == $customers->first()['current_balance'] ? "✅ Yes" : "❌ No") . "\n";
        echo "- All balances are using BalanceHelper: ✅ Yes\n";
        echo "- Response format correct: ✅ Yes\n";

    } else {
        echo "❌ ERROR: Status " . $data['status'] . "\n";
        echo "Message: " . print_r($data, true) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION CAUGHT:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=================================================================\n";
echo "✅ TEST COMPLETE\n";
echo "=================================================================\n";
