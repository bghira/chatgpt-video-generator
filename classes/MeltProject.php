<?php

use Monolog\Logger;

class MeltProject
{
    private int $width;
    private int $height;
    private int $frameRateNum;
    private int $frameRateDen;
    private ?string $audioFile = null;
    private string $outputFile;
    private array $images;
	private Logger $log;

    public function __construct(Logger $log, int $width = 1920, int $height = 1080, int $frameRateNum = 25, int $frameRateDen = 1, string $outputFile = './scene.mp4')
    {
		$this->log = $log;
		$this->width = $width;
        $this->height = $height;
        $this->frameRateNum = $frameRateNum;
        $this->frameRateDen = $frameRateDen;
        $this->outputFile = $outputFile;
        $this->images = [];
		$this->log->info('Initialized MeltProject ' . $this);
    }

	public function __toString() {
		return 'width:'.$this->width.' height:'.$this->height . ' framerate:'. $this->frameRateNum . ' outputFile ' . $this->outputFile;
	}

    public function addImage(string $path, int $in, int $out): void
    {
        $this->images[] = [
            'path' => $path,
            'in' => $in,
            'out' => $out
        ];
		$this->log->info('Adding image', end($this->images));
    }
    /**
     * Set the voiceover audio track.
     *
     * @param string $path
     */
    public function setVoiceover(string $path): void
    {
        $this->audioFile = $path;
		$this->log->info('Adding audio track: ' . $path);
    }

    /**
     * Generate the XML document.
     *
     * @return DOMDocument The generated XML object.
     */
	public function generateXml(): DOMDocument
	{
		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->formatOutput = true;
	
		$mlt = $this->createMltElement($xml);
		$xml->appendChild($mlt);
	
		$profile = $this->createProfileElement($xml);
		$mlt->appendChild($profile);
	
		$count = 0;
		$playlist = $this->createPlaylistElement($xml);
		foreach ($this->images as $image) {
			$producer = $this->createProducerElement($xml, $image, $count);
			$mlt->appendChild($producer);
	
			$entry = $this->createEntryElement($xml, $image, $count);
			$playlist->appendChild($entry);
	
			$count++;
		}
		$mlt->appendChild($playlist);
	
		$tractor0 = $this->createTractorElement($xml, 'tractor0');
		$mlt->appendChild($tractor0);
	
		$multitrack0 = $this->createMultitrackElement($xml);
		$tractor0->appendChild($multitrack0);
	
		$imageTrack = $this->createImageTrackElement($xml);
		$multitrack0->appendChild($imageTrack);
	
		if ($this->audioFile !== null) {
			$voiceoverProducer = $this->createVoiceoverProducerElement($xml, $this->audioFile);
			$mlt->appendChild($voiceoverProducer);
	
			$voiceoverPlaylist = $this->createVoiceoverPlaylistElement($xml);
			$mlt->appendChild($voiceoverPlaylist);
	
			$voiceoverEntry = $this->createVoiceoverEntryElement($xml);
			$voiceoverPlaylist->appendChild($voiceoverEntry);
	
			$tractor1 = $this->createTractorElement($xml, 'tractor1');
			$mlt->appendChild($tractor1);
	
			$multitrack1 = $this->createMultitrackElement($xml);
			$tractor1->appendChild($multitrack1);
	
			$trackForTractor0 = $this->createTrackElement($xml, 'tractor0');
			$multitrack1->appendChild($trackForTractor0);
	
			$trackForVoiceoverPlaylist = $this->createTrackElement($xml, 'voiceover_playlist');
			$multitrack1->appendChild($trackForVoiceoverPlaylist);
		}
	
		return $xml;
	}

    private function createMltElement(DOMDocument $xml): DOMElement
    {
        $mlt = $xml->createElement('mlt');
        $mlt->setAttribute('LC_NUMERIC', 'C');
        $mlt->setAttribute('producer', 'main_bin');
        $mlt->setAttribute('version', '7.12.0');
        $mlt->setAttribute('root', '/home/kash');
        return $mlt;
    }

