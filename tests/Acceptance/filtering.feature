Feature: regex filtering
    In order to not execute all tests in a given set
    As a user
    I can use the `--include` and `--exclude` cli options to filter using regex.

    Background: Configuration is correct
        Given a Facade
        And a directory named "directory"
        And a file named "directory/empty.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should pass
            flags: [raw]
            ---*/
            """
        And a file named "directory/error.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that is assumed to throw an error
            flags: [raw]
            ---*/
            """
        And a file named "directory/fail.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that is assumed to fail
            flags: [raw]
            ---*/
            """

    Scenario: Only include files from a given list
        When I run 'php bin/harness --no-cache --debug --include "e(?:mpty)|(?:rror).*\.js" directory"'
        Then I should see:
            """

            EcmaScript Test Harness

            .E                                                              2 / 2 (100%)

            Duration: %d:%d

            There where error(s)!

            ERRORS:

            1: directory/error.js
            %A
            """

    Scenario: Invalid regex pattern for include leads to error message
        When I run 'php bin/harness --include "(" directory'
        Then I should see:
            """

            EcmaScript Test Harness

            The provided regular expression pattern is malformed.
            The following warning was issued:
            "Compilation failed: missing closing parenthesis at offset 1"

            """

    Scenario: Exclude files from a given list
        When I run 'php bin/harness --no-cache --debug --exclude "e(?:mpty)|(?:rror).*\.js" directory"'
        Then I should see:
            """

            EcmaScript Test Harness

            F                                                               1 / 1 (100%)

            Duration: %d:%d

            There where failure(s)!

            FAILURES:

            1: directory/fail.js
            %A
            """

    Scenario: Invalid regex pattern for exclude leads to error message
        When I run 'php bin/harness --exclude "(" directory'
        Then I should see:
            """

            EcmaScript Test Harness

            The provided regular expression pattern is malformed.
            The following warning was issued:
            "Compilation failed: missing closing parenthesis at offset 1"

            """

