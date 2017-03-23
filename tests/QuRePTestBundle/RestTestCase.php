<?php
/**
 * Created by PhpStorm.
 * User: baloo
 * Date: 2016.02.05.
 * Time: 0:31
 */

namespace QuRePTestBundle\Tests;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RestTestCase extends WebTestCase
{
    protected function assertJsonResponse(Response $response, $statusCode = 200)
    {
        $this->assertEquals(
          $statusCode,
          $response->getStatusCode(),
          $response->getContent()
        );
        $this->assertTrue(
          $response->headers->contains('Content-Type', 'application/json'),
          $response->headers
        );
    }

    protected function assertEntityEquals($entity1, $entity2)
    {
        foreach ($entity1 as $key => $value) {
            if ($key !== "updatedAt" && $key !== "createdAt" && $key !== "id") {
                if (!array_key_exists($key, $entity2)) {
                    self::fail('Key '.$key.' does not exists in entity2!');
                }
                if (is_array($value)) {
                    $this->assertEntityEquals($value, $entity2[$key]);
                } else {
                    $this->assertEquals($value, $entity2[$key]);
                }
            }
        }
        foreach ($entity2 as $key => $value) {
            if ($key !== "updatedAt" && $key !== "createdAt" && $key !== "id") {
                if (!array_key_exists($key, $entity1)) {
                    self::fail('Key '.$key.' does not exists in entity1!');
                }
                if (is_array($value)) {
                    $this->assertEntityEquals($entity1[$key], $value);
                } else {
                    $this->assertEquals($entity1[$key], $value);
                }
            }
        }
    }

    protected function assertEntityArrayEquals($entity1, $entity2)
    {
        for ($i = 0; $i < count($entity1); $i++) {
            $hadItemMatch = true;
            foreach ($entity1[$i] as $key => $value) {
                if ($key != "updatedAt" && $key != "createdAt" && $key != "id") {
                    if (!array_key_exists($key, $entity2[$i]) || $entity2[$i][$key] != $value) {
                        $hadItemMatch = false;
                    }
                }
            }
            foreach ($entity2[$i] as $key => $value) {
                if ($key != "updatedAt" && $key != "createdAt" && $key != "id") {
                    if (!array_key_exists($key, $entity1[$i]) || $entity1[$i][$key] != $value) {
                        $hadItemMatch = false;
                    }
                }
            }
            if (!$hadItemMatch) {
                self::fail(
                  "The given arrays don't match! Expected ".var_export($entity1, true).", got: "
                  .var_export($entity2, true)
                );
            }
        }
    }
}