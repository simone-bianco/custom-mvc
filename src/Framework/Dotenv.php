<?php

declare(strict_types=1);

namespace Framework;

use Exceptions\EnvDoesntExistException;

class Dotenv
{
    /**
     * @param string $file
     * @return void
     * @throws EnvDoesntExistException
     */
    public function load(string $file): void
    {
        if (!file_exists($file)) {
            throw new EnvDoesntExistException();
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key ?? '');
            if (empty($key)) {
                continue;
            }
            $value ??= '';

            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, strlen($value) - 1);
            } else {
                $value = trim($value);
            }

            $_ENV[$key] = $value;
        }
    }
}