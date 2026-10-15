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
            // 1. Mohamed Ahmed: 12 sessions total, 10 remaining, expires in 30 days
            $mohamed = \App\Models\User::where('email', 'member@laragym.com')->first();
            if ($mohamed && !\App\Models\Subscription::where('user_id', $mohamed->id)->exists()) {
                \App\Models\Subscription::create([
                    'user_id' => $mohamed->id,
                    'package_id' => $package->id,
                    'interval' => 1,
                    'total_sessions' => 12,
                    'remaining_sessions' => 10,
                    'sessions_used' => 2,
                    'status' => 'active',
                    'expires_at' => now()->addDays(30)
                ]);
            }

            // 2. Sara Karim: Unlimited sessions, expires in 60 days
            $sara = \App\Models\User::where('email', 'sara@laragym.com')->first();
            if ($sara && !\App\Models\Subscription::where('user_id', $sara->id)->exists()) {
                \App\Models\Subscription::create([
                    'user_id' => $sara->id,
                    'package_id' => $package->id,
                    'interval' => 1,
                    'total_sessions' => null,
                    'remaining_sessions' => null,
                    'sessions_used' => 5,
                    'status' => 'active',
                    'expires_at' => now()->addDays(60)
                ]);
            }

            // 3. Yassine: Completed all 12 sessions (0 remaining)
            $yassine = \App\Models\User::where('email', 'yassine@laragym.com')->first();
            if ($yassine && !\App\Models\Subscription::where('user_id', $yassine->id)->exists()) {
                \App\Models\Subscription::create([
                    'user_id' => $yassine->id,
                    'package_id' => $package->id,
                    'interval' => 1,
                    'total_sessions' => 12,
                    'remaining_sessions' => 0,
                    'sessions_used' => 12,
                    'status' => 'completed',
                    'expires_at' => now()->addDays(15)
                ]);
            }

            // 4. Khaled: Date expired 5 days ago, though had remaining sessions
            $khaled = \App\Models\User::where('email', 'khaled@laragym.com')->first();
            if ($khaled && !\App\Models\Subscription::where('user_id', $khaled->id)->exists()) {
                \App\Models\Subscription::create([
                    'user_id' => $khaled->id,
                    'package_id' => $package->id,
                    'interval' => 1,
                    'total_sessions' => 12,
                    'remaining_sessions' => 4,
                    'sessions_used' => 8,
                    'status' => 'expired',
                    'expires_at' => now()->subDays(5)
                ]);
            }
        }

        Model::reguard();
        Schema::enableForeignKeyConstraints();
    }
}
