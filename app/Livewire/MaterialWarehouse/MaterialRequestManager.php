<?php

namespace App\Livewire\MaterialWarehouse;

use App\Models\ProductionMaterialRequest;
use App\Services\ProductionMaterialService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class MaterialRequestManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'PENDING'; // 'ALL', 'PENDING', 'PROCESSED', 'REJECTED'

    public string $search = '';

    public string $startDate = '';

    public string $endDate = '';

    // Modal Reject
    public bool $showRejectModal = false;

    public ?int $selectedRequestId = null;

    public string $rejectReason = '';

    public ?ProductionMaterialRequest $selectedRequest = null;

    protected $queryString = ['statusFilter', 'search'];

    protected function getService(): ProductionMaterialService
    {
        return app(ProductionMaterialService::class);
    }

    public function mount(): void
    {
        $this->startDate = now('Asia/Jakarta')->subDays(14)->toDateString();
        $this->endDate = now('Asia/Jakarta')->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Buka modal penolakan
     */
    public function openRejectModal(int $id): void
    {
        $this->selectedRequestId = $id;
        $this->selectedRequest = ProductionMaterialRequest::find($id);
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal(): void
    {
        $this->selectedRequestId = null;
        $this->selectedRequest = null;
        $this->rejectReason = '';
        $this->showRejectModal = false;
    }

    /**
     * Konfirmasi tolak permintaan material
     */
    public function confirmReject(): void
    {
        $this->validate([
            'rejectReason' => 'required|string|min:3|max:500',
        ], [
            'rejectReason.required' => 'Wajib mengisi alasan penolakan agar tim produksi mengetahui kendalanya.',
            'rejectReason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        if (! $this->selectedRequestId) {
            return;
        }

        try {
            $service = $this->getService();
            $req = $service->rejectMaterialRequest(
                $this->selectedRequestId,
                $this->rejectReason,
                auth()->id() ?? 1,
                auth()->user()?->name ?? 'Store'
            );

            $this->closeRejectModal();
            $this->dispatch('push-notification', [
                'status' => 'warning',
                'message' => "Permintaan material {$req->request_code} telah ditolak.",
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('push-notification', [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tanggapi / Outgoing: Redirect ke form pengambilan material
     */
    public function fulfillRequest(int $id)
    {
        $req = ProductionMaterialRequest::findOrFail($id);

        return redirect()->route('mwh.outgoing.create', [
            'request_id' => $req->id,
            'selected_item_code' => $req->item_code,
        ]);
    }

    public function render()
    {
        $service = $this->getService();

        $requests = $service->getMaterialRequests(
            $this->statusFilter,
            $this->search,
            $this->startDate,
            $this->endDate
        )->paginate(15);

        $pendingCount = ProductionMaterialRequest::where('status', 'PENDING')->count();
        $processedCount = ProductionMaterialRequest::where('status', 'PROCESSED')->count();
        $rejectedCount = ProductionMaterialRequest::where('status', 'REJECTED')->count();
        $totalPendingQty = (float) ProductionMaterialRequest::where('status', 'PENDING')->sum('qty_requested');

        return view('livewire.material-warehouse.material-request-manager', [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
            'processedCount' => $processedCount,
            'rejectedCount' => $rejectedCount,
            'totalPendingQty' => $totalPendingQty,
        ]);
    }
}

