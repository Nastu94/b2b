<?php

namespace App\Livewire\Admin\Bookings;

use Livewire\Component;

class AdminBookingsTabs extends Component
{
    public string $tab = 'pending';

    protected $queryString = [
        'tab' => ['except' => 'pending'],
    ];

    public function setTab(string $tab): void
    {
        $allowed = ['pending', 'confirmed', 'declined', 'all'];
        $this->tab = in_array($tab, $allowed, true) ? $tab : 'pending';
    }

    public function render()
    {
        return view('livewire.admin.bookings.admin-bookings-tabs')
            ->layout('layouts.admin'); 
    }
}