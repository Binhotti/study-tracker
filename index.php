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

<?php
require_once("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

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
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Study Tracker</title>
<link rel="stylesheet" href="styles.css">
<style>
/* small overrides for dashboard */
.header { text-align:center; padding:18px 0; }
.timer-display { font-size:48px; font-weight:700; }
.list { max-width:720px; margin: 10px auto; }
.item { display:flex; justify-content:space-between; align-items:center; padding:12px; border-bottom:1px solid #1c1c1c; }
.item .left { display:flex; align-items:center; gap:12px; }
.play { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; }
.play.playing { box-shadow: 0 0 0 6px rgba(58,71,213,0.12); }
.add-subj { margin:12px auto; display:flex; gap:8px; justify-content:center; }
.small { color:#b3b3b3; font-size:13px; }
.topbar { display:flex; justify-content:space-between; align-items:center; padding:10px 16px; max-width:720px; margin:0 auto; }
</style>
</head>
<body>
<div class="topbar">
  <div style="color:#b3b3b3">seg., <?= date('d/m') ?></div>
  <div style="color:#b3b3b3"><?= htmlspecialchars($user_name) ?> • <a style="color:#bbb" href="auth/logout.php">Logout</a></div>
</div>

<div class="header">
  <div class="timer-display" id="mainTimer">00:00:00</div>
  <div class="small">Total hoje: <strong id="todayTotal"><?= number_format($today_total,2) ?></strong> h</div>
</div>

<div class="list">
  <?php foreach($subjects as $s): ?>
    <div class="item" data-subject-id="<?= $s['id'] ?>">
      <div class="left">
        <?php
          $color = "orange";
          // choose color by subject id simple
          if ($s['id'] % 3 == 0) $color = "pink";
          elseif ($s['id'] % 2 == 0) $color = "blue";
        ?>
        <div class="play" style="background:<?= ($color=='pink'? '#ff2d82':($color=='blue'? '#3a47d5':'#ff7a18')) ?>" data-subject="<?= $s['id'] ?>">
          <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#fff" d="M8 5v14l11-7z"/></svg>
        </div>
        <div>
          <div style="font-size:16px"><?= htmlspecialchars($s['name']) ?></div>
          <div class="small"><?= number_format($s['total_hours'],2) ?> h</div>
        </div>
      </div>
      <div class="small" id="time-<?= $s['id'] ?>">0:00:00</div>
    </div>
  <?php endforeach; ?>
</div>

<div class="add-subj">
  <form action="subjects/create.php" method="post">
    <input name="name" placeholder="Add subject" required>
    <button type="submit">Add</button>
  </form>
</div>

<script>
// small JS to handle start/stop
let runningSession = <?= $running ? json_encode($running) : 'null' ?>;
const mainTimerEl = document.getElementById('mainTimer');
const todayTotalEl = document.getElementById('todayTotal');

function formatHMS(seconds) {
  const h = Math.floor(seconds/3600), m = Math.floor((seconds%3600)/60), s = seconds%60;
  return String(h).padStart(2,'0')+":"+String(m).padStart(2,'0')+":"+String(s).padStart(2,'0');
}

let startTs = runningSession ? Date.parse("<?= $running ? $running['start_time'] : '' ?>")/1000 : null;
let tickInterval = null;
if (startTs) {
  tickInterval = setInterval(()=> {
    const now = Math.floor(Date.now()/1000);
    mainTimerEl.textContent = formatHMS(now - startTs);
  }, 1000);
}

// Update per-subject totals (simple static display already from server).
document.querySelectorAll('.play').forEach(btn => {
  btn.addEventListener('click', function(){
    const subjectId = this.getAttribute('data-subject');
    if (runningSession && runningSession.subject_id == subjectId) {
      // stop this session
      fetch('sessions/stop.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'session_id=' + runningSession.id
      }).then(r=>r.json()).then(data=>{
        if (data.ok) {
          runningSession = null;
          startTs = null;
          mainTimerEl.textContent = "00:00:00";
          if (tickInterval) clearInterval(tickInterval);
          // reload to refresh totals
          location.reload();
        } else alert(data.error || 'Error stopping');
      });
    } else {
      // start new session
      fetch('sessions/start.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'subject_id=' + subjectId
      }).then(r=>r.json()).then(data=>{
        if (data.ok) {
          runningSession = {id: data.session_id, subject_id: parseInt(subjectId)};
          startTs = Math.floor(Date.parse(data.start_time)/1000);
          mainTimerEl.textContent = "00:00:00";
          if (tickInterval) clearInterval(tickInterval);
          tickInterval = setInterval(()=> {
            const now = Math.floor(Date.now()/1000);
            mainTimerEl.textContent = formatHMS(now - startTs);
          }, 1000);
        } else {
          alert(data.error || 'Error starting');
        }
      });
    }
  });
});
</script>

</body>
</html>
