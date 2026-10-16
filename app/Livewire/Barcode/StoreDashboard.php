<?php

namespace App\Livewire\Barcode;

use App\Models\BarcodePackagingDetail;
use Livewire\Component;

class StoreDashboard extends Component
{
    public $summaryData = [];

    public function mount()
    {
        $this->loadSummary();
    }

    public function loadSummary()
    {
        $allData = BarcodePackagingDetail::with('masterBarcode')
            ->select('partNo', 'label', 'position', 'scantime', 'noDokumen', 'created_at', 'masterId')
            ->orderBy('partNo')
            ->orderBy('label')
            ->orderBy('scantime', 'desc')
            ->get()
            ->groupBy('partNo');

        $summaryData = [];

        foreach ($allData as $partNo => $partRecords) {
            $labels = $partRecords->groupBy('label');

            $daijoQty = $kiicQty = $customerQty = 0;
            $detailPerLabel = [];

            foreach ($labels as $label => $labelRows) {
                $latest = $labelRows->first();
                $position = strtolower(trim($latest->position));

                if ($position === 'jakarta') {
                    $daijoQty += 1;
                } elseif ($position === 'karawang') {
                    $kiicQty += 1;
                } elseif (in_array($position, ['customerjakarta', 'customerkarawang'])) {
                    $customerQty += 1;
                }

                $detailPerLabel[] = [
                    'label' => $label,
                    'position' => $latest->position,
                    'last_transaction' => $latest->scantime,
                    'quantity' => 1,
                    'customer' => $latest->masterBarcode ? $latest->masterBarcode->customer : null,
                    'history' => $labelRows->map(function ($r) {
                        return [
                            'scantime' => $r->scantime,
                            'position' => $r->position,
                            'label' => $r->label,
                            'no_dokumen' => $r->noDokumen,
                        ];
                    })->toArray(),
                ];
            }

            usort($detailPerLabel, function ($a, $b) {
                return strtotime($b['last_transaction']) - strtotime($a['last_transaction']);
            });

            $summaryData[] = [
                'part_no' => $partNo,
                'quantity_daijo' => $daijoQty,
                'quantity_kiic' => $kiicQty,
                'quantity_customer' => $customerQty,
                'total' => $daijoQty + $kiicQty + $customerQty,
                'details' => $detailPerLabel,
            ];
        }

        $this->summaryData = $summaryData;
        // dd($summaryData[1]);
    }

    public function render()
    {
        return view('livewire.barcode.store-dashboard')->layout('layouts.dashboard');
    }
}
