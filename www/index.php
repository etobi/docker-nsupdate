<?php

$keypath = '/var/keys/';

// ----------------------------------------------------------------------------

// https://www.example.com/nsupdate/nsupdate.php?ip=<ipaddr>&server=ns1.hw33.de&zone=hw33.de.&domain=*.hw33.de.&key=Khw33.de.%2B123%2B45678

$ip = $_GET['ip'];
// ipv4 only
if (!preg_match('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip)) {
    echo 'invalid ip';
    die(1);
}

$server = $_GET['server'];
// must be a valid domain name
if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}\.?$/', $server)) {
    echo 'invalid server';
    die(1);
}

$zone = $_GET['zone'];
// must be an existing zone in your bind
if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}\.?$/', $zone)) {
    echo 'invalid zone';
    die(1);
}

$domain = $_GET['domain'];
// domain, subdomain or wildcard. might be relative to zone or absolute ("foobar" or "foobar.mydomain.de." or "*")
if (!preg_match('/^(((\*)|([a-z0-9]+(-[a-z0-9]+)*))\.)+[a-z]{2,}\.?$/', $domain)) {
    echo 'invalid domain';
    die(1);
}

$key = $_GET['key'];
// key file name (without the ".private" extension)
if (!preg_match('/^[A-Za-z0-9\.+]+$/', $key)) {
    echo 'invalid key';
    die(1);
}


$command = 'dig +short ' . $domain . ' @' . $server . ' A';
$currentIp = exec($command);

if ($currentIp === $ip) {
	echo 'OK - no update';
	exit;
}

$update = 'server ' . $server . "\n"
    . 'zone ' . $zone . "\n"
    . 'update DELETE ' . $domain . ' A' . "\n"
    . 'update ADD ' . $domain . ' 60 A ' . $ip . "\n"
    . 'send' . "\n";

$command = 'echo "' . $update . '" | /usr/bin/nsupdate -k "' . $keypath . $key . '.private' . '"';

$output = '';
$return = 0;
exec($command, $output, $return);

echo $return == 0 ? 'OK' : 'ERROR';


