<?php

/*
 * This file is part of PHP-FFmpeg.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FFMpeg\Filters\Video;

use FFMpeg\Coordinate\Dimension;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\Media\Video;
use FFMpeg\Format\VideoInterface;

class FfmpegFilter implements VideoFilterInterface
{
    /** @var Dimension */
    private $dimension;
    /** @var integer */
    private $priority;
    /** @var integer */
    private $transpose;

    public function __construct(Dimension $dimension, $transpose = -1, $priority = 0)
    {
        $this->dimension = $dimension;
        $this->transpose = $transpose;
        $this->priority = $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return Dimension
     */
    public function getDimension()
    {
        return $this->dimension;
    }

    /**
     * @return Integer
     */
    public function getTranspose()
    {
        return $this->transpose;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Video $video, VideoInterface $format)
    {
        // Init Variables
        $commands = array();
        $dimensions = null;
        $tags = null;
        $vf = null;
        $scalecmd = null;
        $rotatecmd = null;

        // Collect details of video stream
        foreach ($video->getStreams() as $stream) {
            if ($stream->isVideo()) {
                try {
                    $dimension = $stream->getDimensions();
                    $tags = $stream->get('tags');
                    break;
                } catch (RuntimeException $e) {
                    // do something?
                }
            }
        }

        /**
         * Scale
         */
        if (null !== $dimension) {
            // Source Size
            $srcHeight = $dimension->getHeight();
            $srcWidth = $dimension->getWidth();
            $srcAspect = $dimension->getRatio()->getValue();
            // Target Size
            $targetHeight = $this->dimension->getHeight();
            $targetWidth = $this->dimension->getWidth();
            $targetAspect = $this->dimension->getRatio()->getValue();
            // Determine Scale Metrics
            if ($srcHeight <= $targetHeight && $srcWidth <= $targetWidth) {
                // Do nothing
            } elseif ($srcWidth > $targetWidth) {
                $scalecmd = 'scale='.$targetWidth.':trunc\(out_w/a/2\)*2';
            } elseif ($srcHeight > $targetHeight) {
                $scalecmd = 'scale=trunc\(out_h/a/2\)*2:'.$targetHeight;
            }
            /*
            if ($srcHeight < $targetHeight && $srcWidth < $targetWidth) {
                // Do not upscale, break out...
            } elseif ($targetAspect > $srcAspect) {
                $scalecmd = 'scale=trunc(out_h/a/2)*2:'.$targetHeight;
            } else {
                $scalecmd = 'scale='.$targetWidth.':trunc(out_w/a/2)*2';
            }*/
        }

        /**
         * Rotate
         */
        if ($this->transpose == -1) {
            // Auto-Detect
            if(array_key_exists('rotate', $tags)) {
                // k, so the "rotate" Metadata existed!
                if ($tags['rotate']==180) {
                    $rotatecmd = 'vflip,hflip';
                } elseif ($tags['rotate']==90) {
                    $rotatecmd = 'transpose=1';
                } elseif ($tags['rotate']==270 || $tags['rotate']==-90) {
                    $rotatecmd = 'transpose=2';
                }
                /**
                 * Ffmpeg Transpose Code:
                 * 0 = 90CounterCLockwise and Vertical Flip (default)
                 * 1 = 90Clockwise
                 * 2 = 90CounterClockwise
                 * 3 = 90Clockwise and Vertical Flip
                 */
            }
        } else {
            $rotatecmd = 'transpose='.$this->transpose;
        }

        if($scalecmd !== null) {
            $vf = $scalecmd;
            if ($rotatecmd !== null) {
                $vf .= ','.$rotatecmd;
            }
        } elseif ($rotatecmd !== null) {
            $vf = $rotatecmd;
        }

        if ($vf !== null)
            $commands = array('-vf', $vf);
        $commands[] = '-metadata:s:v';
        $commands[] = 'rotate="0"';
        $commands[] = '-movflags';
        $commands[] = 'faststart';
        return $commands;
    }
}
