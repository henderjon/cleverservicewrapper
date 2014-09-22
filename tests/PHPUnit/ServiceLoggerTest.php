<?php

class ServiceLoggerTest extends PHPUnit_Framework_TestCase {

	function test_UserFuncLogger(){

		$logger = new \Clever\ServiceLogger;

		ob_start();
		$logger->alert("five", [555, true, null, false]);
		$result = ob_get_clean();

		$expected = "\n\n" . str_repeat("-", 72) . "\n\n";
		$expected .= "    log.level => string  => \"ALERT\"\n";
		$expected .= "  log.message => string  => \"five\"\n";
		$expected .= "log.timestamp => string  => \"".date("c (e)")."\"\n";
		$expected .= "            0 => integer => 555\n";
		$expected .= "            1 => boolean => true\n";
		$expected .= "            2 => NULL    => null\n";
		$expected .= "            3 => boolean => false\n";
		$expected .= "\n\n\n";

		$this->assertEquals($expected, $result);

	}

}