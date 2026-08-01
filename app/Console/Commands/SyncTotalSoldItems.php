<?php

namespace App\Console\Commands;

use App\Models\Tenant\Item;
use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repuebla el contador `items.total_sold` a partir del histórico de `item_movement`.
 *
 * Se corre UNA vez tras portar la feature de "Orden de Búsqueda en POS": en las BD que se
 * restauraron desde dumps de pro8, `total_sold` viene congelado a la fecha del dump. De ahí en
 * adelante lo mantiene al día el increment de AttributePerItems en cada venta.
 *
 * Multi-tenant: inicializa cada website antes de tocar sus tablas (la conexión 'tenant' solo
 * existe con un tenant activo). Mismo patrón que items:fill-price-labels.
 *
 *   php artisan items:sync-sales                 → todos los tenants
 *   php artisan items:sync-sales --tenant=3,7    → solo esos websites
 */
class SyncTotalSoldItems extends Command
{
    protected $signature = 'items:sync-sales {--tenant=}';
    protected $description = 'Sincroniza el contador total_sold de los items basándose en el historial de item_movement';

    public function handle()
    {
        $tenants = explode(',', (string) $this->option('tenant'));

        $websites = (count($tenants) === 1 && $tenants[0] === '')
            ? Website::all()
            : Website::whereIn('id', $tenants)->get();

        if ($websites->isEmpty()) {
            $this->error('No se encontraron tenants para procesar.');
            return 1;
        }

        foreach ($websites as $website) {
            app(\Hyn\Tenancy\Environment::class)->tenant($website);
            $this->line("== Tenant #{$website->id} ({$website->uuid}) ==");
            $this->syncForCurrentTenant();
        }

        return 0;
    }

    private function syncForCurrentTenant(): void
    {
        $updated = 0;

        // chunkById para no cargar catálogos grandes (HENAVI ~2000 ítems) en memoria de golpe.
        Item::select('id')->chunkById(500, function ($items) use (&$updated) {
            foreach ($items as $item) {
                // Sumamos todas las salidas (cantidades negativas) del histórico de este producto.
                $total_salidas = DB::connection('tenant')
                    ->table('item_movement')
                    ->where('item_id', $item->id)
                    ->where('quantity', '<', 0)
                    ->sum('quantity');

                // update por query builder: no dispara timestamps (no toca updated_at).
                Item::where('id', $item->id)->update(['total_sold' => abs($total_salidas)]);
                $updated++;
            }
        });

        $this->info("  total_sold actualizado en {$updated} productos.");
    }
}
