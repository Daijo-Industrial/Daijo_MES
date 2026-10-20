<?php

namespace App\Livewire\Maintenance\MouldChecklist;

use App\Models\MouldChecklist;
use Livewire\Component;

class Show extends Component
{
    public $checklistId;
    public $showRejectModal = false;
    public $rejectionNote = '';

    public function mount($id)
    {
        $this->checklistId = $id;
    }

    public function getChecklistProperty()
    {
        return MouldChecklist::with(['details', 'issuedBy', 'checkedBy'])->findOrFail($this->checklistId);
    }

    public function approveAndSign()
    {
        $user = auth()->user();
        if (! $user) {
            session()->flash('error', 'Silakan login terlebih dahulu.');
            return;
        }

        if (! $user->canSign('mould_checklist', 'checked_by')) {
            session()->flash('error', 'Anda tidak memiliki wewenang untuk verifikasi dan tanda tangan Check By.');
            return;
        }

        $checklist = $this->checklist;
        if ($checklist->status !== 'SUBMITTED') {
            session()->flash('error', 'Hanya dokumen berstatus SUBMITTED yang dapat diapprove.');
            return;
        }

        $checklist->update([
            'status' => 'APPROVED',
            'checked_by_id' => $user->id,
            'checked_name' => $user->name,
            'checked_at' => now(),
            'rejection_note' => null,
        ]);

        session()->flash('success', 'Check sheet mould berhasil diverifikasi dan disetujui (APPROVED) oleh ' . $user->name . '.');
    }

    public function openRejectModal()
    {
        $this->rejectionNote = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectionNote = '';
    }

    public function confirmReject()
    {
        $this->validate([
            'rejectionNote' => 'required|string|min:3|max:500',
        ]);

        $user = auth()->user();
        if (! $user) {
            session()->flash('error', 'Silakan login terlebih dahulu.');
            return;
        }

        if (! $user->canSign('mould_checklist', 'checked_by')) {
            session()->flash('error', 'Anda tidak memiliki wewenang untuk menolak dokumen ini.');
            return;
        }

        $checklist = $this->checklist;
        $checklist->update([
            'status' => 'REJECTED',
            'rejection_note' => $this->rejectionNote,
        ]);

        $this->closeRejectModal();
        session()->flash('info', 'Check sheet mould ditolak (REJECTED) dan dikembalikan ke Teknisi untuk perbaikan.');
    }

    public function render()
    {
        return view('livewire.maintenance.mould-checklist.show', [
            'checklist' => $this->checklist,
        ])->layout('layouts.app');
    }
}
