<?php

namespace Clever;

use \Psr\Log;

class ServiceWrapper implements ServiceWrapperInterface {

	use Log\LoggerAwareTrait;

	/**
	 * how many retires are we going to allow
	 */
	protected $retries = 100;

	/**
	 * the duration of sleep()
	 */
	protected $sleep = 1;

	/**
	 * for exponential backoff, increase the sleep time by $interval after each iteration
	 */
	protected $interval = 1;

	/**
	 * the clever auth token, stored here to pass to the logger
	 */
	protected $token;

	/**
	 * HTTP Statuses that break the retry loop
	 */
	protected $breakStatuses = [404];

	/**
	 *
	 */
	function __construct($token, Log\LoggerInterface $logger = null, $interval = 1, $retries = 100){

		\Clever::setToken(($this->token = $token));

		$this->logger   = $logger;
		$this->interval = $interval;
		$this->retries  = $retries;
	}

	/**
	 * ping clever
	 *
	 * @param string $path The path to the csv
	 * @return SplFileObject
	 */
	function ping(\CleverObject $object, $endpoint, array $query = []) {
		$iteration = 0;
		while($iteration += 1){
			try{
				return call_user_func([$object, $endpoint], $query);
			}catch(\CleverError $e){
				if($this->logger InstanceOf Log\LoggerInterface){
					$this->logger->alert(get_class($e), [
						"e.errno"          => $e->getCode(),
						"e.error"          => $e->getMessage(),
						"e.httpstatus"     => $e->getHttpStatus(),
						"e.httpbody"       => $e->getHttpBody(),
						"e.jsonbody"       => $e->getJsonBody(),
						"e.file"           => $this->getPath($e),
						"e.line"           => $e->getLine(),
						"lib.version"      => \Clever::VERSION,
						"lib.apibase"      => \Clever::$apiBase,
						"request.object"   => json_encode([get_class($object) => $object->id]),
						"request.endpoint" => $endpoint,
						"request.query"    => json_encode($query),
						"request.token"    => $this->token,
						"timestamp"        => date("c (e)"),
						"sleep"            => $this->sleep,
						"interval"         => $this->interval,
						"iteration"        => $iteration,
					]);
				}

				if($this->shouldBreak($e)){
					break;
				}

				if($iteration >= $this->retries){
					throw new ServiceWrapperException("Clever API: Max retry limit ({$this->retries}) reached.", 999, $e);
				}

				sleep($this->sleep += $this->interval);
			}
		}
	}

	protected function getPath(\CleverError $e){
		// assuming that Clever was installed via composer
		$basePath = "vendor" . DIRECTORY_SEPARATOR;
		if(($pos = strpos($e->getFile(),  $basePath)) !== false){
			return substr($e->getFile(), $pos);
		}
		return basename($path);
	}

	protected function shouldBreak(\CleverError $e){
		return in_array($e->getHttpStatus(), $this->breakStatuses);
	}

	/**
	 * get the clever object for that ID
	 */
	function getCleverDistrict($id){
		return $this->ping(new \CleverDistrict($id), "refresh");
		// return \CleverDistrict::retrieve($id);
	}

	/**
	 * get the clever object for that ID
	 */
	function getCleverSchool($id){
		return $this->ping(new \CleverSchool($id), "refresh");
		// return \CleverSchool::retrieve($id);
	}

	/**
	 * get the clever object for that ID
	 */
	function getCleverStudent($id){
		return $this->ping(new \CleverStudent($id), "refresh");
		// return \CleverStudent::retrieve($id);
	}

	/**
	 * get the clever object for that ID
	 */
	function getCleverSection($id){
		return $this->ping(new \CleverSection($id), "refresh");
		// return \CleverSection::retrieve($id);
	}

	/**
	 * get the clever object for that ID
	 */
	function getCleverTeacher($id){
		return $this->ping(new \CleverTeacher($id), "refresh");
		// return \CleverTeacher::retrieve($id);
	}

	/**
	 * get the clever object for that ID
	 */
	function getCleverEvent($id){
		return $this->ping(new \CleverEvent($id), "refresh");
		// return \CleverEvent::retrieve($id);
	}

	/**
	 * get a generic clever object for testing
	 */
	function getCleverObject(){
		return new \CleverObject;
		// return \CleverEvent::retrieve($id);
	}

	/**
	 * setters for use in testing/injecting
	 */
	function setToken($token){
		\Clever::setToken(($this->token = $token));
	}

	/**
	 * setters for use in testing/injecting
	 */
	function setInterval($interval){
		$this->interval = $interval;
	}

	/**
	 * setters for use in testing/injecting
	 */
	function setRetries($retries){
		$this->retries = $retries;
	}

}