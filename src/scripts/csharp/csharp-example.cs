using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Text.Encodings.Web;

class Program
{
    static void Main()
    {
        // Get private key

        string privateKey = File.ReadAllText("../private_key.pem");

        // Create payload

        string jsonPayload = File.ReadAllText("../data.json");

        var serializedPayload = JsonSerializer.Serialize(jsonPayload, new JsonSerializerOptions
        {
            Encoder = JavaScriptEncoder.UnsafeRelaxedJsonEscaping
        });

        // Create digest

        SHA256 hash = SHA256.Create();
        var hashedPayload = hash.ComputeHash(Encoding.UTF8.GetBytes(serializedPayload));
        var hexHashedPayload = Convert.ToBase64String(hashedPayload);
        var digest = $"SHA-256={hexHashedPayload}";

        // Create signing string

        var signingString = "(request-target): post /pis/v2/connect?state=test\ndate: Mon, 26 Feb 2024 13:36:00 GMT\ndigest: " + digest + "\nx-request-id: 963587a9-1fa4-42d2-bca8-85c27d0c859e";

        // Build signature

        var RSACryptoServiceProviderService = new RSACryptoServiceProvider(2048);
        var privateKeyBlocks = privateKey.Split("-", StringSplitOptions.RemoveEmptyEntries); // don't forget to instantiate privateKey with your own private key
        var privateKeyBytes = Convert.FromBase64String(privateKeyBlocks[1]);
        RSACryptoServiceProviderService.ImportPkcs8PrivateKey(privateKeyBytes, out var _);
        var rawSignature = RSACryptoServiceProviderService.SignData(Encoding.UTF8.GetBytes(signingString), SHA256.Create());
        var signature = Convert.ToBase64String(rawSignature);

        // Generate signature header (to use later)

        var headerSignature = $"keyId=\"2fa2be62-94b0-4e88-b089-b73cb1141de0\",algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-request-id\",signature=\"{signature}\"";

        // Verify signature (decrypt with private key and compare digests, like steps)

        // Compute body digest from raw body
        var bodyBytes = Encoding.UTF8.GetBytes(jsonPayload);
        var digestBody = "SHA-256=" + Convert.ToBase64String(bodyBytes);

        // Header digest without backslashes (PHP stripslashes)
        var digestHeader = digest.Replace("\\", "");

        // Decrypt using OAEP-SHA1 to mirror PHP OPENSSL_PKCS1_OAEP_PADDING
        var decrypted = RSACryptoServiceProviderService.Decrypt(Convert.FromBase64String(signature), RSAEncryptionPadding.OaepSHA1);
        var lines = System.Text.RegularExpressions.Regex.Split(Encoding.UTF8.GetString(decrypted), @"\r?\n");
        var line1 = lines.Length > 1 ? lines[1] : string.Empty; // 0: date, 1: digest: "<value>"
        var digestSignature = (line1.Length >= 8 ? line1.Substring(8) : string.Empty).Replace("\"", "");

        var signatureMatch = (digestBody == digestSignature) && (digestBody == digestHeader);

        Console.WriteLine($"Signature Match: {signatureMatch}");
    }
}
