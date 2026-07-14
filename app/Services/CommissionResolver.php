<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Exceptions\BookingBridge\SubscriptionInvalidException;
use App\Exceptions\BookingBridge\CommissionRateMissingException;
use App\Exceptions\BookingBridge\CommissionRateInvalidException;
use App\Exceptions\BookingBridge\ConfigurationErrorException;

class CommissionResolver
{
    public function resolve(VendorAccount $vendor): array
    {
        $maxRate = config('bookingbridge.commission.maximum_rate');
        
        if ($maxRate === null || !is_numeric($maxRate) || $maxRate <= 0) {
            throw new ConfigurationErrorException('Configurazione limite commerciale commissioni mancante o errata.');
        }

        if ($vendor->payment_model === 'SUBSCRIPTION') {
            if (!$vendor->subscribed('default')) {
                throw new SubscriptionInvalidException('La sottoscrizione del vendor non è attiva o è scaduta.');
            }
            return [
                'is_commission_based' => false,
                'commission_rate' => 0.0,
            ];
        }

        // payment_model == 'COMMISSION'
        $customRate = $vendor->custom_commission_rate;
        $categoryRate = $vendor->category?->commission_rate;

        if ($customRate !== null) {
            $rate = (float) $customRate;
        } elseif ($categoryRate !== null) {
            $rate = (float) $categoryRate;
        } else {
            throw new CommissionRateMissingException('Nessuna aliquota commissione configurata per questo vendor o categoria.');
        }

        if ($rate <= 0 || $rate > $maxRate) {
            throw new CommissionRateInvalidException("Aliquota commissione non valida: deve essere maggiore di 0 e minore o uguale a {$maxRate}.");
        }

        return [
            'is_commission_based' => true,
            'commission_rate' => $rate,
        ];
    }
}
