--TEST--
harness Fixtures/ --exclude "e(?:mpty)|(?:rror).*\.js"
--SKIPIF--
<?php

declare(strict_types=1);

// This test should be skipped if tc39/test262 could not be detected
--FILE--
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$facade = new \Tests\Utility\Facade\TestFacade();

$_SERVER['argv'][] = './tests/EndToEnd/Fixtures/';
$_SERVER['argv'][] = '--no-cache';
$_SERVER['argv'][] = '--debug';
$_SERVER['argv'][] = '--exclude';
$_SERVER['argv'][] = 'e(?:mpty)|(?:rror).*\.js';

(new \Oru\Harness\Harness($facade))->run($_SERVER['argv']);
--EXPECTF--

EcmaScript Test Harness

F                                                               1 / 1 (100%)

Duration: %d:%d

There where failure(s)!

FAILURES:

1: ./tests/EndToEnd/Fixtures/fail.js
%A