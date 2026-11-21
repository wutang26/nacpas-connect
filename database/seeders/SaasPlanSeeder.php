<?php

namespace Database\Seeders;

use App\Models\SaasPlan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'code' => 'starter',
                'description' => 'Mpango wa kuanzia kwa biashara ndogo za public WiFi.',
                'price_monthly' => 20000,
                'price_annual' => 200000,
                'currency' => 'TZS',
                'max_sites' => 1,
                'max_users' => 2,
                'max_devices' => 2,
                'features' => [
                    'Site management',
                    'WiFi packages management',
                    'Customer management',
                    'Basic reports',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Business',
                'code' => 'business',
                'description' => 'Mpango kwa biashara zinazomiliki Sites nyingi.',
                'price_monthly' => 50000,
                'price_annual' => 500000,
                'currency' => 'TZS',
                'max_sites' => 5,
                'max_users' => 10,
                'max_devices' => 10,
                'features' => [
                    'Everything in Starter',
                    'Multiple Sites',
                    'Advanced reports',
                    'Network monitoring',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'description' => 'Mpango kwa kampuni kubwa za internet na public WiFi.',
                'price_monthly' => 100000,
                'price_annual' => 1000000,
                'currency' => 'TZS',
                'max_sites' => 999999,
                'max_users' => 999999,
                'max_devices' => 999999,
                'features' => [
                    'Everything in Business',
                    'Large scale Site management',
                    'Advanced network monitoring',
                    'Priority support',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SaasPlan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }

        $this->command->info('SaaS plans seeded successfully.');
    }
}