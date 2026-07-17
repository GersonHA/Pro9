<?php
/**
 * Script de validación para la optimización de items_by_sales()
 * Captura el output del método actual para comparar antes/después del fix.
 *
 * Ejecutar:
 *   docker exec pro9_app php measure_items_by_sales.php > /tmp/items_before.json
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('memory_limit', '1024M');
set_time_limit(600);

$hostname = \Hyn\Tenancy\Models\Hostname::where('fqdn', 'grupomultiservicioshenavi.localhost')->first();
if (!$hostname) { die("Tenant no encontrado\n"); }

$tenancy = $app->make(\Hyn\Tenancy\Environment::class);
$tenancy->hostname($hostname);

$connection_handler = $app->make(\Hyn\Tenancy\Database\Connection::class);
$connection_handler->set($hostname);

$conn = \DB::connection('tenant');
$establishment_id = $conn->table('establishments')->first()->id ?? null;

$d_start = '2026-05-01';
$d_end = '2026-05-31';

$period = $argv[1] ?? 'month';   // month | between_months | date | between_dates | last_week
$month_start = '2026-05';
$date_start = '2026-05-01';
$date_end = '2026-05-31';

$dsp = new \Modules\Dashboard\Helpers\DashboardSalePurchase();

// Helper para llamar métodos privados
function callPrivate($obj, $method, ...$args) {
    $r = new \ReflectionMethod($obj, $method);
    $r->setAccessible(true);
    return $r->invoke($obj, ...$args);
}

// Captura con log de queries
\DB::connection('tenant')->flushQueryLog();
\DB::connection('tenant')->enableQueryLog();
$t0 = microtime(true);

$result = callPrivate($dsp, 'items_by_sales', $establishment_id, $d_start, $d_end, false, false, 1);

$t1 = microtime(true);
$queries = \DB::connection('tenant')->getQueryLog();

$output = [
    'period' => $period,
    'establishment_id' => $establishment_id,
    'd_start' => $d_start,
    'd_end' => $d_end,
    'items_by_sales' => $result->values()->toArray(),
    'metrics' => [
        'ms' => round(($t1-$t0)*1000),
        'queries' => count($queries),
    ],
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
