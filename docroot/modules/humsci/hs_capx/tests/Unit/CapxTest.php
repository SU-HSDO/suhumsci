<?php

namespace Drupal\Tests\hs_capx\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\hs_capx\Capx;
use Drupal\Tests\UnitTestCase;
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
   * @var \GuzzleHttp\Client
   */
  protected $guzzle;

  /**
   * @var \Drupal\hs_capx\Capx
   */
  protected $capx;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->guzzle = $this->createMock(Client::class);
    $cache = $this->createMock(CacheBackendInterface::class);
    $database = $this->createMock(Connection::class);

    $this->capx = new Capx($this->guzzle, $cache, $database);
  }

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

}
