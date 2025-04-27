<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function stockHistory()
    {
        // Dummy data for stock history
        $stockHistory = [
            [
                'Action' => 'Sold',
                'SKU' => 'SKU001',
                'Product' => 'Product A',
               
                'Category' => 'Clothing',
                'Location' => 'Store 1',
                'Unit Selling Price' => 50.00,
                'Current stock' => 100,
                'Current Stock Value (By purchase price)' => 4000.00,
                'Current Stock Value (By sale price)' => 5000.00,
                'Potential profit' => 1000.00,
                'Total unit sold' => 200,
                'Total Unit Transferred' => 50,
                'Total Unit Adjusted' => 10,
            ],
            [
                'Action' => 'Purchased',
                'SKU' => 'SKU002',
                'Product' => 'Product B',
                
                'Category' => 'Electronics',
                'Location' => 'Store 2',
                'Unit Selling Price' => 120.00,
                'Current stock' => 50,
                'Current Stock Value (By purchase price)' => 4500.00,
                'Current Stock Value (By sale price)' => 6000.00,
                'Potential profit' => 1500.00,
                'Total unit sold' => 80,
                'Total Unit Transferred' => 20,
                'Total Unit Adjusted' => 5,
            ],
            [
                'Action' => 'Adjusted',
                'SKU' => 'SKU003',
                'Product' => 'Product C',
                
                'Category' => 'Stationery',
                'Location' => 'Store 3',
                'Unit Selling Price' => 10.00,
                'Current stock' => 300,
                'Current Stock Value (By purchase price)' => 2000.00,
                'Current Stock Value (By sale price)' => 3000.00,
                'Potential profit' => 1000.00,
                'Total unit sold' => 150,
                'Total Unit Transferred' => 30,
                'Total Unit Adjusted' => 15,
            ],
            [
                'Action' => 'Transferred',
                'SKU' => 'SKU004',
                'Product' => 'Product D',
              
                'Category' => 'Apparel',
                'Location' => 'Warehouse',
                'Unit Selling Price' => 80.00,
                'Current stock' => 200,
                'Current Stock Value (By purchase price)' => 8000.00,
                'Current Stock Value (By sale price)' => 16000.00,
                'Potential profit' => 8000.00,
                'Total unit sold' => 100,
                'Total Unit Transferred' => 75,
                'Total Unit Adjusted' => 20,
            ],
            [
                'Action' => 'Sold',
                'SKU' => 'SKU005',
                'Product' => 'Product E',
             
                'Category' => 'Home Decor',
                'Location' => 'Store 4',
                'Unit Selling Price' => 25.00,
                'Current stock' => 150,
                'Current Stock Value (By purchase price)' => 3000.00,
                'Current Stock Value (By sale price)' => 3750.00,
                'Potential profit' => 750.00,
                'Total unit sold' => 250,
                'Total Unit Transferred' => 40,
                'Total Unit Adjusted' => 10,
            ],
        ];

        // Return the view with stock history data
        return view('reports.stock_report', compact('stockHistory'));
    }
}