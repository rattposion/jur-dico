<?php
declare(strict_types=1);

namespace App\Core;

class JSONStream
{
    public static function iterateObjects(string $path): \Generator
    {
        $h = fopen($path, 'rb');
        if (!$h) return;
        $buf = '';
        $depth = 0;
        $inString = false;
        $escape = false;
        $started = false;
        while (!feof($h)) {
            $chunk = fread($h, 8192);
            for ($i = 0, $l = strlen($chunk); $i < $l; $i++) {
                $c = $chunk[$i];
                if (!$started) {
                    if ($c === '[') { $started = true; continue; }
                    continue;
                }
                if ($c === ']' && $depth === 0) break 2;
                if ($depth === 0 && ($c === ' ' || $c === "\n" || $c === "\r" || $c === "\t" || $c === ',')) continue;
                $buf .= $c;
                if ($inString) {
                    if ($escape) { $escape = false; continue; }
                    if ($c === '\\') { $escape = true; continue; }
                    if ($c === '"') { $inString = false; continue; }
                } else {
                    if ($c === '"') { $inString = true; continue; }
                    if ($c === '{') { $depth++; continue; }
                    if ($c === '}') { $depth--; if ($depth === 0) { $obj = json_decode($buf, true); if (is_array($obj)) yield $obj; $buf = ''; } continue; }
                }
            }
        }
        fclose($h);
    }
}

