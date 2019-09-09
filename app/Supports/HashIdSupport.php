<?php

namespace App\Supports;

use Vinkla\Hashids\Facades\Hashids;

trait HashIdSupport
{
    /**
     * Encode parameters to generate a hash.
     *
     * @param integer|array $value
     *
     * @return mixed
     */
    protected function encodeHashId($value)
    {
        return Hashids::encode($value);
    }

    /**
     * Decode a hash to the original parameter values.
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function decodeHashId($value)
    {
        $result = Hashids::decode($value);
        if (is_array($result) && count($result) === 1) {
            return $result[0];
        }

        return $result;
    }

    /**
     * Encode parameters to generate a hash for cursor pagination.
     *
     * @param $value
     *
     * @return mixed
     */
    protected function encodeHashIdForCursorPagination($value)
    {
        return Hashids::connection('cursor_pagination')->encode($value);
    }

    /**
     * Decode a hash to the original parameter values for cursor pagination.
     *
     * @param $value
     *
     * @return mixed
     */
    protected function decodeHashIdForCursorPagination($value)
    {
        return Hashids::connection('cursor_pagination')->decode($value);
    }
}