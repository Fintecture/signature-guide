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

        // Verify signature

        var signatureMatch = RSACryptoServiceProviderService.VerifyData(Encoding.UTF8.GetBytes(signingString), rawSignature, HashAlgorithmName.SHA256, RSASignaturePadding.Pkcs1);

        Console.WriteLine($"Signature Match: {signatureMatch}");
    }
}