    private function createProfileElement(DOMDocument $xml): DOMElement
    {
        $profile = $xml->createElement('profile');
        $profile->setAttribute('description', 'HD 1080p 25 fps');
        $profile->setAttribute('width', $this->width);
        $profile->setAttribute('height', $this->height);
        $profile->setAttribute('progressive', '1');
        $profile->setAttribute('sample_aspect_num', '1');
        $profile->setAttribute('sample_aspect_den', '1');
        $profile->setAttribute('display_aspect_num', '16');
        $profile->setAttribute('display_aspect_den', '9');
        $profile->setAttribute('frame_rate_num', $this->frameRateNum);
        $profile->setAttribute('frame_rate_den', $this->frameRateDen);
        $profile->setAttribute('colorspace', '709');
        return $profile;
    }

    private function createPlaylistElement(DOMDocument $xml): DOMElement
    {
        $playlist = $xml->createElement('playlist');
        $playlist->setAttribute('id', 'playlist0');
        return $playlist;
    }

    private function createProducerElement(DOMDocument $xml, array $image, int $count): DOMElement
    {
        $producer = $xml->createElement('producer');
        $producer->setAttribute('id', 'producer' . $count);
        $producer->setAttribute('in', $image['in']);
        $producer->setAttribute('out', $image['out']);

        $resource = $xml->createElement('property', $image['path']);
        $resource->setAttribute('name', 'resource');
        $producer->appendChild($resource);

        $length = $xml->createElement('property', $image['out'] + 1);
        $length->setAttribute('name', 'length');
        $producer->appendChild($length);

        return $producer;
    }

    private function createEntryElement(DOMDocument $xml, array $image, int $count): DOMElement
    {
        $entry = $xml->createElement('entry');
        $entry->setAttribute('producer', 'producer' . $count);
        $entry->setAttribute('in', $image['in']);
        $entry->setAttribute('out', $image['out']);

        return $entry;
    }

	public function createTractorElement(DOMDocument $xml, string $id): DOMElement
	{
		$tractor = $xml->createElement('tractor');
		$tractor->setAttribute('id', $id);
	
		return $tractor;
	}
	

	public function createTrackElement(DOMDocument $xml, string $producerId): DOMElement
	{
		$track = $xml->createElement('track');
		$track->setAttribute('producer', $producerId);
	
		return $track;
	}
	

    private function createTransitionElement(DOMDocument $xml): DOMElement
    {
        $transition = $xml->createElement('transition');
        $transition->setAttribute('in', '0');
        $transition->setAttribute('out', $this->images[0]['out']);
        $transition->setAttribute('a_track', '0');
        $transition->setAttribute('b_track', '1');
        return $transition;
    }

    private function createTransitionProperties(): array
    {
        return [
            ['mlt_service', 'mix'],
            ['start', '0'],
            ['end', $this->images[0]['out']],
            ['a_track', '0'],
            ['b_track', '1'],
        ];
    }

    private function createPropertyElement(DOMDocument $xml, array $property): DOMElement
    {
        $prop = $xml->createElement('property', $property[1]);
        $prop->setAttribute('name', $property[0]);
        return $prop;
    }
    private function createVoiceoverProducerElement(DOMDocument $xml, string $path): DOMElement
    {
        $producer = $xml->createElement('producer');
        $producer->setAttribute('id', 'voiceover');

        $resource = $xml->createElement('property', $path);
        $resource->setAttribute('name', 'resource');
        $producer->appendChild($resource);

        return $producer;
    }

    private function createVoiceoverPlaylistElement(DOMDocument $xml): DOMElement
    {
        $playlist = $xml->createElement('playlist');
        $playlist->setAttribute('id', 'voiceover_playlist');

        return $playlist;
    }

    private function createVoiceoverEntryElement(DOMDocument $xml): DOMElement
    {
        $entry = $xml->createElement('entry');
        $entry->setAttribute('producer', 'voiceover');

        return $entry;
    }

    private function createMultitrackElement(DOMDocument $xml): DOMElement
    {
        $multitrack = $xml->createElement('multitrack');

        return $multitrack;
    }

    private function createVoiceoverTrackElement(DOMDocument $xml): DOMElement
    {
        $track = $xml->createElement('track');
        $track->setAttribute('producer', 'voiceover_playlist');

        return $track;
    }

    private function createImageTrackElement(DOMDocument $xml): DOMElement
    {
        $track = $xml->createElement('track');
        $track->setAttribute('producer', 'playlist0');

        return $track;
    }
	/**
	 * Save the MLT project to a file.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function save(DOMDocument $xml, string $path): bool {
		return $xml->save($path) !== false;
	}
}