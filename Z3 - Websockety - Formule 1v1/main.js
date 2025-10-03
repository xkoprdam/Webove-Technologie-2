// DOM References
const landing = document.getElementById('landing');
const playBtn = document.getElementById('playBtn');
const howToPlayBtn = document.getElementById('howToPlayBtn');
const nameInput = document.getElementById('nameInput');
const nameField = document.getElementById('nameField');
const submitNameBtn = document.getElementById('submitNameBtn');
const raceOptions = document.getElementById('raceOptions');

const usernameDisplay = document.getElementById('usernameDisplay');
const createRaceBtn = document.getElementById('createRaceBtn');
const connectRaceBtn = document.getElementById('connectRaceBtn');
const chooseLaps = document.getElementById('chooseLaps');
const lapsInput = document.getElementById('lapsInput');
const confirmLapsBtn = document.getElementById('confirmLapsBtn');
const connectToRace = document.getElementById('connectToRace');
const raceIdField = document.getElementById('raceIdField');
const connectRaceConfirmBtn = document.getElementById('connectRaceConfirmBtn');

const waitingScreen = document.getElementById('waitingScreen');
const waitingInfo = document.getElementById('waitingInfo');

const gameScreen = document.getElementById('gameScreen');
const resultsScreen = document.getElementById('resultsScreen');
const resultsTable = document.getElementById('resultsTableBody');
const countdownOverlay = document.getElementById('countdownOverlay');

let userName = '';
let ws = null;

let currentRaceId = null;
let totalLaps = null;
let isHost = false;
let players = {};
let track = {};
let checkpoints = {};
let carWidth = null;
let carHeight = null;

let raceTimerInterval = null;
let raceStartTime = null;

let remoteInput = {
    up: false,
    down: false,
    left: false,
    right: false
};

const keys = {};
const playerImages = {};
let playerStates = {};

function connectWebSocket() {
    ws = new WebSocket('/game');

    ws.addEventListener('open', () => {
        console.log('Connected to server.');
        ws.send(JSON.stringify({ type: 'joinServer', userName }));
    });

    ws.addEventListener('message', (event) => {
        // console.log('Message from server:', event.data);
        const message = JSON.parse(event.data);

        switch (message.type) {
            case 'raceCreated':
                currentRaceId = message.raceId;
                isHost = true;
                showWaitingScreen();
                break;
            case 'playerJoined':
                handlePlayerJoined(message.players);
                currentRaceId = message.raceId;
                break;
            case 'raceStarted':
                startGame(message);
                break;
            case 'stateUpdate':
                stateUpdate(message);
                break;
            case 'playerFinished':
                // console.log('Message from server:', event.data);
                handlePlayerFinished(message.position, message.time);
                break;
            case 'raceFinished':
                console.log('Message from server:', event.data);
                handleRaceFinished(message.results);
                break;
        }
    });

    ws.addEventListener('close', (event) => {
        console.log(`Closed: ${event.code}`);
        setTimeout(connectWebSocket, 2000); // Reconnect after 2s
    });

    ws.addEventListener('error', (event) => {
        console.error('WebSocket error:', event);
    });
}

// Events
playBtn.addEventListener('click', () => {
    landing.classList.add('hidden');
    nameInput.classList.remove('hidden');
    nameField.focus(); // Auto-focus
});

document.getElementById("howToPlayBtn").addEventListener("click", () => {
    document.getElementById("howToPlayModal").classList.remove("hidden");
});

document.getElementById("closeHowToPlay").addEventListener("click", () => {
    document.getElementById("howToPlayModal").classList.add("hidden");
});

document.getElementById('backToLandingBtn').addEventListener('click', () => {
    nameInput.classList.add('hidden');
    landing.classList.remove('hidden');
});

submitNameBtn.addEventListener('click', () => {
    const name = nameField.value.trim();
    if (!name) {
        alert('Please enter a name.');
        return;
    }

    userName = name;
    nameInput.classList.add('hidden');
    raceOptions.classList.remove('hidden');
    usernameDisplay.textContent = `Hello, ${userName}!`;

    connectWebSocket();
});

document.getElementById('backToNameInputBtn').addEventListener('click', () => {
    raceOptions.classList.add('hidden');
    nameInput.classList.remove('hidden');
});

// Create Race
createRaceBtn.addEventListener('click', () => {
    raceOptions.classList.add('hidden');
    chooseLaps.classList.remove('hidden');
});

confirmLapsBtn.addEventListener('click', () => {
    const laps = parseInt(lapsInput.value);
    if (isNaN(laps) || laps < 1) {
        alert('Please enter a valid number of laps!');
        return;
    }

    chooseLaps.classList.add('hidden');

    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'createRace', userName, laps }));
    }
});

