<?php

namespace App\Debug;

final class PrettyDumper
{
    public static function render(mixed $data = null, bool $die = false, bool $showStack = false): string
    {
        $isLocal = (
            (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1', '0.0.0.0', 'localhost'], true))
            || in_array((string) getenv('SERVER_NAME'), ['localhost'], true)
        );

        if (!$isLocal) {
            return '';
        }

        $stack = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
        $out = "<pre style='text-align: left;font-size: 14px;font-family: Courier, monospace; background-color: #f4f4f4; width: fit-content; opacity: .9; z-index: 999;position: relative; padding: 10px'>";

        if ($showStack) {
            $out .= htmlspecialchars(print_r($stack, true));
        }

        //@TODO refucktor
        if (($stack[0]['function'] ?? null) === 'prettyDump') {
            $out .=  'prettyDump() из ' . ($stack[1]['file'] ?? '') . ' строка ' . ($stack[1]['line'] ?? '') . '<br>';
        } else {
            $src = $stack[1]['args'][0] ?? ($stack[1]['file'] ?? '');
            $out .=  'prettyDump() из ' . $src . ' строка ' . ($stack[1]['line'] ?? '') . ':<br>';
        }

        if (is_bool($data) || is_null($data) || empty($data)) {
            ob_start();
            var_dump($data);
            $dump = ob_get_clean();
            $out .= htmlspecialchars($dump ?? '');
        } else {
            $out .= htmlspecialchars(print_r($data, true));
        }

        $out .= '</pre>';

        if ($die) {
            echo $out;
            die;
        }

        return $out;
    }
}
