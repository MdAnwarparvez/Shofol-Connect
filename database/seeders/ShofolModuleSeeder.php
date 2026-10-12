<?php

namespace Database\Seeders;

use App\Models\ShofolModule;
use Illuminate\Database\Seeder;

class ShofolModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotently creates or updates the default 4 Shofol business modules.
     */
    public function run(): void
    {
        $defaultModules = [
            [
                'name' => 'Human Resource Management',
                'code' => 'HRM',
                'icon' => 'bi-people',
                'description' => 'Employee directory, attendance, leave request guidelines, onboarding, and organizational policies.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Payroll Management',
                'code' => 'Payroll',
                'icon' => 'bi-cash-stack',
                'description' => 'Monthly salary cycles, tax itemization, provident funds, cutoff dates, and digital payslips.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Customer Relationship Management',
                'code' => 'CRM',
                'icon' => 'bi-briefcase',
                'description' => 'Sales pipeline stages, lead qualification (BANT), proposal delivery, and customer relationship records.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Financial Accounting',
                'code' => 'Accounting',
                'icon' => 'bi-calculator',
                'description' => 'Sequential invoice generation, standard Net 30 payment terms, bank reconciliations, and billing audit logs.',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($defaultModules as $mod) {
            ShofolModule::updateOrCreate(
                ['code' => $mod['code']],
                [
                    'name' => $mod['name'],
                    'icon' => $mod['icon'],
                    'description' => $mod['description'],
                    'is_active' => $mod['is_active'],
                    'sort_order' => $mod['sort_order'],
                ]
            );
        }
    }
}
