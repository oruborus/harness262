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

require_once "harness/Template/ExecuteTest.php";

--EXPECTREGEX--
.*RuntimeException.*