<?php

declare(strict_types=1);

namespace Oru\Harness\TestRunner;

use Fiber;
use Oru\Harness\Contracts\Loop;
use Oru\Harness\Contracts\TestConfig;
use Oru\Harness\Contracts\TestRunner;
use Oru\Harness\Loop\FiberTask;

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
