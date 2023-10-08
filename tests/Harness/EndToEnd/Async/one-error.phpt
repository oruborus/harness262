--TEST--
harness empty.js --async
--SKIPIF--
<?php

declare(strict_types=1);

// This test should be skipped if tc39/test262 could not be detected
--FILE--
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$facade = new \Tests\Harness\Utility\Facade\TestFacade();

$_SERVER['argv'][] = './tests/Harness/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/Harness/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = './tests/Harness/EndToEnd/Fixtures/error.js';
$_SERVER['argv'][] = './tests/Harness/EndToEnd/Fixtures/empty.js';
$_SERVER['argv'][] = '--no-cache';
$_SERVER['argv'][] = '--async';

(new \Oru\EcmaScript\Harness\Harness($facade))->run($_SERVER['argv']);
--EXPECTREGEX--

EcmaScript Test Harness

(E\.\.\.)|(\.E\.\.)|(\.\.E\.)|(\.\.\.E)                                                            4 \/ 4 \(100%\)

Duration: [0-9]{2}:[0-9]{2}

There where error\(s\)!

ERRORS:

1:
(?s:.*)