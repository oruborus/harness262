<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Storage;

use Oru\EcmaScript\Harness\Contracts\Storage;
use Throwable;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use function mkdir;
use function serialize;
use function unserialize;

use const JSON_THROW_ON_ERROR;

final readonly class FileStorage implements Storage
{
    public function __construct(
        private string $basePath
    ) {
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    public function put(string $key, mixed $content): void
    {
        $prefixedKey = $this->basePath . '/' . $key;

        $serializedContent = serialize($content);

        $stringContent = json_encode($serializedContent, JSON_THROW_ON_ERROR);

        file_put_contents($prefixedKey, $stringContent);
    }

    public function get(string $key): mixed
    {
        $prefixedKey = $this->basePath . '/' . $key;

        if (!file_exists($prefixedKey)) {
            return null;
        }

        $stringContent = @file_get_contents($prefixedKey);
        if ($stringContent === false) {
            return null;
        }

        $content = unserialize(json_decode($stringContent, null, JSON_THROW_ON_ERROR));

        return $content;
    }
}
