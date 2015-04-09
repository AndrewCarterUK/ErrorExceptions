<?php

namespace ErrorExceptions\UnitTest\IO;

class DNSExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\IO\DNSException
     */
    public function testGetAddrInfoFail()
    {
        fopen('http://./', 'r');
    }
}
