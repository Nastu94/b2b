<?php

namespace App\Livewire\Vendor\Offerings;

use App\Models\VendorBlackout;
use App\Models\VendorSlot;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ManageBlackouts extends Component
{
    use AuthorizesRequests;

    public $blackouts;
    public $slots;

    // Form
    public bool $showForm       = false;
    public ?int $editingId      = null;
    public string $date_from    = '';
    public string $date_to      = '';
    public ?int $vendor_slot_id = null;
    public string $reason_internal = '';
    public string $reason_public   = '';

    // Conferma eliminazione
    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', VendorBlackout::class);
        $this->loadData();
    }

    private function loadData(): void
    {
        $vendorAccount = Auth::user()->vendorAccount;

        $this->blackouts = $vendorAccount
            ->blackouts()
            ->with('slot')
            ->orderBy('date_from')
            ->get();

        $this->slots = $vendorAccount
            ->slots()
            ->active()
            ->orderBy('start_time')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        // Default: oggi
        $this->date_from = now()->format('Y-m-d');
        $this->date_to   = now()->format('Y-m-d');
        $this->showForm  = true;
    }

    public function openEdit(int $id): void
    {
        $blackout = $this->getOwnedBlackout($id);
        if (!$blackout) return;

        $this->authorize('update', $blackout);

        $this->editingId         = $blackout->id;
        $this->date_from         = $blackout->date_from->format('Y-m-d');
        $this->date_to           = $blackout->date_to->format('Y-m-d');
        $this->vendor_slot_id    = $blackout->vendor_slot_id;
        $this->reason_internal   = $blackout->reason_internal ?? '';
        $this->reason_public     = $blackout->reason_public ?? '';
        $this->showForm          = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $blackout = $this->getOwnedBlackout($this->editingId);
            $this->authorize('update', $blackout);
        } else {
            $this->authorize('create', VendorBlackout::class);
        }

        $this->validate([
            'date_from'       => 'required|date_format:Y-m-d',
            'date_to'         => 'required|date_format:Y-m-d|after_or_equal:date_from',
            'vendor_slot_id'  => 'nullable|integer|exists:vendor_slots,id',
            'reason_internal' => 'nullable|string|max:500',
            'reason_public'   => 'nullable|string|max:255',
        ]);

        // Verifica ownership slot se specificato
        if ($this->vendor_slot_id) {
            $slotOwned = Auth::user()
                ->vendorAccount
                ->slots()
                ->where('id', $this->vendor_slot_id)
                ->exists();

            if (!$slotOwned) {
                $this->addError('vendor_slot_id', 'Slot non valido.');
                return;
            }
        }

        $data = [
            'date_from'       => $this->date_from,
            'date_to'         => $this->date_to,
            'vendor_slot_id'  => $this->vendor_slot_id ?: null,
            'reason_internal' => $this->reason_internal ?: null,
            'reason_public'   => $this->reason_public ?: null,
            'created_by'      => Auth::id(),
        ];

        if ($this->editingId) {
            $this->getOwnedBlackout($this->editingId)?->update($data);
            session()->flash('status', 'Blackout aggiornato.');
        } else {
            Auth::user()->vendorAccount->blackouts()->create($data);
            session()->flash('status', 'Blackout creato.');
        }

        $this->resetForm();
        $this->loadData();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function delete(): void
    {
        $blackout = $this->getOwnedBlackout($this->confirmDeleteId);
        if (!$blackout) return;

        $this->authorize('delete', $blackout);
        $blackout->delete();

        $this->confirmDeleteId = null;
        session()->flash('status', 'Blackout eliminato.');
        $this->loadData();
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
        $this->showForm        = false;
        $this->editingId       = null;
        $this->date_from       = '';
        $this->date_to         = '';
        $this->vendor_slot_id  = null;
        $this->reason_internal = '';
        $this->reason_public   = '';
    }

    private function getOwnedBlackout(?int $id): ?VendorBlackout
    {
        if (!$id) return null;
        return Auth::user()
            ->vendorAccount
            ->blackouts()
            ->find($id);
    }

    public function render()
    {
        return view('livewire.vendor.offerings.manage-blackouts');
    }
}