<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Cache;

use Oru\EcmaScript\Harness\Contracts\CacheRepository;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;

use function array_key_exists;
use function assert;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;

final readonly class FileCacheRepository implements CacheRepository
{
    /**
     * @var callable(string $string, ...mixed): string $hashFunction
     */
    private mixed $hashFunction;

    public function __construct(
        private string $path = './.harness/cache',

        /**
         * @var callable(string $string, ...mixed): string $hashFunction
         */
        mixed $hashFunction = null
    ) {
        $this->hashFunction = $hashFunction ?? md5(...);
    }

    public function get(TestConfig $config): ?TestResult
    {
        $hash     = ($this->hashFunction)(serialize($config));
        $filePath = "{$this->path}/{$hash}";

        if (!file_exists($filePath)) {
            return null;
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $contents = json_decode($contents, true);
        if (!is_array($contents)) {
            return null;
        }

        assert(array_key_exists('hash', $contents));
        assert(array_key_exists('usedFiles', $contents));
        assert(array_key_exists('result', $contents));
        assert(is_array($contents['usedFiles']));

        if ($hash !== $contents['hash']) {
            return null;
        }

        foreach ($contents['usedFiles'] as $path => $hash) {
            if (($this->hashFunction)(file_get_contents($path)) !== $hash) {
                return null;
            }
        }

        $result = unserialize($contents['result']);

        if (!$result instanceof TestResult) {
            return null;
        }

        return $result;
    }

    public function set(TestConfig $config, TestResult $result): void
    {
        if (!file_exists($this->path)) {
            \mkdir($this->path, 0777, true);
        }

        $hash         = ($this->hashFunction)(serialize($config));
        $filePath     = "{$this->path}/{$hash}";
        $resultString = serialize($result);

        $contents = ['hash' => $hash, 'result' => $resultString, 'usedFiles' => []];
        foreach ($result->usedFiles() as $usedFile) {
            $contents['usedFiles'][$usedFile] = ($this->hashFunction)(file_get_contents($usedFile));
        }

        file_put_contents($filePath, json_encode($contents));
    }
}
