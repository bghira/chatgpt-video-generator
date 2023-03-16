<?php

declare(strict_types=1);

/**
 * Class MeltProject
 */
class MeltProject {
	/**
	 * @var DOMDocument
	 */
	private $xml;

	/**
	 * @var DOMElement
	 */
	private $playlist;

	/**
	 * @var int
	 */
	private $imageIndex;

	/**
	 * MeltProject constructor.
	 */
	public function __construct() {
		$this->xml = new DOMDocument('1.0', 'utf-8');
		$this->xml->formatOutput = true;
		$this->initMelt();
		$this->imageIndex = 1;
	}

	/**
	 * Initialize MLT XML structure.
	 */
	private function initMelt(): void {
		$mlt = $this->xml->createElement('mlt');
		$this->xml->appendChild($mlt);

		$playlist = $this->xml->createElement('playlist');
		$playlist->setAttribute('id', 'image_playlist');
		$mlt->appendChild($playlist);

		$this->playlist = $playlist;

		$tractor = $this->xml->createElement('tractor');
		$tractor->setAttribute('id', 'tractor1');
		$mlt->appendChild($tractor);

		$multitrack = $this->xml->createElement('multitrack');
		$tractor->appendChild($multitrack);

		$track = $this->xml->createElement('track');
		$track->setAttribute('producer', 'image_playlist');
		$multitrack->appendChild($track);
	}

	/**
	 * Add an image to the MLT XML.
	 *
	 * @param string $path
	 * @param int $duration
	 * @param int $transitionDuration
	 */
	public function addImage(string $path, int $duration, int $transitionDuration): void {
		$producerId = 'image' . $this->imageIndex;
		$in = 0;
		$out = $duration - 1;

		$producer = $this->xml->createElement('producer');
		$producer->setAttribute('id', $producerId);
		$producer->setAttribute('in', (string)$in);
		$producer->setAttribute('out', (string)$out);
		$this->xml->documentElement->appendChild($producer);

		$resource = $this->xml->createElement('property', $path);
		$resource->setAttribute('name', 'resource');
		$producer->appendChild($resource);

		$length = $this->xml->createElement('property', (string)$duration);
		$length->setAttribute('name', 'length');
		$producer->appendChild($length);

		$entry = $this->xml->createElement('entry');
		$entry->setAttribute('producer', $producerId);
		$entry->setAttribute('in', (string)$in);
		$entry->setAttribute('out', (string)$out);
		$this->playlist->appendChild($entry);

		if ($this->imageIndex > 1) {
			$blank = $this->xml->createElement('blank');
			$blank->setAttribute('length', (string)$transitionDuration);
			$this->playlist->appendChild($blank);

			$transition = $this->xml->createElement('transition');
			$transition->setAttribute('in', (string)(($duration - $transitionDuration) * ($this->imageIndex - 1)));
			$transition->setAttribute('out', (string)($duration * $this->imageIndex - 1));
			$transition->setAttribute('a_track', '0');
			$transition->setAttribute('b_track', '0');
			$this->xml->getElementsByTagName('tractor')->item(0)->appendChild($transition);

			$luma = $this->xml->createElement('property', 'luma');
			$luma->setAttribute('name', 'mlt_service');
			$transition->appendChild($luma);
		}

		$this->imageIndex++;
	}

	/**
	 * Set the voiceover audio track.
	 *
	 * @param string $path
	 */
	public function setVoiceover(string $path): void {
		$producer = $this->xml->createElement('producer');
		$producer->setAttribute('id', 'voiceover');
		$this->xml->documentElement->appendChild($producer);

		$resource = $this->xml->createElement('property', $path);
		$resource->setAttribute('name', 'resource');
		$producer->appendChild($resource);

		$playlist = $this->xml->createElement('playlist');
		$playlist->setAttribute('id', 'voiceover_playlist');
		$this->xml->documentElement->appendChild($playlist);

		$entry = $this->xml->createElement('entry');
		$entry->setAttribute('producer', 'voiceover');
		$playlist->appendChild($entry);

		$tractor = $this->xml->getElementsByTagName('tractor')->item(0);
		$multitrack = $tractor->getElementsByTagName('multitrack')->item(0);

		$track = $this->xml->createElement('track');
		$track->setAttribute('producer', 'voiceover_playlist');
		$multitrack->appendChild($track);
	}

	/**
	 * Save the MLT project to a file.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function save(string $path): bool {
		return $this->xml->save($path) !== false;
	}
}
