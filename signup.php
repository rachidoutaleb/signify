<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pki";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql_create_db) === FALSE) {
  die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create users_table if it doesn't exist
$sql_create_table = "CREATE TABLE IF NOT EXISTS users_table (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
)";
if ($conn->query($sql_create_table) === FALSE) {
  die("Error creating table: " . $conn->error);
}

// If the registration form was submitted, try to add the new user
if (isset($_POST['username']) && isset($_POST['password'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Check if the username already exists
  $sql_check = "SELECT * FROM users_table WHERE username='$username'";
  $result_check = $conn->query($sql_check);

  if ($result_check->num_rows > 0) {
    // Username already exists, display an error message
    $registration_error = "Username already exists. Please choose a different username.";
  } else {
    // Hash the password before storing it in the database
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new user's record into the users_table
    $sql = "INSERT INTO users_table (username, password) VALUES ('$username', '$password_hash')";
    if ($conn->query($sql) === TRUE) {
      // User was added successfully, redirect to login page
      header("Location: signin.php");
      exit;
    } else {
      // There was an error adding the user, display an error message
      $registration_error = "Error: " . $sql . "<br>" . $conn->error;
    }
  }
}

$conn->close(); // Close the database connection
?>


<!DOCTYPE html>
<html>
<head>
  <title>Registration</title>
  <style>
    /* General styles */
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-color: #f0f2f5;
      font-family: 'Arial', sans-serif;
      margin: 0;
    }

    #container {
      background-color: #ffffff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      max-width: 400px;
      width: 100%;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
      text-align: center;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 15px;
    }

    label {
      margin-bottom: 5px;
      color: #333;
      font-weight: bold;
    }

    input[type=text], input[type=password] {
      width: 100%;
      padding: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
      font-size: 16px;
    }

    input[type=submit] {
      width: 100%;
      padding: 15px;
      background-color: #007bff;
      border: none;
      border-radius: 5px;
      color: white;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    input[type=submit]:hover {
      background-color: #0056b3;
    }

    .error {
      color: red;
      margin: 10px 0;
    }

    .switch-button {
      display: block;
      margin-top: 20px;
      padding: 15px;
      background-color: #6c757d;
      color: white;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      font-size: 16px;
      text-align: center;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .switch-button:hover {
      background-color: #5a6268;
    }
  </style>
</head>
<body>
  <div id="container">
    <h2>Registration</h2>
    <?php if (isset($registration_error)) { echo "<div class='error'>$registration_error</div>"; } ?>
    <form method="post">
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
      </div>

      <input type="submit" value="Register">
    </form>
    <a href="signin.php" class="switch-button">Already have an account? Sign In</a>
  </div>
</body>
</html>
