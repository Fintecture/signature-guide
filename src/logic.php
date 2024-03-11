<?php

function isJSON(string $string): bool
{
    $isJSON = is_string($string) && is_array(json_decode($string, true));
    $validJSON = json_last_error() == JSON_ERROR_NONE;
    return $isJSON && $validJSON ? true : false;
}

/**
 * @return string|false
 */
function createPayload(string $body)
{
    if (isJSON($body)) {
        $body = json_decode($body);
    }
    return json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function createDigest(string $payload): string
{
    return 'SHA-256=' . base64_encode(hash('sha256', $payload, true));
}

function createSigningString(string $requestTarget, string $date, string $xRequestId, string $digest = null): string
{
    return '(request-target): ' . $requestTarget . PHP_EOL . 'date: '. $date
        . ($digest ? PHP_EOL . 'digest: ' . $digest : '') . PHP_EOL . 'x-request-id: ' . $xRequestId;
}

/**
 * @param resource $privateKey
 */
function createSignature(string $signingString, $privateKey): string
{
    openssl_sign($signingString, $rawSignature, $privateKey, OPENSSL_ALGO_SHA256); // sign signing string
    return base64_encode($rawSignature);
}

function createSignatureHeader(string $appId, string $headers, string $signature): string
{
    return 'keyId="' . $appId . '",algorithm="rsa-sha256",headers="' . $headers . '",signature="' . $signature . '"';
}

/**
 * @param resource $publicKey
 */
function createSignatureMatch(string $signingString, string $signature, $publicKey): bool
{
    return openssl_verify($signingString, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256) === 1 ? true : false;
}
