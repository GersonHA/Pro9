<?php

namespace Modules\Item\Observers;

use App\Models\Tenant\Item;
use Exception;

class ItemObserver
{
    public function saving(Item $item)
    {
        $text = [];
        if(!is_null($item->name) && $item->name !== '') {
            $text[] = $item->name;
        }
        if(!is_null($item->second_name) && $item->second_name !== '') {
            $text[] = $item->second_name;
        }
        if(!is_null($item->description) && $item->description !== '') {
            $text[] = $item->description;
        }
        if(!is_null($item->model) && $item->model !== '') {
            $text[] = $item->model;
        }
        if(!is_null($item->barcode) && $item->barcode !== '') {
            $text[] = $item->barcode;
        }
        if(!is_null($item->internal_id) && $item->internal_id !== '') {
            $text[] = $item->internal_id;
        }
        if(!is_null($item->category_id)) {
            $text[] = $item->category->name;
        }
        if(!is_null($item->brand_id)) {
            $text[] = $item->brand->name;
        }

        $item->text_filter = join(' ', $text);
    }

    /**
     * Protege el comodín Producto Libre (LIBRE-SYS): no se puede eliminar.
     */
    public function deleting(Item $item)
    {
        if ($item->internal_id === 'LIBRE-SYS') {
            throw new Exception("ACCESO DENEGADO: Este es un producto del sistema y no puede ser eliminado.");
        }
    }

    /**
     * Permite renombrar el comodín, pero NO cambiar su Código Interno (internal_id).
     */
    public function updating(Item $item)
    {
        if ($item->getOriginal('internal_id') === 'LIBRE-SYS' && $item->internal_id !== 'LIBRE-SYS') {
            throw new Exception("ACCESO DENEGADO: No puedes cambiar el Código Interno de este producto base.");
        }
    }
}
