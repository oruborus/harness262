Feature: async flag
    # The file harness/doneprintHandle.js must be evaluated in the test realm's global scope
    # prior to test execution.
    # The test must not be considered complete until the implementation-defined print function
    # has been invoked or some length of time has passed without any such invocation.
    # In the event of a passing test run, this function will be invoked with the string
    # 'Test262:AsyncTestComplete'.
    # If invoked with a string that is prefixed with the character sequence
    # Test262:AsyncTestFailure:, the test must be interpreted as failed.
    # The implementation is free to select an appropriate length of time to wait before
    # considering the test "timed out" and failing.
    In order to conform to the interpreting rules
    As an interpreter
    I evaluate async flagged test cases based on their print output

    Background: Configuration is correct
        Given an Engine

    Scenario: A passing test run
        Given a file named "passing-async.js" with:
            """
            // Copyright section
            /*---
            description: An async test that should pass
            flags: [async, raw]
            ---*/
            print('Test262:AsyncTestComplete');
            """
        When I run "php bin/harness --no-cache passing-async.js"
        Then I should see:
            """

            EcmaScript Test Harness

            .                                                               1 / 1 (100%)

            Duration: %d:%d

            """

    Scenario: A failing test run
        Given a file named "failing-async.js" with:
            """
            // Copyright section
            /*---
            description: An async test that should fail
            flags: [async, raw]
            ---*/
            print('Test262:AsyncTestFailure: Failure message');
            """
        When I run "php bin/harness --no-cache failing-async.js"
        Then I should see:
            """

            EcmaScript Test Harness

            F                                                               1 / 1 (100%)

            Duration: %d:%d

            There where failure(s)!

            FAILURES:

            1: failing-async.js
            %sAssertionFailedException: Failure message%s
            %A

            """

    Scenario: A test run that does not invoke `print`
        Given a file named "empty.js" with:
            """
            // Copyright section
            /*---
            description: An async test that should pass
            flags: [async, raw]
            ---*/
            """
        When I run "php bin/harness --no-cache empty.js"
        Then I should see:
            """

            EcmaScript Test Harness

            F                                                               1 / 1 (100%)

            Duration: %d:%d

            There where failure(s)!

            FAILURES:

            1: empty.js
            %sAssertionFailedException: %s
            %A

            """

    Scenario: A test run that throws a ThrowCompletion
        Given a file named "throwing-async.js" with:
            """
            // Copyright section
            /*---
            description: An async test that throws
            flags: [async, raw]
            ---*/
            print('Test262:AsyncTestComplete');

            throw new Error();
            """
        When I run "php bin/harness --no-cache throwing-async.js"
        Then I should see:
            """

            EcmaScript Test Harness

            F                                                               1 / 1 (100%)

            Duration: %d:%d

            There where failure(s)!

            FAILURES:

            1: throwing-async.js
            %sAssertionFailedException: %s
            %A

            """