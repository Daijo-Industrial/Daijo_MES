<?php

namespace App\Livewire\Maintenance\MouldChecklist;

use App\Models\MasterMould;
use App\Models\MouldChecklist;
use App\Models\MouldChecklistDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Form extends Component
{
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

    // 16 Check items array: [item_no => ['point_check', 'cleaning_status', 'lubricate_status', 'remark']]
    public $items = [];

    // Dropdown / Search state
    public $showMouldDropdown = false;

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
        }
    }

    public function saveDraft()
    {
        $this->validate();

        $checklist = $this->persistChecklist('DRAFT');

        session()->flash('success', 'Check sheet berhasil disimpan sebagai DRAFT.');
        return redirect()->route('mould-checklist.show', $checklist->id);
    }

    public function submitAndSign()
    {
        $this->validate();

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

        $checklist = $this->persistChecklist('SUBMITTED', true);

        session()->flash('success', 'Check sheet berhasil di-submit dan ditandatangani oleh ' . $checklist->issued_name . '!');
        return redirect()->route('mould-checklist.show', $checklist->id);
    }

    private function persistChecklist(string $status, bool $signIssued = false): MouldChecklist
    {
        return DB::transaction(function () use ($status, $signIssued) {
            $user = auth()->user();

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
