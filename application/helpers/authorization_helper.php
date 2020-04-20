<?php

class AUTHORIZATION
{
    public static function validateTokenTimestamp($token)
    {
        $date = new DateTime();
        $CI =& get_instance();
        $data = self::validateToken($token);
        if ($token != false && ($date->getTimestamp() - $data->timestamp < ($CI->config->item('token_timeout') * 60))) {
            return $data;
        }
        return false;
    }

    public static function validateToken($token)
    {
        $CI =& get_instance();
        return JWT::decode($token, $CI->config->item('jwt_key'));
    }

    public static function generateToken()
    {

        $date = new DateTime();

        $CI =& get_instance();
        return JWT::encode(array("timestamp" => $date->getTimestamp()), $CI->config->item('jwt_key'));
    }
}