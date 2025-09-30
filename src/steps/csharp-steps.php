<?php

/**
 * @return array<string, string>
 */
function csharpPayloadStep(string $payload, string $body): array
{
    $step = <<<'STEP'
    var serializedPayload = JsonSerializer.Serialize(%s, new JsonSerializerOptions
    {
        Encoder = JavaScriptEncoder.UnsafeRelaxedJsonEscaping
    });
    STEP;

    return [
        'title' => 'Create payload',
        'help' => 'Make sure your body is encoded into UTF8 with unescaped unicode to avoid bad surprises in case accents or other special characters are included in the body.',
        'code' => sprintf($step, json_encode($body)),
        'key' => 'payload',
        'value' => $payload,
        'value_type' => 'json',
    ];
}

/**
 * @return array<string, string>
 */
function csharpDigestStep(string $digest): array
{
    $step = <<<'STEP'
    using SHA256 hash = SHA256.Create();
    var hashedPayload = hash.ComputeHash(Encoding.UTF8.GetBytes(serializedPayload));
    var hexHashedPayload = Convert.ToBase64String(hashedPayload);
    var digest = $"SHA-256={hexHashedPayload}";
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
function csharpSigningStringStep(
    string $signingString,
    string $requestTarget,
    string $date,
    string $xRequestId,
    bool $digest
): array {
    if ($digest) {
        $stepSigningString = <<<'STEP'
        var signingString = "(request-target): %s\ndate: %s\ndigest: " + digest + "\nx-request-id: %s";
        STEP;
        $code = sprintf($stepSigningString, $requestTarget, $date, $xRequestId);
    } else {
        $stepSigningString = <<<'STEP'
        var signingString = "(request-target): %s\ndate: %s\nx-request-id: %s";
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
function csharpSignatureStep(string $signature): array
{
    $step = <<<'STEP'
    var RSACryptoServiceProviderService = new RSACryptoServiceProvider(2048);
    var privateKeyBlocks = privateKey.Split("-", StringSplitOptions.RemoveEmptyEntries); // don't forget to instantiate privateKey with your own private key
    var privateKeyBytes = Convert.FromBase64String(privateKeyBlocks[1]);
    RSACryptoServiceProviderService.ImportPkcs8PrivateKey(privateKeyBytes, out var _);
    var rawSignature = RSACryptoServiceProviderService.SignData(Encoding.UTF8.GetBytes(signingString), SHA256.Create());
    var signature = Convert.ToBase64String(rawSignature);
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
function csharpSignatureHeaderStep(string $signatureHeader, string $appId, string $headers): array
{
    $step = <<<'STEP'
    var headerSignature = $"keyId=\"%s\",algorithm=\"rsa-sha256\",headers=\"%s\",signature=\"{signature}\"";
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
function csharpSignatureMatchStep(bool $signatureMatch): array
{
    $step = <<<'STEP'
       using var rsa = LoadRsaFromPem(privateKeyPem);

        // "SHA-256=<base64(body)>"
        var digestBody = "SHA-256=" + Convert.ToBase64String(body);

        // PHP stripslashes()
        var digestHeader = digest.Replace("\\", "");

        // Match PHP self::extractSignature($signature)
        var extractedSignature = ExtractSignature(signature);

        // Decrypt (OPENSSL_PKCS1_OAEP_PADDING defaults to SHA1 in PHP)
        byte[] decrypted = rsa.Decrypt(extractedSignature, RSAEncryptionPadding.OaepSHA1);

        // Split lines (0: date, 1: digest: "<value>")
        var signingString = Regex.Split(Encoding.UTF8.GetString(decrypted), @"\r?\n");

        // Take line 1, remove 'digest: ' (8 chars) and strip quotes
        var line1 = signingString.Length > 1 ? signingString[1] : string.Empty;
        var digestSignature = (line1.Length >= 8 ? line1.Substring(8) : string.Empty).Replace("\"", "");

        return digestBody == digestSignature && digestBody == digestHeader;
    STEP;

    return [
        'title' => 'Verify signature',
        'code' => $step,
        'key' => 'signatureMatch',
        'value' => '<span class="text-' . ($signatureMatch ? 'success' : 'danger') . '">' . ($signatureMatch ? 'true' : 'false') . '</span>'
    ];
}
