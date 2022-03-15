# Codeception Testing Information

To setup Codeception testing locally you will want to defer to the steps listed in the [Lando setup Readme](/lando/README.md#setup-for-local-codeception-testing). This section is intended to provide some extra information or tips for running or writing your tests locally.

* [Codeception Documentation](https://codeception.com/docs/01-Introduction)

## Running locally

* Codeception tests live in this directory `/tests/codeception` from there they split into there respective test type directories.
* The different types have different engines running their functionality, therefor are capable of different things
  * Acceptance tests, these tests run with a headless Chrome browser or Chromedriver and are designed to reproduce an acceptance tester’s actions in scenarios and run them automatically.
  * Functional tests, these tests run with Symfony BrowserKit to send requests to your app and doesn't make actual HTTP requests, these tests are intended to test functionality only and do not require a Chromedriver like Acceptance test do. These should run very fast in comparison.
* This info is listed in the Lando setup steps, but bears repeating for understanding here.
  * To run codeception tests run `lando blt codeception --group=install`. Or if you wish to run a single class/method add the annotation in the docblock `@group testme` and then run `lando blt codeception --group=testme`.

    ```php
    /**
    * Private collections tests description
    *
    * @group testme
    */
    ```

  * This allows you to focus on that one test you are writing or existing test your updating the steps for. It allows you to run that one group with one test vs. the entire install group that may eat up a lot of running time or cause multiple failures while waiting on your test specifically.

## Debugging

* The environments load some things differently, a test that passes locally may not pass on CircleCi. Example: The tests for the mobile menu have conditionals added within them because on page load the mobile menu may be open on one environment or closed on the other. The conditionals look for elements to trigger a click that may be needed for the next steps.
* Acceptance tests that may not be passing on CircleCi but are passing locally remember that these tests run fast and sometimes the browser does not keep up or the environment loads differently.
  * Use a `makeScreenshot`action in your test to pinpoint what the test is seeing before it fails, these can be found in the Artifacts section of the CircleCi test information. Also, locally at `/artifacts`. [Docs for makeScreenshot.](https://codeception.com/docs/modules/WebDriver#makeScreenshot)
  * You can also grab the HTML with a `makeHtmlSnapshot`action in your test to pinpoint what the test is seeing before it fails, these can be found in the Artifacts section of the CircleCi test information. Also, locally at `/artifacts`. [Docs for makeHtmlSnapshot.](https://codeception.com/docs/modules/WebDriver#makeHtmlSnapshot)
  * Another important tool when debugging is the `wait` or `waitForElement` actions, while `wait` allows you to give a browser pause time, `waitForElement` requires the test to stop and wait for a specific element to load before it will timeout and fail. [Docs for wait/waitForElement.](https://codeception.com/docs/modules/WebDriver#wait)
