<?php

namespace Clever;

interface ServiceWrapperInterface {

	/**
	 *
	 */
	function __construct($token);

	/**
	 * ping clever
	 *
	 * @param string $path The path to the csv
	 * @return SplFileObject
	 */
	function ping(\CleverObject $object, $endpoint, array $query = array());

	/**
	 * get the clever object for that ID
	 */
	function getCleverDistrict($id);

	/**
	 * get the clever object for that ID
	 */
	function getCleverSchool($id);

	/**
	 * get the clever object for that ID
	 */
	function getCleverStudent($id);

	/**
	 * get the clever object for that ID
	 */
	function getCleverSection($id);

	/**
	 * get the clever object for that ID
	 */
	function getCleverTeacher($id);

	/**
	 * get the clever object for that ID
	 */
	function getCleverEvent($id);

}