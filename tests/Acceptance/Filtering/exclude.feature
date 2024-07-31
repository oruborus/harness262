Feature: regex filtering
    In order to not execute all tests in a given set
    As a user
    I can use the `--exclude` cli option to filter using regex.

    Background: Configuration is correct
        Given an Engine
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

