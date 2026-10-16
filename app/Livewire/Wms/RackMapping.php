<?php

namespace App\Livewire\Wms;

use App\Models\WmsPosition;
use App\Models\WmsRack;
use App\Services\WmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class RackMapping extends Component
{
    use WithFileUploads;

    // Slot Detail & Edit State
    public $selectedPositionId;

    public $editMaxCapacity;

    public $editCustomerCode;

    // Filtering & Search State
    public $filterCustomer = '';

    public $searchItem = '';

    public $viewMode = 'layout'; // 'layout' (denah 2D) or 'grid' (card list)

    public $activeLevel = 1;    // Level filter for 2D layout (1, 2, 3)

    protected $queryString = ['filterCustomer', 'searchItem', 'whseId', 'viewMode', 'activeLevel'];

    // UI State
    public $showDetail = false;

    // Add Rack Modal State
    public $newRackCode;

    public $newLevels = 2;

    public $newSlotsPerLevel = 4;

    public $newRackCustomer;

    public $newMaxCapacity = 1;

    public $newOrientation = 'VERTICAL'; // 'VERTICAL' or 'HORIZONTAL'

    public $newZone = 'DEFAULT';         // 'LOGISTIC', 'STORE', 'WIP', 'FG', 'DEFAULT'

    public $newXPos = 0;

    public $newYPos = 0;

    public $showAddRackModal = false;

    // Warehouse State
    public $whseId = null;

    public $whseName = '';

    public $whseCode = '';

    public $whseExitLocation = 'BOTTOM_RIGHT';

    public $uploadBlueprint = null;

    public $currentLayoutImage = null;

    public $showEditWarehouseModal = false;

    // Create Warehouse State
    public $showCreateWarehouseModal = false;

    public $newWhseCode = '';

    public $newWhseName = '';

    // Edit Rack & Dimension State
    public $editingRackId = null;

    public $editRackCode = '';

    public $editRackLevels = 1;

    public $editRackSlotsPerLevel = 1;

    public $editRackCustomer = '';

    public $editRackMaxCapacity = 1;

    public $editRackOrientation = 'VERTICAL';

    public $editRackZone = 'DEFAULT';

    public $editRackXPos = 0;

    public $editRackYPos = 0;

    public $showEditRackModal = false;

    // Interactive Layout Settings Mode
    public $isLayoutEditMode = false;

    public function selectPosition($id)
    {
        $this->selectedPositionId = $id;
        $pos = WmsPosition::find($id);
        if ($pos) {
            $this->editMaxCapacity = $pos->max_capacity;
            $this->editCustomerCode = $pos->customer_code;
            $this->showDetail = true;
        }
    }

    public function saveSettings(?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        $pos = WmsPosition::find($this->selectedPositionId);
        if ($pos) {
            $pos->update([
                'max_capacity' => $this->editMaxCapacity,
                'customer_code' => $this->editCustomerCode,
            ]);

            // Recalculate status (EMPTY/PARTIAL/FULL)
            $wmsService->updatePositionStatus($pos->id);

            session()->flash('success', 'Pengaturan slot ' . $pos->position_code . ' berhasil disimpan.');
            $this->showDetail = false;
        }
    }

    public function resetSlot(?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        $pos = WmsPosition::find($this->selectedPositionId);
        if ($pos) {
            try {
                DB::beginTransaction();

                // Detach/unassign any pallet forms currently in this slot
                $pallets = \App\Models\WmsPalletForm::where('position_id', $pos->id)->get();
                foreach ($pallets as $pallet) {
                    $pallet->update([
                        'position_id' => null,
                        'assigned_at' => null,
                    ]);
                    $wmsService->logTransaction($pallet->pallet_id, 'UNASSIGN_SLOT', null, "Unassigned by Store via Slot Reset ({$pos->position_code})");
                }

                $pos->update([
                    'status' => 'EMPTY',
                    'last_item_code' => null,
                ]);

                $wmsService->updatePositionStatus($pos->id);

                DB::commit();

                $this->showDetail = false;
                session()->flash('success', 'Status slot ' . $pos->position_code . ' telah di-reset menjadi EMPTY dan semua pallet telah dilepas.');
            } catch (\Exception $e) {
                DB::rollBack();
                session()->flash('error', 'Gagal me-reset slot: ' . $e->getMessage());
            }
        }
    }

    public function deletePallet($palletId, ?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        try {
            DB::beginTransaction();

            $pallet = \App\Models\WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();
            $positionId = $pallet->position_id;

            // Delete all details
            $pallet->details()->delete();

            // Delete pallet header
            $pallet->delete();

            // Log transaction
            $wmsService->logTransaction($palletId, 'DELETE_PALLET', $positionId, 'Pallet deleted from Rack Mapping by Store');

            // Recalculate position status if it was assigned to a slot
            if ($positionId) {
                $wmsService->updatePositionStatus($positionId);
            }

            DB::commit();

            session()->flash('success', "Pallet {$palletId} berhasil dihapus.");

            // Refresh selected position if slot detail is open
            if ($this->selectedPositionId) {
                $this->selectPosition($this->selectedPositionId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus pallet: ' . $e->getMessage());
        }
    }

    public function deletePalletFromSlot($palletId, ?WmsService $wmsService = null)
    {
        $this->deletePallet($palletId, $wmsService);
    }

    public function unassignPalletFromSlot($palletId, ?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        try {
            DB::beginTransaction();

            $pallet = \App\Models\WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();
            $oldPosId = $pallet->position_id;

            $pallet->update([
                'position_id' => null,
                'assigned_at' => null,
            ]);

            if ($oldPosId) {
                $wmsService->updatePositionStatus($oldPosId);
            }

            $wmsService->logTransaction($pallet->pallet_id, 'UNASSIGN_SLOT', null, 'Unassigned from Rack Mapping by Store');

            DB::commit();

            session()->flash('success', "Pallet {$pallet->pallet_id} berhasil dilepas dari slot.");

            // Refresh selected position if slot detail is open
            if ($this->selectedPositionId) {
                $this->selectPosition($this->selectedPositionId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal melepas pallet: ' . $e->getMessage());
        }
    }

    protected function generatePositionCode(WmsRack $rack, int $levelNo, int $slotNo): string
    {
        $samplePos = $rack->positions->first();
        $whseCode = $rack->warehouse?->whse_code ?? $this->whseCode ?? 'J06';

        if ($samplePos && ! empty($samplePos->position_code)) {
            // Pattern: WHSE-CUST-RACK-L1S1
            if (preg_match('/^([A-Za-z0-9]+)-([A-Za-z0-9]+)-[A-Za-z0-9]+-L\d+S\d+$/i', $samplePos->position_code, $matches)) {
                $whsePrefix = $matches[1];
                $custCode = $matches[2];

                return "{$whsePrefix}-{$custCode}-{$rack->rack_code}-L{$levelNo}S{$slotNo}";
            }
            // Pattern: WHSE-CUST-RACK-L01-S01
            if (preg_match('/^([A-Za-z0-9]+)-([A-Za-z0-9]+)-[A-Za-z0-9]+-L\d+-S\d+$/i', $samplePos->position_code, $matches)) {
                $whsePrefix = $matches[1];
                $custCode = $matches[2];
                $lStr = str_pad($levelNo, 2, '0', STR_PAD_LEFT);
                $sStr = str_pad($slotNo, 2, '0', STR_PAD_LEFT);

                return "{$whsePrefix}-{$custCode}-{$rack->rack_code}-L{$lStr}-S{$sStr}";
            }
        }

        $levelStr = str_pad($levelNo, 2, '0', STR_PAD_LEFT);
        $slotStr = str_pad($slotNo, 2, '0', STR_PAD_LEFT);

        return strtoupper($rack->rack_code) . "-L{$levelStr}-S{$slotStr}";
    }

    protected function upsertPosition(WmsRack $rack, int $levelNo, int $slotNo, string $posCode, int $maxCapacity = 1, ?string $customerCode = null): WmsPosition
    {
        $existing = WmsPosition::withTrashed()->where('position_code', $posCode)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->update([
                'rack_id' => $rack->id,
                'level_no' => $levelNo,
                'slot_no' => $slotNo,
                'position_code' => $posCode,
                'max_capacity' => $maxCapacity,
                'customer_code' => $customerCode,
                'status' => $existing->palletForms()->where('total_pallet_qty', '>', 0)->exists() ? $existing->status : 'EMPTY',
            ]);

            return $existing;
        }

        return WmsPosition::create([
            'rack_id' => $rack->id,
            'level_no' => $levelNo,
            'slot_no' => $slotNo,
            'position_code' => $posCode,
            'max_capacity' => $maxCapacity,
            'customer_code' => $customerCode,
            'status' => 'EMPTY',
        ]);
    }

    public function createNewRack(?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        $this->validate([
            'newRackCode' => [
                'required',
                Rule::unique('wms_racks', 'rack_code')
                    ->where('whse_id', $this->whseId)
                    ->whereNull('deleted_at'),
            ],
            'newLevels' => 'required|integer|min:1',
            'newSlotsPerLevel' => 'required|integer|min:1',
            'newRackCustomer' => 'nullable|string',
            'newMaxCapacity' => 'required|integer|min:1',
            'newOrientation' => 'required|in:HORIZONTAL,VERTICAL',
            'newZone' => 'nullable|string|max:50',
            'newXPos' => 'nullable|integer',
            'newYPos' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $orientation = in_array($this->newOrientation, ['HORIZONTAL', 'VERTICAL']) ? $this->newOrientation : 'VERTICAL';
            $zone = strtoupper(trim($this->newZone ?: 'DEFAULT'));

            $rack = WmsRack::create([
                'whse_id' => $this->whseId,
                'rack_code' => strtoupper(trim($this->newRackCode)),
                'orientation' => $orientation,
                'zone' => $zone,
                'x_pos' => $this->newXPos ?: 0,
                'y_pos' => $this->newYPos ?: 0,
                'width' => $orientation === 'HORIZONTAL' ? 200 : 100,
                'height' => $orientation === 'HORIZONTAL' ? 100 : 200,
            ]);

            $cust = ! empty($this->newRackCustomer) ? strtoupper($this->newRackCustomer) : null;

            // Simple Batch generation
            for ($l = 1; $l <= $this->newLevels; $l++) {
                for ($s = 1; $s <= $this->newSlotsPerLevel; $s++) {
                    $posCode = $this->generatePositionCode($rack, $l, $s);
                    $this->upsertPosition($rack, $l, $s, $posCode, $this->newMaxCapacity, $cust);
                }
            }

            DB::commit();
            session()->flash('success', "Rak {$rack->rack_code} berhasil dibuat ({$orientation}, Kapasitas: {$this->newMaxCapacity}).");

            $this->reset(['newRackCode', 'newRackCustomer', 'newMaxCapacity', 'newOrientation', 'newZone', 'newXPos', 'newYPos', 'showAddRackModal']);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal membuat rak: ' . $e->getMessage());
        }
    }

    public function deleteRack($rackId)
    {
        try {
            DB::beginTransaction();

            $rack = WmsRack::find($rackId);
            if ($rack) {
                $positionIds = WmsPosition::withTrashed()->where('rack_id', $rack->id)->pluck('id');

                // Detach from Pallet Forms and reset status so they are not lost
                \App\Models\WmsPalletForm::whereIn('position_id', $positionIds)->update([
                    'position_id' => null,
                    'status' => 'GENERATED',
                ]);

                // Detach from Logs to preserve history without breaking foreign key
                \App\Models\WmsPalletLog::whereIn('position_id', $positionIds)->update([
                    'position_id' => null,
                ]);

                // Force delete all positions under this rack to keep DB clean
                WmsPosition::withTrashed()->whereIn('id', $positionIds)->forceDelete();

                $rackCode = $rack->rack_code;
                $rack->delete();

                DB::commit();
                session()->flash('success', 'Rak ' . $rackCode . ' beserta seluruh slotnya berhasil dihapus.');

                // Reset selected slot if it belongs to the deleted rack
                $this->selectedPositionId = null;
                $this->showDetail = false;
            } else {
                DB::rollBack();
                session()->flash('error', 'Rak tidak ditemukan.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus rak: ' . $e->getMessage());
        }
    }

    public function mount()
    {
        $warehouse = null;
        if ($this->whseId) {
            $warehouse = \App\Models\WmsWarehouse::find($this->whseId);
        }

        if (! $warehouse) {
            $warehouse = \App\Models\WmsWarehouse::first() ?? \App\Models\WmsWarehouse::create([
                'whse_code' => 'J06',
                'whse_name' => 'Monitoring Hunian Rak Gudang J06 (Highly Marelli)',
            ]);
        }

        $this->whseId = $warehouse->id;
        $this->whseName = $warehouse->whse_name;
        $this->whseCode = $warehouse->whse_code;
    }

    public function updatedWhseId($value)
    {
        $warehouse = \App\Models\WmsWarehouse::find($value);
        if ($warehouse) {
            $this->whseId = $warehouse->id;
            $this->whseName = $warehouse->whse_name;
            $this->whseCode = $warehouse->whse_code;
            $this->selectedPositionId = null;
            $this->showDetail = false;
        }
    }

    public function openCreateWarehouseModal()
    {
        $this->newWhseCode = '';
        $this->newWhseName = '';
        $this->showCreateWarehouseModal = true;
    }

    public function createWarehouse()
    {
        $this->validate([
            'newWhseCode' => 'required|string|max:50|unique:wms_warehouses,whse_code',
            'newWhseName' => 'required|string|max:255',
        ]);

        $newWarehouse = \App\Models\WmsWarehouse::create([
            'whse_code' => strtoupper(trim($this->newWhseCode)),
            'whse_name' => trim($this->newWhseName),
        ]);

        $this->whseId = $newWarehouse->id;
        $this->whseCode = $newWarehouse->whse_code;
        $this->whseName = $newWarehouse->whse_name;
        $this->selectedPositionId = null;
        $this->showDetail = false;
        $this->showCreateWarehouseModal = false;
        $this->reset(['newWhseCode', 'newWhseName']);

        session()->flash('success', "Gudang baru {$newWarehouse->whse_code} ({$newWarehouse->whse_name}) berhasil dibuat dan langsung dipilih. Anda bisa langsung menambahkan rak (+ ADD RACK).");
    }

    public function deleteWarehouse($id)
    {
        $whse = \App\Models\WmsWarehouse::find($id);
        if (! $whse) {
            return;
        }

        if ($whse->racks()->exists()) {
            session()->flash('error', "Gudang {$whse->whse_code} tidak dapat dihapus karena masih memiliki rak aktif. Hapus seluruh rak terlebih dahulu.");

            return;
        }

        if (\App\Models\WmsWarehouse::count() <= 1) {
            session()->flash('error', 'Minimal harus ada 1 gudang di sistem.');

            return;
        }

        $code = $whse->whse_code;
        $whse->delete();

        $fallback = \App\Models\WmsWarehouse::first();
        $this->whseId = $fallback->id;
        $this->whseName = $fallback->whse_name;
        $this->whseCode = $fallback->whse_code;
        $this->showEditWarehouseModal = false;

        session()->flash('success', "Gudang {$code} berhasil dihapus.");
    }

    public function openEditWarehouseModal()
    {
        $warehouse = \App\Models\WmsWarehouse::find($this->whseId) ?? \App\Models\WmsWarehouse::first();
        if ($warehouse) {
            $this->whseId = $warehouse->id;
            $this->whseName = $warehouse->whse_name;
            $this->whseCode = $warehouse->whse_code;
            $this->whseExitLocation = $warehouse->exit_location ?? 'BOTTOM_RIGHT';
            $this->currentLayoutImage = $warehouse->layout_image;
        }
        $this->uploadBlueprint = null;
        $this->showEditWarehouseModal = true;
    }

    public function removeLayoutImage()
    {
        $warehouse = \App\Models\WmsWarehouse::find($this->whseId);
        if ($warehouse && $warehouse->layout_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($warehouse->layout_image);
            $warehouse->update(['layout_image' => null]);
            $this->currentLayoutImage = null;
            session()->flash('success', 'Gambar layout berhasil dihapus.');
        }
    }

    public function saveWarehouse()
    {
        $this->validate([
            'whseName' => 'required|string|max:255',
            'whseCode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('wms_warehouses', 'whse_code')->ignore($this->whseId)->whereNull('deleted_at'),
            ],
            'whseExitLocation' => 'required|in:BOTTOM_LEFT,BOTTOM_RIGHT,TOP_LEFT,TOP_RIGHT',
            'uploadBlueprint' => 'nullable|image|max:5120',
        ]);

        $warehouse = \App\Models\WmsWarehouse::find($this->whseId) ?? \App\Models\WmsWarehouse::first();

        $imagePath = $warehouse?->layout_image;
        if ($this->uploadBlueprint) {
            $imagePath = $this->uploadBlueprint->store('wms/layouts', 'public');
        }

        if (! $warehouse) {
            $warehouse = \App\Models\WmsWarehouse::create([
                'whse_code' => strtoupper(trim($this->whseCode)),
                'whse_name' => trim($this->whseName),
                'exit_location' => $this->whseExitLocation,
                'layout_image' => $imagePath,
            ]);
        } else {
            $warehouse->update([
                'whse_name' => trim($this->whseName),
                'whse_code' => strtoupper(trim($this->whseCode)),
                'exit_location' => $this->whseExitLocation,
                'layout_image' => $imagePath,
            ]);
        }

        $this->whseId = $warehouse->id;
        $this->whseName = $warehouse->whse_name;
        $this->whseCode = $warehouse->whse_code;
        $this->currentLayoutImage = $warehouse->layout_image;
        $this->uploadBlueprint = null;

        session()->flash('success', 'Informasi Gudang & Denah Layout berhasil disimpan.');
        $this->showEditWarehouseModal = false;
    }

    public function openEditRackModal($rackId)
    {
        $rack = WmsRack::with('positions')->find($rackId);
        if (! $rack) {
            session()->flash('error', 'Rak tidak ditemukan.');

            return;
        }

        $this->editingRackId = $rack->id;
        $this->editRackCode = $rack->rack_code;
        $this->editRackOrientation = $rack->orientation ?: 'VERTICAL';
        $this->editRackZone = $rack->zone ?: $rack->effective_zone;
        $this->editRackXPos = $rack->x_pos ?? 0;
        $this->editRackYPos = $rack->y_pos ?? 0;

        $maxLevel = $rack->positions->max('level_no') ?? 1;
        $maxSlots = $rack->positions->max('slot_no') ?? 1;
        $firstPos = $rack->positions->first();

        $this->editRackLevels = max(1, $maxLevel);
        $this->editRackSlotsPerLevel = max(1, $maxSlots);
        $this->editRackCustomer = $firstPos?->customer_code ?? '';
        $this->editRackMaxCapacity = $firstPos?->max_capacity ?? 1;

        $this->showEditRackModal = true;
    }

    public function saveRackChanges(?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        $this->validate([
            'editRackCode' => [
                'required',
                'string',
                Rule::unique('wms_racks', 'rack_code')->ignore($this->editingRackId)->whereNull('deleted_at'),
            ],
            'editRackLevels' => 'required|integer|min:1|max:20',
            'editRackSlotsPerLevel' => 'required|integer|min:1|max:50',
            'editRackCustomer' => 'nullable|string',
            'editRackMaxCapacity' => 'required|integer|min:1',
            'editRackOrientation' => 'required|in:HORIZONTAL,VERTICAL',
            'editRackZone' => 'nullable|string|max:50',
            'editRackXPos' => 'nullable|integer',
            'editRackYPos' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $rack = WmsRack::with('positions')->findOrFail($this->editingRackId);
            $newRackCode = strtoupper(trim($this->editRackCode));
            $orientation = in_array($this->editRackOrientation, ['HORIZONTAL', 'VERTICAL']) ? $this->editRackOrientation : 'VERTICAL';
            $zone = strtoupper(trim($this->editRackZone ?: 'DEFAULT'));

            $rack->update([
                'rack_code' => $newRackCode,
                'orientation' => $orientation,
                'zone' => $zone,
                'x_pos' => (int) $this->editRackXPos,
                'y_pos' => (int) $this->editRackYPos,
            ]);

            $existingPositions = WmsPosition::where('rack_id', $rack->id)->get();
            $existingMap = $existingPositions->keyBy(fn ($p) => "{$p->level_no}-{$p->slot_no}");

            // Check if any positions to be deleted contain active pallets
            $positionsToDelete = [];
            foreach ($existingPositions as $pos) {
                if ($pos->level_no > $this->editRackLevels || $pos->slot_no > $this->editRackSlotsPerLevel) {
                    $hasActivePallet = \App\Models\WmsPalletForm::where('position_id', $pos->id)
                        ->where('total_pallet_qty', '>', 0)
                        ->exists();

                    if ($hasActivePallet) {
                        DB::rollBack();
                        session()->flash('error', "Gagal memperkecil rak: Slot {$pos->position_code} masih berisi pallet aktif. Pindahkan pallet terlebih dahulu.");

                        return;
                    }

                    $positionsToDelete[] = $pos;
                }
            }

            // Force delete excess empty positions
            foreach ($positionsToDelete as $pos) {
                \App\Models\WmsPalletForm::where('position_id', $pos->id)->update(['position_id' => null]);
                \App\Models\WmsPalletLog::where('position_id', $pos->id)->update(['position_id' => null]);
                $pos->forceDelete();
            }

            $cust = ! empty($this->editRackCustomer) ? strtoupper($this->editRackCustomer) : null;

            // Create new positions or update existing ones
            for ($l = 1; $l <= $this->editRackLevels; $l++) {
                for ($s = 1; $s <= $this->editRackSlotsPerLevel; $s++) {
                    $posCode = $this->generatePositionCode($rack, $l, $s);

                    $key = "{$l}-{$s}";
                    if ($existingMap->has($key)) {
                        $pos = $existingMap->get($key);
                        $pos->update([
                            'position_code' => $posCode,
                            'max_capacity' => $this->editRackMaxCapacity,
                            'customer_code' => $cust,
                        ]);
                    } else {
                        $this->upsertPosition($rack, $l, $s, $posCode, $this->editRackMaxCapacity, $cust);
                    }
                }
            }

            DB::commit();
            session()->flash('success', "Rak {$newRackCode} dan seluruh konfigurasinya ({$orientation}, Zona: {$zone}) berhasil diperbarui.");
            $this->showEditRackModal = false;

            if ($this->selectedPositionId) {
                $this->selectPosition($this->selectedPositionId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan perubahan rak: ' . $e->getMessage());
        }
    }

    public function toggleLayoutEditMode()
    {
        $this->isLayoutEditMode = ! $this->isLayoutEditMode;
    }

    public function toggleRackOrientation($rackId)
    {
        $rack = WmsRack::find($rackId);
        if (! $rack) {
            session()->flash('error', 'Rak tidak ditemukan.');

            return;
        }

        $current = $rack->orientation ?: 'VERTICAL';
        $newOrientation = ($current === 'VERTICAL') ? 'HORIZONTAL' : 'VERTICAL';

        $rack->update([
            'orientation' => $newOrientation,
            'width' => $newOrientation === 'HORIZONTAL' ? 200 : 100,
            'height' => $newOrientation === 'HORIZONTAL' ? 100 : 200,
        ]);

        session()->flash('success', "Orientasi Rak {$rack->rack_code} diubah menjadi {$newOrientation}.");
    }

    public function updateRackZone($rackId, $zone)
    {
        $rack = WmsRack::find($rackId);
        if (! $rack) {
            return;
        }

        $zone = strtoupper(trim($zone));
        $rack->update(['zone' => $zone]);
        session()->flash('success', "Zona Rak {$rack->rack_code} berhasil diubah menjadi {$zone}.");
    }

    public function moveRack($rackId, $direction)
    {
        $rack = WmsRack::find($rackId);
        if (! $rack) {
            return;
        }

        $step = 40;
        $x = $rack->x_pos ?? 0;
        $y = $rack->y_pos ?? 0;
        $sort = $rack->sort_order ?? 0;

        switch ($direction) {
            case 'left':
                $x = max(0, $x - $step);
                $sort = max(0, $sort - 1);
                break;
            case 'right':
                $x += $step;
                $sort += 1;
                break;
            case 'up':
                $y = max(0, $y - $step);
                break;
            case 'down':
                $y += $step;
                break;
        }

        $rack->update(['x_pos' => $x, 'y_pos' => $y, 'sort_order' => $sort]);
    }

    public function updateRackCoordinates($rackId, $x, $y)
    {
        $rack = WmsRack::find($rackId);
        if ($rack) {
            $rack->update([
                'x_pos' => max(0, (int) $x),
                'y_pos' => max(0, (int) $y),
            ]);
            session()->flash('success', "Posisi Rak {$rack->rack_code} diperbarui ke (X: {$x}, Y: {$y}).");
        }
    }

    public function moveRackToBlock($rackId, $targetY)
    {
        $rack = WmsRack::find($rackId);
        if ($rack) {
            $rack->update([
                'y_pos' => (int) $targetY,
            ]);
            session()->flash('success', "Rak {$rack->rack_code} dipindahkan ke baris Y: {$targetY}.");
        }
    }

    public function moveRackToWing($rackId, $targetX)
    {
        $rack = WmsRack::find($rackId);
        if ($rack) {
            $rack->update([
                'x_pos' => (int) $targetX,
            ]);
            session()->flash('success', "Rak {$rack->rack_code} dipindahkan ke X: {$targetX}.");
        }
    }

    public function applyLayoutPreset($presetType)
    {
        $racks = WmsRack::where('whse_id', $this->whseId)->orderBy('rack_code')->get();
        if ($racks->isEmpty()) {
            session()->flash('error', 'Belum ada rak di gudang ini.');

            return;
        }

        switch ($presetType) {
            case 'horizontal_dual_wing': // GUB style (Sayap Kiri & Kanan)
                $half = ceil($racks->count() / 2);
                foreach ($racks as $idx => $rack) {
                    $isLeft = $idx < $half;
                    $rowInWing = $isLeft ? $idx : ($idx - $half);
                    $pairIndex = intdiv($rowInWing, 2);
                    $pairSide = $rowInWing % 2;

                    $x = $isLeft ? 80 : 510;
                    $y = 90 + ($pairIndex * 150) + ($pairSide * 40);

                    $rack->update([
                        'orientation' => 'HORIZONTAL',
                        'x_pos' => $x,
                        'y_pos' => $y,
                        'width' => 360,
                        'height' => 36,
                        'sort_order' => $idx,
                    ]);
                }
                session()->flash('success', 'Preset Denah Horizontal 2-Sayap (GUB) berhasil diterapkan.');
                break;

            case 'vertical_dual_row': // GUA style (Baris Atas & Bawah)
                $half = ceil($racks->count() / 2);
                foreach ($racks as $idx => $rack) {
                    $isUpper = $idx >= $half;
                    $colInRow = $isUpper ? ($idx - $half) : $idx;
                    $pairIndex = intdiv($colInRow, 2);
                    $pairSide = $colInRow % 2;

                    $x = 240 + ($pairIndex * 100) + ($pairSide * 36);
                    $y = $isUpper ? 300 : 560;

                    $rack->update([
                        'orientation' => 'VERTICAL',
                        'x_pos' => $x,
                        'y_pos' => $y,
                        'width' => 36,
                        'height' => 180,
                        'sort_order' => $idx,
                    ]);
                }
                session()->flash('success', 'Preset Denah Vertikal 2-Baris (GUA) berhasil diterapkan.');
                break;

            case 'combination_j06': // J06 style (Horizontal Atas + Vertikal Berjejer)
                $horizCount = 0;
                $vertCount = 0;
                foreach ($racks as $idx => $rack) {
                    $isHoriz = str_starts_with($rack->rack_code, 'F4') || str_starts_with($rack->rack_code, 'W1') || $idx < 2;
                    if ($isHoriz) {
                        $x = 380 + ($horizCount * 140);
                        $y = 90;
                        $rack->update([
                            'orientation' => 'HORIZONTAL',
                            'x_pos' => $x,
                            'y_pos' => $y,
                            'width' => 120,
                            'height' => 36,
                            'sort_order' => $horizCount,
                        ]);
                        $horizCount++;
                    } else {
                        $pairIndex = intdiv($vertCount, 2);
                        $pairSide = $vertCount % 2;
                        $x = 170 + ($pairIndex * 90) + ($pairSide * 36);
                        $y = 420;
                        $rack->update([
                            'orientation' => 'VERTICAL',
                            'x_pos' => $x,
                            'y_pos' => $y,
                            'width' => 36,
                            'height' => 200,
                            'sort_order' => $vertCount,
                        ]);
                        $vertCount++;
                    }
                }
                session()->flash('success', 'Preset Denah Kombinasi (J06) berhasil diterapkan.');
                break;
        }
    }

    public function autoArrangeRacks()
    {
        $racks = WmsRack::where('whse_id', $this->whseId)->orderBy('rack_code')->get();
        if ($racks->isEmpty()) {
            session()->flash('error', 'Belum ada rak untuk diatur.');

            return;
        }

        $horizIndex = 0;
        $vertIndex = 0;

        foreach ($racks as $rack) {
            if ($rack->orientation === 'HORIZONTAL') {
                $x = 100 + ($horizIndex % 4) * 220;
                $y = 80 + intdiv($horizIndex, 4) * 110;
                $rack->update([
                    'x_pos' => $x,
                    'y_pos' => $y,
                    'sort_order' => $horizIndex,
                ]);
                $horizIndex++;
            } else {
                $col = intdiv($vertIndex, 2);
                $pairSide = $vertIndex % 2;
                $x = 120 + $col * 80 + $pairSide * 36;
                $y = 350;
                $rack->update([
                    'x_pos' => $x,
                    'y_pos' => $y,
                    'sort_order' => $vertIndex,
                ]);
                $vertIndex++;
            }
        }

        session()->flash('success', 'Layout rak gudang berhasil diatur dan dirapikan secara otomatis.');
    }

    public function addLevelToRack($rackId)
    {
        try {
            DB::beginTransaction();
            $rack = WmsRack::with('positions')->findOrFail($rackId);
            $maxLevel = $rack->positions->max('level_no') ?? 0;
            $newLevel = $maxLevel + 1;
            $maxSlots = $rack->positions->max('slot_no') ?? 1;
            $samplePos = $rack->positions->first();

            for ($s = 1; $s <= $maxSlots; $s++) {
                $posCode = $this->generatePositionCode($rack, $newLevel, $s);
                $this->upsertPosition(
                    $rack,
                    $newLevel,
                    $s,
                    $posCode,
                    $samplePos?->max_capacity ?? 1,
                    $samplePos?->customer_code
                );
            }

            DB::commit();
            session()->flash('success', "Berhasil menambahkan Level {$newLevel} pada Rak {$rack->rack_code}.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menambah level: ' . $e->getMessage());
        }
    }

    public function removeLevelFromRack($rackId)
    {
        try {
            DB::beginTransaction();
            $rack = WmsRack::with('positions')->findOrFail($rackId);
            $maxLevel = $rack->positions->max('level_no');
            if ($maxLevel <= 1) {
                session()->flash('error', "Rak {$rack->rack_code} harus memiliki minimal 1 level.");

                return;
            }

            $positionsOnMaxLevel = WmsPosition::where('rack_id', $rack->id)->where('level_no', $maxLevel)->get();
            foreach ($positionsOnMaxLevel as $pos) {
                $hasActivePallet = \App\Models\WmsPalletForm::where('position_id', $pos->id)->where('total_pallet_qty', '>', 0)->exists();
                if ($hasActivePallet) {
                    DB::rollBack();
                    session()->flash('error', "Tidak dapat menghapus Level {$maxLevel} karena slot {$pos->position_code} masih berisi pallet aktif.");

                    return;
                }
            }

            foreach ($positionsOnMaxLevel as $pos) {
                \App\Models\WmsPalletForm::where('position_id', $pos->id)->update(['position_id' => null]);
                \App\Models\WmsPalletLog::where('position_id', $pos->id)->update(['position_id' => null]);
                $pos->forceDelete();
            }

            DB::commit();
            session()->flash('success', "Level {$maxLevel} pada Rak {$rack->rack_code} berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus level: ' . $e->getMessage());
        }
    }

    public function addSlotToRack($rackId)
    {
        try {
            DB::beginTransaction();
            $rack = WmsRack::with('positions')->findOrFail($rackId);
            $levels = $rack->positions->pluck('level_no')->unique()->values();
            if ($levels->isEmpty()) {
                $levels = collect([1]);
            }
            $maxSlot = $rack->positions->max('slot_no') ?? 0;
            $newSlot = $maxSlot + 1;
            $samplePos = $rack->positions->first();

            foreach ($levels as $l) {
                $posCode = $this->generatePositionCode($rack, $l, $newSlot);
                $this->upsertPosition(
                    $rack,
                    $l,
                    $newSlot,
                    $posCode,
                    $samplePos?->max_capacity ?? 1,
                    $samplePos?->customer_code
                );
            }

            DB::commit();
            session()->flash('success', "Berhasil menambahkan Slot S{$newSlot} ke semua level pada Rak {$rack->rack_code}.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menambah slot: ' . $e->getMessage());
        }
    }

    public function removeSlotFromRack($rackId)
    {
        try {
            DB::beginTransaction();
            $rack = WmsRack::with('positions')->findOrFail($rackId);
            $maxSlot = $rack->positions->max('slot_no');
            if ($maxSlot <= 1) {
                session()->flash('error', "Rak {$rack->rack_code} harus memiliki minimal 1 slot per level.");

                return;
            }

            $positionsOnMaxSlot = WmsPosition::where('rack_id', $rack->id)->where('slot_no', $maxSlot)->get();
            foreach ($positionsOnMaxSlot as $pos) {
                $hasActivePallet = \App\Models\WmsPalletForm::where('position_id', $pos->id)->where('total_pallet_qty', '>', 0)->exists();
                if ($hasActivePallet) {
                    DB::rollBack();
                    session()->flash('error', "Tidak dapat menghapus Slot S{$maxSlot} karena slot {$pos->position_code} masih berisi pallet aktif.");

                    return;
                }
            }

            foreach ($positionsOnMaxSlot as $pos) {
                \App\Models\WmsPalletForm::where('position_id', $pos->id)->update(['position_id' => null]);
                \App\Models\WmsPalletLog::where('position_id', $pos->id)->update(['position_id' => null]);
                $pos->forceDelete();
            }

            DB::commit();
            session()->flash('success', "Slot S{$maxSlot} pada Rak {$rack->rack_code} berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus slot: ' . $e->getMessage());
        }
    }

    public function deletePositionSlot($positionId)
    {
        try {
            DB::beginTransaction();
            $pos = WmsPosition::findOrFail($positionId);
            $hasActivePallet = \App\Models\WmsPalletForm::where('position_id', $pos->id)->where('total_pallet_qty', '>', 0)->exists();
            if ($hasActivePallet) {
                session()->flash('error', "Slot {$pos->position_code} masih berisi pallet aktif. Kosongkan atau pindahkan pallet terlebih dahulu.");

                return;
            }

            $posCode = $pos->position_code;
            \App\Models\WmsPalletForm::where('position_id', $pos->id)->update(['position_id' => null]);
            \App\Models\WmsPalletLog::where('position_id', $pos->id)->update(['position_id' => null]);
            $pos->forceDelete();

            DB::commit();
            $this->selectedPositionId = null;
            $this->showDetail = false;
            session()->flash('success', "Slot {$posCode} berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menghapus slot: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $hasCustomerTable = \Illuminate\Support\Facades\Schema::hasTable('master_customer_delivery');

        $racks = WmsRack::where('whse_id', $this->whseId)
            ->with(['positions' => function ($query) use ($hasCustomerTable) {
                if ($hasCustomerTable) {
                    $query->with('customer');
                }
                $query->with(['palletForms' => function ($q) {
                    $q->with('details');
                }])
                    ->withCount('palletForms')
                    ->orderBy('level_no', 'desc')
                    ->orderBy('slot_no', 'asc');
            }])
            ->orderBy('rack_code')
            ->get();

        $matchingPositionIds = [];
        $searchTerm = trim($this->searchItem);
        if (! empty($searchTerm)) {
            $term = '%' . $searchTerm . '%';

            // 1. Position code or last_item_code
            $posIds1 = WmsPosition::where('position_code', 'like', $term)
                ->orWhere('last_item_code', 'like', $term)
                ->pluck('id');

            // 2. Pallet forms header fields (pallet_id, part_no, model_name, lot_no) with qty > 0
            $posIds2 = \App\Models\WmsPalletForm::whereNotNull('position_id')
                ->where('total_pallet_qty', '>', 0)
                ->where(function ($q) use ($term) {
                    $q->where('pallet_id', 'like', $term)
                        ->orWhere('part_no', 'like', $term)
                        ->orWhere('model_name', 'like', $term)
                        ->orWhere('lot_no', 'like', $term);
                })
                ->pluck('position_id');

            // 3. Pallet form details (item box level inside mixed pallets) with qty > 0
            $posIds3 = \App\Models\WmsPalletForm::whereNotNull('position_id')
                ->whereHas('details', function ($q) use ($term) {
                    $q->where('qty', '>', 0)
                        ->where(function ($dq) use ($term) {
                            $dq->where('part_no', 'like', $term)
                                ->orWhere('model_name', 'like', $term)
                                ->orWhere('spk_no', 'like', $term)
                                ->orWhere('label', 'like', $term);
                        });
                })
                ->pluck('position_id');

            $matchingPositionIds = $posIds1->merge($posIds2)->merge($posIds3)->unique()->filter()->values()->toArray();
        }

        // Live autocomplete suggestions querying WmsPalletFormDetail directly for items with QTY > 0
        $searchSuggestions = [];
        if (strlen($searchTerm) >= 1) {
            $term = '%' . $searchTerm . '%';

            $detailsQuery = \App\Models\WmsPalletFormDetail::whereHas('header', function ($q) {
                $q->whereNotNull('position_id');
            })
                ->where('qty', '>', 0)
                ->where(function ($q) use ($term) {
                    $q->where('part_no', 'like', $term)
                        ->orWhere('model_name', 'like', $term)
                        ->orWhere('spk_no', 'like', $term)
                        ->orWhere('label', 'like', $term);
                })
                ->with(['header.position'])
                ->get();

            $searchSuggestions = $detailsQuery->groupBy('part_no')
                ->map(function ($group, $partNo) {
                    $first = $group->first();
                    $totalQty = $group->sum('qty');
                    $positions = $group->map(fn ($d) => $d->header?->position?->position_code)->filter()->unique()->values()->all();

                    return [
                        'part_no' => $partNo ?: $first->part_no,
                        'model_name' => $first->model_name ?: 'No Model Name',
                        'total_qty' => $totalQty,
                        'pallet_count' => count($positions),
                        'positions' => implode(', ', array_slice($positions, 0, 3)) . (count($positions) > 3 ? '...' : ''),
                    ];
                })
                ->values()
                ->take(8)
                ->toArray();
        }

        $selectedPosRelations = ['palletForms.details'];
        if ($hasCustomerTable) {
            $selectedPosRelations[] = 'customer';
        }

        $selectedPosData = $this->selectedPositionId
            ? WmsPosition::with($selectedPosRelations)->withCount('palletForms')->find($this->selectedPositionId)
            : null;

        $customers = $hasCustomerTable
            ? \App\Models\MasterCustomerDelivery::orderBy('customer_code')->get()
            : collect();

        $unassignedPallets = \App\Models\WmsPalletForm::whereNull('position_id')
            ->where('total_pallet_qty', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $warehouse = \App\Models\WmsWarehouse::find($this->whseId) ?? \App\Models\WmsWarehouse::first();
        $warehouses = \App\Models\WmsWarehouse::orderBy('whse_code')->get();

        $racksByCode = $racks->keyBy('rack_code');
        $racksSortedByDistance = $racks->sortBy('distance_score')->values();
        $rackRanks = [];
        foreach ($racksSortedByDistance as $idx => $r) {
            $rackRanks[$r->rack_code] = $idx + 1;
        }

        // Dynamic rack segregation for 2D layout
        $horizontalRacks = $racks->filter(fn ($r) => $r->orientation === 'HORIZONTAL')
            ->sortBy(fn ($r) => ($r->y_pos * 1000) + ($r->sort_order ?: 0))
            ->values();

        $verticalRacks = $racks->filter(fn ($r) => $r->orientation !== 'HORIZONTAL')
            ->sortBy(fn ($r) => ($r->y_pos * 100000) + ($r->x_pos * 1000) + ($r->sort_order ?: 0))
            ->values();

        // Horizontal Wings & Pairs Clustering (Dual Wing layout like GUB)
        $horizontalWings = [];
        if ($horizontalRacks->isNotEmpty()) {
            $hasWings = $horizontalRacks->contains(fn ($r) => $r->x_pos >= 450) && $horizontalRacks->contains(fn ($r) => $r->x_pos < 450);

            if ($hasWings) {
                $leftRacks = $horizontalRacks->filter(fn ($r) => $r->x_pos < 450)->sortBy('y_pos')->values();
                $rightRacks = $horizontalRacks->filter(fn ($r) => $r->x_pos >= 450)->sortBy('y_pos')->values();

                $horizontalWings = [
                    'left' => [
                        'label' => 'Sayap Kiri (Left Wing)',
                        'pairs' => $this->clusterHorizontalPairs($leftRacks),
                    ],
                    'right' => [
                        'label' => 'Sayap Kanan (Right Wing)',
                        'pairs' => $this->clusterHorizontalPairs($rightRacks),
                    ],
                ];
            } else {
                $horizontalWings = [
                    'main' => [
                        'label' => 'Deretan Rak Horizontal',
                        'pairs' => $this->clusterHorizontalPairs($horizontalRacks),
                    ],
                ];
            }
        }

        // Multi-Row Blocks & Pairs Clustering for Vertical Racks
        $distinctYPositions = $verticalRacks->pluck('y_pos')->unique()->sort()->values();
        $clusteredRows = [];

        if ($distinctYPositions->isEmpty()) {
            $rowBlocks = [];
        } else {
            foreach ($distinctYPositions as $y) {
                $found = false;
                foreach ($clusteredRows as &$cluster) {
                    if (abs($cluster['center_y'] - $y) < 140) {
                        $cluster['y_values'][] = $y;
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $clusteredRows[] = [
                        'center_y' => $y,
                        'y_values' => [$y],
                    ];
                }
            }

            $rowBlocks = [];
            foreach ($clusteredRows as $idx => $cluster) {
                $matchingRacks = $verticalRacks->filter(fn ($r) => in_array($r->y_pos, $cluster['y_values']))->sortBy('x_pos')->values();

                $rowLabel = count($clusteredRows) === 2
                    ? ($idx === 0 ? 'Blok Baris Atas (Upper Aisle)' : 'Blok Baris Bawah (Lower Aisle)')
                    : 'Blok Baris ' . ($idx + 1);

                // Inside this row block, group into pairs or singles based on x_pos adjacency
                $pairs = [];
                $usedIds = [];

                for ($i = 0; $i < $matchingRacks->count(); $i++) {
                    $r1 = $matchingRacks[$i];
                    if (in_array($r1->id, $usedIds)) {
                        continue;
                    }

                    $r2 = null;
                    if ($i + 1 < $matchingRacks->count()) {
                        $candidate = $matchingRacks[$i + 1];
                        if (! in_array($candidate->id, $usedIds) && abs($candidate->x_pos - $r1->x_pos) <= 60) {
                            $r2 = $candidate;
                        }
                    }

                    if ($r2) {
                        $pairs[] = [
                            'type' => 'pair',
                            'rack1' => $r1,
                            'rack2' => $r2,
                            'col_label' => 'Kolom ' . (count($pairs) + 1),
                        ];
                        $usedIds[] = $r1->id;
                        $usedIds[] = $r2->id;
                    } else {
                        $pairs[] = [
                            'type' => 'single',
                            'rack1' => $r1,
                            'rack2' => null,
                            'col_label' => 'Kolom ' . (count($pairs) + 1),
                        ];
                        $usedIds[] = $r1->id;
                    }
                }

                $rowBlocks[] = [
                    'label' => $rowLabel,
                    'y_pos' => $cluster['center_y'],
                    'racks' => $matchingRacks,
                    'pairs' => $pairs,
                ];
            }
        }

        // Calculate dynamic summary per zone (like the summary table in the diagram)
        $zoneSummaries = [];
        $groupedByZone = $racks->groupBy(fn ($r) => $r->effective_zone);

        foreach ($groupedByZone as $zoneName => $zoneRacks) {
            $rackCodes = $zoneRacks->pluck('rack_code')->sort()->values();
            $firstCode = $rackCodes->first() ?? '-';
            $lastCode = $rackCodes->last() ?? '-';
            $codeRange = $rackCodes->count() > 1 ? "{$firstCode} - {$lastCode}" : $firstCode;

            $totalSlots = 0;
            $totalCapacity = 0;
            $maxLevels = 0;
            $maxSlotsPerLevel = 0;

            foreach ($zoneRacks as $r) {
                $positions = $r->positions;
                $totalSlots += $positions->count();
                $totalCapacity += $positions->sum('max_capacity');
                $maxLevels = max($maxLevels, $positions->max('level_no') ?? 0);
                $maxSlotsPerLevel = max($maxSlotsPerLevel, $positions->max('slot_no') ?? 0);
            }

            $colorMeta = match ($zoneName) {
                'LOGISTIC' => ['border' => 'border-emerald-500', 'badge' => 'bg-emerald-600 text-white', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-800'],
                'STORE' => ['border' => 'border-blue-500',    'badge' => 'bg-blue-600 text-white',    'bg' => 'bg-blue-50',    'text' => 'text-blue-800'],
                'WIP' => ['border' => 'border-amber-500',   'badge' => 'bg-amber-600 text-white',   'bg' => 'bg-amber-50',   'text' => 'text-amber-800'],
                'FG' => ['border' => 'border-purple-500',  'badge' => 'bg-purple-600 text-white',  'bg' => 'bg-purple-50',  'text' => 'text-purple-800'],
                default => ['border' => 'border-slate-500',   'badge' => 'bg-slate-700 text-white',   'bg' => 'bg-slate-50',   'text' => 'text-slate-800'],
            };

            $zoneSummaries[] = [
                'zone_name' => $zoneName,
                'code_range' => $codeRange,
                'rack_count' => $zoneRacks->count(),
                'level_count' => $maxLevels,
                'slot_count' => $maxSlotsPerLevel,
                'total_slots' => $totalSlots,
                'total_capacity' => $totalCapacity,
                'color' => $colorMeta,
            ];
        }

        return view('livewire.wms.rack-mapping', [
            'warehouse' => $warehouse,
            'warehouses' => $warehouses,
            'racks' => $racks,
            'racksByCode' => $racksByCode,
            'rackRanks' => $rackRanks,
            'horizontalRacks' => $horizontalRacks,
            'horizontalWings' => $horizontalWings,
            'verticalRacks' => $verticalRacks,
            'rowBlocks' => $rowBlocks,
            'zoneSummaries' => $zoneSummaries,
            'selectedPosData' => $selectedPosData,
            'customers' => $customers,
            'unassignedPallets' => $unassignedPallets,
            'matchingPositionIds' => $matchingPositionIds,
            'searchSuggestions' => $searchSuggestions,
        ]);
    }

    protected function clusterHorizontalPairs($racks)
    {
        $pairs = [];
        $usedIds = [];

        for ($i = 0; $i < $racks->count(); $i++) {
            $r1 = $racks[$i];
            if (in_array($r1->id, $usedIds)) {
                continue;
            }

            $r2 = null;
            if ($i + 1 < $racks->count()) {
                $candidate = $racks[$i + 1];
                if (! in_array($candidate->id, $usedIds) && abs($candidate->y_pos - $r1->y_pos) <= 65) {
                    $r2 = $candidate;
                }
            }

            if ($r2) {
                $pairs[] = [
                    'type' => 'pair',
                    'rack1' => $r1,
                    'rack2' => $r2,
                    'label' => "Pasangan {$r1->rack_code} & {$r2->rack_code}",
                ];
                $usedIds[] = $r1->id;
                $usedIds[] = $r2->id;
            } else {
                $pairs[] = [
                    'type' => 'single',
                    'rack1' => $r1,
                    'rack2' => null,
                    'label' => "Rak {$r1->rack_code}",
                ];
                $usedIds[] = $r1->id;
            }
        }

        return $pairs;
    }

    public function selectSearchSuggestion($itemCode)
    {
        $this->searchItem = $itemCode;
    }

    public function assignPalletToSelectedSlot($palletId, ?WmsService $wmsService = null)
    {
        $wmsService = $wmsService ?? app(WmsService::class);
        if (! $this->selectedPositionId) {
            session()->flash('error', 'Pilih slot rak terlebih dahulu.');

            return;
        }

        try {
            $pos = WmsPosition::find($this->selectedPositionId);
            $pallet = \App\Models\WmsPalletForm::where('pallet_id', $palletId)->firstOrFail();

            if ($pallet->total_pallet_qty <= 0) {
                session()->flash('error', "Pallet {$pallet->pallet_id} sudah habis (quantity 0) dan tidak perlu di-assign ke slot rak.");

                return;
            }

            $oldPosId = $pallet->position_id;
            $pallet->update([
                'position_id' => $pos->id,
                'assigned_at' => $pallet->assigned_at ?? now(),
            ]);

            if ($oldPosId) {
                $wmsService->updatePositionStatus($oldPosId);
            }
            $wmsService->updatePositionStatus($pos->id);

            $wmsService->logTransaction($pallet->pallet_id, 'ASSIGN_SLOT', $pos->id, 'Assigned by Store from Rack Mapping');

            session()->flash('success', "Pallet {$pallet->pallet_id} berhasil di-assign ke slot rak {$pos->position_code}.");
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal assign pallet: ' . $e->getMessage());
        }
    }
}
