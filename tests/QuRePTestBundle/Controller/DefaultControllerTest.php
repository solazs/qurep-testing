<?php

namespace QuRePTestBundle\Tests\Controller;


use Doctrine\ORM\Tools\SchemaTool;

class DefaultControllerTest extends RestTestCase
{
    public static function setUpBeforeClass()
    {


        parent::setUpBeforeClass();

        $kernel = static::createKernel();
        $kernel->boot();
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
            "email" => "telek@valami.hu",
            "children" => []
        ],
        [
            "displayName" => "Hibás Elemér",
            "username" => "hibaselem"
        ],
        [
            "displayName" => "Pár Zoltán",
            "username" => "parzol",
            "email" => "parzol@e.info",
            "children" => []
        ],
    ];

    public function testEmpty()
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $response = $client->getResponse();

        $this->assertJsonResponse($response);

        $this->assertEquals("[]", $response->getContent());
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

        $this->assertEntityEquals(self::$users[0], $responseData);

        self::$users[0] = $responseData;
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

        $this->assertEntityEquals(self::$users[0], $responseData);
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
            json_encode(self::$users[1])
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

        unset(self::$users[1]);
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

        $this->assertEntityArrayEquals(self::$users, $responseData);

        self::$users = $responseData;
    }
}
