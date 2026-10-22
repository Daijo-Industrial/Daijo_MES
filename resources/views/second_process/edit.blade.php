<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('second-process-reports.update', $report->id) }}" method="POST" enctype="multipart/form-data" id="production-report-form">
                @csrf
                @method('PUT')

                @include('second_process._form')
            </form>
        </div>
    </div>
</x-app-layout>
