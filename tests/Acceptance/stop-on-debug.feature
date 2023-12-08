Feature: stop on in debug mode
    In order to stop the execution of a test suite in debug mode
    As a user
    I can use the `--stop-on-*` cli options

    Background: Configuration is correct
        Given a Facade
        And a file named "empty.js" with:
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
        And a file named "fail.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that is assumed to fail
            flags: [raw]
            ---*/
            """

    Scenario: Stops on first occuring defect starting with an error
        When I run "php bin/harness --no-cache --debug --stop-on-defect empty.js error.js fail.js empty.js"
        Then I should see:
            """

            EcmaScript Test Harness

            .E                                                              2 / 4 ( 50%)

            Duration: %d:%d

            There where error(s)!

            ERRORS:

            1: error.js
            %A

            """

    Scenario: Stops on first occuring defect starting with a failure
        When I run "php bin/harness --no-cache --debug --stop-on-defect empty.js fail.js error.js empty.js"
        Then I should see:
            """

            EcmaScript Test Harness

            .F                                                              2 / 4 ( 50%)

            Duration: %d:%d

            There where failure(s)!

            FAILURES:

            1: fail.js
            %A

            """

    Scenario: Stops on first occuring error
        When I run "php bin/harness --no-cache --debug --stop-on-error empty.js fail.js error.js empty.js"
        Then I should see:
            """

            EcmaScript Test Harness

            .FE                                                             3 / 4 ( 75%)

            Duration: %d:%d

            There where error(s) and failure(s)!

            FAILURES:

            1: fail.js
            %A

            ERRORS:

            1: error.js
            %A

            """

    Scenario: Stops on first occuring failure
        When I run "php bin/harness --no-cache --debug --stop-on-failure empty.js error.js fail.js empty.js"
        Then I should see:
            """

            EcmaScript Test Harness

            .EF                                                             3 / 4 ( 75%)

            Duration: %d:%d

            There where error(s) and failure(s)!

            FAILURES:

            1: fail.js
            %A

            ERRORS:

            1: error.js
            %A

            """