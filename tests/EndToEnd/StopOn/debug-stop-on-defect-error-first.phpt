--TEST--
harness empty.js error.js fail.js empty.js --debug --stop-on-defect
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
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/error.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/fail.js';
$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = '--debug';
$_SERVER['argv'][] = '--stop-on-defect';

(new \Oru\Harness\Harness($facade))->run($_SERVER['argv']);
--EXPECTF--

EcmaScript Test Harness

.E                                                              2 / 4 ( 50%)

Duration: %d:%d

There where error(s)!

ERRORS:

1: ./tests/EndToEnd/Fixtures/error.js
%A
