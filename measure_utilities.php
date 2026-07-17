<?php
/**
 * measure_utilities.php
 * Reproduce exactamente el endpoint /utilities con Mes 05/2026 HENAVI
 * para diagnosticar el 500 que el usuario ve en network.
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

// Habilitar query log
DB::connection('tenant')->enableQueryLog();

// Simular exactamente lo que manda el frontend con Mes 05/2026
$requestAll = [
    'establishment_id' => 1,
    'period'           => 'month',
    'date_start'       => null,
    'date_end'         => null,
    'month_start'      => '2026-05',
    'month_end'        => null,
    'enabled_expense'  => 1,
    'item_id'          => null,
];

echo "=== utilities(mes 05/2026, est=1, expenses=1) ===\n";
echo "Mem limit PHP: " . ini_get('memory_limit') . "\n";

$start = microtime(true);
$peak_start = memory_get_peak_usage(true);
try {
    $result = (new \Modules\Dashboard\Helpers\DashboardUtility())->data($requestAll);
    $ms = (microtime(true) - $start) * 1000;
    $peak_end = memory_get_peak_usage(true);

    $queries = count(DB::getQueryLog());
    echo sprintf("OK — %d ms, %d queries\n", $ms, $queries);
    echo sprintf("Mem peak: %.1f MB → %.1f MB\n", $peak_start/1048576, $peak_end/1048576);
    echo "totals: " . json_encode($result['utilities']['totals']) . "\n";
} catch (\Throwable $e) {
    $ms = (microtime(true) - $start) * 1000;
    echo sprintf("FAIL en %.1f ms\n", $ms);
    echo "EXCEPTION: " . get_class($e) . "\n";
    echo "MESSAGE  : " . $e->getMessage() . "\n";
    echo "FILE:LINE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n--- TRACE (primeras 25 líneas) ---\n";
    $trace = explode("\n", $e->getTraceAsString());
    foreach (array_slice($trace, 0, 25) as $i => $line) {
        echo $line . "\n";
    }
}