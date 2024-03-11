<?php

/**
 * @return array<string, string>
 */
function phpPayloadStep(string $payload, string $body): array
{
    $step = <<<'STEP'
    <?php
    $payload = json_encode(json_decode('%s'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>
    STEP;

    return [
        'title' => 'Create payload',
        'help' => 'Make sure your body is encoded into UTF8 with unescaped unicode to avoid bad surprises in case accents or other special characters are included in the body.',
        'code' => sprintf($step, $body),
        'key' => '$payload',
        'value' => $payload,
        'value_type' => 'json',
    ];
}

/**
 * @return array<string, string>
 */
function phpDigestStep(string $digest): array
{
    $step = <<<'STEP'
    <?php
    $digest = 'SHA-256=' . base64_encode(hash('sha256', $payload, true));
    ?>
    STEP;

    return [
        'title' => 'Create digest',
        'code' => $step,
        'key' => '$digest',
        'value' => $digest
    ];
}

/**
 * @return array<string, string>
 */
function phpSigningStringStep(
    string $signingString,
    string $requestTarget,
    string $date,
    string $xRequestId,
    bool $digest
): array {
    if ($digest) {
        $stepSigningString = <<<'STEP'
        <?php
        $signingString = "(request-target): %s\ndate: %s\ndigest: $digest\nx-request-id: %s";
        ?>
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    } else {
        $stepSigningString = <<<'STEP'
        <?php
        $signingString = "(request-target): %s\ndate: %s\nx-request-id: %s";
        ?>
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    }

    return [
        'title' => 'Create signing string',
        'help' => 'Make sure the name of each parameter is lower cased (not the value), there is a ": " between the name and the value, and a return character "\n" at the end of each line except the last one. For the (request-target) include query params to the pathname.',
        'code' => $code,
        'key' => '$signingString',
        'value' => $signingString
    ];
}

/**
 * @return array<string, string>
 */
function phpSignatureStep(string $signature): array
{
    $step = <<<'STEP'
    <?php
    openssl_sign($signingString, $rawSignature, $privateKey, OPENSSL_ALGO_SHA256); // sign signing string
    $signature = base64_encode($rawSignature);
    ?>
    STEP;

    return [
        'title' => 'Build signature',
        'code' => $step,
        'key' => '$signature',
        'value' => $signature
    ];
}

/**
 * @return array<string, string>
 */
function phpSignatureHeaderStep(string $signatureHeader, string $appId, string $headers): array
{
    $step = <<<'STEP'
    <?php
    $headerSignature = 'keyId="%s",algorithm="rsa-sha256",headers="%s",signature="' . $signature . '"';
    ?>
    STEP;

    return [
        'title' => 'Generate signature header',
        'code' => sprintf($step, $appId, $headers),
        'key' => '$headerSignature',
        'value' => $signatureHeader
    ];
}

/**
 * @return array<string, string>
 */
function phpSignatureMatchStep(bool $signatureMatch): array
{
    $step = <<<'STEP'
    <?php
    $privateKey = openssl_pkey_get_private($privateKey);
    $publicKeyStr = openssl_pkey_get_details($privateKey)['key'];
    $publicKey = openssl_pkey_get_public($publicKeyStr);
    $signatureMatch = openssl_verify($signingString, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);
    ?>
    STEP;

    return [
        'title' => 'Verify signature',
        'code' => $step,
        'key' => '$signatureMatch',
        'value' => '<span class="text-' . ($signatureMatch ? 'success' : 'danger') . '">' . ($signatureMatch ? 'true' : 'false') . '</span>'
    ];
}
