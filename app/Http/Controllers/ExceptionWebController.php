<?php

namespace App\Http\Controllers;

use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExceptionWebController extends Controller
{
    public function __construct(
        protected ExceptionService $exceptionService
    ) {}

    public function index(Request $request): View
    {
        $query = ShipmentException::with([
            'shipment.carrier',
            'shipment.originLocation',
            'shipment.destinationLocation',
            'reporter',
            'resolver',
        ]);

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->query('status')));
        }
        if ($request->filled('severity')) {
            $query->where('severity', strtoupper($request->query('severity')));
        }
        if ($request->filled('code')) {
            $query->where('exception_code', strtoupper($request->query('code')));
        }

        $exceptions = $query->orderByDesc('detected_at')->paginate(15)->withQueryString();
        $counts = [
            'total' => ShipmentException::count(),
            'open' => ShipmentException::where('status', 'OPEN')->count(),
            'investigating' => ShipmentException::where('status', 'INVESTIGATING')->count(),
            'resolved' => ShipmentException::where('status', 'RESOLVED')->count(),
        ];

        return view('exceptions.index', compact('exceptions', 'counts'));
    }

    public function update(Request $request, ShipmentException $exception): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:OPEN,INVESTIGATING,RESOLVED',
            'resolution_notes' => 'nullable|string',
        ]);

        $this->exceptionService->updateExceptionStatus($exception, $validated['status'], $validated['resolution_notes'] ?? null);

        return back()->with('success', 'Exception status updated successfully.');
    }
}
