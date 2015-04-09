<?php

namespace ErrorExceptions\UnitTest\IO;

class CurlExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\IO\CurlException
     */
    public function testInvalidCurlHandle()
    {
        $curlHandle = curl_init();
        curl_close($curlHandle);
        curl_exec($curlHandle);
    }
}
