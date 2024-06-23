<?php
$openssl_cnf_path = 'C:/wamp64/bin/php/php8.2.13/extras/ssl/openssl.cnf';

if (file_exists($openssl_cnf_path)) {
    putenv("OPENSSL_CONF=$openssl_cnf_path");
} else {
    exit("Script terminated due to missing OpenSSL configuration file.");
}

$directory = __DIR__ . '\\keys';
$last_generation_file = $directory . '\\last_generation.txt';

function getLastGenerationTime() {
    global $last_generation_file;
    if (file_exists($last_generation_file)) {
        return intval(file_get_contents($last_generation_file));
    }
    return 0;
}

function setLastGenerationTime($time) {
    global $last_generation_file;
    file_put_contents($last_generation_file, $time);
}

function getStoredKeys() {
    global $directory;
    $privateFiles = glob($directory . '\\private_key_*.pem');
    $publicFiles = glob($directory . '\\public_key_*.pem');
    
    if (empty($privateFiles) || empty($publicFiles)) {
        return null;
    }
    
    // Trouver le fichier le plus récent
    $latestPrivateFile = max($privateFiles);
    $latestPublicFile = max($publicFiles);
    
    $privateKey = file_get_contents($latestPrivateFile);
    $publicKey = file_get_contents($latestPublicFile);

    return "Private Key:\n$privateKey\n\nPublic Key:\n$publicKey";
}

function generateKeys($type) {
    global $directory;

    $config = array(
        "config" => getenv("OPENSSL_CONF"),
        "digest_alg" => "sha512",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );

    if (!file_exists($directory)) {
        if (!mkdir($directory, 0777, true)) {
            return null;
        }
    }

    if (!is_writable($directory)) {
        return null;
    }

    $res = openssl_pkey_new($config);
    
    if ($res === false) {
        return null;
    }

    $privateKey = '';
    $exportSuccess = openssl_pkey_export($res, $privateKey, null, $config);

    if (!$exportSuccess) {
        return null;
    }

    $keyDetails = openssl_pkey_get_details($res);
    
    if ($keyDetails === false) {
        return null;
    }
    
    $publicKey = $keyDetails["key"];

    $timestamp = time();
    $privateKeyFilename = $directory . "\\private_key_$timestamp.pem";
    $publicKeyFilename = $directory . "\\public_key_$timestamp.pem";

    if (file_put_contents($privateKeyFilename, $privateKey) === false) {
        return null;
    }

    if (file_put_contents($publicKeyFilename, $publicKey) === false) {
        return null;
    }

    setLastGenerationTime($timestamp);

    return "Private Key:\n$privateKey\n\nPublic Key:\n$publicKey";
}

$keyType = $_POST['generate'] ?? null;

if ($keyType) {
    $last_generation = getLastGenerationTime();
    $current_time = time();
    $time_diff = $current_time - $last_generation;

    if ($last_generation == 0) {
        // First time generating keys
        $keyContent = generateKeys($keyType);
        if ($keyContent !== null) {
            echo json_encode(['status' => 'success', 'message' => $keyContent]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while generating the keys.']);
        }
    } elseif ($time_diff < 120) { // 2 minutes = 120 seconds
        // Less than 2 minutes since last generation
        $storedKeys = getStoredKeys();
        if ($storedKeys !== null) {
            echo json_encode([
                'status' => 'wait',
                'message' => $storedKeys,
                'warning' => 'Please wait 2 minutes before generating new keys.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No stored keys found.']);
        }
    } else {
        // More than 2 minutes since last generation
        $keyContent = generateKeys($keyType);
        if ($keyContent !== null) {
            echo json_encode(['status' => 'success', 'message' => $keyContent]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while generating the keys.']);
        }
    }
}
?>