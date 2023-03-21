<?php
/**
 * Represents a blacklist of phrases to exclude from a DOM tree.
 * The utility of this at present is to remove the "Copy code" string from
 * resulting ChatGPT output when it includes a code snippet.
 */

 class DOMBlacklist {
	/**
	 * The list of phrases to exclude when printing a DOM tree as a string.
	 *
	 * @var array
	 */
	private array $blacklist;

	/**
	 * Creates a new instance of the DOMBlacklist class.
	 *
	 * @param array $blacklist An array of strings representing phrases to exclude.
	 */
	public function __construct(array $blacklist) {
		$this->blacklist = $blacklist;
	}

	/**
	 * Checks if a given string is blacklisted.
	 *
	 * @param ?string $text The string to check.
	 *
	 * @return bool Returns true if the string contains a blacklisted phrase, false otherwise.
	 */
	public function isBlacklisted(?string $text): bool {
		foreach ($this->blacklist as $phrase) {
			if (!is_null($phrase) && strpos($text, $phrase) !== false) {
				return true;
			}
		}

		return false;
	}
}
