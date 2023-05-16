<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers;
use Jdvorak23\Bootstrap5FormRenderer\Designers\Traits\PseudoDesign;
use Nette\Utils\Html;

class PseudoDesigner extends ComponentDesigner
{
    use PseudoDesign;

    /**
     * If you want insert pseudo-element into button group, it must be set as button by 'type' option
     * @param bool $setAsButton
     * @return $this
     */
    public function setAsButton(bool $setAsButton = true): static
    {
        $this->setOption('type', 'button');
        return $this;
    }

    /**
     * Sets content of pseudo element
     * @param Html|string $content
     * @return $this
     */
    public function setContent(Html|string $content): static
    {
        $this->setOption('content', $content);
        return $this;
    }
}