<?php

namespace ErrorExceptions\UnitTest\IO;

class InvalidPathExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\IO\InvalidPathException
     */
    public function testPathToLong()
    {
        $maxPathLength = 512;
        // using PHP_MAXPATHLEN causes the error message to be truncated and the test to fail

        for ($i = 0, $path = ''; $i < $maxPathLength + 1; $i++) {
            $path .= 'X';
        }

        fopen($path, 'r', true);
    }
}
