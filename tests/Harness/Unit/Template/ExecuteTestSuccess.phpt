--TEST--
"O:10:"TestConfig"..." | executeTest.php 
--SKIPIF--
<?php

declare(strict_types=1);

--STDIN--
O:45:"Oru\EcmaScript\Harness\Test\GenericTestConfig":3:{s:51:" Oru\EcmaScript\Harness\Test\GenericTestConfig path";s:0:"";s:54:" Oru\EcmaScript\Harness\Test\GenericTestConfig content";s:0:"";s:58:" Oru\EcmaScript\Harness\Test\GenericTestConfig frontmatter";O:53:"Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatter":1:{s:59:" Oru\EcmaScript\Harness\Frontmatter\GenericFrontmatter data";a:3:{s:11:"description";s:1:"x";s:5:"flags";a:0:{}s:8:"includes";a:2:{i:0;E:58:"Oru\EcmaScript\Harness\Contracts\FrontmatterInclude:assert";i:1;E:55:"Oru\EcmaScript\Harness\Contracts\FrontmatterInclude:sta";}}}}
--FILE--
<?php

declare(strict_types=1);

require_once "harness/Template/ExecuteTest.php";

--EXPECTREGEX--
.*TestResult.*