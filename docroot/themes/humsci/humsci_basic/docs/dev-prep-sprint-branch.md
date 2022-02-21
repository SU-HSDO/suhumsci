# Prep Sprint Branch for Release

When a sprint has completed and is ready to go to the Stanford Web Team for review and release we need to prep our branch for review and release.

1. On JIRA, Create a JIRA Task Card to document the steps of this work for sprint tracking.
    - You'll want to list all the steps you walk through for prep in that card [similar to this](https://sparkbox.atlassian.net/browse/STN-968).
    - Add a "Steps to QA" comment to the JIRA card to help H&S understand the steps to QA the work involved in the approved work coming to release.
    - Add a Check Mark to each task as they are completed for a status on the card. The steps to complete are detailed below in the additional steps that follow.
1. On Github, the current Sprint Branch should have a Draft PR or PR started for it's eventual merge while working, but if it does not, we use a different template for Sprint Branch PR's.

    - Reference template below:

    ```markdown
    # NOT READY FOR REVIEW

    ## Summary
    - #1--1

    ### TASK LIST:
    - [ ] Add compiled CSS & JS
    - [ ] Run BackstopJS visual regression tests
    - [ ] Ensure Code Climate passes against the `develop` branch. Link here: https://codeclimate.com/github/SU-HSDO/suhumsci/pull/Code-Climate-URL-ID
    - [ ] Assign to Mike for review.

    ## Need Review By (Date)
    asap

    ## Urgency
    high

    ## Steps to Test
    1. `npm run test` should pass in the root folder
    2. If you are interested in the testing steps for associated PRs, please take a look at the individual PR's description and "Steps to Test" section.

    ## PR Checklist
    - [PR Checklist](https://gist.github.com/sherakama/0ba17601381e3adbe0cad566ad4d80a5)
    - [Sparkbox PR Checklist](../docs/SparkboxPRChecklist.md)
    ```

1. Add your work to the Draft PR in the list under "Summary", use the Pull Request number (#1001) to easily pull up a linked option for your list.
1. Ensure that the branch is passing Code Climate against the `develop` branch.
1. Ensure the Code Climate URL is accurate in the Task List, you can find this URL by looking down at the Code Climate test in the testing checks at the bottom of the PR.
1. Ensure the branch is up to date with CSS and JS by running a `npm run build` on the branch, if it is not up to date push up the compiled CSS and JS.
1. Run BackstopJS on this work, steps for this can be [found here](https://github.com/SU-HSDO/suhumsci/tree/develop/docroot/themes/humsci/humsci_basic#visual-regression-testing).
    - This step requires the DEV environment to be on your current branch for BackstopJS to compare DEV and Staging against each other. You'll need to swap the [Acquia Cloud](https://accounts.acquia.com/sign-in) DEV environment over to the current branch build before doing this testing.
1. If all tests are passing you are good to change the header in the PR text to "Ready For Review", publish the Draft PR and assign the PR to Mike at Stanford Web Services for review.
1. Mike does receive a Github notification uppon assignment, but if you want you can also ping him in the `#sparkbox-sws-hs` Slack channel for confirmation.
