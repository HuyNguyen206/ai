<?php

namespace App\Support;

class Vector
{
    /**
     * Calculate cosine similarity between two vectors.
     *
     * @param  array<float>  $a
     * @param  array<float>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        // Compare only overlapping dimensions.
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            // Dot product and magnitudes for cosine similarity.
            $dot += $a[$i] * $b[$i];
            $magA += $a[$i] * $a[$i];
            $magB += $b[$i] * $b[$i];
        }

        if ($magA == 0.0 || $magB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($magA) * sqrt($magB));
    }
}
