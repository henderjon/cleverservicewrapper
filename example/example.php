<?php

require "vendor/autoload.php";

$clever   = new \Clever\ServiceWrapper("DEMO_TOKEN", new \Clever\ServiceLogger);
$district = $clever->getCleverDistrict("4fd43cc56d11340000000005"); // mess wtih this value to see the output

if(!$district){
	exit(1);
}

$params["limit"] = 2;
while( $events = $clever->ping( $district, "events", $params ) ) {
	foreach($events as $event){
		$params["starting_after"] = $event->id;
		echo "{$event->id} -> {$event->type}\n";
	}
}

exit(0);