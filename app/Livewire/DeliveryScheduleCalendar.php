<?php

namespace App\Livewire;

use App\Models\MasterCustomerDelivery;
use App\Models\MasterDeliverySchedule;
use Carbon\Carbon;
use Livewire\Component;

class DeliveryScheduleCalendar extends Component
{
    public $customerSearch = '';

    public $selectedCustomer = null;

    public $itemSearch = '';

    public $selectedItemCode = null;

    public $selectedMonth;

    public $selectedYear;

    public $showCustomerDropdown = false;

    public $showItemDropdown = false;

    public $calendarData = [];

    public $selectedDate = null;

    public $selectedDateSchedules = [];

    public $showModal = false;

    public $editingScheduleId = null;

    public $editingQuantity = 0;

    public function mount()
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
        $this->loadCalendarData();
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
            $this->loadCalendarData();
        }
    }

    public function clearCustomer()
    {
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->loadCalendarData();
    }

    public function selectItem($itemCode)
    {
        $this->selectedItemCode = $itemCode;
        $this->itemSearch = $itemCode;
        $this->showItemDropdown = false;
        $this->loadCalendarData();
    }

    public function clearItem()
    {
        $this->selectedItemCode = null;
        $this->itemSearch = '';
        $this->loadCalendarData();
    }

    public function updatedSelectedMonth()
    {
        $this->loadCalendarData();
    }

    public function updatedSelectedYear()
    {
        $this->loadCalendarData();
    }

    public function loadCalendarData()
    {

        $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        // Build query dengan filter yang ada
        $query = MasterDeliverySchedule::whereBetween('tanggal', [$startDate, $endDate]);

        // Filter by customer jika dipilih
        if ($this->selectedCustomer) {
            $query->where('customer_code', $this->selectedCustomer->customer_code);
        }

        // Filter by item code jika dipilih
        if ($this->selectedItemCode) {
            $query->where('item_code', $this->selectedItemCode);
        }

        $schedules = $query->orderBy('tanggal')
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->day;
            });

        // Build calendar data
        $this->calendarData = [];
        $daysInMonth = $startDate->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, $day);
            $daySchedules = $schedules->get($day, collect());

            $this->calendarData[$day] = [
                'date' => $date,
                'day_name' => $date->format('D'),
                'schedules' => $daySchedules,
                'total_items' => $daySchedules->count(),
                'total_quantity' => $daySchedules->sum('quantity'),
                'is_today' => $date->isToday(),
                'is_past' => $date->isPast() && ! $date->isToday(),
            ];
        }
    }

    public function viewDate($day)
    {
        $this->selectedDate = $day;
        $this->selectedDateSchedules = $this->calendarData[$day]['schedules'] ?? [];
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedDate = null;
        $this->selectedDateSchedules = [];
        $this->editingScheduleId = null;
    }

    public function editSchedule($scheduleId)
    {
        $schedule = MasterDeliverySchedule::find($scheduleId);
        if ($schedule) {
            $this->editingScheduleId = $scheduleId;
            $this->editingQuantity = $schedule->quantity;
        }
    }

    public function saveSchedule()
    {
        $this->validate([
            'editingQuantity' => 'required|integer|min:0',
        ]);

        $schedule = MasterDeliverySchedule::find($this->editingScheduleId);
        if ($schedule) {
            if ($this->editingQuantity == 0) {
                $schedule->delete();
            } else {
                $schedule->quantity = $this->editingQuantity;
                $schedule->save();
            }

            $this->editingScheduleId = null;
            $this->loadCalendarData();

            // Refresh modal data
            if ($this->selectedDate) {
                $this->selectedDateSchedules = $this->calendarData[$this->selectedDate]['schedules'] ?? [];
            }

            session()->flash('modal_success', 'Schedule updated successfully!');
        }
    }

    public function cancelEdit()
    {
        $this->editingScheduleId = null;
    }

    public function deleteSchedule($scheduleId)
    {
        $schedule = MasterDeliverySchedule::find($scheduleId);
        if ($schedule) {
            $schedule->delete();
            $this->loadCalendarData();

            // Refresh modal data
            if ($this->selectedDate) {
                $this->selectedDateSchedules = $this->calendarData[$this->selectedDate]['schedules'] ?? [];
            }

            session()->flash('modal_success', 'Schedule deleted successfully!');
        }
    }

    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear = $date->year;
        $this->loadCalendarData();
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear = $date->year;
        $this->loadCalendarData();
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

        // Get distinct item codes dari MasterDeliverySchedule
        return MasterDeliverySchedule::select('item_code')
            ->distinct()
            ->where('item_code', 'like', '%' . $this->itemSearch . '%')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.delivery-schedule-calendar');
    }
}
