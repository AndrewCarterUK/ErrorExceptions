<?php

namespace ErrorExceptions\UnitTest\IO;

class InvalidNetworkAddressExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\IO\InvalidNetworkAddressException
     */
    public function testInvalidIpAddress()
    {
        gethostbyaddr('test');
    }
}
