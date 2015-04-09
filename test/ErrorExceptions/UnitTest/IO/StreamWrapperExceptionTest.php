<?php

namespace ErrorExceptions\UnitTest\IO;

class StreamWrapperExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\IO\StreamWrapperException
     */
    public function testNothingToRestore()
    {
        stream_wrapper_restore('http');
    }

    /**
     * @expectedException ErrorExceptions\IO\StreamWrapperException
     */
    public function testNeverExisted()
    {
        stream_wrapper_restore(uniqid());
    }

    /**
     * @expectedException ErrorExceptions\IO\StreamWrapperException
     */
    public function testProtocolAlreadyDefined()
    {
        $streamWrappers = stream_get_wrappers();

        if (0 === count($streamWrappers)) {
            $this->fail('No registered stream wrappers');
        }

        stream_wrapper_register($streamWrappers[0], 'stdClass');
    }
}
