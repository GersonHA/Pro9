<?php

namespace App\Helpers;

class BarcodeHelper
{
    /**
     * Normaliza un valor de código(s) de barras:
     * - Elimina espacios al inicio/final y caracteres de control.
     * - Convierte separadores comunes (espacios, tabs, punto y coma,
     *   saltos de línea) en comas.
     * - Elimina comas duplicadas o al inicio/final.
     *
     * @param mixed $barcode
     * @return string|null
     */
    public static function normalize($barcode): ?string
    {
        if (is_null($barcode)) {
            return null;
        }

        $normalized = str_replace("\xC2\xA0", ' ', (string) $barcode);
        $withoutControlChars = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized);
        $normalized = is_null($withoutControlChars) ? $normalized : $withoutControlChars;
        $normalized = trim($normalized);

        $normalized = preg_replace('/[;\s]+/', ',', $normalized);
        $normalized = preg_replace('/,+/', ',', $normalized);
        $normalized = trim($normalized, ',');

        return $normalized === '' ? null : $normalized;
    }
}
