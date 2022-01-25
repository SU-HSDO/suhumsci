<?php

namespace Drupal\hs_events_importer\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventsImporterForm.
 */
class EventsImporterForm extends ConfigFormBase {

  /**
   * URL Endpoint for getting categories.
   */
  const STANFORD_EVENTS_IMPORTER_XML = "https://events-legacy.stanford.edu/xml/drupal/v2.php";

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $guzzle;

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ClientInterface $client, CacheBackendInterface $cache) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->guzzle = $client;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'events_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hs_events_importer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $urls = $this->config('hs_events_importer.settings')->get('urls') ?: [];

    $form['extra_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Event URLs'),
      '#description' => $this->t('Enter the full url if available or use the fields below.'),
      '#default_value' => implode(PHP_EOL, $this->getExtraUrls($urls)),
    ];
    $urls = $this->getStanfordEventsUrls($urls);
    $form['url_set'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="urls-wrapper">',
      '#suffix' => '</div>',
    ];

    if (empty($form_state->get('num_urls'))) {
      $form_state->set('num_urls', $urls ? range(0, count($urls) - 1) : [0]);

      foreach ($urls as $delta => $url) {
        $defaults[$delta] = $this->getUrlDefaults($url);
      }
    }

    foreach ($form_state->get('num_urls') as $delta) {

      $form['url_set'][$delta] = [
        '#type' => 'details',
        '#title' => $this->t('URL @number', ['@number' => $delta + 1]),
        '#open' => TRUE,
      ];
      // Add a type of feed.
      $form['url_set'][$delta]['type'] = [
        '#type' => 'select',
        '#title' => $this->t("Event Group Option"),
        '#empty_option' => $this->t("- Select Option -"),
        '#default_value' => $defaults[$delta]['type'] ?? '',
        '#options' => [
          'organization' => $this->t("Organization"),
          'category' => $this->t("Category"),
          'featured' => $this->t('Featured'),
          'today' => $this->t('Today'),
        ],
      ];

      // The organization id (integer) as provided in the xml feed.
      $form['url_set'][$delta]['organization'] = [
        '#type' => 'select',
        '#title' => $this->t("Organization"),
        '#empty_option' => $this->t("- Select Organization -"),
        '#options' => $this->getOrgOptions(),
        '#default_value' => $defaults[$delta]['organization'] ?? '',
        '#states' => [
          'visible' => ["[name='url_set[$delta][type]']" => ['value' => 'organization']],
        ],
      ];

      $form['url_set'][$delta]['category'] = [
        '#type' => 'select',
        '#title' => $this->t("Category"),
        '#empty_option' => $this->t("- Select Category -"),
        '#options' => $this->getCatOptions(),
        '#default_value' => $defaults[$delta]['category'] ?? '',
        '#states' => [
          'visible' => ["[name='url_set[$delta][type]']" => ['value' => 'category']],
        ],
      ];

      $form['url_set'][$delta]['org_status'] = [
        '#type' => 'select',
        '#title' => $this->t("Event Status"),
        '#default_value' => $defaults[$delta]['org_status'] ?? '',
        '#options' => [
          'published' => $this->t("Published"),
          'unlisted' => $this->t("Unlisted"),
          'bookmarked' => $this->t("Bookmarked"),
        ],
        '#states' => [
          'visible' => ["[name='url_set[$delta][type]']" => ['value' => 'organization']],
        ],
      ];
      if (count($form_state->get('num_urls')) > 1) {
        $form['url_set'][$delta]['remove'] = [
          '#type' => 'submit',
          '#name' => "remove_$delta",
          '#value' => $this->t('Remove'),
          '#submit' => ['::removeCallback'],
          '#element_validate' => [],
          '#ajax' => [
            'callback' => '::addMoreCallback',
            'wrapper' => 'urls-wrapper',
          ],
        ];
      }
    }

    $form['add_one'] = [
      '#type' => 'submit',
      '#name' => 'add_one',
      '#value' => $this->t('Add one'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addMoreCallback',
        'wrapper' => 'urls-wrapper',
      ],
    ];
    return $form;
  }

  /**
   * Add one submit handler response.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $num_urls = $form_state->get('num_urls');
    $num_urls[] = count($num_urls) > 0 ? max($num_urls) + 1 : 0;
    $form_state->set('num_urls', $num_urls);
    $form_state->setRebuild();
  }

  /**
   * Add/remove more ajax callback.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Form element render array.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['url_set'];
  }

  /**
   * Remove one item form submit handler.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $num_urls = $form_state->get('num_urls');
    $removed_delta = $form_state->getTriggeringElement()['#parents'][1];
    $k = array_search($removed_delta, $num_urls);
    unset($num_urls[$k]);
    $form_state->set('num_urls', $num_urls);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $urls = array_filter(explode(PHP_EOL, str_replace("\r", '', $form_state->getValue('extra_urls'))));
    foreach ($urls as &$url) {
      $url = trim($url);
      $this->validateUrl($url, $form, $form_state);
    }

    foreach ($form_state->getValue('url_set') as $url_settings) {
      $urls[] = $this->getFullUrl($url_settings);
    }
    asort($urls);
    $urls = array_values(array_unique(array_filter($urls)));
    $form_state->setValue('urls', $urls);
  }

  /**
   * Get the full url for events-legacy.stanford.edu based on the selections.
   *
   * @param array $choices
   *   Keyed array of form values.
   *
   * @return string
   *   Events-legacy.stanford.edu URL.
   */
  protected function getFullUrl(array $choices) {
    // All our extra form fields are stored in _other.
    $type = $choices['type'] ?? NULL;
    $val = $choices[$type] ?? null;
    $extra = $choices['org_status'] ?? '';

    // Valid Data. Create a url for the uri column.
    if ($type == 'featured' || $type == 'today') {
      return static::STANFORD_EVENTS_IMPORTER_XML . '?' . $type;
    }
    if (!is_null($type) && !is_null($val)) {
      $url = static::STANFORD_EVENTS_IMPORTER_XML . '?' . $type . '=' . $val;
      // Organizations have extra options.
      if ($type == 'organization' && $extra) {
        $url .= '&' . $extra;
      }
      return $url;
    }
  }

  /**
   * Validate that the user entered values are xml feeds from stanford events.
   *
   * @param string $url
   *   Url to events-legacy.stanford.edu.
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function validateUrl($url, array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($url, TRUE)) {
      $form_state->setError($form['extra_urls'], $this->t('@url is not a valid url.', ['@url' => $url]));
      return;
    }

    $url_headers = get_headers($url, 1);
    $content_type_header = $url_headers['Content-Type'] ?? [];

    $is_xml = is_string($content_type_header) ? strpos($content_type_header, 'text/xml') !== FALSE : FALSE;

    if (is_array($content_type_header)) {
      foreach ($content_type_header as $value) {
        if (strpos($value, 'text/xml') !== FALSE) {
          $is_xml = TRUE;
          break;
        }
      }
    }

    if (!$is_xml) {
      $form_state->setError($form['extra_urls'], $this->t('@url is not an xml url.', ['@url' => $url]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()
      ->getEditable('hs_events_importer.settings')
      ->set('urls', $form_state->getValue('urls'))
      ->save();
    parent::submitForm($form, $form_state);
    Cache::invalidateTags(['migration_plugins']);

    // Add permission to execute importer.
    $role = $this->entityTypeManager->getStorage('user_role')
      ->load('site_manager');
    if ($role) {
      $role->grantPermission('import hs_events_importer migration');
      $role->save();
    }
  }

  /**
   * Get the organization options available form the events-legacy.stanford.edu.
   *
   * @return array
   *   Keyed array of org id => name.
   */
  protected function getOrgOptions() {
    $xml = $this->getEventsData('organization-list');
    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);

    $options = [];
    /** @var \DOMElement $org_element */
    foreach ($xpath->query('Organization') as $org_element) {
      $org_id = $xpath->query('organizationID', $org_element)
        ->item(0)->nodeValue;
      $org_name = $xpath->query('name', $org_element)
        ->item(0)->nodeValue;
      $options[$org_id] = $org_name;
    }
    asort($options);
    return $options;
  }

  /**
   * Get the Category options available form the events-legacy.stanford.edu.
   *
   * @return array
   *   Keyed array of category id => name.
   */
  protected function getCatOptions() {
    $xml = $this->getEventsData('category-list');
    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $xpath = new \DOMXPath($dom);

    $options = [];
    /** @var \DOMElement $org_element */
    foreach ($xpath->query('Category') as $org_element) {
      $org_id = $xpath->query('categoryID', $org_element)
        ->item(0)->nodeValue;
      $org_name = $xpath->query('name', $org_element)
        ->item(0)->nodeValue;
      $options[$org_id] = $org_name;
    }
    asort($options);
    return $options;
  }

  /**
   * Call the events API to get the requested query.
   *
   * @param string $query
   *   Category or organization lists.
   *
   * @return string
   *   XML response.
   */
  protected function getEventsData($query) {
    if ($cache = $this->cache->get("hs_events_importer:$query")) {
      return $cache->data;
    }

    $options = ['query' => [$query => '']];
    $response = $this->guzzle->get(self::STANFORD_EVENTS_IMPORTER_XML, $options);
    $this->cache->set("hs_events_importer:$query", (string) $response->getBody());

    return (string) $response->getBody();
  }

  /**
   * Parse the url and build an array with the query parts.
   *
   * @param string $url
   *   Events-legacy.stanford.edu url.
   *
   * @return array
   *   Keyed array of the query parameters for the url.
   */
  protected function getUrlDefaults($url) {
    // Break up the URL to get at the query strings.
    $parts = UrlHelper::parse($url);
    $parsed = [];
    // Pull apart the query strings and set them to keys for easy use.
    if (isset($parts['query'])) {
      $parsed = $parts['query'];
      $keys = array_keys($parts['query']);
      $parsed['type'] = array_shift($keys);
      $parsed['org_status'] = array_pop($keys);
    }

    return $parsed;
  }

  /**
   * Separate out the urls that arent targeting events-legacy.stanford.edu.
   *
   * @param array $urls
   *   Array of urls.
   *
   * @return array
   *   Array of urls.
   */
  protected function getExtraUrls(array $urls): array {
    foreach ($urls as $key => $url) {
      if (strpos($url, self::STANFORD_EVENTS_IMPORTER_XML) === 0) {
        unset($urls[$key]);
      }
    }
    return array_values($urls);
  }

  /**
   * Separate out the urls that do target events-legacy.stanford.edu.
   *
   * @param array $urls
   *   Array of urls.
   *
   * @return array
   *   Array of urls.
   */
  protected function getStanfordEventsUrls(array $urls): array {
    foreach ($urls as $key => $url) {
      if (strpos($url, self::STANFORD_EVENTS_IMPORTER_XML) !== 0) {
        unset($urls[$key]);
      }
    }
    return array_values($urls);
  }

}
