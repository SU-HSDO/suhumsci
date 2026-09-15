# Algolia Search

Algolia is a hosted search service that adds relevance tuning, synonyms, facets, promoted results, and search analytics. On HSDP it is an opt-in backend, enabled per site by the H&S web team. It runs alongside the Search API database index and does not replace it.

## How It Works

Every site receives three pieces, all disabled:

- `search_api.server.hs_algolia`, a Search API server using the `search_api_algolia` backend
- `search_api.index.hs_algolia`, a Search API index attached to that server
- The `hs_algolia` module, which adapts the contributed `search_api_algolia` module to HSDP

A site with Algolia enabled has two Search API indexes:

| Index | Backend | Serves |
|---|---|---|
| `default_index` | Database | The `/search` page and header search box |
| `hs_algolia` | Algolia | Nothing yet. |

`default_index` stays on for every site. It is the only index that can serve private content, because its `node_grants` field and `content_access` processor filter results by the current user at query time, and Algolia has no equivalent. The two indexes also store different shapes: `default_index` holds one tokenized blob of rendered HTML, while `hs_algolia` holds flat, facetable attributes because Algolia does its own tokenizing and ranking.

The cost of enabling Algolia is that the site indexes its content twice. The database index updates on save. Algolia is batched through cron.

## What Is Indexed

Algolia records are queried from the browser with a public search-only key, so everything in the index is effectively public. The shipped index guarantees that:

- Published nodes only, enforced by the `entity_status` processor
- An allow-list of content types: Basic Page, Course, Event, Event Series, News, Person, Publications, and Research. New content types are not indexed until added to the list
- Output rendered as an anonymous visitor, so nothing behind a login can appear in a record
- Flat attributes for faceting: content type, site name, taxonomy term names, dates, person titles, image URLs, and the canonical page URL

Never indexed: Private Page content, unpublished content, node grants, and Training and Project content (which have no search indexing view display).

## Per-Site Configuration

Every site imports the same configuration from `config/default`. Values that differ per site are excluded from import by `config_ignore`, and credentials never enter configuration at all. See [Configuration Management](Config.md) for the conventions.

| Value | Where it lives |
|---|---|
| `search_api.server.hs_algolia:status` | Site database, excluded by `config_ignore` |
| `search_api.index.hs_algolia:status` | Site database, excluded by `config_ignore` |
| `search_api.index.hs_algolia:options.algolia_index_name` | Site database, excluded by `config_ignore` |
| Application ID and Admin API key | Per-site `secrets.settings.php` on Acquia, never in this repository |

The server edit form hides the credential fields, since they are set in `secrets.settings.php` and would otherwise appear empty.

> **Important:** Both entities ship disabled through two safeguards: the `hs_algolia` module's `config/install` on new sites, and a deploy hook in `hs_admin` on existing sites. Both are required. See [Ignoring a Single Key of New Configuration](Config.md#ignoring-a-single-key-of-new-configuration) for why.

> **Important:** Excluding a single key from import unlocks the entire Search API configuration form in production, because the read-only check has no key-level granularity. Anyone with `administer search_api` on a site with Algolia enabled can change the index datasource and processors through the admin UI. Restrict that permission to administrators.

## Enabling Algolia for a Site

You need an Algolia application with its Application ID, Admin API key, and Search-only API key before you start.

1. Add the credentials to the site's secrets file on Acquia. Each environment is a separate server, so create the file on each environment where Algolia should run. These are runtime overrides, so `drush config:export` cannot write them into this repository.

   ```bash
   /mnt/files/<APP>.<ENV>/<SITE_NAME>/secrets.settings.php

   # Example, for the archaeology site on production:
   /mnt/files/humscigryphon.prod/archaeology/secrets.settings.php
   ```

   Do not use the environment-wide `/mnt/files/<APP>.<ENV>/secrets.settings.php` one level up. It applies to every site on that environment.

   ```php
   $config['search_api.server.hs_algolia']['backend_config']['application_id'] = '<ALGOLIA_APPLICATION_ID>';
   $config['search_api.server.hs_algolia']['backend_config']['api_key'] = '<ALGOLIA_ADMIN_API_KEY>';
   ```

1. Enable the server, set the Algolia index name, and enable the index.

   ```bash
   drush @<SITE_NAME>.<ENV> config:set search_api.server.hs_algolia status true -y
   drush @<SITE_NAME>.<ENV> config:set search_api.index.hs_algolia options.algolia_index_name <ALGOLIA_INDEX_NAME> -y
   drush @<SITE_NAME>.<ENV> config:set search_api.index.hs_algolia status true -y

   # Example:
   drush @hs_sandbox.dev config:set search_api.index.hs_algolia options.algolia_index_name hs_sandbox -y
   ```

1. Run the initial index. Cron indexes in batches, so populate a new index directly rather than waiting.

   ```bash
   drush @<SITE_NAME>.<ENV> search-api:index hs_algolia
   ```

1. Confirm the records appear in the Algolia dashboard, and that no unpublished or Private Page content is among them.

> **Warning:** Enable a site only after a deploy has finished. The deploy hook that forces the Algolia configuration off runs once per site, and enabling a site partway through a deploy would be undone.

## Local Development Setup

Put local credentials in the site's local settings file at `docroot/sites/<SITE_NAME>/settings/local.settings.php`. Confirm `local.settings.php` is listed in `.gitignore` before you add anything to it. This file applies to one site only, so each local site can point at its own Algolia index.

```php
$config['search_api.server.hs_algolia']['backend_config']['application_id'] = '<ALGOLIA_APPLICATION_ID>';
$config['search_api.server.hs_algolia']['backend_config']['api_key'] = '<ALGOLIA_ADMIN_API_KEY>';
```

Do not put credentials in `keys/secrets.settings.php`. The `drush sws:keys` command overwrites that directory from the staging environment. This is a different file from the `secrets.settings.php` on Acquia described above, which is where a site's real credentials belong.

Use a disposable Algolia application for local and continuous integration work, never a production one.

### Record Size on Smaller Plans

Algolia's free plan rejects any record larger than 10KB, and basic pages built from paragraphs regularly exceed that. This setting trims a record's rendered HTML from the end until it fits:

```php
$settings['hs_algolia_trim_html'] = TRUE;
```

Content near the bottom of long pages stops matching. Use it for local and sandbox work only. On a production site, use a plan with a larger record limit or split oversized records with the `algolia_item_splitter` processor.

## Platform Customizations

The `hs_algolia` module changes how the contributed module indexes and removes content:

- **Deletions are processed on cron.** The contributed module records deletions in a database table and expects a separate drush command to clear it. `hs_algolia` clears that table on cron instead, removing records from Algolia in bulk. To process the queue ahead of the next scheduled cron, run `drush @<SITE_NAME>.<ENV> cron:run hs_algolia_cron`.
- **Unpublishing removes content from search.** Search API stops tracking an unpublished node but leaves its Algolia record in place until the next full reindex. `hs_algolia` queues the record for deletion when a published node is unpublished.
- **URLs use the site's canonical domain.** Cron builds URLs from the current request, which on Acquia is an internal hostname. `hs_algolia` rewrites them to the domain in the site's 301 redirect settings.
- **Taxonomy values are always arrays.** A reference field holding one term arrives as a string. A consistent shape keeps Algolia facets simple.
- **Tracking fields are removed** from each record, and the title is moved to the front so records are readable in the Algolia dashboard.
