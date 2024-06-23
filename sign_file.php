<?php
// Function to load the private key from a file
function load_private_key($key_path) {
    $private_key = openssl_pkey_get_private(file_get_contents($key_path));
    if ($private_key === false) {
        throw new Exception("Failed to load the private key");
    }
    return $private_key;
}

// Function to sign a document with a private key
function sign_document($file_path, $key_path) {
    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

    // Read the file content
    $file_contents = file_get_contents($file_path);
    if ($file_contents === false) {
        throw new Exception("Failed to read the file content");
    }

    // Normalize line endings for text files
    if (!in_array($file_extension, array('jpg', 'png', 'gif', 'pdf', 'zip', 'jpeg', 'docx'))) {
        $file_contents = str_replace("\r\n", "\n", $file_contents);
    }

    // Compute the hash of the file content
    $hash = openssl_digest($file_contents, 'sha256');

    // Load the private key
    $private_key = load_private_key($key_path);

    // Sign the hash with the private key
    $signature = '';
    if (openssl_sign($hash, $signature, $private_key)) {
        $timestamp = time();
        $signature_file = 'signature_' . $timestamp . '.txt';
        return array('content' => $signature, 'file_name' => $signature_file);
    } else {
        throw new Exception("Failed to sign the document");
    }
}

// Function to verify the signature of a document
function verify_signature($file_path, $signature, $public_key_path) {
    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);

    // Read the file content
    $file_contents = file_get_contents($file_path);
    if ($file_contents === false) {
        throw new Exception("Failed to read the file content");
    }

    // Normalize line endings for text files
    if (!in_array($file_extension, array('jpg', 'png', 'gif', 'pdf', 'zip', 'jpeg', 'docx'))) {
        $file_contents = str_replace("\r\n", "\n", $file_contents);
    }

    // Compute the hash of the file content
    $hash = openssl_digest($file_contents, 'sha256');

    // Load the public key
    $public_key = openssl_pkey_get_public(file_get_contents($public_key_path));
    if ($public_key === false) {
        throw new Exception("Failed to load the public key");
    }

    // Verify the signature with the public key
    $result = openssl_verify($hash, $signature, $public_key);
    
    return $result === 1;
}

// Handling the signature form submission
if (isset($_POST['submit']) && isset($_FILES['file']) && isset($_FILES['key'])) {
    $file_path = $_FILES['file']['tmp_name'];
    $key_path = $_FILES['key']['tmp_name'];

    if (is_file($file_path) && is_file($key_path)) {
        try {
            $signature_data = sign_document($file_path, $key_path);
            $signature_content = $signature_data['content'];
            $signature_file_name = $signature_data['file_name'];

            $signature_path = 'signature/' . $signature_file_name; // Path to the signatures folder
            if (!file_exists('signature')) {
                mkdir('signature', 0777, true); // Create the folder if it doesn't exist
            }
            file_put_contents($signature_path, $signature_content); // Save the signature in the signature folder

            $sign = "<div class='msg'><span class='message success-message'>The signature has been saved to the file: " . $signature_file_name . "</span></div>";
        } catch (Exception $e) {
            $sign = "<div class='msg'><span class='message error-message'>" . $e->getMessage() . "</span></div>";
        }
    } else {
        $sign = "<div class='msg'><span class='message error-message'>Error: The file or the private key does not exist</span></div>";
    }
}

