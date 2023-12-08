Feature: harness
    In order to see the results of ecmascript test cases
    As a user
    I need to collect the test cases from files and execute them

    Background: Configuration is correct
        Given a Facade

    Scenario: Single test excecution
        Given a file named "test.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should pass
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache test.js test.js test.js test.js"
        Then I should see:
            """

            EcmaScript Test Harness

            ....                                                            4 / 4 (100%)

            Duration: %d:%d

            """

    Scenario: One error
        Given a file named "test.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should pass
            flags: [raw]
            ---*/
            """
        And a file named "error.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that is assumed to throw an error
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache test.js test.js error.js test.js test.js"
        Then it should pass with unordered steps:
            """

            EcmaScript Test Harness

            %p(E....)                                                           5 / 5 (100%)

            Duration: %d:%d

            There where error(s)!

            ERRORS:

            1: error.js
            %A

            """

    Scenario: One failure
        Given a file named "test.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should pass
            flags: [raw]
            ---*/
            """
        And a file named "fail.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that is assumed to fail
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache test.js test.js fail.js test.js test.js"
        Then it should pass with unordered steps:
            """

            EcmaScript Test Harness

            %p(F....)                                                           5 / 5 (100%)

            Duration: %d:%d

            There where failure(s)!

            FAILURES:

            1: fail.js
            %A

            """