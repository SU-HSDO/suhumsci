<?php

namespace Drupal\Tests\hs_capx\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\hs_capx\Capx;
use Drupal\Tests\UnitTestCase;
use Drush\Log\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

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
  protected function setUp() {
    parent::setUp();

    $this->guzzle = $this->createMock(Client::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $database = $this->createMock(Connection::class);

    $logger = new LoggerChannelFactory();
    $logger->addLogger($this->createMock(Logger::class));
    $this->capx = new TestCapx($this->guzzle, $this->cache, $database, $logger);
  }

  /**
   * Test the static methods.
   */
  public function testStaticMethods() {
    $url = Capx::getWorkgroupUrl('test:group');
    $this->assertEquals('https://cap.stanford.edu/cap-api/api/profiles/v1?privGroups=TEST:GROUP&ps=1000', $url);

    $url = Capx::getOrganizationUrl('test', TRUE);
    $this->assertEquals('https://cap.stanford.edu/cap-api/api/profiles/v1?orgCodes=TEST&ps=1000&includeChildren=true', $url);
  }

  /**
   * Tests failed credentials is handled well.
   */
  public function testFailedConnection() {
    $this->guzzle->method('request')
      ->withAnyParameters()
      ->willThrowException(new ClientException('Failed!', $this->getMockForAbstractClass(RequestInterface::class)));

    $this->capx->setUsername($this->getRandomGenerator()->string());
    $this->capx->setPassword($this->getRandomGenerator()->string());
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
    $this->assertTrue($this->capx->testConnection());
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
