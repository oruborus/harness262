<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\Storage;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestConfigFactory;
use Oru\EcmaScript\Harness\Contracts\FrontmatterFlag;
use Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatter;
use Oru\EcmaScript\Harness\Test\Exception\MissingFrontmatterException;
use RuntimeException;

use function array_map;
use function implode;
use function in_array;
use function ltrim;
use function preg_split;
use function reset;
use function strlen;
use function substr;

use const PHP_EOL;
use const PREG_SPLIT_NO_EMPTY;

final readonly class GenericTestConfigFactory implements TestConfigFactory
{
    public function __construct(
        private Storage $storage
    ) {
    }

    /**
     * @return TestConfig[]
     */
    public function make(string $path): array
    {
        $content = $this->storage->get($path)
            ?? throw new RuntimeException("Could not open `{$path}`");

        $index = preg_match('/\/\*---(.*)---\*\//s', $content, $match);
        if ($index !== 1) {
            throw new MissingFrontmatterException('Provided test file does not contain a frontmatter section');
        }

        $meta = preg_split(
            pattern: '/[\x{000A}\x{000D}\x{2028}\x{2029}]/u',
            subject: $match[$index],
            flags: PREG_SPLIT_NO_EMPTY
        );

        $rawFrontmatter = '';
        if ($line = reset($meta)) {
            $identSize = strlen($line) - strlen(ltrim($line));

            $meta = array_map(static fn (string $line): string => substr($line, $identSize), $meta);
            $rawFrontmatter = implode(PHP_EOL, $meta);
        }

        $frontmatter = new GenericFrontmatter($rawFrontmatter);

        if (
            in_array(FrontmatterFlag::raw, $frontmatter->flags(), true)
            || in_array(FrontmatterFlag::module, $frontmatter->flags(), true)
            || in_array(FrontmatterFlag::noStrict, $frontmatter->flags(), true)
        ) {
            return [new GenericTestConfig($path, $content, $frontmatter)];
        }

        if (in_array(FrontmatterFlag::onlyStrict, $frontmatter->flags(), true)) {
            return [new GenericTestConfig($path, "\"use strict\";\n{$content}", $frontmatter)];
        }

        return [
            new GenericTestConfig($path, $content, $frontmatter),
            new GenericTestConfig($path, "\"use strict\";\n{$content}", $frontmatter)
        ];
    }
}
