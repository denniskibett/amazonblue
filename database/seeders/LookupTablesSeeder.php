<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContactType;
use App\Models\AddressType;
use App\Models\AssetType;
use App\Models\ResidenceType;
use App\Models\PaymentMethodType;
use App\Models\EmploymentType;
use App\Models\RiskCategory;
use App\Models\RecoveryStatus;
use App\Models\RecoveryPriority;
use App\Models\DocumentType;
use App\Models\DocumentStatus;
use App\Models\ActionType;
use App\Models\LegalProceedingType;
use App\Models\CommunicationType;
use App\Models\CommunicationStatus;
use App\Models\HardshipReason;
use App\Models\BureauName;
use App\Models\EventType;

class LookupTablesSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding lookup tables...');

        // Contact Types
        $contactTypes = [
            ['name' => 'Parent', 'slug' => 'parent', 'sort_order' => 10],
            ['name' => 'Sibling', 'slug' => 'sibling', 'sort_order' => 20],
            ['name' => 'Spouse', 'slug' => 'spouse', 'sort_order' => 30],
            ['name' => 'Child', 'slug' => 'child', 'sort_order' => 40],
            ['name' => 'Relative', 'slug' => 'relative', 'sort_order' => 50],
            ['name' => 'Friend', 'slug' => 'friend', 'sort_order' => 60],
            ['name' => 'Business Partner', 'slug' => 'business_partner', 'sort_order' => 70],
            ['name' => 'Landlord', 'slug' => 'landlord', 'sort_order' => 80],
            ['name' => 'Tenant', 'slug' => 'tenant', 'sort_order' => 90],
            ['name' => 'Employer', 'slug' => 'employer', 'sort_order' => 100],
            ['name' => 'Colleague', 'slug' => 'colleague', 'sort_order' => 110],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'sort_order' => 120],
            ['name' => 'Chief', 'slug' => 'chief', 'sort_order' => 130],
            ['name' => 'HOA', 'slug' => 'hoa', 'sort_order' => 140],
            ['name' => 'Neighbor', 'slug' => 'neighbor', 'sort_order' => 150],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 999],
        ];
        foreach ($contactTypes as $type) {
            ContactType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Contact Types seeded: ' . count($contactTypes));

        // Address Types
        $addressTypes = [
            ['name' => 'Current', 'slug' => 'current', 'sort_order' => 10],
            ['name' => 'Previous', 'slug' => 'previous', 'sort_order' => 20],
            ['name' => 'Permanent', 'slug' => 'permanent', 'sort_order' => 30],
            ['name' => 'Postal', 'slug' => 'postal', 'sort_order' => 40],
            ['name' => 'Business', 'slug' => 'business', 'sort_order' => 50],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 999],
        ];
        foreach ($addressTypes as $type) {
            AddressType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Address Types seeded: ' . count($addressTypes));

        // Asset Types
        $assetTypes = [
            ['name' => 'Vehicle', 'slug' => 'vehicle', 'requires_registration' => true, 'sort_order' => 10],
            ['name' => 'Property', 'slug' => 'property', 'requires_registration' => true, 'sort_order' => 20],
            ['name' => 'Equipment', 'slug' => 'equipment', 'requires_registration' => false, 'sort_order' => 30],
            ['name' => 'Inventory', 'slug' => 'inventory', 'requires_registration' => false, 'sort_order' => 40],
            ['name' => 'Bank Account', 'slug' => 'bank_account', 'requires_registration' => false, 'sort_order' => 50],
            ['name' => 'Investment', 'slug' => 'investment', 'requires_registration' => false, 'sort_order' => 60],
            ['name' => 'Crypto Wallet', 'slug' => 'crypto_wallet', 'requires_registration' => false, 'sort_order' => 70],
            ['name' => 'Other', 'slug' => 'other', 'requires_registration' => false, 'sort_order' => 999],
        ];
        foreach ($assetTypes as $type) {
            AssetType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Asset Types seeded: ' . count($assetTypes));

        // Residence Types
        $residenceTypes = [
            ['name' => 'Owned', 'slug' => 'owned', 'sort_order' => 10],
            ['name' => 'Rented', 'slug' => 'rented', 'sort_order' => 20],
            ['name' => 'Family', 'slug' => 'family', 'sort_order' => 30],
            ['name' => 'Employer Housing', 'slug' => 'employer_housing', 'sort_order' => 40],
            ['name' => 'Government Housing', 'slug' => 'government_housing', 'sort_order' => 50],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 999],
        ];
        foreach ($residenceTypes as $type) {
            ResidenceType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Residence Types seeded: ' . count($residenceTypes));

        // Payment Method Types
        $paymentMethodTypes = [
            ['name' => 'Bank Account', 'slug' => 'bank_account', 'requires_verification' => true, 'sort_order' => 10],
            ['name' => 'Mobile Money', 'slug' => 'mobile_money', 'requires_verification' => true, 'sort_order' => 20],
            ['name' => 'Crypto Wallet', 'slug' => 'crypto_wallet', 'requires_verification' => false, 'sort_order' => 30],
            ['name' => 'PayPal', 'slug' => 'paypal', 'requires_verification' => true, 'sort_order' => 40],
            ['name' => 'Other', 'slug' => 'other', 'requires_verification' => false, 'sort_order' => 999],
        ];
        foreach ($paymentMethodTypes as $type) {
            PaymentMethodType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Payment Method Types seeded: ' . count($paymentMethodTypes));

        // Employment Types
        $employmentTypes = [
            ['name' => 'Full Time', 'slug' => 'full_time', 'sort_order' => 10],
            ['name' => 'Part Time', 'slug' => 'part_time', 'sort_order' => 20],
            ['name' => 'Contract', 'slug' => 'contract', 'sort_order' => 30],
            ['name' => 'Casual', 'slug' => 'casual', 'sort_order' => 40],
            ['name' => 'Self Employed', 'slug' => 'self_employed', 'sort_order' => 50],
            ['name' => 'Business Owner', 'slug' => 'business_owner', 'sort_order' => 60],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 999],
        ];
        foreach ($employmentTypes as $type) {
            EmploymentType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Employment Types seeded: ' . count($employmentTypes));

        // Risk Categories
        $riskCategories = [
            ['name' => 'Very High Risk', 'slug' => 'very_high_risk', 'min_score' => 0, 'max_score' => 49, 'color_code' => '#EF4444'],
            ['name' => 'High Risk', 'slug' => 'high_risk', 'min_score' => 50, 'max_score' => 64, 'color_code' => '#F59E0B'],
            ['name' => 'Medium Risk', 'slug' => 'medium_risk', 'min_score' => 65, 'max_score' => 79, 'color_code' => '#FBBF24'],
            ['name' => 'Low Risk', 'slug' => 'low_risk', 'min_score' => 80, 'max_score' => 100, 'color_code' => '#10B981'],
        ];
        foreach ($riskCategories as $category) {
            RiskCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }
        $this->command->info('✅ Risk Categories seeded: ' . count($riskCategories));

        // Recovery Statuses
        $recoveryStatuses = [
            ['name' => 'Open', 'slug' => 'open', 'sort_order' => 10],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'sort_order' => 20],
            ['name' => 'Negotiation', 'slug' => 'negotiation', 'sort_order' => 30],
            ['name' => 'Legal', 'slug' => 'legal', 'sort_order' => 40],
            ['name' => 'Recovered', 'slug' => 'recovered', 'sort_order' => 50],
            ['name' => 'Written Off', 'slug' => 'written_off', 'sort_order' => 60],
            ['name' => 'Closed', 'slug' => 'closed', 'sort_order' => 70],
        ];
        foreach ($recoveryStatuses as $status) {
            RecoveryStatus::updateOrCreate(['slug' => $status['slug']], $status);
        }
        $this->command->info('✅ Recovery Statuses seeded: ' . count($recoveryStatuses));

        // Recovery Priorities
        $recoveryPriorities = [
            ['name' => 'Low', 'slug' => 'low', 'priority_level' => 1, 'color_code' => '#6B7280'],
            ['name' => 'Medium', 'slug' => 'medium', 'priority_level' => 2, 'color_code' => '#F59E0B'],
            ['name' => 'High', 'slug' => 'high', 'priority_level' => 3, 'color_code' => '#EF4444'],
            ['name' => 'Urgent', 'slug' => 'urgent', 'priority_level' => 4, 'color_code' => '#DC2626'],
        ];
        foreach ($recoveryPriorities as $priority) {
            RecoveryPriority::updateOrCreate(['slug' => $priority['slug']], $priority);
        }
        $this->command->info('✅ Recovery Priorities seeded: ' . count($recoveryPriorities));

        // Document Types
        $documentTypes = [
            ['name' => 'Loan Agreement', 'slug' => 'loan_agreement', 'requires_verification' => true],
            ['name' => 'ID Copy', 'slug' => 'id_copy', 'requires_verification' => true],
            ['name' => 'Income Proof', 'slug' => 'income_proof', 'requires_verification' => true],
            ['name' => 'Address Proof', 'slug' => 'address_proof', 'requires_verification' => true],
            ['name' => 'Letter', 'slug' => 'letter', 'requires_verification' => false],
            ['name' => 'Court Document', 'slug' => 'court_document', 'requires_verification' => true],
            ['name' => 'Payment Receipt', 'slug' => 'payment_receipt', 'requires_verification' => true],
            ['name' => 'Other', 'slug' => 'other', 'requires_verification' => false],
        ];
        foreach ($documentTypes as $type) {
            DocumentType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Document Types seeded: ' . count($documentTypes));

        // Document Statuses
        $documentStatuses = [
            ['name' => 'Pending', 'slug' => 'pending'],
            ['name' => 'Verified', 'slug' => 'verified'],
            ['name' => 'Rejected', 'slug' => 'rejected'],
            ['name' => 'Expired', 'slug' => 'expired'],
        ];
        foreach ($documentStatuses as $status) {
            DocumentStatus::updateOrCreate(['slug' => $status['slug']], $status);
        }
        $this->command->info('✅ Document Statuses seeded: ' . count($documentStatuses));

        // Action Types
        $actionTypes = [
            ['name' => 'Phone Call', 'slug' => 'phone_call', 'sort_order' => 10],
            ['name' => 'SMS', 'slug' => 'sms', 'sort_order' => 20],
            ['name' => 'Email', 'slug' => 'email', 'sort_order' => 30],
            ['name' => 'Visit', 'slug' => 'visit', 'sort_order' => 40],
            ['name' => 'Letter', 'slug' => 'letter', 'sort_order' => 50],
            ['name' => 'Legal Notice', 'slug' => 'legal_notice', 'sort_order' => 60],
            ['name' => 'Negotiation', 'slug' => 'negotiation', 'sort_order' => 70],
            ['name' => 'Payment Arrangement', 'slug' => 'payment_arrangement', 'sort_order' => 80],
            ['name' => 'Field Visit', 'slug' => 'field_visit', 'sort_order' => 90],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 999],
        ];
        foreach ($actionTypes as $type) {
            ActionType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Action Types seeded: ' . count($actionTypes));

        // Legal Proceeding Types
        $legalProceedingTypes = [
            ['name' => 'Demand Letter', 'slug' => 'demand_letter', 'sort_order' => 10],
            ['name' => 'Court Filing', 'slug' => 'court_filing', 'sort_order' => 20],
            ['name' => 'Judgment', 'slug' => 'judgment', 'sort_order' => 30],
            ['name' => 'Writ of Attachment', 'slug' => 'writ_of_attachment', 'sort_order' => 40],
            ['name' => 'Garnishment', 'slug' => 'garnishment', 'sort_order' => 50],
            ['name' => 'Bankruptcy Notice', 'slug' => 'bankruptcy_notice', 'sort_order' => 60],
            ['name' => 'Settlement', 'slug' => 'settlement', 'sort_order' => 70],
            ['name' => 'Appeal', 'slug' => 'appeal', 'sort_order' => 80],
            ['name' => 'Other', 'slug' => 'other', 'sort_order' => 999],
        ];
        foreach ($legalProceedingTypes as $type) {
            LegalProceedingType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Legal Proceeding Types seeded: ' . count($legalProceedingTypes));

        // Communication Types
        $communicationTypes = [
            ['name' => 'SMS', 'slug' => 'sms', 'sort_order' => 10],
            ['name' => 'Email', 'slug' => 'email', 'sort_order' => 20],
            ['name' => 'WhatsApp', 'slug' => 'whatsapp', 'sort_order' => 30],
            ['name' => 'Letter', 'slug' => 'letter', 'sort_order' => 40],
            ['name' => 'Phone Call', 'slug' => 'phone_call', 'sort_order' => 50],
        ];
        foreach ($communicationTypes as $type) {
            CommunicationType::updateOrCreate(['slug' => $type['slug']], $type);
        }
        $this->command->info('✅ Communication Types seeded: ' . count($communicationTypes));

        // Communication Statuses
        $communicationStatuses = [
            ['name' => 'Sent', 'slug' => 'sent'],
            ['name' => 'Delivered', 'slug' => 'delivered'],
            ['name' => 'Failed', 'slug' => 'failed'],
            ['name' => 'Read', 'slug' => 'read'],
            ['name' => 'Replied', 'slug' => 'replied'],
        ];
        foreach ($communicationStatuses as $status) {
            CommunicationStatus::updateOrCreate(['slug' => $status['slug']], $status);
        }
        $this->command->info('✅ Communication Statuses seeded: ' . count($communicationStatuses));

        // Hardship Reasons
        $hardshipReasons = [
            ['name' => 'Job Loss', 'slug' => 'job_loss'],
            ['name' => 'Medical Emergency', 'slug' => 'medical_emergency'],
            ['name' => 'Business Failure', 'slug' => 'business_failure'],
            ['name' => 'Natural Disaster', 'slug' => 'natural_disaster'],
            ['name' => 'Death in Family', 'slug' => 'death_in_family'],
            ['name' => 'Divorce', 'slug' => 'divorce'],
            ['name' => 'Other', 'slug' => 'other'],
        ];
        foreach ($hardshipReasons as $reason) {
            HardshipReason::updateOrCreate(['slug' => $reason['slug']], $reason);
        }
        $this->command->info('✅ Hardship Reasons seeded: ' . count($hardshipReasons));

        // Bureau Names
        $bureauNames = [
            ['name' => 'CRB Kenya', 'slug' => 'crb_kenya'],
            ['name' => 'TransUnion', 'slug' => 'transunion'],
            ['name' => 'Equifax', 'slug' => 'equifax'],
            ['name' => 'Experian', 'slug' => 'experian'],
            ['name' => 'Other', 'slug' => 'other'],
        ];
        foreach ($bureauNames as $bureau) {
            BureauName::updateOrCreate(['slug' => $bureau['slug']], $bureau);
        }
        $this->command->info('✅ Bureau Names seeded: ' . count($bureauNames));

        // Event Types
        $eventTypes = [
            ['name' => 'Case Created', 'slug' => 'case_created', 'icon' => 'fa-plus-circle', 'color_code' => '#10B981'],
            ['name' => 'Action Taken', 'slug' => 'action_taken', 'icon' => 'fa-phone', 'color_code' => '#3B82F6'],
            ['name' => 'Communication Sent', 'slug' => 'communication_sent', 'icon' => 'fa-envelope', 'color_code' => '#8B5CF6'],
            ['name' => 'Payment Received', 'slug' => 'payment_received', 'icon' => 'fa-money-bill-wave', 'color_code' => '#10B981'],
            ['name' => 'Payment Plan Created', 'slug' => 'payment_plan_created', 'icon' => 'fa-file-contract', 'color_code' => '#F59E0B'],
            ['name' => 'Legal Proceeding', 'slug' => 'legal_proceeding', 'icon' => 'fa-gavel', 'color_code' => '#EF4444'],
            ['name' => 'Document Uploaded', 'slug' => 'document_uploaded', 'icon' => 'fa-file-upload', 'color_code' => '#6B7280'],
            ['name' => 'Status Changed', 'slug' => 'status_changed', 'icon' => 'fa-exchange-alt', 'color_code' => '#3B82F6'],
            ['name' => 'Note Added', 'slug' => 'note_added', 'icon' => 'fa-sticky-note', 'color_code' => '#F59E0B'],
            ['name' => 'Task Created', 'slug' => 'task_created', 'icon' => 'fa-tasks', 'color_code' => '#8B5CF6'],
            ['name' => 'Task Completed', 'slug' => 'task_completed', 'icon' => 'fa-check-circle', 'color_code' => '#10B981'],
            ['name' => 'Agency Assigned', 'slug' => 'agency_assigned', 'icon' => 'fa-handshake', 'color_code' => '#3B82F6'],
            ['name' => 'Case Resolved', 'slug' => 'case_resolved', 'icon' => 'fa-check-double', 'color_code' => '#10B981'],
        ];
        foreach ($eventTypes as $event) {
            EventType::updateOrCreate(['slug' => $event['slug']], $event);
        }
        $this->command->info('✅ Event Types seeded: ' . count($eventTypes));

        $this->command->info('🎉 All lookup tables seeded successfully!');
    }
}