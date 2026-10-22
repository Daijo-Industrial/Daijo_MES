<?php

namespace App\Livewire;

use Livewire\Component;

class Alert extends Component
{
    public $message = '';

    public $type = 'success'; // Default type is success

    public $visible = false;

    protected $listeners = ['showAlert'];

    // Handle session flash messages in the mount() method
    public function mount()
    {
        if (session()->has('success')) {
            $this->message = session('success');
            $this->visible = true;
        } elseif (session()->has('error')) {
            $this->message = session('error');
            $this->type = 'error';
            $this->visible = true;
        } elseif (session()->has('errors')) {
            $this->message = 'Please correct the errors and try again.';
            $this->type = 'error';
            $this->visible = true;
        }
    }

    // Handle real-time events to show alerts
    public function showAlert($message, $type = 'success')
    {
        $this->message = $message;
        $this->type = $type; // Set type dynamically
        $this->visible = true;
    }

    // Close the alert manually
    public function closeAlert()
    {
        $this->visible = false;
    }

    public function render()
    {
        return view('livewire.alert');
    }
}
