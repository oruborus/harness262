<?php

declare(strict_types=1);

use Oru\EcmaScript\Harness\Assertion\GenericAssertionFactory;
use Oru\EcmaScript\Harness\Box\TestConfigFromStdinBox;
use Oru\EcmaScript\Harness\Printer\SilentPrinter;
use Oru\EcmaScript\Harness\Subprocess\SingleTestSubprocess;
use Oru\EcmaScript\Harness\Test\LinearTestRunner;

use function Oru\EcmaScript\Harness\getEngine;

require './vendor/autoload.php';

try {
    $result = (new SingleTestSubprocess(
        new LinearTestRunner(
            getEngine(),
            new GenericAssertionFactory(),
            new SilentPrinter()
        ),
        (new TestConfigFromStdinBox())->unbox(),
    ))->run();
} catch (Throwable $throwable) {
    $result = $throwable;
}

echo serialize($result);
