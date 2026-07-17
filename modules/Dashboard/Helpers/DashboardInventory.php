<?php

namespace Modules\Dashboard\Helpers;

use Modules\Inventory\Models\ItemWarehouse;
use Modules\Dashboard\Http\Resources\DashboardInventoryCollection;
use App\Models\Tenant\Establishment;

class DashboardInventory
{

    public function data($request)
    {
        $establishment_id = $request->establishment_id;
        $date_start = $request->date_start;
        $date_end = $request->date_end;
        // Default = config existente (5) mantiene compatibilidad con widget legacy
        // <inventory-data-table>. Widget moderno ExpiringLots.vue pide limit=20.
        $limit = $request->limit ?? config('tenant.items_per_page_simple_d_table');

        return $this->products_date_of_due($establishment_id, $date_start, $date_end, $limit);
    }


    private function products_date_of_due($establishment_id, $date_start, $date_end, $limit = null)
    {
        // Fallback al config si el llamador no pasa limit (legacy sin param)
        $limit = $limit ?? config('tenant.items_per_page_simple_d_table');

        if(!$establishment_id){
            $establishment_id = Establishment::select('id')->first()->id;
        }

        if ($date_start && $date_end) {
        $products = ItemWarehouse::whereHas('item',function($query) use($date_start,$date_end){

                        $query->whereNotIsSet();
                        $query->where('unit_type_id','!=', 'ZZ');
                        $query->whereBetween('date_of_due', [$date_start, $date_end]);
                        
                    })
                    ->whereHas('warehouse',function($query) use($establishment_id){
                        $query->where('establishment_id', $establishment_id);
                    })
                    ->paginate($limit);
        }else{
            $products = ItemWarehouse::whereHas('item',function($query){

                $query->whereNotIsSet();
                $query->where('unit_type_id','!=', 'ZZ');

            })
            ->whereHas('warehouse',function($query) use($establishment_id){
                $query->where('establishment_id', $establishment_id);
            })
            ->paginate($limit);
        }
                   
        return new DashboardInventoryCollection($products);
    }
 
}