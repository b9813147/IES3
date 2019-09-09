<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 學校授權到期
 *
 * @package App\Exceptions
 */
class UnauthorizedSchoolException extends HttpException
{
    /**
     * @param string $challenge WWW-Authenticate challenge string
     * @param string $message The internal exception message
     * @param \Exception $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct($challenge, $message = null, \Exception $previous = null, $code = 0)
    {
        $headers = array('WWW-Authenticate' => $challenge);

        parent::__construct(401, $message, $previous, $headers, $code);
    }
}