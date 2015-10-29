# Let's Encrypt / ACME Protocol PHP Client Library

This is an [Acme Protocol](https://letsencrypt.github.io/acme-spec/) implementation written fully using PHP language. It allows you to create manage and revoke certificates using ACME protocol used by [Let's Encrypt](https://www.letsencrypt.org/) 

Its aim is to be used by hosting control panel software and hosting companies using PHP for their hosting panel. 

# Install & dependencies

We use [PSR-norms](http://www.php-fig.org/psr/) for PHP to build this library, mainly PSR 0,1,2,3,4.
Thanks to that, you can use a `composer.json` description file or `./composer.phar install octopuce/acmephpc` to get this library. All dependencies will follow. 

The dependencies are: [gree/jose](https://github.com/gree/jose) for [Json Web Signature](https://tools.ietf.org/html/rfc7515) implementation, [phpseclib](https://github.com/phpseclib/phpseclib) for PHP RSA and X.509 implementation, and [phpunit](https://github.com/phpunit/phpunit) if you want to launch unit tests. 

# How to use this library

This library has some code dependencies, but most are provided either fully working or as examples. Those 

# Authors, community license, how to help...

[Benjamin Sonntag](https://benjamin.sonntag.fr) is the main author of this library, mainly for [Octopuce, his hosting company](https://www.octopuce.fr/) in Paris, France, and for [AlternC, a hosting control panel free software](https://www.alternc.com/).

If you want to help improving or managing this library, you can start by reporting bugs using github bug reporting, or just contact us by sending a mail to _benjamin at octopuce dot fr_. 

This software is distributed under LGPLv2+ license. see the [LICENSE](LICENSE) file for complete license terms. 

