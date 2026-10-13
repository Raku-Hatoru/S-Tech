<?php

namespace App\Console\Commands;

use App\Models\StatusWilayah;
use App\Services\StatusService;
use Illuminate\Console\Command;

class StatusRecalcCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status:recalc {--periode= : Periode spesifik dalam format YYYY-MM}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hitung ulang tabel status_wilayah dan bersihkan cache status';

    /**
     * Execute the console command.
     */
    public function handle(StatusService $statusService): int
    {
        $periode = $this->option('periode');

        if ($periode) {
            $this->info(" Memulai kalkulasi ulang status wilayah untuk periode {$periode}...");
            $statusService->recalc(null, null, $periode);
        } else {
            $this->info(' Memulai kalkulasi ulang seluruh status wilayah (historis & forecast)...');
            $statusService->recalcSemua();
        }

        $totalStatus = StatusWilayah::count();

        $this->info("✅ Perhitungan ulang status wilayah selesai! Total record status_wilayah: {$totalStatus}");
        $this->info(' Cache status wilayah telah dibersihkan secara otomatis.');

        return self::SUCCESS;
    }
}
