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

            // Get public key from private key

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

            PublicKey publicKey = KeyFactory.getInstance("RSA").generatePublic(
                    new RSAPublicKeySpec(rsaPrivateKey.getModulus(), rsaPrivateKey.getPublicExponent()));

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

            // Verify signature

            Signature publicSignature = Signature.getInstance("SHA256withRSA");
            publicSignature.initVerify(publicKey);
            publicSignature.update(signingString.getBytes(StandardCharsets.UTF_8));
            boolean verifySignature = publicSignature.verify(Base64.getDecoder().decode(signature));

            System.out.println("Signature match: " + verifySignature);

        } catch (IOException | NoSuchAlgorithmException | InvalidKeyException | InvalidKeySpecException
                | SignatureException e) {
            e.printStackTrace();
        }
    }
}