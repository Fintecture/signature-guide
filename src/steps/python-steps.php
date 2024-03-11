<?php

/**
 * @return array<string, string>
 */
function pythonPayloadStep(string $payload, string $body): array
{
    $step = <<<'STEP'
    payload = json.dumps(%s, separators=(',', ':')).encode('utf-8')
    STEP;

    return [
        'title' => 'Create payload',
        'help' => 'Make sure your body is encoded into UTF8 with unescaped unicode to avoid bad surprises in case accents or other special characters are included in the body.',
        'code' => sprintf($step, $body),
        'key' => 'payload',
        'value' => $payload,
        'value_type' => 'json',
    ];
}

/**
 * @return array<string, string>
 */
function pythonDigestStep(string $digest): array
{
    $step = <<<'STEP'
    hash_sha256 = hashlib.sha256(payload)
    encoded_hash_sha256 = base64.b64encode(hash_sha256.digest())
    digest = 'SHA-256=' + encoded_hash_sha256.decode('utf-8')
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
function pythonSigningStringStep(
    string $signingString,
    string $requestTarget,
    string $date,
    string $xRequestId,
    bool $digest
): array {
    if ($digest) {
        $stepSigningString = <<<'STEP'
        signing_string = "(request-target): %s\ndate: %s\ndigest: " + digest + "\nx-request-id: %s";
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    } else {
        $stepSigningString = <<<'STEP'
        signing_string = "(request-target): %s\ndate: %s\nx-request-id: %s";
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
function pythonSignatureStep(string $signature): array
{
    $step = <<<'STEP'
    hasher = hashes.Hash(hashes.SHA256(), backend=default_backend())
    hasher.update(signing_string.encode('utf-8'))
    signature = base64.b64encode(private_key.sign(
        hasher.finalize(),
        padding.PKCS1v15(),
        utils.Prehashed(hashes.SHA256())
    )).decode('utf-8')
    STEP;

    return [
        'title' => 'Build signature',
        'code' => $step,
        'key' => 'signature',
        'value' => $signature
    ];
}

/**
 * @return array<string, string>
 */
function pythonSignatureHeaderStep(string $signatureHeader, string $appId, string $headers): array
{
    $step = <<<'STEP'
    headerSignature = 'keyId="%s",algorithm="rsa-sha256",headers="%s",signature="' + signature + '"';
    STEP;

    return [
        'title' => 'Generate signature header',
        'code' => sprintf($step, $appId, $headers),
        'key' => 'headerSignature',
        'value' => $signatureHeader
    ];
}

/**
 * @return array<string, string>
 */
function pythonSignatureMatchStep(bool $signatureMatch): array
{
    $step = <<<'STEP'
    try:
        public_key = private_key.public_key()
        public_key.verify(
            base64.b64decode(signature),
            signing_string.encode('utf-8'),
            padding.PKCS1v15(),
            hashes.SHA256()
        )
        signatureMatch = True
    except:
        signatureMatch = False
    STEP;

    return [
        'title' => 'Verify signature',
        'code' => $step,
        'key' => 'signatureMatch',
        'value' => '<span class="text-' . ($signatureMatch ? 'success' : 'danger') . '">' . ($signatureMatch ? 'true' : 'false') . '</span>'
    ];
}

function pythonCompleteExample(): string
{
    return '<a href="https://github.com/Fintecture/signature-guide/blob/src/scripts/python-example.py" target="_blank">View complete example on GitHub</a>';
}