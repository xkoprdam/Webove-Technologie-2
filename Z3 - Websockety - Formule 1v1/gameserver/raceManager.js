const { v4: uuidv4 } = require('uuid');
const WebSocket = require('ws');

const carWidth = 20;
const carHeight = 40;

const track = {
    x: 50,
    y: 50,
    width: 800,
    height: 600,
    cornerRadius: 130,
    innerMargin: 80
};

const checkpoints = [
    { x: 370, y: 60, width: 10, height: 60, id: "top" },
    { x: 780, y: 270, width: 60, height: 10, id: "right" },
    { x: 370, y: 580, width: 10, height: 60, id: "bottom" },
    { x: 60, y: 270, width: 60, height: 10, id: "left" } // Finish line
];


const trackData = {
    carWidth,
    carHeight,
    track,
    checkpoints
};

function createRace(ws, data, clients, races) {
    const raceId = uuidv4().slice(0, 6);

    const playerStates = {};

    // For host (first player)
    playerStates[data.userName] = {
        position: { x: track.x + 20, y: track.y + track.height / 2 },
        velocity: { x: 0, y: 0 },
        angle: -Math.PI / 2,
        input: { up: false, down: false, left: false, right: false },
        lap: 0,
        finished: false,
        justCrossedFinish: false,
        frozenUntil: 0,
        nextCheckpointIndex: 0
    };

    races.set(raceId, {
        host: ws,
        players: [data.userName],
        laps: data.laps,
        playerStates,
        track,
        checkpoints
    });

    clients.get(ws).raceId = raceId;

    ws.send(JSON.stringify({ type: 'raceCreated', raceId }));
    console.log(`Race created: ${raceId}`);
}

function joinRace(ws, data, clients, races) {
    const race = races.get(data.raceId);

    if (!race) {
        ws.send(JSON.stringify({ type: 'error', message: 'Race not found.' }));
        return;
    }

    if (race.players.length >= 2) {
        ws.send(JSON.stringify({ type: 'error', message: 'Race is full.' }));
        return;
    }

    race.players.push(data.userName);
    clients.set(ws, { raceId: data.raceId, userName: data.userName });


    race.playerStates[data.userName] = {
        position: { x: track.x + 55, y: track.y + track.height / 2 },
        velocity: { x: 0, y: 0 },
        angle: -Math.PI / 2,
        input: { up: false, down: false, left: false, right: false },
        lap: 0,
        finished: false,
        justCrossedFinish: false,
        frozenUntil: 0,
        nextCheckpointIndex: 0
    };

    const players = race.players;

    // Notify everyone in this race
    for (const [client, clientData] of clients.entries()) {
        if (clientData.raceId === data.raceId && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify({
                type: 'playerJoined',
                players,
                raceId: data.raceId
            }));
        }
    }

    console.log(`${data.userName} joined race ${data.raceId}`);
}

function startRace(ws, data, clients, races) {
    console.log(`Starting race ${data.raceId}`);
    const race = races.get(data.raceId);

    if (race) {
        race.startTime = Date.now();

        for (const [client, clientData] of clients.entries()) {
            if (clientData.raceId === data.raceId && client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify({
                    type: 'raceStarted',
                    players: [...race.players],
                    playerStates: race.playerStates,
                    raceId: data.raceId,
                    hostUsername: race.host.userName,
                    startTime: race.startTime,
                    laps: race.laps,
                    trackData
                }));
            }
        }
    }
}

function remoteInput(ws, data, clients, races) {
    const race = races.get(data.raceId);
    if (!race) return;

    const playerState = race.playerStates[data.userName];
    if (!playerState) return;

    const now = Date.now();
    if (playerState.finished && now - (playerState.finishedAt || 0) > 1000) {
        // Ignore input if finished more than 1s ago
        return;
    }

    playerState.input = data.input;
}

function updatePlayerPhysics(player) {

    // Skip movemeznt if frozen
    const now = Date.now();
    if (player.frozenUntil && now < player.frozenUntil) {
        return;
    }

    const onTrack = isOnTrack(player.position, track);

    const speed = onTrack ? 0.2 : 0.1;         // Half speed off track
    const maxSpeed = onTrack ? 4 : 2;          // Lower max speed off track
    const friction = onTrack ? 0.05 : 0.1;     // More friction off track
    const rotationSpeed = 0.04;

    if (player.input.left) player.angle -= rotationSpeed;
    if (player.input.right) player.angle += rotationSpeed;

    if (player.input.up) {
        player.velocity.x += Math.cos(player.angle) * speed;
        player.velocity.y += Math.sin(player.angle) * speed;
    }

    player.velocity.x *= (1 - friction);
    player.velocity.y *= (1 - friction);

    const velocityMag = Math.hypot(player.velocity.x, player.velocity.y);
    if (velocityMag > maxSpeed) {
        const scale = maxSpeed / velocityMag;
        player.velocity.x *= scale;
        player.velocity.y *= scale;
    }

    player.position.x += player.velocity.x;
    player.position.y += player.velocity.y;

    // Clamp car to canvas boundaries
    const halfCarWidth = carWidth / 2;
    const halfCarHeight = carHeight / 2;

    player.position.x = Math.max(halfCarWidth, Math.min(900 - halfCarWidth, player.position.x));
    player.position.y = Math.max(halfCarHeight, Math.min(700 - halfCarHeight, player.position.y));

}

