<?php

require_once("bootstrap.php");

$id = 24;

echo "We get account information:\n";

print_r(
        $client->getReg($id)
);