// Handling the verification form submission
if (isset($_POST['verify_submit']) && isset($_FILES['file_verify']) && isset($_FILES['signature_text']) && isset($_FILES['public_key_verify'])) {
    $file_path = $_FILES['file_verify']['tmp_name'];
    $signature_path = $_FILES['signature_text']['tmp_name'];
    $public_key_path = $_FILES['public_key_verify']['tmp_name'];

    if (is_file($file_path) && is_file($signature_path) && is_file($public_key_path)) {
        try {
            $signature = file_get_contents($signature_path);
            $is_valid = verify_signature($file_path, $signature, $public_key_path);

            if ($is_valid) {
                $verif = "<div class='msg'><span class='message success-message'>The signature is valid.</span></div>";
            } else {
                $verif = "<div class='msg'><span class='message error-message'>The signature is invalid.</span></div>";
            }
        } catch (Exception $e) {
            $verif = "<div class='msg'><span class='message error-message'>" . $e->getMessage() . "</span></div>";
        }
    } else {
        $verif = "<span class='message error-message'>Error: The file or the public key does not exist</span>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign and Verify a Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        h1 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 30px;
            color: #333;
        }

        form {
            margin: 0 auto;
        }

        p {
            margin-top: 0;
            margin-bottom: 10px;
        }

        input[type=file] {
            margin-bottom: 20px;
        }

        input[type=submit] {
            display: block;
            margin: 0 auto 20px;
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        input[type=submit]:hover {
            background-color: #0062cc;
        }

        button {
            display: block;
            margin: 0 auto 20px;
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0062cc;
        }

        .green-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .green-button:hover {
            background-color: #218838;
        }

        .green-button:active {
            background-color: #1e7e34;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(0);
        }

        .center-button {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .msg-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .msg {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: auto;
            border-radius: 5px;
        }

        .success-message {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .error-message-box {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div id="container">
        <!-- Signing section -->
        <div id="sign-section">
            <h1>Sign a Document</h1>
            <div id="sign-error-message" class="error-message-box" style="display:none;"></div>
            <form action="" method="post" enctype="multipart/form-data" onsubmit="return validateSignForm()">
                <p>Select a file to sign:</p>
                <input type="file" name="file" id="file">
                <p>Select a private key:</p>
                <input type="file" name="key" id="key">
                <input type="submit" value="Sign" name="submit">
            </form>
            <?php echo isset($sign) ? $sign : ''; ?>
            <div class="center-button">
                <button class="green-button" onclick="showSection('verify-section')">Go to Document Verification</button>
            </div>
        </div>

        <!-- Verification section -->
        <div id="verify-section" style="display:none;">
            <h1>Verify a Document</h1>
            <div id="verify-error-message" class="error-message-box" style="display:none;"></div>
            <form action="" method="post" enctype="multipart/form-data" onsubmit="return validateVerifyForm()">
                <p>Select a file to verify:</p>
                <input type="file" name="file_verify" id="file_verify">
                <p>Enter the signature:</p>
                <input type="file" name="signature_text" id="signature_text">
                <p>Select a public key file:</p>
                <input type="file" name="public_key_verify" id="public_key_verify">
                <input type="submit" value="Verify" name="verify_submit">
            </form>
            <?php echo isset($verif) ? $verif : ''; ?>
            <div class="center-button">
                <button class="green-button" onclick="showSection('sign-section')">Go to Document Signing</button>
            </div>
        </div>
    </div>

    <script>
        // JavaScript functions for managing sections and form validation
        function showSection(sectionId) {
            document.getElementById('sign-section').style.display = 'none';
            document.getElementById('verify-section').style.display = 'none';
            document.getElementById(sectionId).style.display = 'block';
            localStorage.setItem('currentSection', sectionId); // Save the current section in local storage
        }

        window.onload = function () {
            const currentSection = localStorage.getItem('currentSection') || 'sign-section';
            showSection(currentSection);
        }

        function validateSignForm() {
            var file = document.getElementById('file').value;
            var key = document.getElementById('key').value;
            var errorMessageDiv = document.getElementById('sign-error-message');

            if (file === '' || key === '') {
                errorMessageDiv.textContent = 'Please fill in all fields.';
                errorMessageDiv.style.display = 'block';
                return false;
            }

            errorMessageDiv.style.display = 'none';
            return true;
        }

        function validateVerifyForm() {
            var fileVerify = document.getElementById('file_verify').value;
            var signatureText = document.getElementById('signature_text').value;
            var publicKeyVerify = document.getElementById('public_key_verify').value;
            var errorMessageDiv = document.getElementById('verify-error-message');

            if (fileVerify === '' || signatureText === '' || publicKeyVerify === '') {
                errorMessageDiv.textContent = 'Please fill in all fields.';
                errorMessageDiv.style.display = 'block';
                return false;
            }

            errorMessageDiv.style.display = 'none';
            return true;
        }
    </script>
</body>
</html>
