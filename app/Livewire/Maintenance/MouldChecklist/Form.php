<?php

namespace App\Livewire\Maintenance\MouldChecklist;

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
    public $mould_search = '';
    public $mould_id = null;
    public $mould_no = '';
    public $part_no = '';
    public $part_name = '';
    public $model = '';
    public $customer = '';
    public $machine_number = '';
    public $technician_name = '';
    public $start_time = '';
    public $end_time = '';
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
        return [
            'check_date' => 'required|date',
            'mould_no' => 'required|string|max:100',
            'part_no' => 'required|string|max:100',
            'part_name' => 'required|string|max:255',
            'customer' => 'required|string|max:150',
            'technician_name' => 'required|string|max:255',
            'machine_number' => 'nullable|string|max:100',
            'start_time' => 'nullable|string|max:10',
            'end_time' => 'nullable|string|max:10',
            'items.*.cleaning_status' => 'required|in:OK,NG,-',
            'items.*.lubricate_status' => 'required|in:OK,NG,-',
            'items.*.remark' => 'nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'mould_no.required' => 'Silakan pilih mould dari Master Mould atau gunakan mode Input Manual.',
            'part_no.required' => 'Part number wajib diisi.',
            'part_name.required' => 'Part name wajib diisi.',
            'customer.required' => 'Customer wajib diisi.',
            'technician_name.required' => 'Nama teknisi (PIC) wajib diisi.',
            'check_date.required' => 'Tanggal pengerjaan wajib diisi.',
            'items.*.cleaning_status.required' => 'Status cleaning wajib dipilih.',
            'items.*.lubricate_status.required' => 'Status lubricate wajib dipilih.',
        ];
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
            $this->check_date = $checklist->check_date ? $checklist->check_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $this->mould_id = $checklist->mould_id;
            $this->mould_no = $checklist->mould_no;
            $this->part_no = $checklist->part_no;
            $this->part_name = $checklist->part_name;
            $this->model = $checklist->model;
            $this->customer = $checklist->customer;
            $this->machine_number = $checklist->machine_number;
            $this->technician_name = $checklist->technician_name;
            $this->start_time = $checklist->start_time;
            $this->end_time = $checklist->end_time;
            $this->doc_number = $checklist->doc_number;
            $this->revision = $checklist->revision;

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
            $this->check_date = Carbon::now('Asia/Jakarta')->subHours(7)->subMinutes(30)->format('Y-m-d');
            $this->technician_name = auth()->user()?->name ?: '';
            $this->start_time = Carbon::now('Asia/Jakarta')->format('H:i');
            $this->end_time = Carbon::now('Asia/Jakarta')->addHours(2)->format('H:i');

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
            $this->validate([
                'check_date' => 'required|date',
                'mould_no' => 'required|string|max:100',
                'part_no' => 'required|string|max:100',
                'part_name' => 'required|string|max:255',
                'customer' => 'required|string|max:150',
                'technician_name' => 'required|string|max:255',
                'machine_number' => 'nullable|string|max:100',
                'start_time' => 'nullable|string|max:10',
                'end_time' => 'nullable|string|max:10',
            ], $this->messages());
        } elseif ($step === 2) {
            $this->validate([
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            $step1Fields = ['check_date', 'mould_no', 'part_no', 'part_name', 'customer', 'technician_name', 'machine_number', 'start_time', 'end_time'];
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

            $data = [
                'doc_number' => $this->doc_number,
                'revision' => $this->revision,
                'check_date' => $this->check_date,
                'mould_id' => $this->mould_id,
                'mould_no' => mb_strtoupper(trim($this->mould_no)),
                'part_no' => trim($this->part_no),
                'part_name' => trim($this->part_name),
                'model' => $this->model ? trim($this->model) : null,
                'customer' => mb_strtoupper(trim($this->customer)),
                'machine_number' => $this->machine_number ? trim($this->machine_number) : null,
                'technician_name' => trim($this->technician_name),
                'start_time' => $this->start_time ?: null,
                'end_time' => $this->end_time ?: null,
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

        $machines = User::where('role_id', 4)->orderBy('name')->pluck('name');

        return view('livewire.maintenance.mould-checklist.form', [
            'mouldSearchResults' => $mouldSearchResults,
            'machines' => $machines,
        ])->layout('layouts.app');
    }
}
