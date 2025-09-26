const keys = {};

export function initializeGame() {
    console.log('init')
    const canvas = document.getElementById('raceCanvas');
    const ctx = canvas.getContext('2d');

    // const keys = {};
    document.addEventListener('keydown', (e) => keys[e.key.toLowerCase()] = true);
    document.addEventListener('keyup', (e) => keys[e.key.toLowerCase()] = false);

    const carImageRed = new Image();
    const carImageBlue = new Image();
    carImageRed.src = 'assets/formula_car_red.png';
    carImageBlue.src = 'assets/formula_car_blue.png';

    // Track dimensions - rectangular with rounded corners
    const track = {
        x: 50,  // Reduced from 100 to make track wider (more space on sides)
        y: 50,  // Reduced from 100 to make track wider (more space top/bottom)
        width: canvas.width - 100,  // Increased from canvas.width - 200
        height: canvas.height - 100,  // Increased from canvas.height - 200
        cornerRadius: 130,  // Increased from 80 for smoother corners
        innerMargin: 80
    };

    const players = [
        {
            x: track.x + 25,  // Placed near the left side (you can adjust as needed)
            y: track.y + track.height / 2,  // Centered vertically on the track
            angle: -Math.PI / 2,  // Facing right (towards the finish line)
            vx: 0,
            vy: 0,
            speed: 0,
            image: carImageRed
        },
        {
            x: track.x + 25 + 25,  // Placed to the right of the red car
            y: track.y + track.height / 2,  // Centered vertically on the track
            angle: -Math.PI / 2,  // Facing right
            vx: 0,
            vy: 0,
            speed: 0,
            image: carImageBlue
        }
    ];

    // Define checkpoints (top, right, bottom, left)
    const checkpoints = [
        { x: canvas.width / 2 - 30, y: track.y + 10, width: 10, height: 60, id: "top" },
        { x: track.width - 20, y: canvas.height / 2 - 30, width: 60, height: 10, id: "right" },
        { x: canvas.width / 2 - 30, y: track.y + track.height - 70, width: 10, height: 60, id: "bottom" },
        { x: track.x + 10, y: canvas.height / 2 - 30, width: 60, height: 10, id: "left" } // Finish line
    ];

    // Add checkpoint tracking and lap count
    players.forEach(p => {
        p.checkpointsPassed = new Set();
        p.laps = 0;
        p.frozenUntil = 0;
        p.penaltyMessage = '';
    });

    const carWidth = 40;
    const carHeight = 20;

    const maxSpeed = 4;
    const acceleration = 0.15;
    const friction = 0.04;
    const turnSpeed = 0.05;

    function updatePlayer(p, input) {
        if (Date.now() < p.frozenUntil) return;

        if (input.left)  p.angle -= turnSpeed;
        if (input.right) p.angle += turnSpeed;
        if (input.up)    p.speed += acceleration;
        if (input.down)  p.speed -= acceleration;

        p.speed = Math.max(-maxSpeed, Math.min(maxSpeed, p.speed));
        p.speed *= (1 - friction);

        p.vx = Math.cos(p.angle) * p.speed;
        p.vy = Math.sin(p.angle) * p.speed;

        const nextX = p.x + p.vx;
        const nextY = p.y + p.vy;

        const halfW = carWidth / 2;

        // Check if the next position is on track
        const isOnTrack = isPointOnTrack(nextX, nextY, halfW);

        if (isOnTrack) {
            p.x = nextX;
            p.y = nextY;
        } else {
            // Off-track penalty
            p.speed *= 0.85;
            p.x += p.vx * 0.5;
            p.y += p.vy * 0.5;
        }
    }

    function isPointOnTrack(x, y, margin) {
        // Outer rectangle dimensions
        const outerLeft = track.x;
        const outerRight = track.x + track.width;
        const outerTop = track.y;
        const outerBottom = track.y + track.height;

        // Inner rectangle dimensions (using smaller innerMargin)
        const innerLeft = track.x + track.innerMargin;
        const innerRight = track.x + track.width - track.innerMargin;
        const innerTop = track.y + track.innerMargin;
        const innerBottom = track.y + track.height - track.innerMargin;

        // Check if point is outside outer track
        if (!isInRoundedRect(x, y, outerLeft, outerTop, outerRight, outerBottom, track.cornerRadius)) {
            return false;
        }

        // Check if point is inside inner track (which would be off-track)
        if (isInRoundedRect(x, y, innerLeft, innerTop, innerRight, innerBottom, track.cornerRadius - track.innerMargin)) {
            return false;
        }

        return true;
    }

    function isInRoundedRect(x, y, left, top, right, bottom, radius) {
        // Check if point is in the center rectangle
        if (x >= left + radius && x <= right - radius &&
            y >= top + radius && y <= bottom - radius) {
            return true;
        }

        // Check if point is in the rounded corners
        // Top-left corner
        if (x < left + radius && y < top + radius) {
            const dx = x - (left + radius);
            const dy = y - (top + radius);
            return (dx * dx + dy * dy) <= (radius * radius);
        }

        // Top-right corner
        if (x > right - radius && y < top + radius) {
            const dx = x - (right - radius);
            const dy = y - (top + radius);
            return (dx * dx + dy * dy) <= (radius * radius);
        }

        // Bottom-left corner
        if (x < left + radius && y > bottom - radius) {
            const dx = x - (left + radius);
            const dy = y - (bottom - radius);
            return (dx * dx + dy * dy) <= (radius * radius);
        }

        // Bottom-right corner
        if (x > right - radius && y > bottom - radius) {
            const dx = x - (right - radius);
            const dy = y - (bottom - radius);
            return (dx * dx + dy * dy) <= (radius * radius);
        }

        // Check if point is in the side rectangles (not in corners)
        if (x >= left && x <= right && y >= top && y <= bottom) {
            return true;
        }

        return false;
    }

    function drawPlayer(p) {
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.angle);
        ctx.drawImage(p.image, -carWidth / 2, -carHeight / 2, carWidth, carHeight);
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

        // Draw inner track (grass) - ensuring positive radius
        const innerRadius = Math.max(10, track.cornerRadius - track.innerMargin); // Never goes below 10
        drawRoundedRect(
            ctx,
            track.x + track.innerMargin,
            track.y + track.innerMargin,
            track.width - 2 * track.innerMargin,
            track.height - 2 * track.innerMargin,
            innerRadius,  // Now guaranteed to be positive
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

    function checkCollision(car1, car2) {
        return !(
            car1.x + carWidth / 2 < car2.x - carWidth / 2 ||
            car1.x - carWidth / 2 > car2.x + carWidth / 2 ||
            car1.y + carHeight / 2 < car2.y - carHeight / 2 ||
            car1.y - carHeight / 2 > car2.y + carHeight / 2
        );
    }

    function normalizeAngle(angle) {
        return ((angle + Math.PI) % (2 * Math.PI)) - Math.PI;
    }
    function detectRearCollision(attacker, target) {
        const dx = target.x - attacker.x;
        const dy = target.y - attacker.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist === 0) return false;

        const dirToTarget = Math.atan2(dy, dx);
        const attackerToTargetAngle = normalizeAngle(dirToTarget - attacker.angle);
        const targetForwardAngle = normalizeAngle(dirToTarget - target.angle);

        // Rear collision = attacker is moving toward the target from behind
        const attackerMovingToward = Math.abs(attackerToTargetAngle) < Math.PI / 2;
        const isBehindTarget = Math.abs(targetForwardAngle) < Math.PI / 3;

        return attackerMovingToward && isBehindTarget;
    }

    function gameLoop() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawTrack();

        const localPlayer = isHost ? players[0] : players[1];
        const remotePlayer = isHost ? players[1] : players[0];

        const input = {
            up: keys['w'],
            down: keys['s'],
            left: keys['a'],
            right: keys['d']
        };

        updatePlayer(localPlayer, input);
        updatePlayer(remotePlayer, remoteInput);

        if (checkCollision(players[0], players[1])) {
            players[0].speed = 0;
            players[1].speed = 0;

            const dx = players[1].x - players[0].x;
            const dy = players[1].y - players[0].y;
            const pushX = dx * 0.05;
            const pushY = dy * 0.05;

            players[0].x -= pushX;
            players[0].y -= pushY;
            players[1].x += pushX;
            players[1].y += pushY;

            // Log rear collision
            if (detectRearCollision(players[0], players[1])) {
                players[0].frozenUntil = Date.now() + 2000;
                players[0].penaltyMessage = 'Player 1 freeze for 2 seconds: rear collision!';
                console.log("Player 1 hit Player 2 from behind!");
            } else if (detectRearCollision(players[1], players[0])) {
                players[1].frozenUntil = Date.now() + 2000;
                players[1].penaltyMessage = 'Player 2 freeze for 2 seconds: rear collision!';
                console.log("Player 2 hit Player 1 from behind!");
            }
        }

        players.forEach(p => {
            const carLeft = p.x - carWidth / 2;
            const carRight = p.x + carWidth / 2;
            const carTop = p.y - carHeight / 2;
            const carBottom = p.y + carHeight / 2;

            checkpoints.forEach(cp => {
                if (
                    carRight > cp.x &&
                    carLeft < cp.x + cp.width &&
                    carBottom > cp.y &&
                    carTop < cp.y + cp.height
                ) {
                    if (cp.id !== "left") {
                        if (!p.checkpointsPassed.has(cp.id)) {
                            console.log(`Player ${p === players[0] ? 1 : 2} hit ${cp.id}`);
                        }
                        p.checkpointsPassed.add(cp.id);
                    } else {
                        if (p.checkpointsPassed.size === 3) {
                            p.laps += 1;
                            p.checkpointsPassed.clear();
                            console.log(`${p === players[0] ? "Player 1" : "Player 2"} completed lap ${p.laps}`);
                        }
                    }
                }
            });

            if (Date.now() < p.frozenUntil) {
                ctx.save();
                ctx.fillStyle = 'red';
                ctx.font = '24px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(p.penaltyMessage, canvas.width / 2, canvas.height / 2 + 80);
                ctx.restore();
            }
        });

        drawPlayer(players[0]);
        drawPlayer(players[1]);

        // Draw lap counter
        ctx.save();
        ctx.fillStyle = 'white';
        ctx.font = '30px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(`Player 1: ${players[0].laps} Laps`, canvas.width / 2, canvas.height / 2 - 20);
        ctx.fillText(`Player 2: ${players[1].laps} Laps`, canvas.width / 2, canvas.height / 2 + 20);
        ctx.restore();

        requestAnimationFrame(gameLoop);
    }

    // Wait for both images to load before starting
    let imagesLoaded = 0;

    function checkReady() {
        imagesLoaded++;
        if (imagesLoaded === 2) {
            gameLoop();

            // Start sending local input to server after game starts
            setInterval(() => {
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
            }, 16); // every 50ms
        }
    }

    carImageRed.onload = checkReady;
    carImageBlue.onload = checkReady;
}


// module.exports = { initializeGame };