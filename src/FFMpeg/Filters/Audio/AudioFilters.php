<?php

namespace FFMpeg\Filters\Audio;

use FFMpeg\Media\Audio;
use FFMpeg\Filters\Audio\AudioResamplableFilter;

class AudioFilters
{
    protected $media;

    public function __construct(Audio $media)
    {
        $this->media = $media;
    }

    /**
     * Resamples the audio file.
     *
     * @param Integer $rate
     *
     * @return AudioFilters
     */
    public function resample($rate, $channels = 2)
    {
        $this->media->addFilter(new AudioResamplableFilter($rate, $channels));

        return $this;
    }
}
