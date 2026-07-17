<?php
/**
 * Script de medición de rendimiento del Dashboard Pro9
 * Mide los endpoints reales para "Mes 05/2026".
 *
 * Ejecutar con:
 *   docker exec pro9_app php measure_dashboard.php
 *
 * NOTA: Este script NO modifica nada - solo lee BD y mide tiempos.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('memory_limit', '1024M');
set_time_limit(600);

// Inicializar contexto tenant (hyn/multi-tenant 5.x)
$hostname = \Hyn\Tenancy\Models\Hostname::where('fqdn', 'grupomultiservicioshenavi.localhost')->first();
if (!$hostname) { die("Tenant no encontrado\n"); }

$tenancy = $app->make(\Hyn\Tenancy\Environment::class);
$tenancy->hostname($hostname);

// Configurar conexión tenant
$connection_handler = $app->make(\Hyn\Tenancy\Database\Connection::class);
$connection_handler->set($hostname);

$conn = \DB::connection('tenant');

$dd = new \Modules\Dashboard\Helpers\DashboardData();

$month_start = '2026-05';
$d_start = '2026-05-01';
$d_end = '2026-05-31';
$establishment_id = $conn->table('establishments')->first()->id ?? null;

echo "=== MEDICIÓN DASHBOARD PRO9 - HENAVI - Mayo 2026 ===\n";
echo "Documents totales: ".$conn->table('documents')->count()."\n";
echo "SaleNotes totales: ".$conn->table('sale_notes')->count()."\n";
echo "Documents mayo 2026: ".$conn->table('documents')->whereBetween('date_of_issue',[$d_start,$d_end])->count()."\n";
echo "SaleNotes mayo 2026: ".$conn->table('sale_notes')->whereBetween('date_of_issue',[$d_start,$d_end])->count()."\n";
echo "SaleNotes últimos 7d (2026-05-25 a 2026-05-31): ".$conn->table('sale_notes')->whereBetween('date_of_issue',['2026-05-25','2026-05-31'])->count()."\n";
echo "Establishment id: {$establishment_id}\n\n";

function measure($label, $cb) {
    \DB::connection('tenant')->flushQueryLog();
    \DB::connection('tenant')->enableQueryLog();
    $t0 = microtime(true);
    $result = null;
    try { $result = $cb(); } catch (\Throwable $e) {
        echo "   ❌ ERROR: ".substr($e->getMessage(),0,80)."\n";
        return null;
    }
    $t1 = microtime(true);
    $queries = \DB::connection('tenant')->getQueryLog();
    $ms = round(($t1-$t0)*1000);
    $nq = count($queries);
    $sqls = array_map(fn($q) => preg_replace('/\s+/',' ',$q['query']), $queries);
    $cnt = array_count_values($sqls);
    $dupes = array_filter($cnt, fn($c) => $c > 1);
    $time_sql = 0;
    foreach ($queries as $q) { $time_sql += $q['time']; }
    $php_time = round($ms - $time_sql);
    echo sprintf("⏱  %-45s | %6dms (sql=%dms) | %5d queries | %d duplicadas\n",
        $label, $ms, round($time_sql), $nq, count($dupes));
    foreach (array_slice($dupes,0,3,true) as $sql=>$n) {
        echo "   ⚠️  x{$n} (sql repetida): ".substr($sql,0,110)."...\n";
    }
    return ['ms'=>$ms,'queries'=>$nq,'dupes'=>$dupes];
}

// Helper para llamar métodos privados via Reflection
function callPrivate($obj, $method, ...$args) {
    $r = new \ReflectionMethod($obj, $method);
    $r->setAccessible(true);
    return $r->invoke($obj, ...$args);
}

echo "--- 1. Endpoint /dashboard/data (lo que se ejecuta al elegir Mes 05/2026) ---\n";
$totals = measure('data() COMPLETO', function() use ($dd, $establishment_id, $month_start) {
    return $dd->data([
        'establishment_id' => $establishment_id,
        'period' => 'month',
        'month_start' => $month_start,
        'month_end' => $month_start,
        'date_start' => $d_start ?? '2026-05-01',
        'date_end' => $d_end ?? '2026-05-31',
    ]);
});

echo "\n--- 2. Sub-métodos directos (descomposición de data()) ---\n";
measure('document_totals(est,mes05)', function() use ($dd, $establishment_id, $d_start, $d_end) {
    return callPrivate($dd, 'document_totals', $establishment_id, $d_start, $d_end);
});
measure('sale_note_totals(est,mes05)', function() use ($dd, $establishment_id, $d_start, $d_end) {
    return callPrivate($dd, 'sale_note_totals', $establishment_id, $d_start, $d_end);
});
measure('balance(est,mes05)', function() use ($dd, $establishment_id, $d_start, $d_end) {
    return callPrivate($dd, 'balance', $establishment_id, $d_start, $d_end);
});
measure('totals(est,mes05)', function() use ($dd, $month_start, $establishment_id) {
    return callPrivate($dd, 'totals', $establishment_id, '2026-05-01', '2026-05-31', 'month', $month_start, $month_start);
});
measure('getItems()', function() use ($dd) { return $dd->getItems(); });

echo "\n--- 3. Endpoint /dashboard/global-data ---\n";
measure('globalData(last_week)', function() use ($dd) {
    return $dd->globalData(['period'=>'last_week','date_start'=>'2026-05-25','date_end'=>'2026-05-31']);
});
measure('globalData(month=05)', function() use ($dd, $month_start) {
    return $dd->globalData(['period'=>'month','month_start'=>$month_start,'month_end'=>$month_start]);
});

echo "\n--- 4. Endpoints NUEVOS (widgets de Buho) ---\n";
measure('lowStock()', function() use ($dd) { return $dd->lowStock(); });
measure('monthGoal()', function() use ($dd) { return $dd->monthGoal(); });
measure('sunatStatus(mes05)', function() use ($dd) { return $dd->sunatStatus(['period'=>'month','month_start'=>'2026-05','month_end'=>'2026-05']); });
measure('paymentMethods(mes05)', function() use ($dd) { return $dd->paymentMethods(['period'=>'month','month_start'=>'2026-05','month_end'=>'2026-05']); });
measure('salesWeek()', function() use ($dd) { return $dd->salesWeek(); });
measure('cashFlow(months=6)', function() use ($dd) { return $dd->cashFlow([], 6); });
measure('debtors(limit=4)', function() use ($dd) { return $dd->debtors([], 4); });

echo "\n--- 5. DashboardKpi ---\n";
$dk = new \Modules\Dashboard\Helpers\DashboardKpi();
measure('kpi->data(mes05)', function() use ($dk) {
    return $dk->data(['period'=>'month','month_start'=>'2026-05','month_end'=>'2026-05']);
});
measure('kpi->monthlyComparison()', function() use ($dk) { return $dk->monthlyComparison(); });
measure('kpi->salesGrowth()', function() use ($dk) { return $dk->salesGrowth(); });

echo "\n--- 6. Otros widgets ---\n";
measure('DashboardStock->data()', function() use ($dd) { return (new \Modules\Dashboard\Helpers\DashboardStock())->data(request()); });
measure('DashboardInventory->data()', function() use ($dd) { return (new \Modules\Dashboard\Helpers\DashboardInventory())->data(request()); });

echo "\n--- 7. data_aditional (Productos más vendidos) ---\n";
$dsp = new \Modules\Dashboard\Helpers\DashboardSalePurchase();
measure('data_aditional COMPLETO (mes05)', function() use ($dsp, $establishment_id, $month_start, $d_start, $d_end) {
    return $dsp->data([
        'establishment_id' => $establishment_id,
        'period' => 'month',
        'month_start' => $month_start,
        'month_end' => $month_start,
        'date_start' => $d_start,
        'date_end' => $d_end,
        'enabled_move_item' => false,
        'enabled_transaction_customer' => false,
        'no_take' => false,
        'page' => 1,
    ]);
});
measure('items_by_sales(est,mes05)', function() use ($dsp, $establishment_id, $d_start, $d_end) {
    return callPrivate($dsp, 'items_by_sales', $establishment_id, $d_start, $d_end, false, false, 1);
});
measure('top_customers(est,mes05)', function() use ($dsp, $establishment_id, $d_start, $d_end) {
    return callPrivate($dsp, 'top_customers', $establishment_id, $d_start, $d_end, false);
});
measure('purchase_totals(est,mes05)', function() use ($dsp, $establishment_id, $d_start, $d_end) {
    return callPrivate($dsp, 'purchase_totals', $establishment_id, $d_start, $d_end);
});

echo "\n--- 8. utilities (endpoint que crashea en F12 con Mes 05/2026) ---\n";
measure('utilities(mes05,expenses=1)', function() use ($establishment_id, $month_start, $d_start, $d_end) {
    return (new \Modules\Dashboard\Helpers\DashboardUtility())->data([
        'establishment_id' => $establishment_id,
        'period'           => 'month',
        'month_start'      => $month_start,
        'month_end'        => $month_start,
        'date_start'       => $d_start,
        'date_end'         => $d_end,
        'enabled_expense'  => 1,
        'item_id'          => null,
    ]);
});

echo "\n--- 9. Cuello de botella específico: ¿por qué globalData es tan lento? ---\n";
\DB::connection('tenant')->flushQueryLog();
\DB::connection('tenant')->enableQueryLog();
$t0 = microtime(true);
$dd->globalData(['period'=>'last_week','date_start'=>'2026-05-25','date_end'=>'2026-05-31']);
$queries = \DB::connection('tenant')->getQueryLog();
$total_ms = round((microtime(true)-$t0)*1000);
echo "Total: {$total_ms}ms en ".count($queries)." queries\n";

// Top 20 queries más lentas
usort($queries, fn($a,$b) => $b['time'] <=> $a['time']);
echo "\nTop 10 queries más lentas:\n";
foreach (array_slice($queries, 0, 10) as $q) {
    $sql = preg_replace('/\s+/',' ',substr($q['query'],0,150));
    echo sprintf("  %.2fms  %s\n", $q['time'], $sql);
}

// Top 20 queries más frecuentes
$sqls = array_map(fn($q) => preg_replace('/\s+/',' ',$q['query']), $queries);
$cnt = array_count_values($sqls);
arsort($cnt);
echo "\nTop 10 queries más frecuentes:\n";
foreach (array_slice($cnt, 0, 10, true) as $sql=>$n) {
    echo "  x{$n}: ".substr($sql,0,150)."\n";
}

echo "\n=== FIN MEDICIÓN ===\n";
