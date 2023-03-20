<?php

/**
 * DOMExtractor - Pull DOM elements from ChatGPT DOM, and reassemble into Markdown.
 *
 * Usage example
 * $jsonData = json_decode('{"input": {"data": {...}}');
 * $logger = new SimpleLogger();
 * $domExtractor = new DOMExtractor($jsonData, $logger);
 * $output = $domExtractor->extract();
 * echo $output;
 */
use Monolog\Logger;

class DOMExtractor {
	/**
	 * The JSON data to extract DOM elements from.
	 *
	 * @var stdClass
	 */
	private stdClass $jsonData;

	/**
	 * The output Markdown string.
	 *
	 * @var string
	 */
	private string $output;

	/**
	 * A blacklist of phrases to exclude from the output.
	 *
	 * @var DOMBlacklist
	 */
	private DOMBlacklist $blacklist;

	/**
	 * The logger to use for outputting log messages.
	 *
	 * @var Monolog\Logger
	 */
	private Logger $log;

	/**
	 * Creates a new instance of the DOMExtractor class.
	 *
	 * @param stdClass $jsonData The JSON data to extract DOM elements from.
	 * @param Logger $log The logger to use for outputting log messages.
	 */
	public function __construct(stdClass $jsonData, Logger $log) {
		$this->jsonData = $jsonData;
		$this->log = $log;
		$this->output = '';
		$this->blacklist = new DOMBlacklist(['Copy code']);
        $this->log->info('Initialised DOMExtractor');
	}

	/**
	 * Extracts text and code blocks from the JSON data.
	 *
	 * @return string Returns the extracted text and code blocks as a Markdown-formatted string.
	 */
	public function extract(): string {
		$this->traverseNodes($this->jsonData->input->data);

		return $this->output;
	}

	/**
	 * Traverses the given DOM node and extracts any text or code blocks found within it.
	 *
	 * @param stdClass $node The DOM node to traverse.
	 *
	 * @return void
	 */
	private function traverseNodes(stdClass $node): void {
		if ($node->nodeType == 3) { // Check if the node is a text node
			if (!$this->blacklist->isBlacklisted($node->nodeValue)) {
				$this->output .= $node->nodeValue;
			}
		} elseif (isset($node->tagName)) {
			$tagName = $node->tagName;
			if ($tagName == 'PRE') {
				$this->handlePreTag($node);
			} else {
				$this->handleNonPreTag($node);
			}
		}
	}

	/**
	 * Handles a <pre> tag found in the DOM.
	 *
	 * @param stdClass $node The <pre> tag to handle.
	 *
	 * @return void
	 */
	private function handlePreTag(stdClass $node): void {
		$language = $this->getLanguage($node);
		if ($language != '') {
			$this->output .= "\n```" . $language . "\n";
		} else {
			$this->output .= "\n```\n";
		}
		foreach ($node->childNodes as $childNode) {
			$this->traverseNodes($childNode);
		}
		$this->output .= $language ? "```\n" : "\n```\n";
	}

	/**
	 * Handles a non-<pre> tag found in the DOM.
	 *
	 *
	 * @param stdClass $node The non-<pre> tag to handle.
     *
     * @return void
     */
	private function handleNonPreTag(stdClass $node): void {
		if (isset($node->childNodes) && count($node->childNodes) > 0) {
			foreach ($node->childNodes as $childNode) {
				$this->traverseNodes($childNode);
			}
		}
	}

	/**
	 * Gets the language for a <pre> tag found in the DOM, if any.
	 *
	 * @param stdClass $node The <pre> tag to get the language for.
	 *
	 * @return string Returns the language for the <pre> tag, or an empty string if none is found.
	 */
	private function getLanguage(stdClass $node): string {
		if (isset($node->childNodes) && count($node->childNodes) > 0) {
			foreach ($node->childNodes as $childNode) {
				if ($childNode->nodeType == 1 && $childNode->tagName == 'DIV') {
					if (isset($childNode->attributes->class) && strpos($childNode->attributes->class, 'flex items-center') !== false) {
						foreach ($childNode->childNodes as $spanNode) {
							if ($spanNode->nodeType == 1 && $spanNode->tagName == 'SPAN') {
								return $spanNode->nodeValue;
							}
						}
					}
				}
			}
		}

		return '';
	}
}
