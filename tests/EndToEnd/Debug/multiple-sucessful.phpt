--TEST--
harness empty.js empty.js empty.js empty.js --debug
--SKIPIF--
<?php

declare(strict_types=1);

// This test should be skipped if tc39/test262 could not be detected
--FILE--
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$facade = new \Tests\Utility\Facade\TestFacade();

$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = '--debug';

(new \Oru\Harness\Harness($facade))->run($_SERVER['argv']);
--EXPECTF--

EcmaScript Test Harness

....                                                            4 / 4 (100%)

Duration: %d:%d