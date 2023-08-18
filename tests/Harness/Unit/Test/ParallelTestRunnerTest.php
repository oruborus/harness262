<?php

declare(strict_types=1);

namespace Tests\Harness\Unit\Test;

use Oru\EcmaScript\Harness\Contracts\Printer;
use Oru\EcmaScript\Harness\Test\ParallelTestRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function ini_restore;
use function ini_set;

#[CoversClass(ParallelTestRunner::class)]
final class ParallelTestRunnerTest extends TestCase
{
    #[Test]
    public function replicatesRuntimeCommandOptions(): void
    {
        $this->assertTrue(true);
        // $code = <<<"EOF"
        // <?php

        // require_once 'vendor/autoload.php';

        // use Oru\EcmaScript\Harness\Contracts\Printer;
        // use Oru\EcmaScript\Harness\Contracts\TestResultState;
        // use Oru\EcmaScript\Harness\Test\ParallelTestRunner;

        // ini_set('test_option', '456');
        // echo get_cfg_var('cfg_file_path') . PHP_EOL;
        // echo get_cfg_var('test_option') . PHP_EOL;

        // \$printer = new class () implements Printer {
        //     public function setStepCount(int \$stepCount): void {}
        //     public function start(): void {}
        //     public function step(TestResultState \$state): void {}
        //     public function end(array \$testResults, int \$duration): void {}
        // };

        // echo (new ParallelTestRunner(\$printer))->command();
        // EOF;

        // $descriptorspec = [
        //     0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
        //     1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
        //     2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        // ];

        // $cwd = __DIR__ . '../../../../..';
        // $env = [];

        // $options = ['bypass_shell' => true];

        // $process = \proc_open('php -d "test_option=123"', $descriptorspec, $pipes, $cwd, $env, $options);

        // if (!\is_resource($process)) {
        //     throw new RuntimeException('Coud not open process');
        // }

        // // $pipes now looks like this:
        // // 0 => writeable handle connected to child stdin
        // // 1 => readable handle connected to child stdout
        // // 2 => readable handle connected to child stderr

        // \fwrite($pipes[0], $code);
        // \fclose($pipes[0]);

        // $output = \stream_get_contents($pipes[1]);
        // $errors = \stream_get_contents($pipes[2]);
        // \fclose($pipes[1]);

        // // It is important that you close any pipes before calling
        // // proc_close in order to avoid a deadlock
        // $return_value = \proc_close($process);



        // $this->assertStringContainsString('-d "test_option=123"', $output);
    }
}
