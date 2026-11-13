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

    public string $customer_search = '';

    public bool $show_customer_dropdown = false;

    public $inspection_date;

    public $part_number;

    public string $part_search = '';

    public bool $show_part_dropdown = false;

    public bool $isSearchingAgain = false;

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

    public function getValidCustomers(): array
    {
        $bpCustomers = MasterBusinessPartner::query()
            ->whereNotNull('bp_name')
            ->where('bp_name', '!=', '')
            ->distinct()
            ->pluck('bp_name')
            ->toArray();

        $deliveryCustomers = MasterCustomerDelivery::query()
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->distinct()
            ->pluck('customer_name')
            ->toArray();

        return collect(array_merge($bpCustomers, $deliveryCustomers))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function getValidMachines(): array
    {
        $operatorUsers = User::whereHas('role', fn ($q) => $q->where('name', 'OPERATOR'))
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $masterMachines = MasterMachine::query()
            ->when(method_exists(MasterMachine::class, 'scopeActive'), fn ($q) => $q->active(), fn ($q) => $q->where('is_active', true))
            ->pluck('name')
            ->toArray();

        $itemTipeMesin = MasterListItem::query()
            ->whereNotNull('tipe_mesin')
            ->where('tipe_mesin', '!=', '')
            ->distinct()
            ->pluck('tipe_mesin')
            ->toArray();

        return collect(array_merge($operatorUsers, $masterMachines, $itemTipeMesin))
            ->filter()
            ->map(fn ($m) => strtoupper(trim((string) $m)))
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    public function rules(): array
    {
        $validCustomers = $this->getValidCustomers();
        $validMachines = $this->getValidMachines();

        return [
            'document_number' => ['required', 'string', Rule::unique(InspectionReport::class, 'document_number')],
            'customer' => ['required', 'string', Rule::in($validCustomers)],
            'inspection_date' => 'required|date',
            'part_number' => ['required', 'string', Rule::exists(MasterListItem::class, 'item_code')],
            'part_name' => 'required|string',
            'weight' => 'required|numeric|min:0',
            'weight_uom' => 'required|string',
            'material' => 'required|string',
            'color' => 'required|string',
            'tool_number_or_cav_number' => 'required|string',
            'machine_number' => ['required', 'string', Rule::in($validMachines)],
            'shift' => 'required|integer|min:1|max:3',
            'operator' => 'required|string',
            'inspector' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'customer.in' => 'Customer wajib dipilih dari opsi Master Customer resmi yang terdaftar.',
            'customer.required' => 'Customer wajib dipilih dari daftar master customer.',
            'machine_number.in' => 'Nomor Mesin wajib dipilih dari daftar Mesin / Operator yang terdaftar.',
            'machine_number.required' => 'Nomor Mesin wajib dipilih.',
            'part_number.exists' => 'Part Number wajib dipilih melalui fitur pencarian Master Part.',
            'part_number.required' => 'Part Number wajib dipilih melalui fitur pencarian Master Part.',
            'part_name.required' => 'Part Name wajib terisi dari data master part.',
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
        $timezone = config('mes.timezone') ?: 'Asia/Jakarta';
        $now = now()->setTimezone($timezone);
        $currentTime = $now->format('H:i');

        $shifts = config('mes.shifts', []);

        foreach ($shifts as $shiftId => $schedule) {
            $start = $schedule['start'] ?? null;
            $end = $schedule['end'] ?? null;

            if (! $start || ! $end) {
                continue;
            }

            if ($start > $end) {
                // Crosses midnight (e.g. 23:30 to 07:30)
                if ($currentTime >= $start || $currentTime < $end) {
                    return (int) $shiftId;
                }
            } else {
                if ($currentTime >= $start && $currentTime < $end) {
                    return (int) $shiftId;
                }
            }
        }

        return 1;
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

        $timezone = config('mes.timezone') ?: 'Asia/Jakarta';
        if (! $this->inspection_date) {
            $this->inspection_date = now()->setTimezone($timezone)->format('Y-m-d');
        }

        if (! $this->shift) {
            $this->shift = $this->determineCurrentShift();
        }

        if (! empty($this->inspector)) {
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

        if (! empty($this->customer)) {
            $this->customer_search = $this->customer;
        }
    }

    public function updated($field): void
    {
        if ($field === 'customer_search') {
            $this->show_customer_dropdown = true;
            $matched = collect($this->getValidCustomers())->first(fn ($c) => strcasecmp($c, trim($this->customer_search)) === 0);
            $this->customer = $matched ?: null;
        }

        if ($field === 'operator' && is_string($this->operator)) {
            $this->operator = strtoupper(trim($this->operator));
        }

        if ($field === 'inspector' && is_string($this->inspector)) {
            $this->inspector = strtoupper(trim($this->inspector));
        }

        if ($field === 'machine_search') {
            $this->show_machine_dropdown = true;
            $matched = collect($this->getValidMachines())->first(fn ($m) => strcasecmp($m, trim($this->machine_search)) === 0);
            $this->machine_number = $matched ? strtoupper($matched) : null;
        }

        if ($field === 'part_search') {
            $this->show_part_dropdown = ! empty(trim($this->part_search));
        }

        $this->validateOnly($field);
    }

    public function selectPartItem(string $itemCode): void
    {
        $item = MasterListItem::with(['customer', 'businessPartner'])
            ->where('item_code', $itemCode)
            ->first();

        if ($item) {
            $this->part_number = $item->item_code;
            $this->part_name = $item->item_name;
            $this->part_search = '';

            $cust = $item->customer?->customer_name ?: ($item->businessPartner?->bp_name ?: null);
            if ($cust) {
                $this->customer = $cust;
                $this->customer_search = $cust;
            }

            if (! empty($item->tipe_mesin)) {
                $this->machine_number = strtoupper(trim((string) $item->tipe_mesin));
                $this->machine_search = $this->machine_number;
            }

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
            $this->isSearchingAgain = false;
            $this->resetValidation(['part_number', 'part_name', 'customer', 'machine_number']);
        }
    }

    public function startSearchAgain(): void
    {
        $this->isSearchingAgain = true;
        $this->part_search = '';
        $this->show_part_dropdown = false;
    }

    public function cancelSearchAgain(): void
    {
        $this->isSearchingAgain = false;
        $this->part_search = '';
        $this->show_part_dropdown = false;
    }

    public function clearPartSelection(): void
    {
        $this->part_number = null;
        $this->part_name = null;
        $this->part_search = '';
        $this->isSearchingAgain = false;
        $this->show_part_dropdown = false;
    }

    public function closePartDropdown(): void
    {
        $this->show_part_dropdown = false;
    }

    public function selectCustomer(string $name): void
    {
        $matched = collect($this->getValidCustomers())->first(fn ($c) => strcasecmp($c, $name) === 0);
        if ($matched) {
            $this->customer = $matched;
            $this->customer_search = $matched;
        }
        $this->show_customer_dropdown = false;
        $this->validateOnly('customer');
    }

    public function closeCustomerDropdown(): void
    {
        $this->show_customer_dropdown = false;
    }

    public function selectMachine(string $machineName): void
    {
        $matched = collect($this->getValidMachines())->first(fn ($m) => strcasecmp($m, $machineName) === 0);
        if ($matched) {
            $this->machine_number = strtoupper($matched);
            $this->machine_search = $this->machine_number;
        }
        $this->show_machine_dropdown = false;
        $this->validateOnly('machine_number');
    }

    public function closeMachineDropdown(): void
    {
        $this->show_machine_dropdown = false;
    }

    public function saveStep(): void
    {
        if (! empty($this->customer_search) && empty($this->customer)) {
            $matched = collect($this->getValidCustomers())->first(fn ($c) => strcasecmp($c, trim($this->customer_search)) === 0);
            if ($matched) {
                $this->customer = $matched;
            }
        }
        if (! empty($this->machine_search) && empty($this->machine_number)) {
            $matched = collect($this->getValidMachines())->first(fn ($m) => strcasecmp($m, trim($this->machine_search)) === 0);
            if ($matched) {
                $this->machine_number = strtoupper($matched);
            }
        }
        if (! empty($this->operator)) {
            $this->operator = strtoupper(trim($this->operator));
        }
        if (! empty($this->inspector)) {
            $this->inspector = strtoupper(trim($this->inspector));
        }

        $this->validate();

        $data = $this->snapshot();
        $timezone = config('mes.timezone') ?: 'Asia/Jakarta';

        session([
            'stepHeaderSaved' => $data,
            'stepHeaderSavedAt' => now()->setTimezone($timezone)->toIso8601String(),
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

        $distinctCustomers = $this->getValidCustomers();
        $filteredCustomers = $distinctCustomers;
        if (! empty(trim($this->customer_search))) {
            $searchUpper = strtoupper(trim($this->customer_search));
            $filteredCustomers = array_values(array_filter($distinctCustomers, fn ($c) => str_contains(strtoupper($c), $searchUpper)));
        }

        $machineList = $this->getValidMachines();
        $filteredMachines = $machineList;
        if (! empty(trim($this->machine_search))) {
            $searchUpper = strtoupper(trim($this->machine_search));
            $filteredMachines = array_values(array_filter($machineList, fn ($m) => str_contains($m, $searchUpper)));
        }

        // Inspector & Operator suggestions from distinct historical records
        $inspectorSuggestions = InspectionReport::query()
            ->whereNotNull('inspector')
            ->where('inspector', '!=', '')
            ->where('inspector', '!=', '-')
            ->distinct()
            ->orderBy('inspector')
            ->pluck('inspector')
            ->map(fn ($n) => strtoupper(trim((string) $n)))
            ->unique()
            ->values()
            ->toArray();

        $user = auth()->user();
        if ($user && ! empty($user->name)) {
            $userNameUpper = strtoupper(trim($user->name));
            if (! in_array($userNameUpper, $inspectorSuggestions, true)) {
                array_unshift($inspectorSuggestions, $userNameUpper);
            }
        }

        $operatorSuggestions = InspectionReport::query()
            ->whereNotNull('operator')
            ->where('operator', '!=', '')
            ->where('operator', '!=', '-')
            ->distinct()
            ->orderBy('operator')
            ->pluck('operator')
            ->map(fn ($n) => strtoupper(trim((string) $n)))
            ->unique()
            ->values()
            ->toArray();

        $shiftsConfig = config('mes.shifts', []);

        return view('livewire.inspection.form.step-header', [
            'customerOptions' => $filteredCustomers,
            'searchResults' => $searchResults,
            'machineOptions' => $filteredMachines,
            'inspectorSuggestions' => $inspectorSuggestions,
            'operatorSuggestions' => $operatorSuggestions,
            'shiftsConfig' => $shiftsConfig,
        ]);
    }
}
