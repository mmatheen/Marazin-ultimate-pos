<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SetDefaultInvoiceLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set default invoice layout for all existing locations that don't have one
        DB::table('locations')
            ->whereNull('invoice_layout_pos')
            ->orWhere('invoice_layout_pos', '')
            ->update(['invoice_layout_pos' => '80mm']);
            
        $this->command->info('Default invoice layout (80mm) set for all existing locations.');
    }
}