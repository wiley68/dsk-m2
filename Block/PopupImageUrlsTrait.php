<?php

declare(strict_types=1);

namespace Avalon\Dskapipayment\Block;

/**
 * Popup banner image URLs for desktop and mobile layouts.
 */
trait PopupImageUrlsTrait
{
    /**
     * Desktop popup header image URL.
     */
    public function getDskapiPopupImageDesktopUrl(): string
    {
        return $this->getDskapiPopupImageUrl('dsk');
    }

    /**
     * Mobile popup header image URL.
     */
    public function getDskapiPopupImageMobileUrl(): string
    {
        return $this->getDskapiPopupImageUrl('dskm');
    }

    private function getDskapiPopupImageUrl(string $prefix): string
    {
        $reklama = 1;
        if ($this->_paramsdskapi !== null && isset($this->_paramsdskapi->dsk_reklama)) {
            $reklama = (int) $this->_paramsdskapi->dsk_reklama;
        }

        return $this->getDskapiLiveUrl()
            . '/calculators/assets/img/'
            . $prefix
            . $reklama
            . '.png';
    }
}
