# PHP ECMAScript testing harness

This library aims to test ecma262 implementations.

## Usage

The supplied testing harness can be used to execute the [Test262](https://github.com/tc39/test262) described in [ECMA TR/104](http://ecma-international.org/publications/techreports/E-TR-104.htm).

Run the following command to execute the complete test suite. Using the jit is advised.
```bash
$ harness ./vendor/tc39/test262/test
```

### Command-line options

#### `--debug`
Runs the testsuite in sequence and allows for step-debugging using xdebug or similar solutions.  
Caching is disabled for this setting.

#### `--no-cache`, `-n`
Disables caching of test results.

#### `--silent`, `-s`
Runs the testsuite without output.

#### `--verbose`, `-v`
Runs the testsuite with extended output.

#### `--concurrency <number of concurrent tests>`, `-c <number of concurrent tests>`
Sets the desired number of concurrent test cases to be run. The set value is clamped between one and the number of available logical cores on the host machine. 
This option has no effect if the `--debug` option is set.

#### `--include <pattern>`
Includes matching paths from the provided paths using the regular expression `<pattern>`.

#### `--exclude <pattern>`
Excludes matching paths from the provided paths using the regular expression `<pattern>`.

#### `--stop-on-error`
Stops the execution of the test suite after the first occurring error.

#### `--stop-on-failure`
Stops the execution of the test suite after the first occurring failure.

#### `--stop-on-defect`
Stops the execution of the test suite after the first occurring error or failure.

#### `--only-strict`, `--no-strict`, `--module`, `--async` and `--raw`
Providing one of these options will only execute test cases with the corresponding frontmatter flag. The `onlyStrict` and `noStrict` might be set implicitly (see [Interpreting Test262 Tests - strict mode](https://github.com/tc39/test262/blob/main/INTERPRETING.md#strict-mode)).
The options are mutually exclusive - providing two of those options will result in an empty test suite!

### Testing

```bash
$ phpunit
$ infection
$ psalm 
```
