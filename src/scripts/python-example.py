import json
import hashlib
import base64
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import serialization, hashes
from cryptography.hazmat.primitives.asymmetric import padding, utils

# Get private key

with open("private_key.pem", 'rb') as file:
    private_key_data = file.read()

private_key = serialization.load_pem_private_key(
    private_key_data,
    password=None,
    backend=default_backend()
)

# Get public key from private key

public_key = private_key.public_key()

# Create payload

with open("data.json", 'r') as file:
        json_payload = file.read()

payload = json.dumps(json_payload, separators=(',', ':')).encode('utf-8')

# Create digest

hash_sha256 = hashlib.sha256(payload)
encoded_hash_sha256 = base64.b64encode(hash_sha256.digest())
digest = 'SHA-256=' + encoded_hash_sha256.decode('utf-8')

# Create signing string

signing_string = "(request-target): post /pis/v2/connect?state=test\ndate: Mon, 26 Feb 2024 13:36:00 GMT\ndigest: " + digest + "\nx-request-id: 963587a9-1fa4-42d2-bca8-85c27d0c859e"

# Build signature

hasher = hashes.Hash(hashes.SHA256(), backend=default_backend())
hasher.update(signing_string.encode('utf-8'))
signature = base64.b64encode(private_key.sign(
    hasher.finalize(),
    padding.PKCS1v15(),
    utils.Prehashed(hashes.SHA256())
)).decode('utf-8')

# Generate signature header (to use later)

headerSignature = 'keyId="2fa2be62-94b0-4e88-b089-b73cb1141de0",algorithm="rsa-sha256",headers="(request-target) date digest x-request-id",signature="' + signature + '"'

# Verify signature

try:
    public_key.verify(
        base64.b64decode(signature),
        signing_string.encode('utf-8'),
        padding.PKCS1v15(),
        hashes.SHA256()
    )
    signatureMatch = True
except:
    signatureMatch = False

print(f"Signature Match: '{signatureMatch}'")