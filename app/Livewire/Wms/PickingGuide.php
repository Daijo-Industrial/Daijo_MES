<?php

namespace App\Livewire\Wms;

use App\Models\MasterListItem;
use App\Models\SoData;
use App\Models\WmsPickingHeader;
use App\Services\WmsPickingService;
use Livewire\Component;

class PickingGuide extends Component
{
    public string $docNumSearch = '';

    public array $requestItems = [];

    // Active picking sheet ID from URL/Query
    public ?int $activePickingId = null;

    protected $queryString = [
        'activePickingId' => ['except' => null],
    ];

    public function mount(): void
    {
        // Start with one default empty row
        $this->requestItems = [
            ['item_code' => '', 'item_name' => '', 'quantity' => ''],
        ];
    }

    /**
     * Add a blank row to the manual picking list.
     */
    public function addItemRow(): void
    {
        $this->requestItems[] = [
            'item_code' => '',
            'item_name' => '',
            'quantity' => '',
        ];
    }

    /**
     * Remove an item row from the picking list.
     */
    public function removeItemRow(int $index): void
    {
        array_splice($this->requestItems, $index, 1);

        if (empty($this->requestItems)) {
            $this->mount();
        }
    }

    /**
     * Load items and quantities automatically from a DO/SO document in so_datas.
     */
    public function loadFromDocNum(WmsPickingService $pickingService): void
    {
        $docNum = trim($this->docNumSearch);
        if (empty($docNum)) {
            session()->flash('error', 'Masukkan atau pilih Nomor Dokumen terlebih dahulu.');

            return;
        }

        $items = $pickingService->loadItemsFromDocNum($docNum);

        if (empty($items)) {
            session()->flash('error', "Dokumen \"{$docNum}\" tidak ditemukan atau tidak memiliki item.");

            return;
        }

        $this->requestItems = $items;
        $this->activePickingId = null;
        session()->flash('success', 'Berhasil memuat ' . count($items) . " item dari Dokumen {$docNum}.");
    }

    /**
     * Create a persistent WMS Picking sheet and generate FIFO routing instructions.
     */
    public function createPickingSheet(WmsPickingService $pickingService): void
    {
        // Validate request rows
        $validatedItems = [];
        foreach ($this->requestItems as $item) {
            $code = trim($item['item_code']);
            $qty = (float) $item['quantity'];

            if (! empty($code) && $qty > 0) {
                $modelName = $item['item_name'];
                if (empty($modelName)) {
                    $mItem = MasterListItem::where('item_code', $code)->first();
                    $modelName = $mItem ? $mItem->item_name : 'N/A';
                }

                $validatedItems[] = [
                    'item_code' => $code,
                    'item_name' => $modelName,
                    'quantity' => $qty,
                ];
            }
        }

        if (empty($validatedItems)) {
            session()->flash('error', 'Masukkan minimal 1 item dengan kuantitas lebih dari 0.');

            return;
        }

        // Save sheet in DB using picking service transaction
        $header = $pickingService->createPickingList(
            $this->docNumSearch ?: null,
            $validatedItems,
            auth()->id()
        );

        $this->activePickingId = $header->id;
        session()->flash('success', "Dokumen Picking {$header->picking_no} berhasil dibuat!");
    }

    /**
     * Toggle picked state for a detail row and save to database.
     */
    public function toggleDetailPicked(WmsPickingService $pickingService, int $detailId, bool $isPicked): void
    {
        $pickingService->togglePickState($detailId, $isPicked);

        // Flash a quick message if the document status changed to COMPLETED
        if ($this->activePickingHeader && $this->activePickingHeader->status === 'COMPLETED') {
            session()->flash('success', "Dokumen Picking {$this->activePickingHeader->picking_no} telah SELESAI diambil!");
        }
    }

    /**
     * Select an existing picking list sheet to view/work on.
     */
    public function selectPickingSheet(int $id): void
    {
        $this->activePickingId = $id;
        session()->flash('success', 'Memuat dokumen picking.');
    }

    /**
     * Cancel an active picking sheet document.
     */
    public function cancelPickingSheet(int $id): void
    {
        $header = WmsPickingHeader::findOrFail($id);
        $header->update(['status' => 'CANCELLED']);

        if ($this->activePickingId === $id) {
            $this->activePickingId = null;
        }

        session()->flash('success', "Dokumen Picking {$header->picking_no} telah dibatalkan.");
    }

    /**
     * Close the currently open picking guide sheet.
     */
    public function closeActiveSheet(): void
    {
        $this->activePickingId = null;
    }

    /**
     * Reset the creation request form state.
     */
    public function clear(): void
    {
        $this->docNumSearch = '';
        $this->activePickingId = null;
        $this->mount();
    }

    /**
     * Get the currently active picking header model.
     */
    public function getActivePickingHeaderProperty(): ?WmsPickingHeader
    {
        if (! $this->activePickingId) {
            return null;
        }

        return WmsPickingHeader::with('details')->find($this->activePickingId);
    }

    /**
     * Get unique SO/DO numbers to populate the selector dropdown.
     */
    public function getAvailableDocNumsProperty(): array
    {
        return SoData::select('doc_num')
            ->distinct()
            ->orderBy('doc_num', 'desc')
            ->limit(100)
            ->pluck('doc_num')
            ->toArray();
    }

    /**
     * Get all master item codes to populate autocomplete inputs.
     */
    public function getAvailableItemsProperty(): array
    {
        return MasterListItem::orderBy('item_code')
            ->select('item_code', 'item_name')
            ->get()
            ->toArray();
    }

    /**
     * Get all pending picking sheets to list.
     */
    public function getActivePickingSheetsProperty()
    {
        return WmsPickingHeader::whereIn('status', ['PENDING', 'PICKING'])
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get history of completed/cancelled sheets.
     */
    public function getCompletedPickingSheetsProperty()
    {
        return WmsPickingHeader::whereIn('status', ['COMPLETED', 'CANCELLED'])
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();
    }

    public function render()
    {
        return view('livewire.wms.picking-guide')
            ->layout('layouts.app');
    }
}
