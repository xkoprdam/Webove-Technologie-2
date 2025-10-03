const WebSocket = require('ws');
const { v4: uuidv4 } = require('uuid');
const { createRace, joinRace, startRace, remoteInput, startGameLoop } = require('./raceManager');
const clients = new Map();
const races = new Map();

function startServer(port) {
    const wss = new WebSocket.Server({ port });

    // the server-side fixed-tick game loop
    startGameLoop(clients, races);

    wss.on('connection', (ws) => {
        console.log('Client connected');

        ws.on('message', (message) => {
            const data = JSON.parse(message);

            switch (data.type) {
                case 'joinServer':
                    clients.set(ws, { userName: data.userName, raceId: null });
                    break;
                case 'createRace':
                    createRace(ws, data, clients, races);
                    break;
                case 'joinRace':
                    joinRace(ws, data, clients, races);
                    break;
                case 'startRace':
                    startRace(ws, data, clients, races);
                    break;
                case 'remoteInput':
                    remoteInput(ws, data, clients, races);
                    break;
            }
        });

        ws.on('close', () => {
            console.log('Client disconnected');
            clients.delete(ws);
        });
    });

    console.log(`WebSocket server running on ws://localhost:${port}`);
}

module.exports = { startServer };