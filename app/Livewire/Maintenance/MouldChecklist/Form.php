<?php

namespace App\Livewire\Maintenance\MouldChecklist;

use App\Models\MasterMachine;
use App\Models\MasterMould;
use App\Models\MouldChecklist;
use App\Models\MouldChecklistDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public $checklistId = null;
    public $isEdit = false;

    // Header Fields
    public $check_date;
    public $start_date = '';
    public $start_time = '';
    public $end_date = '';
    public $end_time = '';
    public $is_ongoing = false;
    public $mould_search = '';
    public $mould_id = null;
    public $mould_no = '';
    public $part_no = '';
    public $part_name = '';
    public $model = '';
    public $customer = '';
    public $machine_number = '';
    public $technician_name = '';
    public $doc_number = 'DI-F-P/PD/05/MTM-043';
    public $revision = '00';

    // Wizard Step Navigation
    public $currentStep = 1;
    public $totalSteps = 2;

    // 16 Check items array: [item_no => ['point_check', 'cleaning_status', 'lubricate_status', 'remark']]
    public $items = [];

    // Dropdown / Search state
    public $showMouldDropdown = false;
    public $isManualMould = false;
    public $auto_sign_check_by = false;

    protected function rules()
    {
        $isManual = (bool) $this->isManualMould;
        $isOngoing = (bool) $this->is_ongoing;

        return [
            'mould_id' => 'nullable|exists:master_moulds,id',
            'check_date' => 'required|date',
            'start_date' => 'required|date',
            'start_time' => 'required|string|max:10',
            'end_date' => $isOngoing ? 'nullable|date' : 'required|date|after_or_equal:start_date',
            'end_time' => $isOngoing ? 'nullable|string|max:10' : 'required|string|max:10',
            'is_ongoing' => 'boolean',
            'mould_no' => 'required|string|max:100',
            'part_no' => 'required|string|max:100',
            'part_name' => $isManual ? 'nullable|string|max:255' : 'required|string|max:255',
            'customer' => $isManual ? 'nullable|string|max:150' : 'required|string|max:150',
            'model' => 'nullable|string|max:100',
            'technician_name' => 'required|string|max:255',
            'machine_number' => 'nullable|string|max:100',
            'items.*.point_check' => 'required|string|max:255',
            'items.*.cleaning_status' => 'required|in:OK,NG,-',
            'items.*.lubricate_status' => 'required|in:OK,NG,-',
            'items.*.remark' => 'nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'mould_no.required' => 'Mould number wajib diisi.',
            'part_no.required' => 'Part number wajib diisi.',
            'part_name.required' => 'Part name wajib diisi.',
            'customer.required' => 'Customer wajib diisi.',
            'technician_name.required' => 'Nama teknisi (PIC) wajib diisi.',
            'check_date.required' => 'Tanggal pengerjaan wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi jika pekerjaan sudah selesai.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'end_time.required' => 'Jam selesai wajib diisi jika pekerjaan sudah selesai.',
            'items.*.cleaning_status.required' => 'Status cleaning wajib dipilih.',
            'items.*.lubricate_status.required' => 'Status lubricate wajib dipilih.',
        ];
    }

    public function updatedStartDate($value)
    {
        $this->check_date = $value;
        if (empty($this->end_date) || $this->end_date < $value) {
            $this->end_date = $value;
        }
        if (! empty($this->end_date) && $this->end_date >= $value) {
            $this->resetErrorBag('end_date');
        }
    }

    public function updatedEndDate($value)
    {
        if (! empty($value) && ! empty($this->start_date)) {
            if ($value < $this->start_date) {
                $this->addError('end_date', 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
            } else {
                $this->resetErrorBag('end_date');
            }
        }
    }

    public function updatedIsOngoing($value)
    {
        if ($value) {
            $this->resetErrorBag(['end_date', 'end_time']);
        }
    }

    public function mount($id = null)
    {
        if ($id) {
            $checklist = MouldChecklist::with('details')->findOrFail($id);

            // Guard: Only DRAFT or REJECTED can be edited
            if ($checklist->status !== 'DRAFT' && $checklist->status !== 'REJECTED' && ! (auth()->user()?->hasRole('SUPER-ADMIN') || auth()->user()?->hasRole('ADMIN'))) {
                session()->flash('error', 'Dokumen berstatus ' . $checklist->status . ' tidak dapat diedit.');
                return redirect()->route('mould-checklist.show', $checklist->id);
            }

            $this->checklistId = $checklist->id;
            $this->isEdit = true;
            $this->check_date = $checklist->check_date ? $checklist->check_date->format('Y-m-d') : Carbon::now('Asia/Jakarta')->format('Y-m-d');
            $this->is_ongoing = (bool) $checklist->is_ongoing;

            if ($checklist->start_at) {
                $this->start_date = $checklist->start_at->format('Y-m-d');
                $this->start_time = $checklist->start_at->format('H:i');
            } else {
                $this->start_date = $this->check_date;
                $this->start_time = $checklist->start_time ?: '';
            }

            if ($checklist->end_at) {
                $this->end_date = $checklist->end_at->format('Y-m-d');
                $this->end_time = $checklist->end_at->format('H:i');
            } else {
                $this->end_date = $this->start_date;
                $this->end_time = $checklist->end_time ?: '';
            }

            $this->mould_id = $checklist->mould_id;
            $this->mould_no = $checklist->mould_no;
            $this->part_no = $checklist->part_no;
            $this->part_name = $checklist->part_name;
            $this->model = $checklist->model;
            $this->customer = $checklist->customer;
            $this->machine_number = $checklist->machine_number;
            $this->technician_name = $checklist->technician_name;
            $this->doc_number = $checklist->doc_number;
            $this->revision = $checklist->revision;

            $this->isManualMould = empty($checklist->mould_id);
            if (! $this->isManualMould) {
                $this->mould_search = $checklist->mould_no . ' - ' . $checklist->part_no;
            }

            // Populate items from details
            $this->items = [];
            foreach ($checklist->details as $d) {
                $this->items[$d->item_no] = [
                    'item_no' => $d->item_no,
                    'point_check' => $d->point_check,
                    'cleaning_status' => $d->cleaning_status,
                    'lubricate_status' => $d->lubricate_status,
                    'remark' => $d->remark,
                ];
            }
        } else {
            // New checklist defaults
            $now = Carbon::now('Asia/Jakarta');
            $this->start_date = $now->format('Y-m-d');
            $this->check_date = $this->start_date;
            $this->start_time = $now->format('H:i');
            $this->end_date = $this->start_date;
            $this->end_time = $now->copy()->addHours(2)->format('H:i');
            $this->is_ongoing = false;
            $this->technician_name = auth()->user()?->name ?: '';

            if (auth()->user()?->hasRole('MOULD-MAINTENANCE-LEADER')) {
                $this->auto_sign_check_by = true;
            }

            // Initialize standard 16 items
            $this->initStandardItems();
        }
    }

    public function initStandardItems()
    {
        $this->items = [];
        foreach (MouldChecklist::STANDARD_CHECK_POINTS as $no => $item) {
            $this->items[$no] = [
                'item_no' => $no,
                'point_check' => $item['name'],
                'cleaning_status' => $item['cleaning'],
                'lubricate_status' => $item['lubricate'],
                'remark' => '',
            ];
        }
    }

    public function setAllApplicableToOk()
    {
        foreach ($this->items as $no => $item) {
            $standard = MouldChecklist::STANDARD_CHECK_POINTS[$no] ?? null;
            if ($standard) {
                $this->items[$no]['cleaning_status'] = $standard['cleaning'];
                $this->items[$no]['lubricate_status'] = $standard['lubricate'];
            }
        }
        session()->flash('info', 'Semua poin checklist disetel ke standar normal!');
    }

    public function selectMould($id)
    {
        $mould = MasterMould::find($id);
        if ($mould) {
            $this->mould_id = $mould->id;
            $this->mould_no = $mould->mould_no;
            $this->part_no = $mould->part_no;
            $this->part_name = $mould->part_name;
            $this->customer = $mould->customer;
            $this->model = $mould->model;
            if ($mould->machine_number && empty($this->machine_number)) {
                $this->machine_number = $mould->machine_number;
            }
            $this->mould_search = $mould->mould_no . ' - ' . $mould->part_no;
            $this->showMouldDropdown = false;
            $this->isManualMould = false;
        }
    }

    public function clearSelectedMould()
    {
        $this->mould_id = null;
        $this->mould_no = '';
        $this->part_no = '';
        $this->part_name = '';
        $this->model = '';
        $this->customer = '';
        $this->mould_search = '';
        $this->isManualMould = false;
        $this->showMouldDropdown = false;
    }

    public function enableManualMould()
    {
        $this->mould_id = null;
        $this->isManualMould = true;
        $this->showMouldDropdown = false;
        $this->mould_search = '';
    }

    public function disableManualMould()
    {
        $this->clearSelectedMould();
    }

    public function validateStep(int $step)
    {
        if ($step === 1) {
            $isManual = (bool) $this->isManualMould;
            $isOngoing = (bool) $this->is_ongoing;

            $rules = [
                'check_date' => 'required|date',
                'start_date' => 'required|date',
                'start_time' => 'required|string|max:10',
                'end_date' => $isOngoing ? 'nullable|date' : 'required|date|after_or_equal:start_date',
                'end_time' => $isOngoing ? 'nullable|string|max:10' : 'required|string|max:10',
                'mould_no' => 'required|string|max:100',
                'part_no' => 'required|string|max:100',
                'part_name' => $isManual ? 'nullable|string|max:255' : 'required|string|max:255',
                'customer' => $isManual ? 'nullable|string|max:150' : 'required|string|max:150',
                'model' => 'nullable|string|max:100',
                'technician_name' => 'required|string|max:255',
                'machine_number' => 'nullable|string|max:100',
            ];

            $this->validate($rules, $this->messages());

            if (! $isOngoing && $this->start_date && $this->end_date && $this->start_date === $this->end_date) {
                if ($this->start_time && $this->end_time && $this->end_time <= $this->start_time) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'end_time' => 'Jam selesai harus setelah jam mulai untuk tanggal pengerjaan yang sama.'
                    ]);
                }
            }
        } elseif ($step === 2) {
            $this->validate([
                'items.*.point_check' => 'required|string|max:255',
                'items.*.cleaning_status' => 'required|in:OK,NG,-',
                'items.*.lubricate_status' => 'required|in:OK,NG,-',
                'items.*.remark' => 'nullable|string|max:255',
            ], $this->messages());
        }
    }

    public function nextStep()
    {
        $this->validateStep($this->currentStep);
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function setStep($step)
    {
        $step = (int) $step;
        if ($step < 1 || $step > $this->totalSteps) {
            return;
        }

        // Validate all preceding steps when moving to a later step
        if ($step > 1) {
            for ($i = 1; $i < $step; $i++) {
                $this->validateStep($i);
            }
        }

        $this->currentStep = $step;
    }

    public function getNgItemsProperty()
    {
        return collect($this->items)->filter(function ($item) {
            return ($item['cleaning_status'] ?? '') === 'NG' || ($item['lubricate_status'] ?? '') === 'NG';
        });
    }

    public function getNgCountProperty(): int
    {
        return $this->ngItems->count();
    }

    public function getOkCountProperty(): int
    {
        return collect($this->items)->filter(function ($item) {
            return ($item['cleaning_status'] ?? '') === 'OK' && ($item['lubricate_status'] ?? '') === 'OK';
        })->count();
    }

    public function getNaCountProperty(): int
    {
        return collect($this->items)->filter(function ($item) {
            return ($item['cleaning_status'] ?? '') === '-' && ($item['lubricate_status'] ?? '') === '-';
        })->count();
    }

    protected function validateWithStepRouting()
    {
        try {
            $this->validate();

            if (! $this->is_ongoing && $this->start_date && $this->end_date && $this->start_date === $this->end_date) {
                if ($this->start_time && $this->end_time && $this->end_time <= $this->start_time) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'end_time' => 'Jam selesai harus setelah jam mulai untuk tanggal pengerjaan yang sama.'
                    ]);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $step1Fields = [
                'check_date', 'start_date', 'start_time', 'end_date', 'end_time',
                'mould_no', 'part_no', 'part_name', 'customer', 'technician_name', 'machine_number'
            ];
            foreach ($step1Fields as $f) {
                if ($e->validator->errors()->has($f)) {
                    $this->currentStep = 1;
                    throw $e;
                }
            }
            if ($e->validator->errors()->has('items.*')) {
                $this->currentStep = 2;
            }
            throw $e;
        }
    }

    public function saveDraft()
    {
        $this->validateWithStepRouting();

        $checklist = $this->persistChecklist('DRAFT');

        session()->flash('success', 'Check sheet berhasil disimpan sebagai DRAFT.');
        return redirect()->route('mould-checklist.show', $checklist->id);
    }

    public function submitAndSign()
    {
        if ($this->is_ongoing) {
            session()->flash('error', 'Pekerjaan yang masih berlangsung (Ongoing) hanya dapat disimpan sebagai Draft. Harap selesaikan pekerjaan dan tentukan waktu selesai sebelum submit & tanda tangan.');
            return;
        }

        $this->validateWithStepRouting();

        $user = auth()->user();
        if (! $user) {
            session()->flash('error', 'Silakan login terlebih dahulu.');
            return;
        }

        // Check permission to sign issued_by
        if (! $user->canSign('mould_checklist', 'issued_by')) {
            session()->flash('error', 'Anda tidak memiliki wewenang untuk submit dan tanda tangan Issued By.');
            return;
        }

        $autoSignCheckBy = $this->auto_sign_check_by && $user->hasRole('MOULD-MAINTENANCE-LEADER');
        $status = $autoSignCheckBy ? 'APPROVED' : 'SUBMITTED';

        $checklist = $this->persistChecklist($status, true, $autoSignCheckBy);

        if ($autoSignCheckBy) {
            session()->flash('success', 'Check sheet berhasil di-submit dan disetujui (APPROVED) otomatis oleh Leader ' . $checklist->checked_name . '!');
        } else {
            session()->flash('success', 'Check sheet berhasil di-submit dan ditandatangani oleh ' . $checklist->issued_name . '!');
        }

        return redirect()->route('mould-checklist.show', $checklist->id);
    }

    private function persistChecklist(string $status, bool $signIssued = false, bool $signChecked = false): MouldChecklist
    {
        return DB::transaction(function () use ($status, $signIssued, $signChecked) {
            $user = auth()->user();

            if ($this->checklistId) {
                $existing = MouldChecklist::find($this->checklistId);
                if ($existing && ! in_array($existing->status, ['DRAFT', 'REJECTED']) && ! ($user?->hasRole('SUPER-ADMIN') || $user?->hasRole('ADMIN'))) {
                    throw new \RuntimeException("Dokumen sudah berstatus {$existing->status} dan tidak dapat diubah lagi.");
                }
            }

            $startDate = $this->start_date ?: $this->check_date;
            $startAt = null;
            if ($startDate && $this->start_time) {
                try {
                    $startAt = Carbon::parse($startDate . ' ' . $this->start_time, 'Asia/Jakarta');
                } catch (\Throwable $e) {
                    $startAt = null;
                }
            }

            $endAt = null;
            $endTime = null;
            if (! $this->is_ongoing) {
                $endDate = $this->end_date ?: $startDate;
                if ($endDate && $this->end_time) {
                    try {
                        $endAt = Carbon::parse($endDate . ' ' . $this->end_time, 'Asia/Jakarta');
                        $endTime = $this->end_time;
                    } catch (\Throwable $e) {
                        $endAt = null;
                    }
                }
            }

            // Normalization & fallback for customer
            $customerVal = trim($this->customer);
            if (empty($customerVal) || in_array(strtolower($customerVal), ['-', '0', 'n/a', 'na'])) {
                $customerVal = 'N/A';
            }

            $partNameVal = trim($this->part_name);
            if ($this->isManualMould && empty($partNameVal)) {
                $partNameVal = trim($this->part_no) ?: 'N/A';
            }

            $data = [
                'doc_number' => $this->doc_number,
                'revision' => $this->revision,
                'check_date' => $startDate,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'is_ongoing' => (bool) $this->is_ongoing,
                'mould_id' => $this->isManualMould ? null : $this->mould_id,
                'mould_no' => mb_strtoupper(trim($this->mould_no)),
                'part_no' => trim($this->part_no),
                'part_name' => $partNameVal,
                'model' => $this->model ? trim($this->model) : null,
                'customer' => mb_strtoupper($customerVal),
                'machine_number' => $this->machine_number ? trim($this->machine_number) : null,
                'technician_name' => trim($this->technician_name),
                'start_time' => $this->start_time ?: null,
                'end_time' => $endTime,
                'status' => $status,
            ];

            if ($signIssued) {
                $data['issued_by_id'] = $user->id;
                $data['issued_name'] = $user->name;
                $data['issued_at'] = now();
            }

            if ($signChecked) {
                $data['checked_by_id'] = $user->id;
                $data['checked_name'] = $user->name;
                $data['checked_at'] = now();
                $data['status'] = 'APPROVED';
            }

            if (! $this->isEdit) {
                $data['created_by'] = $user?->id;
            }

            $checklist = MouldChecklist::updateOrCreate(
                ['id' => $this->checklistId],
                $data
            );

            // Persist detail items
            foreach ($this->items as $no => $item) {
                MouldChecklistDetail::updateOrCreate(
                    [
                        'mould_checklist_id' => $checklist->id,
                        'item_no' => $no,
                    ],
                    [
                        'point_check' => $item['point_check'],
                        'cleaning_status' => $item['cleaning_status'],
                        'lubricate_status' => $item['lubricate_status'],
                        'remark' => $item['remark'] ?: null,
                    ]
                );
            }

            return $checklist;
        });
    }

    public function render()
    {
        $mouldSearchResults = collect();
        if (strlen($this->mould_search) >= 2) {
            $term = '%' . $this->mould_search . '%';
            $mouldSearchResults = MasterMould::where('mould_no', 'LIKE', $term)
                ->orWhere('part_no', 'LIKE', $term)
                ->orWhere('part_name', 'LIKE', $term)
                ->limit(10)
                ->get();
        }

        $machines = MasterMachine::active()->orderBy('sort_order')->orderBy('name')->pluck('name');
        if ($machines->isEmpty()) {
            $machines = User::where('role_id', 4)->orderBy('name')->pluck('name');
        }
        $machines = $machines->filter()->values();

        return view('livewire.maintenance.mould-checklist.form', [
            'mouldSearchResults' => $mouldSearchResults,
            'machines' => $machines,
        ])->layout('layouts.app');
    }
}
