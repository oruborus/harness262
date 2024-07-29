Feature: Realm Isolation
    # Each test must be executed in a new [ECMAScript realm](https://tc39.github.io/ecma262/#sec-code-realms) dedicated to that test.
    # Unless configured otherwise (via the `module` flag), source text must be interpreted as [global code](https://tc39.github.io/ecma262/#sec-types-of-source-code).
    In order to conform to the interpreting rules
    As an interpreter
    I run tests independent of each other

    Background: Configuration is correct
        Given an Engine
        And a directory named "directory1"
        And a directory named "directory2"
        And a file named "directory1/empty.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that causes the engine to emit the current pid
            flags: [raw]
            ---*/
            """
        And a file named "directory2/empty1.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that causes the engine to emit the current object id of the engine (oid)
            flags: [raw]
            ---*/
            """
        And a file named "directory2/empty2.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that causes the engine to emit the current object id of the engine (oid)
            flags: [raw]
            ---*/
            """

    Scenario: Standard execution uses new process for each test case
        When I run "php bin/harness --no-cache directory1"
        Then a new process gets spawned

    Scenario: Debug execution uses new process for each test case
        When I run "php bin/harness --no-cache --debug directory2"
        Then a new engine is used