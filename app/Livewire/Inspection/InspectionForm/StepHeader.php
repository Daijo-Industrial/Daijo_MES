<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Models\Inspection\InspectionReport;
use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
use App\Models\MasterMachine;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StepHeader extends Component
{
    public $document_number;

    public $customer;

    public $inspection_date;

    public $part_number;

    public $part_search = '';

    public bool $show_part_dropdown = false;

    public bool $showManualSpecs = false;

    public $part_name;

    public $weight;

    public $weight_uom = 'g';

    public $material;

    public $color;

    public $tool_number_or_cav_number;

    public $machine_number;

    public string $machine_search = '';

    public bool $show_machine_dropdown = false;

    public $shift;

    public $operator;

    public $inspector;

    public array $sessionSaved = [];

    public ?string $savedAt = null;

    public bool $isSaved = false;

    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', Rule::unique(InspectionReport::class, 'document_number')],
            'customer' => 'required|string',
            'inspection_date' => 'required|date',
            'part_number' => 'required|string',
            'part_name' => 'required|string',
            'weight' => 'required|numeric|min:0',
            'weight_uom' => 'required|string',
            'material' => 'required|string',
            'color' => 'required|string',
            'tool_number_or_cav_number' => 'required|string',
            'machine_number' => 'required|string',
            'shift' => 'required|integer|min:1|max:3',
            'operator' => 'required|string',
            'inspector' => 'required|string|max:255',
        ];
    }

    protected function snapshot(): array
    {
        return [
            'document_number' => $this->document_number,
            'customer' => $this->customer,
            'inspection_date' => $this->inspection_date,
            'part_number' => $this->part_number,
            'part_name' => $this->part_name,
            'weight' => $this->weight,
            'weight_uom' => $this->weight_uom,
            'material' => $this->material,
            'color' => $this->color,
            'tool_number_or_cav_number' => $this->tool_number_or_cav_number,
            'machine_number' => $this->machine_number,
            'shift' => $this->shift,
            'operator' => strtoupper(trim((string) $this->operator)),
            'inspector' => strtoupper(trim((string) $this->inspector)),
        ];
    }

    public function isFieldSaved(string $field): bool
    {
        $current = data_get($this, $field);
        $baseline = data_get($this->sessionSaved, $field);
        $normalize = fn ($v) => $v === '' ? null : $v;

        return $normalize($current) !== null && $normalize($current) === $normalize($baseline);
    }

    public function isGroupSaved(array $fields): bool
    {
        foreach ($fields as $f) {
            if (! $this->isFieldSaved($f)) {
                return false;
            }
        }

        return true;
    }

    private function determineCurrentShift(): int
    {
        $now = now()->format('H:i');
        if ($now >= '07:30' && $now < '15:30') {
            return 1;
        }
        if ($now >= '15:30' && $now < '23:30') {
            return 2;
        }

        return 3;
    }

    public function mount(): void
    {
        $saved = session('stepHeaderSaved', []);

        $this->sessionSaved = $saved;
        $this->savedAt = session('stepHeaderSavedAt');
        $this->isSaved = ! empty($this->sessionSaved);

        if ($saved) {
            foreach ($saved as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }

        if (! $this->document_number) {
            $this->document_number = 'INSP-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        }

        if (! $this->inspection_date) {
            $this->inspection_date = now()->format('Y-m-d');
        }

        if (! $this->shift) {
            $this->shift = $this->determineCurrentShift();
        }

        if (empty($this->inspector)) {
            $user = auth()->user();
            if ($user && ! empty($user->name)) {
                $this->inspector = strtoupper($user->name);
            }
        } else {
            $this->inspector = strtoupper(trim((string) $this->inspector));
        }

        if (! empty($this->operator)) {
            $this->operator = strtoupper(trim((string) $this->operator));
        }

        // Detect machine from user if role is OPERATOR
        if (empty($this->machine_number)) {
            $user = auth()->user();
            if ($user && $user->hasRole('OPERATOR')) {
                $this->machine_number = strtoupper($user->name);
                $this->machine_search = $this->machine_number;
            }
        } else {
            $this->machine_search = $this->machine_number;
        }

        if (! empty($this->part_number)) {
            $this->part_search = $this->part_number;
        }
    }

    public function updated($field): void
    {
        if ($field === 'operator' && is_string($this->operator)) {
            $this->operator = strtoupper(trim($this->operator));
        }

        if ($field === 'inspector' && is_string($this->inspector)) {
            $this->inspector = strtoupper(trim($this->inspector));
        }

        if ($field === 'machine_search') {
            $this->show_machine_dropdown = true;
            $this->machine_number = strtoupper(trim($this->machine_search));
        }

        if ($field === 'part_search') {
            $this->show_part_dropdown = ! empty(trim($this->part_search));
        }

        $this->validateOnly($field);

        if ($field === 'part_number' && ! empty($this->part_number)) {
            $item = MasterListItem::with(['customer', 'businessPartner'])
                ->where('item_code', $this->part_number)
                ->first();

            if ($item) {
                $this->part_search = $item->item_code;
                if (empty($this->part_name)) {
                    $this->part_name = $item->item_name;
                }
                if (empty($this->customer)) {
                    $this->customer = $item->customer?->customer_name ?: ($item->businessPartner?->bp_name ?: 'N/A');
                }
                if (empty($this->color) && ! empty($item->color)) {
                    $this->color = $item->color;
                }
                if (empty($this->weight) && ! empty($item->part_weight)) {
                    $this->weight = (float) $item->part_weight;
                }
                if (empty($this->tool_number_or_cav_number) && ! empty($item->cavity)) {
                    $this->tool_number_or_cav_number = (string) $item->cavity;
                }
                if (! empty($item->standart_packaging_list)) {
                    session(['stepHeaderSaved_default_snp' => (int) $item->standart_packaging_list]);
                }
            }
        }
    }

    public function selectPartItem(string $itemCode): void
    {
        $item = MasterListItem::with(['customer', 'businessPartner'])
            ->where('item_code', $itemCode)
            ->first();

        if ($item) {
            $this->part_number = $item->item_code;
            $this->part_search = $item->item_code;
            $this->part_name = $item->item_name;

            $this->customer = $item->customer?->customer_name ?: ($item->businessPartner?->bp_name ?: 'N/A');

            if (! empty($item->color)) {
                $this->color = $item->color;
            }
            if (! empty($item->part_weight)) {
                $this->weight = (float) $item->part_weight;
            }
            if (! empty($item->cavity)) {
                $this->tool_number_or_cav_number = (string) $item->cavity;
            }
            if (! empty($item->standart_packaging_list)) {
                session(['stepHeaderSaved_default_snp' => (int) $item->standart_packaging_list]);
            }

            $this->show_part_dropdown = false;
            $this->showManualSpecs = false;
            $this->resetValidation(['part_number', 'part_name', 'customer']);
        }
    }

    public function closePartDropdown(): void
    {
        $this->show_part_dropdown = false;
    }

    public function selectMachine(string $machineName): void
    {
        $this->machine_number = strtoupper($machineName);
        $this->machine_search = $this->machine_number;
        $this->show_machine_dropdown = false;
        $this->validateOnly('machine_number');
    }

    public function closeMachineDropdown(): void
    {
        $this->show_machine_dropdown = false;
    }

    public function toggleManualSpecs(): void
    {
        $this->showManualSpecs = ! $this->showManualSpecs;
    }

    public function saveStep(): void
    {
        if (! empty($this->operator)) {
            $this->operator = strtoupper(trim($this->operator));
        }
        if (! empty($this->inspector)) {
            $this->inspector = strtoupper(trim($this->inspector));
        }
        if (! empty($this->machine_search)) {
            $this->machine_number = strtoupper(trim($this->machine_search));
        }

        $this->validate();

        $data = $this->snapshot();

        session([
            'stepHeaderSaved' => $data,
            'stepHeaderSavedAt' => now()->toIso8601String(),
            'stepHeaderSavedHash' => md5(json_encode($data)),
        ]);

        $this->sessionSaved = $data;
        $this->savedAt = session('stepHeaderSavedAt');
        $this->isSaved = true;

        $this->dispatch('stepHeaderSaved', data: $data, savedAt: $this->savedAt);
        $this->dispatch('nextStep');
        $this->dispatch('toast', message: 'Header informasi berhasil disimpan!');
    }

    public function render(): View
    {
        $searchResults = [];
        if (! empty(trim($this->part_search))) {
            $searchResults = MasterListItem::with(['customer', 'businessPartner'])
                ->where(function ($q) {
                    $q->where('item_code', 'like', "%{$this->part_search}%")
                        ->orWhere('item_name', 'like', "%{$this->part_search}%");
                })
                ->limit(8)
                ->get();
        }

        // All active business partners in CUSTOMER category
        $customerList = MasterBusinessPartner::customers()
            ->select('bp_code', 'bp_name')
            ->orderBy('bp_name')
            ->get();

        // Machine list sourced from Users with role OPERATOR merged with MasterMachine
        $operatorUsers = User::whereHas('role', fn ($q) => $q->where('name', 'OPERATOR'))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $masterMachines = MasterMachine::query()
            ->when(method_exists(MasterMachine::class, 'scopeActive'), fn ($q) => $q->active(), fn ($q) => $q->where('is_active', true))
            ->pluck('name')
            ->toArray();

        $machineList = collect(array_merge($operatorUsers, $masterMachines))
            ->filter()
            ->map(fn ($m) => strtoupper(trim((string) $m)))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $filteredMachines = $machineList;
        if (! empty(trim($this->machine_search))) {
            $searchUpper = strtoupper(trim($this->machine_search));
            $filteredMachines = array_values(array_filter($machineList, fn ($m) => str_contains($m, $searchUpper)));
        }

        $configuredInspectors = (array) config('mes.ipqc_inspectors', ['Dewi', 'Rini', 'Agus', 'Budi', 'Siti', 'Wahyu', 'Rina']);
        $configuredInspectors = array_map(fn ($n) => strtoupper((string) $n), $configuredInspectors);
        $user = auth()->user();
        if ($user && ! empty($user->name)) {
            $userNameUpper = strtoupper($user->name);
            if (! in_array($userNameUpper, $configuredInspectors, true)) {
                array_unshift($configuredInspectors, $userNameUpper);
            }
        }

        return view('livewire.inspection.form.step-header', [
            'customerList' => $customerList,
            'searchResults' => $searchResults,
            'machineOptions' => $filteredMachines,
            'inspectorOptions' => $configuredInspectors,
        ]);
    }
}