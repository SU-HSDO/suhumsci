
# Patches

All modifications to contributed projects and most modifications to Drupal core must be performed via patches. To ensure patch stability and reproducibility, all patches must be downloaded and stored locally in the repository. Only rarely should they be referenced by URL in `composer.json`.

## Patch Storage and Naming Conventions

- Store all patches in either `patches/core/` (for Drupal core) or `patches/contrib/` (for contributed modules/themes).
- **Naming conventions:**

  - For Drupal patches from merge requests: `project-[issue-number]-mr-[merge-request-number]-[YYYYMMDD].patch`
    - Example: `core-3202896-mr-9357-20250410.patch`
  - For Drupal patches from issues: `project-[issue-number]-[comment-number]-[YYYYMMDD].patch`
    - Example: `project-1234567-12-20250724.patch`
  - For manually created patches not tied to an issue: `project-[short-description]-[YYYYMMDD].patch`
    - Example: `core-fix-cache-bug-20250724.patch`
  - For non-Drupal patches: `project-[short-description]-[YYYYMMDD].patch` (same as manually created patches)
    - Example: `somevendor-fix-compatibility-20250724.patch`

*If a patch does not fit any of the above scenarios, use your best judgment for naming and documentation.*

## Downloading and Adding a Patch

1. **Find the patch or diff URL** (from a drupal.org issue or GitLab merge request):

   - Start at the relevant drupal.org issue page.
   - If there is a merge request, click through to the [Drupal GitLab](https://git.drupalcode.org/) merge request page.
     - On the merge request page, click the **Code** button to open the dropdown menu.
     - You will see options to download a `.patch` or `.diff` file. **Do not click to download**. Instead, right-click the desired option and copy the link address. This URL can be used with `wget`.
     - **Always use the `.diff` file** for Composer patching. The `.patch` file includes all commits and metadata, which can cause issues or unexpected results. The `.diff` file contains only the final changes, making it more reliable and easier to apply.
   - If there is no merge request, look for a patch file attached directly to the drupal.org issue. Download the patch file directly.
2. **Download the patch** to the appropriate directory:

   ```sh
   cd patches/core/   # or patches/contrib/
   wget <patch-or-diff-URL>
   mv <downloaded-file> <final-patch-name>.patch
   ```
   Example:
   ```sh
   cd patches/core/
   wget https://git.drupalcode.org/project/drupal/-/merge_requests/9357.diff
   mv 9357.diff core-3202896-mr-9357-20250410.patch
   ```
3. **Update `composer.json`** to reference the local patch. Use the following format for the patch entry:

   ```json
   "<issue link>: <brief description>": "patches/core/core-3202896-mr-9357-20250410.patch"
   ```
   Example:
   ```json
   "https://www.drupal.org/project/drupal/issues/3202896: Do not display oEmbed resource error to anonymous users": "patches/core/core-3202896-mr-9357-20250410.patch"
   ```
4. **Apply the patch:**
   - Run `composer install` to apply the patch.
   - Run `composer update --lock` to ensure `composer.lock` is in sync with `composer.json` after patch changes.
   - Commit the modified `composer.json`, `composer.lock`, and the new patch file.

## Updating or Replacing a Patch

When a patch needs to be updated (e.g., a new version is released or the issue is fixed differently):

1. Download the new patch as above, using the current date in the filename.
2. Update the reference in `composer.json` to point to the new patch file.
3. Remove the old patch file from the repository.
4. Run `composer install` and then `composer update --lock`, then commit the changes.

## Removing a Patch

When a patch is no longer needed (e.g., the fix is included upstream):

1. Remove the patch entry from `composer.json`.
2. Delete the patch file from the repository.
3. Run `composer install` and then `composer update --lock`, then commit the changes.

## Manually Created Patches

If you create a patch manually (not from an existing issue or merge request):

- If possible, open an issue in the relevant Drupal.org issue queue and follow the standard process above.
- If not, document the reason for the patch in the `composer.json` entry and keep the description clear and concise.

Refer to the [Patch Storage and Naming Conventions](#patch-storage-and-naming-conventions) section above for the correct naming format.

## Gotchas

- Composer can only patch files that are distributed with Composer packages. Some files (like Drupal core `.htaccess` and `robots.txt`) cannot be patched this way.
- There is a known quirk in the Drupal packaging system that makes it difficult to patch module and theme `.info.yml` files. If you have trouble applying a patch that modifies an info file, see this issue for a description and workaround: https://www.drupal.org/node/2858245
