<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueToItemLotsGroup extends Migration
{
    public function up()
    {
        // Merge duplicate rows (same code + item_id + date_of_due) before adding the constraint
        $duplicates = DB::table('item_lots_group')
            ->select('code', 'item_id', 'date_of_due', DB::raw('MIN(id) as keep_id'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('code', 'item_id', 'date_of_due')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('item_lots_group')
                ->where('id', $dup->keep_id)
                ->update(['quantity' => $dup->total_qty]);

            DB::table('item_lots_group')
                ->where('code', $dup->code)
                ->where('item_id', $dup->item_id)
                ->where('date_of_due', $dup->date_of_due)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->unique(['code', 'item_id', 'date_of_due'], 'item_lots_group_code_item_date_unique');
        });
    }

    public function down()
    {
        Schema::table('item_lots_group', function (Blueprint $table) {
            $table->dropUnique('item_lots_group_code_item_date_unique');
        });
    }
}
