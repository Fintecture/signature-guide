<?php

// Get private key

$privateKey = file_get_contents('private_key.pem');
$privateKey = openssl_pkey_get_private($privateKey);

// Get public key from private key

$publicKeyStr = openssl_pkey_get_details($privateKey)['key'];
$publicKey = openssl_pkey_get_public($publicKeyStr);

// Create payload

$jsonPayload = file_get_contents('data.json');

$payload = json_encode(json_decode($jsonPayload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Create digest

$digest = 'SHA-256=' . base64_encode(hash('sha256', $payload, true));

// Create signing string

$signingString = "(request-target): post /pis/v2/connect?state=test\ndate: Mon, 26 Feb 2024 13:23:58 GMT\ndigest: $digest\nx-request-id: 963587a9-1fa4-42d2-bca8-85c27d0c859e";

// Build signature

openssl_sign($signingString, $rawSignature, $privateKey, OPENSSL_ALGO_SHA256); // Sign signing string
$signature = base64_encode($rawSignature);

// Generate signature header (to use later)

$headerSignature = 'keyId="2fa2be62-94b0-4e88-b089-b73cb1141de0",algorithm="rsa-sha256",headers="(request-target) date digest x-request-id",signature="' . $signature . '"';

// Verify signature

$verifySignature = openssl_verify($signingString, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);
$signatureMatch = $verifySignature ? 'true' : 'false';

var_dump("Signature match: " . $signatureMatch);
