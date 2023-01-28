<?php

use Phpml\Math\Distance;

class Dice implements Distance
{
    public function distance(array $a, array $b): float
    {
        $count = count($a);
        if ($count !== count($b)) {
            throw new InvalidArgumentException('Size of given arrays does not match (' . $count . ' and ' . count($b) . ')');
        }
        $mul_sums = 0; // Jumlah perkalian elemen a dengan elemen b
        $a_sqr_sums = 0; // Jumlah kuadrat elemen a
        $b_sqr_sums = 0; // Jumlah kuadrat elemen b
        for ($i = 0; $i < $count; $i++) {
            $mul_sums += $a[$i] * $b[$i];
            $a_sqr_sums += $a[$i] ** 2;
            $b_sqr_sums += $b[$i] ** 2;
        }
        return $mul_sums / (0.5 * ($a_sqr_sums + $b_sqr_sums));
    }
}
