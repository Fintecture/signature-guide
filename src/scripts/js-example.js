const crypto = require('crypto');
const fs = require('fs');

// Get private key

const privateKey = fs.readFileSync('private_key.pem', 'utf-8');

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

// Compute "SHA-256=<base64(body)>" like in the step guide
const bodyBuf = Buffer.from(fs.readFileSync('data.json', 'utf-8'), 'utf8');
const digestBody = 'SHA-256=' + bodyBuf.toString('base64');

// Header digest with backslashes removed
const digestHeader = digest.replace(/\\/g, '');

// Extract the actual signature payload (using the built signature directly)
const extractedSignature = Buffer.from(signature, 'base64');

// Decrypt with RSA-OAEP (SHA-1 to match OPENSSL_PKCS1_OAEP_PADDING default)
const decrypted = crypto.privateDecrypt(
  {
    key: privateKey,
    padding: crypto.constants.RSA_PKCS1_OAEP_PADDING,
    oaepHash: 'sha1'
  },
  extractedSignature
);

// The decrypted content is a signing string with lines:
// index 0: date, index 1: digest: "<value>"
const signingStringLines = decrypted.toString('utf8').split(/\r?\n/);

// Take line 1, remove leading 'digest: ' (8 chars) and strip quotes
const digestSignature = (signingStringLines[1] || '').slice(8).replace(/"/g, '');

const signatureMatch = digestBody === digestSignature && digestBody === digestHeader;

console.log("Signature Match: " + signatureMatch);