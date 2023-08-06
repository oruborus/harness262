<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestConfigFactory;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function array_map;
use function file_get_contents;
use function implode;
use function in_array;
use function is_string;
use function ltrim;
use function preg_split;
use function reset;
use function strpos;
use function strlen;
use function substr;
use function trim;

use const PHP_EOL;

final readonly class GenericTestConfigFactory implements TestConfigFactory
{
    private const HARNESS_BASE_DIRECTORY = './vendor/tc39/test262/harness/';

    /**
     * @return TestConfig[]
     */
    public function make(string $path): array
    {
        $content = file_get_contents($path)
            ?: throw new RuntimeException("Could not open `{$path}`");

        $metaData = $this->handleMetaData($content, $path);

        if (in_array('raw', $metaData['flags'], true)) {
            return [
                new GenericTestConfig(
                    path: $path,
                    content: $content,
                    flags: $metaData['flags'],
                    includes: [],
                    features: $metaData['features'],
                    negative: $metaData['negative']
                )
            ];
        }

        if (in_array('module', $metaData['flags'], true)) {
            return [
                new GenericTestConfig(
                    path: $path,
                    content: $content,
                    flags: $metaData['flags'],
                    includes: $metaData['includes'],
                    features: $metaData['features'],
                    negative: $metaData['negative']
                )
            ];
        }

        if (in_array('noStrict', $metaData['flags'], true)) {
            return [
                new GenericTestConfig(
                    path: $path,
                    content: $content,
                    flags: $metaData['flags'],
                    includes: $metaData['includes'],
                    features: $metaData['features'],
                    negative: $metaData['negative']
                )
            ];
        }

        if (in_array('onlyStrict', $metaData['flags'], true)) {
            return [
                new GenericTestConfig(
                    path: $path,
                    content: "\"use strict\";\n{$content}",
                    flags: $metaData['flags'],
                    includes: $metaData['includes'],
                    features: $metaData['features'],
                    negative: $metaData['negative']
                )
            ];
        }

        return [
            new GenericTestConfig(
                path: $path,
                content: $content,
                flags: $metaData['flags'],
                includes: $metaData['includes'],
                features: $metaData['features'],
                negative: $metaData['negative']
            ),
            new GenericTestConfig(
                path: $path,
                content: "\"use strict\";\n{$content}",
                flags: $metaData['flags'],
                includes: $metaData['includes'],
                features: $metaData['features'],
                negative: $metaData['negative']
            )
        ];
    }

    private function handleMetaData(string $content, string $path): array
    {
        $metaData = [];
        $start = strpos($content, '/*---');

        if ($start !== false) {
            $end = strpos($content, '---*/', $start)
                ?: throw new RuntimeException("Could not locate meta data end for file `{$path}`");

            $start += 5;

            $meta = substr($content, $start, $end - $start);
            $meta = preg_split('/[\x{000A}\x{000D}\x{2028}\x{2029}]/u', $meta);
            $meta = array_filter($meta, static fn (string $line): bool => $line !== '');

            $line = reset($meta) ?: '';
            $identSize = strlen($line) - strlen(ltrim($line));

            $meta = array_map(static fn (string $line): string => substr($line, $identSize), $meta);
            $meta = implode(PHP_EOL, $meta);

            try {
                $metaData = Yaml::parse($meta);
            } catch (ParseException) {
            }
        }

        foreach ($metaData as $key => $item) {
            if (is_string($item)) {
                $metaData[$key] = trim($item);
            }
        }

        $metaData['flags'] ??= [];
        $metaData['includes'] ??= [];
        $metaData['features'] ??= [];
        $metaData['negative'] ??= [];

        $includes = ['assert.js', 'sta.js'];

        if (in_array('async', $metaData['flags'], true)) {
            $includes[] = 'doneprintHandle.js';
        }

        $metaData['includes'] = array_map(
            fn (string $include): string => static::HARNESS_BASE_DIRECTORY . $include,
            [...$includes, ...$metaData['includes']]
        );

        return $metaData;
    }
}
