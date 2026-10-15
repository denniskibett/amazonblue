<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lookup values the app's forms and reports rely on (types, statuses, dropdown categories, loan products).
 * Taken from the live data. Safe to run any time: rows are matched on their natural key and only
 * missing ones are added, so existing values (and anything edited in the app) are never overwritten.
 */
class LookupTablesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seed('contact_types', 'slug', [
            ['id' => 1, 'name' => 'Parent', 'slug' => 'parent', 'description' => null, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Sibling', 'slug' => 'sibling', 'description' => null, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Spouse', 'slug' => 'spouse', 'description' => null, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Child', 'slug' => 'child', 'description' => null, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Relative', 'slug' => 'relative', 'description' => null, 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Friend', 'slug' => 'friend', 'description' => null, 'is_active' => 1, 'sort_order' => 60],
            ['id' => 7, 'name' => 'Business Partner', 'slug' => 'business_partner', 'description' => null, 'is_active' => 1, 'sort_order' => 70],
            ['id' => 8, 'name' => 'Landlord', 'slug' => 'landlord', 'description' => null, 'is_active' => 1, 'sort_order' => 80],
            ['id' => 9, 'name' => 'Tenant', 'slug' => 'tenant', 'description' => null, 'is_active' => 1, 'sort_order' => 90],
            ['id' => 10, 'name' => 'Employer', 'slug' => 'employer', 'description' => null, 'is_active' => 1, 'sort_order' => 100],
            ['id' => 11, 'name' => 'Colleague', 'slug' => 'colleague', 'description' => null, 'is_active' => 1, 'sort_order' => 110],
            ['id' => 12, 'name' => 'Supervisor', 'slug' => 'supervisor', 'description' => null, 'is_active' => 1, 'sort_order' => 120],
            ['id' => 13, 'name' => 'Chief', 'slug' => 'chief', 'description' => null, 'is_active' => 1, 'sort_order' => 130],
            ['id' => 14, 'name' => 'HOA', 'slug' => 'hoa', 'description' => null, 'is_active' => 1, 'sort_order' => 140],
            ['id' => 15, 'name' => 'Neighbor', 'slug' => 'neighbor', 'description' => null, 'is_active' => 1, 'sort_order' => 150],
            ['id' => 16, 'name' => 'Other', 'slug' => 'other', 'description' => null, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('address_types', 'slug', [
            ['id' => 1, 'name' => 'Current', 'slug' => 'current', 'description' => null, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Previous', 'slug' => 'previous', 'description' => null, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Permanent', 'slug' => 'permanent', 'description' => null, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Postal', 'slug' => 'postal', 'description' => null, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Business', 'slug' => 'business', 'description' => null, 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Other', 'slug' => 'other', 'description' => null, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('asset_types', 'slug', [
            ['id' => 1, 'name' => 'Vehicle', 'slug' => 'vehicle', 'requires_registration' => 1, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Property', 'slug' => 'property', 'requires_registration' => 1, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Equipment', 'slug' => 'equipment', 'requires_registration' => 0, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Inventory', 'slug' => 'inventory', 'requires_registration' => 0, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Bank Account', 'slug' => 'bank_account', 'requires_registration' => 0, 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Investment', 'slug' => 'investment', 'requires_registration' => 0, 'is_active' => 1, 'sort_order' => 60],
            ['id' => 7, 'name' => 'Crypto Wallet', 'slug' => 'crypto_wallet', 'requires_registration' => 0, 'is_active' => 1, 'sort_order' => 70],
            ['id' => 8, 'name' => 'Other', 'slug' => 'other', 'requires_registration' => 0, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('residence_types', 'slug', [
            ['id' => 1, 'name' => 'Owned', 'slug' => 'owned', 'description' => null, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Rented', 'slug' => 'rented', 'description' => null, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Family', 'slug' => 'family', 'description' => null, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Employer Housing', 'slug' => 'employer_housing', 'description' => null, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Government Housing', 'slug' => 'government_housing', 'description' => null, 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Other', 'slug' => 'other', 'description' => null, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('payment_method_types', 'slug', [
            ['id' => 1, 'name' => 'Bank Account', 'slug' => 'bank_account', 'requires_verification' => 1, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Mobile Money', 'slug' => 'mobile_money', 'requires_verification' => 1, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Crypto Wallet', 'slug' => 'crypto_wallet', 'requires_verification' => 0, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'PayPal', 'slug' => 'paypal', 'requires_verification' => 1, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Other', 'slug' => 'other', 'requires_verification' => 0, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('employment_types', 'slug', [
            ['id' => 1, 'name' => 'Full Time', 'slug' => 'full_time', 'description' => null, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Part Time', 'slug' => 'part_time', 'description' => null, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Contract', 'slug' => 'contract', 'description' => null, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Casual', 'slug' => 'casual', 'description' => null, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Self Employed', 'slug' => 'self_employed', 'description' => null, 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Business Owner', 'slug' => 'business_owner', 'description' => null, 'is_active' => 1, 'sort_order' => 60],
            ['id' => 7, 'name' => 'Other', 'slug' => 'other', 'description' => null, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('risk_categories', 'slug', [
            ['id' => 1, 'name' => 'Very High Risk', 'slug' => 'very_high_risk', 'min_score' => 0, 'max_score' => 49, 'color_code' => '#EF4444', 'description' => null],
            ['id' => 2, 'name' => 'High Risk', 'slug' => 'high_risk', 'min_score' => 50, 'max_score' => 64, 'color_code' => '#F59E0B', 'description' => null],
            ['id' => 3, 'name' => 'Medium Risk', 'slug' => 'medium_risk', 'min_score' => 65, 'max_score' => 79, 'color_code' => '#FBBF24', 'description' => null],
            ['id' => 4, 'name' => 'Low Risk', 'slug' => 'low_risk', 'min_score' => 80, 'max_score' => 100, 'color_code' => '#10B981', 'description' => null],
        ]);

        $this->seed('recovery_statuses', 'slug', [
            ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'In Progress', 'slug' => 'in_progress', 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Negotiation', 'slug' => 'negotiation', 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Legal', 'slug' => 'legal', 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Recovered', 'slug' => 'recovered', 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Written Off', 'slug' => 'written_off', 'is_active' => 1, 'sort_order' => 60],
            ['id' => 7, 'name' => 'Closed', 'slug' => 'closed', 'is_active' => 1, 'sort_order' => 70],
        ]);

        $this->seed('recovery_priorities', 'slug', [
            ['id' => 1, 'name' => 'Low', 'slug' => 'low', 'priority_level' => 1, 'color_code' => '#6B7280'],
            ['id' => 2, 'name' => 'Medium', 'slug' => 'medium', 'priority_level' => 2, 'color_code' => '#F59E0B'],
            ['id' => 3, 'name' => 'High', 'slug' => 'high', 'priority_level' => 3, 'color_code' => '#EF4444'],
            ['id' => 4, 'name' => 'Urgent', 'slug' => 'urgent', 'priority_level' => 4, 'color_code' => '#DC2626'],
        ]);

        $this->seed('document_types', 'slug', [
            ['id' => 1, 'name' => 'Loan Agreement', 'slug' => 'loan_agreement', 'requires_verification' => 1, 'is_active' => 1],
            ['id' => 2, 'name' => 'ID Copy', 'slug' => 'id_copy', 'requires_verification' => 1, 'is_active' => 1],
            ['id' => 3, 'name' => 'Income Proof', 'slug' => 'income_proof', 'requires_verification' => 1, 'is_active' => 1],
            ['id' => 4, 'name' => 'Address Proof', 'slug' => 'address_proof', 'requires_verification' => 1, 'is_active' => 1],
            ['id' => 5, 'name' => 'Letter', 'slug' => 'letter', 'requires_verification' => 0, 'is_active' => 1],
            ['id' => 6, 'name' => 'Court Document', 'slug' => 'court_document', 'requires_verification' => 1, 'is_active' => 1],
            ['id' => 7, 'name' => 'Payment Receipt', 'slug' => 'payment_receipt', 'requires_verification' => 1, 'is_active' => 1],
            ['id' => 8, 'name' => 'Other', 'slug' => 'other', 'requires_verification' => 0, 'is_active' => 1],
        ]);

        $this->seed('document_statuses', 'slug', [
            ['id' => 1, 'name' => 'Pending', 'slug' => 'pending', 'description' => null, 'is_active' => 1],
            ['id' => 2, 'name' => 'Verified', 'slug' => 'verified', 'description' => null, 'is_active' => 1],
            ['id' => 3, 'name' => 'Rejected', 'slug' => 'rejected', 'description' => null, 'is_active' => 1],
            ['id' => 4, 'name' => 'Expired', 'slug' => 'expired', 'description' => null, 'is_active' => 1],
        ]);

        $this->seed('action_types', 'slug', [
            ['id' => 1, 'name' => 'Phone Call', 'slug' => 'phone_call', 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'SMS', 'slug' => 'sms', 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Email', 'slug' => 'email', 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Visit', 'slug' => 'visit', 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Letter', 'slug' => 'letter', 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Legal Notice', 'slug' => 'legal_notice', 'is_active' => 1, 'sort_order' => 60],
            ['id' => 7, 'name' => 'Negotiation', 'slug' => 'negotiation', 'is_active' => 1, 'sort_order' => 70],
            ['id' => 8, 'name' => 'Payment Arrangement', 'slug' => 'payment_arrangement', 'is_active' => 1, 'sort_order' => 80],
            ['id' => 9, 'name' => 'Field Visit', 'slug' => 'field_visit', 'is_active' => 1, 'sort_order' => 90],
            ['id' => 10, 'name' => 'Other', 'slug' => 'other', 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('legal_proceeding_types', 'slug', [
            ['id' => 1, 'name' => 'Demand Letter', 'slug' => 'demand_letter', 'description' => null, 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Court Filing', 'slug' => 'court_filing', 'description' => null, 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'Judgment', 'slug' => 'judgment', 'description' => null, 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Writ of Attachment', 'slug' => 'writ_of_attachment', 'description' => null, 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Garnishment', 'slug' => 'garnishment', 'description' => null, 'is_active' => 1, 'sort_order' => 50],
            ['id' => 6, 'name' => 'Bankruptcy Notice', 'slug' => 'bankruptcy_notice', 'description' => null, 'is_active' => 1, 'sort_order' => 60],
            ['id' => 7, 'name' => 'Settlement', 'slug' => 'settlement', 'description' => null, 'is_active' => 1, 'sort_order' => 70],
            ['id' => 8, 'name' => 'Appeal', 'slug' => 'appeal', 'description' => null, 'is_active' => 1, 'sort_order' => 80],
            ['id' => 9, 'name' => 'Other', 'slug' => 'other', 'description' => null, 'is_active' => 1, 'sort_order' => 999],
        ]);

        $this->seed('communication_types', 'slug', [
            ['id' => 1, 'name' => 'SMS', 'slug' => 'sms', 'is_active' => 1, 'sort_order' => 10],
            ['id' => 2, 'name' => 'Email', 'slug' => 'email', 'is_active' => 1, 'sort_order' => 20],
            ['id' => 3, 'name' => 'WhatsApp', 'slug' => 'whatsapp', 'is_active' => 1, 'sort_order' => 30],
            ['id' => 4, 'name' => 'Letter', 'slug' => 'letter', 'is_active' => 1, 'sort_order' => 40],
            ['id' => 5, 'name' => 'Phone Call', 'slug' => 'phone_call', 'is_active' => 1, 'sort_order' => 50],
        ]);

        $this->seed('communication_statuses', 'slug', [
            ['id' => 1, 'name' => 'Sent', 'slug' => 'sent', 'description' => null, 'is_active' => 1],
            ['id' => 2, 'name' => 'Delivered', 'slug' => 'delivered', 'description' => null, 'is_active' => 1],
            ['id' => 3, 'name' => 'Failed', 'slug' => 'failed', 'description' => null, 'is_active' => 1],
            ['id' => 4, 'name' => 'Read', 'slug' => 'read', 'description' => null, 'is_active' => 1],
            ['id' => 5, 'name' => 'Replied', 'slug' => 'replied', 'description' => null, 'is_active' => 1],
        ]);

        $this->seed('hardship_reasons', 'slug', [
            ['id' => 1, 'name' => 'Job Loss', 'slug' => 'job_loss', 'description' => null, 'is_active' => 1],
            ['id' => 2, 'name' => 'Medical Emergency', 'slug' => 'medical_emergency', 'description' => null, 'is_active' => 1],
            ['id' => 3, 'name' => 'Business Failure', 'slug' => 'business_failure', 'description' => null, 'is_active' => 1],
            ['id' => 4, 'name' => 'Natural Disaster', 'slug' => 'natural_disaster', 'description' => null, 'is_active' => 1],
            ['id' => 5, 'name' => 'Death in Family', 'slug' => 'death_in_family', 'description' => null, 'is_active' => 1],
            ['id' => 6, 'name' => 'Divorce', 'slug' => 'divorce', 'description' => null, 'is_active' => 1],
            ['id' => 7, 'name' => 'Other', 'slug' => 'other', 'description' => null, 'is_active' => 1],
        ]);

        $this->seed('bureau_names', 'slug', [
            ['id' => 1, 'name' => 'CRB Kenya', 'slug' => 'crb_kenya', 'website' => null, 'is_active' => 1],
            ['id' => 2, 'name' => 'TransUnion', 'slug' => 'transunion', 'website' => null, 'is_active' => 1],
            ['id' => 3, 'name' => 'Equifax', 'slug' => 'equifax', 'website' => null, 'is_active' => 1],
            ['id' => 4, 'name' => 'Experian', 'slug' => 'experian', 'website' => null, 'is_active' => 1],
            ['id' => 5, 'name' => 'Other', 'slug' => 'other', 'website' => null, 'is_active' => 1],
        ]);

        $this->seed('categories', ['category_type', 'name'], [
            ['id' => 1, 'name' => 'Christian', 'category_type' => 'religion', 'categoryable_id' => 1],
            ['id' => 2, 'name' => 'Muslim', 'category_type' => 'religion', 'categoryable_id' => 1],
            ['id' => 3, 'name' => 'Atheist', 'category_type' => 'religion', 'categoryable_id' => 1],
            ['id' => 4, 'name' => 'Hindu', 'category_type' => 'religion', 'categoryable_id' => 1],
            ['id' => 5, 'name' => 'Traditional', 'category_type' => 'religion', 'categoryable_id' => 1],
            ['id' => 11, 'name' => 'Primary', 'category_type' => 'education', 'categoryable_id' => 2],
            ['id' => 12, 'name' => 'Secondary', 'category_type' => 'education', 'categoryable_id' => 2],
            ['id' => 13, 'name' => 'Diploma', 'category_type' => 'education', 'categoryable_id' => 2],
            ['id' => 14, 'name' => 'Bachelor\'s Degree', 'category_type' => 'education', 'categoryable_id' => 2],
            ['id' => 15, 'name' => 'Master\'s Degree', 'category_type' => 'education', 'categoryable_id' => 2],
            ['id' => 16, 'name' => 'PhD', 'category_type' => 'education', 'categoryable_id' => 2],
            ['id' => 27, 'name' => 'Brother', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 28, 'name' => 'Sister', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 29, 'name' => 'Father', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 30, 'name' => 'Mother', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 31, 'name' => 'Aunt', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 32, 'name' => 'Uncle', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 33, 'name' => 'Aunt', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 34, 'name' => 'Grand Mother', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 35, 'name' => 'Grand Father', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 36, 'name' => 'Friend', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 37, 'name' => 'Business Partner', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 38, 'name' => 'Work Colleague', 'category_type' => 'relationship', 'categoryable_id' => 3],
            ['id' => 39, 'name' => 'Employment', 'category_type' => 'income_type', 'categoryable_id' => 4],
            ['id' => 40, 'name' => 'Business', 'category_type' => 'income_type', 'categoryable_id' => 4],
            ['id' => 41, 'name' => 'Asset', 'category_type' => 'income_type', 'categoryable_id' => 4],
            ['id' => 42, 'name' => 'Government', 'category_type' => 'income_type', 'categoryable_id' => 4],
            ['id' => 43, 'name' => 'Remittances', 'category_type' => 'income_type', 'categoryable_id' => 4],
        ]);

        $this->seed('loan_types', 'name', [
            ['id' => 1, 'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k!'],
            ['id' => 2, 'name' => 'Quick Loans 1 month 20%', 'period' => 1, 'unit' => 'months', 'interest_rate' => 20.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! Monthly'],
            ['id' => 3, 'name' => 'Quick Loans 14', 'period' => 2, 'unit' => 'weeks', 'interest_rate' => 20.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! 2 weeks!'],
            ['id' => 4, 'name' => 'Quick Loans 3 months', 'period' => 3, 'unit' => 'months', 'interest_rate' => 95.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! 2 weeks!'],
            ['id' => 5, 'name' => 'Quick Loans 1 months 30%', 'period' => 1, 'unit' => 'months', 'interest_rate' => 30.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! 2 weeks!'],
            ['id' => 6, 'name' => 'Emergency Loans 2 weeks 35%', 'period' => 2, 'unit' => 'weeks', 'interest_rate' => 35.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! 2 weeks!'],
            ['id' => 7, 'name' => 'Quick Loans 1 month 10%', 'period' => 1, 'unit' => 'months', 'interest_rate' => 10.00, 'penalty_rate' => 1.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! Monthly'],
            ['id' => 8, 'name' => 'Quick Loans 2 weeks +1 repayment week at 20%', 'period' => 3, 'unit' => 'weeks', 'interest_rate' => 20.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 1m! Monthly'],
            ['id' => 9, 'name' => 'Quick Loans 3 months 50% ', 'period' => 3, 'unit' => 'months', 'interest_rate' => 50.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! 2 weeks!'],
            ['id' => 10, 'name' => 'Quick Loans 3 months 10% monthly', 'period' => 3, 'unit' => 'months', 'interest_rate' => 30.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 300k! 2 weeks!'],
            ['id' => 11, 'name' => 'Quick Loans 2 months 15% monthly', 'period' => 2, 'unit' => 'months', 'interest_rate' => 30.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 300k! 2 weeks!'],
            ['id' => 12, 'name' => 'Quick Loans 2 months 20% monthly', 'period' => 2, 'unit' => 'months', 'interest_rate' => 20.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 300k! 2 weeks!'],
            ['id' => 13, 'name' => 'Quick Loans 3 months 60% ', 'period' => 3, 'unit' => 'months', 'interest_rate' => 60.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k! 2 weeks!'],
            ['id' => 14, 'name' => 'Emergency 5 Days', 'period' => 5, 'unit' => 'days', 'interest_rate' => 10.00, 'penalty_rate' => 7.50, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k!'],
            ['id' => 15, 'name' => 'Family 3 months 2% monthly', 'period' => 3, 'unit' => 'months', 'interest_rate' => 6.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 300k! 2 weeks!'],
            ['id' => 16, 'name' => '7 days 25%', 'period' => 7, 'unit' => 'days', 'interest_rate' => 25.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 300k! 2 weeks!'],
            ['id' => 17, 'name' => '3 weeks 20%', 'period' => 3, 'unit' => 'weeks', 'interest_rate' => 20.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 300k! 3 weeks!'],
            ['id' => 18, 'name' => 'Emergency 10 Days at 30%', 'period' => 10, 'unit' => 'days', 'interest_rate' => 30.00, 'penalty_rate' => 10.00, 'grace_period_days' => 0, 'default_threshold_days' => 30, 'description' => 'Upto 250k!'],
        ]);

        $this->command?->info('Lookup tables seeded.');
    }

    /**
     * Insert each row unless one with the same natural key already exists.
     * The original id is kept when it is free, so ids match the live database.
     */
    private function seed(string $table, string|array $key, array $rows): void
    {
        $now = now();
        $added = 0;

        foreach ($rows as $row) {
            $match = array_intersect_key($row, array_flip((array) $key));
            if (DB::table($table)->where($match)->exists()) {
                continue;
            }
            if (DB::table($table)->where('id', $row['id'])->exists()) {
                unset($row['id']);
            }
            DB::table($table)->insert($row + ['created_at' => $now, 'updated_at' => $now]);
            $added++;
        }

        if ($added) {
            $this->command?->line("  {$table}: {$added} added");
        }
    }
}
