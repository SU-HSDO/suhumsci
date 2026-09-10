# Algolia Search

HSDP provides site search through Search API backed by a database index. Algolia is a hosted search service that adds relevance tuning, synonyms, facets, promoted results, and search analytics. It is available as an opt-in backend on a per-site basis.

Algolia does not replace the database index. Every site keeps its database-backed `/search` page whether or not Algolia is enabled.

## How It Fits the Platform

The platform ships three things to every site:

- `search_api.server.hs_algolia`, a Search API server using the `search_api_algolia` backend
- `search_api.index.hs_algolia`, a second index that runs alongside `default_index`
- The `hs_algolia` module, which adapts the contributed module to this platform

All three ship disabled. A site starts indexing only after the H&S web team adds Algolia credentials to that site's secrets file and enables the server and index.

### Why This Is a Second Index

`hs_algolia` does not replace `default_index`, and the `/search` page and header search box continue to query the database. Three reasons:

- `default_index` is the only index that can serve private content. It carries the `node_grants` field and the `content_access` processor, which filter results by the current user's access at query time. Algolia has no equivalent.
- The two indexes have different shapes. `default_index` stores one tokenized blob of rendered HTML with a boosted title. `hs_algolia` stores flat, facetable attributes because Algolia does its own tokenizing, stemming, and relevance ranking.
- The Algolia front end queries Algolia directly from the browser rather than through Search API views.

The cost is that a site with Algolia enabled indexes its content twice. The database index writes on save and is local; Algolia is batched through cron.

### Per-Site Configuration

Every site imports the same configuration from `config/default`, so the values that must differ per site are excluded from import by `config_ignore`. See [Configuration Management](Config.md) for the general conventions.

| Value | Where it lives |
|---|---|
| `search_api.server.hs_algolia:status` | Site database, excluded by `config_ignore` |
| `search_api.index.hs_algolia:status` | Site database, excluded by `config_ignore` |
| `search_api.index.hs_algolia:options.algolia_index_name` | Site database, excluded by `config_ignore` |
| Application ID and Admin API key | Per-site `secrets.settings.php`, never in this repository |

> **Important:** A per-key `config_ignore` pattern strips the key entirely when the configuration does not yet exist in a site's active storage, rather than preserving it. Because Drupal defaults a config entity's status to enabled, both entities would be created enabled on their first import. Two safeguards prevent this: the `hs_algolia` module creates both entities disabled from its `config/install` on new sites, and a deploy hook in `hs_admin` forces them back off on existing sites immediately after import. Do not remove either one.

> **Important:** Excluding a single key from import unlocks the entire configuration form in production, because the read-only check has no key-level granularity. Anyone with `administer search_api` on a site with Algolia enabled can change the index datasource, processors, and rendered-item roles through the admin UI. Restrict that permission to administrators.

## What Is and Is Not Indexed

Algolia records are queried from the browser using a public search-only key, so everything in an Algolia index is effectively public. The shipped index is built to guarantee that.

Indexed:

- Published nodes only, enforced by the `entity_status` processor
- An allow-list of content types: Basic Page, Course, Event, Event Series, News, Person, Publications, and Research. New content types are not indexed until they are added to the list
- Rendered output produced as an anonymous visitor, so nothing that requires a login can appear in a record
- Flat attributes for faceting: content type, site name, taxonomy term names, dates, person titles, image URLs, and the canonical page URL

Not indexed:

- Private Page content
- Unpublished content
- Training and Project content, which have no search indexing view display yet
- Node grants, which are never sent to a third-party service

> **Note:** Because private content is excluded, a future search across sites will not surface private content. Supporting that safely requires per-user filtered queries using signed Algolia keys, which is a separate piece of work.

## Enabling Algolia for a Site

You need an Algolia application with its Application ID, Admin API key, and Search-only API key before you start.

1. Add the credentials to the site's secrets file on Acquia at `<AH_FILES_ROOT>/<SITE_NAME>/secrets.settings.php`. Create the file if it does not exist.

   ```php
   $config['search_api.server.hs_algolia']['backend_config']['application_id'] = '<ALGOLIA_APPLICATION_ID>';
   $config['search_api.server.hs_algolia']['backend_config']['api_key'] = '<ALGOLIA_ADMIN_API_KEY>';
   ```

   These are runtime overrides. They never reach the site's active configuration, so `drush config:export` cannot leak them into this repository.

