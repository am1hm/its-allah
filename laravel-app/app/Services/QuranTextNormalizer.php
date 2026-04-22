<?php

namespace App\Services;

class QuranTextNormalizer
{
    public function normalize(string $text): string
    {
        $value = trim($text);

        // Remove ayah end numbers and common decorative separators.
        $value = preg_replace('/[٠-٩0-9۝۞]+/u', '', $value) ?? $value;
        $value = str_replace("\u{00A0}", ' ', $value);

        // Collapse repeated whitespace (including non-breaking spaces).
        $value = preg_replace('/[\s\x{00A0}]+/u', ' ', $value) ?? $value;

        // Remove tatweel for normalized comparison.
        $value = str_replace('ـ', '', $value);

        // Unify common Quranic mark variants used across datasets.
        $value = str_replace(['ۡ', 'ْ'], '', $value); // sukun variants
        $value = str_replace(['ٰ'], '', $value); // dagger alif
        $value = str_replace(['ٱ', 'ا۟'], 'ا', $value); // alif variants
        $value = str_replace(['ى', 'ی'], 'ي', $value); // ya/alif maqsura normalization
        $value = str_replace(['ة'], 'ه', $value); // ta marbuta glyph variation in some exports

        // Normalize spaces around common Arabic punctuation.
        $value = preg_replace('/\s*([،؛:,.!?؟])\s*/u', '$1 ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        // Remove all remaining harakat and small high/low Quranic marks for comparison-only mode.
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D4}-\x{08FF}]/u', '', $value) ?? $value;

        // Normalize hamza-on-seat variants to standalone hamza in compare mode.
        $value = str_replace(['أ', 'إ', 'ؤ', 'ئ'], 'ء', $value);

        return trim($value);
    }
}

