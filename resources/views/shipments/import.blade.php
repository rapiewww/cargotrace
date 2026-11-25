@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    Bulk CSV shipment import
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-cyan-400 border border-blue-500/20 font-mono">
                    Max 500 rows
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Upload a structured CSV file to validate and batch register freight consignments.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('shipments.import.sample') }}" class="px-3.5 py-2 rounded-xl theme-card-elevated hover:bg-slate-200 dark:hover:bg-slate-800 theme-text-primary border theme-border text-xs font-semibold flex items-center gap-1.5 transition">
                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download sample CSV
            </a>
            <a href="{{ route('shipments.index') }}" class="px-3.5 py-2 rounded-xl theme-card-elevated theme-text-secondary hover:theme-heading border theme-border text-xs font-semibold transition">
                Back to shipments
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs space-y-1">
            <p class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Upload rejected:
            </p>
            <ul class="list-disc list-inside space-y-0.5 pl-2 font-mono text-[11px]">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Upload Card -->
    <x-card class="space-y-6">
        <form id="csv-import-form" action="{{ route('shipments.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="space-y-2">
                <label class="block text-xs font-bold theme-heading uppercase tracking-wider font-sans">
                    Select CSV consignment manifest
                </label>
                <div class="border-2 border-dashed theme-border rounded-2xl p-6 sm:p-8 text-center hover:border-blue-500/50 transition-colors bg-slate-500/5">
                    <svg class="mx-auto h-10 w-10 text-cyan-600 dark:text-cyan-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-2 text-xs">
                        <label for="csv_file" class="cursor-pointer font-bold text-blue-600 dark:text-cyan-400 hover:underline">
                            Choose a CSV file
                        </label>
                        <span class="theme-text-muted">or drag and drop here</span>
                    </div>
                    <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" required class="mt-4 block w-full max-w-sm mx-auto text-xs theme-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                    <p class="text-[11px] theme-text-muted mt-2 font-mono">
                        Plain CSV (.csv) up to 2 MB (maximum 500 records per upload)
                    </p>
                </div>
            </div>

            <!-- Validation Guarantees & Column Specs -->
            <div class="rounded-xl p-4 theme-card-elevated border theme-border space-y-3">
                <h3 class="text-xs font-bold theme-heading flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Import specifications and validation standards
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs theme-text-secondary">
                    <div class="space-y-1">
                        <p class="font-bold text-[11px] theme-heading">Required columns:</p>
                        <p class="font-mono text-[11px] theme-text-muted">carrier_code, origin_unlocode, destination_unlocode, expected_departure_at, expected_arrival_at</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-[11px] theme-heading">Container standard (ISO 6346):</p>
                        <p class="text-[11px] theme-text-muted">Optional container numbers (e.g. <span class="font-mono">MSCU 123456 6</span>) are verified against official ISO check digit algorithms.</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-[11px] theme-heading">Uniqueness enforcement:</p>
                        <p class="text-[11px] theme-text-muted">Shipment tracking numbers must be distinct within the file and against existing database records.</p>
                    </div>
                    <div class="space-y-1">
                        <p class="font-bold text-[11px] theme-heading">Atomic transaction:</p>
                        <p class="text-[11px] theme-text-muted">You will preview all verified rows and specific errors before any record is committed to the database.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('shipments.index') }}" class="px-4 py-2.5 rounded-xl theme-card-elevated theme-text-secondary hover:theme-heading border theme-border text-xs font-semibold transition">
                    Cancel
                </a>
                <button id="btn-upload-preview" type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-blue-500/25 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Upload and preview
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection
