<?php

namespace Clever;

class ServiceWrapper implements ServiceWrapperInterface {

	protected $logger;

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
	protected $breakStatuses = array(404);

	/**
	 * create a new ServiceWrapper instance
	 *
	 * @param string $token The auth token for a given district
	 * @param \Psr\Log\LoggerInterface $logger A place to log errors
	 * @param int $interval The number of seconds to add to sleep() after each failure
	 * @param int $retries The number of times to retry an individual call
	 */
	function __construct($token, \Psr\Log\LoggerInterface $logger = null, $interval = 1, $retries = 100){

		\Clever::setToken(($this->token = $token));

		$this->logger   = $logger;
		$this->interval = $interval;
		$this->retries  = $retries;
	}

	/**
	 * alias ping by making the Wrapper callable
	 */
	function __invoke(\CleverObject $object, $endpoint, array $query = array()){
		return $this->ping($object, $endpoint, $query);
	}

    public function setLogger(\Psr\Log\LoggerInterface $logger){
        $this->logger = $logger;
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
	function ping(\CleverObject $object, $endpoint, array $query = array()) {
		$iteration   = 0;
		$this->sleep = 1;
		while($iteration += 1){
			try{
				return call_user_func(array($object, $endpoint), $query);
			}catch(\CleverError $e){
				if($this->logger InstanceOf \Psr\Log\LoggerInterface){
					$this->logger->alert(get_class($e), array(
						"e.errno"          => $e->getCode(),
						"e.error"          => $e->getMessage(),
						"e.httpstatus"     => $e->getHttpStatus(),
						"e.httpbody"       => $e->getHttpBody(),
						"e.jsonbody"       => $e->getJsonBody(),
						"e.file"           => $this->getPath($e),
						"e.line"           => $e->getLine(),
						"lib.version"      => \Clever::VERSION,
						"lib.apibase"      => \Clever::$apiBase,
						"request.object"   => array(get_class($object) => $object->id),
						"request.endpoint" => $endpoint,
						"request.query"    => $query,
						"request.token"    => $this->token,
						"loop.timestamp"   => date("c (e)"),
						"loop.sleep"       => $this->sleep,
						"loop.interval"    => $this->interval,
						"loop.iteration"   => $iteration,
					));
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
	protected function getPath(\CleverError $e){
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
	protected function shouldBreak(\CleverError $e){
		return in_array($e->getHttpStatus(), $this->breakStatuses);
	}

	/**
	 * get the CleverDistrict object for that ID. Using "refresh" allows initial object
	 * calls to pass through ping.
	 */
	function getCleverDistrict($id){
		return $this->ping(new \CleverDistrict($id), "refresh");
		// return \CleverDistrict::retrieve($id);
	}

	/**
	 * get the CleverSchool object for that ID. Using "refresh" allows initial object
	 * calls to pass through ping.
	 */
	function getCleverSchool($id){
		return $this->ping(new \CleverSchool($id), "refresh");
		// return \CleverSchool::retrieve($id);
	}

	/**
	 * get the CleverStudent object for that ID. Using "refresh" allows initial object
	 * calls to pass through ping.
	 */
	function getCleverStudent($id){
		return $this->ping(new \CleverStudent($id), "refresh");
		// return \CleverStudent::retrieve($id);
	}

	/**
	 * get the CleverSection object for that ID. Using "refresh" allows initial object
	 * calls to pass through ping.
	 */
	function getCleverSection($id){
		return $this->ping(new \CleverSection($id), "refresh");
		// return \CleverSection::retrieve($id);
	}

	/**
	 * get the CleverTeacher object for that ID. Using "refresh" allows initial object
	 * calls to pass through ping.
	 */
	function getCleverTeacher($id){
		return $this->ping(new \CleverTeacher($id), "refresh");
		// return \CleverTeacher::retrieve($id);
	}

	/**
	 * get the CleverEvent object for that ID. Using "refresh" allows initial object
	 * calls to pass through ping.
	 */
	function getCleverEvent($id){
		return $this->ping(new \CleverEvent($id), "refresh");
		// return \CleverEvent::retrieve($id);
	}

	/**
	 * get a generic CleverObject for testing, __GET, __SET lets us use
	 * this as a mock if we want.
	 */
	function getCleverObject(){
		return new \CleverObject;
		// return \CleverEvent::retrieve($id);
	}

	/**
	 * set/change the token after the fact
	 */
	function setToken($token){
		\Clever::setToken(($this->token = $token));
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

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const DISTRICT = "district";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const SCHOOLS = "schools";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const SCHOOL = "school";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const TEACHERS = "teachers";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const TEACHER = "teacher";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const STUDENTS = "students";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const SECTIONS = "sections";

	/**
	 * constant naming an endpoint of the Clever API
	 */
	const EVENTS = "events";

	/**
	 * constant naming an endpoint of the Clever API
	 * CURRENTLY NOT AN OBJECT IN CLEVER-PHP
	 */
	// const ADMINS = "admins";

	/**
	 * constant naming an endpoint of the Clever API
	 * CURRENTLY NOT AN OBJECT IN CLEVER-PHP
	 */
	// const STATUS = "status";

	/**
	 * constant naming an endpoint of the Clever API
	 * CURRENTLY NOT AN OBJECT IN CLEVER-PHP
	 */
	// const GRADELEVELS = "grade_levels";

	/**
	 * constant naming an endpoint of the Clever API
	 * CURRENTLY NOT AN OBJECT IN CLEVER-PHP
	 */
	// const CONTACTS = "contacts";

}