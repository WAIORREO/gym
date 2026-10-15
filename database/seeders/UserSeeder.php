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
        User::truncate();

        User::create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'is_admin' => true,
            'password' => bcrypt('password'),
            'account_number' => '100000000001',
            'avatar' => null
        ]);

        User::create([
            'name' => 'محمد أحمد (عضو تجريبي)',
            'email' => 'member@laragym.com',
            'is_admin' => false,
            'status' => 'active',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'RFID123456',
            'account_number' => '000000000101',
            'avatar' => null
        ]);

        User::create([
            'name' => 'سارة كريم (عضو نشط)',
            'email' => 'sara@laragym.com',
            'is_admin' => false,
            'status' => 'active',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'RFID654321',
            'account_number' => '000000000102',
            'avatar' => null
        ]);

        User::create([
            'name' => 'ياسين بن علي (حصصه مكتملة)',
            'email' => 'yassine@laragym.com',
            'is_admin' => false,
            'status' => 'active',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'RFID_DEPLETED',
            'account_number' => '000000000103',
            'avatar' => null
        ]);

        User::create([
            'name' => 'خالد مراد (اشتراك منتهي المدة)',
            'email' => 'khaled@laragym.com',
            'is_admin' => false,
            'status' => 'active',
            'password' => bcrypt('password'),
            'rfid_card_id' => 'RFID_EXPIRED',
            'account_number' => '000000000104',
            'avatar' => null
        ]);
    }
}
