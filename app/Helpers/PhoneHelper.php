<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Clean and format phone numbers
     * Default country is Kenya (KE)
     */
    public static function cleanPhoneNumber($phone, $countryCode = 'KE')
    {
        if (empty($phone) || $phone === 'N/A') {
            return 'N/A';
        }

        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Kenya numbers specifically
        if ($countryCode === 'KE') {
            // Remove leading 0 and add country code
            if (strlen($cleaned) === 9 && substr($cleaned, 0, 1) === '7') {
                $cleaned = '254' . $cleaned;
            }
            // Handle numbers starting with 0
            elseif (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
                $cleaned = '254' . substr($cleaned, 1);
            }
            // Handle numbers already with country code
            elseif (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '254') {
                // Already in correct format
            }
            // Handle numbers with +254
            elseif (strlen($cleaned) === 12 && substr($phone, 0, 1) === '+') {
                // Already cleaned to 254 format
            }
        }

        // Format for display: +254 XXX XXX XXX
        if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '254') {
            return '+254 ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6, 3) . substr($cleaned, 9, 3);
        }

        // If we can't format it properly, return the original
        return $phone;
    }

    /**
     * Extract just the digits for storage
     */
    public static function normalizePhoneNumber($phone, $countryCode = 'KE')
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle Kenya numbers
        if ($countryCode === 'KE') {
            if (strlen($cleaned) === 9 && substr($cleaned, 0, 1) === '7') {
                return '254' . $cleaned;
            }
            if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
                return '254' . substr($cleaned, 1);
            }
            if (strlen($cleaned) === 12 && substr($cleaned, 0, 3) === '254') {
                return $cleaned;
            }
        }

        return $cleaned;
    }
}