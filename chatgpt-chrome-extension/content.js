function main() {
  console.log('Running DOM ready routines.');
  const websocketURL = 'ws://localhost:9090'; // Replace with your WebSocket server URL
  let websocket;

  function setupWebSocket() {
    websocket = new WebSocket(websocketURL);

    websocket.onopen = (event) => {
      console.log('WebSocket connection established:', event);
    };

    websocket.onmessage = (event) => {
      console.log('Websocket msg', event);
      const outputDiv = document.querySelector('textarea');
      outputDiv.value = event.data;
      const regenerateButton = formInput.nextElementSibling;
      if (regenerateButton.isConnected) {
        regenerateButton.click();
      } else {
        console.warn('The regenerate button is not connected to the DOM');
      }
    };

    websocket.addEventListener("error", (event) => {
      console.log("WebSocket error:", event);
    });

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

  const form = document.querySelector('form');
  const outputDiv = form.parentElement.querySelector(':scope > div');
    formInput.addEventListener('change', (event) => {
    sendDataToWebSocket(outputDiv.textContent);
  });
}

const observer = new MutationObserver((mutationsList, observer) => {
  main();
  observer.disconnect(); // Disconnect the observer once the main function has run
});

observer.observe(document.body, {
  childList: true,
  subtree: true,
});

main();
