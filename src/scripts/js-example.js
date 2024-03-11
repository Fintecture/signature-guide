const crypto = require('crypto');
const fs = require('fs');

// Get private key

const privateKey = fs.readFileSync('private_key.pem', 'utf-8');

// Get public key from private key

const publicKey = privateKeyObj.export({ type: 'pkcs1', format: 'pem' });

// Create payload

const payload = JSON.stringify(fs.readFileSync('data.json', 'utf-8'));

// Create digest

const hash = crypto.createHash('sha256');
hash.update(payload);
const hashBuffer = hash.digest();
const hashBase64 = hashBuffer.toString('base64');
const digest = 'SHA-256=' + hashBase64;

// Create signing string

const signingString = "(request-target): post /pis/v2/connect?state=test\ndate: Wed, 07 Feb 2024 08:33:06  GMT\ndigest: " + digest + "\nx-request-id: 963587a9-1fa4-42d2-bca8-85c27d0c859e";

// Build signature

let privateKeyObj = crypto.createPrivateKey(privateKey);

const sign = crypto.createSign('SHA256');
sign.update(signingString);
const rawSignature = sign.sign(privateKeyObj, 'base64');
const signature = Buffer.from(rawSignature, 'base64').toString('base64');

// Generate signature header (to use later)

const headerSignature = 'keyId="2fa2be62-94b0-4e88-b089-b73cb1141de0",algorithm="rsa-sha256",headers="(request-target) date digest x-request-id",signature="' + signature + '"';

// Verify signature

const verify = crypto.createVerify('SHA256');
verify.update(signingString);
const isSignatureValid = verify.verify(publicKey, Buffer.from(signature, 'base64'));

console.log("Signature Match: " + isSignatureValid);