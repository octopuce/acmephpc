<?php

require_once("bootstrap.php");

$id = 24;
$authz = array("type" => "dns", "value" => "sonntag.fr");

echo "We ask for a new Authz:\n";

// print_r($client->getReg($id));

print_r(
        $client->newAuthz($authz)
        );

