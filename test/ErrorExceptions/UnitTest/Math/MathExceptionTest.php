<?php

namespace ErrorExceptions\UnitTest\Math;

class MathExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\Math\MathException
     */
    public function testMathException()
    {
        sqrt(-1);
    }
}
