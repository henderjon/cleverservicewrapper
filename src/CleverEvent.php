<?php

namespace Clever;

class CleverEvent {

	protected $service;

	protected $id;

	function __construct(Service $service){
		$this->service = $service;
	}

	function all(array $params = []){
		$endpoint = "events";
		return $this->service->get($endpoint, $params);
	}

}
