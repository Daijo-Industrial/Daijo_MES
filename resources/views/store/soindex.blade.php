<x-app-layout>
    @if (session('success'))
        <div
            class="flex items-center p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg"
            role="alert"
        >
            <svg
                class="w-5 h-5 mr-2 text-green-500"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-3-3v6m8.5-10.25A10.017 10.017 0 0122 12c0 5.522-4.478 10-10 10S2 17.522 2 12c0-5.522 4.478-10 10-10a10.017 10.017 0 018.5 4.75z"
                />
            </svg>
            <div>
                {{ session('success') }}
            </div>
            <button
                type="button"
                class="ml-auto -mx-1.5 -my-1.5 bg-green-100 rounded-md p-1.5 inline-flex items-center justify-center text-green-500 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-400"
                aria-label="Close"
            >
                <span class="sr-only">Close</span>
                <svg
                    class="w-5 h-5"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>
    @endif

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @php
                    $lastUploadTime = \App\Models\UpdateLog::latest()->first();
                @endphp

                @if ($lastUploadTime)
                    <div class="flex justify-end mt-4">
                        <div
                            class="bg-gray-200 border border-gray-300 rounded-lg p-4 max-w-xs"
                        >
                            <p class="text-right text-gray-600">
                                <strong>LAST UPLOAD</strong>
                                <br />
                                {{ \Carbon\Carbon::parse($lastUploadTime->last_upload_time)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="flex justify-center items-center mt-4 gap-6">
                    <form
                        action="{{ route('import.so.data') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="flex items-center"
                    >
                        @csrf
                        <input
                            type="file"
                            name="import_file"
                            id="import_file"
                            accept=".xlsx, .xls, .csv"
                            class="mr-2"
                            required
                        />
                        <button
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer whitespace-nowrap"
                        >
                            Import Excel
                        </button>
                    </form>

                    <div class="border-l-2 border-gray-300 h-10"></div>

                    <a href="{{ route('upload.spk.history') }}" 
                       class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded cursor-pointer whitespace-nowrap flex items-center shadow-sm transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Upload SPK History
                    </a>

                    <div class="border-l-2 border-gray-300 h-10"></div>

                    <a href="{{ route('so.tracking') }}" 
                       class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded cursor-pointer whitespace-nowrap flex items-center shadow-sm transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Tracking SPK & Label DO
                    </a>
                </div>

                <div class="flex justify-center mt-4">
                    <form
                        action="{{ route('so.index') }}"
                        method="GET"
                        class="ml-4"
                    >
                        <label for="doc_num" class="mr-2 font-semibold text-lg">
                            Search Doc Number:
                        </label>
                        <input
                            type="text"
                            name="doc_num"
                            id="doc_num"
                            class="border rounded p-2"
                            value="{{ request('doc_num') }}"
                            placeholder="Enter doc number"
                        />
                        <button
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer"
                        >
                            Search
                        </button>
                    </form>
                </div>

                <div class="flex justify-center mb-6">
                    <form action="{{ route('so.filter') }}" method="GET">
                        <label for="is_done" class="mr-2 font-semibold text-lg">
                            Show:
                        </label>
                        <select
                            name="is_done"
                            id="is_done"
                            class="border rounded p-2"
                            onchange="this.form.submit()"
                        >
                            <option
                                value="all"
                                {{ request('is_done') === 'all' ? 'selected' : '' }}
                            >
                                All
                            </option>
                            <option
                                value="1"
                                {{ request('is_done') === '1' ? 'selected' : '' }}
                            >
                                Done
                            </option>
                            <option
                                value="0"
                                {{ request('is_done') === '0' ? 'selected' : '' }}
                            >
                                Not Done
                            </option>
                        </select>
                    </form>
                </div>

                <h1
                    class="text-2xl font-semibold text-gray-800 mb-6 text-center mt-5"
                >
                    Distinct DOC Numbers
                </h1>
                <hr />

                <!-- Display distinct doc_num values with buttons -->
                <ul class="space-y-4">
                    @foreach ($docNums as $docNum)
                        <li
                            class="flex justify-between items-center bg-white p-4 rounded-lg shadow-md"
                        >
                            <span class="text-gray-700 text-lg">
                                {{ $docNum->doc_num }}
                                @if ($docNum->is_done == 1)
                                    <span class="text-green-500 text-sm ml-2">
                                        Done
                                    </span>
                                @endif
                            </span>

                            <a
                                href="{{ route('so.process', [$docNum->doc_num]) }}"
                                class="inline-block px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Process
                            </a>
                        </li>
                    @endforeach
                </ul>

                <!-- Optional: Display status message -->
                @if (session('status'))
                    <p class="mt-4 text-center text-green-600 font-semibold">
                        {{ session('status') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{ $docNums->links() }}
    </div>
</x-app-layout>
