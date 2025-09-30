<?php

/**
 * @return array<string, string>
 */
function javascriptPayloadStep(string $payload, string $body): array
{
    $step = <<<'STEP'
    const payload = JSON.stringify(%s);
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
function javascriptDigestStep(string $digest): array
{
    $step = <<<'STEP'
    const crypto = require('crypto');
    const hash = crypto.createHash('sha256');
    hash.update(payload);
    const hashBuffer = hash.digest();
    const hashBase64 = hashBuffer.toString('base64');
    const digest = 'SHA-256=' + hashBase64;
    STEP;

    return [
        'title' => 'Create digest',
        'code' => $step,
        'key' => 'digest',
        'value' => $digest
    ];
}

/**
 * @return array<string, string>
 */
function javascriptSigningStringStep(
    string $signingString,
    string $requestTarget,
    string $date,
    string $xRequestId,
    bool $digest
): array {
    if ($digest) {
        $stepSigningString = <<<'STEP'
        const signingString = "(request-target): %s\ndate: %s\ndigest: "+ digest +"\nx-request-id: %s";
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    } else {
        $stepSigningString = <<<'STEP'
        signingString = "(request-target): %s\ndate: %s\nx-request-id: %s";
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    }

    return [
        'title' => 'Create signing string',
        'help' => 'Make sure the name of each parameter is lower cased (not the value), there is a ": " between the name and the value, and a return character "\n" at the end of each line except the last one. For the (request-target) include query params to the pathname.',
        'code' => $code,
        'key' => 'signingString',
        'value' => $signingString
    ];
}

/**
 * @return array<string, string>
 */
function javascriptSignatureStep(string $signature): array
{
    $step = <<<'STEP'
    let privateKeyObj = crypto.createPrivateKey(privateKey);
    const sign = crypto.createSign('SHA256');
    sign.update(signingString);
    const rawSignature = sign.sign(privateKeyObj, 'base64');
    const signature = Buffer.from(rawSignature, 'base64').toString('base64');
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
function javascriptSignatureHeaderStep(string $signatureHeader, string $appId, string $headers): array
{
    $step = <<<'STEP'
    const headerSignature = 'keyId="%s",algorithm="rsa-sha256",headers="%s",signature="'+ signature +'"';
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
function javascriptSignatureMatchStep(bool $signatureMatch): array
{
    $step = <<<'STEP'
    // Compute "SHA-256=<base64(body)>" like in PHP Crypto::encodeToBase64($body, true)
    const bodyBuf = Buffer.isBuffer(%s) ? %s : Buffer.from(%s, 'utf8');
    const digestBody = 'SHA-256=' + bodyBuf.toString('base64');

    // Header digest with backslashes removed (PHP stripslashes)
    const digestHeader = digest.replace(/\\/g, '');

    // Extract the actual signature payload (stubbed—match your PHP extractSignature)
    const extractedSignature = extractSignature(%s);

    // Decrypt with RSA-OAEP (SHA-1 to match OPENSSL_PKCS1_OAEP_PADDING default in PHP)
    const decrypted = crypto.privateDecrypt(
        {
            key: privateKeyPem,
            padding: crypto.constants.RSA_PKCS1_OAEP_PADDING,
            oaepHash: 'sha1',
        },
        extractedSignature
    );

    // The decrypted content is a "signing string" with lines:
    // index 0: date, index 1: digest: "<value>"
    const signingString = decrypted.toString('utf8').split(/\r?\n/);

    // Take line 1, remove leading 'digest: ' (8 chars) and strip quotes
    const digestSignature = signingString[1].slice(8).replace(/"/g, '');

    // Compare like the PHP return
    return digestBody === digestSignature && digestBody === digestHeader;
    STEP;

    return [
        'title' => 'Verify signature',
        'code' => $step,
        'key' => '$signatureMatch',
        'value' => '<span class="text-' . ($signatureMatch ? 'success' : 'danger') . '">' . ($signatureMatch ? 'true' : 'false') . '</span>'
    ];
}
