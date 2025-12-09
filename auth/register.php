<?php
session_start();
require_once("../config/db.php");
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $pass = $_POST['password'];

  if (!$name || !$email || !$pass) {
    $error = "Fill all fields.";
  } else {

    // 💡 Verificar se nome OU email já existem
    $check = $conn->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
    $check->bind_param("ss", $name, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $error = "Nome ou Email já registrado.";
    } else {

      // 🔒 Criar conta
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sss", $name, $email, $hash);

      if ($stmt->execute()) {
        header("Location: login.php");
        exit;
      } else {
        $error = "Error: " . $conn->error;
      }
    }
  }
}
?>

<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <title>Clocky - Register</title>
  <link rel="shortcut icon" href="../images/logo.png" type="image/x-icon">
  <link rel="stylesheet" href="../styles.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jersey+10&family=Josefin+Sans:ital,wght@0,700;1,700&family=Pixelify+Sans:wght@400..700&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap"
    rel="stylesheet">
</head>

<body>
  <div class="btn-voltar">
    <a href="../index.php">Voltar</a>
  </div>
  <div class="login-container">
    <h2>Criar Conta</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input name="name" placeholder="Nome" required>
      <input name="email" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Senha" required>
      <button type="submit">Registrar</button>
    </form>
  </div>
</body>

</html>