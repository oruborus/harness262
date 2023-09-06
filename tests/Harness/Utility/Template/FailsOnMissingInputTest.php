<?php

declare(strict_types=1);

use Oru\EcmaScript\Harness\Box\TestConfigFromStdinBox;

require './vendor/autoload.php';

(new TestConfigFromStdinBox())->unbox();

echo serialize(new RuntimeException('SUCCESS'));
