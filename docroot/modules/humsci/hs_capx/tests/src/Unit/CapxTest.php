<?php

namespace Drupal\Tests\hs_capx\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\hs_capx\Capx;
use Drupal\key\KeyInterface;
use Drupal\Tests\UnitTestCase;
use Drush\Log\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CapxTest.
 *
 * @covers \Drupal\hs_capx\Capx
 * @group hs_capx
 */
class CapxTest extends UnitTestCase {

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $guzzle;

  /**
   * @var \Drupal\Tests\hs_capx\Unit\TestCapx
   */
  protected $capx;

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->guzzle = $this->createMock(Client::class);


    $this->cache = $this->createMock(CacheBackendInterface::class);
    $database = $this->createMock(DatabaseConnection::class);
    $merge = $this->createMock(Merge::class);
    $merge->method('fields')->will($this->returnValue($merge));
    $merge->method('key')->will($this->returnValue($merge));
    $database->method('merge')->willReturn($merge);

    $config_object = $this->createMock(ImmutableConfig::class);
    $config_object->method('get')->willReturn($this->randomMachineName());

    $logger = new LoggerChannelFactory();
    $logger->addLogger($this->createMock(Logger::class));

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->willReturn($config_object);

    $key = $this->createMock(KeyInterface::class);
    $key->method('getKeyValue')->willReturn($this->randomMachineName());

    $key_storage = $this->createMock(EntityStorageBase::class);
    $key_storage->method('load')->willReturn($key);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($key_storage);
    $this->capx = new TestCapx($this->cache, $database, $logger, $config_factory, $entity_type_manager);

    $container = new ContainerBuilder();
    $container->set('http_client', $this->guzzle);
    $container->set('logger.factory', $logger);
    \Drupal::setContainer($container);
  }

  /**
   * Test the static methods.
   */
  public function testStaticMethods() {
    $url = Capx::getWorkgroupUrl('test:group');
    $this->assertEquals('https://cap.stanford.edu/cap-api/api/profiles/v1?privGroups=TEST:GROUP&filter=publications.featured:equals:true', $url);

    $url = Capx::getOrganizationUrl('test', TRUE);
    $this->assertEquals('https://cap.stanford.edu/cap-api/api/profiles/v1?orgCodes=TEST&includeChildren=true&filter=publications.featured:equals:true', $url);
  }

  /**
   * Tests failed credentials is handled well.
   */
  public function testFailedConnection() {
    $request = $this->createMock(RequestInterface::class);
    $response = $this->createMock(ResponseInterface::class);
    $this->guzzle->method('request')
      ->withAnyParameters()
      ->willThrowException(new ClientException('Failed!', $request, $response));

    $this->expectException(ClientException::class);
    $this->assertFalse($this->capx->testConnection());
  }

  /**
   * Tests successful credentials is handled well.
   */
  public function testSuccessfulConnection() {
    $this->guzzle->method('request')
      ->withAnyParameters()
      ->willReturn(new Response(200, [], '{}'));

    $this->capx->setUsername($this->getRandomGenerator()->string());
    $this->capx->setPassword($this->getRandomGenerator()->string());
    $this->assertEquals('{}', $this->capx->testConnection());
  }

  /**
   * Test the orgdata is returned.
   */
  public function testOrgData() {
    $this->guzzle->method('request')
      ->withAnyParameters()
      ->will($this->returnCallback([$this, 'guzzleRequestCallback']));
    $data = $this->capx->getOrgData();

    $this->assertArrayHasKey('name', $data);
    $this->assertArrayHasKey('alias', $data);
    $this->assertArrayHasKey('orgCodes', $data);
    $this->assertArrayHasKey('children', $data);
  }

  public function testCachedOrgData() {
    $this->cache->method('GET')
      ->withAnyParameters()
      ->will($this->returnCallback([$this, 'cacheGetCallback']));
    $this->testOrgData();

    $this->assertNotEmpty($this->capx->getAccessToken());
  }

  public function testSync() {
    $this->cache->method('GET')
      ->withAnyParameters()
      ->will($this->returnCallback([$this, 'cacheGetCallback']));

    $this->assertNull($this->capx->syncOrganizations($this->capx->getOrgData()));
  }

  /**
   * Callback function for guzzle mock service.
   *
   * @param string $method
   *   Request method.
   * @param string $url
   *   Request url.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   Guzzle's mock response.
   */
  public function guzzleRequestCallback($method, $url) {
    $body = [];
    switch ($url) {
      case 'https://authz.stanford.edu/oauth/token':
        $body = [
          'access_token' => $this->getRandomGenerator()->string(),
          'expires_in' => rand(1000, 9999),
        ];
        break;
      case 'https://api.stanford.edu/cap/v1/orgs/AA00':
        $body = json_decode(file_get_contents(__DIR__ . '/orgs.json'), TRUE);
        break;
    }

    return new Response(200, [], json_encode($body));
  }

  /**
   * Cache get callback to return desired test data.
   *
   * @param string $cid
   *   Cache ID.
   *
   * @return array|mixed|string
   *   Cached data.
   */
  public function cacheGetCallback($cid) {
    switch ($cid) {
      case 'capx:org_data':
        $data = json_decode(file_get_contents(__DIR__ . '/orgs.json'), TRUE);
        break;
      case 'capx:access_token':
        $data = ['access_token' => $this->getRandomGenerator()->string()];
        break;
    }
    return (object) ['data' => $data];
  }

}

/**
 * Class TestCapx to expose methods as public.
 */
class TestCapx extends Capx {

  /**
   * {@inheritdoc}
   */
  public function getOrgData() {
    return parent::getOrgData();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    return parent::getAccessToken();
  }

}
