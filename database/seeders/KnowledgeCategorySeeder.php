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
                [
                    'name' => 'Employee Management',
                    'slug' => 'employee-management',
                    'description' => 'Staff profiles, designations, department allocation, and contact records.',
                    'sort_order' => 4,
                ],
                [
                    'name' => 'Shift & Roster Management',
                    'slug' => 'shift-roster-management',
                    'description' => 'Shift configurations, working hour schedules, and employee roster assignments.',
                    'sort_order' => 5,
                ],
                [
                    'name' => 'Attendance Management',
                    'slug' => 'attendance-management',
                    'description' => 'Daily check-in policies, attendance logs, and working hour tracking.',
                    'sort_order' => 6,
                ],
                [
                    'name' => 'Work Location Management',
                    'slug' => 'work-location-management',
                    'description' => 'Branch offices, branch allocation, geofencing, and work location settings.',
                    'sort_order' => 7,
                ],
                [
                    'name' => 'Leave Management',
                    'slug' => 'leave-management',
                    'description' => 'Employee leave applications, managerial approvals, and leave cancellations.',
                    'sort_order' => 8,
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
                [
                    'name' => 'Salary Processing',
                    'slug' => 'salary-processing',
                    'description' => 'End-to-end payroll generation, review cycles, and disbursement procedures.',
                    'sort_order' => 4,
                ],
                [
                    'name' => 'Payroll Troubleshooting',
                    'slug' => 'payroll-troubleshooting',
                    'description' => 'Resolving absence count errors, attendance discrepancies, and deduction issues.',
                    'sort_order' => 5,
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
                [
                    'name' => 'Customer Management',
                    'slug' => 'customer-management',
                    'description' => 'Customer directories, profile updates, and interaction history.',
                    'sort_order' => 4,
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
                [
                    'name' => 'Banking & Reconciliation',
                    'slug' => 'banking-and-reconciliation',
                    'description' => 'Reconciling general ledger balances with bank feeds and statement entries.',
                    'sort_order' => 4,
                ],
            ],
            'Inventory' => [
                [
                    'name' => 'Stock Management',
                    'slug' => 'stock-management',
                    'description' => 'Product stock checks, warehouse inventory auditing, and item quantities.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Inventory Troubleshooting',
                    'slug' => 'inventory-troubleshooting',
                    'description' => 'Investigating stock discrepancies, damaged goods records, and transfer audits.',
                    'sort_order' => 2,
                ],
            ],
            'General' => [
                [
                    'name' => 'Login & Authentication',
                    'slug' => 'login-authentication',
                    'description' => 'Password reset, credential verification, and account lockout troubleshooting.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Mobile Application Troubleshooting',
                    'slug' => 'mobile-app-troubleshooting',
                    'description' => 'Android and iOS mobile client syncing, updates, and offline cache issues.',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'User Management & Permissions',
                    'slug' => 'user-management-permissions',
                    'description' => 'Role-based access control, module visibility, and administrative privileges.',
                    'sort_order' => 3,
                ],
            ],
            'POS' => [
                [
                    'name' => 'Sales Transactions',
                    'slug' => 'sales-transactions',
                    'description' => 'Processing counter sales, cart items, discounts, and customer receipts.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Outlet & Stock Management',
                    'slug' => 'outlet-stock-management',
                    'description' => 'Troubleshooting multi-outlet stock quantities and warehouse allocations.',
                    'sort_order' => 2,
                ],
            ],
            'Field-Automation' => [
                [
                    'name' => 'Visit Planning',
                    'slug' => 'visit-planning',
                    'description' => 'Route planning, store visit itineraries, and customer appointment schedules.',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Visit Management',
                    'slug' => 'visit-management',
                    'description' => 'GPS check-ins, customer meeting logs, and store feedback entries.',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Expense Management',
                    'slug' => 'expense-management',
                    'description' => 'Field travel reimbursement claims, mileage logs, and receipt submissions.',
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
