<?php

namespace Database\Seeders;

use App\Models\KnowledgeArticle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class KnowledgeBaseArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds the official 29 Shofol Connect knowledge base articles idempotently.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin?->id;

        $articles = [
            [
                'article_number' => 1,
                'module' => 'HRM',
                'category' => 'Employee Management',
                'title' => 'How to Add a New Employee in Shofol Connect',
                'question' => 'How do I add a new employee?
How can I create an employee profile?
Where can I add a new staff member?
How do I register a new employee in HRM?
How can I add an employee to Shofol Connect?
Ami kivabe notun employee add korbo?
Notun kormi add korar process ki?
Employee add korbo kivabe?',
                'content' => 'Purpose: This guide explains how to create a new employee record in Shofol Connect.
Prerequisites:
* The user must have access to the HRM module.
* The user must have permission to create employee records.
* The required employee information must be available.
Step-by-Step Process:
1. Log in to Shofol Connect using your authorized account.
2. Open the HRM module from the main navigation menu.
3. Navigate to the Employees section.
4. Click the Add Employee or equivalent button.
5. Enter the required employee information. Depending on the configuration, this may include employee name, employee ID, contact information, email address, department, designation, joining date, employment type, and work location.
6. Review the information and verify that all mandatory fields are completed.
7. Click Save or the equivalent submission button.
8. Return to the Employees list and search for the newly created employee.
9. If required, complete additional configurations such as work shifts, roster assignments, attendance policies, and work locations.
Expected Result: The employee record should appear in the employee list with the information that was successfully saved.
Troubleshooting:
* If the Add Employee button is unavailable, check your permissions.
* If the form cannot be submitted, verify the required fields.
* If the employee already exists, search for the existing record before creating another one.
* If the record does not appear after saving, refresh the list and verify the information.',
                'keywords' => 'HRM, employee management, add employee, new employee, create employee, employee registration, employee profile, staff management.',
                'status' => 'active',
            ],
            [
                'article_number' => 2,
                'module' => 'HRM',
                'category' => 'Employee Management',
                'title' => 'How to Edit an Employee Profile',
                'question' => 'How do I edit an employee profile?
How can I update employee information?
How do I change an employee\'s department?
How can I update an employee\'s designation?
Employee information edit korbo kivabe?
Employee profile update korar process ki?',
                'content' => 'Purpose: This guide explains how to update an existing employee\'s information.
Prerequisites: You must have permission to view and edit employee records.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to HRM → Employees.
3. Search for the employee whose information you want to update.
4. Open the employee record.
5. Click Edit or the equivalent action.
6. Update the necessary fields, such as contact information, department, designation, or work location, where available.
7. Review the changes for accuracy.
8. Click Save or Update.
9. Return to the employee record and verify that the changes have been applied.
Expected Result: The employee profile should display the updated information.
Troubleshooting:
* If editing is unavailable, check your access permissions.
* If changes are not saved, check for validation errors.
* If updated information does not appear immediately, refresh the page.
* If a change is restricted, contact the system administrator.',
                'keywords' => 'HRM, edit employee, update employee, employee profile, employee information, department change, designation change.',
                'status' => 'active',
            ],
            [
                'article_number' => 3,
                'module' => 'HRM',
                'category' => 'Shift & Roster Management',
                'title' => 'How to Create and Configure Employee Work Shifts',
                'question' => 'How do I create a work shift?
How can I configure employee working hours?
How do I set up morning and night shifts?
How can I change shift timings?
Work shift create korbo kivabe?
Morning shift ba night shift setup korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process for configuring employee work shifts.
Prerequisites: You must have access to the Work Shifts feature and the required permissions.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to HRM.
3. Open Work Shifts.
4. Select the available option to create a new shift.
5. Enter the shift name and other required information.
6. Configure the start time, end time, and any other available shift settings.
7. Review the configuration.
8. Save the shift.
9. Verify that the shift appears in the work shift list.
10. If required, assign the shift to employees through the roster or relevant scheduling feature.
Expected Result: The work shift should be available for use in employee scheduling.
Troubleshooting:
* If the shift cannot be created, verify the required fields.
* If a shift is duplicated, review existing shift records.
* If shift times appear incorrect, check the configured start and end times.
* If employees are not following the expected shift, review their roster assignments.',
                'keywords' => 'HRM, work shifts, shift management, morning shift, night shift, employee working hours, shift configuration.',
                'status' => 'active',
            ],
            [
                'article_number' => 4,
                'module' => 'HRM',
                'category' => 'Shift & Roster Management',
                'title' => 'How to Assign Work Shifts Through Employee Rosters',
                'question' => 'How do I assign a roster to an employee?
How can I schedule an employee\'s work shift?
How do I assign morning or night shifts?
How can I change an employee\'s roster?
Employee roster assign korbo kivabe?
Employee ke shift dibo kivabe?',
                'content' => 'Purpose: This guide explains how to assign employee work schedules through the roster functionality.
Prerequisites: Work shifts should be configured, and you must have permission to manage rosters.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to HRM → Roster.
3. Locate the available option for creating or updating a roster.
4. Select the employee or employees who need a schedule.
5. Select the applicable work shift and date or scheduling period.
6. Enter any additional required information.
7. Review the roster for overlapping or duplicate assignments.
8. Save the roster.
9. Review the resulting schedule to confirm that the assignment is correct.
Expected Result: The employee should have the appropriate roster assignment for the selected period.
Troubleshooting:
* If an employee cannot be selected, check the employee record and your permissions.
* If the wrong shift appears, review the selected shift.
* If duplicate assignments appear, check whether the employee has multiple roster entries for the same date.
* If the roster cannot be saved, verify all required fields.',
                'keywords' => 'HRM, roster, employee roster, shift assignment, work schedule, employee scheduling, duplicate roster, shift management.',
                'status' => 'active',
            ],
            [
                'article_number' => 5,
                'module' => 'HRM',
                'category' => 'Attendance Management',
                'title' => 'How to Configure Employee Attendance Policies',
                'question' => 'How do I configure an attendance policy?
How can I set employee attendance rules?
How do I manage late arrivals and working hours?
Where can I configure attendance settings?
Attendance policy setup korbo kivabe?
Employee attendance rules set korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of managing attendance policies.
Prerequisites: You must have permission to manage attendance policies.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to HRM → Attendance Policies.
3. Open the available option for creating or editing a policy.
4. Enter the policy name and required information.
5. Configure the attendance rules supported by your system, such as working hours, grace periods, or late-arrival rules, if available.
6. Review the policy settings.
7. Save the policy.
8. Verify that the policy appears in the relevant list.
9. Assign or apply the policy according to your organization\'s workflow, if the system supports this action.
Expected Result: The attendance policy should be available for the applicable employees or attendance configuration.
Troubleshooting:
* If a policy cannot be saved, check the required fields.
* If attendance calculations appear incorrect, review the applicable policy and employee work shift.
* If a policy does not apply to an employee, verify the assignment and configuration.',
                'keywords' => 'HRM, attendance policy, attendance rules, late arrival, working hours, attendance configuration, employee attendance.',
                'status' => 'active',
            ],
            [
                'article_number' => 6,
                'module' => 'HRM',
                'category' => 'Work Location Management',
                'title' => 'How to Create and Manage Employee Work Locations',
                'question' => 'How do I add a work location?
How can I configure employee work locations?
How do I manage office locations?
Where can I update a work location?
Work location add korbo kivabe?
Office location setup korar process ki?',
                'content' => 'Purpose: This guide explains how to manage work locations in Shofol Connect.
Prerequisites: You must have permission to access Work Locations.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to HRM → Work Locations.
3. Select the option to add or manage a location.
4. Enter the required location information.
5. If supported, configure additional location details such as address or location-specific attendance settings.
6. Review the information.
7. Save the location.
8. Verify that the location appears in the list.
9. If required, associate the location with the relevant employees or attendance configuration.
Expected Result: The work location should be available for the supported HRM workflows.
Troubleshooting:
* If the location cannot be saved, verify the mandatory fields.
* If the location is not available for an employee, review the applicable assignment.
* If location-based attendance is not working, verify the relevant system configuration.',
                'keywords' => 'HRM, work location, office location, employee location, location management, attendance location.',
                'status' => 'active',
            ],
            [
                'article_number' => 7,
                'module' => 'HRM',
                'category' => 'Attendance Management',
                'title' => 'How to View and Review Employee Attendance Records',
                'question' => 'How do I check employee attendance?
How can I view daily attendance?
How do I find an employee\'s attendance history?
Where can I see attendance records?
Employee attendance check korbo kivabe?
Kono employee office e asche kina dekhbo kivabe?',
                'content' => 'Purpose: This guide explains how to review employee attendance records.
Prerequisites: You must have permission to view attendance information.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to HRM → Attendance.
3. Select the relevant date or date range using the available filters.
4. Search for a specific employee if necessary.
5. Review the attendance information displayed.
6. If required, review the employee\'s assigned shift and attendance policy.
7. Use the available reporting or export options if your account supports them.
Expected Result: The relevant employee attendance information should be displayed.
Troubleshooting:
* If attendance records are missing, check the selected date and filters.
* If an employee\'s attendance is incorrect, review the assigned shift and attendance policy.
* If attendance is missing from the mobile application, verify whether the record is available on the web interface.
* Contact the system administrator if the discrepancy persists.',
                'keywords' => 'HRM, attendance, attendance records, daily attendance, attendance history, employee attendance report.',
                'status' => 'active',
            ],
            [
                'article_number' => 8,
                'module' => 'HRM',
                'category' => 'Leave Management',
                'title' => 'How to Submit an Employee Leave Request',
                'question' => 'How do I apply for leave?
How can I submit a leave request?
How do I request annual leave?
Where can I apply for employee leave?
Ami kivabe leave application dibo?
Chutir application korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of submitting a leave request.
Prerequisites: The user must have access to the leave management feature, if enabled.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to the available Leave Management or leave application section.
3. Select the option to create a leave request.
4. Select the leave type, if required.
5. Enter the leave start date and end date.
6. Provide a reason or other required information.
7. Attach supporting documents if required and supported.
8. Review the request.
9. Submit the application.
10. Check the request status using the available leave tracking feature.
Expected Result: The leave request should be submitted and displayed with its current status.
Troubleshooting:
* If the request cannot be submitted, verify the required fields.
* If the leave type is unavailable, contact HR.
* If the request remains pending, contact the responsible approver according to your organization\'s workflow.',
                'keywords' => 'HRM, leave application, apply for leave, annual leave, leave request, leave approval, employee leave.',
                'status' => 'active',
            ],
            [
                'article_number' => 9,
                'module' => 'HRM',
                'category' => 'Leave Management',
                'title' => 'How Managers Review Employee Leave Applications',
                'question' => 'How do I approve an employee\'s leave request?
How can I review pending leave applications?
Where can a manager see leave requests?
How do I reject a leave application?
Employee leave approve korbo kivabe?
Pending leave request kothay dekhbo?',
                'content' => 'Purpose: This guide explains how authorized approvers can review leave applications.
Prerequisites: The user must have the appropriate approval permissions.
Step-by-Step Process:
1. Log in to Shofol Connect using an authorized account.
2. Navigate to the relevant leave management or approval section.
3. Locate the pending leave requests.
4. Open the request that requires review.
5. Review the employee information, requested dates, leave type, and other available details.
6. Check the request against your organization\'s leave policy.
7. Select the available approval or rejection action.
8. Provide comments if required.
9. Confirm the action if prompted.
10. Verify that the request status has been updated.
Expected Result: The leave request should reflect the action taken by the authorized approver.
Troubleshooting:
* If no approval option is visible, check your permissions.
* If the request is missing, verify the selected filters and current status.
* If the status does not update, refresh the page and verify the request again.',
                'keywords' => 'HRM, leave approval, approve leave, reject leave, pending leave, leave management, manager approval.',
                'status' => 'active',
            ],
            [
                'article_number' => 10,
                'module' => 'HRM',
                'category' => 'Leave Management',
                'title' => 'How to Cancel or Withdraw a Leave Request',
                'question' => 'How do I cancel a leave request?
Can I withdraw an approved leave?
How do I cancel leave after the leave date?
How can I reverse a leave application?
Leave cancel korbo kivabe?
Approved leave cancel kora jay?
Leave use korar por cancel korte parbo?',
                'content' => 'Purpose: This guide explains how to check whether a leave request can be cancelled or withdrawn.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the leave management section.
3. Find the relevant leave request.
4. Open the request and check its current status.
5. Look for a Cancel, Withdraw, or equivalent action.
6. If the action is available, review any confirmation message and proceed according to your organization\'s policy.
7. Verify the updated status.
8. If the cancellation option is unavailable, contact HR or an authorized administrator.
Important: Cancellation rules may differ for pending, approved, ongoing, and completed leave. Whether leave can be cancelled after the leave date or after it has been used depends on the actual application functionality and organizational policy. Do not assume that cancelling a leave request automatically restores leave balances.
Expected Result: If cancellation is supported and permitted, the request status should reflect the cancellation.',
                'keywords' => 'HRM, cancel leave, withdraw leave, approved leave cancellation, leave reversal, leave balance, leave status.',
                'status' => 'active',
            ],
            [
                'article_number' => 11,
                'module' => 'Payroll',
                'category' => 'Salary Processing',
                'title' => 'How to Process Monthly Employee Payroll',
                'question' => 'How do I process monthly payroll?
How can I calculate employee salaries?
How do I prepare monthly salary?
What should I check before processing payroll?
Monthly salary process korbo kivabe?
Employee salary calculate korbo kivabe?',
                'content' => 'Purpose: This guide explains the general steps involved in reviewing and processing payroll.
Prerequisites: The user must have access to the payroll features available in the organization\'s Shofol Connect configuration.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the Payroll module, if available.
3. Navigate to the relevant payroll processing section.
4. Select the payroll period.
5. Review the employee records included in the period.
6. Verify salary information and applicable payroll components.
7. Review attendance, absences, deductions, allowances, and other configured components.
8. Run or calculate payroll using the available system action.
9. Review the resulting salary figures.
10. Investigate discrepancies before finalizing payroll.
11. Complete the available approval or finalization process.
12. Verify the final payroll status.
Expected Result: Payroll should be calculated and processed according to the configured rules and authorized workflow.
Troubleshooting:
* If an employee is missing, check the employee\'s status and payroll eligibility.
* If absence counts are incorrect, review attendance records.
* If salary figures are unexpected, verify the employee\'s salary information and applicable payroll components.
* Do not finalize payroll until material discrepancies have been investigated.',
                'keywords' => 'Payroll, monthly payroll, salary processing, salary calculation, employee salary, payroll period, allowances, deductions.',
                'status' => 'active',
            ],
            [
                'article_number' => 12,
                'module' => 'Payroll',
                'category' => 'Payroll Troubleshooting',
                'title' => 'Troubleshooting Missing Absence Counts in Payroll',
                'question' => 'Why is an employee\'s absence count missing from payroll?
Why is absent attendance not showing in salary calculation?
How do I fix incorrect absence data in payroll?
Payroll e absent count ashe na keno?
Salary calculation e absence show korche na keno?',
                'content' => 'If an employee\'s absence count is missing from payroll, the issue may relate to attendance records, shift assignments, attendance policies, or payroll configuration.
Step-by-Step Troubleshooting:
1. Open the employee\'s attendance records.
2. Select the payroll period and verify the relevant dates.
3. Confirm that attendance information is available for the period.
4. Review the employee\'s assigned work shift and roster.
5. Check the applicable attendance policy.
6. Return to payroll and review the relevant employee\'s calculation.
7. Recalculate or refresh the payroll information only if the system supports this action and you are authorized to do so.
8. Verify whether the absence count is now reflected correctly.
9. If the issue remains, report it to the support team with the employee identifier, payroll period, and screenshots.
Expected Result: The absence information should be displayed correctly if the underlying records and payroll configuration support the calculation.',
                'keywords' => 'Payroll, absent count, missing absence, attendance discrepancy, salary calculation, payroll troubleshooting, attendance records.',
                'status' => 'active',
            ],
            [
                'article_number' => 13,
                'module' => 'CRM',
                'category' => 'Lead Management',
                'title' => 'How to Create a New Customer Lead in Shofol Connect',
                'question' => 'How do I add a new lead?
How can I create a customer lead?
Where can I register a potential customer?
How do I add a sales prospect?
Notun lead add korbo kivabe?
CRM e customer lead create korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of creating a customer lead.
Prerequisites: You must have access to the CRM module and permission to create leads.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the CRM module.
3. Navigate to the Leads section.
4. Click Add Lead or the equivalent button.
5. Enter the required information, such as the lead\'s name, contact details, company, source, or other available fields.
6. Enter any additional information required by the organization.
7. Review the information and check for duplicate leads.
8. Save the lead.
9. Search for the new record in the lead list.
10. Update the lead\'s status according to the organization\'s sales process when appropriate.
Expected Result: The new lead should appear in the CRM lead list.
Troubleshooting:
* If the lead cannot be created, verify mandatory fields.
* If the lead already exists, review the existing record before creating another.
* If you cannot access the Leads section, check your permissions.',
                'keywords' => 'CRM, lead management, add lead, create lead, customer prospect, sales lead, new customer lead.',
                'status' => 'active',
            ],
            [
                'article_number' => 14,
                'module' => 'CRM',
                'category' => 'Lead Management',
                'title' => 'How to Change a Customer Lead\'s Status',
                'question' => 'How do I change a lead\'s status?
How can I qualify a lead?
How do I mark a lead as Won?
How can I update the sales pipeline?
Lead status change korbo kivabe?
Lead qualify korar process ki?',
                'content' => 'Purpose: This guide explains how to update a lead\'s status according to the configured CRM workflow.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Navigate to CRM → Leads.
3. Search for the relevant lead.
4. Open the lead record.
5. Locate the status or stage field.
6. Review the available status options.
7. Select the appropriate status based on the actual progress of the lead.
8. Save the change.
9. Verify that the updated status appears on the record.
Important: Lead qualification, conversion, and closing a sales opportunity are different business concepts. A lead should only be marked Qualified or Won according to the organization\'s defined workflow and the actual available CRM statuses.
Expected Result: The lead should display the updated status.
Troubleshooting:
* If the status cannot be changed, verify permissions and mandatory fields.
* If the expected status is unavailable, contact the CRM administrator.
* If the update does not appear, refresh the record and verify the saved status.',
                'keywords' => 'CRM, lead status, lead qualification, qualified lead, Won status, sales pipeline, lead conversion.',
                'status' => 'active',
            ],
            [
                'article_number' => 15,
                'module' => 'CRM',
                'category' => 'Customer Management',
                'title' => 'How to Search and Update Customer Records',
                'question' => 'How do I find a customer in CRM?
How can I edit customer information?
How do I update a customer\'s contact details?
How do I avoid duplicate customer records?
CRM e customer search korbo kivabe?
Customer information update korbo kivabe?',
                'content' => 'Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the CRM module.
3. Navigate to the relevant customer or contact section.
4. Search using the available name, contact information, or other filters.
5. Open the correct customer record.
6. Review the existing information.
7. Select Edit if available and permitted.
8. Update the necessary fields.
9. Save the changes.
10. Reopen the record and verify the updated information.
Important: Before creating a new record, search for an existing customer to reduce duplication.
Expected Result: The customer record should display the updated information.',
                'keywords' => 'CRM, customer records, customer search, edit customer, update contact, duplicate customer, customer management.',
                'status' => 'active',
            ],
            [
                'article_number' => 16,
                'module' => 'Accounting',
                'category' => 'Invoicing & Receivables',
                'title' => 'How to Generate a Customer Invoice',
                'question' => 'How do I create a customer invoice?
How can I generate a sales invoice?
How do I bill a customer?
Where can I create an invoice?
Customer invoice create korbo kivabe?
Invoice generate korar process ki?',
                'content' => 'Purpose: This guide explains the general process of creating a customer invoice.
Prerequisites: You must have access to the relevant invoicing features.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the Accounting module.
3. Navigate to the invoicing or relevant sales-related section.
4. Select Create Invoice or the equivalent option.
5. Select the customer.
6. Enter the invoice date and other required information.
7. Add the relevant products or services, quantities, prices, and applicable tax information where supported.
8. Review the subtotal, taxes, discounts, and total amount.
9. Enter payment terms or due dates if these fields are available.
10. Verify all information before saving.
11. Save or issue the invoice using the available action.
12. Reopen the invoice and verify that it was created successfully.
Expected Result: The invoice should be saved with the correct customer, items, and amounts.
Troubleshooting:
* If the customer is unavailable, check the customer records and permissions.
* If the invoice cannot be saved, verify mandatory fields.
* If totals are incorrect, review quantities, prices, discounts, and tax settings.
* Confirm payment terms against the actual customer agreement.',
                'keywords' => 'Accounting, invoice, customer invoice, sales invoice, billing, invoicing, accounts receivable, invoice creation.',
                'status' => 'active',
            ],
            [
                'article_number' => 17,
                'module' => 'Accounting',
                'category' => 'Invoicing & Receivables',
                'title' => 'How to Record and Track Customer Payments',
                'question' => 'How do I record a customer payment?
How can I mark an invoice as paid?
How do I check an outstanding invoice balance?
How can I record a partial payment?
Customer payment record korbo kivabe?
Invoice paid mark korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of recording a customer payment.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the Accounting module.
3. Navigate to the relevant invoice or payment section.
4. Locate the customer and the relevant invoice.
5. Select the available payment-recording action, if supported.
6. Enter the payment amount, date, method, and reference details required by the system.
7. Verify the information against the actual transaction.
8. Save the payment record.
9. Review the invoice status and outstanding balance.
10. Confirm that the payment is reflected correctly.
Important: Record only payments that have been received and verified according to your organization\'s financial procedures. Partial payments should be handled according to the system\'s supported functionality.
Expected Result: The payment record should be saved and reflected in the applicable accounting records.',
                'keywords' => 'Accounting, customer payment, invoice payment, outstanding balance, partial payment, payment recording, accounts receivable.',
                'status' => 'active',
            ],
            [
                'article_number' => 18,
                'module' => 'Accounting',
                'category' => 'Banking & Reconciliation',
                'title' => 'How to Reconcile Accounting Transactions with Bank Statements',
                'question' => 'How do I reconcile bank transactions?
How can I compare bank statements with accounting records?
Why is a bank transaction unmatched?
Bank reconciliation korbo kivabe?
Bank statement er sathe accounting match korbo kivabe?',
                'content' => 'Purpose: This guide explains the general bank reconciliation process. Use it only if the relevant functionality is available in your Shofol Connect configuration.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open Accounting.
3. Locate the available banking or reconciliation section.
4. Select the relevant bank account and period, if supported.
5. Review the recorded transactions.
6. Compare the recorded amounts, dates, and references with the bank statement.
7. Investigate unmatched transactions and discrepancies.
8. Correct verified errors using authorized procedures.
9. Complete the reconciliation process if supported.
10. Review the final reconciliation status.
Expected Result: Transactions should be reconciled according to the available system functionality and the organization\'s financial procedures.
Troubleshooting:
* If transactions do not match, check dates, amounts, and references.
* If a transaction is missing, verify whether it has been recorded.
* Do not assume that marking an invoice as paid automatically reconciles a bank transaction.',
                'keywords' => 'Accounting, bank reconciliation, bank statement, unmatched transactions, financial records, banking, transaction verification.',
                'status' => 'active',
            ],
            [
                'article_number' => 19,
                'module' => 'Inventory',
                'category' => 'Stock Management',
                'title' => 'How to Check Product Stock Levels',
                'question' => 'How do I check product stock?
How can I see available inventory?
How do I find the stock quantity of a product?
How can I check stock in a warehouse?
Product stock check korbo kivabe?
Warehouse e koto stock ache dekhbo kivabe?',
                'content' => 'Purpose: This guide explains how to review product stock information where inventory features are enabled.
Step-by-Step Process:
1. Log in to Shofol Connect.
2. Open the Inventory module.
3. Navigate to the relevant product or stock section.
4. Search for the product using the available filters.
5. Open the product record or stock details.
6. Review the displayed quantity and any available warehouse or location information.
7. If the system supports multiple warehouses, select the relevant warehouse to review its stock.
8. Verify the information against the relevant stock records if a discrepancy is suspected.
Expected Result: The product\'s available stock information should be displayed.
Troubleshooting:
* If the product cannot be found, verify the search filters.
* If the quantity appears incorrect, review relevant stock transactions.
* If warehouse quantities differ from expectations, verify the selected warehouse and applicable stock records.',
                'keywords' => 'Inventory, product stock, stock quantity, stock levels, warehouse stock, inventory management, stock tracking.',
                'status' => 'active',
            ],
            [
                'article_number' => 20,
                'module' => 'Inventory',
                'category' => 'Inventory Troubleshooting',
                'title' => 'How to Troubleshoot Incorrect Stock Quantities',
                'question' => 'Why is my product stock incorrect?
How do I fix a stock discrepancy?
Why does warehouse stock not match?
How do I investigate inventory differences?
Product er stock milche na keno?
Warehouse stock mismatch solve korbo kivabe?',
                'content' => 'Purpose: This guide explains how to investigate differences between recorded and actual stock.
Step-by-Step Process:
1. Open the Inventory module.
2. Search for the affected product.
3. Verify the selected warehouse or location, if applicable.
4. Review the displayed stock quantity.
5. Check relevant purchase, sales, transfer, return, and adjustment records where supported.
6. Compare the recorded transactions with the physical stock count.
7. Identify the source of the discrepancy.
8. If an adjustment is necessary, follow the organization\'s authorization process.
9. Use the supported stock adjustment feature, if available.
10. Verify the updated stock quantity.
Important: Do not make an inventory adjustment without verifying the discrepancy and obtaining any required approval.
Expected Result: The discrepancy should be investigated and corrected through authorized procedures.',
                'keywords' => 'Inventory, stock discrepancy, stock mismatch, warehouse stock, stock adjustment, inventory troubleshooting, physical stock.',
                'status' => 'active',
            ],
            [
                'article_number' => 21,
                'module' => 'General',
                'category' => 'Login & Authentication',
                'title' => 'How to Troubleshoot Shofol Connect Login Issues',
                'question' => 'How do I fix a login problem?
Why can\'t I log in to Shofol Connect?
What should I do if my login fails?
Shofol Connect e login korte parchi na keno?
Login problem solve korbo kivabe?',
                'content' => 'If you cannot log in to Shofol Connect, follow these troubleshooting steps.
1. Verify that you are using the correct login page for your organization.
2. Check that your email address or username is entered correctly.
3. Verify your password.
4. Check your internet connection.
5. If your organization uses account verification, complete the required verification process.
6. If a password reset option is available, use the official password recovery process.
7. Try again after checking whether the service is accessible.
8. If the issue persists, contact your organization\'s administrator or support team.
Security Reminder: Never share your password or one-time verification codes with anyone.
Expected Result: You should be able to identify whether the issue is related to credentials, connectivity, verification, or account access.',
                'keywords' => 'Shofol Connect, login, login problem, authentication, password, account access, sign-in troubleshooting.',
                'status' => 'active',
            ],
            [
                'article_number' => 22,
                'module' => 'General',
                'category' => 'Mobile Application Troubleshooting',
                'title' => 'Troubleshooting Shofol Connect on Android and iOS',
                'question' => 'Why is Shofol Connect not working on my phone?
How do I fix the mobile application?
Why is my profile picture not updating?
Why is my attendance not showing in the app?
Shofol Connect app kaj korche na keno?
Mobile app er problem solve korbo kivabe?',
                'content' => 'Purpose: This guide explains how to investigate common mobile application issues.
Step-by-Step Process:
1. Check that your phone has a stable internet connection.
2. Verify that you are using the correct Shofol Connect application.
3. Check whether an application update is available.
4. Close and reopen the application.
5. Sign in again if appropriate.
6. Check whether the affected feature works in the web application.
7. If the issue concerns profile information, verify that the information was saved successfully on the web.
8. If the issue concerns attendance, check the relevant attendance records and applicable work settings.
9. If the problem persists, report it to the support team.
When Reporting a Problem, Include:
* Device platform: Android or iOS.
* Application version, if available.
* Affected module or feature.
* Steps to reproduce the problem.
* Error messages and relevant screenshots.
Do not include passwords, OTPs, or other confidential authentication information.
Expected Result: The user should be able to identify basic troubleshooting steps and provide the information needed for further investigation.',
                'keywords' => 'Shofol Connect, mobile application, Android, iOS, login issue, profile picture, attendance issue, application update.',
                'status' => 'active',
            ],
            [
                'article_number' => 23,
                'module' => 'HRM',
                'category' => 'Employee Management',
                'title' => 'Employee Profile Picture Not Updating on Mobile',
                'question' => 'Why is my employee profile picture not updating in the mobile app?
Why does my new profile picture show on the web but not on Android?
Why is the updated photo missing on iOS?
Web e profile picture update hoyeche but app e dekhacche na keno?
Mobile app e profile picture update hocche na keno?',
                'content' => 'If a profile picture has been updated on the web application but is not visible in the mobile application, follow these steps.
1. Open Shofol Connect on the web.
2. Navigate to the relevant employee profile.
3. Verify that the new profile picture is displayed correctly.
4. Save the profile again only if the application indicates that the changes were not saved.
5. Open the Shofol Connect mobile application.
6. Refresh the relevant profile screen if the application supports refreshing.
7. Close and reopen the application.
8. Sign in again if necessary.
9. Check whether the updated picture is displayed.
10. If the picture is still missing, report the issue to technical support.
Information to Include in a Support Report:
* Platform: Android or iOS.
* Whether the picture is visible on the web.
* Whether the issue affects one employee or multiple employees.
* Steps to reproduce the problem.
* Relevant screenshots.
Expected Result: The updated profile picture should appear in the mobile application if synchronization and display are working correctly.',
                'keywords' => 'HRM, profile picture, employee photo, profile synchronization, Android, iOS, image update, mobile application bug.',
                'status' => 'active',
            ],
            [
                'article_number' => 24,
                'module' => 'General',
                'category' => 'User Management & Permissions',
                'title' => 'How to Troubleshoot Missing Module Access or Permissions',
                'question' => 'Why can\'t I access a module?
Why is the Add Employee button missing?
Why can\'t I edit an employee record?
How do I get permission to use a feature?
Module access pacchi na keno?
Add Employee button dekhacche na keno?',
                'content' => 'If you cannot access a module or perform an action, the issue may relate to account permissions or system configuration.
Step-by-Step Process:
1. Sign in using your authorized account.
2. Navigate to the relevant module.
3. Identify the missing menu, button, or action.
4. Check whether other authorized users can access the same feature, if appropriate.
5. Contact your system administrator.
6. Explain which module or action you cannot access.
7. Provide any relevant error message or screenshot.
8. Ask the administrator to verify your assigned role and permissions.
9. After any authorized permission change, sign in again if required.
10. Verify whether the feature is now available.
Security Reminder: Do not attempt to bypass access restrictions or use another person\'s credentials.
Expected Result: The access issue should be identified and referred to the appropriate administrator if a permission change is required.',
                'keywords' => 'Shofol Connect, permissions, module access, user role, access control, Add Employee, administrator, authorization. Use these articles only if POS functionality is available in your Shofol Connect deployment.',
                'status' => 'active',
            ],
            [
                'article_number' => 25,
                'module' => 'POS',
                'category' => 'Sales Transactions',
                'title' => 'How to Process a Sales Transaction in POS',
                'question' => 'How do I create a sale in POS?
How can I process a customer purchase?
How do I add products to a POS bill?
POS e sale korbo kivabe?
Customer er bill create korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of processing a POS sale.
Step-by-Step Process:
1. Sign in to the POS interface using your authorized account.
2. Select the appropriate outlet or register if the system requires it.
3. Start a new sales transaction.
4. Search for products or scan their barcodes if supported.
5. Add the required products and quantities.
6. Verify the product prices and applicable discounts or taxes.
7. Review the transaction total.
8. Select the available payment method.
9. Enter the payment details required by the system.
10. Confirm the transaction.
11. Print or issue a receipt if supported.
12. Verify that the transaction appears in the relevant sales records.
Expected Result: The sales transaction should be recorded according to the configured POS workflow.',
                'keywords' => 'POS, sales transaction, checkout, customer purchase, barcode, product quantity, payment, receipt.',
                'status' => 'active',
            ],
            [
                'article_number' => 26,
                'module' => 'POS',
                'category' => 'Outlet & Stock Management',
                'title' => 'Troubleshooting Incorrect Stock Quantities Across Outlets',
                'question' => 'Why is outlet stock incorrect?
Why does POS show the wrong product quantity?
How do I check stock across multiple warehouses?
Why does the outlet stock not match the inventory total?
Outlet er stock vul dekhacche keno?
Warehouse stock mismatch kivabe check korbo?',
                'content' => 'When POS stock differs from the expected quantity, first determine whether the displayed number represents one outlet, one warehouse, or a combined total.
Step-by-Step Process:
1. Open the relevant POS or Inventory section.
2. Identify the affected product.
3. Check the selected outlet or warehouse.
4. Review the stock quantity displayed for that location.
5. If supported, compare the quantities across other warehouses or outlets.
6. Review stock transfers, purchases, sales, returns, and adjustments where available.
7. Verify whether the total quantity includes stock from multiple locations.
8. Compare the recorded information with the relevant stock records.
9. Report unresolved discrepancies to the inventory administrator.
Important: A total across multiple warehouses may differ from the quantity available at one specific outlet. Always confirm the location scope before comparing stock figures.
Expected Result: The user should be able to identify whether the discrepancy is caused by location selection, transaction records, or another inventory issue.',
                'keywords' => 'POS, outlet stock, warehouse stock, inventory discrepancy, stock transfer, product quantity, multi-outlet inventory. Use these articles only if the relevant Field Automation features are available to the user.',
                'status' => 'active',
            ],
            [
                'article_number' => 27,
                'module' => 'Field-Automation',
                'category' => 'Visit Planning',
                'title' => 'How to Create and Manage Field Visit Plans',
                'question' => 'How do I create a field visit plan?
How can I schedule customer visits?
How do I plan visits for field employees?
Field visit plan create korbo kivabe?
Customer visit schedule korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of planning field visits.
Step-by-Step Process:
1. Log in to Shofol Connect or the relevant Field Automation interface.
2. Open the Visit Plans section.
3. Select the option to create a visit plan.
4. Select the relevant employee or field representative, if required.
5. Enter the planned visit date and other required details.
6. Select the customer or visit location if supported.
7. Review the visit information.
8. Save or submit the plan.
9. Verify that the plan appears in the visit plan list.
10. Check the plan status using the available interface.
Expected Result: The visit plan should be recorded and available for the relevant workflow.',
                'keywords' => 'Field Automation, visit plan, field visits, customer visit, visit scheduling, field representative, visit management.',
                'status' => 'active',
            ],
            [
                'article_number' => 28,
                'module' => 'Field-Automation',
                'category' => 'Visit Management',
                'title' => 'How to Record and Track a Customer Visit',
                'question' => 'How do I record a customer visit?
How can I check my assigned visits?
How do I update a visit status?
Field visit record korbo kivabe?
Customer visit complete korbo kivabe?',
                'content' => 'Step-by-Step Process:
1. Open the Field Automation interface.
2. Navigate to the Visits section.
3. Locate the relevant planned or assigned visit.
4. Open the visit record.
5. Review the customer and visit details.
6. Complete the available visit actions according to your organization\'s process.
7. Enter any required notes or visit information.
8. Update the visit status if the interface supports it.
9. Save or submit the visit record.
10. Verify the updated status in the visit list.
Expected Result: The visit record should reflect the information successfully submitted by the user.
Troubleshooting:
* If a visit is missing, check the selected date and filters.
* If a visit cannot be updated, verify your permissions.
* If a visit status does not change, check for validation errors or required fields.',
                'keywords' => 'Field Automation, customer visit, visit record, visit status, assigned visits, field operations, visit tracking.',
                'status' => 'active',
            ],
            [
                'article_number' => 29,
                'module' => 'Field-Automation',
                'category' => 'Expense Management',
                'title' => 'How to Record and Submit Field Visit Expenses',
                'question' => 'How do I submit field expenses?
How can I record travel expenses?
How do I submit expenses for a customer visit?
Field expense submit korbo kivabe?
Travel expense record korbo kivabe?',
                'content' => 'Purpose: This guide explains the general process of submitting field expenses where expense functionality is available.
Step-by-Step Process:
1. Open the Field Automation interface.
2. Navigate to the Expenses section.
3. Select the option to create a new expense record.
4. Enter the required expense information, such as date, category, amount, and description.
5. Associate the expense with a visit or other relevant record if supported.
6. Attach receipts or supporting documents if required.
7. Review the expense information.
8. Submit the expense.
9. Check the expense status using the available interface.
10. Follow up with the authorized approver if necessary.
Expected Result: The expense should be recorded and submitted for the applicable review process.',
                'keywords' => 'Field Automation, expenses, field expenses, travel expenses, expense submission, expense approval, receipt.',
                'status' => 'active',
            ],
        ];

        foreach ($articles as $data) {
            KnowledgeArticle::updateOrCreate(
                ['title' => $data['title']],
                [
                    'module' => $data['module'],
                    'category' => $data['category'],
                    'question' => $data['question'],
                    'content' => $data['content'],
                    'keywords' => $data['keywords'],
                    'status' => $data['status'],
                    'created_by' => $adminId,
                ]
            );
        }

        Cache::forget('kb_active_articles');
        Cache::forget('kb_active_faqs');
    }
}
