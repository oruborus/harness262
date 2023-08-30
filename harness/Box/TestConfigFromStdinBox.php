<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Box;

use Oru\EcmaScript\Harness\Contracts\Box;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use RuntimeException;

use function fopen;
use function stream_get_contents;
use function unserialize;

/**
 * @implements Box<TestConfig>
 */
final readonly class TestConfigFromStdinBox implements Box
{
    private TestConfig $testConfig;

    public function __construct()
    {
        $input = fopen('php://stdin', 'r')
            ?: throw new RuntimeException('Could not open STDIN');

        $input = stream_get_contents($input)
            ?: throw new RuntimeException('Could not get contents of STDIN');

        $config = unserialize($input);

        if (!$config instanceof TestConfig) {
            throw new RuntimeException('STDIN did not contain a serialized `TestConfig` object');
        }

        $this->testConfig = $config;
    }

    public function unbox(): TestConfig
    {
        return $this->testConfig;
    }
}
