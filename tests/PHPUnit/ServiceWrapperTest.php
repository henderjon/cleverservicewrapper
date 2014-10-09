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

	/**
	 * events come and go, this test doesn't always work
	 */
	// function test_getCleverEvent(){
	// 	$inst = $this->getService();

	// 	$id = "53ff6e6b322eced002000088";

	// 	$result = $inst->getCleverEvent($id);

	// 	$this->assertInstanceOf("\\CleverEvent", $result);
	// 	$this->assertEquals($id, $result->id);
	// }

	function test_ShouldBreak(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005a";

		// should 404
		$dist = $inst->getCleverDistrict($id);

		$this->assertEquals(null, $dist);
	}

	function test___invoke(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005";

		$dist = $inst->getCleverDistrict($id);

		$result = $inst($dist, "schools", array("limit" => "1"));

		$this->assertEquals(1, count($result));

	}

	/**
	 * @expectedException Clever\ServiceWrapperException
	 */
	function test_ServiceWrapperException(){
		$inst = $this->getService();
		$inst->setToken("DEMO_TOKEN"); // coverage
		$inst->setRetries(2); // test the sleep()
		$inst->setInterval(0);

		$id = "4fd43cc56d11340000000005";

		$dist = $inst->getCleverDistrict($id);

		$result = $inst->ping($dist, "events", ["starting_after" => "4fd43cc56d11340000000005", "page" => "2"]);

	}

	function test___toString(){
		$info = [
			"lib.version"    => \Clever::VERSION,
			"lib.apibase"    => \Clever::$apiBase,
			"lib.token"      => $this->token,
			"lib.retries"    => $this->retries,
			"lib.interval"   => $this->interval,
			"call.timestamp" => date("c (e)"),
		];

		$inst = $this->getService();
		$result = (string)$inst;
		$expected = json_encode($info);
		$this->assertEquals(json_encode($info), $result);

		$result = serialize($inst);
		$expected = 'C:21:"Clever\ServiceWrapper":235:{'.serialize($info).'}';
		$this->assertEquals($expected, $result);
	}

	function test_logging_looping(){
		$inst = $this->getService();

		$prop = new \ReflectionProperty($inst, "breakStatuses");
		$prop->setAccessible(true);
		$prop->setValue($inst, []);

		$inst->setRetries(3);

		$logger = new NoOutPutLogger;

		$inst->setLogger($logger);

		$id = "4fd43cc56d11340000000005a";

		try{
			$dist = $inst->getCleverDistrict($id);
		}catch(\Clever\ServiceWrapperException $e){
			// do nothing, we're inspecting the logger output
		}

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
			"loop.sleep"       => 3, // sleep is added to AFTER the log is generated which is why it's -1 from what you might expect
			"loop.interval"    => 1,
			"loop.iteration"   => 3,
			"log.level"        => "alert",
			"log.message"      => "CleverInvalidRequestError",
		];

		$result = $logger->getContext();

		$this->assertEquals($expected, $result);

	}

	function test_sleep_reseting(){
		$inst = $this->getService();

		$prop = new \ReflectionProperty($inst, "breakStatuses");
		$prop->setAccessible(true);
		$prop->setValue($inst, []);

		$inst->setRetries(3);

		$logger = new NoOutPutLogger;

		$inst->setLogger($logger);

		$id = "4fd43cc56d11340000000005a";

		try{
			$dist = $inst->getCleverDistrict($id);
		}catch(\Clever\ServiceWrapperException $e){
			// do nothing, we're inspecting the logger output
		}

		$result = $logger->getContext();

		$this->assertEquals(3, $result["loop.sleep"]);
		$this->assertEquals(3, $result["loop.iteration"]);

		// test the second loop -- make sure the sleep reset
		$prop->setValue($inst, [404]);

		$dist = $inst->getCleverDistrict($id);

		$result = $logger->getContext();

		$this->assertEquals(1, $result["loop.sleep"]);
		$this->assertEquals(1, $result["loop.iteration"]);

	}

	function test_endpoint_schools(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005";

		$district = $inst->getCleverDistrict($id);

		$schools = $inst($district, $inst::SCHOOLS);

		$this->assertInternalType("array", $schools);
		$this->assertInstanceOf("\\CleverSchool", reset($schools));
	}

	function test_endpoint_teachers(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005";

		$district = $inst->getCleverDistrict($id);

		$teachers = $inst($district, $inst::TEACHERS);

		$this->assertInternalType("array", $teachers);
		$this->assertInstanceOf("\\CleverTeacher", reset($teachers));
	}

	function test_endpoint_students(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005";

		$district = $inst->getCleverDistrict($id);

		$students = $inst($district, $inst::STUDENTS);

		$this->assertInternalType("array", $students);
		$this->assertInstanceOf("\\CleverStudent", reset($students));
	}

	function test_endpoint_sections(){
		$inst = $this->getService();

		$id = "4fd43cc56d11340000000005";

		$district = $inst->getCleverDistrict($id);

		$sections = $inst($district, $inst::SECTIONS);

		$this->assertInternalType("array", $sections);
		$this->assertInstanceOf("\\CleverSection", reset($sections));
	}

	// function test_endpoint_district(){
	// 	$inst = $this->getService();

	// 	$id = "530e595026403103360ff9fd";

	// 	$school = $inst->getCleverSchool($id);

	// 	$district = $inst($school, $inst::DISTRICT);

	// 	$this->assertInstanceOf("\\CleverDistrict", $district);
	// }

	// function test_endpoint_school(){}

	// function test_endpoint_school(){
	// 	$inst = $this->getService();

	// 	$id = "530e5979049e75a9262d0af2";

	// 	$section = $inst->getCleverSection($id);

	// 	$school = $inst($section, $inst::SCHOOL);

	// 	$this->assertInstanceOf("\\CleverSchool", $school);
	// }

	// function test_endpoint_teacher(){
	// 	$inst = $this->getService();

	// 	$id = "530e5979049e75a9262d0af2";

	// 	$section = $inst->getCleverSection($id);

	// 	$teacher = $inst($section, $inst::TEACHER);

	// 	$this->assertInstanceOf("\\CleverTeacher", $teacher);
	// }

	/**
	 * changes too often to depend on it
	 */
	// function test_endpoint_events(){}

	/**
	 * not implemented
	 */
	// function test_endpoint_admins(){}

	/**
	 * not implemented
	 */
	// function test_endpoint_status(){}

	/**
	 * not implemented
	 */
	// function test_endpoint_grade_levels(){}

	/**
	 * not implemented
	 */
	// function test_endpoint_contacts(){}

}

