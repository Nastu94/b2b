<?php

namespace App\Livewire\Admin\Approvals;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Models\Offering;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminApprovalsPage extends Component
{
    use WithPagination;
    use AuthorizesRequests;

    public string $filterType = 'all';
    public string $filterCategory = '';
    public string $filterStatus = 'pending';
    public string $search = '';
    public string $filterVendorId = '';

    protected $queryString = [
        'filterType' => ['except' => 'all'],
        'filterCategory' => ['except' => ''],
        'filterStatus' => ['except' => 'pending'],
        'search' => ['except' => ''],
        'filterVendorId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->filterType = 'all';
        $this->filterStatus = 'pending';
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['filterType', 'filterCategory', 'filterStatus', 'search', 'filterVendorId'])) {
            $this->resetPage();
        }
    }

    public function getCategoriesProperty()
    {
        return Category::orderBy('name')->get();
    }

    public function getVendorsListProperty()
    {
        return VendorAccount::orderBy('company_name')
            ->orderBy('first_name')
            ->get();
    }

    private function getVendorName(?VendorAccount $vendor): string
    {
        if (!$vendor) return 'N/A';
        $name = trim($vendor->company_name ?: ($vendor->first_name . ' ' . $vendor->last_name));
        return $name ?: 'Vendor #' . $vendor->id;
    }

    public function getPendingItemsProperty()
    {
        $items = collect();

        $type = $this->filterType;
        $status = $this->filterStatus;
        $search = strtolower($this->search);
        $categoryId = $this->filterCategory;
        $vendorId = $this->filterVendorId;

        // 1. Vendors
        if (in_array($type, ['all', 'vendor'])) {
            $query = VendorAccount::with(['user', 'category']);
            
            if ($status === 'pending') {
                $query->where('status', 'PENDING');
            } elseif ($status === 'approved') {
                $query->where('status', 'ACTIVE');
            } elseif ($status === 'rejected') {
                $query->where('status', 'REJECTED');
            }

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($vendorId) {
                $query->where('id', $vendorId);
            }

            $vendors = $query->get()->filter(function($vendor) use ($search) {
                if (!$search) return true;
                $vName = $this->getVendorName($vendor);
                return str_contains(strtolower($vName), $search)
                    || str_contains(strtolower($vendor->user?->email ?? ''), $search);
            });

            foreach ($vendors as $v) {
                $vName = $this->getVendorName($v);
                $items->push([
                    'type' => 'vendor',
                    'id' => $v->id,
                    'title' => $vName,
                    'subtitle' => $v->user?->email,
                    'category' => $v->category?->name,
                    'status' => $v->status,
                    'vendor_account_id' => $v->id,
                    'vendor_name' => $vName,
                    'related_offering_id' => null,
                ]);
            }
        }

        // 2. Services / Contents
        if (in_array($type, ['all', 'services', 'contents'])) {
            $query = VendorOfferingProfile::with(['vendorAccount.category', 'offering']);
            
            if ($status === 'pending') {
                $query->where('is_published', true)->where('is_approved', false);
            } elseif ($status === 'approved') {
                $query->where('is_published', true)->where('is_approved', true);
            } elseif ($status === 'rejected') {
                $query->where('is_published', false)->where('is_approved', false);
            }

            if ($vendorId) {
                $query->where('vendor_account_id', $vendorId);
            }

            $profiles = $query->get();

            if ($categoryId) {
                $profiles = $profiles->filter(fn($p) => $p->vendorAccount?->category_id == $categoryId);
            }

            $profiles = $profiles->filter(function($p) use ($search) {
                if (!$search) return true;
                $vendorName = $this->getVendorName($p->vendorAccount);
                return str_contains(strtolower($p->title), $search)
                    || str_contains(strtolower($vendorName), $search)
                    || str_contains(strtolower($p->offering?->name ?? ''), $search);
            });

            foreach ($profiles as $p) {
                $itemStatus = 'DRAFT_OR_REJECTED';
                if ($p->is_approved) {
                    $itemStatus = 'APPROVED';
                } elseif ($p->is_published) {
                    $itemStatus = 'PENDING_REVIEW';
                }

                $items->push([
                    'type' => 'service',
                    'id' => $p->id,
                    'title' => $p->title ?: 'Servizio senza titolo',
                    'subtitle' => $p->offering?->name,
                    'category' => $p->vendorAccount?->category?->name,
                    'status' => $itemStatus,
                    'vendor_account_id' => $p->vendor_account_id,
                    'vendor_name' => $this->getVendorName($p->vendorAccount),
                    'related_offering_id' => $p->offering_id,
                ]);
            }
        }

        // 3. Custom Offerings
        if (in_array($type, ['all', 'custom_offering'])) {
            $query = Offering::with(['category', 'createdByVendor'])
                ->where('is_custom', true)
                ->whereDoesntHave('vendorProfiles');

            
            if ($status === 'pending') {
                $query->where('status', Offering::STATUS_PENDING_REVIEW);
            } elseif ($status === 'approved') {
                $query->where('status', Offering::STATUS_APPROVED);
            } elseif ($status === 'rejected') {
                $query->where('status', Offering::STATUS_REJECTED);
            }

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            if ($vendorId) {
                $query->where('created_by_vendor_account_id', $vendorId);
            }

            $offerings = $query->get()->filter(function($o) use ($search) {
                if (!$search) return true;
                $vendorName = $this->getVendorName($o->createdByVendor);
                return str_contains(strtolower($o->name), $search)
                    || str_contains(strtolower($vendorName), $search);
            });

            foreach ($offerings as $o) {
                $items->push([
                    'type' => 'custom_offering',
                    'id' => $o->id,
                    'title' => $o->name,
                    'subtitle' => 'Proposta Servizio',
                    'category' => $o->category?->name,
                    'status' => $o->status,
                    'vendor_account_id' => $o->created_by_vendor_account_id,
                    'vendor_name' => $this->getVendorName($o->createdByVendor),
                    'related_offering_id' => $o->id,
                ]);
            }
        }

        return $items->sortBy('title')->values();
    }

    public function approveService(int $vendorId, int $offeringId)
    {
        $vendor = VendorAccount::findOrFail($vendorId);
        $this->authorize('update', $vendor);
        
        try {
            app(\App\Services\OfferingApprovalService::class)->approveOfferingProfile($vendor, $offeringId);
            session()->flash('status', 'Servizio approvato con successo.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('general', collect($e->errors())->flatten()->first());
        }
    }

    public function rejectService(int $vendorId, int $offeringId)
    {
        $vendor = VendorAccount::findOrFail($vendorId);
        $this->authorize('update', $vendor);
        app(\App\Services\OfferingApprovalService::class)->rejectOfferingProfile($vendor, $offeringId);
        session()->flash('status', 'Servizio rifiutato.');
    }

    public function render()
    {
        $items = $this->pendingItems;
        
        $page = $this->getPage();
        $perPage = 20;
        
        $paginatedItems = new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.admin.approvals.admin-approvals-page', [
            'items' => $paginatedItems,
        ])->layout('layouts.admin', ['title' => 'Approvazioni']);
    }
}
