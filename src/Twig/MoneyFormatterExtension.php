<?php

namespace App\Twig;

use NumberFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MoneyFormatterExtension extends AbstractExtension
{
    private ?NumberFormatter $formatter = null;

    public function getFilters(): array
    {
        return [
            new TwigFilter('money_rub', [$this, 'formatRub']),
        ];
    }

    public function formatRub(int|float|null $value): string
    {
        $amount = (float) $value;
        if (!is_finite($amount)) {
            $amount = 0.0;
        }

        $formatter = $this->getFormatter();
        $formatted = $formatter->formatCurrency($amount, 'RUB');

        if ($formatted === false) {
            return number_format($amount, 0, ',', ' ');
        }

        return $formatted;
    }

    private function getFormatter(): NumberFormatter
    {
        if ($this->formatter instanceof NumberFormatter) {
            return $this->formatter;
        }

        $formatter = new NumberFormatter('ru_RU', NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

        return $this->formatter = $formatter;
    }
}
