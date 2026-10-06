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
            'account_number' => fake()->randomDigit(),
            'avatar' => fake()->imageUrl(50, 50, 'people')
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

        User::factory(24)->create(['is_admin' => false]);
    }
}
