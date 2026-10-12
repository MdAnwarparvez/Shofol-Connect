<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default Admin User
        User::updateOrCreate(
            ['email' => 'admin@shofolconnect.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Default Regular User
        $user = User::updateOrCreate(
            ['email' => 'user@shofolconnect.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        // Seed Core Shofol Business Modules (Idempotent)
        $this->call(ShofolModuleSeeder::class);

        // Seed Default Knowledge Categories (Idempotent)
        $this->call(KnowledgeCategorySeeder::class);

        // Seed Default Verified Policy FAQs (Idempotent)
        $this->call(FaqSeeder::class);

        // Seed Sample Knowledge Articles
        $adminUser = User::where('email', 'admin@shofolconnect.com')->first();
        $adminId = $adminUser ? $adminUser->id : null;

        \App\Models\KnowledgeArticle::updateOrCreate(
            ['title' => 'Annual & Casual Leave Guidelines'],
            [
                'module' => 'HRM',
                'category' => 'Leave Policy',
                'question' => 'How do full-time employees request annual or casual leave?',
                'content' => "Full-time employees receive 14 annual leave days and 10 casual leave days per calendar year. Submit requests through the HRM portal at least 3 business days in advance. Line managers must review requests within 48 hours. Unused casual leave expires at year-end; up to 5 annual leave days may be carried forward.",
                'keywords' => 'hrm, leave, annual leave, casual leave, full-time employees, request leave, time off, vacation, pto, line manager, carry forward',
                'status' => 'active',
                'created_by' => $adminId,
            ]
        );

        \App\Models\KnowledgeArticle::updateOrCreate(
            ['title' => 'Monthly Payroll Cycle and Tax Deductions'],
            [
                'module' => 'Payroll',
                'category' => 'Salary & Tax',
                'question' => 'When is monthly salary disbursed and how is tax deduction computed?',
                'content' => "Payroll cutoff is the 25th of each month. Salary disbursement is executed on the final working day of each month by direct bank transfer. Tax deduction (TDS) and provident fund contributions are itemized on the digital payslip accessible under the Payroll portal. Employee and employer provident fund contributions are each 8%. Tax deduction (TDS) is computed based on applicable statutory tax rules rather than a fixed company rate, and is itemized on the digital payslip.",
                'keywords' => 'payroll, salary, disbursement, bank transfer, direct bank transfer, tax deduction, tds, taxes, provident fund, cutoff, digital payslip, pay slip',
                'status' => 'active',
                'created_by' => $adminId,
            ]
        );

        \App\Models\KnowledgeArticle::updateOrCreate(
            ['title' => 'Sales Pipeline Stages and Lead Qualification'],
            [
                'module' => 'CRM',
                'category' => 'Lead Management',
                'question' => 'What are the official sales pipeline stages and criteria to qualify a lead?',
                'content' => "The Shofol CRM pipeline consists of five stages: 1. New Lead (unverified contact), 2. Contacted (initial outreach made), 3. Qualified (BANT criteria confirmed: Budget, Authority, Need, Timeline), 4. Proposal Sent (formal quotation delivered), and 5. Closed Won/Lost. Deals inactive for more than 14 days trigger an automated reminder to the assigned sales representative.",
                'keywords' => 'crm, leads, pipeline, sales, qualification, proposal, deals, bant',
                'status' => 'active',
                'created_by' => $adminId,
            ]
        );

        \App\Models\KnowledgeArticle::updateOrCreate(
            ['title' => 'Customer Invoice Generation and Payment Terms'],
            [
                'module' => 'Accounting',
                'category' => 'Invoicing & Receivables',
                'question' => 'How do I generate a customer invoice and what are the payment terms?',
                'content' => "Generate invoices through the Accounting module with sequential serial numbers. Standard payment terms are Net 30 days unless an approved contract specifies otherwise. A 7-day grace period applies; after it, a 1.5% monthly service fee applies to unpaid invoices. Marking an invoice Paid automatically reconciles it with connected bank statements.",
                'keywords' => 'accounting, invoice, customer invoice, billing, payment terms, net 30, sequential serial numbers, grace period, service fee, reconciliation, bank statements',
                'status' => 'active',
                'created_by' => $adminId,
            ]
        );
    }
}
