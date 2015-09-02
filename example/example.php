<?php

namespace Clever;

require "vendor/autoload.php";

$request = new Request;
$request->setAuthorization("DEMO_TOKEN");

$service = new Service($request, new ServiceLogger);

$D = new CleverDistrict($service, "4fd43cc56d11340000000005");

drop(json_decode($D->schools())->data[0]->data);

