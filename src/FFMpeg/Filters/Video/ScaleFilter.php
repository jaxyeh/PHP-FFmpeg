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

class ScaleFilter implements VideoFilterInterface
{
    /** @var Dimension */
    private $dimension;
    /** @var integer */
    private $priority;

    public function __construct(Dimension $dimension, $priority = 0)
    {
        $this->dimension = $dimension;
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
     * {@inheritdoc}
     */
    public function apply(Video $video, VideoInterface $format)
    {
        $targetHeight =$this->dimension->getHeight();
        $targetWidth = $this->dimension->getWidth();
        $targetAspect = round($this->dimension->getRatio()->getValue(), 2);
        $commands = array('-vf', 'scale = min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')) * '.
            '(gte(a\,'.$targetAspect.')*'.$targetWidth.' + lt(a\,'.$targetAspect.')*(('.$targetHeight.'*iw)/ih)) + '.
            'not(min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')))*iw : '.
            'min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')) * '.
            '(lte(a\,'.$targetAspect.')*'.$targetHeight.' + gt(a\,'.$targetAspect.')*(('.$targetWidth.'*ih)/iw)) + '.
            'not(min(1\,gt(iw\,'.$targetWidth.')+gt(ih\,'.$targetHeight.')))*ih, '.
            'pad='.$targetWidth.':'.$targetHeight.':(ow-iw)/2:(oh-ih)/2');
        return $commands;
    }
}
