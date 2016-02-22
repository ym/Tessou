#!/usr/bin/env php
<?php

define('STASH_PATH', '/www/infected');

function is_infected_code($code)
{
    foreach (token_get_all($code) as $token) {
        if (!is_array($token)) {
            continue;
        }

        if ($token[1] == '$qweezazb') {
            return true;
        }

        if ($token[1] == '$cb8c3eec') {
            return true;
        }
    }
    return false;
}

function stash_code($code)
{
    $key = sha1($code);
    $path = STASH_PATH . "/{$key}";
    file_put_contents($path, $code);

    return $key;
}

function fix_file($path)
{
    $changed = 0;

    $codeBlockHandler = function ($php = true) {
        return function ($matches) use (&$changed, $php) {
            list($code) = $matches;
            if (is_infected_code($code, $php)) {
                $changed++;
                $key = stash_code($code);
                return implode(
                    "\n",
                    [
                        "<?php /*",
                        "    Code is infected and removed by Tessou",
                        "    Hash: {$key}",
                        "*/ ?>"
                    ]
                );
            }
            return $code;
        };
    };

    $file = file_get_contents($path);
    $source = preg_replace_callback('/<\?php.+?\?>/ms', $codeBlockHandler(), $file);
    $source = preg_replace_callback('/<script[\s\S]*?>[\s\S]*?<\/script>/mi', $codeBlockHandler(false), $file);

    if ($changed) {
        echo "[FIXED, {$changed}] {$path}\n";
        file_put_contents($path, $source);
    } else {
        echo "[CLEAN] {$path}\n";
    }
}

function fix_directory($path)
{
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($objects as $object) {
        $path = $object->getPathName();
        $extname = pathinfo($path, PATHINFO_EXTENSION);
        if ($extname == 'php') {
            fix_file($path);
        }
    }
}

fix_directory($argv[1]);
