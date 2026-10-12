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

        // Seed Sample Knowledge Articles
        $adminUser = User::where('email', 'admin@shofolconnect.com')->first();
        $adminId = $adminUser ? $adminUser->id : null;

        \App\Models\KnowledgeArticle::updateOrCreate(
            ['title' => 'Annual & Casual Leave Guidelines'],
            [
                'module' => 'HRM',
                'category' => 'Leave Policy',
                'question' => 'How do full-time employees request annual or casual leave in Shofol Connect?',
                'content' => "Employees are entitled to 14 days of annual leave and 10 days of casual leave per calendar year. Leave requests must be submitted through the HRM portal at least 3 business days in advance. Line managers must review and approve requests within 48 hours. Unused casual leave lapses at year-end, while up to 5 days of annual leave can be carried forward.",
                'keywords' => 'leave, annual leave, casual leave, vacation, pto, hrm, time off',
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
                'content' => "Payroll cutoff is the 25th day of every calendar month. Salary disbursement is executed on the final working day of each month via direct bank transfer. Statutory deductions such as income tax withholding (TDS) and provident fund contributions (8% employer and employee match) are itemized on the digital pay slip accessible under the Payroll portal.",
                'keywords' => 'payroll, salary, tax, deduction, payslip, disbursement, bank transfer, provident fund',
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
                'question' => 'How do we issue customer invoices and what are the standard payment terms?',
                'content' => "Invoices must be generated from the Accounting module with auto-generated serial numbering. Standard payment terms are Net 30 days unless a custom contract specifies otherwise. Late payments incur a 1.5% monthly service fee after a 7-day grace period. Invoices marked as Paid reconcile automatically with connected bank statements.",
                'keywords' => 'accounting, invoice, billing, payment terms, net 30, receivables, reconciliation',
                'status' => 'active',
                'created_by' => $adminId,
            ]
        );
    }
}
