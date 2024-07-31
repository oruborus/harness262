Feature: harness
    In order to abort (infinitely) long running tests
    As a user
    I can rely on a default timeout value
    or can specify my own value using the `--timeout` option

    Background: Configuration is correct
        Given an Engine
        Given a file named "test.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should pass
            flags: [raw]
            ---*/
            """

    Scenario: Timeout while test executing when test runs longer than the standard timeout of 10 seconds
        Given a file named "timeout.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should run for 15 second
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache test.js test.js timeout.js test.js"
        Then I should see:
            """

            EcmaScript Test Harness

            ...T                                                            4 / 4 (100%)

            Duration: %d:%d

            TIMEOUTS:

            1: timeout.js
            %A


            """

    Scenario: No timeout aoccurs when the test does not run longer than the standard timeout of 10 seconds
        Given a file named "timeout.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should run for 1 second
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache test.js test.js timeout.js test.js"
        Then I should see:
            """

            EcmaScript Test Harness

            ....                                                            4 / 4 (100%)

            Duration: %d:%d

            """

    Scenario: Timeout while test executing when test runs longer than the configured timeout of 3 seconds
        Given a file named "timeout.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should run for 5 second
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache --timeout 3 test.js test.js timeout.js test.js"
        Then I should see:
            """

            EcmaScript Test Harness

            ...T                                                            4 / 4 (100%)

            Duration: %d:%d

            TIMEOUTS:

            1: timeout.js
            %A


            """

    Scenario: No timeout aoccurs when the test does not run longer than the configured timeout of 3 seconds
        Given a file named "timeout.js" with:
            """
            // Copyright section
            /*---
            description: An empty test that should run for 1 second
            flags: [raw]
            ---*/
            """
        When I run "php bin/harness --no-cache --timeout 3 test.js test.js timeout.js test.js"
        Then I should see:
            """

            EcmaScript Test Harness

            ....                                                            4 / 4 (100%)

            Duration: %d:%d

            """

    Scenario: Resets to default value and prints notice when invalid timeout is specified
        When I run "php bin/harness --no-cache --timeout NOTAPOSITIVEINT test.js"
        Then I should see:
            """

            EcmaScript Test Harness

            [NOTICE] Invalid timeout value provided - defaulting to 10 seconds

            .                                                               1 / 1 (100%)

            Duration: %d:%d

            """
