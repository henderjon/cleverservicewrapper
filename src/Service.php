<?php

namespace Clever;

use \Psr\Log;

class Service implements \Serializable, \JsonSerializable {

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
	 * HTTP Statuses that break the retry loop
	 */
	protected $breakStatuses = [404];

	/**
	 * create a new ServiceWrapper instance
	 *
	 * @param string $token The auth token for a given district
	 * @param Log\LoggerInterface $logger A place to log errors
	 * @param int $interval The number of seconds to add to sleep() after each failure
	 * @param int $retries The number of times to retry an individual call
	 */
	function __construct(Request $request, Log\LoggerInterface $logger = null, $interval = 1, $retries = 100){
		$this->request = $request;
		$this->logger   = $logger;
		$this->interval = $interval;
		$this->retries  = $retries;
	}

	/**
	 * alias ping by making the Wrapper callable
	 */
	function __invoke($base, $endpoint, array $query = []){
		return $this->ping($object, $endpoint, $query);
	}

	/**
	 * ping the Clever API for a given object/endpoint/query and evaluate the response. If an
	 * error is thrown from the Clever PHP SDK, log it, decide to retry using an exponential
	 * backoff of +1 second (by default) for up to 100 retries (by default).
	 *
	 * @param CleverObject $object An instance of a CleverObject
	 * @param string $endpoint The endpoint/method to call on the provided CleverObject
	 * @param array $query Query params to pass to that method based on Clever's API docs
	 * @return \CleverObject
	 */
	function get($endpoint, array $query = []) {
		$iteration   = 0;
		$this->sleep = 1;
		while($iteration += 1){
			try{
				$this->request->get($endpoint, $query);
				return $this->request->getResponse();
			}catch(\Exception $e){
				if($this->logger InstanceOf Log\LoggerInterface){
					$this->logger->alert(get_class($e), [
						"e.errno"          => $e->getCode(),
						"e.error"          => $e->getMessage(),
						"e.file"           => $this->cleanPath($e),
						"e.line"           => $e->getLine(),
						"request.endpoint" => $endpoint,
						"request.query"    => $query,
						"request.request"  => $this->request->toArray(),
						"service.params"   => $this->toArray(),
						"loop.timestamp"   => date("c (e)"),
						"loop.sleep"       => $this->sleep,
						"loop.interval"    => $this->interval,
						"loop.iteration"   => $iteration,
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

	/**
	 * hide the full file system path in case the logger spits out data to the
	 * public
	 */
	protected function cleanPath(\Exception $e){
		// assuming that Clever was installed via composer
		$basePath = "vendor" . DIRECTORY_SEPARATOR;
		if(($pos = strpos($e->getFile(),  $basePath)) !== false){
			return substr($e->getFile(), $pos);
		}
		return basename($e->getFile());
	}

	/**
	 * decide if the http status should break the loop.
	 *
	 * In some cases (e.g. a 404 error) the requested resource isn't there
	 * and won't be there. Asking once or asking 5 million times won't change
	 * that response. In those instances, break the loop (having logged the error)
	 * and move on.
	 */
	protected function shouldBreak(\Exception $e){
		return in_array($e->getCode(), $this->breakStatuses);
	}

	/**
	 * set/change the interval after the fact
	 */
	function setInterval($interval){
		$this->interval = $interval;
	}

	/**
	 * set/change the number of retries after the fact
	 */
	function setRetries($retries){
		$this->retries = $retries;
	}

	function __toString(){
		return json_encode($this->toArray());
	}

	function serialize(){
		return serialize($this->toArray());
	}

	function unserialize($serialized){
		//noop
	}

	function toArray(){
		return $this->jsonSerialize();
	}

	function jsonSerialize(){
		return $this->__debugInfo();
	}

	function __debugInfo(){
		return [
			"lib.retries"    => $this->retries,
			"lib.interval"   => $this->interval,
			"call.timestamp" => date("c (e)"),
		];
	}

}
