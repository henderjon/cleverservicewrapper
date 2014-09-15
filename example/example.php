<?php

require "vendor/autoload.php";

use Psr\Log;

class TmpLogger extends Log\AbstractLogger {
	function log ($level, $message, array $context = []){
		$context = ["level" => strtoupper($level), "message" => $message, "timestamp" => date("c (e)")] + $context;

		$len = 0;
		foreach($context as $key => $value){
			if( ($l = strlen($key)) > $len){ $len = $l; }
		}

		$output = "\n\n".str_repeat("-", 72)."\n\n";

		foreach($context as $key => $value){
			$output .= sprintf("%{$len}s => (%s)%s\n", $key, gettype($value), json_encode($value));
		}

		$output .= "\n\n\n";

		echo $output;
	}
}

$clever   = new \Clever\ServiceWrapper("DEMO_TOKEN", new TmpLogger);
$district = $clever->getCleverDistrict("4fd43cc56d11340000000005"); // mess wtih this value to see the output

if(!$district){
	exit(1);
}

$params = ["limit" => 50];
while( $events = $clever->ping( $district, "events", $params ) ) {
	foreach($events as $event){
		$params["starting_after"] = $event->id];
		echo "{$event->id} -> {$event->type}\n";
	}
}

exit(0);