<?php

namespace App\Services;

use App\Models\Offering;
use App\Models\VendorAccount;
use App\Models\VendorDocument;
use App\Models\VendorOfferingProfile;

class AdminApprovalCountService
{
    public function pendingCount(): int
    {
        return $this->pendingVendorsCount()
            + $this->pendingServicesCount()
            + $this->pendingCustomOfferingsCount()
            + $this->pendingDocumentsCount();
    }

    public function pendingVendorsCount(): int
    {
        return VendorAccount::where('status', 'PENDING')->count();
    }

    public function pendingServicesCount(): int
    {
        return VendorOfferingProfile::where('is_published', true)
            ->where('is_approved', false)
            ->count();
    }

    public function pendingCustomOfferingsCount(): int
    {
        return Offering::where('is_custom', true)
            ->whereDoesntHave('vendorProfiles')
            ->where('status', Offering::STATUS_PENDING_REVIEW)
            ->count();
    }

    public function pendingDocumentsCount(): int
    {
        return VendorDocument::where('status', VendorDocument::STATUS_PENDING)->count();
    }
}
