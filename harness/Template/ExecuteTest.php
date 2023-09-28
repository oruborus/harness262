<?php

declare(strict_types=1);

use Oru\EcmaScript\Harness\Assertion\GenericAssertionFactory;
use Oru\EcmaScript\Harness\Box\TestConfigFromStdinBox;
use Oru\EcmaScript\Harness\Contracts\Facade;
use Oru\EcmaScript\Harness\Printer\SilentPrinter;
use Oru\EcmaScript\Harness\Subprocess\SingleTestSubprocess;
use Oru\EcmaScript\Harness\Test\LinearTestRunner;

require './vendor/autoload.php';

/** @var Facade $facade */
$facade = (require './harness.php')();

try {
    $result = (new SingleTestSubprocess(
        new LinearTestRunner(
            $facade,
            new GenericAssertionFactory($facade),
            new SilentPrinter()
        ),
        (new TestConfigFromStdinBox())->unbox(),
    ))->run();
} catch (Throwable $throwable) {
    $result = $throwable;
}

echo serialize($result);
