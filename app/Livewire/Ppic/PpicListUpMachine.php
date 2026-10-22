<?php

namespace App\Livewire\Ppic;

use App\Models\MasterListItem;
use App\Models\PpicListUp;
use App\Models\PpicListUpItem;
use App\Models\User;
use App\Services\PpicListUpService;
use Carbon\Carbon;
use Livewire\Component;

class PpicListUpMachine extends Component
{
    public string $selectedDate = '';

    public ?int $listUpId = null;

    public string $status = 'DRAFT';

    public ?string $notes = '';

    public bool $isLocked = false;

    public array $rows = [];

    public array $availableMachines = [];

    // History Modal State
    public bool $showHistoryModal = false;

    public array $historyListUps = [];

    // Part Search Modal State
    public bool $showPartSearchModal = false;

    public ?int $searchRowIndex = null;

    public string $partSearchQuery = '';

    public array $partSearchResults = [];

    // SPK Search Modal State
    public bool $showSpkSearchModal = false;

    public ?int $searchSpkRowIndex = null;

    public string $spkSearchQuery = '';

    public array $spkSearchResults = [];

    // Flash notification
    public ?string $alertMessage = null;

    public string $alertType = 'success'; // success, error, info

    public function mount(?string $date = null)
    {
        $this->selectedDate = $date ?: Carbon::now()->format('Y-m-d');
        $this->loadAvailableMachines();
        $this->loadListUp();
    }

    public function updatedSelectedDate()
    {
        $this->loadListUp();
    }

    protected function loadAvailableMachines(): void
    {
        $machines = User::whereHas('role', function ($q) {
            $q->where('name', 'OPERATOR');
        })->get();

        $this->availableMachines = $machines->map(function ($m) {
            $cleanName = preg_match('/^0*(\d+)([A-Z])$/', $m->name, $matches)
                ? ($matches[1] . $matches[2])
                : $m->name;

            return [
                'id' => $m->id,
                'name' => $cleanName,
                'raw_name' => $m->name,
            ];
        })->sortBy('name')->values()->toArray();
    }

    public function loadListUp(): void
    {
        $listUp = PpicListUp::with('items')
            ->where('date', $this->selectedDate)
            ->first();

        if ($listUp) {
            $this->listUpId = $listUp->id;
            $this->status = $listUp->status;
            $this->notes = $listUp->notes;
            $this->isLocked = ($this->status === 'GENERATED' || $this->status === 'FINAL');

            $service = app(PpicListUpService::class);
            $this->rows = $listUp->items->map(function ($item, $idx) use ($service) {
                $details = ! empty($item->part_no) ? $service->resolvePartDetails($item->part_no) : [];
                $availableSpks = $details['available_spks'] ?? [];

                if (! empty($item->spk_no) && ! collect($availableSpks)->contains('spk_number', $item->spk_no)) {
                    $availableSpks[] = [
                        'spk_number' => $item->spk_no,
                        'planned_quantity' => 0,
                        'remaining_quantity' => 0,
                        'post_date' => '-',
                        'production_status' => '-',
                    ];
                }

                return [
                    'id' => $item->id,
                    'machine_id' => $item->machine_id,
                    'machine_name' => $item->machine_name,
                    'is_change_to' => false,
                    'part_no' => $item->part_no,
                    'description' => $item->description,
                    'material_type' => $item->material_type,
                    'spk_no' => $item->spk_no,
                    'available_spks' => $availableSpks,
                    'is_manual_spk' => empty($availableSpks),
                    'cavity' => $item->cavity,
                    'cycle_time' => $item->cycle_time,
                    'target_per_hour' => $item->target_per_hour,
                    'operator_shift_1' => $item->operator_shift_1 > 0 ? $item->operator_shift_1 : null,
                    'operator_shift_2' => $item->operator_shift_2 > 0 ? $item->operator_shift_2 : null,
                    'operator_shift_3' => $item->operator_shift_3 > 0 ? $item->operator_shift_3 : null,
                    'qty_to_run' => $item->qty_to_run > 0 ? $item->qty_to_run : null,
                    'reason' => $item->reason,
                    'priority' => $item->priority,
                    'is_generated' => (bool) $item->is_generated,
                ];
            })->toArray();
        } else {
            $this->listUpId = null;
            $this->status = 'DRAFT';
            $this->notes = '';
            $this->isLocked = false;
            $this->initDefaultRows();
        }
    }

