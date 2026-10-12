<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\KnowledgeCategory;
use App\Models\ShofolModule;
use App\Models\User;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotently seeds enterprise FAQs grounded in verified company policies.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin?->id;

        $faqsData = [
            [
                'module_code' => 'HRM',
                'category_name' => 'Leave Policy',
                'question' => 'How many annual and casual leave days do full-time employees receive?',
                'slug' => 'annual-and-casual-leave-entitlement',
                'answer' => 'Full-time employees receive 14 annual leave days and 10 casual leave days per calendar year. Requests must be submitted through the HRM portal at least 3 business days in advance and reviewed by line managers within 48 hours. Unused casual leave expires at year-end, while up to 5 annual leave days may be carried forward.',
                'keywords' => 'hrm, leave, annual leave, casual leave, full-time employees, request leave, carry forward, line manager',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'module_code' => 'Payroll',
                'category_name' => 'Salary & Tax',
                'question' => 'When is monthly salary disbursed and how is tax deduction computed?',
                'slug' => 'monthly-salary-disbursement-and-tax-deduction',
                'answer' => 'Payroll cutoff is the 25th of each month, and salary disbursement is executed on the final working day of each month via direct bank transfer. Tax deduction (TDS) and provident fund contributions (8% employee and 8% employer) are itemized on the digital payslip. Tax deduction (TDS) is computed based on applicable statutory tax rules rather than a fixed company rate.',
                'keywords' => 'payroll, salary, disbursement, tax deduction, tds, provident fund, cutoff, digital payslip, bank transfer',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'module_code' => 'CRM',
                'category_name' => 'Lead Management',
                'question' => 'What criteria must be confirmed before a lead is qualified in the sales pipeline?',
                'slug' => 'lead-qualification-and-bant-criteria',
                'answer' => 'In the Shofol CRM sales pipeline, a lead is qualified when the official BANT criteria are confirmed: Budget, Authority, Need, and Timeline. Qualified leads then progress from Contacted to Proposal Sent. Deals inactive for more than 14 days trigger an automated reminder to the assigned sales representative.',
                'keywords' => 'crm, lead qualification, bant, budget, authority, need, timeline, proposal sent, deals, reminder',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'module_code' => 'Accounting',
                'category_name' => 'Invoicing & Receivables',
                'question' => 'What are the customer invoice payment terms, grace period, and late fees?',
                'slug' => 'customer-invoice-payment-terms-and-late-fees',
                'answer' => 'Customer invoices are generated through the Accounting module with sequential serial numbers. Standard payment terms are Net 30 days unless an approved contract specifies otherwise. A 7-day grace period applies after the due date, after which a 1.5% monthly service fee applies to unpaid balances. Marking an invoice Paid automatically reconciles it with connected bank statements.',
                'keywords' => 'accounting, invoice, payment terms, net 30, grace period, late fee, service fee, reconciliation, bank statements',
                'is_active' => true,
                'sort_order' => 1,
            ],
        ];

        foreach ($faqsData as $data) {
            $module = ShofolModule::where('code', $data['module_code'])->first();
            if (!$module) {
                continue;
            }

            $category = KnowledgeCategory::where('shofol_module_id', $module->id)
                ->where('name', $data['category_name'])
                ->first();

            Faq::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'shofol_module_id' => $module->id,
                    'knowledge_category_id' => $category?->id,
                    'question' => $data['question'],
                    'answer' => $data['answer'],
                    'keywords' => $data['keywords'],
                    'is_active' => $data['is_active'],
                    'sort_order' => $data['sort_order'],
                    'created_by' => $adminId,
                ]
            );
        }
    }
}
