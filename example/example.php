<?php

namespace Clever;

require "vendor/autoload.php";

$request = new Request;
$request->setAuthorization("DEMO_TOKENZ");

$service = new Service($request, new ServiceLogger);

$D = new CleverDistrict($service, "4fd43cc56d11340000000005");

drop($D->schools());

