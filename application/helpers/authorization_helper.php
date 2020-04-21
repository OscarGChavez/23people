<?php

class AUTHORIZATION{
    public static function validateTokenTimestamp($token){
        $date = new DateTime();
        $CI =& get_instance();
        $data = self::validateToken($token);
        if ($token != false && ($date->getTimestamp() - $data->timestamp < ($CI->config->item('token_timeout') * 60))) {
            return $data;
        }
        return false;
    }

    public static function validateToken($token){
        $CI =& get_instance();
        return JWT::decode($token, $CI->config->item('jwt_key'));
    }

    public static function generateToken()
    {

        $date = new DateTime();

        $CI =& get_instance();
        return JWT::encode(array("timestamp" => $date->getTimestamp()), $CI->config->item('jwt_key'));
    }

    /**
     * [verify_request Function that validate the received token against server jwt_key and timeout]
     * @author Oscar García Chávez
     * @date   2020-04-20
     * @param  [Array]        $headers         [Array with all received headers]
     * @param  boolean       $jsonContentType [Indicate if it is necessary to validate Content-Type header]
     */
    public static function verify_request($headers, $jsonContentType = false){

        // Instance of CI superobject for access to response methods
        $CI =& get_instance();

        try {
            // Extract the token
            $token = $headers['Authorization'];
            // Validate the token
            $data = self::validateTokenTimestamp($token);
            // Return response depending on validation status
            if ($data === false) {
                $status   = REST_Controller::HTTP_UNAUTHORIZED;
                $response = ['status' => $status, 'msg' => 'Unauthorized Access!'];
                $CI->response($response, $status);
                exit();
            } else if($jsonContentType && $headers['Content-Type'] != "application/json"){
                $status   = REST_Controller::HTTP_BAD_REQUEST;
                $response = ['status' => $status, 'msg' => 'Missing Content-Type Header!'];
                $CI->response($response, $status);
                exit();
            }
        } catch (Exception $e) {
            // Token is invalid
            // Send the unathorized access message
            $status   = REST_Controller::HTTP_UNAUTHORIZED;
            $response = ['status' => $status, 'msg' => 'Unauthorized Access! '];
            $CI->response($response, $status);
            exit();
        }
    }
}