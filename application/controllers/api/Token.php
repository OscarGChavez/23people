<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Requrired library
require APPPATH . '/libraries/REST_Controller.php';

// CORS configuration
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Main class that contains all APIs for this test
class Token extends REST_Controller{

	function __construct(){
		// Construct the parent class
		parent::__construct();

		// Configure limits on our controller methods
		$this->methods['user_get']['limit']    = 500; // 500 requests per hour per user/key
		$this->methods['user_post']['limit']   = 100; // 100 requests per hour per user/key
		$this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
	}

	/**
	 * [token_get GET Rest API that return a JSON with JWT token for consume authenticated APIs]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 */
	public function token_get(){

		$token = AUTHORIZATION::generateToken();

		$response = array("token" => $token);

		$this->set_response( $response, REST_Controller::HTTP_OK);
	}
}