<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$query = $conn->query("SELECT SUM(hours) AS total_hours FROM study_sessions WHERE user_id = $user_id");
$row = $query->fetch_assoc();
$total = $row["total_hours"] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="style.css">
    <title>Study Tracker UI</title>
</head>
<body>
    <div class="header">
        <div>seg., 08/12</div>
        <div class="timer-display">0:59:43</div>
        <div class="subtitle">Descanso 16m 8s</div>
    </div>

    <div class="tab-menu">
        <div class="tab-active">Timer</div>
        <div>Livros</div>
        <div>Insights</div>
        <div>Agenda</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn"></div>
            backend
        </div>
        <div class="task-time">0:59:43</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn orange"></div>
            Automação de IA
        </div>
        <div class="task-time">0:00:00</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn orange"></div>
            projeto
        </div>
        <div class="task-time">0:00:00</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn pink"></div>
            Context API
        </div>
        <div class="task-time">0:00:00</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn orange"></div>
            react
        </div>
        <div class="task-time">0:00:00</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn orange"></div>
            typescript
        </div>
        <div class="task-time">0:00:00</div>
    </div>

    <div class="task">
        <div class="task-name">
            <div class="play-btn orange"></div>
            redux
        </div>
        <div class="task-time">0:00:00</div>
    </div>

    <div class="bottom-menu">
        <div class="bottom-item active">Início</div>
        <div class="bottom-item">Grupos</div>
        <div class="bottom-item">Ver Mais</div>
    </div>
</body>
</html>

