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
    /**
     * Ffmpeg Transpose Code:
     * 0 = 90CounterCLockwise and Vertical Flip (default)
     * 1 = 90Clockwise
     * 2 = 90CounterClockwise
     * 3 = 90Clockwise and Vertical Flip
     */
    const ROTATE_AUTO = 'auto';
    const ROTATE_90 = 'transpose=1';
    const ROTATE_180 = 'hflip,vflip';
    const ROTATE_270 = 'transpose=2';

    /** @var Dimension */
    private $dimension;
    /** @var boolean */
    private $padding;
    /** @var string */
    private $angle;
    /** @var integer */
    private $priority;

    public function __construct(Dimension $dimension, $padding = false, $angle = self::ROTATE_AUTO, $priority = 0)
    {
        $this->dimension = $dimension;
        $this->padding = $padding;
        $this->setAngle($angle);
        $this->priority = $priority;
    }

    /**
     * @return Dimension
     */
    public function getDimension()
    {
        return $this->dimension;
    }

    /**
     * @return boolean
     */
    public function getPadding()
    {
        return $this->padding;
    }

    /**
     * @return Angles
     */
    public function getAngle()
    {
        return $this->angle;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Video $video, VideoInterface $format)
    {
        // Init Variables
        $commands = array();
        $dimensions = null;
        $metadata = null;

        // Collect details of video stream
        foreach ($video->getStreams() as $stream) {
            if ($stream->isVideo()) {
                try {
                    $dimension = $stream->getDimensions();
                    $metadata = $stream->get('tags');
                    break;
                } catch (RuntimeException $e) {
                    // do something?
                }
            }
        }

        $vf = null;
        $scalecmd = $this->detectScale($dimension);
        $rotatecmd = $this->detectAngle($metadata);

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

    private function setAngle($angle)
    {
        switch ($angle) {
            case self::ROTATE_AUTO:
            case self::ROTATE_90:
            case self::ROTATE_180:
            case self::ROTATE_270:
                $this->angle = $angle;
                break;
            default:
                throw new InvalidArgumentException('Invalid angle value.');
        }
    }

    private function detectAngle($metadata)
    {
        if ($this->angle != self::ROTATE_AUTO) {
            return $this->angle;
        } else {
            // Auto Detect
            if(array_key_exists('rotate', $metadata)) {
                $rotation = $metadata['rotate'];
                // k, so the "rotate" Metadata existed!
                if ($rotation==90) {
                    return self::ROTATE_90;
                } elseif($rotation==180) {
                    return self::ROTATE_180;
                } elseif ($rotation==270 || $rotation==-90) {
                    return self::ROTATE_270;
                }
            } else {
                return null;
            }
        }
    }

    private function detectScale($dimension)
    {
        // Target Size
        $targetHeight = $this->dimension->getHeight();
        $targetWidth = $this->dimension->getWidth();
        $targetAspect = round($this->dimension->getRatio()->getValue(), 2);

        if ($this->padding) {
            return 'scale = min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')) * '.
            '(gte(a\,'.$targetAspect.')*'.$targetWidth.' + lt(a\,'.$targetAspect.')*(('.$targetHeight.'*iw)/ih)) + '.
            'not(min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')))*iw : '.
            'min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')) * '.
            '(lte(a\,'.$targetAspect.')*'.$targetHeight.' + gt(a\,'.$targetAspect.')*(('.$targetWidth.'*ih)/iw)) + '.
            'not(min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')))*ih, '.
            'pad='.$targetWidth.':'.$targetHeight.':(ow-iw)/2:(oh-ih)/2';
        } else {
            /**
             * Normal Scaling
             */
            if (null !== $dimension) {
                // Source Size
                $srcHeight = $dimension->getHeight();
                $srcWidth = $dimension->getWidth();
                // Determine Scale Metrics
                if ($srcHeight <= $targetHeight && $srcWidth <= $targetWidth) {
                    // Do not upscale!
                    return null;
                } elseif ($srcWidth > $targetWidth) {
                    return 'scale='.$targetWidth.':trunc\(out_w/a/2\)*2';
                } elseif ($srcHeight > $targetHeight) {
                    return 'scale=trunc\(out_h/a/2\)*2:'.$targetHeight;
                }
            } else {
                return null;
            }
        }
    }
}