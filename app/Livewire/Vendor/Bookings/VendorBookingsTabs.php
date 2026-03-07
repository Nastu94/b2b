<?php

namespace App\Livewire\Vendor\Bookings;

use Livewire\Component;

class VendorBookingsTabs extends Component
{
    public string $tab = 'pending';

    protected $queryString = [
        'tab' => ['except' => 'pending'],
    ];

    public function setTab(string $tab): void
    {
        $allowed = ['pending', 'confirmed'];
        $this->tab = in_array($tab, $allowed, true) ? $tab : 'pending';
    }

    public function render()
    {
        return view('livewire.vendor.bookings.vendor-bookings-tabs')->layout('layouts.vendor');
    }
}