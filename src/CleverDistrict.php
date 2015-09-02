<?php

namespace Clever;

class CleverDistrict {

	protected $service;

	protected $id;

	function __construct(Service $service, $id){
		$this->service = $service;
		$this->id = $id;
	}

	function schools(array $params = []){
		$endpoint = "districts/{$this->id}/schools";
		return $this->service->get($endpoint, $params);
	}

	function teachers(array $params = []){
		$endpoint = "districts/{$this->id}/teachers";
		return $this->service->get($endpoint, $params);
	}

	function students(array $params = []){
		$endpoint = "districts/{$this->id}/students";
		return $this->service->get($endpoint, $params);
	}

	function sections(array $params = []){
		$endpoint = "districts/{$this->id}/sections";
		return $this->service->get($endpoint, $params);
	}

}
