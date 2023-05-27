<?php
define('CERTIFICATE_FILE', 'selfSigned');

//Prepare a self signed certificate
$configargs = array();
if (strpos(PHP_OS, 'WIN') === 0) {
    $phpbin = defined('PHP_BINARY')
        ? PHP_BINARY
        : getenv('PHP_PEAR_PHP_BIN');
    $configargs['config'] = dirname($phpbin) . '/extras/ssl/openssl.cnf';
}

$privkey = openssl_pkey_new($configargs);
$cert = openssl_csr_sign(
    openssl_csr_new(
        array(
            'countryName' => 'US',
            'stateOrProvinceName' => 'IRRELEVANT',
            'localityName' => 'IRRELEVANT',
            'organizationName' => 'PHP',
            'organizationalUnitName' => 'PHP',
            'commonName' => 'IRRELEVANT',
            'emailAddress' => 'IRRELEVANT@example.com'
        ),
        $privkey,
        $configargs
    ),
    null,
    $privkey,
    2,
    $configargs
);

$pem = array();
openssl_x509_export($cert, $pem[0]);
openssl_pkey_export($privkey, $pem[1], null, $configargs);

openssl_pkcs12_export_to_file(
    $pem[0],
    __DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE . '.p12',
    $pem[1],
    '123456'
);
file_put_contents(
    __DIR__ . DIRECTORY_SEPARATOR . CERTIFICATE_FILE . '.cer',
    implode('', $pem)
);