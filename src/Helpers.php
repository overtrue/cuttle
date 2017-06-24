<?php

/*
 * This file is part of the overtrue/cuttle.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\Cuttle;

/**
 * To camel case.
 *
 * @param string $str
 *
 * @return string
 */
function camel_case(string $str)
{
    return lcfirst(studly_case($str));
}

/**
 * To snake case.
 *
 * @param string $str
 *
 * @return mixed|string
 */
function snake_case(string $str)
{
    if (ctype_lower($str)) {
        return $str;
    }

    return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', preg_replace('/\s+/u', '', $str)), 'utf-8');
}

/**
 * To studly case.
 *
 * @param string $str
 *
 * @return string
 */
function studly_case(string $str)
{
    return ucwords(str_replace(['-', '_'], ' ', trim($str)));
}
