--TEST--
"O:10:"TestConfig"..." | executeTest.php 
--SKIPIF--
<?php

declare(strict_types=1);

--STDIN--
O:34:"Oru\Harness\Test\GenericTestConfig":3:{s:40:" Oru\Harness\Test\GenericTestConfig path";s:0:"";s:43:" Oru\Harness\Test\GenericTestConfig content";s:0:"";s:47:" Oru\Harness\Test\GenericTestConfig frontmatter";O:42:"Oru\Harness\Frontmatter\GenericFrontmatter":1:{s:48:" Oru\Harness\Frontmatter\GenericFrontmatter data";a:3:{s:11:"description";s:1:"x";s:5:"flags";a:0:{}s:8:"includes";a:2:{i:0;E:47:"Oru\Harness\Contracts\FrontmatterInclude:assert";i:1;E:44:"Oru\Harness\Contracts\FrontmatterInclude:sta";}}}}
--FILE--
<?php

declare(strict_types=1);

const TEMPLATE_PATH = './src/Template/ExecuteTest';

file_put_contents(
    TEMPLATE_PATH . '.php',
    str_replace(
        '{{FACADE_PATH}}',
        './tests/Utility/Facade/create-test-facade.php',
        file_get_contents(realpath(TEMPLATE_PATH))
    )
);

require_once "src/Template/ExecuteTest.php";

unlink(TEMPLATE_PATH . '.php');

--EXPECTREGEX--
.*TestResult.*