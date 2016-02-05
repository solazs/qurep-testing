<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 2016.02.05.
 * Time: 0:31
 */

namespace QuRePTestBundle\Tests\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RestTestCase extends WebTestCase
{
    protected function assertJsonResponse($response, $statusCode = 200) {
        $this->assertEquals(
            $statusCode, $response->getStatusCode(),
            $response->getContent()
        );
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            $response->headers
        );
    }

    protected function assertEntityEquals($entity1, $entity2){
        foreach (array_keys($entity1) as $key) {
            if ($key != "updated_at" && $key != "created_at"){
                $this->assertEquals($entity1[$key], $entity2[$key]);
            }
        }
    }
}