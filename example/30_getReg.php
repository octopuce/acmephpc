<?php

require_once("bootstrap.php");

$id = 12;

echo "We get account information:\n";

print_r(
        $client->getReg($id,true)
);

