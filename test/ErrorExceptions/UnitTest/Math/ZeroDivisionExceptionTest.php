<?php

namespace ErrorExceptions\UnitTest\Math;

class ZeroDivisionExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\Math\ZeroDivisionException
     */
    public function testZeroDivisionException()
    {
        1 / 0;
    }
}
