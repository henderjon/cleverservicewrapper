<?php

require "vendor/autoload.php";

$clever   = new \Clever\ServiceWrapper("DEMO_TOKEN", new \Clever\ServiceLogger);
$district = $clever->getCleverDistrict("4fd43cc56d11340000000005"); // mess with this value to see the output

if(!$district){
	exit(1);
}

$params["limit"] = 2;
while( $schools = $clever( $district, $clever::SCHOOLS, $params ) ) {
	foreach($schools as $school){
		$params["starting_after"] = $school->id;
		echo "{$school->id} -> {$school->name}\n";
	}
}

exit(0);