import java.nio.file.Files;
import java.nio.file.Path;
import java.io.IOException;
import java.security.NoSuchAlgorithmException;
import java.security.PrivateKey;
import java.security.PublicKey;
import java.security.InvalidKeyException;
import java.security.spec.InvalidKeySpecException;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.Base64;
import java.security.spec.PKCS8EncodedKeySpec;
import java.security.spec.X509EncodedKeySpec;
import java.security.KeyFactory;
import java.security.Signature;
import java.security.SignatureException;
import java.security.spec.RSAPrivateCrtKeySpec;
import java.security.interfaces.RSAPrivateCrtKey;
import java.security.spec.RSAPublicKeySpec;

public class Program {
    public static void main(String[] args) {
        try {
            // Get private key

            String privateKeyString = new String(Files.readAllBytes(Path.of("private_key.pem")),
                    StandardCharsets.UTF_8);
            String sanitizedPk = privateKeyString.replace("-----END PRIVATE KEY-----", "")
                    .replace("-----BEGIN PRIVATE KEY-----", "").replaceAll("\\r\\n|\\r|\\n", "");
            byte[] privateKeyBytes = Base64.getDecoder().decode(sanitizedPk);
            KeyFactory keyFactory = KeyFactory.getInstance("RSA");
            PKCS8EncodedKeySpec keySpec = new PKCS8EncodedKeySpec(privateKeyBytes);
            PrivateKey privateKey = keyFactory.generatePrivate(keySpec);

            RSAPrivateCrtKey rsaPrivateKey = (RSAPrivateCrtKey) privateKey;
            RSAPrivateCrtKeySpec rsaPrivateCrtKeySpec = new RSAPrivateCrtKeySpec(
                    rsaPrivateKey.getModulus(),
                    rsaPrivateKey.getPublicExponent(),
                    rsaPrivateKey.getPrivateExponent(),
                    rsaPrivateKey.getPrimeP(),
                    rsaPrivateKey.getPrimeQ(),
                    rsaPrivateKey.getPrimeExponentP(),
                    rsaPrivateKey.getPrimeExponentQ(),
                    rsaPrivateKey.getCrtCoefficient());

            // Create payload

            String jsonPayload = new String(Files.readAllBytes(Path.of("data.json")), StandardCharsets.UTF_8);
            String payload = jsonPayload;

            // Create digest

            MessageDigest digest = MessageDigest.getInstance("SHA-256");
            byte[] hashedBytes = digest.digest(payload.getBytes(StandardCharsets.UTF_8));
            String digestValue = "SHA-256=" + Base64.getEncoder().encodeToString(hashedBytes);

            // Create signing string

            String signingString = "(request-target): post /pis/v2/connect?state=test\ndate: Mon, 26 Feb 2024 13:23:58 GMT\ndigest: "
                    + digestValue + "\nx-request-id: 963587a9-1fa4-42d2-bca8-85c27d0c859e";

            // Build signature

            Signature privateSignature = Signature.getInstance("SHA256withRSA");
            privateSignature.initSign(privateKey);
            privateSignature.update(signingString.getBytes(StandardCharsets.UTF_8));
            byte[] signatureBytes = privateSignature.sign();
            String signature = Base64.getEncoder().encodeToString(signatureBytes);

            // Generate signature header (to use later)

            String headerSignature = "keyId=\"2fa2be62-94b0-4e88-b089-b73cb1141de0\","
                    + "algorithm=\"rsa-sha256\",headers=\"(request-target) date digest x-request-id\"," + "signature=\""
                    + signature + "\"";

            // Verify signature (decrypt with private key and compare digests like in steps)

            // Compute body digest from raw body
            String digestBody = "SHA-256=" + Base64.getEncoder().encodeToString(jsonPayload.getBytes(StandardCharsets.UTF_8));

            // Header digest without backslashes
            String digestHeader = digestValue.replace("\\", "");

            // Decrypt signature with RSA-OAEP SHA-1 (to mirror PHP OPENSSL_PKCS1_OAEP_PADDING)
            javax.crypto.Cipher rsa = javax.crypto.Cipher.getInstance("RSA/ECB/OAEPWithSHA-1AndMGF1Padding");
            rsa.init(javax.crypto.Cipher.DECRYPT_MODE, privateKey);
            byte[] decrypted = rsa.doFinal(Base64.getDecoder().decode(signature));

            String[] lines = new String(decrypted, StandardCharsets.UTF_8).split("\\r?\\n");
            String line1 = lines.length > 1 ? lines[1] : ""; // 0: date, 1: digest: "<value>"
            String digestSignature = line1.length() >= 8 ? line1.substring(8).replace("\"", "") : "";

            boolean signatureMatch = digestBody.equals(digestSignature) && digestBody.equals(digestHeader);

            System.out.println("Signature match: " + signatureMatch);

        } catch (IOException | NoSuchAlgorithmException | InvalidKeyException | InvalidKeySpecException
                | SignatureException e) {
            e.printStackTrace();
        }
    }
}