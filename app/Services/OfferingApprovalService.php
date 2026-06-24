<?php

namespace App\Services;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\Offering;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OfferingApprovalService
{
    /**
     * Approva un profilo di servizio e, se custom, anche l'offering sottostante.
     *
     * @param VendorAccount $vendorAccount
     * @param int $offeringId
     * @return void
     * @throws ValidationException
     */
    public function approveOfferingProfile(VendorAccount $vendorAccount, int $offeringId): void
    {
        $profile = VendorOfferingProfile::where('vendor_account_id', $vendorAccount->id)
            ->where('offering_id', $offeringId)
            ->first();

        if (!$profile) {
            return;
        }

        DB::transaction(function () use ($profile, $offeringId) {
            $offering = Offering::find($offeringId);

            if (
                $offering &&
                $offering->is_custom &&
                $offering->status === Offering::STATUS_PENDING_REVIEW &&
                str_starts_with($offering->name, 'Proposta vendor #')
            ) {
                throw ValidationException::withMessages([
                    'editOfferingName' => 'Prima di approvare devi impostare il nome interno definitivo del servizio.',
                ]);
            }

            if ($offering && $offering->is_custom && $offering->status === Offering::STATUS_PENDING_REVIEW) {
                $offering->update([
                    'status' => Offering::STATUS_APPROVED,
                    'is_active' => true,
                ]);
            }

            if ($profile) {
                $profile->update([
                    'is_approved' => true,
                    'is_published' => true,
                ]);
            }
        });

        if ($vendorAccount->user && $vendorAccount->user->email) {
            try {
                Mail::to($vendorAccount->user->email)
                    ->send(new \App\Mail\VendorServiceApprovedMail($profile));
            } catch (\Exception $e) {
                Log::error('Impossibile inviare email Servizio: ' . $e->getMessage());
            }
        }
    }

    /**
     * Rifiuta un profilo di servizio e disabilita l'offering.
     *
     * @param VendorAccount $vendorAccount
     * @param int $offeringId
     * @return void
     */
    public function rejectOfferingProfile(VendorAccount $vendorAccount, int $offeringId): void
    {
        $profile = VendorOfferingProfile::where('vendor_account_id', $vendorAccount->id)
            ->where('offering_id', $offeringId)
            ->first();

        DB::transaction(function () use ($vendorAccount, $profile, $offeringId) {
            if ($profile) {
                $profile->update([
                    'is_approved' => false,
                    'is_published' => false,
                ]);
            }

            $offering = Offering::find($offeringId);
            if ($offering && $offering->is_custom && $offering->status === Offering::STATUS_PENDING_REVIEW) {
                $offering->update([
                    'status' => Offering::STATUS_REJECTED,
                    'is_active' => false,
                ]);
            }

            $vendorAccount->offerings()
                ->updateExistingPivot($offeringId, ['is_active' => false]);
        });
    }
}
