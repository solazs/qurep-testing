<?php

namespace QuRePTestBundle\Tests\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use QuRePTestBundle\Tests\RestTestCase;

class DefaultControllerTest extends RestTestCase
{
    public static function setUpBeforeClass()
    {


        parent::setUpBeforeClass();

        $kernel = static::createKernel(['debug' => true]);
        $kernel->boot();
        /* @var $em EntityManager */
        $em = $kernel->getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        // Drop and recreate tables for all entities
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    static $users = [
      [
        "name" => "Teszt Elek",
        "username"    => "telek",
        "email"       => "telek@valami.hu",
      ],
      [
        "name" => "Pár Zoltán",
        "username"    => "parzol",
        "email"       => "parzol@e.info",
      ],
      [
        "name" => "Gyerekes Emil",
        "username"    => "gyeremil",
        "email"       => "gyeremil@e.info",
      ],
      [
        "name" => "Hibás Elemér",
        "username"    => "hibaselem",
      ],
    ];

    public function testEmpty()
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response);

        $this->assertEquals('{"data":[],"meta":{"limit":25,"offset":0,"count":0}}', $response->getContent());
    }

    /**
     * @depends testEmpty
     */
    public function testPost()
    {
        $client = static::createClient();
        $client->request(
          "POST",
          "/users",
          [],
          [],
          [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
          ],
          json_encode(self::$users[0])
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response, 201);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[0], $responseData["data"]);

        self::$users[0] = $responseData["data"];
    }

    /**
     * @depends testPost
     */
    public function testUpdate()
    {
        $putData = [
          "name" => "Teszt Elek Rendesen",
        ];
        $user = self::$users[0];
        $user["name"] = $putData["name"];
        self::$users[0] = $user;

        $client = static::createClient();
        $client->request(
          "POST",
          "/users/".self::$users[0]['id'],
          [],
          [],
          [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
          ],
          json_encode($putData)
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response, 201);

        $client = static::createClient();
        $client->request(
          "GET",
          "/users/".self::$users[0]['id']
        );

        $response = $client->getResponse();

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[0], $responseData["data"]);
    }

    /**
     * @depends testPost
     *
     */
    public function testDelete()
    {
        $client = static::createClient();
        $client->request(
          "DELETE",
          "/users/".self::$users[0]['id']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testDelete
     */
    public function testFailedPost()
    {
        $client = static::createClient();
        $client->request(
          "POST",
          "/users",
          [],
          [],
          [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
          ],
          json_encode(self::$users[3])
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response, 400);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEquals(
          '{"error":"Invalid Form","form":{"children":{"username":{},"name":{},"email":{"errors":["This'
          .' value should not be blank."]},"children":{},"parent":{}}},"code":400}',
          $response->getContent()
        );

        unset(self::$users[3]);
        self::$users = array_values(self::$users);
    }

    /**
     * @depends testFailedPost
     */
    public function testBulkUpdate()
    {
        foreach (self::$users as &$user) {
            if (array_key_exists("id", $user)) {
                unset($user['id']);
            }
            if (array_key_exists("created_at", $user)) {
                unset($user['created_at']);
                unset($user['updated_at']);
            }
        }

        $this->testPost();

        foreach (self::$users as &$user) {
            if (array_key_exists("created_at", $user)) {
                unset($user['created_at']);
                unset($user['updated_at']);
            }
        }

        $client = static::createClient();
        $client->request(
          "POST",
          "/users/bulk",
          [],
          [],
          [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
          ],
          json_encode(self::$users)
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response, 201);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityArrayEquals(self::$users, $responseData["data"]);

        self::$users = $responseData["data"];
    }

    /**
     * @depends testBulkUpdate
     */
    public function testExpand()
    {
        $client = static::createClient();
        $client->request(
          "POST",
          "/users/".self::$users[2]['id'],
          [],
          [],
          [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
          ],
          json_encode(["parent" => self::$users[0]['id']])
        );

        $response = $client->getResponse();

        $this->assertJsonResponse($response, 201);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[2], $responseData["data"]);

        $child = self::$users[2];
        $child['parent'] = self::$users[0];
        self::$users[2] = $child;

        $client->request('GET', '/users/'.self::$users[2]['id'].'?expand=parent');

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[2], $responseData["data"]);

    }

    /**
     * @depends testExpand
     */
    public function testAdvancedExpand()
    {
        $client = static::createClient();

        $client->request('GET', '/users/'.self::$users[2]['id'].'?expand=parent.children.parent.children');

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        self::$users[2]['parent']['children'] = [self::$users[2]];
        self::$users[2]['parent']['children'][0]['parent']['children'] = [self::$users[2]['parent']['children'][0]];
        unset(self::$users[2]['parent']['children'][0]['parent']['children'][0]['parent']);

        echo PHP_EOL.json_encode(self::$users[2]);
        $this->assertEntityEquals(self::$users[2], $responseData["data"]);

    }

    /**
     * @depends testAdvancedExpand
     */
    public function testFilter()
    {
        $client = static::createClient();

        $client->request(
          'GET',
          '/users',
          ['filter' => 'parent.name,eq,'.self::$users[0]['name']]
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        unset(self::$users[2]['parent']);

        $this->assertEntityArrayEquals([self::$users[2]], $responseData["data"]);

    }

    /**
     * @depends testFilter
     */
    public function testPagination()
    {
        $client = static::createClient();

        $client->request(
          'GET',
          '/users',
          [
            'offset' => 0,
            'limit'  => 20,
          ]
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            self::fail("Not valid JSON or null response!");
        }

        $this->assertEquals(['limit' => 20, 'offset' => 0, 'count' => 3], $responseData['meta']);

        $this->assertEntityArrayEquals(self::$users, $responseData["data"]);

        $client->request(
          'GET',
          '/users',
          [
            'offset' => 1,
            'limit'  => 20,
          ]
        );

        $response = $client->getResponse();
        echo "Got response: ".$response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            self::fail("Not valid JSON or null response!");
        }

        $this->assertEquals(['limit' => 20, 'offset' => 1, 'count' => 3], $responseData['meta']);

        $this->assertEntityArrayEquals([self::$users[1], self::$users[2]], $responseData["data"]);
    }

    /**
     * @depends testFilter
     */
    function testBulkDelete()
    {
        $client = static::createClient();
        $client->request(
          "DELETE",
          "/users/bulk",
          [],
          [],
          [
            'CONTENT_TYPE'          => 'application/json',
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
          ],
          json_encode([self::$users[2], self::$users[1]])
        );


        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());

        $client->request('GET', '/users');

        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null) {
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals([self::$users[0]], $responseData["data"]);
    }
}
