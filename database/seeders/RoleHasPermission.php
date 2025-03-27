<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class RoleHasPermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Get the roles' IDs for reference
          $superAdmin = DB::table('roles')->where('name', 'Super Admin')->first()->id;
          $admin = DB::table('roles')->where('name', 'Admin')->first()->id;
          $manager = DB::table('roles')->where('name', 'Manager')->first()->id;
          $cashier = DB::table('roles')->where('name', 'Cashier')->first()->id;

          // Get the permissions' IDs for reference
          $addProduct = DB::table('permissions')->where('name', 'Add Product')->first()->id;
          $editProduct = DB::table('permissions')->where('name','Edit/Update Product')->first()->id;
          $deleteProduct = DB::table('permissions')->where('name', 'Delete Product')->first()->id;
          $viewWarranty = DB::table('permissions')->where('name', 'View Warranty')->first()->id;
          $addWarranty = DB::table('permissions')->where('name', 'Add Warranty')->first()->id;
          $editWarranty = DB::table('permissions')->where('name', 'Edit/Update Warranty')->first()->id;
          $deleteWarranty = DB::table('permissions')->where('name', 'Delete Warranty')->first()->id;

        DB::table('role_has_permissions')->insert([

            // super admin
            [
                'permission_id' => $addProduct, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],
            [
                'permission_id' => $editProduct, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],
            [
                'permission_id' => $deleteProduct, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],
            [
                'permission_id' => $viewWarranty, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],

            [
                'permission_id' => $addWarranty, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],
            [
                'permission_id' => $editWarranty, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],
            [
                'permission_id' => $deleteWarranty, // Foreign key reference
                'role_id' => $superAdmin, // Foreign key reference
            ],


            // Admin

            [
                'permission_id' => $addProduct, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],
            [
                'permission_id' => $editProduct, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],
            [
                'permission_id' => $deleteProduct, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],
            [
                'permission_id' => $viewWarranty, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],
            [
                'permission_id' => $addWarranty, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],
            [
                'permission_id' => $editWarranty, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],
            [
                'permission_id' => $deleteWarranty, // Foreign key reference
                'role_id' => $admin, // Foreign key reference
            ],



            // Manager

            [
                'permission_id' => $addProduct, // Foreign key reference
                'role_id' => $manager, // Foreign key reference
            ],
            [
                'permission_id' => $editProduct, // Foreign key reference
                'role_id' => $manager, // Foreign key reference
            ],
            [
                'permission_id' => $deleteProduct, // Foreign key reference
                'role_id' => $manager, // Foreign key reference
            ],


            // Cashier
            [
                'permission_id' => $addWarranty, // Foreign key reference
                'role_id' => $cashier, // Foreign key reference
            ],
            [
                'permission_id' => $editWarranty, // Foreign key reference
                'role_id' => $cashier, // Foreign key reference
            ],
            [
                'permission_id' => $deleteWarranty, // Foreign key reference
                'role_id' => $cashier, // Foreign key reference
            ],




        ]);
    }
}
