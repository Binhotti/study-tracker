<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Fetch subjects and total hours per subject
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

$user_name = $_SESSION['user_name'] ?? 'User';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <title>Study Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="topbar">
        <div style="color:#b3b3b3">seg., <?= date('d/m') ?></div>
        <div style="color:#; font-size:24px">Bem vindo, <?= htmlspecialchars($user_name) ?>! </div>
        <div class="logout">
            <a href="auth/logout.php">Sair</a>
        </div>
    </div>

    <div class="header">
        <div class="timer-display" id="mainTimer">00:00:00</div>
        <div class="small">Total hoje: <strong id="todayTotal"><?php
        $seconds = (int) ($today_total * 3600);
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
                    // choose color by subject id simple
                    if ($s['id'] % 3 == 0)
                        $color = "pink";
                    elseif ($s['id'] % 2 == 0)
                        $color = "blue";
                    ?>
                    <div class="play"
                        style="background:<?= ($color == 'pink' ? '#ff2d82' : ($color == 'blue' ? '#3a47d5' : '#ff7a18')) ?>"
                        data-subject="<?= $s['id'] ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24">
                            <path fill="#fff" d="M8 5v14l11-7z" />
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:16px"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="small" id="time-<?= $s['id'] ?>"><?php
                          $seconds = (int) ($s['total_hours'] * 3600);
                          $h = intdiv($seconds, 3600);
                          $m = intdiv($seconds % 3600, 60);
                          $sec = $seconds % 60;
                          echo str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . ':' . str_pad($sec, 2, '0', STR_PAD_LEFT);
                          ?></div>
                    </div>
                </div>
                <div class="small"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="add-subj">
        <form action="subjects/create.php" method="post">
            <input name="name" placeholder="Adicionar matéria" required>
            <button type="submit">Adicionar</button>
        </form>
    </div>

    <script>
        // Timer state
        let runningSession = null;
        let tickInterval = null;
        let elapsedSeconds = 0;
        let runningButton = null;

        const mainTimerEl = document.getElementById('mainTimer');
        const todayTotalEl = document.getElementById('todayTotal');

        function formatHMS(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return String(h).padStart(2, '0') + ":" + String(m).padStart(2, '0') + ":" + String(s).padStart(2, '0');
        }

        function stopPlayIcon() {
            return '<svg width="16" height="16" viewBox="0 0 24 24"><rect fill="#fff" x="6" y="4" width="4" height="16"/><rect fill="#fff" x="14" y="4" width="4" height="16"/></svg>';
        }

        function playIcon() {
            return '<svg width="16" height="16" viewBox="0 0 24 24"><path fill="#fff" d="M8 5v14l11-7z"/></svg>';
        }

        function updateTotalDisplay(newTotalSeconds) {
            const h = Math.floor(newTotalSeconds / 3600);
            const m = Math.floor((newTotalSeconds % 3600) / 60);
            const s = newTotalSeconds % 60;
            todayTotalEl.textContent = String(h).padStart(2, '0') + ":" + String(m).padStart(2, '0') + ":" + String(s).padStart(2, '0');
        }

        // Handle play/stop buttons
        document.querySelectorAll('.play').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const subjectId = this.getAttribute('data-subject');
                const self = this;

                console.log('Button clicked for subject:', subjectId, 'Running session:', runningSession);

                // Se já tem uma sessão rodando
                if (runningSession) {
                    // Se é o mesmo botão, para
                    if (runningSession.subject_id == subjectId) {
                        console.log('Stopping session with elapsed:', elapsedSeconds, 'seconds');
                        // STOP
                        fetch('sessions/stop.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'session_id=' + runningSession.id + '&elapsed_seconds=' + elapsedSeconds
                        }).then(r => r.json()).then(data => {
                            console.log('Stop response:', data);
                            if (data.ok) {
                                // Para o intervalo
                                if (tickInterval) clearInterval(tickInterval);

                                // Remove visual do botão
                                if (runningButton) {
                                    runningButton.classList.remove('playing');
                                    runningButton.innerHTML = playIcon();
                                }

                                // Parse current total from todayTotalEl
                                const totalText = todayTotalEl.textContent;
                                const [h, m, s] = totalText.split(':').map(Number);
                                const currentTotalSeconds = h * 3600 + m * 60 + s;

                                // Add the new elapsed seconds to the total
                                const newTotalSeconds = currentTotalSeconds + elapsedSeconds;

                                // Update display
                                updateTotalDisplay(newTotalSeconds);

                                // Update subject time
                                const subjectTimeEl = document.getElementById('time-' + subjectId);
                                if (subjectTimeEl) {
                                    const subjectText = subjectTimeEl.textContent;
                                    const [sh, sm, ss] = subjectText.split(':').map(Number);
                                    const currentSubjectSeconds = sh * 3600 + sm * 60 + ss;
                                    const newSubjectSeconds = currentSubjectSeconds + elapsedSeconds;
                                    const sh2 = Math.floor(newSubjectSeconds / 3600);
                                    const sm2 = Math.floor((newSubjectSeconds % 3600) / 60);
                                    const ss2 = newSubjectSeconds % 60;
                                    subjectTimeEl.textContent = String(sh2).padStart(2, '0') + ":" + String(sm2).padStart(2, '0') + ":" + String(ss2).padStart(2, '0');
                                }

                                // Reset
                                runningSession = null;
                                elapsedSeconds = 0;
                                mainTimerEl.textContent = "00:00:00";
                                runningButton = null;

                                console.log('Session stopped and data updated.');
                            } else {
                                alert(data.error || 'Erro ao parar sessão');
                            }
                        }).catch(e => console.error('Erro:', e));
                    } else {
                        alert('Já existe uma sessão em andamento. Encerre-a primeiro.');
                    }
                } else {
                    console.log('Starting new session');
                    // START new session
                    fetch('sessions/start.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'subject_id=' + subjectId
                    }).then(r => r.json()).then(data => {
                        console.log('Start response:', data);
                        if (data.ok) {
                            runningSession = {
                                id: data.session_id,
                                subject_id: parseInt(subjectId)
                            };

                            // Update button visually
                            runningButton = self;
                            runningButton.classList.add('playing');
                            runningButton.innerHTML = stopPlayIcon();

                            // Reset counter e comça a contar
                            elapsedSeconds = 0;
                            mainTimerEl.textContent = "00:00:00";

                            if (tickInterval) clearInterval(tickInterval);
                            tickInterval = setInterval(() => {
                                elapsedSeconds++;
                                mainTimerEl.textContent = formatHMS(elapsedSeconds);
                                console.log('Elapsed:', elapsedSeconds);
                            }, 1000);
                        } else {
                            alert(data.error || 'Erro ao iniciar sessão');
                        }
                    }).catch(e => console.error('Erro:', e));
                }
            });
        });
    </script>

</body>

</html>