<?php namespace Model;

/* This basic model has been auto-generated by the Gas ORM */

use \Gas\Core;
use \Gas\ORM;

class Courses extends ORM {

	public $primary_key = 'id_courses';

	function _init(){

		// Relationship definition
		self::$relationships = array (
                'students'	=>	ORM::has_many('\\Model\\Students')
        );

        // Fields definition
		self::$fields = array(
			'id_courses' => ORM::field('auto'),
			'name' => ORM::field('char[255]'),
			'code' => ORM::field('char[4]'),
		);

	}
}