document.getElementById('backToRaceOptionsBtn').addEventListener('click', () => {
    chooseLaps.classList.add('hidden');
    raceOptions.classList.remove('hidden');
});

// Connect to Race
connectRaceBtn.addEventListener('click', () => {
    raceOptions.classList.add('hidden');
    connectToRace.classList.remove('hidden');
    raceIdField.focus(); // Auto-focus
});

connectRaceConfirmBtn.addEventListener('click', () => {
    const raceId = raceIdField.value.trim();
    if (!raceId) {
        alert('Please enter a Race ID.');
        return;
    }

    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: 'joinRace', userName, raceId }));
    }

    connectToRace.classList.add('hidden');
    waitingScreen.classList.remove('hidden');
    waitingInfo.textContent = 'Waiting for host to start the race...';
});

document.getElementById('backToRaceOptionsFromConnectBtn').addEventListener('click', () => {
    connectToRace.classList.add('hidden');
    raceOptions.classList.remove('hidden');
});

// Show Waiting Screen
function showWaitingScreen() {
    waitingScreen.classList.remove('hidden');
    waitingInfo.innerHTML = `
        Waiting for another player to join...<br>
        <span class="font-bold text-lg">Race ID: ${currentRaceId}</span><br>
        Share this ID with your friend!
    `;
}

// when another player joins
function handlePlayerJoined(players) {
    if (isHost) {
        waitingInfo.innerHTML = `
            Second player connected!<br>
            Starting the race...
        `;

        // a tiny delay before starting
        setTimeout(() => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ type: 'startRace', raceId: currentRaceId }));
            }
        }, 1500);
    }
}

function startGame(data) {
    waitingScreen.classList.add('hidden');
    gameScreen.classList.remove('hidden');

    // Store race info
    players = data.players;
    playerStates = data.playerStates;
    currentRaceId = data.raceId;
    isHost = data.hostUsername === userName;
    totalLaps = data.laps;

    // Store track and checkpoint data
    track = data.trackData.track;
    checkpoints = data.trackData.checkpoints;
    carWidth = data.trackData.carWidth;
    carHeight = data.trackData.carHeight;

    startCountdown(() => {
        raceStartTime = Date.now();
        startRaceTimer();
        initializeGame();
    });
}

function startCountdown(callback) {
    countdownOverlay.classList.remove('hidden');
    let count = 3;
    countdownOverlay.textContent = count;

    const interval = setInterval(() => {
        count--;
        if (count > 0) {
            countdownOverlay.textContent = count;
        } else {
            clearInterval(interval);
            countdownOverlay.textContent = "GO!";
            setTimeout(() => {
                countdownOverlay.classList.add('hidden');
                document.getElementById('counters').classList.remove('hidden');
                callback();
            }, 1000);
        }
    }, 1000);
}

function startRaceTimer() {
    const timerDisplay = document.getElementById('timerDisplay');
    if (raceTimerInterval) clearInterval(raceTimerInterval);

    raceTimerInterval = setInterval(() => {
        const elapsedMs = Date.now() - raceStartTime;
        const seconds = (elapsedMs / 1000).toFixed(2);
        timerDisplay.textContent = `${seconds}s`;
    }, 100);
}

let inputIntervalId = null;

