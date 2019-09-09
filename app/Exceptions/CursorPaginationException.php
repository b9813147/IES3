<?php

namespace App\Exceptions;

use Exception;

class CursorPaginationException extends Exception
{
    /**
     * 無效的 Cursor
     *
     * @param string $cursor
     *
     * @return self
     */
    public static function invalidCursor($cursor)
    {
        return new self($cursor);
    }
}