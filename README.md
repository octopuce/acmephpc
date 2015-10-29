# Let's Encrypt / ACME Protocol PHP Client Library

This is an [Acme Protocol](https://letsencrypt.github.io/acme-spec/) implementation written fully using PHP language. It allows you to create manage and revoke certificates using ACME protocol used by [Let's Encrypt](https://www.letsencrypt.org/) 

Its aim is to be used by hosting control panel software and hosting companies using PHP for their hosting panel. 

# Install & dependencies

We use [PSR-norms](http://www.php-fig.org/psr/) for PHP to build this library, mainly PSR 0,1,2,3,4.
Thanks to that, you can use a `composer.json` description file or `./composer.phar install octopuce/acmephpc` to get this library. All dependencies will follow. 

The dependencies are: [gree/jose](https://github.com/gree/jose) for [Json Web Signature](https://tools.ietf.org/html/rfc7515) implementation, [phpseclib](https://github.com/phpseclib/phpseclib) for PHP RSA and X.509 implementation, and [phpunit](https://github.com/phpunit/phpunit) if you want to launch unit tests. 

# How to use this library

This library consists of the following classes and interfaces: 

The main **Octopuce\Acme\Client** class, with public methods to launch API calls to ACME-Compliant  
server, creating accounts (_reg_) Authorization on domains (_authz_), solving _challenges_ to prove you own those domains, and asking for certificates (_cert_) or revocation of existing ones (_revoke_). 

This library depends on the following others, provided either with a fully-working code, or with example of Interface you'll have to customize:

* a Storage Interface implementing **Octopuce\Acme\StorageInterface**. a **StoragePdo** class is provided, along with the SQL schema for MySQL. The schema is very simple and is known to work with Postgresql or Sqlite too. This stores the private keys of accounts and certificates, the certificates and the authorization and challenges objects locally. You may want to implement a link to your favorite HSM here...

* an HTTP Client Interface, implementing **Octopuce\Acme\HttpClientInterface**. a **HttpClientCurl** class is provided, using php5-curl calls to do HTTP Get or Post calls. If you want to implement your own for some reasons, please know that we need to do Get and Post HTTP calls, and be able to get the headers answered by the HTTP, since ACME protocol use headers to provide with useful information.

* a (non mandatory) PSR-3 Logger Interface, implementing **\Psr\Log\LoggerInterface**. No example is provided, use existing code to store your logs where you want.

* an SSL Interface, implementing **Octopuce\Acme\SslInterface**. a **SslPhpseclib** class is provided, using phpseclib to provide you with the necessary SSL and X.509 methods to create RSA keys, generate CSR, revocation requests, and convert PEM-encoded X.509 structure to DER.

To prove you own a domain name, Acme ask you to solve a **challenge**, either :

* by publishing a HTTP page at a .well-known URL, 
* or by answering a challenge using SNI on the domain's server, 
* or by setting some records in your domain's DNS zone. 

Those challenge need to interact with the system of your server, so you'll likely change the code we give you there. That said, we provide you with example for AlternC, a free-software web control panel for Debian GNU/Linux. Those challenge-solving classes are plugins implementing the **Octopuce\Acme\ValidationPluginInterface**. This is the **ValidationApache** class. 

If your plugin can't configure a DNS, HTTP or SNI challenge at once, your plugin can answer with a "in progress" reply, so the ACME Library will have to ask for it again later, and your plugin will have to check that the challenge is ready before saying "OK". Then we will ask the ACME server to check the challenge.

 
# Examples & tests

The tests/ folder contains unit tests you can launch using phpunit library. They test all features and exceptions and should work fine.

The example/ folder contains example you can run, after changing the config.sample.php, then launch the <10-100>_*.php scripts in that order for each step of the ACME certificate enrollment process. It also shows you how you can use this library. 

# Authors, community license, how to help...

[Benjamin Sonntag](https://benjamin.sonntag.fr) is the main author of this library, mainly for [Octopuce, his hosting company](https://www.octopuce.fr/) in Paris, France, and for [AlternC, a hosting control panel free software](https://www.alternc.com/).

If you want to help improving or managing this library, you can start by reporting bugs using github bug reporting, or just contact us by sending a mail to _benjamin at octopuce dot fr_. 

This software is distributed under LGPLv2+ license. see the [LICENSE](LICENSE) file for complete license terms. 
