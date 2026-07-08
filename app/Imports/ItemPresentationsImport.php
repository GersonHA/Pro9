<?php

namespace App\Imports;

use App\Helpers\BarcodeHelper;
use App\Models\Tenant\Item;
use App\Models\Tenant\ItemUnitType;
use App\Models\Tenant\ItemUnitTypePrice;
use App\Models\Tenant\PriceLabel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Importa masivamente presentaciones (item_unit_types) desde Excel.
 * El precio real vive en item_unit_type_prices y se mapea por posición del
 * PriceLabel (1/2/3); price1/2/3 en item_unit_types se conserva solo por
 * compatibilidad legacy.
 */
class ItemPresentationsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Saltar filas completamente vacías.
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $item = Item::where('internal_id', $row['codigo_padre_interno'])->first();

            // Sin item padre o sin unidad de presentación no se puede crear la fila.
            if (!$item || empty($row['unidad_presentacion'])) {
                continue;
            }

            $presentation = ItemUnitType::firstOrNew([
                'item_id' => $item->id,
                'unit_type_id' => $row['unidad_presentacion'],
            ]);

            $presentation->description = $row['descripcion'] ?? null;
            $presentation->quantity_unit = $row['factor'];
            $presentation->price_default = $row['precio_defecto'] ?? 1;
            $presentation->barcode = BarcodeHelper::normalize($row['codigo_barras_hijo'] ?? null);

            // Espejo legacy: los precios reales van a item_unit_type_prices (abajo),
            // price1/2/3 se mantiene por compatibilidad con la convención de Pro9.
            $presentation->price1 = (float) ($row['precio_1'] ?? 0);
            $presentation->price2 = (float) ($row['precio_2'] ?? 0);
            $presentation->price3 = (float) ($row['precio_3'] ?? 0);

            $presentation->save();

            // Upsert de precios en el modelo nuevo (fuente de verdad).
            // Cada posición mapea al PriceLabel de esa position; solo se guardan
            // precios > 0 (no se crean filas en cero).
            $pricesByPosition = [
                1 => $row['precio_1'] ?? null,
                2 => $row['precio_2'] ?? null,
                3 => $row['precio_3'] ?? null,
            ];

            foreach ($pricesByPosition as $position => $value) {
                if ((float) $value <= 0) {
                    continue;
                }

                $label = PriceLabel::where('position', $position)->first();
                if (!$label) {
                    continue;
                }

                ItemUnitTypePrice::updateOrCreate(
                    [
                        'item_unit_type_id' => $presentation->id,
                        'price_label_id' => $label->id,
                    ],
                    [
                        'price' => (float) $value,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
