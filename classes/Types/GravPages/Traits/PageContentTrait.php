<?php

namespace Grav\Plugin\FlexObjects\Types\GravPages\Traits;

use Grav\Plugin\FlexObjects\Types\FlexPages\Traits\PageContentTrait as ParentTrait;


/**
 * Implements PageContentInterface.
 */
trait PageContentTrait
{
    use ParentTrait;

        /**
     * @inheritdoc
     */
    public function id($var = null): string
    {
        $property = 'id';
        $value = null === $var ? $this->getProperty($property) : null;
        if (null === $value) {
            $value = $this->language() . ($var ?? ($this->modified() . md5($this->filePath())));

            $this->setProperty($property, $value);
            if ($this->doHasProperty($property)) {
                $value = $this->getProperty($property);
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function isPage(): bool
    {
        // FIXME: needs to be better
        return !$this->exists() || !empty($this->getLanguages()) || $this->modular();
    }
}
