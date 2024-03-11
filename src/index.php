<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once('config.php');

$language = 'php'; // default language

$tabs = [
    'php' => 'PHP',
    'javascript' => 'JavaScript',
    'java' => 'Java',
    'csharp' => '.NET',
    'python' => 'Python'
];

$languages = array_keys($tabs); // available languages

// Requirements
$requirements = [
    'csharp' => '.NET Core 3.0, Core 3.1, 5, 6',
    'python' => 'Python 3 & <a href="https://cryptography.io">cryptography module</a>',
    'javascript' => 'NodeJS'
];

$completeExamples = [
    'csharp' => '<a href="https://github.com/Fintecture/signature-guide/blob/src/scripts/csharp" target="_blank">View complete example on GitHub</a>',
    'php' => '<a href="https://github.com/Fintecture/signature-guide/blob/src/scripts/php-example.php" target="_blank">View complete example on GitHub</a>',
    'javascript' => '<a href="https://github.com/Fintecture/signature-guide/blob/src/scripts/js-example.js" target="_blank">View complete example on GitHub</a>',
    'java' => '<a href="https://github.com/Fintecture/signature-guide/blob/src/scripts/java-example.java" target="_blank">View complete example on GitHub</a>',
    'python' => '<a href="https://github.com/Fintecture/signature-guide/blob/src/scripts/python-example.py" target="_blank">View complete example on GitHub</a>'
];

// Import logic
foreach ($languages as $languageImport) {
    require_once('steps/' . $languageImport . '-steps.php');
}

require_once('logic.php');

$values = $defaultValues; // prefill form

$steps = [];

// Form submission
$isFormSubmitted = false;
if (!empty($_POST) && isset($_POST['submit'])) {
    $isFormSubmitted = true;

    $form = handlingForm();
    if (is_string($form)) {
        $error = $form;
    } else {
        $values = $form[0];
        $privateKey = $form[1];
        $privateKeyStr = $form[2];

        foreach ($languages as $languageStep) {
            $steps[$languageStep] = makeSteps($values, $privateKey, $languageStep);
        }
        if (is_string($steps[$languageStep])) {
            $error = $form;
        }
    }
}

/**
 * @return array<int, mixed>|string
 */
function handlingForm()
{
    $fields = [
        'method',
        'request-target',
        'date',
        'x-request-id',
        'app-id',
        'payload'
    ];

    foreach ($fields as $field) {
        if (!isset($_POST[$field]) || strlen($_POST[$field]) === 0) {
            if ($field === 'payload' && $_POST['method'] === '1') {
                continue;
            }
            return 'Please complete all the fields.';
        }
    }

    if (isset($_POST['private-key']) && !empty($_POST['private-key'])) {
        $privateKeyStr = $_POST['private-key'];
    } elseif (isset($_FILES['private-key']) && $_FILES['private-key']['error'] === UPLOAD_ERR_OK) {
        $privateKeyStr = file_get_contents($_FILES['private-key']['tmp_name']);
    }

    if (isset($privateKeyStr)) {
        $privateKeyStr = preg_replace("/\n\r/m", "\n", $privateKeyStr);
        $privateKey = openssl_pkey_get_private($privateKeyStr);
    } else {
        $privateKey = openssl_pkey_new();
        if (!$privateKey) {
            return 'Can\'t generate a Private Key';
        }
        openssl_pkey_export($privateKey, $privateKeyStr);
    }
    if ($privateKey) {
        $privateKeyStr = trim($privateKeyStr);
        $values = [
            'method' => $_POST['method'],
            'request-target' => $_POST['request-target'],
            'date' => $_POST['date'],
            'x-request-id' => $_POST['x-request-id'],
            'app-id' => $_POST['app-id'],
            'payload' => $_POST['payload']
        ];

        return [
            $values,
            $privateKey,
            $privateKeyStr
        ];
    } else {
        return 'Invalid format of Private Key.';
    }
}

/**
 * @param array<string, string> $values
 * @param resource $privateKey
 * @return array<int, array>|string
 */
function makeSteps(array $values, $privateKey, string $language)
{
    // Create public key from private key
    $publicKeyInfos = openssl_pkey_get_details($privateKey);
    if (!$publicKeyInfos) {
        return 'Can\'t extract Public Key from Private Key.';
    }
    $publicKey = openssl_pkey_get_public($publicKeyInfos['key']);
    if (!$publicKey) {
        return 'Can\'t get Public Key in string.';
    }

    $steps = []; // init steps array


    if ($values['method'] === '0') {
        // Payload
        $payload = createPayload($values['payload']);
        if (!$payload) {
            return 'Can\'t generate payload.';
        }
        $steps[] = call_user_func($language.'PayloadStep', $payload, $values['payload']);

        // Digest
        $digest = createDigest($payload);
        $steps[] = call_user_func($language.'DigestStep', $digest);

        // Create signing string
        $signingString = createSigningString($values['request-target'], $values['date'], $values['x-request-id'], $digest);
        $steps[] = call_user_func($language.'SigningStringStep', nl2br($signingString), $values['request-target'], $values['date'], $values['x-request-id'], true);

        $headers = '(request-target) date digest x-request-id';
    } else {
        // Create signing string
        $signingString = createSigningString($values['request-target'], $values['date'], $values['x-request-id']);
        $steps[] = call_user_func($language.'signingStringStep', nl2br($signingString), $values['request-target'], $values['date'], $values['x-request-id'], false);

        $headers = '(request-target) date x-request-id';
    }

    // Signature
    $signature = createSignature($signingString, $privateKey);
    $steps[] = call_user_func($language.'signatureStep', $signature);

    // Header signature
    $signatureHeader = createSignatureHeader($values['app-id'], $headers, $signature);
    $steps[] =  call_user_func($language.'signatureHeaderStep', $signatureHeader, $values['app-id'], $headers, $signature);

    // Decrypt
    $signatureMatch = createSignatureMatch($signingString, $signature, $publicKey);
    $steps[] =  call_user_func($language.'signatureMatchStep', $signatureMatch);

    return $steps;
}

// Start template content
ob_start();
?>

<div class="p-5 mb-4 bg-light rounded-3">
    <div class="container-fluid">
        <h1 class="display-5 fw-bold">Signature guide</h1>
        <p class="fs-4 mb-0">This guide helps you go through the process of creating a Signature as defined by the draft cavage signature #10 in the context of Fintecture APIs calls</p>
        <a href="https://doc.fintecture.com/docs/api-http-signature">https://doc.fintecture.com/docs/api-http-signature</a>
    </div>
</div>

<?php
if (isset($error) && !empty($error)) {
    echo '<div class="alert alert-danger" role="alert"><b>Error: </b>' . $error  .'</div>';
}

require_once('views/form.php');

if ($isFormSubmitted && !isset($error)) {
    require_once('views/results.php');
}

// Template generation
$content = ob_get_clean();
require_once('layout.php');
?>