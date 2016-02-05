<?php

namespace QuRePTestBundle\Tests\Controller;

class DefaultControllerTest extends RestTestCase
{
    static $createdUserId;
    public function testEmpty()
    {
        $client = static::createClient();

        $client->request('GET', '/users');

        $response = $client->getResponse();

        $this->assertJsonResponse($response);

        $this->assertEquals("[]", $response->getContent());
    }

    public function testPost(){
        $user = array(
            "displayName" => "Soltész Balázs",
            "username" => "solazs",
            "email" => "solazs@szolazs.hu"
        );

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
            json_encode($user)
        );

        $response = $client->getResponse();
        echo "Got response: " . $response;

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals($user, $responseData);

        DefaultControllerTest::$createdUserId = $responseData['id'];

    }

    /**
     * @depends testPost
     */
    public function testUpdate(){
        $putData = array(
            "displayName" => "Soltész Balázs Péter"
        );

        $user = array(
            "displayName" => "Soltész Balázs Péter",
            "username" => "solazs",
            "email" => "solazs@szolazs.hu"
        );

        $client = static::createClient();
        $client->request(
            "POST",
            "/users/" . DefaultControllerTest::$createdUserId,
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

        $client = static::createClient();
        $client->request(
            "GET",
            "/users/" . DefaultControllerTest::$createdUserId
        );

        $response = $client->getResponse();

        $responseData = json_decode($response->getContent(), true);

        if ($responseData === null){
            $this->fail("Not valid JSON or null response!");
        }

        $this->assertEntityEquals($user, $responseData);
    }

    /**
     * @depends testPost
     */
    public function testDelete(){
        $client = static::createClient();
        $client->request(
            "DELETE",
            "/users/" . DefaultControllerTest::$createdUserId
        );
        $response = $client->getResponse();
        $this->assertEquals(204, $response->getStatusCode());
    }
}
