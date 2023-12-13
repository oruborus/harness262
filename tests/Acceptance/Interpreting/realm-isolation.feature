Feature: Realm Isolation
    # Each test must be executed in a new [ECMAScript realm](https://tc39.github.io/ecma262/#sec-code-realms) dedicated to that test.
    # Unless configured otherwise (via the `module` flag), source text must be interpreted as [global code](https://tc39.github.io/ecma262/#sec-types-of-source-code).
    In order to conform to the interpreting rules
    As a interpreter
    I run tests independent of each other

    Background: Configuration is correct
        Given a Facade that checks for realm isolation
        And a directory named "directory"
        And a file named "directory/empty.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should pass
            flags: [raw]
            ---*/
            """

    Scenario: Standard execution uses new process for each test case
        When I run "php bin/harness --no-cache directory"
        Then a new process gets spawned

    Scenario: Debug execution uses new process for each test case
        When I run "php bin/harness --no-cache --debug directory"
        Then a new process gets spawned