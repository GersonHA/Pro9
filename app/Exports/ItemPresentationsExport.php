<?php

namespace App\Exports;

use App\Models\Tenant\ItemUnitType;
use App\Models\Tenant\PriceLabel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exporta las presentaciones (item_unit_types) a Excel para su migración/edición
 * masiva. Los precios se leen desde item_unit_type_prices (modelo nuevo) y se
 * mapean por posición del PriceLabel (1/2/3), cayendo a las columnas legacy
 * price1/2/3 solo si aún no hay fila migrada.
 */
class ItemPresentationsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    /**
     * Cache de posición de PriceLabel => id, resuelto una sola vez.
     *
     * @var \Illuminate\Support\Collection|null
     */
    protected $labelIdsByPosition = null;

    public function collection()
    {
        return ItemUnitType::with(['item', 'prices'])->get();
    }

    public function headings(): array
    {
        return [
            'CODIGO_PADRE_INTERNO',
            'UNIDAD_PRESENTACION',
            'DESCRIPCION',
            'FACTOR',
            'PRECIO_1',
            'PRECIO_2',
            'PRECIO_3',
            'PRECIO_DEFECTO',
            'CODIGO_BARRAS_HIJO',
        ];
    }

    /**
     * @param \App\Models\Tenant\ItemUnitType $row
     */
    public function map($row): array
    {
        return [
            $row->item->internal_id ?? 'SIN_CODIGO',
            $row->unit_type_id,
            $row->description,
            $row->quantity_unit,
            $this->priceForPosition($row, 1),
            $this->priceForPosition($row, 2),
            $this->priceForPosition($row, 3),
            $row->price_default,
            $row->barcode,
        ];
    }

    /**
     * Obtiene el precio de la presentación para la posición de PriceLabel indicada.
     * Prioriza el modelo nuevo (item_unit_type_prices); si no hay fila, cae al
     * campo legacy priceN para exportar datos aún no migrados.
     *
     * @param \App\Models\Tenant\ItemUnitType $row
     * @param int $position
     * @return float|int
     */
    protected function priceForPosition($row, int $position)
    {
        $labelId = $this->labelIdsByPosition()->get($position);

        if ($labelId) {
            $price = optional($row->prices->firstWhere('price_label_id', $labelId))->price;
            if (!is_null($price)) {
                return $price;
            }
        }

        // Fallback legacy: la columna price1/2/3 vive en item_unit_types.
        return $row->{'price' . $position} ?? 0;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    protected function labelIdsByPosition(): Collection
    {
        if (is_null($this->labelIdsByPosition)) {
            $this->labelIdsByPosition = PriceLabel::pluck('id', 'position');
        }

        return $this->labelIdsByPosition;
    }
}
