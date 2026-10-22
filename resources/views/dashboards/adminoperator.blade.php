<x-app-layout>
    <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">📅 Jadwal Produksi Operator - Mesin: {{ auth()->user()->name }}</h1>


        <button onclick="toggleAddForm()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
            + Tambah Daily Item
        </button>

        <!-- Modal Structure -->
        <div id="addForm" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg w-full md:w-1/2 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-700">Form Input</h2>
                    <button id="closeAddForm" class="text-gray-500 hover:text-gray-700">
                        &times;
                    </button>
                </div>

                <form method="POST" action="{{ route('dicadmin.store') }}">
                    @csrf

                    <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">


                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Item Code Dropdown AJAX -->
                        <div class="relative">
                            <label class="block font-semibold text-gray-700">Item Code</label>
                            <input type="text" id="item_code" name="item_code" autocomplete="off" required
                                class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none"
                                placeholder="Ketik item code">

                            <select id="item_code_list"
                                    size="5"
                                    class="border border-gray-300 rounded mt-1 max-h-40 overflow-y-auto bg-white absolute z-50 w-full hidden"></select>
                        </div>

                        <!-- Shift -->
                        <div>
                            <label class="block font-semibold text-gray-700">Shift</label>
                            <select id="shift" name="shift" required
                                    class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none">
                                <option value="">Pilih Shift</option>
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                                <option value="3">Shift 3</option>
                            </select>
                        </div>

                        <!-- Tanggal -->
                        <div>
                            <label class="block font-semibold text-gray-700">Tanggal Mulai</label>
                            <input type="date" name="start_schedule_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required
                                class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none">
                        </div>

                          <!-- Tanggal -->
                        <div>
                            <label class="block font-semibold text-gray-700">Tanggal Selesai</label>
                            <input type="date" name="end_schedule_date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required
                                class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none">
                        </div>
                    </div>

                    <!-- Start & End Time (otomatis berdasarkan shift) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block font-semibold text-gray-700">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required
                                class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none">
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-700">End Time</label>
                            <input type="time" id="end_time" name="end_time" required
                                class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none">
                        </div>
                    </div>

                    <!-- PIC -->
                    <div class="mt-4">
                        <label class="block font-semibold text-gray-700">Quantity</label>
                        <input type="number" name="quantity" required
                            class="w-full border rounded-lg p-2 mt-1 focus:ring focus:ring-blue-300 focus:outline-none">
                    </div>

                    <button type="submit"
                            class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Simpan
                    </button>
                </form>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if ($datas->isEmpty())
            <p class="text-gray-600">Belum ada jadwal produksi.</p>
        @else
            @php
                $grouped = $datas
                    ->groupBy(fn($item) => \Carbon\Carbon::parse($item->start_date)->format('Y-m-d'))
                    ->sortKeys();
            @endphp

            @foreach ($grouped as $date => $items)
                @php
                    $sortedItems = $items->sortBy('start_time');
                    $dateLabel = match (true) {
                        $date === \Carbon\Carbon::yesterday()->format('Y-m-d') => '🕒 Kemarin',
                        $date === \Carbon\Carbon::today()->format('Y-m-d') => '📆 Hari Ini',
                        $date === \Carbon\Carbon::tomorrow()->format('Y-m-d') => '🔮 Besok',
                        default => \Carbon\Carbon::parse($date)->format('d M Y'),
                    };
                @endphp

                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-blue-700 mb-3">{{ $dateLabel }}</h2>

                    <div class="overflow-x-auto bg-white shadow-md rounded-lg">
                        <table class="min-w-full text-sm text-gray-800">
                            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-2 text-left">#</th>
                                    <th class="px-4 py-2 text-left">Item Code</th>
                                    <th class="px-4 py-2 text-left">Shift</th>
                                    <th class="px-4 py-2 text-left">Start Time</th>
                                    <th class="px-4 py-2 text-left">End Time</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sortedItems as $index => $item)
                                    @php
                                        $isToday = $date === \Carbon\Carbon::today()->format('Y-m-d');
                                        $bg = $isToday ? 'bg-blue-50' : 'bg-white';
                                        $isDone = $item->is_done == 1;

                                        $status = $isDone ? 'Done' : 'Proses';
                                        $statusClass = $isDone
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-yellow-100 text-yellow-700';
                                    @endphp

                                    {{-- tiap item punya scope Alpine sendiri --}}
                                    <tbody x-data="{ open: false }">
                                        <tr class="border-b hover:bg-gray-50 {{ $bg }}">
                                            <td class="px-4 py-2">
                                                <button @click="open = !open" type="button"
                                                    class="text-gray-600 hover:text-blue-600 focus:outline-none">
                                                    <span x-show="!open">🔽</span>
                                                    <span x-show="open">🔼</span>
                                                </button>
                                            </td>
                                            <td class="px-4 py-2 font-medium">{{ $item->item_code }}</td>
                                            <td class="px-4 py-2">{{ $item->shift ?? '-' }}</td>
                                            <td class="px-4 py-2">{{ $item->start_time ?? '-' }}</td>
                                            <td class="px-4 py-2">{{ $item->end_time ?? '-' }}</td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusClass }}">
                                                    {{ $status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 space-x-2">
                                                <form method="POST"
                                                    action="{{ route('dailyitemcodes.set-status', $item->id) }}"
                                                    class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="null">
                                                    <button type="submit"
                                                        class="text-red-600 hover:underline text-xs">✖ Proses</button>
                                                </form>

                                                <form method="POST"
                                                    action="{{ route('dailyitemcodes.set-status', $item->id) }}"
                                                    class="inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="1">
                                                    <button type="submit"
                                                        class="text-green-600 hover:underline text-xs">✔ Done</button>
                                                </form>
                                            </td>
                                            <td>
                                               <form method="POST"
                                                    action="{{ route('dailyitemcodes.delete', $item->id) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('⚠️ Yakin ingin menghapus Daily Item Code ini? Semua data terkait akan ikut terhapus!');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-gray-600 hover:text-red-700 hover:scale-125 hover:rotate-6 hover:shadow-lg transition-all duration-200 ease-in-out animate-hover-shake text-xs"
                                                        title="Hapus">
                                                        💀
                                                    </button>
                                                </form>

                                                <style>
                                                    @keyframes shake {
                                                        0%, 100% { transform: translateX(0); }
                                                        25% { transform: translateX(-3px); }
                                                        50% { transform: translateX(3px); }
                                                        75% { transform: translateX(-3px); }
                                                    }

                                                    .animate-hover-shake:hover {
                                                        animation: shake 0.3s;
                                                    }
                                                </style>
                                            </td>
                                        </tr>

                                        {{-- Detail Hourly Remarks --}}
                                        <tr x-show="open" x-transition>
                                            <td colspan="7" class="bg-gray-50 p-4">
                                                <h3 class="font-semibold text-gray-700 mb-2">Detail Hourly Remarks:</h3>

                                                @if ($item->hourlyRemarks->isEmpty())
                                                    <p class="text-gray-500 text-sm">Tidak ada data remark.</p>
                                                @else
                                                    <table class="min-w-full text-xs text-gray-700 border">
                                                        <thead class="bg-gray-100 text-gray-600 uppercase">
                                                            <tr>
                                                                <th class="px-2 py-1 text-left">Start</th>
                                                                <th class="px-2 py-1 text-left">End</th>
                                                                <th class="px-2 py-1 text-left">Target</th>
                                                                <th class="px-2 py-1 text-left">Scan</th>
                                                                <th class="px-2 py-1 text-left">Actual</th>
                                                                <th class="px-2 py-1 text-left">Remark</th>
                                                                <th class="px-2 py-1 text-left">Action</th>

                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($item->hourlyRemarks as $hr)
                                                                @php
                                                                    $initActual = $hr->actual_production !== null ? $hr->actual_production : 0;
                                                                    $initRemark = $hr->remark ?? '';
                                                                    $dispActual = $hr->actual_production !== null ? (string)$hr->actual_production : '-';
                                                                    $dispRemark = ($hr->remark !== null && trim($hr->remark) !== '') ? $hr->remark : '-';
                                                                @endphp
                                                                <tr class="border-t hover:bg-gray-50/50" 
                                                                    x-data="{ 
                                                                        editing: false, 
                                                                        actual: {{ json_encode($initActual) }}, 
                                                                        remark: {{ json_encode($initRemark) }},
                                                                        displayActual: {{ json_encode($dispActual) }},
                                                                        displayRemark: {{ json_encode($dispRemark) }},
                                                                        isSaving: false,
                                                                        save() {
                                                                            this.isSaving = true;
                                                                            $.ajax({
                                                                                url: '{{ url('/hourly-remarks') }}/{{ $hr->id }}/update-remark',
                                                                                type: 'POST',
                                                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                                                                data: {
                                                                                    actual_production: this.actual,
                                                                                    remark: this.remark
                                                                                },
                                                                                success: (res) => {
                                                                                    this.isSaving = false;
                                                                                    this.editing = false;
                                                                                    this.displayActual = (this.actual !== '' && this.actual !== null) ? this.actual : '-';
                                                                                    this.displayRemark = (this.remark && this.remark.trim() !== '') ? this.remark : '-';
                                                                                },
                                                                                error: (xhr) => {
                                                                                    this.isSaving = false;
                                                                                    alert('Gagal menyimpan perubahan.');
                                                                                }
                                                                            });
                                                                        }
                                                                    }">
                                                                    <td class="px-2 py-1 text-gray-600">{{ $hr->start_time }}</td>
                                                                    <td class="px-2 py-1 text-gray-600">{{ $hr->end_time }}</td>
                                                                    <td class="px-2 py-1 font-semibold">{{ $hr->target ?? '-' }}</td>
                                                                    <td class="px-2 py-1 font-semibold text-blue-600">{{ $hr->actual ?? '-' }}</td>
                                                                    
                                                                    <!-- Editable Actual Production -->
                                                                    <td class="px-2 py-1">
                                                                        <span x-show="!editing" class="font-bold text-emerald-700" x-text="displayActual"></span>
                                                                        <input x-show="editing" type="number" min="0" x-model="actual" class="w-20 border border-indigo-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500 font-bold">
                                                                    </td>

                                                                    <!-- Editable Remark -->
                                                                    <td class="px-2 py-1 min-w-[200px]">
                                                                        <span x-show="!editing" class="whitespace-pre-wrap text-gray-700 block" x-text="displayRemark"></span>
                                                                        <textarea x-show="editing" x-model="remark" rows="2" class="w-full border border-indigo-300 rounded px-2 py-1 text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Ketik remark..."></textarea>
                                                                    </td>

                                                                    <!-- Action Column -->
                                                                    <td class="px-2 py-1">
                                                                        <div class="flex items-center gap-2">
                                                                            <button x-show="!editing" type="button" @click="editing = true" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded transition cursor-pointer">
                                                                                ✏️ Edit
                                                                            </button>

                                                                            <div x-show="editing" class="flex items-center gap-1" style="display: none;">
                                                                                <button type="button" @click="save()" :disabled="isSaving" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2 py-1 rounded text-xs transition shadow-sm cursor-pointer">
                                                                                    <span x-show="!isSaving">💾 Simpan</span>
                                                                                    <span x-show="isSaving">...</span>
                                                                                </button>
                                                                                <button type="button" @click="editing = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-2 py-1 rounded text-xs transition cursor-pointer">
                                                                                    Batal
                                                                                </button>
                                                                            </div>

                                                                            <form action="{{ route('hourlyremarks.destroy', $hr->id) }}" method="POST" onsubmit="return confirm('Yakin hapus remark ini beserta data scan terkait?')" class="inline">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-semibold hover:underline cursor-pointer">Hapus</button>
                                                                            </form>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <script>
    function toggleAddForm() {
        const form = document.getElementById('addForm');
        form.classList.toggle('hidden');
    }

    const addForm = document.getElementById('addForm');
    const closeAddForm = document.getElementById('closeAddForm');
    closeAddForm.addEventListener('click', function() {
        addForm.classList.add('hidden');
    });

    document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('item_code');
    const select = document.getElementById('item_code_list');
    let timeout = null;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = input.value.trim();

        if (query.length < 1) {
            select.innerHTML = '';
            select.classList.add('hidden');
            return;
        }

        timeout = setTimeout(() => {
            fetch(`{{ route('daily-item-code.get-item-codes') }}?search=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                    const items = data.items;
                    select.innerHTML = '';

                    if (items.length === 0) {
                        select.classList.add('hidden');
                        return;
                    }

                    items.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.text;
                        select.appendChild(option);
                    });

                    select.classList.remove('hidden');
                })
                .catch(err => console.error('Fetch error:', err));
        }, 300);
    });

    // Ketika user klik salah satu option
    select.addEventListener('change', function() {
        input.value = select.value;
        select.classList.add('hidden');
    });

    // Tutup dropdown kalau klik di luar
    document.addEventListener('click', (e) => {
        if (!select.contains(e.target) && e.target !== input) {
            select.classList.add('hidden');
        }
    });
    

    // Shift otomatis set start & end time
    const shiftEl = document.getElementById('shift');
    shiftEl.addEventListener('change', function () {
        const startInput = document.getElementById('start_time');
        const endInput = document.getElementById('end_time');

        switch (this.value) {
            case '1':
                startInput.value = '07:30';
                endInput.value = '15:30';
                break;
            case '2':
                startInput.value = '15:30';
                endInput.value = '23:30';
                break;
            case '3':
                startInput.value = '23:30';
                endInput.value = '07:30';
                break;
            default:
                startInput.value = '';
                endInput.value = '';
        }
    });
});
</script>
</x-app-layout>
