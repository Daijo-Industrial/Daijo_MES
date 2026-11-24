<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($report->sp_production_session_id)
                <div class="mb-4 p-4 bg-amber-50 border border-amber-300 rounded-xl text-amber-900 text-xs">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="space-y-0.5">
                            <span class="font-bold uppercase tracking-wider text-[11px] text-amber-800">Peringatan Integrasi Sesi Digital</span>
                            <p class="font-medium">
                                Laporan ini disinkronisasi otomatis dari Sesi Tablet (#{{ $report->sp_production_session_id }}). Perubahan manual dapat tertimpa jika sesi direvisi dan disetujui ulang di menu Approvals.
                            </p>
                        </div>
                        @if($report->session?->workOrder)
                            <a href="{{ route('sp-work-orders.show', $report->session->work_order_id) }}" class="px-3 py-1.5 bg-amber-200 hover:bg-amber-300 text-amber-950 font-semibold text-xs rounded-lg transition uppercase tracking-wider shrink-0">
                                Lihat WO #{{ $report->session->workOrder->wo_number }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <form action="{{ route('second-process-reports.update', $report->id) }}" method="POST" enctype="multipart/form-data" id="production-report-form">
                @csrf
                @method('PUT')

                @include('second_process._form')
            </form>
        </div>
    </div>
</x-app-layout>
