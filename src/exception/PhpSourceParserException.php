<?php
declare(strict_types=1);

namespace knotlib\exceptionhandler\html\exception;

use Throwable;

use knotlib\exceptionhandler\exception\ExceptionHandlerException;


class PhpSourceParserException extends ExceptionHandlerException
{
    /**
     * PhpSourceParserException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|NULL $prev
     */
    public function __construct(string $message, int $code = 0, Throwable $prev = null)
    {
        parent::__construct($message, $code, $prev );
    }

}

