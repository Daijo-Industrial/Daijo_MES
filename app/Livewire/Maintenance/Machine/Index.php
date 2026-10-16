<?php

namespace App\Livewire\Maintenance\Machine;

use App\Models\MaintenanceMachine;
use App\Models\User;
use Livewire\Component;

class Index extends Component
{
    public $maintenanceMachines;

    public $users;

    // Form input untuk create
    public $tanggal;

    public $mesin;

    public $jenis_kerusakan;

    public $perbaikan;

    public $pic;

    public $remark;

    public $tipe;

    // Form input untuk edit/detail
    public $editTanggal;

    public $editMesin;

    public $editJenisKerusakan;

    public $editPerbaikan;

    public $editPic;

    public $editRemark;

    public $editTipe;

    public $editStatus;

    public $showModal = false;

    public $selectedMaintenance;

    public $showDetailModal = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->maintenanceMachines = MaintenanceMachine::latest()->get();
        $this->users = User::where('role_id', 4)->get();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->tanggal = null;
        $this->mesin = null;
        $this->jenis_kerusakan = null;
        $this->perbaikan = null;
        $this->pic = null;
        $this->remark = null;
        $this->tipe = null;
    }

    public function saveMaintenance()
    {
        $this->validate([
            'tanggal' => 'required|date',
            'mesin' => 'required',
            'jenis_kerusakan' => 'required|string',
            'perbaikan' => 'required|string',
            'pic' => 'required|string',
            'tipe' => 'required|in:Repair,Maintenance',
        ]);

        MaintenanceMachine::create([
            'tanggal' => $this->tanggal,
            'mesin' => $this->mesin, // Simpan name langsung
            'jenis_kerusakan' => $this->jenis_kerusakan,
            'perbaikan' => $this->perbaikan,
            'pic' => $this->pic,
            'remark' => $this->remark,
            'tipe' => $this->tipe,
        ]);

        $this->closeModal();
        $this->loadData();
        session()->flash('message', 'Maintenance/Repair berhasil ditambahkan.');
    }

    public function openDetailModal($id)
    {
        $this->selectedMaintenance = MaintenanceMachine::find($id);

        if ($this->selectedMaintenance) {
            // Populate form dengan data yang ada
            $this->editTanggal = $this->selectedMaintenance->tanggal->format('Y-m-d');
            $this->editMesin = $this->selectedMaintenance->mesin; // Langsung ambil name
            $this->editJenisKerusakan = $this->selectedMaintenance->jenis_kerusakan;
            $this->editPerbaikan = $this->selectedMaintenance->perbaikan;
            $this->editPic = $this->selectedMaintenance->pic;
            $this->editRemark = $this->selectedMaintenance->remark;
            $this->editTipe = $this->selectedMaintenance->tipe;
            $this->editStatus = $this->selectedMaintenance->status;

            $this->showDetailModal = true;
        }
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedMaintenance = null;
        $this->resetEditForm();
    }

    public function resetEditForm()
    {
        $this->editTanggal = null;
        $this->editMesin = null;
        $this->editJenisKerusakan = null;
        $this->editPerbaikan = null;
        $this->editPic = null;
        $this->editRemark = null;
        $this->editTipe = null;
        $this->editStatus = null;
    }

    public function updateMaintenance()
    {
        $this->validate([
            'editTanggal' => 'required|date',
            'editMesin' => 'required',
            'editJenisKerusakan' => 'required|string',
            'editPerbaikan' => 'required|string',
            'editPic' => 'required|string',
            'editTipe' => 'required|in:Repair,Maintenance',
        ]);

        if ($this->selectedMaintenance) {
            $this->selectedMaintenance->update([
                'tanggal' => $this->editTanggal,
                'mesin' => $this->editMesin, // Simpan name
                'jenis_kerusakan' => $this->editJenisKerusakan,
                'perbaikan' => $this->editPerbaikan,
                'pic' => $this->editPic,
                'remark' => $this->editRemark,
                'tipe' => $this->editTipe,
            ]);

            $this->closeDetailModal();
            $this->loadData();
            session()->flash('message', 'Data maintenance berhasil diperbarui.');
        }
    }

    public function finishMaintenance()
    {
        $this->validate([
            'editTanggal' => 'required|date',
            'editMesin' => 'required',
            'editJenisKerusakan' => 'required|string',
            'editPerbaikan' => 'required|string',
            'editPic' => 'required|string',
            'editTipe' => 'required|in:Repair,Maintenance',
        ]);

        if ($this->selectedMaintenance) {
            // Update semua field terlebih dahulu
            $this->selectedMaintenance->tanggal = $this->editTanggal;
            $this->selectedMaintenance->mesin = $this->editMesin; // Simpan name
            $this->selectedMaintenance->jenis_kerusakan = $this->editJenisKerusakan;
            $this->selectedMaintenance->perbaikan = $this->editPerbaikan;
            $this->selectedMaintenance->pic = $this->editPic;
            $this->selectedMaintenance->remark = $this->editRemark;
            $this->selectedMaintenance->tipe = $this->editTipe;

            // Set finished time dan hitung lama pengerjaan
            $this->selectedMaintenance->finished_at = now();

            $created = $this->selectedMaintenance->created_at;
            $finished = $this->selectedMaintenance->finished_at;
            $diff = $finished->diff($created);

            // Hitung total dalam format yang lebih lengkap
            if ($diff->days > 0) {
                $this->selectedMaintenance->lama_pengerjaan = $diff->days . 'd ' . $diff->h . 'h ' . $diff->i . 'm';
            } else {
                $this->selectedMaintenance->lama_pengerjaan = $diff->h . 'h ' . $diff->i . 'm';
            }

            $this->selectedMaintenance->status = 1;
            $this->selectedMaintenance->save();

            $this->closeDetailModal();
            $this->loadData();
            session()->flash('message', 'Maintenance selesai dan data berhasil diperbarui.');
        }
    }

    public function render()
    {
        return view('livewire.maintenance.machine.index');
    }
}
