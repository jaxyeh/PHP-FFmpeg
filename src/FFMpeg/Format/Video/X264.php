<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg\Format\Video;

/**
 * The X264 video format
 */
class X264 extends DefaultVideo
{
    /** @var String */
    protected $profile;
    /** @var String */
    protected $level;
    /** @var Integer */
    protected $passes;

    public function __construct($audioCodec = 'libfaac', $videoCodec = 'libx264')
    {
        $this
            ->setAudioCodec($audioCodec)
            ->setVideoCodec($videoCodec);
    }

    /**
     * Sets the X264 Profile
     * 
     * @param string $profile Default is set at baseline, possible options are: baseline, main, high, high10, high422, high444
     * @param string $level   Default is set at 3.0
     */
    public function setProfile($profile = 'baseline', $level = '3.0')
    {
        /* baseline, main, high, high10, high422, high444 */
        $this->profile = $profile;
        $this->level = $level;
    }

    public function setPasses(Integer $passes){
        $this->passes = $passes;
    }

    public function getExtraParams()
    {
        if (!empty($this->profile) && !empty($this->level)) {
            return array('-profile:v', $this->profile, '-level:v', $this->level);
        } else {
            return array();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supportBFrames()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableAudioCodecs()
    {
        return array('libvo_aacenc', 'libfaac', 'libmp3lame');
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableVideoCodecs()
    {
        return array('libx264');
    }

    /**
     * {@inheritDoc}
     */
    public function getPasses()
    {
        return $this->passes;
    }

    public function getModulus()
    {
        return 2;
    }
}
