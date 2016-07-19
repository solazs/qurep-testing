<?php

namespace QuRePTestBundle\Tests\Controller;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

class DefaultControllerTest extends RestTestCase
{
    public static function setUpBeforeClass()
    {


        parent::setUpBeforeClass();

        $kernel = static::createKernel();
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
            "displayName" => "Teszt Elek",
            "username" => "telek",
            "email" => "telek@valami.hu"
        ],
        [
            "displayName" => "Pár Zoltán",
            "username" => "parzol",
            "email" => "parzol@e.info"
        ],
        [
            "displayName" => "Gyerekes Emil",
            "username" => "gyeremil",
            "email" => "gyeremil@e.info"
        ],
        [
            "displayName" => "Hibás Elemér",
            "username" => "hibaselem"
        ],
    ];

    public function testEmpty()
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response);

        $this->assertEquals('{"data":[],"meta":[]}', $response->getContent());
    }

    /**
     * @depends testEmpty
     */
    public function testPost(){
        $client = static::createClient();
        $client->request(
            "POST",
            "/users",
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ),
            json_encode(self::$users[0])
        );

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response, 201);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[0], $responseData["data"]);

        self::$users[0] = $responseData["data"];
    }

    /**
     * @depends testPost
     */
    public function testUpdate(){
        $putData = array(
            "displayName" => "Teszt Elek Rendesen"
        );
        $user = self::$users[0];
        $user["displayName"] = $putData["displayName"];
        self::$users[0] = $user;

        $client = static::createClient();
        $client->request(
            "POST",
            "/users/" . self::$users[0]['id'],
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ),
            json_encode($putData)
        );

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response, 201);

        $client = static::createClient();
        $client->request(
            "GET",
            "/users/" . self::$users[0]['id']
        );

        $response = $client->getResponse();

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[0], $responseData["data"]);
    }

    /**
     * @depends testPost
     *
     */
    public function testDelete(){
        $client = static::createClient();
        $client->request(
            "DELETE",
            "/users/" . self::$users[0]['id']
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @depends testDelete
     */
    public function testFailedPost(){
        $client = static::createClient();
        $client->request(
            "POST",
            "/users",
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ),
            json_encode(self::$users[3])
        );

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response, 400);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEquals('{"error":{"global":[],"fields":{"email":"This value should not be blank'
            . '."}},"code":400}', $response->getContent());

        unset(self::$users[3]);
        self::$users = array_values(self::$users);
    }

    /**
     * @depends testFailedPost
     */
    public function testBulkUpdate(){
        foreach (self::$users as &$user) {
            if (array_key_exists("id", $user)){
                unset($user['id']);
            }
            if (array_key_exists("createdAt", $user)) {
                unset($user['createdAt']);
                unset($user['updatedAt']);
            }
        }

        $this->testPost();

        foreach (self::$users as &$user) {
            if (array_key_exists("createdAt", $user)) {
                unset($user['createdAt']);
                unset($user['updatedAt']);
            }
        }

        $client = static::createClient();
        $client->request(
            "POST",
            "/users/bulk",
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ),
            json_encode(self::$users)
        );

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response, 201);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
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
            "/users/" . self::$users[2]['id'],
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ),
            json_encode(array("parent" => self::$users[0]['id']))
        );

        $response = $client->getResponse();

        $this->assertJsonResponse($response, 201);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals(self::$users[2], $responseData["data"]);

        $child = self::$users[2];
        $child['parent'] = self::$users[0];
        self::$users[2] = $child;

        $client->request('GET', '/users/' . self::$users[2]['id'] . '?expand=parent');

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
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

        $client->request('GET', '/users/' . self::$users[2]['id'] . '?expand=parent.children');

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $grandchild = self::$users[2];
        unset($grandchild['parent']);

        $child = self::$users[2]['parent'];
        $child['children'] = array($grandchild);
        self::$users[2]['parent'] = $child;

        $this->assertEntityEquals(self::$users[2], $responseData["data"]);

    }

    /**
     * @depends testAdvancedExpand
     */
    public function testFilter()
    {
        $client = static::createClient();

        $client->request('GET',
            '/users',
            ['filter' => 'parent.displayName,eq,' . self::$users[0]['displayName']]
        );

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $this->assertJsonResponse($response);

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        unset(self::$users[2]['parent']);

        $this->assertEntityArrayEquals([self::$users[2]], $responseData["data"]);

    }

    /**
     * @depends testFilter
     */
    function testBulkDelete(){
        $client = static::createClient();
        $client->request(
            "DELETE",
            "/users/bulk",
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ),
            json_encode([self::$users[2], self::$users[1]])
        );


        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());

        $client->request('GET', '/users');

        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals([self::$users[0]], $responseData["data"]);
    }
}
