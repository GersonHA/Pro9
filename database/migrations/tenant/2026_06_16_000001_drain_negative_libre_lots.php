<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class DrainNegativeLibreLots extends Migration
{
    /**
     * Drena los lotes LIBRE con cantidad negativa a 0 (ADR-0012).
     *
     * El LIBRE negativo representaba un déficit de sobreventa imputado a un lote
     * fantasma. Ese déficit ahora vive solo en item_warehouse.stock (negativo),
     * gobernado por stock_control, no en un lote. item_warehouse.stock ya es
     * correcto (lo decrementa updateStock en cada venta de forma independiente),
     * así que poner el LIBRE en 0 no destruye stock: solo elimina el espejo
     * redundante. El sync (LIBRE = max(0, stock − Σreales)) lo mantendría en 0 de
     * todos modos; esto deja slate limpio de inmediato.
     */
    public function up()
    {
        DB::table('item_lots_group')
            ->where('code', 'LIBRE')
            ->where('quantity', '<', 0)
            ->update(['quantity' => 0]);
    }

    /**
     * Irreversible: el valor negativo previo era un espejo redundante del déficit
     * que ya vive en item_warehouse.stock. No hay nada que restaurar.
     */
    public function down()
    {
        //
    }
}
