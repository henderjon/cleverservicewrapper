<?php

use Psr\Log;

class NoOutPutLogger extends Log\AbstractLogger {
	protected $context;
	function log($level, $message, array $context = []){
		$context["log.level"]   = $level;
		$context["log.message"] = $message;
		$this->context = $context;
	}
	function getContext(){ return $this->context; }
}

class ServiceWrapperTest extends PHPUnit_Framework_TestCase {

	function getService(){
		return new \Clever\ServiceWrapper("DEMO_TOKEN");
	}

	function test_construct(){
		$inst = $this->getService();
		$this->assertInstanceOf("\\Clever\\ServiceWrapperInterface", $inst);
	}

	function test_getCleverDistrict(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005";

		$result = $inst->getCleverDistrict($id);

		$this->assertInstanceOf("\\CleverDistrict", $result);
		$this->assertEquals($id, $result->id);
	}

	function test_getCleverSchool(){
		$inst = $this->getService();

		$id = "530e595026403103360ff9fd";

		$result = $inst->getCleverSchool($id);

		$this->assertInstanceOf("\\CleverSchool", $result);
		$this->assertEquals($id, $result->id);
	}

	function test_getCleverSection(){
		$inst = $this->getService();

		$id = "530e5979049e75a9262d0af2";

		$result = $inst->getCleverSection($id);

		$this->assertInstanceOf("\\CleverSection", $result);
		$this->assertEquals($id, $result->id);
	}

	function test_getCleverStudent(){
		$inst = $this->getService();

		$id = "530e5960049e75a9262cff1d";

		$result = $inst->getCleverStudent($id);

		$this->assertInstanceOf("\\CleverStudent", $result);
		$this->assertEquals($id, $result->id);
	}

	function test_getCleverTeacher(){
		$inst = $this->getService();

		$id = "509fbd7ec474fab64a8e9d53";

		$result = $inst->getCleverTeacher($id);

		$this->assertInstanceOf("\\CleverTeacher", $result);
		$this->assertEquals($id, $result->id);
	}

	function test_getCleverEvent(){
		$inst = $this->getService();

		$id = "53ff6e6b322eced002000088";

		$result = $inst->getCleverEvent($id);

		$this->assertInstanceOf("\\CleverEvent", $result);
		$this->assertEquals($id, $result->id);
	}

	function test_ShouldBreak(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005a";

		// should 404
		$dist = $inst->getCleverDistrict($id);

		$this->assertEquals(null, $dist);
	}

	/**
	 * @expectedException Clever\ServiceWrapperException
	 */
	function test_ServiceWrapperException(){
		$inst = $this->getService();
		$inst->setToken("DEMO_TOKEN");
		$inst->setRetries(2); // test the sleep()
		$inst->setInterval(0);

		$id = "4fd43cc56d11340000000005";

		$dist = $inst->getCleverDistrict($id);

		$result = $inst->ping($dist, "events", ["starting_after" => "4fd43cc56d11340000000005", "page" => "2"]);

	}

	function test_logging(){
		$inst = $this->getService();

		$logger = new NoOutPutLogger;

		$inst->setLogger($logger);

		$id = "4fd43cc56d11340000000005a";

		$dist = $inst->getCleverDistrict($id);

		$expected = [
			"e.errno"          => 0,
			"e.error"          => "",
			"e.httpstatus"     => 404,
			"e.httpbody"       => "Not Found",
			"e.jsonbody"       => null,
			"e.file"           => "vendor/clever/clever/lib/Clever/ApiRequestor.php",
			"e.line"           => 66,
			"lib.version"      => \Clever::VERSION,
			"lib.apibase"      => \Clever::$apiBase,
			"request.object"   => ["CleverDistrict" => $id],
			"request.endpoint" => "refresh",
			"request.query"    => [],
			"request.token"    => "DEMO_TOKEN",
			"loop.timestamp"   => date("c (e)"),
			"loop.sleep"       => 1,
			"loop.interval"    => 1,
			"loop.iteration"   => 1,
			"log.level"        => "alert",
			"log.message"      => "CleverInvalidRequestError",
		];

		$result = $logger->getContext();

		$this->assertEquals($expected, $result);

	}

}

