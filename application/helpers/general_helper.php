<?php
if (!function_exists('cleanJSON')){
	/**
	 * [cleanJSON Function that clean whitespaces from a given JSON]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  $auxJson [JSON to clean]
	 */
	function cleanJSON($auxJson){
		$json = array();
		foreach ($auxJson as $key => $value) {
			$json[trim($key)] = trim($value);
		}

		return $json;
	}
}

if (!function_exists('validateRut')) {
	/**
	 * [validateRut Function that validate if a string is a valid chilean rut or not]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [String]        $rut [Rut to validate]
	 */
	function validateRut($rut){
	    if (strlen($rut) < 8 || !preg_match("/^[0-9.]+[-]?+[0-9kK]{1}/", $rut)) {
	        return false;
	    }

	    $rut = preg_replace('/[\.\-]/i', '', $rut);
	    $dv = substr($rut, -1);
	    $numero = substr($rut, 0, strlen($rut) - 1);
	    $i = 2;
	    $suma = 0;
	    foreach (array_reverse(str_split($numero)) as $v) {
	        if ($i == 8)
	            $i = 2;
	        $suma += $v * $i;
	        ++$i;
	    }
	    $dvr = 11 - ($suma % 11);

	    if ($dvr == 11)
	        $dvr = 0;
	    if ($dvr == 10)
	        $dvr = 'K';
	    if ($dvr == strtoupper($dv))
	        return true;
	    else
	        return false;
	}
}

if (!function_exists('cleanRut')) {
	/**
	 * [cleanRut Function that clean a chilean rut to insert on DB]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [String]        $rut [Clean rut to insert on db]
	 */
	function cleanRut($rut){
	    return $rut = preg_replace('/[\.\-]/i', '', $rut);
	}
}

if (!function_exists('formatRut')) {
	/**
	 * [formatRut Function that format a chilean rut]
	 * @author Oscar García Chávez
	 * @date   2020-04-20
	 * @param  [String]        $rut [Rut to format]
	 */
	function formatRut($rut){
	    $rutNumber = mb_substr($rut, 0, -1);
	    $rutDigit = substr($rut, -1);
		return number_format( $rutNumber, 0, "", ".") . '-' . $rutDigit;
	}
}
?>


