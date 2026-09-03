<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class PartNumber
{
    public const PREFIX = 'PN-';

    public const MAX_LENGTH = 100;

    public const PATTERN = '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/';

    public const DUPLICATE_MESSAGE = 'This Part Number is already assigned to another inventory item.';

    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', trim($value)) ?? '');
        $normalized = preg_replace('/-+/', '-', $normalized) ?? $normalized;

        return trim($normalized, '-');
    }

    public static function isValid(string $value): bool
    {
        $length = strlen($value);

        return $length >= 1
            && $length <= self::MAX_LENGTH
            && preg_match(self::PATTERN, $value) === 1;
    }

    public static function formatSequence(int $sequence): string
    {
        return sprintf('%s%06d', self::PREFIX, max(1, $sequence));
    }

    public static function maxNumericSequence(): int
    {
        $values = DB::table('inventory_items')
            ->where('part_number', 'like', self::PREFIX.'%')
            ->pluck('part_number');

        $max = 0;
        $pattern = '/^'.preg_quote(self::PREFIX, '/').'(\d+)$/';

        foreach ($values as $value) {
            if (is_string($value) && preg_match($pattern, $value, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max;
    }

    public static function peekNext(): string
    {
        return self::formatSequence(self::maxNumericSequence() + 1);
    }

    /**
     * Allocate the next unused PN-###### value.
     */
    public static function generate(): string
    {
        $sequence = self::maxNumericSequence() + 1;

        do {
            $candidate = self::formatSequence($sequence);
            $sequence++;
        } while (DB::table('inventory_items')->where('part_number', $candidate)->exists());

        return $candidate;
    }
}
