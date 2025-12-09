<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT s.id, s.name,
       IFNULL((SELECT SUM(hours) FROM study_sessions ss WHERE ss.subject_id = s.id AND ss.user_id = ?),0) AS total_hours
       FROM subjects s
       WHERE s.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$subj_res = $stmt->get_result();
$subjects = $subj_res->fetch_all(MYSQLI_ASSOC);

// Check running session (if any)
$stmt2 = $conn->prepare("SELECT id, subject_id, start_time FROM study_sessions WHERE user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$r2 = $stmt2->get_result();
$running = $r2->fetch_assoc(); // may be null

// Total today
$stmt3 = $conn->prepare("SELECT IFNULL(SUM(hours),0) AS today_total FROM study_sessions WHERE user_id = ? AND session_date = CURDATE()");
$stmt3->bind_param("i", $user_id);
$stmt3->execute();
$row3 = $stmt3->get_result()->fetch_assoc();
$today_total = $row3['today_total'];

if ($today_total >= 24) {
    $today_total = 0;
}

$user_name = $_SESSION['user_name'] ?? 'User';


$days = [
    "Domingo,",
    "Segunda-Feira,",
    "Terça-Feira,",
    "Quarta-Feira,",
    "Quinta-Feira,",
    "Sexta-Feira,",
    "Sábado,"
];

$weekday = $days[date('w')]; // 0 = domingo, 6 = sábado

?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Clocky</title>
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="topbar">
        <div style="color:#b3b3b3"><?= $weekday ?> <?= date('d/m') ?>
        </div>
        <div style="color:#; font-size:24px">Bem vindo, <?= htmlspecialchars($user_name) ?>! </div>
        <div class="logout">
            <a href="auth/logout.php">Sair</a>
        </div>
    </div>

    <div class="header">
        <div class="timer-display" id="mainTimer">00:00:00</div>
        <div class="small">Total hoje: <strong id="todayTotal"><?php
        $seconds = (int) ($today_total * 3600);

        if ($today_total >= 24) {
            $today_total = 0;
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        echo str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . ':' . str_pad($s, 2, '0', STR_PAD_LEFT);
        ?></strong></div>
    </div>

    <div class="list">
        <?php foreach ($subjects as $s): ?>
            <div class="item" data-subject-id="<?= $s['id'] ?>">
                <div class="left">
                    <?php
                    $color = "orange";
                    if ($s['id'] % 3 == 0)
                        $color = "pink";
                    elseif ($s['id'] % 2 == 0)
                        $color = "blue";
                    ?>
                    <div class="play"
                        style="background:<?= ($color == 'pink' ? '#ff2d82' : ($color == 'blue' ? '#3a47d5' : '#ff7a18')) ?>;"
                        data-subject="<?= $s['id'] ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path fill="#fff" d="M8 5v14l11-7z" />
                        </svg>
                    </div>

                    <div>
                        <div style="font-size:16px"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="small" id="time-<?= $s['id'] ?>">
                            <?php
                            $seconds = (int) ($s['total_hours'] * 3600);
                            $h = intdiv($seconds, 3600);
                            $m = intdiv($seconds % 3600, 60);
                            $sec = $seconds % 60;
                            echo str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . ':' . str_pad($sec, 2, '0', STR_PAD_LEFT);
                            ?>
                        </div>
                    </div>
                </div>

                <div class="action">
                    <div class="delete">
                        <a href="subjects/delete.php?id=<?= $s['id'] ?>"
                            onclick="return confirm('Tem certeza que deseja excluir esta matéria? Todas as sessões relacionadas também serão excluídas.')">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path fill="#888"
                                    d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6Z" />
                            </svg>
                        </a>
                    </div>

                    <div class="edit">
                        <a href="subjects/edit.php?id=<?= $s['id'] ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24">
                                <path fill="#888"
                                    d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.13,5.12L18.88,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="add-subj">
        <form action="subjects/create.php" method="post">
            <input name="name" placeholder="Adicionar matéria" required>
            <button type="submit">Adicionar</button>
        </form>
    </div>
</body>
<script src="index.js"></script>

</html>