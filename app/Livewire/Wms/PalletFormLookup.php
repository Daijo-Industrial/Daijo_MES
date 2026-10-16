<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletForm;
use App\Services\WmsService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PalletFormLookup extends Component
{
    public $pallet_id = '';

    public $palletForm = null;

    protected $rules = [
        'pallet_id' => 'required',
    ];

    public function updatedPalletId($value)
    {
        $this->pallet_id = trim($value);
        if (empty($this->pallet_id)) {
            $this->palletForm = null;

            return;
        }

        $this->palletForm = WmsPalletForm::with(['details' => function ($q) {
            $q->withTrashed();
        }, 'position'])
            ->where('pallet_id', $this->pallet_id)
            ->first();

        if (! $this->palletForm) {
            session()->flash('error', 'Pallet ID "' . $this->pallet_id . '" tidak ditemukan.');
            $this->palletForm = null;
            $this->dispatch('scan-error');
        } else {
            session()->forget('error');
            $this->dispatch('scan-success');
        }

        // Auto-select text for next scan
        $this->dispatch('select-pallet-id');
    }

    public function deletePallet(?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        if (! $this->palletForm) {
            return;
        }

        try {
            DB::beginTransaction();

            $pallet = WmsPalletForm::where('pallet_id', $this->palletForm->pallet_id)->firstOrFail();
            $palletId = $pallet->pallet_id;
            $positionId = $pallet->position_id;

            // Delete all details
            $pallet->details()->delete();

            // Delete pallet header
            $pallet->delete();

            // Log transaction
            $wmsService->logTransaction($palletId, 'DELETE_PALLET', $positionId, 'Deleted from Pallet Lookup');

            if ($positionId) {
                $wmsService->updatePositionStatus($positionId);
            }

            DB::commit();

            session()->flash('success', "Pallet {$palletId} berhasil dihapus.");
            $this->palletForm = null;
            $this->pallet_id = '';
            $this->dispatch('focus-pallet-id');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus pallet: ' . $e->getMessage());
        }
    }

    public function clear()
    {
        $this->pallet_id = '';
        $this->palletForm = null;
        $this->dispatch('focus-pallet-id');
    }

    public function render()
    {
        return view('livewire.wms.pallet-form-lookup');
    }
}
