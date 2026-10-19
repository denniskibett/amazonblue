<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kind of payout/payment method (bank_account, mobile_money, crypto_wallet, …): table payment_method_types.
 */
class PaymentMethodType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'requires_verification',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_verification' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'method_type_id');
    }
}
