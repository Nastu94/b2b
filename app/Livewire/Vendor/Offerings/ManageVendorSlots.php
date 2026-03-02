<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\VendorSlot;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class ManageVendorSlots extends Component
{
    use AuthorizesRequests;

    public $slots;

    public bool $showForm   = false;
    public ?int $editingId  = null;
    public string $label     = '';
    public string $start_time = '';
    public string $end_time   = '';

    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', VendorSlot::class);
        $this->loadSlots();
    }

    private function loadSlots(): void
    {
        $this->slots = Auth::user()
            ->vendorAccount
            ->slots()
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $slot = $this->getOwnedSlot($id);
        if (!$slot) return;

        $this->authorize('update', $slot);

        $this->editingId   = $slot->id;
        $this->label       = $slot->label;
        $this->start_time  = $slot->start_time ? substr($slot->start_time, 0, 5) : '';
        $this->end_time    = $slot->end_time   ? substr($slot->end_time, 0, 5)   : '';
        $this->showForm    = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $slot = $this->getOwnedSlot($this->editingId);
            $this->authorize('update', $slot);
        } else {
            $this->authorize('create', VendorSlot::class);
        }

        $this->validate([
            'label'      => 'required|string|max:120',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $vendorAccount = Auth::user()->vendorAccount;

        // Genera slug da nome + orario es. "sera-2000-2300"
        $startClean = str_replace(':', '', $this->start_time);
        $endClean   = str_replace(':', '', $this->end_time);
        $slug = Str::slug($this->label) . '-' . $startClean . '-' . $endClean;

        $data = [
            'label'      => trim($this->label),
            'slug'       => $slug,
            'start_time' => $this->start_time . ':00',
            'end_time'   => $this->end_time   . ':00',
            'is_active'  => true,
        ];

        if ($this->editingId) {
            $this->getOwnedSlot($this->editingId)?->update($data);
            session()->flash('status', 'Slot aggiornato.');
        } else {
            $vendorAccount->slots()->create($data);
            session()->flash('status', 'Slot creato.');
        }

        $this->resetForm();
        $this->loadSlots();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function delete(): void
    {
        $slot = $this->getOwnedSlot($this->confirmDeleteId);
        if (!$slot) return;

        $this->authorize('delete', $slot);
        $slot->delete();

        $this->confirmDeleteId = null;
        session()->flash('status', 'Slot eliminato.');
        $this->loadSlots();
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showForm   = false;
        $this->editingId  = null;
        $this->label      = '';
        $this->start_time = '';
        $this->end_time   = '';
    }

    private function getOwnedSlot(?int $id): ?VendorSlot
    {
        if (!$id) return null;
        return Auth::user()->vendorAccount->slots()->find($id);
    }

    public function render()
    {
        return view('livewire.vendor.offerings.manage-vendor-slots');
    }
}