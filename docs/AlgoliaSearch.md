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
- Only content the anonymous user can view. Every item is checked against node access as the anonymous user before it is sent, so content restricted with `content_access` or per-node grants stays out even when published
- An allow-list of content types: Basic Page, Course, Event, Event Series, News, Person, Publications, and Research. New content types are not indexed until added to the list
- Output rendered as an anonymous visitor, so nothing behind a login can appear in a record
- Flat attributes for faceting: content type, site name, taxonomy term names, dates, person titles, image URLs, and the canonical page URL

Never indexed in `hs_algolia`: Private Page content, unpublished content, node grants, Project content (which has no search indexing view display), and Training content (which goes to the shared Training Hub index instead). See [Training Hub Index](#training-hub-index).

Restricting content that is already in Algolia removes it, but not instantly. The access check runs when an item is indexed, so a grant change has to put the item back in front of the indexer first. Three paths do that, and no manual step is needed on any of them:

| Restricting through | What re-tracks the content |
| --- | --- |
| The access widget on the node edit form (`content_access_simple`) | The node is saved, so Search API re-tracks it the usual way |
| `/node/<NID>/access` | `hs_algolia_content_access_per_node()` queues the record for removal immediately and re-tracks the item |
| `/admin/structure/types/manage/<TYPE>/access` | `hs_algolia_form_content_access_admin_settings_alter()` adds a handler that re-tracks every item of that content type |

The record leaves Algolia on the next `hs_algolia_cron` run. Worst case, the record stays public until that run, so on an urgent takedown unpublish the node or clear the index from the Algolia dashboard rather than waiting.

## Per-Site Configuration

Every site imports the same configuration from `config/default`. The values that differ per site are set on the Algolia settings form at `/admin/config/search/algolia` and excluded from import by `config_ignore`, so a configuration export never carries them. See [Configuration Management](Config.md) for the conventions.

| Value | Where it lives |
|---|---|
| Whether Algolia is on | `status` of the server and index entities, excluded by `config_ignore` |
| Application ID and Algolia index name | `hs_algolia.settings`, excluded by `config_ignore` |
| Write API key | A key entity created on the site, excluded by `config_ignore` (`key.key.*`) |

The credentials and index name are applied to the Search API server and index as runtime overrides. They never appear in those entities, and the server edit form hides the credential fields so nobody enters them there.

> **Important:** Both entities ship disabled. New sites get that from `config/default` when the site is installed. Existing sites import before the configuration exists, where the ignored `status` key is stripped and Drupal defaults it to enabled, so a deploy hook in `hs_admin` turns both back off. Do not remove that hook. See [Ignoring a Single Key of New Configuration](Config.md#ignoring-a-single-key-of-new-configuration) for why.

> **Important:** Excluding a single key from import unlocks the entire Search API configuration form in production, because the read-only check has no key-level granularity. Anyone with `administer search_api` on a site with Algolia enabled can change the index datasource and processors through the admin UI. Restrict that permission to administrators.

Saving the server or index from those unlocked forms would otherwise write the overridden credentials and index name into active configuration, and the next configuration export would commit the Algolia write key to the repository. `hs_algolia_search_api_server_presave()` and `hs_algolia_search_api_index_presave()` put the empty strings back on every save, so the values stay runtime-only whatever the admin UI does. The rest of the form is still editable, so the permission restriction above still matters.

## Enabling Algolia for a Site

You need an Algolia application and, from its API Keys page, the Application ID and an API key with write access. Create a key scoped to this site's index with the `addObject`, `deleteObject`, `deleteIndex`, and `settings` permissions rather than using the Admin API key.

1. Create a key entity to hold the API key at `/admin/config/system/keys/add`. Use key type **Authentication** and key provider **Encrypted Configuration** with the `real_aes` encryption profile, then paste the API key as the value. This stores the key encrypted rather than in plain text, and matches how `hs_shield_key` is stored.

1. Open `/admin/config/search/algolia`, fill in the Application ID, select the key, and set the Algolia index name. Use the site name, for example `archaeology`. Check **Enable Algolia search** and save.

1. Run the initial index. Cron indexes in batches, so populate a new index directly rather than waiting. Use **Index now** at `/admin/config/search/search-api/index/hs_algolia`, or:

   ```bash
   drush @<SITE_NAME>.<ENV> search-api:index hs_algolia

   # Example:
   drush @archaeology.prod search-api:index hs_algolia
   ```

1. Confirm the records appear in the Algolia dashboard, and that no unpublished or Private Page content is among them.

Each environment is a separate site database, so repeat these steps on each environment where Algolia should run.

To turn Algolia off, uncheck **Enable Algolia search** and save. This removes every record from the Algolia index.

> **Warning:** Enable a site only after a deploy has finished. The deploy hook that forces the Algolia configuration off runs once per site, and enabling a site partway through a deploy would be undone.

## Training Hub Index

The Training Hub lists Training content from several sites in one merged result list. Every participating site writes its Training content into one shared Algolia index, so results can be ranked, faceted and paginated together. Each site receives two more entities for this, both disabled:

- `search_api.server.hs_algolia_federated`, a server pointing at the dedicated Training Hub Algolia application
- `search_api.index.hs_algolia_trainings`, an index of Training content only, rendered through the Training `search_index` display

Each record's `objectID` is `<site_id>:<uuid>`, where `site_id` is the site directory name. A bare node UUID is not enough: sites cloned from a template, and content copied with `single_content_sync`, share UUIDs, so one site's training would overwrite another's. Every deletion `hs_algolia` queues for this index, on entity delete, unpublish or an access change, uses the same prefixed ID. The per-site index is still keyed on the bare UUID. Each record carries `site_name` for display and `site_id`, the site directory name, as a facet key that does not change when a site is renamed. Training contacts are never indexed: they are hidden on the `search_index` display and have no field on the index.

The published-only, anonymous-access and anonymous-rendering guarantees under [What Is Indexed](#what-is-indexed) apply to this index too, along with the platform customizations below.

Unlike the per-site index, the Application ID, write API key, search key and index name are the same on every participating site. They come from `$config` overrides in `docroot/sites/settings/algolia.settings.php`, not from the Algolia settings form, and are set on Acquia production only. The index name there is `hsdp_trainings`. Every other environment, including stage and dev, gets no credentials and no index name, so it cannot write to the Training Hub at all. A site there can have the index enabled, for example after a database copy from production. It sends nothing, queues no deletions, and reports items as processed so cron does not keep reloading them. The presave hooks blank these values on the federated server and index the same way they do for the per-site entities, and a deploy hook in `hs_admin` turns both entities off on existing sites after import.

> **Important:** The production write API key must be restricted in the Algolia dashboard to exactly these three indexes: `hsdp_trainings`, `hsdp_trainings_date_start_asc` and `hsdp_trainings_date_start_desc`. List them by name. Do not use an `hsdp_trainings*` wildcard, because it also matches the development indexes such as `hsdp_trainings_local`, in the same Algolia application.

Facet values are sent as editors entered them. Each site's editors maintain their own Training vocabularies, so keeping terms consistent across sites, so that they merge into one facet value, is a governance decision rather than something the index enforces.

Records indexed before the `<site_id>:<uuid>` key was introduced use the bare UUID and are not replaced by a re-index, so each copy would appear twice. Remove them with a clear, which deletes every record carrying the site's `site_id` under either key, then index again:

```bash
drush @<SITE_NAME>.<ENV> search-api:clear hs_algolia_trainings
drush @<SITE_NAME>.<ENV> search-api:index hs_algolia_trainings
```

Clearing this index removes only the current site's records. Search API clears an index when it is disabled, cleared with **Clear all indexed data** or `drush search-api:clear`, rebuilt, deleted, or moved to another server. The contributed backend would empty the whole Algolia index each time, deleting every other site's records. `hs_algolia` replaces it with a backend that deletes the records whose `site_id` matches the current site, which needs `site_id` declared for faceting in the Algolia dashboard, as described in [Training Hub Algolia Dashboard Setup](#training-hub-algolia-dashboard-setup). Until it is, a clear fails and Search API retries it as a pending server task. The per-site index is cleared as before.

> **Important:** A refused clear does not look like a failure. `drush search-api:clear` and **Clear all indexed data** still report that the index was cleared, because Search API queues the failed removal rather than passing the error on. After clearing the Training Hub index, check the site log for "cannot filter on site_id" and confirm no pending server task remains for `hs_algolia_trainings`. If one does, add `site_id` to the index's facets in the Algolia dashboard, and the task completes on the next cron run.

> **Important:** Node view displays are excluded by `config_ignore`, so a configuration import never creates the Training `search_index` display on an existing site. A deploy hook in `hs_admin` creates it. If the display is still missing or disabled, `hs_algolia` omits the rendered HTML from every record and logs a warning, because Drupal would otherwise render the default display, which shows contacts.

### Training Hub Site Requirements

Every participating site needs a canonical domain in its 301 redirect settings (`domain_301_redirect.settings:domain`). Each record links back to its own site, and without that domain the URL uses whichever host served the request, which on cron is an Acquia internal hostname and under drush is the drush alias host. Set the domain before indexing the site. While the Training Hub index is enabled and the domain is empty, the status report (`/admin/reports/status`) shows a warning.

### Training Hub Algolia Dashboard Setup

The Algolia dashboard is the source of truth for the Training Hub index settings. Nothing in the codebase applies them. Set them once on the production index, `hsdp_trainings`, and on any development index.

> **Important:** `site_id` must be listed in **Attributes for faceting**. Clearing the Training Hub index on a site deletes only the records whose `site_id` matches that site, and Algolia only filters on attributes declared for faceting. Without it, every clear is refused rather than risk emptying other sites' records, and Search API keeps retrying the removal as a pending server task.

Under **Configuration > Facets > Attributes for faceting**, declare:

| Attribute | Setting |
| --- | --- |
| `site_id` | Required. Any mode works; **filter only** is enough |
| `site_name` | Searchable |
| `training_name` | Searchable |
| `provider` | Searchable |
| `audience` | Searchable |
| `product` | Searchable |
| `unit` | Searchable |
| `delivery_method` | Facet |
| `nodate` | Filter only |

Sorting by start date needs a standard replica for each direction. The Search API backend picks the replica from the first sort as `<INDEX_NAME>_<FIELD>_<DIRECTION>`, so the names must match exactly:

| Replica | Ranking |
| --- | --- |
| `<INDEX_NAME>_date_start_asc` | `asc(date_start)` first, then the default ranking criteria |
| `<INDEX_NAME>_date_start_desc` | `desc(date_start)` first, then the default ranking criteria |

On production these are `hsdp_trainings_date_start_asc` and `hsdp_trainings_date_start_desc`. Create them as standard replicas, not virtual ones, under **Configuration > Replicas**, then set the same attributes for faceting on each replica as on the primary, and confirm them with the check below. Trainings without a date have no `date_start` attribute, so they sort last in both directions.

To check what an index currently has:

```bash
drush @<SITE_NAME>.<ENV> php:eval '$index = \Drupal\search_api\Entity\Index::load("hs_algolia_trainings"); $settings = $index->getServerInstance()->getBackend()->getAlgolia()->getSettings($index->getOption("algolia_index_name")); print_r([$settings["attributesForFaceting"] ?? NULL, $settings["replicas"] ?? NULL]);'
```

## Local Development Setup

Follow the same steps on the local site. For the per-site `hs_algolia` index, use a disposable Algolia application for local and continuous integration work, never a production one. The Training Hub works differently, as described below.

The Training Hub index gets no settings outside production. Local development uses a separate development index, such as `hsdp_trainings_local`, in the same Training Hub Algolia application as production. To work on it locally, set all four values in the site's `docroot/sites/<SITE_NAME>/settings/local.settings.php`:

```php
$config['search_api.server.hs_algolia_federated']['backend_config']['application_id'] = '<APPLICATION_ID>';
$config['search_api.server.hs_algolia_federated']['backend_config']['api_key'] = '<WRITE_API_KEY>';
$settings['hs_algolia_federated_search_key'] = '<SEARCH_API_KEY>';
$config['search_api.index.hs_algolia_trainings']['options']['algolia_index_name'] = 'hsdp_trainings_local';
```

> **Important:** The local index name must never be `hsdp_trainings`, which is the production Training Hub. Use a separate development write key, restricted in the Algolia dashboard to the development index and its replicas by name, such as `hsdp_trainings_local`, `hsdp_trainings_local_date_start_asc` and `hsdp_trainings_local_date_start_desc`, so a local site cannot write to production even by mistake.

Several local sites can share one development index, which is how the Training Hub is tested across sites. Give the development index the same [dashboard setup](#training-hub-algolia-dashboard-setup), with replica names built from its own name. If a site already tracked its Training content before the index name was set, re-index it:

```bash
drush @<SITE_NAME>.local search-api:reset-tracker hs_algolia_trainings
drush @<SITE_NAME>.local search-api:index hs_algolia_trainings
```

Local environments have no `REAL_AES_ENCRYPTION` value, so `global.settings.php` generates a random one on each request and anything stored with the **Encrypted Configuration** provider cannot be read back. Either set a fixed value in your local settings before creating the key:

```php
putenv('REAL_AES_ENCRYPTION=<ANY_32_CHARACTER_STRING>');
```

or use the plain **Configuration** provider locally, which stores the key unencrypted and is acceptable for a disposable Algolia application or a development key restricted to a development index.

### Record Size on Smaller Plans

Algolia's free plan rejects any record larger than 10KB, and basic pages built from paragraphs regularly exceed that. This setting trims a record's rendered HTML from the end until it fits:

```php
$settings['hs_algolia_trim_html'] = TRUE;
```

Content near the bottom of long pages stops matching. Use it for local and sandbox work only. On a production site, use a plan with a larger record limit or split oversized records with the `algolia_item_splitter` processor.

## Platform Customizations

The `hs_algolia` module changes how the contributed module indexes and removes content:

- **Restricted content is never sent.** An event subscriber drops any item the anonymous user cannot view and queues its record for removal. It applies only to indexes on an Algolia server, so the database index still serves private content to users who may see it.
- **Deletions are processed on cron.** The contributed module records deletions in a database table and expects a separate drush command to clear it. `hs_algolia` clears that table on cron instead, removing records from Algolia in bulk. To process the queue ahead of the next scheduled cron on one site, run `drush @<SITE_NAME>.<ENV> cron:run hs_algolia_cron --force`. To run it on every site, for example from an Acquia scheduled job, run `drush humsci:multisite:cron:run hs_algolia_cron --force` from the repository root on the target environment.
- **Unpublishing removes content from search.** Search API stops tracking an unpublished node but leaves its Algolia record in place until the next full reindex. `hs_algolia` queues the record for deletion when a published node is unpublished.
- **URLs use the site's canonical domain.** Cron builds URLs from the current request, which on Acquia is an internal hostname. `hs_algolia` rewrites them to the domain in the site's 301 redirect settings.
- **Taxonomy values are always arrays.** A reference field holding one term arrives as a string. A consistent shape keeps Algolia facets simple.
- **Tracking fields are removed** from each record, and the title is moved to the front so records are readable in the Algolia dashboard.
