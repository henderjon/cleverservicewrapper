<?php

namespace Clever;

use \Psr\Log;

class ServiceLogger extends Log\AbstractLogger {

	function log($level, $message, array $context = []){

		$context = [
			"log.level"     => strtoupper($level),
			"log.message"   => $message,
			"log.timestamp" => date("c (e)"),
		] + $context;

		$len = 0;
		foreach($context as $key => $value){
			if( ($l = strlen($key)) > $len){ $len = $l; }
		}

		$output = "\n\n" . str_repeat("-", 72) . "\n\n";

		foreach($context as $key => $value){
			$output .= sprintf("%{$len}s => %-7s => %s\n", $key, gettype($value), json_encode($value));
		}

		$output .= "\n\n\n";

		echo $output;

	}

}