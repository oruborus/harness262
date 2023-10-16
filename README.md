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

#### `--async` (deprecated)
Runs the testsuite in parallel using multiple php processes.
This option will be phased out in the future, as this is the intended standard behavior.

#### `--no-cache`, `-n`
Disables caching of test results.

#### `--silent`, `-s`
Runs the testsuite without output.

#### `--verbose`, `-v`
Runs the testsuite with extended output.

### Testing

```bash
$ phpunit
$ infection
$ psalm 
```
