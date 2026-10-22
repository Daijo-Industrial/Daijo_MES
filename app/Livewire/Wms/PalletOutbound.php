<?php

namespace App\Livewire\Wms;

use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use App\Services\WmsService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PalletOutbound extends Component
{
    public $pallet_id_input;

    public $isProcessing = false;

    // State untuk tahap ke-2 (Input Qty)
    public $palletData = null;

    public $palletItems = [];

    public $outboundQtys = [];

    public function processOutbound()
    {
        if ($this->isProcessing) {
            return;
        }
        $this->isProcessing = true;

        $this->validate([
            'pallet_id_input' => 'required',
        ]);

        $pallet = WmsPalletForm::with('details')->where('pallet_id', $this->pallet_id_input)->first();

        if (! $pallet) {
            session()->flash('error', 'Pallet ID ' . $this->pallet_id_input . ' tidak ditemukan.');
            $this->pallet_id_input = '';
            $this->isProcessing = false;
            $this->dispatch('scan-error');

            return;
        }

        if ($pallet->status === 'OUT') {
            session()->flash('error', 'Pallet ID ' . $this->pallet_id_input . ' sudah tercatat keluar sebelumnya.');
            $this->pallet_id_input = '';
            $this->isProcessing = false;
            $this->dispatch('scan-error');

            return;
        }

        // Kelompokkan item berdasarkan part_no
        $items = [];
        foreach ($pallet->details as $detail) {
            $part = $detail->part_no;
            if (! isset($items[$part])) {
                $items[$part] = [
                    'part_no' => $part,
                    'model_name' => $detail->model_name,
                    'total_boxes' => 0,
                    'total_pcs' => 0,
                ];
                $this->outboundQtys[$part] = 0; // Default input 0
            }
            $items[$part]['total_boxes']++;
            $items[$part]['total_pcs'] += $detail->qty;
        }

        $this->palletData = $pallet;
        $this->palletItems = array_values($items);
        $this->isProcessing = false;

        $this->dispatch('scan-success');
    }

    public function cancelOutbound()
    {
        $this->palletData = null;
        $this->palletItems = [];
        $this->outboundQtys = [];
        $this->pallet_id_input = '';
    }

    public function submitPartialOutbound(WmsService $wmsService)
    {
        if ($this->isProcessing) {
            return;
        }
        $this->isProcessing = true;

        try {
            DB::beginTransaction();

            $pallet = WmsPalletForm::lockForUpdate()->find($this->palletData->pallet_id);
            $oldPositionId = $pallet->position_id;

            $totalPcsOut = 0;
            $totalBoxesRemoved = 0;
            $logDetails = [];

            foreach ($this->palletItems as $item) {
                $partNo = $item['part_no'];
                $requestQty = (float) ($this->outboundQtys[$partNo] ?? 0);

                if ($requestQty <= 0) {
                    continue;
                }
                if ($requestQty > $item['total_pcs']) {
                    throw new \Exception("Kuantitas yang diminta untuk {$partNo} ({$requestQty}) melebihi stok yang ada ({$item['total_pcs']}).");
                }

                $totalPcsOut += $requestQty;
                $logDetails[] = "{$requestQty} pcs ({$partNo})";

                // Ambil box untuk part_no ini, diurutkan dari ID terbesar (mengurangi box yang paling atas)
                $boxes = WmsPalletFormDetail::where('pallet_form_id', $pallet->pallet_id)
                    ->where('part_no', $partNo)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->get();

                $remainingToDeduct = $requestQty;

                foreach ($boxes as $box) {
                    if ($remainingToDeduct <= 0) {
                        break;
                    }

                    if ($box->qty <= $remainingToDeduct) {
                        // Box habis sepenuhnya, hapus box
                        $remainingToDeduct -= $box->qty;
                        $box->delete();
                        $totalBoxesRemoved++;
                    } else {
                        // Box tersisa sebagian, update qty-nya
                        $box->qty -= $remainingToDeduct;
                        $box->save();
                        $remainingToDeduct = 0;
                    }
                }
            }

            if ($totalPcsOut <= 0) {
                throw new \Exception('Tidak ada barang yang dikeluarkan.');
            }

            // Update Pallet Header
            $pallet->box_qty -= $totalBoxesRemoved;
            $pallet->total_pallet_qty -= $totalPcsOut;

            if ($pallet->total_pallet_qty <= 0) {
                // Semua barang habis, set palet menjadi OUT
                $pallet->status = 'OUT';
                $pallet->position_id = null;
                $pallet->box_qty = 0;

                $notes = 'Full Outbound: ' . implode(', ', $logDetails);
                $wmsService->logTransaction($pallet->pallet_id, 'OUT', $oldPositionId, $notes);
            } else {
                // Sebagian barang keluar
                $notes = 'Partial Outbound: ' . implode(', ', $logDetails);
                $wmsService->logTransaction($pallet->pallet_id, 'OUT', $oldPositionId, $notes);
            }

            $pallet->save();

            // Update Position Status (Kapasitas Rak)
            if ($oldPositionId) {
                $wmsService->updatePositionStatus($oldPositionId);
            }

            DB::commit();

            session()->flash('success', 'Berhasil mengeluarkan total ' . $totalPcsOut . ' pcs dari Palet ' . $pallet->pallet_id);
            $this->cancelOutbound();
            $this->dispatch('scan-success');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
            $this->dispatch('scan-error');
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        return view('livewire.wms.pallet-outbound');
    }
}
