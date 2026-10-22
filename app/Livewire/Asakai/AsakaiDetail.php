<?php

namespace App\Livewire\Asakai;

use App\Models\Asakai;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class AsakaiDetail extends Component
{
    public $asakai;

    public $asakaiId;

    public function mount($id)
    {
        $this->asakaiId = $id;
        $this->loadAsakai();
    }

    public function loadAsakai()
    {
        $this->asakai = Asakai::with([
            'pics',
            'rcas',
            'correctiveActions',
            'creator',
            'updater',
        ])->findOrFail($this->asakaiId);
    }

    public function changeStatus($status)
    {
        if ($status === 'closed') {
            $this->asakai->markAsClosed();
        } else {
            $this->asakai->update(['status' => $status]);
        }

        $this->loadAsakai();
        session()->flash('success', 'Status berhasil diubah!');
    }

    public function delete()
    {
        // if (!$this->asakai->canBeEditedBy(auth()->user())) {
        //     session()->flash('error', 'Anda tidak memiliki izin untuk menghapus data ini.');
        //     return;
        // }

        $this->asakai->delete();
        session()->flash('success', 'Data Asakai berhasil dihapus!');

        return redirect()->route('asakai.index');
    }

    public function render()
    {
        return view('livewire.asakai.asakai-detail');
    }
}