function handleGameLogic(currentUserName, playerState, race, clients) {

    const checkpoints = race.checkpoints;
    const expectedCheckpoint = checkpoints[playerState.nextCheckpointIndex];

    if (isInsideCheckpoint(playerState.position, expectedCheckpoint)) {
        console.log(`${currentUserName} hit checkpoint ${expectedCheckpoint.id}`);

        playerState.nextCheckpointIndex++;

        if (playerState.nextCheckpointIndex >= checkpoints.length) {
            playerState.lap++;
            playerState.nextCheckpointIndex = 0;

            console.log(`${currentUserName} completed lap ${playerState.lap}`);

            if (playerState.lap >= race.laps && !playerState.finished) {
                playerState.finished = true;
                console.log(`${currentUserName} has finished the race!`);
                markPlayerFinished(currentUserName, race, clients);
            }
        }
    }

    // Collision logic
    for (const [otherUserName, otherPlayerState] of Object.entries(race.playerStates)) {
        if (otherUserName === currentUserName || otherPlayerState.finished) continue;

        if (checkCollision(playerState, otherPlayerState)) {
            if (detectRearCollision(playerState, otherPlayerState)) {
                // Rear-end collision by `player` into `otherPlayer`
                console.log(`${currentUserName} rear-ended ${otherUserName}`);
                playerState.velocity.x = 0;
                playerState.velocity.y = 0;
                playerState.frozenUntil = Date.now() + 1000;
            }
        }
    }
}

function isOnTrack(pos, track) {
    const outerLeft = track.x;
    const outerRight = track.x + track.width;
    const outerTop = track.y;
    const outerBottom = track.y + track.height;

    const innerLeft = track.x + track.innerMargin;
    const innerRight = track.x + track.width - track.innerMargin;
    const innerTop = track.y + track.innerMargin;
    const innerBottom = track.y + track.height - track.innerMargin;

    const inOuterBounds = (
        pos.x >= outerLeft &&
        pos.x <= outerRight &&
        pos.y >= outerTop &&
        pos.y <= outerBottom
    );

    const inInnerBounds = (
        pos.x >= innerLeft &&
        pos.x <= innerRight &&
        pos.y >= innerTop &&
        pos.y <= innerBottom
    );

    return inOuterBounds && !inInnerBounds;
}

function isInsideCheckpoint(pos, cp) {
    return (
        pos.x >= cp.x &&
        pos.x <= cp.x + cp.width &&
        pos.y >= cp.y &&
        pos.y <= cp.y + cp.height
    );
}

function checkCollision(car1, car2) {
    return !(
        car1.position.x + carWidth / 2 < car2.position.x - carWidth / 2 ||
        car1.position.x - carWidth / 2 > car2.position.x + carWidth / 2 ||
        car1.position.y + carHeight / 2 < car2.position.y - carHeight / 2 ||
        car1.position.y - carHeight / 2 > car2.position.y + carHeight / 2
    );
}

function normalizeAngle(angle) {
    return ((angle + Math.PI) % (2 * Math.PI)) - Math.PI;
}

function detectRearCollision(attacker, target) {
    const dx = target.position.x - attacker.position.x;
    const dy = target.position.y - attacker.position.y;
    const dist = Math.sqrt(dx * dx + dy * dy);
    if (dist === 0) return false;

    const dirToTarget = Math.atan2(dy, dx);
    const attackerToTargetAngle = normalizeAngle(dirToTarget - attacker.angle);
    const targetForwardAngle = normalizeAngle(dirToTarget - target.angle);

    const attackerMovingToward = Math.abs(attackerToTargetAngle) < Math.PI / 2;
    const isBehindTarget = Math.abs(targetForwardAngle) < Math.PI / 3;

    return attackerMovingToward && isBehindTarget;
}

function markPlayerFinished(currentUserName, race, clients) {
    if (!race.finishedPlayers) race.finishedPlayers = [];

    if (race.finishedPlayers.includes(currentUserName)) return;

    race.finishedPlayers.push(currentUserName);

    const playerState = race.playerStates[currentUserName];
    playerState.finishedAt = Date.now();

    const timeElapsed = ((playerState.finishedAt - race.startTime) / 1000).toFixed(2); // in seconds

    // Send to the player who just finished
    for (const [client, clientData] of clients.entries()) {
        if (clientData.userName === currentUserName && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify({
                type: 'playerFinished',
                position: race.finishedPlayers.length,
                time: timeElapsed
            }));
        }
    }

    // Notify all players when the race ends
    if (race.finishedPlayers.length === race.players.length) {
        for (const [client, clientData] of clients.entries()) {
            if (client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify({
                    type: 'raceFinished',
                    raceId: race.raceId,
                    results: race.finishedPlayers.map((userName, index) => {
                        const p = race.playerStates[userName];
                        return {
                            userName,
                            time: ((p.finishedAt - race.startTime) / 1000).toFixed(2),
                            position: index + 1
                        };
                    })
                }));
            }
        }
    }
}

/**
 * Start the server-side fixed-tick game loop.
 * @param {Map} clients - Map of WebSocket clients to metadata.
 * @param {Map} races - Map of raceId to race objects.
 * @param {number} [tickRate=60] - Ticks per second.
 */
function startGameLoop(clients, races, tickRate = 60) {
    const intervalMs = 1000 / tickRate;
    setInterval(() => {
        for (const [raceId, race] of races.entries()) {
            for (const userName of race.players) {
                const playerState = race.playerStates[userName];
                updatePlayerPhysics(playerState);
                handleGameLogic(userName, playerState, race, clients);
            }
            // Broadcast updated state to all clients in this race
            for (const [clientWs, clientData] of clients.entries()) {
                if (clientData.raceId === raceId && clientWs.readyState === WebSocket.OPEN) {
                    clientWs.send(JSON.stringify({
                        type: 'stateUpdate',
                        raceId,
                        playerStates: race.playerStates
                    }));
                }
            }
        }
    }, intervalMs);
}

module.exports = { createRace, joinRace, startRace, remoteInput, startGameLoop };