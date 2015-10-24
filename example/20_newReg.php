<?php

require_once("bootstrap.php");

$contactinfo = array("mailto"=>"test105@sonntag.fr");

echo "We create an account using static informations:\n";

print_r(
        $client->newReg($contactinfo)
);

