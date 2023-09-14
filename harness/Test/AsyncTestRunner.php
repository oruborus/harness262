<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Test;

use Oru\EcmaScript\Harness\Contracts\Loop;
use Oru\EcmaScript\Harness\Contracts\TestConfig;
use Oru\EcmaScript\Harness\Contracts\TestResult;
use Oru\EcmaScript\Harness\Contracts\TestRunner;

final readonly class AsyncTestRunner implements TestRunner
{
    /**
     * @param Loop<TestResult> $loop 
     */
    public function __construct(
        private TestRunner $runner,
        private Loop $loop,
    ) {
    }

    public function run(TestConfig $config): void
    {
        $this->loop->addTask(
            function () use ($config): void {
                $this->runner->run($config);
            }
        );
    }

    /**
     * @return TestResult[]
     */
    public function finalize(): array
    {
        return $this->loop->run();
    }
}
