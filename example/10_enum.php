<?php

require_once("bootstrap.php");

echo "We enumerate the API endpoints and get a Nonce stored in acme_status:\n";

print_r(
        $client->enumApi()
        );


