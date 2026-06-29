<?php

namespace Modules\WhatsAppBot\Services;

class IntentClassifier
{
    public const INTENT_YES = 'yes';
    public const INTENT_NO = 'no';
    public const INTENT_UNCLEAR = 'unclear';

    // SOLO confirmaciones inequivocas. Palabras como "vale", "ya", "listo",
    // "claro", "perfecto", "va", "correcto" son conectores comunes en frases
    // que NO son confirmacion ("vale, entonces dejalo en piero" no es un si).
    // Mejor errar al lado de UNCLEAR (delega al LLM) que confirmar por error.
    private const YES_KEYWORDS = [
        'si', 'sí', 'sí.', 'si.', 'sip', 'sii', 'sisi', 'sisí', 'sii.',
        'ok', 'okey', 'okay', 'okk',
        'dale', 'dele',
        'confirmo', 'confirmar', 'confirmado',
        'hazlo', 'hazlo!', 'emite', 'emítelo', 'emitelo', 'emítela', 'emitela',
        'emitir', 'emítela!', 'emitirla',
        'adelante', 'procede', 'procedamos',
        'afirmativo',
        '👍', '✅', '👌',
    ];

    private const NO_KEYWORDS = [
        'no', 'no.', 'nop', 'nope', 'cancela', 'cancelar', 'cancelado',
        'espera', 'aguarda', 'aguanta', 'detente', 'alto', 'para',
        'mejor no', 'no por ahora', 'aún no', 'todavía no', 'todavia no',
        'negativo', 'olvídalo', 'olvidalo', '👎', '❌',
    ];

    public function classify(string $message): string
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return self::INTENT_UNCLEAR;
        }

        $isNegative = $this->matches($normalized, self::NO_KEYWORDS);
        $isAffirmative = $this->matches($normalized, self::YES_KEYWORDS);

        if ($isNegative && !$isAffirmative) {
            return self::INTENT_NO;
        }

        if ($isAffirmative && !$isNegative) {
            return self::INTENT_YES;
        }

        return self::INTENT_UNCLEAR;
    }

    private function normalize(string $message): string
    {
        $message = trim(mb_strtolower($message));
        $message = preg_replace('/\s+/u', ' ', $message);
        return $message;
    }

    private function matches(string $normalized, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $kw = preg_quote($keyword, '/');
            if (preg_match('/(^|\s|[[:punct:]])' . $kw . '($|\s|[[:punct:]])/u', $normalized)) {
                return true;
            }
        }
        return false;
    }
}
