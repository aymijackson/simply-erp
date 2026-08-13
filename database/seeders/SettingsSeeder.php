<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\SettingGroup;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'code' => 'core.company',
                'name' => 'Company Profile',
                'module' => 'core',
                'description' => 'Company identity used on documents',
                'sort_order' => 10,
            ],
            [
                'code' => 'sales.receipts',
                'name' => 'Sales Receipts',
                'module' => 'sales',
                'description' => 'Receipt/PDF settings for Sales module',
                'sort_order' => 20,
            ],
            [
                'code' => 'finance_accounting',
                'name' => 'Finance Accounting',
                'module' => 'finance',
                'description' => 'Finance posting & closing settings',
                'sort_order' => 10,
            ],
            [
                'code' => 'procurement',
                'name' => 'Procurement Settings',
                'module' => 'procurement',
                'description' => 'Settings for procurement workflows',
                'sort_order' => 30,
            ],
        ];

        $groupIds = [];

        foreach ($groups as $g) {
            $group = SettingGroup::updateOrCreate(
                ['code' => $g['code']],
                [
                    'name' => $g['name'],
                    'module' => $g['module'],
                    'description' => $g['description'],
                    'sort_order' => $g['sort_order'],
                    'is_active' => true,
                ]
            );

            $groupIds[$g['code']] = $group->id;
        }

        $settings = [
            // core.company
            ['group' => 'core.company', 'key' => 'company_name', 'label' => 'Company Name', 'description' => 'Shown on invoices/receipts', 'value' => 'THEKAN', 'value_type' => 'string', 'scope' => 'global', 'scope_id' => null, 'is_required' => true, 'sort_order' => 10],
            ['group' => 'core.company', 'key' => 'company_address', 'label' => 'Company Address', 'description' => 'Shown on documents', 'value' => 'Company Address Line 1', 'value_type' => 'text', 'scope' => 'global', 'scope_id' => null, 'is_required' => true, 'sort_order' => 20],
            ['group' => 'core.company', 'key' => 'company_phone', 'label' => 'Company Phone', 'description' => 'Shown on documents', 'value' => '+234000000000', 'value_type' => 'phone', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 30],
            ['group' => 'core.company', 'key' => 'company_email', 'label' => 'Company Email', 'description' => 'Shown on documents', 'value' => 'support@example.com', 'value_type' => 'email', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 40],
            ['group' => 'core.company', 'key' => 'company_logo', 'label' => 'Company Logo', 'description' => 'File path stored (public/uploads/..)', 'value' => null, 'value_type' => 'file', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 50],

            // sales.receipts
            ['group' => 'sales.receipts', 'key' => 'receipt_footer_note', 'label' => 'Receipt Footer Note', 'description' => 'Printed at the bottom of receipts', 'value' => 'Thanks for your business.', 'value_type' => 'text', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 10],
            ['group' => 'sales.receipts', 'key' => 'receipt_show_qr', 'label' => 'Show QR Verification', 'description' => 'Show QR code on receipt', 'value' => '1', 'value_type' => 'bool', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 20],

            // finance_accounting (company scope_id 1)
            ['group' => 'finance_accounting', 'key' => 'retained_earnings_account_id', 'label' => 'Retained Earnings Account', 'description' => 'Account used to store accumulated profit/loss.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => true, 'sort_order' => 10],
            ['group' => 'finance_accounting', 'key' => 'income_summary_account_id', 'label' => 'Income Summary Account', 'description' => 'Temporary account used during year-end close to clear income/expense.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => true, 'sort_order' => 20],
            ['group' => 'finance_accounting', 'key' => 'ar_control_account_id', 'label' => 'Accounts Receivable (Control)', 'description' => 'Control account for customer receivables.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => true, 'sort_order' => 30],
            ['group' => 'finance_accounting', 'key' => 'ap_control_account_id', 'label' => 'Accounts Payable (Control)', 'description' => 'Control account for supplier payables.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => true, 'sort_order' => 40],
            ['group' => 'finance_accounting', 'key' => 'default_cash_account_id', 'label' => 'Default Cash/Bank Account', 'description' => 'Default bank/cash account for receipts/payments if not specified.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => false, 'sort_order' => 50],
            ['group' => 'finance_accounting', 'key' => 'vat_output_account_id', 'label' => 'VAT Output Account', 'description' => 'VAT output (sales VAT) account.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => false, 'sort_order' => 60],
            ['group' => 'finance_accounting', 'key' => 'vat_input_account_id', 'label' => 'VAT Input Account', 'description' => 'VAT input (purchase VAT) account.', 'value' => null, 'value_type' => 'int', 'scope' => 'company', 'scope_id' => 1, 'is_required' => false, 'sort_order' => 70],

            // procurement
            ['group' => 'procurement', 'key' => 'procurement.rfq_allow_awarded_for_supplier_quotation', 'label' => 'Allow Awarded RFQs in Supplier Quotation Lookup', 'description' => 'If enabled, awarded RFQs can still be selected when creating supplier quotations.', 'value' => '1', 'value_type' => 'bool', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 10],
            ['group' => 'procurement', 'key' => 'procurement.rfq_allow_cancelled_for_supplier_quotation', 'label' => 'Allow Cancelled RFQs in Supplier Quotation Lookup', 'description' => 'If enabled, cancelled RFQs can still be selected when creating supplier quotations.', 'value' => '1', 'value_type' => 'bool', 'scope' => 'global', 'scope_id' => null, 'is_required' => false, 'sort_order' => 11],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                [
                    'scope' => $s['scope'],
                    'scope_id' => $s['scope_id'],
                    'key' => $s['key'],
                ],
                [
                    'setting_group_id' => $groupIds[$s['group']],
                    'label' => $s['label'],
                    'description' => $s['description'],
                    'value' => $s['value'],
                    'value_type' => $s['value_type'],
                    'is_sensitive' => false,
                    'is_required' => $s['is_required'],
                    'is_active' => true,
                    'sort_order' => $s['sort_order'],
                ]
            );
        }
    }
}
