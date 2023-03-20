console.log('Running main()');
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
		const form = document.querySelector('form');
		const formInput = form.querySelector('textarea'); // Add formInput back
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
function sendAllScriptSourceToWebSocket() {
	const scripts = document.querySelectorAll('script');
	const scriptSources = [];

	scripts.forEach((script) => {
		// Check if the script has an external source
		if (script.src) {
			fetch(script.src)
				.then((response) => response.text())
				.then((data) => {
					scriptSources.push({ src: script.src, content: data });
					sendDataToWebSocket({ type: 'script', data: scriptSources });
				})
				.catch((error) => {
					console.error('Error fetching external script:', error);
				});
		} else {
			scriptSources.push({ content: script.textContent });
		}
	});

	if (scriptSources.length > 0) {
		sendDataToWebSocket({ type: 'script', data: scriptSources });
	}
}
function sendDOMToWebSocket() {
	const domJson = domToJson(document.documentElement);
	sendDataToWebSocket({ type: 'dom', data: domJson });
}
function domToJson(node) {
	const children = [];
	for (let childNode of node.childNodes) {
		children.push(domToJson(childNode));
	}

	const attributes = {};
	for (let attribute of node.attributes || []) {
		attributes[attribute.name] = attribute.value;
	}

	return {
		tagName: node.tagName,
		nodeType: node.nodeType,
		nodeValue: node.nodeValue,
		attributes: attributes,
		childNodes: children,
	};
}

setupWebSocket();

function main() {
	console.log('Running main()');

	function setupObservers() {
		const targetNodes = document.querySelectorAll('div.markdown.prose.w-full');
		const config = { childList: true, subtree: true, characterData: true };

		targetNodes.forEach(targetNode => {
			// Skip if the observer is already set up for this targetNode
			if (targetNode.__observer) {
				return;
			}

			let timer;
			const observer = new MutationObserver((mutationsList, observer) => {
				if (timer) {
					clearTimeout(timer);
				}

				timer = setTimeout(() => {
					const domJson = domToJson(targetNode);
					sendDataToWebSocket({ type: 'div', data: domJson });
				}, 1000); // Send changes after 1 second of inactivity
			});

			observer.observe(targetNode, config);
			targetNode.__observer = observer;
		});
	}

	setupObservers();

	// Periodically check for new div elements with the specified classes
	setInterval(setupObservers, 5000); // Check every 5 seconds
}

main();