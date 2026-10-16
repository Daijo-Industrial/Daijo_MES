<?php

namespace App\Livewire\Maintenance\Mould;

use App\Models\MaintenanceMould;
use Livewire\Component;

class Index extends Component
{
    public $maintenanceMoulds;

    // Form input
    public $tanggal;

    public $part_no;

    public $part_name;

    public $jenis_kerusakan;

    public $perbaikan;

    public $remark;

    public $tipe;

    public $pic; // <--- tambahin ini

    public $remarkInput;

    public $showModal = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->maintenanceMoulds = MaintenanceMould::latest()->get();
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
        $this->part_no = null;
        $this->part_name = null;
        $this->jenis_kerusakan = null;
        $this->perbaikan = null;
        $this->remark = null;
        $this->tipe = null;
        $this->pic = null; // reset juga
    }

    public function saveMaintenance()
    {
        $this->validate([
            'tanggal' => 'required|date',
            'part_no' => 'required|string',
            'part_name' => 'required|string',
            'jenis_kerusakan' => 'required|string',
            'perbaikan' => 'required|string',
            'pic' => 'required|string',
            'tipe' => 'required|in:Overhaul,Repair',
        ]);

        MaintenanceMould::create([
            'tanggal' => $this->tanggal,
            'part_no' => $this->part_no,
            'part_name' => $this->part_name,
            'jenis_kerusakan' => $this->jenis_kerusakan,
            'perbaikan' => $this->perbaikan,
            'pic' => $this->pic,
            'remark' => $this->remark,
            'tipe' => $this->tipe,
        ]);

        $this->closeModal();
        $this->loadData();
        session()->flash('message', 'Maintenance/Repair mould berhasil ditambahkan.');
    }

    // Detail Modal
    public $selectedMould;

    public $showDetailModal = false;

    public function openDetailModal($id)
    {
        $this->selectedMould = MaintenanceMould::find($id);
        $this->remarkInput = $this->selectedMould->remark;
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedMould = null;
    }

    public function finishMaintenance()
    {
        if ($this->selectedMould) {
            $this->selectedMould->remark = $this->remarkInput;
            $this->selectedMould->finished_at = now();
            $created = $this->selectedMould->created_at;
            $finished = $this->selectedMould->finished_at;
            $diff = $finished->diff($created);
            $this->selectedMould->lama_pengerjaan = $diff->h . 'h ' . $diff->i . 'm';
            $this->selectedMould->status = 1;
            $this->selectedMould->save();

            $this->closeDetailModal();
            $this->loadData();
            session()->flash('message', 'Maintenance mould selesai dan lama pengerjaan dihitung.');
        }
    }

    public function render()
    {
        return view('livewire.maintenance.mould.index');
    }
}