    protected function initDefaultRows(): void
    {
        $this->rows = [];

        // Prepopulate with first 5-8 machines or leave empty with 1 template row
        $preset = ! empty($this->availableMachines) ? array_slice($this->availableMachines, 0, 8) : [];
        if (empty($preset)) {
            $preset = [
                ['id' => null, 'name' => '350F'],
                ['id' => null, 'name' => '450H'],
                ['id' => null, 'name' => '450I'],
                ['id' => null, 'name' => '450J'],
                ['id' => null, 'name' => '550B'],
            ];
        }

        foreach ($preset as $m) {
            $this->rows[] = $this->createEmptyRow($m['name'], $m['id']);
        }
    }

    protected function createEmptyRow(string $machineName = '', ?int $machineId = null, bool $isChangeTo = false): array
    {
        return [
            'id' => null,
            'machine_id' => $machineId,
            'machine_name' => $machineName,
            'is_change_to' => $isChangeTo,
            'part_no' => '',
            'description' => '',
            'material_type' => '',
            'spk_no' => '',
            'available_spks' => [],
            'is_manual_spk' => false,
            'cavity' => 1,
            'cycle_time' => 0,
            'target_per_hour' => 0,
            'operator_shift_1' => null,
            'operator_shift_2' => null,
            'operator_shift_3' => null,
            'qty_to_run' => null,
            'reason' => '',
            'priority' => 0,
            'is_generated' => false,
        ];
    }

    public function addRow(?string $machineName = null, ?int $machineId = null, bool $isChangeTo = false, ?int $afterIndex = null): void
    {
        $newRow = $this->createEmptyRow($machineName ?? '', $machineId, $isChangeTo);

        if ($afterIndex !== null && isset($this->rows[$afterIndex])) {
            array_splice($this->rows, $afterIndex + 1, 0, [$newRow]);
        } else {
            $this->rows[] = $newRow;
        }
    }

