<?php

use App\Debug\PrettyDumper;

/**
 * Debug helper function - thin wrapper delegating to PrettyDumper
 * @ignore
 */
function prettyDump($data = null, $die = false, $showStack = false)
{
    // Делегируем в единый источник истины
    $html = PrettyDumper::render($data, $die, $showStack);
    if ($html !== '') {
        echo $html;
    }
}
