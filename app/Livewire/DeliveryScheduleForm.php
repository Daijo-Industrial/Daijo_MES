<?php

namespace App\Livewire;

use App\Models\MasterCustomerDelivery;
use App\Models\MasterDeliverySchedule;
use App\Models\MasterListItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DeliveryScheduleForm extends Component
{
    public $customerSearch = '';

    public $selectedCustomer = null;

    public $selectedMonth;

    public $selectedYear;

    public $itemSearch = '';

    public $selectedItem = null;

    public $soNum = '';

    public $schedules = [];

    public $showCustomerDropdown = false;

    public $showItemDropdown = false;

    protected $rules = [
        'selectedCustomer' => 'required',
        'selectedMonth' => 'required|integer|between:1,12',
        'selectedYear' => 'required|integer|min:2020',
        'selectedItem' => 'required',
    ];

    public function mount()
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->generateScheduleTable();
    }

    public function updatedCustomerSearch()
    {
        $this->showCustomerDropdown = ! empty($this->customerSearch);
    }

    public function updatedItemSearch()
    {
        $this->showItemDropdown = ! empty($this->itemSearch);
    }

    public function selectCustomer($customerId)
    {
        $customer = MasterCustomerDelivery::find($customerId);
        if ($customer) {
            $this->selectedCustomer = $customer;
            $this->customerSearch = $customer->customer_code . ' - ' . $customer->customer_name;
            $this->showCustomerDropdown = false;
        }
    }

    public function selectItem($itemId)
    {
        $item = MasterListItem::find($itemId);
        if ($item) {
            $this->selectedItem = $item;
            $this->itemSearch = $item->item_code . ' - ' . $item->item_name;
            $this->showItemDropdown = false;
        }
    }

    public function updatedSelectedMonth()
    {
        $this->generateScheduleTable();
    }

    public function updatedSelectedYear()
    {
        $this->generateScheduleTable();
    }

    public function generateScheduleTable()
    {
        if ($this->selectedMonth && $this->selectedYear) {
            $daysInMonth = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->daysInMonth;

            $this->schedules = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $this->schedules[$day] = [
                    'date' => $day,
                    'quantity' => 0,
                ];
            }
        }
    }

    public function submit()
    {
        $this->validate();

        if (empty($this->schedules)) {
            session()->flash('error', 'Silakan generate tabel schedule terlebih dahulu');

            return;
        }

        DB::beginTransaction();

        try {
            $inserted = 0;

            foreach ($this->schedules as $day => $schedule) {
                // Skip jika quantity 0 atau kosong
                if (! isset($schedule['quantity']) || $schedule['quantity'] <= 0) {
                    continue;
                }

                // Buat tanggal lengkap
                $tanggal = Carbon::createFromDate(
                    $this->selectedYear,
                    $this->selectedMonth,
                    $day
                )->format('Y-m-d');

                // Insert ke database - LANGSUNG TANPA CONTROLLER
                MasterDeliverySchedule::create([
                    'customer_code' => $this->selectedCustomer->customer_code,
                    'item_code' => $this->selectedItem->item_code,
                    'tanggal' => $tanggal,
                    'quantity' => $schedule['quantity'],
                    'so_num' => $this->soNum ?: null,
                ]);

                $inserted++;
            }

            DB::commit();

            session()->flash('success', "Berhasil menyimpan {$inserted} schedule delivery!");
            $this->reset(['customerSearch', 'selectedCustomer', 'itemSearch', 'selectedItem', 'soNum', 'schedules']);
            $this->mount();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getCustomersProperty()
    {
        if (empty($this->customerSearch)) {
            return collect();
        }

        return MasterCustomerDelivery::where('customer_code', 'like', '%' . $this->customerSearch . '%')
            ->orWhere('customer_name', 'like', '%' . $this->customerSearch . '%')
            ->limit(10)
            ->get();
    }

    public function getItemsProperty()
    {
        if (empty($this->itemSearch)) {
            return collect();
        }

        return MasterListItem::where('item_code', 'like', '%' . $this->itemSearch . '%')
            ->orWhere('item_name', 'like', '%' . $this->itemSearch . '%')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.delivery-schedule-form');
    }
}
