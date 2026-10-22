<?php

namespace App\Livewire\Asakai;

use App\Models\Asakai;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class AsakaiForm extends Component
{
    public $asakaiId = null;

    public $isEdit = false;

    public $isClosed = false;

    // Main Fields
    #[Validate('required|string')]
    public $customer;

    #[Validate('required|string')]
    public $part_no;

    #[Validate('required|string')]
    public $issue;

    #[Validate('required|integer|min:1')]
    public $quantity;

    #[Validate('required|in:Shift 1,Shift 2,Shift 3')]
    public $lot_shift;

    #[Validate('required|date')]
    public $lot_date;

    #[Validate('required|date')]
    public $date_issue;

    // PICs (Dynamic Array)
    public $pics = [''];

    // RCAs (Fixed 5, min 3 required)
    public $rcas = [
        ['why_level' => 1, 'description' => ''],
        ['why_level' => 2, 'description' => ''],
        ['why_level' => 3, 'description' => ''],
        ['why_level' => 4, 'description' => ''],
        ['why_level' => 5, 'description' => ''],
    ];

    // Corrective Actions (Dynamic 1-5)
    public $corrective_actions = [''];

    // Other Fields
    public $pokayoke;

    public $audit_date;

    public $reply_date;

    public $verify;

    public $fmea_cp;

    public $std_work;

    public $remark;

    public function mount($id = null)
    {
        if ($id) {
            $this->loadAsakai($id);

            if ($this->isClosed) {
                session()->flash('error', 'Data tidak dapat diedit karena sudah berstatus CLOSED!');

                return redirect()->route('asakai.detail', $id);
            }
        }
    }

    public function loadAsakai($id)
    {
        $asakai = Asakai::with(['pics', 'rcas', 'correctiveActions'])->findOrFail($id);

        $this->asakaiId = $asakai->id;
        $this->isEdit = true;
        $this->isClosed = $asakai->status === 'closed';

        // Fill main data
        $this->customer = $asakai->customer;
        $this->part_no = $asakai->part_no;
        $this->issue = $asakai->issue;
        $this->quantity = $asakai->quantity;
        $this->lot_shift = $asakai->lot_shift;

        // FIX: Format tanggal untuk input type="date" (Y-m-d)
        $this->lot_date = $asakai->lot_date ? $asakai->lot_date->format('Y-m-d') : null;
        $this->date_issue = $asakai->date_issue ? $asakai->date_issue->format('Y-m-d') : null;
        $this->audit_date = $asakai->audit_date ? $asakai->audit_date->format('Y-m-d') : null;
        $this->reply_date = $asakai->reply_date ? $asakai->reply_date->format('Y-m-d') : null;

        $this->pokayoke = $asakai->pokayoke;
        $this->verify = $asakai->verify;
        $this->fmea_cp = $asakai->fmea_cp;
        $this->std_work = $asakai->std_work;
        $this->remark = $asakai->remark;

        // Fill PICs
        $this->pics = $asakai->pics->count() > 0
            ? $asakai->pics->pluck('pic_name')->toArray()
            : [''];

        // Fill RCAs
        foreach ($asakai->rcas as $rca) {
            $this->rcas[$rca->why_level - 1]['description'] = $rca->description;
        }

        // Fill Corrective Actions
        $this->corrective_actions = $asakai->correctiveActions->count() > 0
            ? $asakai->correctiveActions->pluck('action')->toArray()
            : [''];
    }

    // PIC Methods
    public function addPic()
    {
        $this->pics[] = '';
    }

    public function removePic($index)
    {
        if (count($this->pics) > 1) {
            unset($this->pics[$index]);
            $this->pics = array_values($this->pics);
        }
    }

    // Corrective Action Methods
    public function addCorrectiveAction()
    {
        if (count($this->corrective_actions) < 5) {
            $this->corrective_actions[] = '';
        }
    }

    public function removeCorrectiveAction($index)
    {
        if (count($this->corrective_actions) > 1) {
            unset($this->corrective_actions[$index]);
            $this->corrective_actions = array_values($this->corrective_actions);
        }
    }

    public function save()
    {
        if ($this->isClosed) {
            session()->flash('error', 'Data tidak dapat diubah karena sudah berstatus CLOSED!');

            return;
        }

        $this->validate();

        // Custom Validation
        $this->validateRcas();
        $this->validatePics();
        $this->validateCorrectiveActions();

        \DB::beginTransaction();
        try {
            // FIX: Ambil existing asakai dulu kalau edit
            $existingAsakai = $this->asakaiId ? Asakai::find($this->asakaiId) : null;

            $asakai = Asakai::updateOrCreate(
                ['id' => $this->asakaiId],
                [
                    'customer' => $this->customer,
                    'part_no' => $this->part_no,
                    'issue' => $this->issue,
                    'quantity' => $this->quantity,
                    'lot_shift' => $this->lot_shift,
                    'lot_date' => $this->lot_date,
                    'date_issue' => $this->date_issue,
                    'pokayoke' => $this->pokayoke,
                    'audit_date' => $this->audit_date,
                    'reply_date' => $this->reply_date,
                    'verify' => $this->verify,
                    'fmea_cp' => $this->fmea_cp,
                    'std_work' => $this->std_work,
                    'remark' => $this->remark,
                    // FIX: Logic created_by & updated_by
                    'created_by' => $existingAsakai ? $existingAsakai->created_by : 1,
                    'updated_by' => 1,
                ]
            );

            // Save PICs
            $asakai->pics()->delete();
            foreach (array_filter($this->pics) as $index => $pic) {
                $asakai->pics()->create([
                    'pic_name' => $pic,
                    'order_no' => $index + 1,
                ]);
            }

            // Save RCAs
            $asakai->rcas()->delete();
            foreach ($this->rcas as $rca) {
                if (! empty($rca['description'])) {
                    $asakai->rcas()->create($rca);
                }
            }

            // Save Corrective Actions
            $asakai->correctiveActions()->delete();
            foreach (array_filter($this->corrective_actions) as $index => $action) {
                $asakai->correctiveActions()->create([
                    'action' => $action,
                    'order_no' => $index + 1,
                ]);
            }

            \DB::commit();

            session()->flash('success', 'Asakai berhasil disimpan!');

            return redirect()->route('asakai.index');

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    protected function validateRcas()
    {
        $filledRcas = array_filter($this->rcas, fn ($rca) => ! empty($rca['description']));

        if (count($filledRcas) < 3) {
            $this->addError('rcas', 'Minimal 3 Why harus diisi untuk RCA');
            throw new \Illuminate\Validation\ValidationException(
                validator([], [])
            );
        }
    }

    protected function validatePics()
    {
        $filledPics = array_filter($this->pics);

        if (count($filledPics) < 1) {
            $this->addError('pics', 'Minimal 1 PIC harus diisi');
            throw new \Illuminate\Validation\ValidationException(
                validator([], [])
            );
        }
    }

    protected function validateCorrectiveActions()
    {
        $filledActions = array_filter($this->corrective_actions);

        if (count($filledActions) < 1) {
            $this->addError('corrective_actions', 'Minimal 1 Corrective Action harus diisi');
            throw new \Illuminate\Validation\ValidationException(
                validator([], [])
            );
        }
    }

    public function render()
    {
        return view('livewire.asakai.asakai-form');
    }
}
