<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Model::unguard();

        $this->call(UserSeeder::class);
        $this->call(CycleSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(ActivitySeeder::class);
        $this->call(PackageSeeder::class);

        \App\Models\Branch::firstOrCreate(['name' => 'الفرع الرئيسي (Main Branch)']);
        \App\Models\Branch::firstOrCreate(['name' => 'فرع النخبة (VIP Club)']);

        $package = \App\Models\Package::first();
        if ($package) {
            foreach (\App\Models\User::where('is_admin', false)->get() as $user) {
                \App\Models\Subscription::create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'interval' => 1,
                    'status' => 'active',
                    'expires_at' => now()->addDays(60)
                ]);
            }
        }

        Model::reguard();
        Schema::enableForeignKeyConstraints();
    }
}
