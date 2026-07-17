<?php
/**
 * measure_data_aditional_jun.php
 * Aísla cada uno de los 3 sub-productos de data_aditional para Mes 06/2026.
 * Hipótesis: items_by_sales OK (Fase 2), pero top_customers o purchase OOM.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('memory_limit', '128M');
set_time_limit(600);

$hostname = \Hyn\Tenancy\Models\Hostname::where('fqdn', 'grupomultiservicioshenavi.localhost')->first();
if (!$hostname) { die("Tenant no encontrado\n"); }

$tenancy = $app->make(\Hyn\Tenancy\Environment::class);
$tenancy->hostname($hostname);
$connection_handler = $app->make(\Hyn\Tenancy\Database\Connection::class);
$connection_handler->set($hostname);

DB::connection('tenant')->getPdo();

$month = '2026-06';
$d_start = '2026-06-01';
$d_end = '2026-06-30';
$establishment_id = DB::connection('tenant')->table('establishments')->first()->id ?? null;

$dsp = new \Modules\Dashboard\Helpers\DashboardSalePurchase();

function callPrivate($obj, $method, ...$args) {
    $r = new \ReflectionMethod($obj, $method);
    $r->setAccessible(true);
    return $r->invoke($obj, ...$args);
}

function measure_one($label, $cb) {
    DB::connection('tenant')->flushQueryLog();
    DB::connection('tenant')->enableQueryLog();
    $peak0 = memory_get_peak_usage(true);
    $t0 = microtime(true);
    $err = null;
    $result = null;
    try { $result = $cb(); } catch (\Throwable $e) { $err = $e; }
    $t1 = microtime(true);
    $peak = memory_get_peak_usage(true);
    $queries = DB::getQueryLog();
    if ($err) {
        echo sprintf("  ❌ FAIL %s: %s (%.0f ms)\n", $label, substr($err->getMessage(), 0, 80), ($t1-$t0)*1000);
        echo "     FILE:LINE: {$err->getFile()}:{$err->getLine()}\n";
    } else {
        echo sprintf("  ✅ OK %s: %.0f ms / %d queries / peak %.1f MB\n",
            $label, ($t1-$t0)*1000, count($queries), $peak/1048576);
    }
    return $err ? null : $result;
}

echo "=== Aislamiento sub-productos de data_aditional(mes 06/2026, memory_limit=128M) ===\n";

$ctx = ['est' => $establishment_id, 'start' => $d_start, 'end' => $d_end];

measure_one('data_aditional completo', function() use ($dsp, $ctx) {
    return $dsp->data([
        'establishment_id' => $ctx['est'],
        'period' => 'month',
        'month_start' => '2026-06',
        'month_end' => '2026-06',
        'date_start' => $ctx['start'],
        'date_end' => $ctx['end'],
        'enabled_move_item' => false,
        'enabled_transaction_customer' => false,
        'no_take' => false,
        'page' => 1,
    ]);
});

echo "\n--- Sub-productos aislados ---\n";
$items = measure_one('items_by_sales(est, jun)', function() use ($dsp, $ctx) {
    return callPrivate($dsp, 'items_by_sales', $ctx['est'], $ctx['start'], $ctx['end'], false, false, 1);
});
if ($items) {
    echo "     items_by_sales rows: " . (is_countable($items) ? count($items) : 'n/a') . "\n";
}

$tc = measure_one('top_customers(est, jun)', function() use ($dsp, $ctx) {
    return callPrivate($dsp, 'top_customers', $ctx['est'], $ctx['start'], $ctx['end'], false);
});
if ($tc) {
    echo "     top_customers rows: " . (is_countable($tc) ? count($tc) : 'n/a') . "\n";
}

$pt = measure_one('purchase_totals(est, jun)', function() use ($dsp, $ctx) {
    return callPrivate($dsp, 'purchase_totals', $ctx['est'], $ctx['start'], $ctx['end']);
});
if ($pt) {
    echo "     purchase_totals keys: " . json_encode(array_keys((array)$pt)) . "\n";
}

echo "\n--- Conteo por mes en HENAVI ---\n";
$conn = DB::connection('tenant');
echo "SN jun-26: " . $conn->table('sale_notes')->whereBetween('date_of_issue',['2026-06-01','2026-06-30'])->count() . "\n";
echo "SN may-26: " . $conn->table('sale_notes')->whereBetween('date_of_issue',['2026-05-01','2026-05-31'])->count() . "\n";
echo "Doc jun-26: " . $conn->table('documents')->whereBetween('date_of_issue',['2026-06-01','2026-06-30'])->count() . "\n";
echo "Purchases jun-26: " . $conn->table('purchases')->whereBetween('date_of_issue',['2026-06-01','2026-06-30'])->count() . "\n";
