<?php

require_once("bootstrap.php");

$id = 12;
$authz = array("type" => "dns", "value" => "octopuce.fr");

echo "We ask for a new Authz:\n";

print_r($client->getReg($id));

print_r(
        $client->newAuthz($authz)
        );

