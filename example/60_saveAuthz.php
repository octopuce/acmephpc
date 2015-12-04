<?php

require_once("bootstrap.php");

$id=12;
$authzid=4;
$type="http-01";

echo "Asking for a challenge solving...\n";

$client->getReg($id);

print_r(
        $client->getAuthz($authzid,true)
        );

