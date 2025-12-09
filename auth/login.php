<?php
session_start();
require_once "../config/db.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $sql = "SELECT id, name, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $error = "Erro na consulta: " . $conn->error;
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($stmt->errno) {
            $error = "Erro ao executar: " . $stmt->error;
        } else {
            $res = $stmt->get_result();

            if ($res->num_rows === 1) {
                $user = $res->fetch_assoc();
                if (password_verify($pass, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = "Senha errada";
                }
            } else {
                $error = "Email não encontrado. (Linhas encontradas: " . $res->num_rows . ")";
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Clocky - Login</title>
    <link rel="shortcut icon" href="../images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jersey+10&family=Josefin+Sans:ital,wght@0,700;1,700&family=Pixelify+Sans:wght@400..700&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="titulo">
        <h1>Clocky</h1>
    </div>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <input name="email" type="email" placeholder="Email" required>
            <input name="password" type="password" placeholder="Senha" required>
            <button type="submit">Entrar</button>
            <p>Não tem conta? <a href="register.php">Registrar</a></p>
        </form>
    </div>
    <div class="image-estudante">
        <img src="../images/estudante.png" alt="">
    </div>
</body>

</html>