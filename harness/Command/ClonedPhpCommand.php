<?php

declare(strict_types=1);

namespace Oru\EcmaScript\Harness\Command;

use Oru\EcmaScript\Harness\Contracts\Command;

use function assert;
use function fclose;
use function fwrite;
use function implode;
use function ini_get_all;
use function json_decode;
use function json_encode;
use function proc_close;
use function proc_open;
use function str_replace;
use function stream_get_contents;

final readonly class ClonedPhpCommand implements Command
{
    private string $command;

    public function __construct(
        private string $suffix
    ) {
        $iniSettings = ini_get_all(details: false);

        $iniSettingsJson = str_replace('\\\\', '\\\\\\\\', json_encode($iniSettings));
        $code = <<<"EOF"
            <?php

            declare(strict_types=1);

            \$ini   = ini_get_all(details: false);
            \$given = json_decode('{$iniSettingsJson}', true);
            \$diff  = array_diff_assoc(\$given, \$ini);

            echo str_replace('\\\\', '\\\\\\\\', json_encode(\$diff));
            EOF;

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $process = proc_open('php', $descriptorspec, $pipes);

        fwrite($pipes[0], $code);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        assert($exitCode === 0);
        $output = json_decode($output);

        $commandParts = ['php'];
        foreach ($output as $entry => $setting) {
            $commandParts[] = "-d \"{$entry}={$setting}\"";
        }
        $commandParts[] = $suffix;

        $this->command = implode(' ', $commandParts);
    }

    public function __toString(): string
    {
        return $this->command;
    }
}
