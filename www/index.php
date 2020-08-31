<?php

$keypath = '/var/keys/';

// ----------------------------------------------------------------------------
// https://www.example.com/nsupdate/nsupdate.php?ip=<ipaddr>&server=ns1.hw33.de&zone=hw33.de.&domain=*.hw33.de.&key=Khw33.de.%2B123%2B45678

$ipv4 = trim($_GET['ip']);
if (!empty($ipv4) && !preg_match('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/', $ipv4)) {
    echo 'invalid ip';
    http_response_code(400);
    exit;
}

$ipv6 = strtoupper(trim($_GET['ipv6']));
if (!empty($ipv6) && !preg_match('/^(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}$/', $ipv6)) {
    echo 'invalid ipv6';
    http_response_code(400);
    exit;
}

if (empty($ipv6) && empty($ipv4)) {
    echo 'no ip or ipv6';
    http_response_code(400);
    exit;
}

$server = trim($_GET['server']);
// must be a valid domain name
if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}\.?$/', $server)) {
    echo 'invalid server';
    http_response_code(400);
    exit;
}

$zone = trim($_GET['zone']);
// must be an existing zone in your bind
if (!preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}\.?$/', $zone)) {
    echo 'invalid zone';
    http_response_code(400);
    exit;
}

$domain = trim($_GET['domain']);
// domain, subdomain or wildcard. might be relative to zone or absolute ("foobar" or "foobar.mydomain.de." or "*")
if (!preg_match('/^(((\*)|([a-z0-9]+(-[a-z0-9]+)*))\.)+[a-z]{2,}\.?$/', $domain)) {
    echo 'invalid domain';
    http_response_code(400);
    exit;
}

$key = trim($_GET['key']);
// key file name (without the ".private" extension)
if (!preg_match('/^[A-Za-z0-9\.+]+$/', $key)) {
    echo 'invalid key';
    http_response_code(400);
    exit;
}

$update = [];
$update[] = 'server ' . $server;
$update[] = 'zone ' . $zone;

if (!empty($ipv4)) {
  $command = 'dig +short ' . escapeshellarg($domain) . ' ' . escapeshellarg('@' . $server) . ' A';
  $currentIp = exec($command);

  if ($currentIp !== $ipv4) {
    $update[] = 'update DELETE ' . $domain . ' A';
    $update[] = 'update ADD ' . $domain . ' 60 A ' . $ipv4;
  }
}

if (!empty($ipv6)) {
  $command = 'dig +short ' . escapeshellarg($domain) . ' ' . escapeshellarg('@' . $server) . ' AAAA';
  $currentIp = exec($command);

  if ($currentIp !== $ipv6) {
    $update[] = 'update DELETE ' . $domain . ' AAAA';
    $update[] = 'update ADD ' . $domain . ' 60 AAAA ' . $ipv6;
  }
}

$update[] = 'send' . "\n";

$command = 'echo '
  . escapeshellarg(
    implode("\n", $update)
  )
  . ' | /usr/bin/nsupdate -k '
  . escapeshellarg($keypath . $key . '.private');

$output = '';
$return = 0;
exec($command, $output, $return);

echo $return == 0 ? 'OK' : 'ERROR';
