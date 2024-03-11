<?php

/**
 * @return array<string, string>
 */
function javaPayloadStep(string $payload, string $body): array
{
    $step = <<<'STEP'
    String payload = (new JSONObject(jsonPayload)).toString();
    STEP;
    // particularity of JSONObject is that it sorts the JSON keys
    $orderedPayload = json_decode($payload, true);
    ksort($orderedPayload);
    $orderedPayload = json_encode($orderedPayload);

    return [
        'title' => 'Create payload',
        'help' => 'Make sure your body is encoded into UTF8 with unescaped unicode to avoid bad surprises in case accents or other special characters are included in the body. <div class="alert alert-warning"><b>IMPORTANT:</b> org.json.JSONObject orders alphabetically your payload thus potentially changing the payload. Make sure you send the same payload as you sign.</div>',
        'code' => sprintf($step, json_encode($body)),
        'key' => 'payload',
        'value' => $orderedPayload,
        'value_type' => 'json',
    ];
}

/**
 * @return array<string, string>
 */
function javaDigestStep(string $digest): array
{
    $step = <<<'STEP'
    MessageDigest msgDigest = MessageDigest.getInstance("SHA-256");
    byte[] hash = msgDigest.digest(payload.getBytes(StandardCharsets.UTF_8));
    String hashString = Base64.getEncoder().encodeToString(hash);
    String digest = "SHA-256=" + hashString;
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
function javaSigningStringStep(
    string $signingString,
    string $requestTarget,
    string $date,
    string $xRequestId,
    bool $digest
): array {
    if ($digest) {
        $stepSigningString = <<<'STEP'
        String signingString = "(request-target): %s\ndate: %s\ndigest: " + digest + "\nx-request-id: %s";
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    } else {
        $stepSigningString = <<<'STEP'
        String signingString = "(request-target): %s\ndate: %s\nx-request-id: %s";
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
function javaSignatureStep(string $signature): array
{
    $step = <<<'STEP'
    String sanitizedPk = privateKey.replace("-----END PRIVATE KEY-----", "")
    .replace("-----BEGIN PRIVATE KEY-----", "").replaceAll("\\r\\n|\\r|\\n", "");

    byte[] b1 = Base64.getDecoder().decode(sanitizedPk);
    PKCS8EncodedKeySpec spec = new PKCS8EncodedKeySpec(b1);
    KeyFactory kf = KeyFactory.getInstance("RSA");
    Signature privateSignature = Signature.getInstance("SHA256withRSA");
    privateSignature.initSign(kf.generatePrivate(spec));
    privateSignature.update(signingString.getBytes(StandardCharsets.UTF_8));
    byte[] s = privateSignature.sign();
    String signature = Base64.getEncoder().encodeToString(s);
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
function javaSignatureHeaderStep(string $signatureHeader, string $appId, string $headers): array
{
    $step = <<<'STEP'
    String headerSignature = "keyId=\"%s\"," + "algorithm=\"rsa-sha256\",headers=\"%s\"," + "signature=\"" + signature + "\"";
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
function javaSignatureMatchStep(bool $signatureMatch): array
{
    $step = <<<'STEP'
    Signature publicSignature = Signature.getInstance("SHA256withRSA");
    publicSignature.initVerify(publicKey);
    publicSignature.update(signingString.getBytes(StandardCharsets.UTF_8));
    boolean signatureMatch = publicSignature.verify(Base64.getDecoder().decode(signature));
    STEP;

    return [
        'title' => 'Verify signature',
        'code' => $step,
        'key' => 'signatureMatch',
        'value' => '<span class="text-' . ($signatureMatch ? 'success' : 'danger') . '">' . ($signatureMatch ? 'true' : 'false') . '</span>'
    ];
}