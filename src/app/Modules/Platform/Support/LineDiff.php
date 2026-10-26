<?php

namespace App\Modules\Platform\Support;

/**
 * Line-by-line difference between two texts (longest common subsequence),
 * for showing what an edit changed.
 */
final class LineDiff
{
    /** Texts longer than this are compared as "replaced" to keep it fast. */
    private const MAX_LINES = 400;

    /**
     * @return array<int, array{type: 'same'|'added'|'removed', text: string}>
     */
    public static function compare(string $old, string $new): array
    {
        $a = preg_split('/\R/', $old);
        $b = preg_split('/\R/', $new);

        if (count($a) > self::MAX_LINES || count($b) > self::MAX_LINES) {
            return [
                ...array_map(fn ($l) => ['type' => 'removed', 'text' => $l], $a),
                ...array_map(fn ($l) => ['type' => 'added', 'text' => $l], $b),
            ];
        }

        // lcs[i][j] = length of the common subsequence of a[i..] and b[j..]
        $n = count($a);
        $m = count($b);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $out = [];
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $out[] = ['type' => 'same', 'text' => $a[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $out[] = ['type' => 'removed', 'text' => $a[$i++]];
            } else {
                $out[] = ['type' => 'added', 'text' => $b[$j++]];
            }
        }
        while ($i < $n) {
            $out[] = ['type' => 'removed', 'text' => $a[$i++]];
        }
        while ($j < $m) {
            $out[] = ['type' => 'added', 'text' => $b[$j++]];
        }

        return $out;
    }
}
