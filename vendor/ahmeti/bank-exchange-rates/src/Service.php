<?php

namespace Ahmeti\BankExchangeRates;

use DateTimeZone;

class Service
{
    protected $rates = [];

    protected function merge(array $items): void
    {
        foreach ($items as $item) {
            if (!array_key_exists($item['symbol'], $this->rates)) {
                $this->rates[$item['symbol']] = [];
            }

            $this->rates[$item['symbol']][] = $item;
        }
    }

    public static function timeZone(): DateTimeZone
    {
        return new DateTimeZone('Europe/Istanbul');
    }

    public static function toFloat(string $text): float
    {
        return (float)str_replace(['.', ','], ['', '.'], $text);
    }

    public static function replace(array $replaces, $symbol): string
    {
        return str_replace(array_keys($replaces), array_values($replaces), $symbol);
    }

    public function get(): array
    {
        $this->merge((new Garanti)->get());

        return $this->rates;
    }
}
