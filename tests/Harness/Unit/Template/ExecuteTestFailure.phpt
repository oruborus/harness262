--TEST--
"O:8:"stdClass":1:{s:1:"a";i:132;}" | executeTest.php 
--SKIPIF--
<?php

declare(strict_types=1);

--STDIN--
O:8:"stdClass":1:{s:1:"a";i:132;}
--FILE--
<?php

declare(strict_types=1);

const TEMPLATE_PATH = './harness/Template/ExecuteTest';

file_put_contents(
    TEMPLATE_PATH . '.php',
    str_replace(
        '{{FACADE_PATH}}',
        './tests/Harness/Utility/Facade/create-test-facade.php',
        file_get_contents(realpath(TEMPLATE_PATH))
    )
);

require_once "harness/Template/ExecuteTest.php";

unlink(TEMPLATE_PATH . '.php');

--EXPECTREGEX--
.*RuntimeException.*