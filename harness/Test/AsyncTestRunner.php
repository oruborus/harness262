<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Fiber;
use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestRunner;
use Oru\EcmaScript\Harness\Loop\FiberTask;

final readonly class AsyncTestRunner implements TestRunner
{
    public function __construct(
        private TestRunner $runner,
        private Loop $loop,
    ) {
    }

    public function run(TestConfig $config): void
    {
        $this->loop->add(
            new FiberTask(
                new Fiber(
                    function () use ($config): void {
                        $this->runner->run($config);
                    }
                )
            )
        );
    }

    public function finalize(): array
    {
        $this->loop->run();

        return $this->runner->finalize();
    }
}
