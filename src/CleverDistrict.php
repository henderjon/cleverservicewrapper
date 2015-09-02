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

	function teachers(){
		$endpoint = "districts/{$this->id}/teachers";
		return $this->service->get($endpoint, $params);
	}

	function students(){
		$endpoint = "districts/{$this->id}/students";
		return $this->service->get($endpoint, $params);
	}

	function sections(){
		$endpoint = "districts/{$this->id}/sections";
		return $this->service->get($endpoint, $params);
	}

}
