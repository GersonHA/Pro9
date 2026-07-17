<?php
/**
 * measure_data_aditional.php <month> [est]
 * Reproduce el endpoint /data_aditional con el mes indicado para diagnosticar
 * el 500 que aparece con Mes 06/2026 en HENAVI.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('memory_limit', '128M'); // simular PHP-FPM prod
set_time_limit(600);

$hostname = \Hyn\Tenancy\Models\Hostname::where('fqdn', 'grupomultiservicioshenavi.localhost')->first();
if (!$hostname) { die("Tenant no encontrado\n"); }

$tenancy = $app->make(\Hyn\Tenancy\Environment::class);
$tenancy->hostname($hostname);
$connection_handler = $app->make(\Hyn\Tenancy\Database\Connection::class);
$connection_handler->set($hostname);

DB::connection('tenant')->getPdo();
DB::connection('tenant')->enableQueryLog();

$month = $argv[1] ?? '2026-06';
$d_start = $month . '-01';
$d_end   = date('Y-m-t', strtotime($d_start));
$establishment_id = DB::connection('tenant')->table('establishments')->first()->id ?? null;

echo "=== data_aditional(mes {$month}, est={$establishment_id}) — memory_limit=128M ===\n";

$start = microtime(true);
$peak0 = memory_get_peak_usage(true);
try {
    $result = (new \Modules\Dashboard\Helpers\DashboardSalePurchase())->data([
        'establishment_id' => $establishment_id,
        'period'           => 'month',
        'month_start'      => $month,
        'month_end'        => $month,
        'date_start'       => $d_start,
        'date_end'         => $d_end,
        'enabled_move_item'    => false,
        'enabled_transaction_customer' => false,
        'no_take'           => false,
        'page'              => 1,
    ]);
    $ms = (microtime(true) - $start) * 1000;
    $peak = memory_get_peak_usage(true);
    $queries = count(DB::getQueryLog());
    echo sprintf("OK — %.0f ms, %d queries\n", $ms, $queries);
    echo sprintf("Mem peak: %.1f MB → %.1f MB\n", $peak0/1048576, $peak/1048576);
    echo "Items devueltos: " . count($result['top_items_by_sale'] ?? $result['top_10_items_by_sale'] ?? []) . "\n";
} catch (\Throwable $e) {
    $ms = (microtime(true) - $start) * 1000;
    echo sprintf("FAIL en %.0f ms\n", $ms);
    echo "EXCEPTION: " . get_class($e) . "\n";
    echo "MESSAGE  : " . $e->getMessage() . "\n";
    echo "FILE:LINE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n--- TRACE (primeras 25 líneas) ---\n";
    foreach (array_slice(explode("\n", $e->getTraceAsString()), 0, 25) as $i => $line) {
        echo $line . "\n";
    }
}
