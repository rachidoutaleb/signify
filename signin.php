<?php
// Connect to the MySQL database
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "pki";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// If the login form was submitted, try to authenticate the user
if (isset($_POST['username']) && isset($_POST['password'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Query the users_table for the entered username
  $sql = "SELECT * FROM users_table WHERE username='$username'";
  $result = $conn->query($sql);

  if ($result->num_rows == 1) {
    // User was found, check if the entered password matches the stored password hash
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
      // Password matches, set session variable and redirect to secure page
      session_start();
      $_SESSION['loggedin'] = true;
      header("Location: dashboard.html"); // Changed redirection to dashboard.html
      exit;
    } else {
      // Password doesn't match, display error message
      $login_error = "Invalid username or password";
    }
  } else {
    // User not found, display error message
    $login_error = "Invalid username or password";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-color: #f0f2f5;
      font-family: 'Arial', sans-serif;
      margin: 0;
    }

    .container {
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
      text-align: center;
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
  <div class="container">
    <form method="post">
      <h2>Login</h2>
      <?php if (isset($login_error)) { echo "<div class='error'>$login_error</div>"; } ?>
      <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
      </div>

      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
      </div>

      <input type="submit" value="Login">
    </form>
    <a href="signup.php" class="switch-button">Don't have an account? Sign Up</a>
  </div>
</body>
</html>
