// ============================================================
//   AIMLAB — LOGIQUE DU MINI JEU
// ============================================================

const game = document.getElementById('game');
const target = document.getElementById('target');
const scoreDisplay = document.getElementById('score');
const hitSound = document.getElementById('hit-sound');
const retryBtn = document.getElementById('retry-btn');

const totalTargets = 10;
let clicks = 0;
let times = [];
let startTime = null;
let gameStarted = false;

// ============================================================
// 🧱 Anti-DRAG / Anti-SELECT — 100% bloque les glitches
// ============================================================
target.draggable = false;
target.addEventListener("dragstart", e => e.preventDefault());
target.addEventListener("mousedown", e => e.preventDefault());
target.addEventListener("mousemove", e => e.preventDefault());
target.addEventListener("drag", e => e.preventDefault());
target.addEventListener("selectstart", e => e.preventDefault());

// ============================================================
// Position aléatoire
// ============================================================
function randomPosition() {
    const rect = game.getBoundingClientRect();
    const x = Math.random() * (rect.width - target.width);
    const y = Math.random() * (rect.height - target.height);
    target.style.left = `${x}px`;
    target.style.top = `${y}px`;
}

function nextTarget() {
    randomPosition();
}

// ============================================================
// Fin de partie
// ============================================================
function endGame() {
    target.style.display = 'none';

    const avg = times.reduce((a, b) => a + b, 0) / times.length;
    scoreDisplay.textContent = `Bien joué ! Temps moyen : ${avg.toFixed(0)} ms par cible.`;

    // --------------------------------------------------------
    // 🔥 Envoi score → Symfony
    // --------------------------------------------------------
    fetch('/aimlab/submit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ average: avg })
    })
    .then(res => res.json())
    .then(() => loadTop3()) // Mise à jour podium INSTANT
    .catch(err => console.error('Erreur sauvegarde score:', err));

    retryBtn.style.display = 'inline-block';
}

// ============================================================
// Clic sur la cible
// ============================================================
target.addEventListener('click', () => {

    if (!gameStarted) {
        gameStarted = true;
        startTime = performance.now();
    }

    target.classList.add('hit');
    hitSound.currentTime = 0;
    hitSound.play();

    const flash = document.createElement('div');
    flash.classList.add('flash');
    game.appendChild(flash);
    setTimeout(() => flash.remove(), 150);

    if (clicks > 0) {
        const reaction = performance.now() - startTime;
        times.push(reaction);
    }

    clicks++;

    if (clicks < totalTargets) {
        scoreDisplay.textContent = `Clics : ${clicks} / ${totalTargets}`;
        randomPosition();
        startTime = performance.now();
    } else {
        endGame();
    }

    setTimeout(() => target.classList.remove('hit'), 80);
});

// ============================================================
// Rejouer
// ============================================================
retryBtn.addEventListener('click', () => {
    clicks = 0;
    times = [];
    gameStarted = false;
    scoreDisplay.textContent = `Clics : 0 / ${totalTargets}`;
    target.style.display = 'block';
    retryBtn.style.display = 'none';
    nextTarget();
});

// Premier target
nextTarget();

// ============================================================
// 🔥 Récupération dynamique du TOP 3
// ============================================================

function loadTop3() {
    const box = document.getElementById('aimlab-top3');
    if (!box) return;

    fetch('/aimlab/top3')
        .then(res => res.json())
        .then(data => {

            if (!Array.isArray(data) || data.length === 0) {
                box.innerHTML = "<em>Aucun score enregistré pour le moment.</em>";
                return;
            }

            // Réordonner : 2e, 1er, 3e
            const ordered = [
                data[1] || null, // 🥈
                data[0] || null, // 🥇
                data[2] || null  // 🥉
            ];

            const medals = ["🥈", "🥇", "🥉"];

            let html = `
            <h2 style="margin:0; font-size:1.3rem; color:#2a7a0b;">🏆 Top 3 des meilleurs joueurs</h2>
            <div style="display:flex; justify-content:center; gap:25px; margin-top:10px;">`;

            ordered.forEach((u, index) => {
                if (!u) return;

                html += `
                <div style="text-align:center;">
                    <div style="font-size:1.4rem;">${medals[index]}</div>
                    <div style="font-weight:600;">${u.prenom} ${u.nom_initial}.</div>
                    <div style="opacity:0.7;">${u.average} ms</div>
                </div>`;
            });

            html += "</div>";
            box.innerHTML = html;
        })
        .catch(() => {
            box.innerHTML =
                "<span style='color:red;'>Erreur lors du chargement.</span>";
        });
}

// Charge dès ouverture
document.addEventListener("DOMContentLoaded", loadTop3);


