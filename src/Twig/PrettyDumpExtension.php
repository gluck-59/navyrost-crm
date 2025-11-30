<?php

namespace App\Twig;

use App\Debug\PrettyDumper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;

class PrettyDumpExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('prettyDump', [$this, 'prettyDump'], ['is_safe' => ['html']]),
        ];
    }

    public function prettyDump($data = null, $die = false, $showStack = false): string
    {
        // Единый источник истины — делегируем в PrettyDumper
        return PrettyDumper::render($data, $die, $showStack);
    }
}
