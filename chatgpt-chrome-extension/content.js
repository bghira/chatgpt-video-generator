const websocketURL = 'wss://localhost:9091'; // Replace with your WebSocket server URL
let websocket;

function setupWebSocket() {
  websocket = new WebSocket(websocketURL);

  websocket.onopen = (event) => {
    console.log('WebSocket connection established:', event);
  };

  websocket.onmessage = (event) => {
    const response = JSON.parse(event.data);
    const outputDiv = document.querySelector('textarea');
    outputDiv.value = response.data;
  };

  websocket.onerror = (event) => {
    console.error('WebSocket error:', event);
  };

  websocket.onclose = (event) => {
    console.log('WebSocket connection closed:', event);
    setTimeout(setupWebSocket, 5000); // Attempt to reconnect after 5 seconds
  };
}

function sendDataToWebSocket(inputData) {
  if (websocket.readyState === WebSocket.OPEN) {
    websocket.send(JSON.stringify({ input: inputData }));
  } else {
    console.error('WebSocket connection is not open');
  }
}

setupWebSocket();

const formInput = document.querySelector('textarea[data-id="fea0524b-76c5-426f-beb4-449f1b916e69"]');
formInput.addEventListener('change', (event) => {
  sendDataToWebSocket(event.target.value);
});

const regenerateButton = document.querySelector('button.btn');
regenerateButton.addEventListener('click', () => {
  sendDataToWebSocket(formInput.value);
});