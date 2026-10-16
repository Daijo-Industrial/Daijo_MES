<?php

namespace App\Livewire\Wms;

use App\Jobs\SyncPalletToSapJob;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Services\WmsSapSyncService;
use Livewire\Component;
use Livewire\WithPagination;

class SapSyncMonitor extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = '';

    // Modal & Test properties
    public $showDetails = false;

    public $selectedPalletId = null;

    public $palletDetails = [];

    public $connectionTestResult = null;

    protected $queryString = ['search', 'statusFilter'];

    public function testEndpoint(WmsSapSyncService $sapService)
    {
        $res = $sapService->testConnection();
        $this->connectionTestResult = $res;
        if ($res['status']) {
            session()->flash('message', $res['message']);
        } else {
            session()->flash('error', $res['message']);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Show modal with pallet item details
     */
    public function viewDetails($palletId)
    {
        $this->selectedPalletId = $palletId;
        $this->palletDetails = WmsPalletFormDetail::where('pallet_form_id', $palletId)->get();
        $this->showDetails = true;
    }

    public function closeDetails()
    {
        $this->showDetails = false;
        $this->selectedPalletId = null;
        $this->palletDetails = [];
    }

    /**
     * Retry syncing a pallet
     */
    public function retrySync($palletId, ?WmsSapSyncService $sapService = null)
    {
        $sapService = $sapService ?? app(WmsSapSyncService::class);

        // Reset status to pending (0)
        WmsPalletForm::where('pallet_id', $palletId)->update([
            'sap_sync_status' => 0,
            'updated_at' => now(),
        ]);

        // Reset details not already synced or ignored
        WmsPalletFormDetail::where('pallet_form_id', $palletId)
            ->whereNotIn('sap_sync_status', [1, 4])
            ->update([
                'sap_sync_status' => 0,
                'sap_error_msg' => null,
            ]);

        // Execute sync immediately so API call & API log are generated live
        $res = $sapService->syncPalletInventoryTransfer($palletId);

        $msg = $res['status'] ? "Pallet {$palletId} berhasil disinkronkan ke SAP." : "Pallet {$palletId} dicoba ulang (Status: " . ($res['message'] ?? 'Proses selesai') . ').';
        session()->flash($res['status'] ? 'message' : 'error', $msg);
    }

    /**
     * Retry syncing a specific SPK in a pallet
     */
    public function retrySpk($palletId, $spkNo, ?WmsSapSyncService $sapService = null)
    {
        $sapService = $sapService ?? app(WmsSapSyncService::class);

        WmsPalletForm::where('pallet_id', $palletId)->update([
            'sap_sync_status' => 0,
            'updated_at' => now(),
        ]);

        WmsPalletFormDetail::where('pallet_form_id', $palletId)
            ->where('spk_no', $spkNo)
            ->whereNotIn('sap_sync_status', [1, 4])
            ->update([
                'sap_sync_status' => 0,
                'sap_error_msg' => null,
            ]);

        // Execute sync immediately so API call & API log are generated live
        $res = $sapService->syncPalletInventoryTransfer($palletId);

        $this->palletDetails = WmsPalletFormDetail::where('pallet_form_id', $palletId)->get();

        $msg = $res['status'] ? "SPK {$spkNo} di Pallet {$palletId} berhasil disinkronkan ke SAP." : "SPK {$spkNo} dicoba ulang (Status: " . ($res['message'] ?? 'Proses selesai') . ').';
        session()->flash($res['status'] ? 'message' : 'error', $msg);
    }

    /**
     * Mark a pallet as already synced / success manually (e.g. verified directly in SAP)
     */
    public function markAsSynced($palletId)
    {
        WmsPalletForm::where('pallet_id', $palletId)->update([
            'sap_sync_status' => 1, // 1 = SUCCESS
            'sap_error_msg' => null,
            'sap_sync_at' => now(),
            'updated_at' => now(),
        ]);

        WmsPalletFormDetail::where('pallet_form_id', $palletId)->update([
            'sap_sync_status' => 1,
            'sap_error_msg' => 'Diverifikasi manual: Data sudah ada di SAP',
            'sap_sync_at' => now(),
        ]);

        if ($this->selectedPalletId === $palletId) {
            $this->palletDetails = WmsPalletFormDetail::where('pallet_form_id', $palletId)->get();
        }

        session()->flash('message', "Pallet {$palletId} berhasil ditandai SUKSES (Sudah Masuk di SAP).");
    }

    /**
     * Mark a specific detail as synced manually
     */
    public function markDetailAsSynced($detailId)
    {
        $detail = WmsPalletFormDetail::find($detailId);
        if (! $detail) {
            return;
        }

        $detail->update([
            'sap_sync_status' => 1,
            'sap_error_msg' => 'Diverifikasi manual: Data sudah ada di SAP',
            'sap_sync_at' => now(),
        ]);

        $palletId = $detail->pallet_form_id;

        // Ground truth check: jika semua detail palet sudah sukses / ignored, update header jadi 1
        $hasPendingOrError = WmsPalletFormDetail::where('pallet_form_id', $palletId)
            ->whereNotIn('sap_sync_status', [1, 4])
            ->exists();

        if (! $hasPendingOrError) {
            WmsPalletForm::where('pallet_id', $palletId)->update([
                'sap_sync_status' => 1,
                'sap_error_msg' => null,
                'sap_sync_at' => now(),
            ]);
        }

        if ($this->selectedPalletId) {
            $this->palletDetails = WmsPalletFormDetail::where('pallet_form_id', $this->selectedPalletId)->get();
        }

        session()->flash('message', "Item #{$detail->label} berhasil ditandai SUKSES.");
    }

    /**
     * Mark a pallet as ignored
     */
    public function ignorePallet($palletId)
    {
        WmsPalletForm::where('pallet_id', $palletId)->update([
            'sap_sync_status' => 4, // 4 = IGNORED
            'sap_error_msg' => 'Manually ignored by user',
        ]);

        WmsPalletFormDetail::where('pallet_form_id', $palletId)
            ->where('sap_sync_status', '!=', 1)
            ->update([
                'sap_sync_status' => 4,
                'sap_error_msg' => 'Manually ignored by user',
            ]);

        session()->flash('message', "Pallet {$palletId} marked as ignored.");
    }

    /**
     * Mark a specific detail as ignored
     */
    public function ignoreDetail($detailId)
    {
        WmsPalletFormDetail::where('id', $detailId)->update([
            'sap_sync_status' => 4,
            'sap_error_msg' => 'Manually ignored by user',
        ]);

        if ($this->selectedPalletId) {
            $this->palletDetails = WmsPalletFormDetail::where('pallet_form_id', $this->selectedPalletId)->get();
        }

        session()->flash('message', 'Item marked as ignored.');
    }

    /**
     * Retry all failed pallets
     */
    public function retryAllFailed()
    {
        $failedPalletIds = WmsPalletForm::where('sap_sync_status', 2)->pluck('pallet_id');

        if ($failedPalletIds->isEmpty()) {
            return;
        }

        WmsPalletForm::whereIn('pallet_id', $failedPalletIds)->update(['sap_sync_status' => 0]);
        WmsPalletFormDetail::whereIn('pallet_form_id', $failedPalletIds)
            ->whereNotIn('sap_sync_status', [1, 4])
            ->update([
                'sap_sync_status' => 0,
                'sap_error_msg' => null,
            ]);

        foreach ($failedPalletIds as $id) {
            SyncPalletToSapJob::dispatch($id);
        }

        session()->flash('message', count($failedPalletIds) . ' failed pallets re-queued.');
    }

    public function render()
    {
        // Ground truth self-healing: Ensure any header status matches the status of its detail items
        $unresolvedPallets = WmsPalletForm::whereIn('sap_sync_status', [0, 2, 3])->has('details')->get();
        foreach ($unresolvedPallets as $p) {
            $allSynced = ! $p->details()->whereNotIn('sap_sync_status', [1, 4])->exists();
            if ($allSynced) {
                $p->update([
                    'sap_sync_status' => 1,
                    'sap_error_msg' => null,
                    'sap_sync_at' => $p->details()->max('sap_sync_at') ?? now(),
                ]);
            }
        }

        $query = WmsPalletForm::query()->withCount('details');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pallet_id', 'like', '%' . $this->search . '%')
                    ->orWhere('part_no', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter !== '') {
            $query->where('sap_sync_status', $this->statusFilter);
        }

        $pallets = $query->orderBy('created_at', 'desc')->paginate(10);

        $baseQuery = WmsPalletForm::query();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'success' => (clone $baseQuery)->where('sap_sync_status', 1)->count(),
            'failed' => (clone $baseQuery)->where('sap_sync_status', 2)->count(),
            'pending' => (clone $baseQuery)->where('sap_sync_status', 0)->count(),
        ];

        return view('livewire.wms.sap-sync-monitor', [
            'pallets' => $pallets,
            'stats' => $stats,
        ]);
    }
}
