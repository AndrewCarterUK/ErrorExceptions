<?php

namespace ErrorExceptions\UnitTest\IO;

class FileNotFoundExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ErrorExceptions\IO\FileNotFoundException
     */
    public function testInvalidFileName()
    {
        $path = dirname(__FILE__);

        do {
            $file = $path . uniqid(mt_rand());
        } while (file_exists($file));

        fopen($file, 'r');
    }
}
