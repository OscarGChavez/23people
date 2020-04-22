<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Requrired library
require APPPATH . '/libraries/REST_Controller.php';

// CORS configuration
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

// Main class that contains all APIs for this test
class Students extends REST_Controller{

	function __construct(){
		// Construct the parent class
		parent::__construct();

		// Configure limits on our controller methods
		$this->methods['user_get']['limit']    = 500; // 500 requests per hour per user/key
		$this->methods['user_post']['limit']   = 100; // 100 requests per hour per user/key
		$this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
	}

	/**
	 * [checkStudentID Check if the student id provided exists or not]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [int]        $idStudent
	 */
	private function checkStudentID($idStudent){

		if ($idStudent > 0) {
			// Get student by ID
			$student = Model\Students::find($idStudent);

			if (!is_null($course)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * [checkStudentData Check if json with student data is valid or not]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [Array]        $data      [Student data]
	 * @param  [int]        $idStudent
	 */
	private function checkStudentData($data, $idStudent = null){

		// Sets aux vars
		$nameMatchs = $codeMatchs = array();
		$valid = $validCode = true;
		$msg = "";
		$matchs = array();
		$requiredKeys = array("rut", "name", "lastName", "age", "course");

		// Check if all keys are in recived json
		if (!array_diff_key(array_flip($requiredKeys), $data)) {

			//Check specific restrictions and set msg and response codes
			if (!validateRut($data["rut"])) {
				$valid = false;
				$msg = "Student's rut is not valid!";
			}else{
				$rutMatchs = Model\Students::find_by_rut(cleanRut($data["rut"]));
				if ((is_null($idStudent) && !empty($rutMatchs))
					||
						(!is_null($idStudent) && !empty($rutMatchs) && $rutMatchs[0]->id_students != $idStudent)) {
					$valid = false;
					$msg = "Student's rut already exists!";
				}
			}

			if($valid && intval($data["age"]) <= 18) {
				$valid = false;
				$msg = "Student's age must be over 18 years old!";
			}

			if ($valid && (is_numeric($data["name"]) || $data["name"] == "")) {
				$valid = false;
				$msg = "Student's name not valid!";
			}

			if ($valid && (is_numeric($data["lastName"]) || $data["lastName"] == "")) {
				$valid = false;
				$msg = "Student's last name not valid!";
			}

			if($valid && (!is_numeric($data["course"]) || is_null(Model\Courses::find(intval($data["course"]))))) {
				$valid = false;
				$msg = "Course id not valid!";
			}

			return array("valid" => $valid, "msg" => $msg);

		}else{
			return array("valid" => false, "msg" => "Wrong student data!");
		}
	}

	/**
	 * [students_get GET Rest API that return a paginated list of students and their information]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 */
	public function students_get(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		$pageNumber = intval($this->input->get("page"));

		if ($pageNumber > 0) {

			$pageSize = 3;
			$startElement  = $pageSize * ($pageNumber - 1);

			// Get data from DB and set aux vars
			$students      = Model\Students::limit($pageSize, $startElement)->all();
			$studentsCount = Model\Students::count_all();
			$pagePrevious  = $pageNumber -1;
			$pageNext      = $pageNumber +1;
			$pageTotal     = ceil($studentsCount / $pageSize);

			if ($pageNumber <= $pageTotal) {
				// Prepare response data
				$data = array();
				foreach ($students as $item) {
					array_push($data, array(
											'id'       => $item->id_students,
											'rut'      => formatRut($item->rut),
											'name'     => $item->name,
											'lastName' => $item->lastName,
											'age'      => $item->age,
											'course'   => $item->course()->name
										));
				}

				$response = array();
				if ($pageNumber == 1) {
					$response["self"]     = base_url()."api/students/students";
				}else{
					$response["self"]     = base_url()."api/students/students?page=".$pageNumber;
					$response["previous"] = base_url()."api/students/students?page=" . $pagePrevious;
				}

				if ($pageNumber < $pageTotal) {
					$response["next"] = base_url()."api/students/students?page=".$pageNext;
				}

				$response["first"]    = base_url()."api/students/students";
				$response["last"]     = base_url()."api/students/students?page=".$pageTotal;
				$response["count"]    = count($students);
				$response["total"]    = $studentsCount;
				$response["students"] = $data;

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
	 * [students_all_get GET Rest API that return a list of all students and their information]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 */
	public function students_all_get(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Check if recived ID is valid
		if ($this->checkStudentID($id)) {

			// Get data from DB
			$students = Model\Students::all();

			// Prepare response data
			$data = array();
			foreach ($students as $item) {
				array_push($data, array(
										'id'       => $item->id_students,
										'rut'      => formatRut($item->rut),
										'name'     => $item->name,
										'lastName' => $item->lastName,
										'age'      => $item->age,
										'course'   => $item->course()->name
									));
			}

			$response = array("students" => $data);

			try{
				$this->set_response( $response, REST_Controller::HTTP_OK);
			}
			catch(Exception $e) {
				$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		}else{
			$response = array("msg" => "Student id don't exists!");
			$code     = REST_Controller::HTTP_NOT_FOUND;

			$this->set_response( $response, $code);
		}
	}

	/**
	 * [students_by_id_get GET Rest API that return information about the requested student]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [int]        $id
	 */
	public function students_by_id_get($id){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Get data from DB
		$student = Model\Students::find($id);

		// Prepare response data
		$data = array(
					'id'       => $student->id_students,
					'rut'      => formatRut($student->rut),
					'name'     => $student->name,
					'lastName' => $student->lastName,
					'age'      => $student->age,
					'course'   => $student->course()->name

				);

		$response = array("student" => $data);

		try{
			$this->set_response( $response, REST_Controller::HTTP_OK);
		}
		catch(Exception $e) {
			$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * [students_post POST Rest API that creates a new student]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @vars [JSON]   [JSON with data for the new student]
	 */
	public function students_post(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers(), true);

		// Get data from POST
		$auxJson = json_decode(file_get_contents('php://input'), TRUE);

		// Check JSON syntax
		if (json_last_error() == JSON_ERROR_NONE) {
			$json = cleanJSON($auxJson);
			// Check Student data from JSON
			$checkData = $this->checkStudentData($json);

			if ($checkData["valid"]) {
				// Insert Student to DB
				$json["rut"]        = cleanRut($json["rut"]);
				$json["id_courses"] = $json["course"];
				unset($json["course"]);

				$newStudent = Model\Students::make($json);

				if ($newStudent->save(TRUE)) {
					$response = array("msg" => "Student inserted correctly with ID ".Model\Students::last_created()->id_students);
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
	 * [students_put PUT Rest API that update the requested student]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @vars [JSON]   [JSON with data to update the requested student]
	 * @vars [int]   [ID of student to update]
	 */
	public function students_put(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers(), true);

		// Get data from GET
		$idStudent = intval($this->input->get("id"));

		// Check if recived ID is valid
		if ($this->checkStudentID($idStudent)) {

			// Get data from POST
			$auxJson = json_decode(file_get_contents('php://input'), TRUE);

			// Check JSON syntax
			if (json_last_error() == JSON_ERROR_NONE) {
				$json = cleanJSON($auxJson);
				$checkData = $this->checkStudentData($json, $idStudent);

				if ($checkData["valid"]) {
					// Get Student from DB
					$student             = Model\Students::find($idStudent);
					// Modify Student with recived data
					$student->rut        = cleanRut($json["rut"]);
					$student->name       = $json["name"];
					$student->lastName   = $json["lastName"];
					$student->age        = $json["age"];
					$student->name       = $json["name"];
					$student->id_courses = $json["course"];

					// Save changed to DB
					if ($student->save(FALSE)) {
						$response = array("msg" => "Student with ID ".$idStudent." updated correctly");
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
			$response = array("msg" => "Student id don't exists!");
			$code     = REST_Controller::HTTP_NOT_FOUND;

			$this->set_response( $response, $code);
		}
	}

	/**
	 * [students_put DELETE Rest API that delete the requested student]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @vars [int]   [ID of student to delete]
	 */
	public function students_delete(){

		// Validate token
		AUTHORIZATION::verify_request($this->input->request_headers());

		// Get data from GET
		$idStudent = intval($this->input->get("id"));

		// Check if recived ID is valid
		if($this->checkStudentID($idStudent)){

			// Get Student from DB
			$student = Model\Students::find($idStudent);

			// Delete Student from DB
			if (Model\Students::delete($idStudent)) {
				$response = array("msg" => "Student with ID ".$idStudent." deleted correctly");
				$code     = REST_Controller::HTTP_OK;
			}else{
				$response = array("msg" => "Something went wrong!");
				$code     = REST_Controller::HTTP_INTERNAL_SERVER_ERROR;
			}

			try{
				$this->set_response( $response, $code);
			}
			catch(Exception $e) {
				$this->set_response( $e, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}

		}else{
			$response = array("msg" => "Student id don't exists!");
			$code     = REST_Controller::HTTP_NOT_FOUND;

			$this->set_response( $response, $code);
		}
	}
}