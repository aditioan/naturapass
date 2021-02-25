<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 03/07/14
 * Time: 14:07
 */

namespace NaturaPass\MainBundle\Component\Security;


class SecurityUtilities {


    public static function sanitize($toSanitize) {
        $sanitized = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $toSanitize);

        $sanitized = preg_replace('#("|\')javascript:.*("|\')#is', '', $sanitized);

        return $sanitized;
    }
} 