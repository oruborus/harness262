# PHP ECMAScript testing harness

This library aims to test ecma262 implementations.

## Usage

The supplied testing harness can be used to execute the [Test262](https://github.com/tc39/test262) described in [ECMA TR/104](http://ecma-international.org/publications/techreports/E-TR-104.htm).

Run the following command to execute the complete test suite. Using the jit is advised.
```bash
$ harness ./vendor/tc39/test262/test
```

### Testing

```bash
$ phpunit
$ infection
$ psalm 
```
