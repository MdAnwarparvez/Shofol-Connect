<?php

namespace Database\Seeders;

use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KnowledgeCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotently seeds initial enterprise categories for core Shofol modules.
     */
    public function run(): void
    {
        $categoriesByModule = [
            'HRM' => [
                [
                    'name' => 'Leave Policy',
                    'slug' => 'leave-policy',
                    'description' => 'Guidelines on annual leave, casual leave, sick days, and carry-forward rules.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Onboarding & Training',
                    'slug' => 'onboarding-training',
                    'description' => 'Protocols for welcoming new employees, paperwork, and training programs.',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Employee Benefits & PTO',
                    'slug' => 'employee-benefits-pto',
                    'description' => 'Medical insurance, wellness benefits, and paid time-off provisions.',
                    'sort_order' => 3,
                ],
            ],
            'Payroll' => [
                [
                    'name' => 'Salary & Tax',
                    'slug' => 'salary-tax',
                    'description' => 'Monthly payroll cutoff dates, disbursement schedules, and tax withholding.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Provident Fund',
                    'slug' => 'provident-fund',
                    'description' => 'Employer and employee contribution rates and retirement funds.',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Deductions & Payslips',
                    'slug' => 'deductions-payslips',
                    'description' => 'Itemized digital payslips, standard statutory deductions, and allowances.',
                    'sort_order' => 3,
                ],
            ],
            'CRM' => [
                [
                    'name' => 'Lead Management',
                    'slug' => 'lead-management',
                    'description' => 'Inbound lead qualification, BANT criteria, and prospect pipeline tracking.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Sales Pipeline',
                    'slug' => 'sales-pipeline',
                    'description' => 'Official deal stages, proposal delivery, and sales rep activity reminders.',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Customer Engagement',
                    'slug' => 'customer-engagement',
                    'description' => 'Client communications, SLA expectations, and account management.',
                    'sort_order' => 3,
                ],
            ],
            'Accounting' => [
                [
                    'name' => 'Invoicing & Receivables',
                    'slug' => 'invoicing-receivables',
                    'description' => 'Customer invoice generation, Net 30 payment terms, and late fee policies.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Banking Reconciliation',
                    'slug' => 'banking-reconciliation',
                    'description' => 'Automated bank feed synchronization and payment matching procedures.',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Expense Auditing',
                    'slug' => 'expense-auditing',
                    'description' => 'Corporate expense filings, vendor disbursements, and tax auditing records.',
                    'sort_order' => 3,
                ],
            ],
        ];

        foreach ($categoriesByModule as $moduleCode => $categories) {
            $module = ShofolModule::where('code', $moduleCode)->first();
            if (!$module) {
                continue;
            }

            foreach ($categories as $cat) {
                KnowledgeCategory::updateOrCreate(
                    [
                        'shofol_module_id' => $module->id,
                        'name' => $cat['name'],
                    ],
                    [
                        'slug' => $cat['slug'] ?? Str::slug($cat['name']),
                        'description' => $cat['description'] ?? null,
                        'is_active' => true,
                        'sort_order' => $cat['sort_order'] ?? 0,
                    ]
                );
            }
        }
    }
}
