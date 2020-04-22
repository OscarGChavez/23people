<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Requrired library
require APPPATH . '/libraries/REST_Controller.php';

// CORS configuration
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

// Main class that contains all APIs for this test
class Courses extends REST_Controller{

	function __construct(){
		// Construct the parent class
		parent::__construct();

		// Configure limits on our controller methods
		$this->methods['user_get']['limit']    = 500; // 500 requests per hour per user/key
		$this->methods['user_post']['limit']   = 100; // 100 requests per hour per user/key
		$this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
	}

	/**
	 * [checkCourseID Check if the course id provided exists or not]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [int]        $idCourse
	 */
	private function checkCourseID($idCourse){

		if ($idCourse > 0) {
			// Get course by ID
			$course = Model\Courses::find($idCourse);

			if (!is_null($course)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * [checkCourseData Check if json with course data is valid or not]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [Array]        $data      [Course data]
	 * @param  [int]        $idCourse
	 */
	private function checkCourseData($data, $idCourse = null){

		// Sets aux vars
		$nameMatchs = $codeMatchs = array();
		$valid = $validCode = true;
		$msg = "";
		$matchs = array();
		$requiredKeys = array("name", "code");

		// Check if all keys are in recived json
		if (!array_diff_key(array_flip($requiredKeys), $data)) {

			//Check specific restrictions and set msg and response codes
			if ($valid && (is_numeric($data["name"]) || $data["name"] == "")) {
				$valid = false;
				$msg = "Course name not valid!";
			}else{
				$nameMatchs = Model\Courses::find_by_name($data["name"]);
				if ((is_null($idCourse) && !empty($nameMatchs))
					||
						(!is_null($idCourse) && !empty($nameMatchs) && $nameMatchs[0]->id_courses != $idCourse)) {
					$valid = false;
					$msg = "Course name already exists!";
				}
			}

			if ($valid && $data["code"] == "") {
				$valid = false;
				$msg = "Course code not valid!";
			}else{
				$codeMatchs = Model\Courses::find_by_code($data["code"]);
				if ((is_null($idCourse) && !empty($codeMatchs))
					||
						(!is_null($idCourse) && !empty($codeMatchs) && $codeMatchs[0]->id_courses != $idCourse)) {
					$valid = false;
					$msg = "Course code already exists!";
				}
			}

			return array("valid" => $valid, "msg" => $msg);

		}else{
			return array("valid" => false, "msg" => "Wrong course data!");
		}
	}

	/**
	 * [courses_get GET Rest API that return a paginated list of courses and their information]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 */
	public function courses_get(){


		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Set GET vars
		$pageNumber = intval($this->input->get("page"));

		if ($pageNumber > 0) {

			$pageSize = 3;
			$startElement = $pageSize * ($pageNumber - 1);

			// Get data from DB and set aux vars
			$courses      = Model\Courses::limit($pageSize, $startElement)->all();
			$coursesCount = Model\Courses::count_all();
			$pagePrevious = $pageNumber -1;
			$pageNext     = $pageNumber +1;
			$pageTotal    = ceil($coursesCount / $pageSize);

			if ($pageNumber <= $pageTotal) {
				// Prepare response data
				$data = array();
				foreach ($courses as $item) {
					array_push($data, array(
											'id'   => $item->id_courses,
											'name' => $item->name,
											'code' => $item->code
										));
				}

				$response = array();
				if ($pageNumber == 1) {
					$response["self"]     = base_url()."api/courses/courses";
				}else{
					$response["self"]     = base_url()."api/courses/courses?page=".$pageNumber;
					$response["previous"] = base_url()."api/courses/courses?page=" . $pagePrevious;
				}

				if ($pageNumber < $pageTotal) {
					$response["next"] = base_url()."api/courses/courses?page=".$pageNext;
				}

				$response["first"]   = base_url()."api/courses/courses";
				$response["last"]    = base_url()."api/courses/courses?page=".$pageTotal;
				$response["count"]   = count($courses);
				$response["total"]   = $coursesCount;
				$response["courses"] = $data;

				$code = REST_Controller::HTTP_OK;
			}else{
				$response = array("msg" => "Page number off limits.");
				$code = REST_Controller::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE;
			}

			try{
				$this->set_response( $response, $code);
			}
			catch(Exception $e) {
				$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		}else{
			$response = array("msg" => "Page number cannot be less than 1.");
			$code = REST_Controller::HTTP_BAD_REQUEST;

			$this->set_response( $response, $code);
		}
	}

	/**
	 * [courses_all_get GET Rest API that return a list of all courses and their information]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 */
	public function courses_all_get(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Get data from DB
		$courses = Model\Courses::all();

		// Prepare response data
		$data = array();
		foreach ($courses as $item) {
			array_push($data, array(
								'id'   => $item->id_courses,
								'name' => $item->name,
								'code' => $item->code
							));
		}

		$response = array("courses" => $data);

		try{
			$this->set_response( $response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e) {
			$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * [courses_by_id_get GET Rest API that return information about the requested course]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [int]        $id
	 */
	public function courses_by_id_get($id){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Check if recived ID is valid
		if ($this->checkCourseID($id)) {

			// Get data from DB
			$course = Model\Courses::find($id);

			// Prepare response data
			$data = array(
						'id'   => $course->id_courses,
						"name" => $course->name,
						"code" => $course->code

					);

			$response = array("course" => $data);

			try{
				$this->set_response( $response, REST_Controller::HTTP_OK);
			}
			catch(Exception $e) {
				$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		}else{
			$response = array("msg" => "Course id don't exists!");
			$code     = REST_Controller::HTTP_NOT_FOUND;

			$this->set_response( $response, $code);
		}
	}

	/**
	 * [courses_post POST Rest API that creates a new course]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @vars [JSON]   [JSON with data for the new course]
	 */
	public function courses_post(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers(), true);

		// Get data from POST
		$auxJson = json_decode(file_get_contents('php://input'), TRUE);

		// Check JSON syntax
		if (json_last_error() == JSON_ERROR_NONE) {
			$json = cleanJSON($auxJson);
			// Check Course data from JSON
			$checkData = $this->checkCourseData($json);

			if ($checkData["valid"]) {
				// Insert Crouse to DB
				$newCourse = Model\Courses::make($json);

				if ($newCourse->save(TRUE)) {
					$response = array("msg" => "Course inserted correctly with ID ".Model\Courses::last_created()->id_courses);
					$code     = REST_Controller::HTTP_OK;
				}else{
					$response = array("msg" => "Something went wrong!");
					$code     = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
				}
			}else{
				$response = array("msg" => $checkData["msg"]);
				$code     = REST_Controller::HTTP_BAD_REQUEST;
			}

			try{
				$this->set_response( $response, $code);
			}
			catch(Exception $e) {
				$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		}else{
			$response = array("msg" => "Error Decoding JSON Data: Syntax Error.");
			$this->set_response( $response, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	/**
	 * [courses_put PUT Rest API that update the requested course]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @vars [JSON]   [JSON with data to update the requested course]
	 * @vars [int]   [ID of course to update]
	 */
	public function courses_put(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers(), true);

		// Get data from GET
		$idCourse = intval($this->input->get("id"));

		// Check if recived ID is valid
		if ($this->checkCourseID($idCourse)) {

			// Get data from POST
			$auxJson = json_decode(file_get_contents('php://input'), TRUE);

			// Check JSON syntax
			if (json_last_error() == JSON_ERROR_NONE) {
				$json = cleanJSON($auxJson);
				$checkData = $this->checkCourseData($json, $idCourse);

				if ($checkData["valid"]) {
					// Get Course from DB
					$course       = Model\Courses::find($idCourse);
					// Modify Course with recived data
					$course->name = $json["name"];
					$course->code = $json["code"];

					// Save changed to DB
					if ($course->save(FALSE)) {
						$response = array("msg" => "Course with ID ".$idCourse." updated correctly");
						$code     = REST_Controller::HTTP_OK;
					}else{
						$response = array("msg" => "Something went wrong!");
						$code     = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
					}
				}else{
					$response = array("msg" => $checkData["msg"]);
					$code     = REST_Controller::HTTP_BAD_REQUEST;
				}

				try{
					$this->set_response( $response, $code);
				}
				catch(Exception $e) {
					$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
				}
			}else{
				$response = array("msg" => "Error Decoding JSON Data: Syntax Error.");
				$this->set_response( $response, REST_Controller::HTTP_BAD_REQUEST);
			}
		}else{
			$response = array("msg" => "Course id don't exists!");
			$code     = REST_Controller::HTTP_NOT_FOUND;

			$this->set_response( $response, $code);
		}
	}

	/**
	 * [courses_delete DELETE Rest API that delete the requested course]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @vars [int]   [ID of course to delete]
	 */
	public function courses_delete(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Get data from GET
		$idCourse = intval($this->input->get("id"));

		// Check if recived ID is valid
		if($this->checkCourseID($idCourse)){

			// Get Course from DB
			$course = Model\Courses::find($idCourse);
			// Get student of this Course from DB
			$course->students = $course->students();

			// Check if requested course have registered students
			if (count($course->students) == 0) {
				// Delete Course from DB
				if (Model\Courses::delete($idCourse)) {
					$response = array("msg" => "Course with ID ".$idCourse." deleted correctly");
					$code     = REST_Controller::HTTP_OK;
				}else{
					$response = array("msg" => "Something went wrong!");
					$code     = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
				}
			}else{
				$response = array("msg" => "Course ID ".$idCourse." have registered students, cannot be eliminated!");
				$code     = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
			}

			try{
				$this->set_response( $response, $code);
			}
			catch(Exception $e) {
				$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}

		}else{
			$response = array("msg" => "Course id don't exists!");
			$code     = REST_Controller::HTTP_NOT_FOUND;

			$this->set_response( $response, $code);
		}
	}
}