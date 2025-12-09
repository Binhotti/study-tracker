<?php
// subjects/edit.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once('../config/db.php');

// must be logged
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// validate id param
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$subject_id = (int) $_GET['id'];

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'Name cannot be empty.';
    } else {
        // verify ownership and update safely
        $upd = $conn->prepare("UPDATE subjects SET name = ? WHERE id = ? AND user_id = ?");
        $upd->bind_param("sii", $name, $subject_id, $user_id);

        if ($upd->execute()) {
            // success: redirect back to dashboard
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

// On GET (or if POST failed), load subject to show form
$stmt = $conn->prepare("SELECT id, name FROM subjects WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $subject_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    // not found or not allowed
    header('Location: ../index.php');
    exit;
}

$subject = $res->fetch_assoc();
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Editar matéria</title>
    <link rel="shortcut icon" href="../images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jersey+10&family=Josefin+Sans:ital,wght@0,700;1,700&family=Pixelify+Sans:wght@400..700&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <style>

    </style>
</head>

<body>
    <div class="edit-container">
        <h2>Editar Matéria</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="edit.php?id=<?= $subject_id ?>">
            <label for="name">Nome da Matéria</label>
            <div class="input-subject">
                <input id="name" name="name" type="text" value="<?= htmlspecialchars($subject['name']) ?>" required>
            </div>

            <div class="edit-actions">
                <button type="submit" class="btn btn-save">Salvar</button>
                <a href="../index.php" class="btn btn-cancel"
                    style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Cancelar</a>
            </div>
        </form>
    </div>
</body>

</html>