<?php

namespace ErrorExceptions;

use ErrorException;

class ErrorExceptions {
    protected $callbacks = array();
    protected $errors = E_ALL;
    protected $exceptions = array();
    protected $failureCallbacks = array();
    protected $lastError = array();
    protected $registered = false;

    private static function mergeExceptionArrays(array $array1, array $array2) {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key]) && is_array($array1[$key]) && is_array($value)) {
                $array1[$key] = self::mergeExceptionArrays($array1[$key], $array2[$key]);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    public function __construct($errorLevel = E_ALL) {
        $this->errors = $errorLevel;

        $this->addConfigurationExceptions();
        $this->addCoreExceptions();
        $this->addIOExceptions();
        $this->addMathExceptions();
    }

    public function addCallback($callback) {
        if (is_callable($callback)) {
            $this->callbacks[] = $callback;
        }
    }

    public function addFailureCallback($callback) {
        if (is_callable($callback)) {
            $this->failureCallbacks[] = $callback;
        }
    }

    public function addException($errNo, $regex, $exception) {
        if (!isset($this->exceptions[$errNo])) {
            $this->exceptions[$errNo] = array();
        }
        $this->exceptions[$errNo][$regex] = $exception;
    }

    public function addExceptions(array $exceptions) {
        $this->exceptions = self::mergeExceptionArrays(
            $exceptions, $this->exceptions
        );
    }

    public function handleError($errNo, $errStr, $errFile, $errLine) {
        if (!$this->registered) {
            return true;
        }
        $this->lastError = array(
            'type' => $errNo,
            'message' => $errStr,
            'file' => $errFile,
            'line' => $errLine
        );
        $exception = $this->lookupError($errNo, $errStr, $errFile, $errLine);
        if ($exception && class_exists($exception)) {
            $previous = new ErrorException(
                $errStr,
                0,
                $errNo,
                $errFile,
                $errLine
            );
            throw new $exception($errStr, $errNo, $previous);
        }
        return $exception;
    }

    public function handleShutdown() {
        if (!$this->registered) {
            return true;
        }
        $error = error_get_last();
        if (!is_null($error) && $error != $this->lastError) {
            $this->callFailedCallbacks(
                $error['type'], $error['message'], $error['file'], $error['line']
            );
        }
    }

    public function register() {
        set_error_handler(
            array($this, 'handleError'), $this->errors
        );
        register_shutdown_function(array($this, 'handleShutdown'));
        $this->registered = true;
    }

    public function unregister() {
        restore_error_handler();
        $this->registered = false;
    }

    protected function callFailedCallbacks($errNo, $errStr, $errFile, $errLine) {
        foreach ($this->failureCallbacks as $callback) {
            if (call_user_func($callback, $errNo, $errStr, $errFile, $errLine)) {
                return true;
            }
        }
        return false;
    }

    protected function lookupError($errNo, $errStr, $errFile, $errLine) {
        foreach ($this->callbacks as $callback) {
            $exception = call_user_func($callback, $errNo, $errStr);
            if ($exception && class_exists($exception)) {
                return $exception;
            }
        }
        if (!isset($this->exceptions[$errNo])) {
            return false;
        }
        foreach ($this->exceptions[$errNo] as $regex => $exception) {
            if (preg_match($regex, $errStr)) {
                return $exception;
            }
        }
        return $this->callFailedCallbacks($errNo, $errStr, $errFile, $errLine);
    }

    private function addConfigurationExceptions() {
        $this->addExceptions(array(
            E_WARNING => array(
                '/function is disabled/' => 'DisabledFeatureException',
                '/has been disabled for security reasons/' => '',
                '/open(_|\s)basedir/i' => 'OpenBaseDirException',
                '/safe(_|\s)mode/i' => 'SafeModeException',
                '/Unable to access .*/' => 'SafeModeException',
            ),
        ));
    }

    private function addCoreExceptions() {
        $this->addExceptions(array(
            E_DEPRECATED => array(
                '/./' => 'ErrorExceptions\\Core\\DeprecatedException',
            ),
            E_NOTICE => array(
                '/Constant .* already defined/' => '\\LogicException',
                '/Exceptions must be derived from the Exception/' => 'ErrorExceptions\\Core\\InvalidClassException',
                '/failed to (flush|delete|delete and flush) buffer/' => 'ErrorExceptions\\Core\\OutputException',
                '/(modify|assign|get) property of non-object/' => '\\LogicException',
                '/(Illegal|Corrupt) member variable name/' => 'ErrorExceptions\\Core\\ParseException',
                '/Indirect modification of overloaded property/' => '\\LogicException',
                '/(Object|Array) .* to string conversion/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/Object of class .* could not be converted to/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/undefined constant/i' => 'ErrorExceptions\\Core\\UndefinedConstantException',
                '/undefined (property|variable)/i' => 'ErrorExceptions\\Core\\UndefinedVariableException',
                '/Undefined offset/' => 'ErrorExceptions\\Core\\OutOfBoundsException',
                '/Uninitialized string offset/' => 'ErrorExceptions\\Core\\OutOfBoundsException',

            ),
            E_RECOVERABLE_ERROR => array(
                '/__toString\(\) must return a string/' => 'ErrorExceptions\\Core\\InvalidReturnValueException',
                '/Argument \d+ passed to .* must/' => 'ErrorExceptions\\Core\\InvalidArgumentException',
                '/Cannot get arguments for calling closure/' => '\\LogicException',
                '/Closure object cannot have properties/' => '\\LogicException',
                '/Instantiation of .* is not allowed/' => '\\LogicException',
                '/Object of class .* could not be converted to/' => 'ErrorExceptions\\Core\\TypeConversionException',
            ),
            E_STRICT => array(
                '/Accessing static property .* as non static/' => 'ErrorExceptions\\Core\\StrictException',
                '/Creating default object from empty value/' => 'ErrorExceptions\\Core\\UndefinedVariableException',
                '/Non-static method .* be called statically/' => 'ErrorExceptions\\Core\\StrictException',
                '/Redefining already defined constructor/' => 'ErrorExceptions\\Core\\ParseException',
                '/Resource .* used as offset/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/Static function .* should not be abstract/' => 'ErrorExceptions\\Core\\ParseException',
            ),
            E_WARNING => array(
                '/__toString\(\) must return a string/' => 'ErrorExceptions\\Core\\InvalidReturnValueException',
                '/a COM object/i' => '\\com_exception',
                '/Argument \d+ not passed to function/' => '\\OutOfBoundsException',
                '/bad type specified while parsing parameters/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/called from outside a class/' => 'ErrorExceptions\\Core\\InvalidScopeException',
                '/Can only handle single dimension variant arrays/' => '\\com_exception',
                '/Cannot add element to the array/' => '\\RuntimeException',
                '/Cannot add (user|internal) functions to return value/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/Cannot convert to (real|ordinal) value/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/Cannot (read|write) property of object - .* handler defined/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/Cannot redeclare class/' => 'ErrorExceptions\\Core\\InvalidClassException',
                '/Cannot unset offset in a non-array variable/' => '\\LogicException',
                '/Cannot use a scalar value as an array/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/class .* is undefined/' => 'ErrorExceptions\\Core\\InvalidClassException',
                '/Class .* not found/' => 'ErrorExceptions\\Core\\InvalidClassException',
                '/Class constants cannot be defined/' => '\\LogicException',
                '/Clone method does not require arguments/' => 'ErrorExceptions\\Core\\ParseException',
                '/Constants may only evaluate to scalar values/' => 'ErrorExceptions\\Core\\InvalidValueException',
                '/converting from PHP array to VARIANT/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/Could not convert string to unicode/i' => 'ErrorExceptions\\Core\\InvalidInputException',
                '/Could not execute/' => '\\LogicException',
                '/Could not find a factory/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/could not obtain parameters for parsing/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/expected to be a reference/' => 'ErrorExceptions\\Core\\ReferenceException',
                '/expects at least \d+ parameters, \d+ given/' => '\\InvalidArgumentException',
                '/expects the argument .* to be a valid callback/' => 'ErrorExceptions\\Core\\InvalidCallbackException',
                '/expects parameter \d+ to be a valid callback/' => 'ErrorExceptions\\Core\\InvalidCallbackException',
                '/failed allocating/i' => 'ErrorExceptions\\Core\\OutOfMemoryException',
                '/failed to allocate/i' => 'ErrorExceptions\\Core\\OutOfMemoryException',
                '/first parameter has to be/i' => '\\InvalidArgumentException',
                '/First parameter must either be/' => '\\InvalidArgumentException',
                '/function is not supported/' => '\\BadFunctionCallException',
                '/handler .* did not return a/' => '\\LogicException',
                '/Illegal offset type/' => '\\LogicException',
                '/Illegal string offset/' => '\\OutOfBoundsException',
                '/Illegal type returned from/' => 'ErrorExceptions\\Core\\InvalidReturnValueException',
                '/Indirect modification of overloaded element/' => '\\LogicException',
                '/Input variable nesting level exceeded/' => 'ErrorExceptions\\Core\\InvalidInputException',
                '/invalid .* ID/i' => '\\InvalidArgumentException',
                '/Invalid callback/' => 'ErrorExceptions\\Core\\InvalidCallbackException',
                '/invalid date/'   => '\\InvalidArgumentException',
                '/Invalid error type specified/' => '\\DomainException',
                '/Illegal offset type/' => '\\DomainException',
                '/invalid parameter given for/i' => '\\DomainException',
                '/Invalid scanner mode/' => '\\DomainException',
                '/is no longer supported/' => 'ErrorExceptions\\Core\\DeprecatedException',
                '/is not a valid mode for/' => '\\DomainException',
                '/is not a valid .* resource/' => '\\DomainException',
                '/is not implemented/' => 'ErrorExceptions\\Core\\InvalidImplementationException',
                '/is only valid for years between/i' => '\\OutOfRangeException',
                '/is too long for/' => '\\DomainException',
                '/may not be negative/i'    => '\\OutOfRangeException',
                '/(modify|assign|get) property of non-object/' => '\\LogicException',
                '/must be a name of .* class/' => 'ErrorExceptions\\Core\\InvalidClassException',
                '/must be greather than/' => '\\RangeException',
                '/must not return itself/' => 'ErrorExceptions\\Core\\InvalidReturnValueException',
                '/must return a/' => 'ErrorExceptions\\Core\\InvalidReturnValueException',
                '/no .* resource supplied/' => '\\InvalidArgumentException',
                '/no function context/' => '\\LogicException',
                '/not a dispatchable interface/' => '\\BadMethodCallException',
                '/not supported on this platform/' => 'ErrorExceptions\\Core\\NotSupportedException',
                '/Nothing returned from/' => 'ErrorExceptions\\Core\\InvalidReturnValueException',
                '/object doesn\'t support property references/' => 'ErrorExceptions\\Core\\ReferenceException',
                '/only one varargs specifier/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/output handler .* conflicts with/' => 'ErrorExceptions\\Core\\ConflictException',
                '/Parameter wasn\'t passed by reference/' => 'ErrorExceptions\\Core\\ReferenceException',
                '/POST Content-Length of/' => 'ErrorExceptions\\Core\\InvalidInputException',
                '/POST length does not match Content-Length/' => 'ErrorExceptions\\Core\\InvalidInputException',
                '/request_startup.* failed/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/should be >/' => '\\RangeException',
                '/The magic method .* must have public/' => 'ErrorExceptions\\Core\\ParseException',
                '/The use statement with non-compound name/' => 'ErrorExceptions\\Core\\ParseException',
                '/this code not yet written/i' => 'ErrorExceptions\\Core\\WTFException',
                '/timestamp value must be a positive value/i' => '\\\InvalidArgumentException',
                '/type library constant .* already defined/i' => '\\LogicException',
                '/Unable to find typeinfo using/i' => '\\RuntimeException',
                '/Unknown .* list entry type in/' => 'ErrorExceptions\\Core\\PHPCoreException',
                '/Unspecified error/' => 'ErrorExceptions\\Core\\UnknownErrorException',
                '/Variable passed to .* is not an/' => '\\DomainException',
                '/variant is not an/' => '\\InvalidArgumentException',
                '/variant: failed to copy from/' => 'ErrorExceptions\\Core\\TypeConversionException',
                '/Wrong parameter count for/' => '\\InvalidArgumentException',
                '/year out of range/i' => '\\OutOfRangeException',
                '/zval: conversion from/' => 'ErrorExceptions\\Core\\TypeConversionException',
            ),
        ));
    }

    private function addIOExceptions() {
        $this->addExceptions(array(
            E_NOTICE => array(
                '/:\/\/ was never changed, nothing to restore/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/path was truncated to/' => 'ErrorExceptions\\IO\\InvalidPathException',
                '/send of \d+ bytes failed/' => 'ErrorExceptions\\IO\\WriteFailureException',
            ),
            E_WARNING => array(
                '/:\/\/ never existed, nothing to restore/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/Attempt to close cURL handle from a callback/' => 'ErrorExceptions\\IO\\CurlException',
                '/bytes more data than requested/' => '\\OverflowException',
                '/call the CURL/i' => 'ErrorExceptions\\IO\\CurlException',
                '/cannot peek or fetch OOB data/' => 'ErrorExceptions\\IO\\ReadFailureException',
                '/cannot read from a stream opened in/' => 'ErrorExceptions\\IO\\InvalidStreamException',
                '/cannot seek/' => 'ErrorExceptions\\IO\\IOException',
                '/cannot use stream opened in mode/' => 'ErrorExceptions\\IO\\InvalidStreamException',
                '/cannot write OOB data/' => 'ErrorExceptions\\IO\\WriteFailureException',
                '/Could not build curl_slist/' => 'ErrorExceptions\\IO\\CurlException',
                '/could not extract hash key from/' => 'ErrorExceptions\\IO\\SSLException',
                '/could not read .* data from stream/' => 'ErrorExceptions\\IO\\IOException',
                '/cURL handle/' => 'ErrorExceptions\\IO\\CurlException',
                '/CURLOPT/' => '\\InvalidArgumentException',
                '/Failed opening .* for (inclusion|highlighting)/' => 'ErrorExceptions\\IO\\NotReadableException',
                '/failed to bind to/' => 'ErrorExceptions\\IO\\IOException',
                '/Failed to resolve/' => 'ErrorExceptions\\IO\\DNSException',
                '/filename cannot be empty/' => 'ErrorExceptions\\IO\\InvalidFileNameException',
                '/file handle is not writable/' => 'ErrorExceptions\\IO\\NotWritableException',
                '/File name is longer than the maximum/' => 'ErrorExceptions\\IO\\InvalidFileNameException',
                '/getaddrinfo failed/' => 'ErrorExceptions\\IO\\DNSException',
                '/gethostbyname failed/' => 'ErrorExceptions\\IO\\DNSException',
                '/Invalud curl configuration option/' => 'ErrorExceptions\\IO\\CurlException',
                '/Invalid IP address/' => 'ErrorExceptions\\IO\\InvalidNetworkAddressException',
                '/invalid URL/' => 'ErrorExceptions\\IO\\InvalidURLException',
                '/No such file or directory/' => 'ErrorExceptions\\IO\\FileNotFoundException',
                '/protocol .* disabled in curl/i' => 'ErrorExceptions\\IO\\InvalidProtocolException',
                '/Protocol .* already defined/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/There was an error mcode=/' => 'ErrorExceptions\\IO\\CurlException',
                '/this stream does not support SSL/' => 'ErrorExceptions\\IO\\SSLException',
                '/unable to allocate stream/' => 'ErrorExceptions\\IO\\IOException',
                '/Unable to find the wrapper/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/Unable to include uri/' => 'ErrorExceptions\\IO\\IOException',
                '/Unable to include .* (URI|request)/' => 'ErrorExceptions\\IO\\IOException',
                '/Unable to register wrapper/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/Unable to restore original .* wrapper/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/Unable to unregister protocol/' => 'ErrorExceptions\\IO\\StreamWrapperException',
                '/URI lookup failed/' => 'ErrorExceptions\\IO\\DNSException',
            ),
        ));
    }

    private function addMathExceptions() {
        $this->addExceptions(array(
            E_WARNING => array(
                '/Division by zero/'                 => 'ErrorExceptions\\Math\\ZeroDivisionException',
                '/Square root of a negative number/' => 'ErrorExceptions\\Math\\MathException',
            ),
        ));
    }
}
