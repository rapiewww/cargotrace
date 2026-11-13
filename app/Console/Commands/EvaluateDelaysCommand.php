<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Services\EtaService;
use Illuminate\Console\Command;

class EvaluateDelaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cargotrace:evaluate-delays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-evaluate ETA variances and delay severity for all active shipments.';

    /**
     * Execute the console command.
     */
    public function handle(EtaService $etaService): int
    {
        $this->info('Starting automated delay evaluation for active ocean container shipments...');

        // Only active shipments (not delivered)
        $shipments = Shipment::where('status', '!=', 'DELIVERED')
            ->with(['carrier', 'customer', 'originLocation', 'destinationLocation'])
            ->get();

        $total = $shipments->count();
        $delayedCount = 0;

        foreach ($shipments as $shipment) {
            $updated = $etaService->evaluateDelay($shipment);
            if ($updated->is_delayed) {
                $delayedCount++;
            }
        }

        $this->info("Completed delay evaluation: {$total} active shipments evaluated, {$delayedCount} currently delayed.");

        return Command::SUCCESS;
    }
}
