--TEST--
harness Fixtures/ --exclude "("
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
$_SERVER['argv'][] = '(';

(new \Oru\Harness\Harness($facade))->run($_SERVER['argv']);
--EXPECTF--

EcmaScript Test Harness

The provided regular expression pattern is malformed.
The following warning was issued:
"Compilation failed: missing closing parenthesis at offset 1"
