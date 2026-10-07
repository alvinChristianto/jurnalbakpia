<?php

namespace App\Support;

use stdClass;

/**
 * Resolves the "isi" label for a transaction line.
 *
 * Lines written before the box-size variant was dropped from the bakpia master
 * carry a `box_varian` snapshot; lines written after it carry no variant at all.
 * Only the former get a label, so old receipts keep their "isi 8" / "isi 18"
 * annotation while new ones render like any other product.
 */
class LegacyIsiLabel
{
    /**
     * The variant values that have ever been written to a transaction line.
     *
     * `'8'` and `'18'` come from the earliest seed data and the legacy POS,
     * which stored the bare number rather than the `box_8` form.
     *
     * @var array<string, string>
     */
    private const VARIANTS = [
        'box_8' => 'isi 8',
        '8' => 'isi 8',
        'box_18' => 'isi 18',
        '18' => 'isi 18',
    ];

    /**
     * Attach the legacy `isi` label to a decoded transaction line.
     */
    public static function apply(object $line): object
    {
        $label = self::forVariant($line->box_varian ?? null);

        if ($label !== null) {
            $line->isi = $label;
        }

        return $line;
    }

    /**
     * Label every line of a decoded transaction, preserving array keys.
     *
     * @param  iterable<int, mixed>  $lines
     * @return array<int, mixed>
     */
    public static function applyAll(iterable $lines): array
    {
        $result = [];

        foreach ($lines as $key => $line) {
            $result[$key] = $line instanceof stdClass ? self::apply($line) : $line;
        }

        return $result;
    }

    /**
     * The label for a raw `box_varian` snapshot, or null when there is none.
     */
    public static function forVariant(mixed $variant): ?string
    {
        if (! is_string($variant) && ! is_int($variant)) {
            return null;
        }

        return self::VARIANTS[(string) $variant] ?? null;
    }
}
