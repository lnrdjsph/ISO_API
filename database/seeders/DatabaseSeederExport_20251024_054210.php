<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeederExport_20251024_054210 extends Seeder
{
    public function run()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Table: order_items
        DB::table('order_items')->truncate();
        DB::table('order_items')->insert(array (
  0 => 
  array (
    'id' => 1,
    'order_id' => 1,
    'sku' => '102806178',
    'item_description' => 'Bearbrand Pwdr Mlk 128192/33G',
    'scheme' => '15+2',
    'price_per_pc' => '11.20',
    'price' => '2150.40',
    'qty_per_pc' => 192,
    'qty_per_cs' => 50,
    'freebies_per_cs' => '0',
    'discount' => '0',
    'total_qty' => 50,
    'amount' => '107520.00',
    'remarks' => 'For SO (Special Order)',
    'store_order_no' => NULL,
    'item_type' => 'MAIN',
    'created_at' => '2025-10-24 05:36:57',
    'updated_at' => '2025-10-24 05:36:57',
  ),
  1 => 
  array (
    'id' => 2,
    'order_id' => 1,
    'sku' => '8404794',
    'item_description' => 'Lucky Me Pc Xtra Hot Chi72/60G',
    'scheme' => 'Freebie',
    'price_per_pc' => '11.50',
    'price' => '828.00',
    'qty_per_pc' => 72,
    'qty_per_cs' => 0,
    'freebies_per_cs' => '6',
    'discount' => '0',
    'total_qty' => 6,
    'amount' => '4968.00',
    'remarks' => 'For SO (Special Order)',
    'store_order_no' => NULL,
    'item_type' => 'FREEBIE',
    'created_at' => '2025-10-24 05:36:57',
    'updated_at' => '2025-10-24 05:36:57',
  ),
));

        // Table: orders
        DB::table('orders')->truncate();
        DB::table('orders')->insert(array (
  0 => 
  array (
    'id' => 1,
    'sof_id' => 'SOF20251024-001',
    'requesting_store' => '4002',
    'requested_by' => '1',
    'mbc_card_no' => '9999999999999999',
    'customer_name' => 'test',
    'contact_number' => '099999999999',
    'channel_order' => 'E-Commerce',
    'warehouse' => '80041',
    'time_order' => '2025-10-24T13:36',
    'payment_center' => 'F2 - Metro Wholesalemart Colon',
    'mode_payment' => 'PO15%',
    'payment_date' => '2025-10-24',
    'mode_dispatching' => 'Customer Pick-up',
    'delivery_date' => '2025-10-24',
    'address' => NULL,
    'landmark' => NULL,
    'order_status' => 'new order',
    'created_at' => '2025-10-24 05:36:57',
    'updated_at' => '2025-10-24 05:36:57',
    'approval_document' => NULL,
  ),
));

        // Table: products_4002
        DB::table('products_4002')->truncate();
        DB::table('products_4002')->insert(array (
  0 => 
  array (
    'id' => 1,
    'sku' => '102806178',
    'description' => 'Bearbrand Pwdr Mlk 128192/33G',
    'department_code' => '1106',
    'department' => 'Basic Grocery 1 - Milk',
    'case_pack' => '192',
    'srp' => '11.20',
    'wms_allocation_per_case' => NULL,
    'allocation_per_case' => 450,
    'cash_bank_card_scheme' => '15+1',
    'po15_scheme' => '15+2',
    'discount_scheme' => '10%',
    'freebie_sku' => '9413022 | 8404794',
    'archived_at' => NULL,
    'archived_by' => NULL,
    'archive_reason' => NULL,
    'created_at' => '2025-10-24 05:36:40',
    'updated_at' => '2025-10-24 05:36:57',
  ),
  1 => 
  array (
    'id' => 2,
    'sku' => '8404794',
    'description' => 'Lucky Me Pc Xtra Hot Chi72/60G',
    'department_code' => '1107',
    'department' => 'Basic Grocery 1 - Pasta and Noodles',
    'case_pack' => '72',
    'srp' => '11.50',
    'wms_allocation_per_case' => NULL,
    'allocation_per_case' => 594,
    'cash_bank_card_scheme' => '10+1',
    'po15_scheme' => '8+1',
    'discount_scheme' => '66',
    'freebie_sku' => '8404794',
    'archived_at' => NULL,
    'archived_by' => NULL,
    'archive_reason' => NULL,
    'created_at' => '2025-10-24 05:36:40',
    'updated_at' => '2025-10-24 05:36:57',
  ),
));

        // Table: users
        DB::table('users')->truncate();
        DB::table('users')->insert(array (
  0 => 
  array (
    'id' => 1,
    'name' => 'Biboy',
    'email' => 'leonard.tomalon@metroretail.ph',
    'role' => 'super admin',
    'user_location' => '4002',
    'email_verified_at' => NULL,
    'password' => '$2y$10$4/ewTnA3hpLnX.gA1oWiwO.QytLMuxvbhkYujmOOngcli/tldnwqi',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:06',
    'updated_at' => '2025-10-24 05:31:06',
  ),
  1 => 
  array (
    'id' => 2,
    'name' => 'Gene',
    'email' => 'gene.catarina@metroretail.ph',
    'role' => 'super admin',
    'user_location' => '6012',
    'email_verified_at' => NULL,
    'password' => '$2y$10$OafIBer79AKX5v51u/0ReewjcRRi2KrLA7CMRUcETsaqWpNkBFba6',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:06',
    'updated_at' => '2025-10-24 05:31:06',
  ),
  2 => 
  array (
    'id' => 3,
    'name' => 'Akehide',
    'email' => 'akehide.tecson@metroretail.ph',
    'role' => 'super admin',
    'user_location' => '4002',
    'email_verified_at' => NULL,
    'password' => '$2y$10$zwQH4eo5eFIcjq.JmnW0wepOLVj24gSHE5Xz.4FbQl9SnFhJ66xpO',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:06',
    'updated_at' => '2025-10-24 05:31:06',
  ),
  3 => 
  array (
    'id' => 4,
    'name' => 'Store Personnel',
    'email' => 'test.storepersonnel@metroretail.ph',
    'role' => 'store personnel',
    'user_location' => '4002',
    'email_verified_at' => NULL,
    'password' => '$2y$10$Vm2zn2sBDwk84OEpy.t4J.zLgX23niJ4Cpct9IyVfhZNX073Xv43C',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:06',
    'updated_at' => '2025-10-24 05:31:06',
  ),
  4 => 
  array (
    'id' => 5,
    'name' => 'Warehouse Admin',
    'email' => 'test.warehouseadmin@metroretail.ph',
    'role' => 'warehouse admin',
    'user_location' => '6012',
    'email_verified_at' => NULL,
    'password' => '$2y$10$gfz.97y.Jzgh.LFF/p6I.urz9daWG0yEiPOTWFw9ou34FCJPAS3Z2',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:06',
    'updated_at' => '2025-10-24 05:31:06',
  ),
  5 => 
  array (
    'id' => 6,
    'name' => 'Warehouse Personnel',
    'email' => 'test.warehousepersonnel@metroretail.ph',
    'role' => 'warehouse personnel',
    'user_location' => '6012',
    'email_verified_at' => NULL,
    'password' => '$2y$10$j7pW6J47sMvU5xXqs9Tkau0MxOhu.WN/3fcUhAG4ZLelYSQRVPI/i',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:07',
    'updated_at' => '2025-10-24 05:31:07',
  ),
  6 => 
  array (
    'id' => 7,
    'name' => 'Manager',
    'email' => 'test.manager@metroretail.ph',
    'role' => 'manager',
    'user_location' => '6012',
    'email_verified_at' => NULL,
    'password' => '$2y$10$KX4E7F0vl0lE0uhQz3C3QuY1FIZYJwrK1RTMI575PSVU0EkBoF5hW',
    'two_factor_secret' => NULL,
    'two_factor_recovery_codes' => NULL,
    'two_factor_confirmed_at' => NULL,
    'remember_token' => NULL,
    'created_at' => '2025-10-24 05:30:07',
    'updated_at' => '2025-10-24 05:31:07',
  ),
));

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
