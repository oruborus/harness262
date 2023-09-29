--TEST--
harness empty.js
--SKIPIF--
<?php

declare(strict_types=1);

// This test should be skipped if tc39/test262 could not be detected
--FILE--
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$facade = new \Tests\Harness\Utility\Facade\SuccessfulFacade();

$_SERVER['argv'][] = './tests/Harness/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = '--no-cache';

(new \Oru\EcmaScript\Harness\Harness($facade))->run($_SERVER['argv']);
--EXPECTF--

EcmaScript Test Harness

.                                                               1 / 1 (100%)

Duration: %d:%d