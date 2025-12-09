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

                    // Reset counter e começa a contar
                    elapsedSeconds = 0;
                    mainTimerEl.textContent = "00:00:00";

                    if (tickInterval) clearInterval(tickInterval);
                    tickInterval = setInterval(() => {
                        elapsedSeconds++;
                        if (elapsedSeconds > 72000) {
                            alert("O cronômetro passou de 20 horas. Ele será encerrado automaticamente.");
                       
                            clearInterval(tickInterval);
                            
                            fetch('sessions/stop.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'session_id=' + runningSession.id + '&elapsed_seconds=' + elapsedSeconds
                            }).then(r => r.json()).then(data => {

                            
                                if (runningButton) {
                                    runningButton.classList.remove('playing');
                                    runningButton.innerHTML = playIcon();
                                }

                              
                                mainTimerEl.textContent = "00:00:00";

                           
                                runningSession = null;
                                elapsedSeconds = 0;
                                runningButton = null;

                            }).catch(e => console.error('Erro:', e));

                            return; 
                        }

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