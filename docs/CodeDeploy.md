# Code Deployment

1. Checkout latest code in the `develop` branch
1. Execute `compser install --prefer-source` to build all of the needed dependencies.
1. Commit any changes to the composer files.
1. Execute the blt command `blt deploy`
1. Answer the questions appropriately
   * For releases intended for staging or production environments, enter Yes to create a tag
   * Enter the appropriate tag name when asked
1. In the Acquia environment, choose the tag for the staging environment.
1. Drag and drop the files from the production environment into the staging environment
1. Drag and drop the databases from production environment into the staging environment
1. Verify the task logs in the dashboard for anything unexpected or errors
1. Back up all databases on the production site
1. Deploy the same tag to the production environment
1. Validate deployment was successful.
