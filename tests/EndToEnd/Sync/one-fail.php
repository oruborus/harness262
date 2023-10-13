<?php

declare(strict_types=1);

require_once 'C:\Users\orubo\Code\harness262\tests\EndToEnd\Sync' . '/../bootstrap.php';

$facade = new \Tests\Utility\Facade\TestFacade();

$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/fail.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = '--no-cache';

(new \Oru\Harness\Harness($facade))->run($_SERVER['argv']);
