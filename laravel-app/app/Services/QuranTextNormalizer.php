<?php

namespace App\Services;

class QuranTextNormalizer
{
    public function normalize(string $text): string
    {
        $value = trim($text);

        // Remove ayah end markers and decorative separators.
        $value = preg_replace('/[٠-٩0-9۝۞]+/u', '', $value) ?? $value;

        // Remove tatweel.
        $value = str_replace('ـ', '', $value);

        // Sukun variants.
        $value = str_replace(['ۡ', 'ْ'], '', $value);

        // Dagger alif.
        $value = str_replace('ٰ', '', $value);

        // Alif variants → standard alif.
        $value = str_replace(['ٱ', 'ا۟'], 'ا', $value);

        // Ya / alif maqsura → ya.
        $value = str_replace(['ى', 'ی'], 'ي', $value);

        // Ta marbuta → ha.
        $value = str_replace('ة', 'ه', $value);

        // Remove all harakat and Quranic diacritical marks (covers tanwin, shadda, kasra, etc.).
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{08D4}-\x{08FF}]/u', '', $value) ?? $value;

        // Normalize hamza-on-seat variants to standalone hamza.
        $value = str_replace(['أ', 'إ', 'ؤ', 'ئ'], 'ء', $value);

        // Rare glyph variants present in some Uthmani Unicode encodings.
        $value = str_replace(['ۥ', 'ۦ'], 'و', $value);
        $value = str_replace('ہ', 'ه', $value);
        $value = str_replace('ۿ', 'ي', $value);

        // KFGQPC encodes آخرة with madda (أٓ → ء after the hamza rule above),
        // which drops the following alif. Tanzil writes ءا explicitly.
        // Applying ءا → ء to both sides makes الءاخره == الءخره.
        $value = preg_replace('/ءا/u', 'ء', $value);

        // Strip all whitespace — makes بعد ما and بعدما compare as equal.
        $value = preg_replace('/[\s\p{Z}]+/u', '', $value);

        return $value;
    }
}
