<?php

$header = <<<EOF
Copyright (c) 2023, Felix Jahn

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.

SPDX-License-Identifier: BSD-3-Clause

@see https://github.com/oruborus/harness262
EOF;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS2.0' => true,
        // 'header_comment' => [
        //     'comment_type' => 'PHPDoc',
        //     'header' => $header,
        //     'location' => 'after_open',
        //     'separate' => 'both',
        // ],
    ])
    ->setFinder($finder);
