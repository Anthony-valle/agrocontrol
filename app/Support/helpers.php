<?php

if (!function_exists('agro_number')) {
    function agro_number(mixed $number, ?int $decimals = null, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        if (!is_numeric($number)) {
            return (string) $number;
        }

        $resolvedDecimals = $decimals;

        if ($resolvedDecimals === null || in_array($resolvedDecimals, [0, 2], true)) {
            $resolvedDecimals = 3;
        }

        return number_format((float) $number, $resolvedDecimals, $decimalSeparator, $thousandsSeparator);
    }
}