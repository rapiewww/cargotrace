<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shipment\ImportShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ShipmentImportWebController extends Controller
{
    public function __construct(
        protected ShipmentCsvImportService $importService
    ) {}

    /**
     * Show the CSV upload form.
     */
    public function index(): View
    {
        Gate::authorize('import', Shipment::class);

        return view('shipments.import');
    }

    /**
     * Parse and preview the uploaded CSV data.
     */
    public function preview(ImportShipmentRequest $request): View
    {
        Gate::authorize('import', Shipment::class);

        $results = $this->importService->parseAndValidate(
            $request->file('csv_file'),
            $request->user()
        );

        session()->put('shipment_import_valid_rows', $results['valid_rows']);

        return view('shipments.import-preview', [
            'results' => $results,
        ]);
    }

    /**
     * Commit the verified rows in a single database transaction.
     */
    public function confirm(Request $request): RedirectResponse
    {
        Gate::authorize('import', Shipment::class);

        $validRows = session()->get('shipment_import_valid_rows');

        if (empty($validRows) || ! is_array($validRows)) {
            return redirect()->route('shipments.import.index')
                ->withErrors(['csv_file' => 'No valid consignment rows pending import. Please upload a new CSV file.']);
        }

        $outcome = $this->importService->commit($validRows, $request->user());

        session()->forget('shipment_import_valid_rows');

        $count = $outcome['imported_count'];

        return redirect()->route('shipments.index')
            ->with('success', "Successfully imported {$count} ocean container ".($count === 1 ? 'shipment' : 'shipments').'.');
    }

    /**
     * Download a sample CSV file formatted for the importer.
     */
    public function sample(): Response
    {
        Gate::authorize('import', Shipment::class);

        $csv = $this->importService->generateSampleCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="cargotrace_shipments_sample.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}