1. Enable the server and set the Algolia index name, then enable the index.

   ```bash
   drush @<SITE_NAME>.<ENV> config:set search_api.server.hs_algolia status true -y
   drush @<SITE_NAME>.<ENV> config:set search_api.index.hs_algolia options.algolia_index_name <ALGOLIA_INDEX_NAME> -y
   drush @<SITE_NAME>.<ENV> config:set search_api.index.hs_algolia status true -y

   # Example:
   drush @hs_sandbox.dev config:set search_api.index.hs_algolia options.algolia_index_name hs_sandbox -y
   ```

1. Run the initial index. Content is indexed through cron in batches, so populate a new index directly rather than waiting.

   ```bash
   drush @<SITE_NAME>.<ENV> search-api:index hs_algolia
   ```

1. Confirm the records appear in the Algolia dashboard, and that no unpublished or Private Page content is among them.

> **Warning:** Enable a site only after a deploy has finished. The deploy hook that forces the Algolia configuration off runs once per site, and enabling a site partway through a deploy would be undone.

## Local Development Setup

Local credentials go in `keys/secrets.settings.php`. Confirm `keys` is listed in `.gitignore` before you put anything in it.

This file is shared by every local multisite, so scope the values to one site or every local site will write to the same Algolia index.

```php
use Drupal\SwsDrush\Helpers\EnvironmentDetector;

if (EnvironmentDetector::getSiteName($site_path) === '<SITE_NAME>') {
  $config['search_api.server.hs_algolia']['backend_config']['application_id'] = getenv('ALGOLIA_APPLICATION_ID');
  $config['search_api.server.hs_algolia']['backend_config']['api_key'] = getenv('ALGOLIA_ADMIN_API_KEY');
}
```

Use a disposable Algolia application for local and continuous integration work, never a production one.

### Record Size on Smaller Plans

Algolia's free plan rejects any record larger than 10KB. Basic pages built from layouts and paragraphs regularly exceed that. Setting `hs_algolia_trim_html` trims the rendered HTML of a record from the end until it fits:

```php
$settings['hs_algolia_trim_html'] = TRUE;
```

This drops the tail of long pages, so content near the bottom stops matching. Use it for local and sandbox work. On a production site, either use a plan with a larger record limit or split oversized records with the `algolia_item_splitter` processor.

## Platform Customizations

The contributed `search_api_algolia` module handles indexing only. It does not provide a search results page. The `hs_algolia` module adds the behavior this platform needs.

- **Deletions happen immediately.** The contributed module records deletions in a database table and expects a separate scheduled task to run a drush command that clears them. This platform does not run that task, so records for deleted content would remain in Algolia indefinitely. `hs_algolia` deletes the record during request shutdown instead.
- **Unpublishing removes content from search.** Search API stops tracking an unpublished node but leaves its Algolia record in place until the next full reindex. `hs_algolia` clears the record as soon as a published node is unpublished.
- **URLs use the site's canonical domain.** Cron builds URLs from the current request, which on Acquia is an internal hostname. `hs_algolia` rewrites them to the domain configured in the site's 301 redirect settings.
- **Taxonomy values are always arrays.** A reference field holding one term arrives as a string. Sending a consistent shape keeps Algolia facets and the front end simple.
- **Tracking fields are removed** from each record, and the title is moved to the front so records are readable in the Algolia dashboard.

## Future Work

- A front-end search experience. The results page, filters, and facets query Algolia directly from the browser using the search-only key, typically through Algolia's InstantSearch library. This is new work rather than a change to the existing search view.
- Search across multiple sites, where two or more sites index into one shared Algolia application. Records are already keyed on node UUID rather than node ID, so content from different sites cannot overwrite one another, and each record carries the site name for attribution and filtering. What remains undecided is whether participation is opt-in per site, whether a shared index gets its own Algolia application, and how a site joins an existing group later.
- Indexing Training and Project content, which first requires a search indexing view display for each and a deploy hook to roll it out.
