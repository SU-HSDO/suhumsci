# Config & Content Update

Periodically the H&S team wants to make several changes to the default installation configurations & content. This often
includes changes to the views, entity displays, entity forms, permissions, and some field settings. It also includes
default content changes.

## Preparation
H&S has been using the site [HS Sandbox](https://hs-sandbox-stage.stanford.edu) for this purpose. It is best to install
the site with a fresh installation and lock the staging environment to a tag to avoid any unwanted code deployments to
the staging environment.
1. `blt deploy --commit-msg="Defaults Update" --tag=DEFAULTS-[YYYY-MM-DD]`
