<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExpenseParentCategory;
use App\Models\ExpenseSubCategory;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Parent Categories
        $parentCategories = [
            [
                'expenseParentCatergoryName' => 'Office Supplies',
                'description' => 'General office supplies and stationery'
            ],
            [
                'expenseParentCatergoryName' => 'Equipment',
                'description' => 'Office equipment and machinery'
            ],
            [
                'expenseParentCatergoryName' => 'Utilities',
                'description' => 'Electricity, water, internet, phone bills'
            ],
            [
                'expenseParentCatergoryName' => 'Marketing',
                'description' => 'Advertising and promotional expenses'
            ],
            [
                'expenseParentCatergoryName' => 'Travel',
                'description' => 'Business travel and transportation'
            ],
            [
                'expenseParentCatergoryName' => 'Professional Services',
                'description' => 'Legal, accounting, consulting fees'
            ],
            [
                'expenseParentCatergoryName' => 'Maintenance',
                'description' => 'Repairs and maintenance costs'
            ]
        ];

        foreach ($parentCategories as $category) {
            ExpenseParentCategory::firstOrCreate(
                ['expenseParentCatergoryName' => $category['expenseParentCatergoryName']],
                $category
            );
        }

        // Get created parent categories
        $officeSupplies = ExpenseParentCategory::where('expenseParentCatergoryName', 'Office Supplies')->first();
        $equipment = ExpenseParentCategory::where('expenseParentCatergoryName', 'Equipment')->first();
        $utilities = ExpenseParentCategory::where('expenseParentCatergoryName', 'Utilities')->first();
        $marketing = ExpenseParentCategory::where('expenseParentCatergoryName', 'Marketing')->first();
        $travel = ExpenseParentCategory::where('expenseParentCatergoryName', 'Travel')->first();
        $professional = ExpenseParentCategory::where('expenseParentCatergoryName', 'Professional Services')->first();
        $maintenance = ExpenseParentCategory::where('expenseParentCatergoryName', 'Maintenance')->first();

        // Create Sub Categories
        $subCategories = [
            // Office Supplies
            [
                'main_expense_category_id' => $officeSupplies->id,
                'subExpenseCategoryname' => 'Stationery',
                'subExpenseCategoryCode' => 'OS001',
                'description' => 'Pens, papers, notebooks, etc.'
            ],
            [
                'main_expense_category_id' => $officeSupplies->id,
                'subExpenseCategoryname' => 'Printing',
                'subExpenseCategoryCode' => 'OS002',
                'description' => 'Printer ink, toner, paper'
            ],
            
            // Equipment
            [
                'main_expense_category_id' => $equipment->id,
                'subExpenseCategoryname' => 'Computer Hardware',
                'subExpenseCategoryCode' => 'EQ001',
                'description' => 'Computers, laptops, accessories'
            ],
            [
                'main_expense_category_id' => $equipment->id,
                'subExpenseCategoryname' => 'Office Furniture',
                'subExpenseCategoryCode' => 'EQ002',
                'description' => 'Desks, chairs, cabinets'
            ],
            
            // Utilities
            [
                'main_expense_category_id' => $utilities->id,
                'subExpenseCategoryname' => 'Electricity',
                'subExpenseCategoryCode' => 'UT001',
                'description' => 'Electricity bills and charges'
            ],
            [
                'main_expense_category_id' => $utilities->id,
                'subExpenseCategoryname' => 'Internet & Phone',
                'subExpenseCategoryCode' => 'UT002',
                'description' => 'Internet and telephone services'
            ],
            
            // Marketing
            [
                'main_expense_category_id' => $marketing->id,
                'subExpenseCategoryname' => 'Digital Marketing',
                'subExpenseCategoryCode' => 'MK001',
                'description' => 'Online ads, social media, SEO'
            ],
            [
                'main_expense_category_id' => $marketing->id,
                'subExpenseCategoryname' => 'Print Marketing',
                'subExpenseCategoryCode' => 'MK002',
                'description' => 'Brochures, flyers, banners'
            ],
            
            // Travel
            [
                'main_expense_category_id' => $travel->id,
                'subExpenseCategoryname' => 'Transportation',
                'subExpenseCategoryCode' => 'TR001',
                'description' => 'Fuel, taxi, public transport'
            ],
            [
                'main_expense_category_id' => $travel->id,
                'subExpenseCategoryname' => 'Accommodation',
                'subExpenseCategoryCode' => 'TR002',
                'description' => 'Hotels and lodging'
            ],
            
            // Professional Services
            [
                'main_expense_category_id' => $professional->id,
                'subExpenseCategoryname' => 'Legal Services',
                'subExpenseCategoryCode' => 'PS001',
                'description' => 'Legal consultation and services'
            ],
            [
                'main_expense_category_id' => $professional->id,
                'subExpenseCategoryname' => 'Accounting Services',
                'subExpenseCategoryCode' => 'PS002',
                'description' => 'Bookkeeping and tax services'
            ],
            
            // Maintenance
            [
                'main_expense_category_id' => $maintenance->id,
                'subExpenseCategoryname' => 'Equipment Repair',
                'subExpenseCategoryCode' => 'MN001',
                'description' => 'Repair and maintenance of equipment'
            ],
            [
                'main_expense_category_id' => $maintenance->id,
                'subExpenseCategoryname' => 'Building Maintenance',
                'subExpenseCategoryCode' => 'MN002',
                'description' => 'Building repairs and upkeep'
            ]
        ];

        foreach ($subCategories as $subCategory) {
            ExpenseSubCategory::firstOrCreate(
                [
                    'main_expense_category_id' => $subCategory['main_expense_category_id'],
                    'subExpenseCategoryCode' => $subCategory['subExpenseCategoryCode']
                ],
                $subCategory
            );
        }

        $this->command->info('Expense categories seeded successfully!');
    }
}