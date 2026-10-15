<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'is_admin' => true,
                'status' => 'active',
                'password' => bcrypt('password'),
                'account_number' => '100000000001',
                'avatar' => null
            ]
        );

        User::firstOrCreate(
            ['email' => 'member@laragym.com'],
            [
                'name' => 'محمد أحمد (عضو تجريبي)',
                'is_admin' => false,
                'status' => 'active',
                'password' => bcrypt('password'),
                'rfid_card_id' => 'RFID123456',
                'account_number' => '000000000101',
                'avatar' => null
            ]
        );

        User::firstOrCreate(
            ['email' => 'sara@laragym.com'],
            [
                'name' => 'سارة كريم (عضو نشط)',
                'is_admin' => false,
                'status' => 'active',
                'password' => bcrypt('password'),
                'rfid_card_id' => 'RFID654321',
                'account_number' => '000000000102',
                'avatar' => null
            ]
        );

        User::firstOrCreate(
            ['email' => 'yassine@laragym.com'],
            [
                'name' => 'ياسين بن علي (حصصه مكتملة)',
                'is_admin' => false,
                'status' => 'active',
                'password' => bcrypt('password'),
                'rfid_card_id' => 'RFID_DEPLETED',
                'account_number' => '000000000103',
                'avatar' => null
            ]
        );

        User::firstOrCreate(
            ['email' => 'khaled@laragym.com'],
            [
                'name' => 'خالد مراد (اشتراك منتهي المدة)',
                'is_admin' => false,
                'status' => 'active',
                'password' => bcrypt('password'),
                'rfid_card_id' => 'RFID_EXPIRED',
                'account_number' => '000000000104',
                'avatar' => null
            ]
        );
    }
}