function initializeGame() {

    if (inputIntervalId) {
        clearInterval(inputIntervalId);
    }

    const canvas = document.getElementById('raceCanvas');
    const ctx = canvas.getContext('2d');

    document.addEventListener('keydown', (e) => keys[e.key.toLowerCase()] = true);
    document.addEventListener('keyup', (e) => keys[e.key.toLowerCase()] = false);

    const carImageRed = new Image();
    const carImageBlue = new Image();
    carImageRed.src = 'assets/formula_car_red.png';
    carImageBlue.src = 'assets/formula_car_blue.png';

    playerImages[players[0]] = carImageRed;
    playerImages[players[1]] = carImageBlue;

    function drawPlayer(userName, playerState) {
        const img = playerImages[userName];
        if (!img) return; // prevent drawing if image isn't assigned yet

        ctx.save();
        ctx.translate(playerState.position.x, playerState.position.y);
        ctx.rotate(playerState.angle);
        ctx.drawImage(img, -carHeight / 2, -carWidth / 2, carHeight, carWidth);
        ctx.restore();
    }

    function drawTrack() {
        ctx.save();

        // Fill background (grass)
        ctx.fillStyle = '#2e8b57';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Draw outer track (road)
        drawRoundedRect(
            ctx,
            track.x,
            track.y,
            track.width,
            track.height,
            track.cornerRadius,
            '#555'
        );

        // Draw inner track (grass)
        const innerRadius = Math.max(10, track.cornerRadius - track.innerMargin);
        drawRoundedRect(
            ctx,
            track.x + track.innerMargin,
            track.y + track.innerMargin,
            track.width - 2 * track.innerMargin,
            track.height - 2 * track.innerMargin,
            innerRadius,
            '#2e8b57'
        );


        checkpoints.forEach(cp => {
            ctx.save();
            ctx.strokeStyle = cp.id === "left" ? 'white' : 'yellow';
            ctx.lineWidth = 2;

            if (cp.id === "top" || cp.id === "bottom") {
                // Draw vertical line through the center of the checkpoint rect
                ctx.beginPath();
                ctx.moveTo(cp.x + cp.width / 2, cp.y);
                ctx.lineTo(cp.x + cp.width / 2, cp.y + cp.height);
                ctx.stroke();
            } else {
                // Draw horizontal line through the center of the checkpoint rect
                ctx.beginPath();
                ctx.moveTo(cp.x, cp.y + cp.height / 2);
                ctx.lineTo(cp.x + cp.width, cp.y + cp.height / 2);
                ctx.stroke();
            }

            ctx.restore();
        });


        // Draw start/finish line
        const startLineX = track.x + track.width / 2;
        const startLineY1 = track.y + track.height - 10;
        const startLineY2 = track.y + track.height - track.innerMargin + 10;
    }

    function drawRoundedRect(ctx, x, y, width, height, radius, color) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + width - radius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        ctx.lineTo(x + width, y + height - radius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        ctx.lineTo(x + radius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
    }

    function gameLoop() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        drawTrack();

        const input = {
            up: keys['w'],
            down: keys['s'],
            left: keys['a'],
            right: keys['d']
        };

        drawPlayer(players[0], playerStates[players[0]]);
        drawPlayer(players[1], playerStates[players[1]]);

        requestAnimationFrame(gameLoop);
    }

    // Wait for both images to load before starting
    let imagesLoaded = 0;

    function checkReady() {
        imagesLoaded++;
        if (imagesLoaded === 2) {
            gameLoop();

            // Start sending local input to server after game starts
            inputIntervalId = setInterval(() => {
                const input = {
                    up: keys['w'],
                    down: keys['s'],
                    left: keys['a'],
                    right: keys['d']
                };

                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'remoteInput',
                        input: input,
                        raceId: currentRaceId,
                        userName
                    }));
                }
            }, 16); // ~60hz
        }
    }

    carImageRed.onload = checkReady;
    carImageBlue.onload = checkReady;
}

function stateUpdate(message) {
    Object.entries(message.playerStates).forEach(([userName, state]) => {
        playerStates[userName] = state;
    });

    // Update lap counter for current user
    const myState = message.playerStates[userName];
    if (myState) {
        const lapCounter = document.getElementById('lapCounter');
        lapCounter.textContent = `Lap: ${myState.lap + 1} / ${totalLaps}`;

        // Update checkpoint counter
        const checkpointCounter = document.getElementById('checkpointCounter');
        checkpointCounter.textContent = `Checkpoint: ${myState.nextCheckpointIndex} / ${checkpoints.length}`;

        // Update penalty info (show remaining frozen time if penalized)
        const penaltyInfo = document.getElementById('penaltyInfo');
        const now = Date.now();
        if (myState.frozenUntil && myState.frozenUntil > now) {
            const remaining = ((myState.frozenUntil - now) / 1000).toFixed(2);
            penaltyInfo.textContent = `Frozen: ${remaining}s`;
        } else {
            penaltyInfo.textContent = '';
        }
    }
}

function handlePlayerFinished(position, time) {
    clearInterval(raceTimerInterval); // Stop the timer

    document.getElementById("overlayBlur").classList.remove("hidden");
    document.getElementById('counters').classList.add('hidden');

    const positionText = position === 1 ? 'You won the race! 🏆' : 'You finished 2nd.\n';
    const timeText = `Time: ${time}s`;

    document.getElementById("playerFinishedText").innerHTML = `${positionText}<br>${timeText}`;
}

function handleRaceFinished(results) {
    const tableBody = document.getElementById("resultsTableBody");
    tableBody.innerHTML = "";
    results.forEach(({ userName, time, position }) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td class="border px-4 py-2">${position}</td>
            <td class="border px-4 py-2">${userName}</td>
            <td class="border px-4 py-2">${time}s</td>
        `;
        tableBody.appendChild(row);
    });

    setTimeout(() =>
        document.getElementById("resultsContent").classList.remove("hidden")
    , 1000);
}

// Play Again logic
const playAgainBtn = document.getElementById('playAgainBtn');

playAgainBtn.addEventListener('click', () => {
    document.getElementById("overlayBlur").classList.add("hidden");
    document.getElementById("resultsContent").classList.add("hidden");
    document.getElementById("gameScreen").classList.add("hidden");
    raceOptions.classList.remove('hidden');

    // Reset state if needed
    currentRaceId = null;
    isHost = false;
});
