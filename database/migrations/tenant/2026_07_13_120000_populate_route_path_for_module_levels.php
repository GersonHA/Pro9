<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Poblar route_name y route_path en module_levels para los niveles críticos
 * de Configuración y POS garage. Sin esto, la cascada "Ruta de inicio" del
 * form1.vue muestra todos los sub-options con path=null (porque la columna
 * existe pero nunca se pobló al crear los levels).
 *
 * Idempotente: usa `whereNull('route_path')` para no pisar valores existentes.
 *
 * Origen del bug: el form1.vue tab "Ruta de inicio" usa child.route_path con
 * fallback a getRoutePath(child.value). Sin route_path poblado, el admin no
 * puede asignar un start_route desde la UI — y los usuarios con
 * start_route='/pos/garage' heredado del input libre (commit Pro9 d62ae14d)
 * no se pueden corregir.
 */
return new class extends Migration {

    public function up(): void
    {
        $rows = [
            ['value' => 'configuration_advance', 'route_name' => 'tenant.advanced.index',    'route_path' => '/advanced'],
            ['value' => 'configuration_visual',  'route_name' => 'tenant.login_page',       'route_path' => '/login-page'],
            ['value' => 'pos_garage',            'route_name' => 'tenant.pos.garage',       'route_path' => '/pos/garage'],
            ['value' => 'configuration_company', 'route_name' => 'tenant.companies.create', 'route_path' => '/companies/create'],
        ];

        foreach ($rows as $row) {
            DB::table('module_levels')
              ->where('value', $row['value'])
              ->whereNull('route_path')
              ->update([
                  'route_name' => $row['route_name'],
                  'route_path' => $row['route_path'],
              ]);
        }
    }

    public function down(): void
    {
        // No-op: dejar las rutas pobladas. El usuario puede limpiarlas manualmente
        // desde SQL si necesita revertir.
    }
};