    public function removeRow(int $index): void
    {
        if (isset($this->rows[$index])) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
        }
    }

    public function onPartNoInput(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }

        $partNo = trim($this->rows[$index]['part_no']);
        if (empty($partNo)) {
            return;
        }

        $service = app(PpicListUpService::class);
        $details = $service->resolvePartDetails($partNo);

        $this->rows[$index]['description'] = $details['description'];
        $this->rows[$index]['cavity'] = $details['cavity'];
        $this->rows[$index]['cycle_time'] = $details['cycle_time'];
        $this->rows[$index]['target_per_hour'] = $details['target_per_hour'];
        $this->rows[$index]['spk_no'] = $details['spk_no'];
        $this->rows[$index]['available_spks'] = $details['available_spks'] ?? [];
        $this->rows[$index]['is_manual_spk'] = empty($details['available_spks']);
        $this->rows[$index]['material_type'] = $details['material_type'];
    }

    public function onCycleTimeChanged(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }
        $ct = (int) $this->rows[$index]['cycle_time'];
        $this->rows[$index]['target_per_hour'] = $ct > 0 ? (int) round(3600 / $ct) : 0;
    }

    // Modal Search Part
    public function openPartSearch(int $index): void
    {
        $this->searchRowIndex = $index;
        $this->partSearchQuery = $this->rows[$index]['part_no'] ?? '';
        $this->executePartSearch();
        $this->showPartSearchModal = true;
    }

    public function updatedPartSearchQuery(): void
    {
        $this->executePartSearch();
    }

    public function executePartSearch(): void
    {
        $query = trim($this->partSearchQuery);
        $dbQuery = MasterListItem::select('item_code', 'item_name', 'cavity', 'cycle_time');

        if (! empty($query)) {
            $dbQuery->where(function ($q) use ($query) {
                $q->where('item_code', 'LIKE', "%{$query}%")
                    ->orWhere('item_name', 'LIKE', "%{$query}%");
            });
        }

        $this->partSearchResults = $dbQuery->limit(20)->get()->toArray();
    }

    public function selectPart(string $partNo): void
    {
        if ($this->searchRowIndex !== null && isset($this->rows[$this->searchRowIndex])) {
            $this->rows[$this->searchRowIndex]['part_no'] = $partNo;
            $this->onPartNoInput($this->searchRowIndex);
        }

        $this->showPartSearchModal = false;
        $this->searchRowIndex = null;
    }

    // Modal Search SPK Master
    public function openSpkSearch(int $index): void
    {
        $this->searchSpkRowIndex = $index;
        // Search by part_no or spk_no
        $this->spkSearchQuery = $this->rows[$index]['part_no'] ?: ($this->rows[$index]['spk_no'] ?? '');
        $this->executeSpkSearch();
        $this->showSpkSearchModal = true;
    }

    public function updatedSpkSearchQuery(): void
    {
        $this->executeSpkSearch();
    }

    public function executeSpkSearch(): void
    {
        $service = app(PpicListUpService::class);
        $this->spkSearchResults = $service->searchSpkMasters($this->spkSearchQuery, 30);
    }

    public function selectSpk(string $spkNo, ?float $plannedQty = null, ?string $itemCode = null): void
    {
        if ($this->searchSpkRowIndex !== null && isset($this->rows[$this->searchSpkRowIndex])) {
            $rowIndex = $this->searchSpkRowIndex;

            // If row has no part_no and SPK has an item_code, auto-fill part details
            if (empty($this->rows[$rowIndex]['part_no']) && ! empty($itemCode)) {
                $this->rows[$rowIndex]['part_no'] = $itemCode;
                $this->onPartNoInput($rowIndex);
            }

            $this->rows[$rowIndex]['spk_no'] = $spkNo;

            // Ensure this SPK is present in available_spks list
            $available = $this->rows[$rowIndex]['available_spks'] ?? [];
            if (! collect($available)->contains('spk_number', $spkNo)) {
                $available[] = [
                    'spk_number' => $spkNo,
                    'planned_quantity' => $plannedQty ?? 0,
                    'remaining_quantity' => $plannedQty ?? 0,
                    'post_date' => '-',
                    'production_status' => 'OPEN',
                ];
                $this->rows[$rowIndex]['available_spks'] = $available;
            }

            $this->rows[$rowIndex]['is_manual_spk'] = false;
        }

        $this->showSpkSearchModal = false;
        $this->searchSpkRowIndex = null;
    }

    public function toggleManualSpk(int $index): void
    {
        if (isset($this->rows[$index])) {
            $this->rows[$index]['is_manual_spk'] = ! ($this->rows[$index]['is_manual_spk'] ?? false);
        }
    }

    public function onMachineChange(int $index): void
    {
        if (! isset($this->rows[$index])) {
            return;
        }
        $name = $this->rows[$index]['machine_name'];
        $match = collect($this->availableMachines)->firstWhere('name', $name);
        $this->rows[$index]['machine_id'] = $match['id'] ?? null;
    }

    public function setDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->loadListUp();
    }

    public function goToPreviousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->loadListUp();
    }

    public function goToNextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->loadListUp();
    }

    public function goToToday(): void
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->loadListUp();
    }

    public function openHistory(): void
    {
        $this->historyListUps = PpicListUp::withCount('items')
            ->orderBy('date', 'desc')
            ->limit(40)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'date' => Carbon::parse($l->date)->format('Y-m-d'),
                    'formatted_date' => Carbon::parse($l->date)->translatedFormat('l, d F Y'),
                    'status' => $l->status,
                    'items_count' => $l->items_count,
                    'notes' => $l->notes,
                ];
            })
            ->toArray();

        $this->showHistoryModal = true;
    }

    public function selectHistoryDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->showHistoryModal = false;
        $this->loadListUp();
    }

    public function reopenDraft(): void
    {
        if ($this->listUpId) {
            PpicListUp::where('id', $this->listUpId)->update(['status' => 'DRAFT']);
        }
        $this->status = 'DRAFT';
        $this->isLocked = false;
        $this->alertMessage = 'Status list up berhasil diubah kembali ke DRAFT. Anda dapat mengedit jadwal ini.';
        $this->alertType = 'info';
    }

    public function saveDraft(): void
    {
        if ($this->isLocked) {
            $this->alertMessage = 'List up ini telah difinalisasi / digenerate dan terkunci dari perubahan.';
            $this->alertType = 'error';

            return;
        }

        $this->persistListUp();
        $this->alertMessage = "Draft List Up mesin untuk tanggal {$this->selectedDate} berhasil disimpan.";
        $this->alertType = 'success';
    }

    public function generateToDailyItemCodes(): void
    {
        if ($this->isLocked) {
            $this->alertMessage = 'List up ini telah difinalisasi / digenerate sebelumnya.';
            $this->alertType = 'error';

            return;
        }

        $this->persistListUp();

        $listUp = PpicListUp::find($this->listUpId);
        if (! $listUp) {
            $this->alertMessage = 'List Up tidak ditemukan untuk diproses.';
            $this->alertType = 'error';

            return;
        }

        $service = app(PpicListUpService::class);
        $result = $service->generateDailyItemCodes($listUp);

        if ($result['success']) {
            $this->status = 'GENERATED';
            $this->isLocked = true;
            $this->alertMessage = $result['message'];
            $this->alertType = 'success';
            $this->loadListUp(); // Refresh state
        } else {
            $this->alertMessage = $result['message'];
            $this->alertType = 'error';
        }
    }

    protected function persistListUp(): void
    {
        $listUp = PpicListUp::updateOrCreate(
            [
                'date' => $this->selectedDate,
            ],
            [
                'zone' => 'ALL',
                'status' => $this->status,
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]
        );

        $this->listUpId = $listUp->id;

        // Sync items
        $existingItemIds = [];
        foreach ($this->rows as $idx => $row) {
            if (empty(trim($row['part_no'])) && empty(trim($row['machine_name']))) {
                continue;
            }

            // Ensure machine_id strictly matches machine_name
            $mId = $row['machine_id'] ?? null;
            if (! empty($row['machine_name'])) {
                $targetClean = preg_replace('/^0+/', '', strtoupper($row['machine_name']));
                $match = collect($this->availableMachines)->first(function ($m) use ($targetClean, $row) {
                    $mClean = preg_replace('/^0+/', '', strtoupper($m['name']));

                    return $mClean === $targetClean || strtoupper($m['name']) === strtoupper($row['machine_name']);
                });
                if ($match) {
                    $mId = $match['id'];
                }
            }

            $item = PpicListUpItem::updateOrCreate(
                [
                    'id' => $row['id'] ?? null,
                    'list_up_id' => $listUp->id,
                ],
                [
                    'machine_id' => $mId,
                    'machine_name' => $row['machine_name'] ?: 'UNKNOWN',
                    'part_no' => $row['part_no'] ?: '',
                    'description' => $row['description'] ?: null,
                    'material_type' => $row['material_type'] ?: null,
                    'spk_no' => $row['spk_no'] ?: null,
                    'cavity' => (int) ($row['cavity'] ?? 1),
                    'cycle_time' => (int) ($row['cycle_time'] ?? 0),
                    'target_per_hour' => (int) ($row['target_per_hour'] ?? 0),
                    'operator_shift_1' => (int) ($row['operator_shift_1'] ?? 0),
                    'operator_shift_2' => (int) ($row['operator_shift_2'] ?? 0),
                    'operator_shift_3' => (int) ($row['operator_shift_3'] ?? 0),
                    'qty_to_run' => (int) ($row['qty_to_run'] ?? 0),
                    'reason' => $row['reason'] ?: null,
                    'priority' => (int) ($row['priority'] ?? $idx),
                ]
            );

            $existingItemIds[] = $item->id;
        }

        // Delete items that were removed
        PpicListUpItem::where('list_up_id', $listUp->id)
            ->whereNotIn('id', $existingItemIds)
            ->delete();
    }

    public function render()
    {
        return view('livewire.ppic.ppic-list-up-machine')
            ->layout('layouts.app');
    }
}
