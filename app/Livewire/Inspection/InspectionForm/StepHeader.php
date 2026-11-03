<?php

namespace App\Livewire\Inspection\InspectionForm;

use App\Models\Inspection\InspectionReport;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterListItem;
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

    public $part_name;

    public $weight;

    public $weight_uom = 'g';

    public $material;

    public $color;

    public $tool_number_or_cav_number;

    public $machine_number;

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
            'operator' => $this->operator,
            'inspector' => $this->inspector,
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
    }

    public function updated($field): void
    {
        $this->validateOnly($field);

        if ($field === 'part_number' && ! empty($this->part_number)) {
            $item = MasterListItem::where('item_code', $this->part_number)->first();
            if ($item) {
                if (empty($this->part_name)) {
                    $this->part_name = $item->item_name;
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
            }
        }
    }

    public function saveStep(): void
    {
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
        $customerSuggestions = MasterCustomerDelivery::query()
            ->select('customer_name')
            ->distinct()
            ->orderBy('customer_name')
            ->limit(30)
            ->pluck('customer_name')
            ->toArray();

        return view('livewire.inspection.form.step-header', [
            'customerSuggestions' => $customerSuggestions,
        ]);
    }
}
