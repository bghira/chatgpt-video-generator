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
	 * The currently-detected language, of the codeblock being rendered.
	 *
	 * @var string
	 */
	private string $language = '';

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
	 * Whether we've added a detected codeblock's language to the output yet.
	 *
	 * @var boolean
	 */
	private bool $languageAdded = false;
	/**
	 * Whether we've added a detected codeblock.
	 *
	 * @var boolean
	 */
	private bool $insideCodeBlock = false;
	/**
	 * Whether we've reached a CODE tag.
	 *
	 * @var string
	 */
	private string $insideCodeTag = '';

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
		$this->log->info('Running first invocation of traverseNodes.. You are in the matrix. this->insideCodeBlock should be FALSE.');
		$this->traverseNodes($this->jsonData->input->data);

		return $this->output;
	}

	/**
	 * Traverses the given DOM node and extracts any text or code blocks found within it.
	 *
	 * @param stdClass $node The DOM node to traverse.
	 * @return void
	 */
	private function traverseNodes(stdClass $node): void {
		$this->log->info('You are in the matrix now for realsies. inCodeBlock: ' . var_export($this->insideCodeBlock, true) . ' nodeType ' . $node->nodeType);
		$this->insideCodeTag = $this->isUnlabeledCodeTag($node);
		if ($node->nodeType == 3 || $this->insideCodeTag) { // Check if the node is a text node or a CODE tag
			if (!$this->blacklist->isBlacklisted($node->nodeValue)) {
				if (!($this->insideCodeBlock && $this->languageAdded && $node->nodeValue === $this->language) && !$this->insideCodeTag) {
						$this->log->info('Adding ' . $node->nodeValue);
						$this->output .= $node->nodeValue;
				} elseif ($this->insideCodeBlock && !$this->languageAdded) {
					$this->log->info('We ARE inside code block. And we have not printed the language yet.');
				} elseif ($this->insideCodeBlock && $this->languageAdded && $node->nodeValue !== $this->language) {
					$this->log->info('We ARE inside code block. And we are not detecting the language.');
				} elseif ($this->insideCodeTag && $this->language == '') {
					$this->log->info('Adding single backticks: `' . $this->insideCodeTag . '`');
					$this->output .= '`' . $this->insideCodeTag . '`';
					$this->insideCodeTag = '';
				}
			}
		} elseif (isset($node->tagName)) {
			$tagName = $node->tagName;
			if ($tagName == 'PRE') {
				$this->handlePreTag($node);
			} else {
				$this->handleNonPreTag($node);
			}
		}
		$this->log->info('insideCodeblock IS: ' . var_export($this->insideCodeBlock, true) . ', insideCodeTag: ' . $this->insideCodeTag . ', languageAdded:  '. var_export($this->languageAdded, true) . ' and node value is, ' . $node->nodeValue);
		$this->output .= PHP_EOL;
	}
	private function isUnlabeledCodeTag(stdClass $node) : string {
		$codeblock = '';
		if (isset($node->tagName) && $node->tagName === 'CODE') {
			if (count($node->childNodes) === 1) {
				$lang = $this->getLanguage($node);
				if ($lang === '') {
					$codeblock = $node->childNodes[0]->nodeValue;
					$this->log->info('Found unlabeled code block, ' . $codeblock);
				}
			}
		}
		return $codeblock;
	}
	private function handlePreTag(stdClass $node): void {
		$this->insideCodeBlock = true;
		$this->language = $this->getLanguage($node);
		$this->log->info('We found a language in the preTag code block?');
		if ($this->language != '') {
			$this->log->info('Adding language to output where we are supposed to');
			$this->output .= "\n```" . $this->language . "\n";
			$this->languageAdded = true;
		} else {
			$this->output .= "```\n";
		}
		foreach ($node->childNodes as $childNode) {
			$this->log->info('Beginning traversal into childnode while in code block and this->insideCodeBlock should be TRUE.');
			$this->traverseNodes($childNode);
		}
		$this->output .= "```\n";
		$this->insideCodeBlock = false;
		$this->languageAdded = false;
		$this->language = '';
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
				$this->log->info(__FUNCTION__ . ' is entering the matrix again, and this->insideCodeBlock is unchanged.');
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
		$encoded = json_encode($node);
		$node_array = json_decode($encoded, true);
		$all_attributes = $this->array_value_recursive('class', $node_array);
		$pattern = '/(language-\w+)/';
		array_map(function ($string) use ($pattern) {
			if (preg_match($pattern, $string, $matches)) {
				// Remove the prefix, "language-"
				$this->language = str_replace('language-', '', $matches[1]);
			}
		}, (array) $all_attributes);

		return $this->language;
	}
	/**
	 * Get all values from specific key in a multidimensional array
	 *
	 * @param $key string
	 * @param $arr array
	 * @return null|string|array
	 */
	private function array_value_recursive($key, array $arr) {
		$val = [];
		array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
			if ($k == $key) {
				array_push($val, $v);
			}
		});

		return count($val) > 1 ? $val : array_pop($val);
	}
}
