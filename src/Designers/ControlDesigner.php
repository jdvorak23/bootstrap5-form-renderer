<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers;

use Jdvorak23\Bootstrap5FormRenderer\Designers\Traits\ControlDesign;

class ControlDesigner extends ComponentDesigner
{
    use ControlDesign;
    public function addToOption(string $option, mixed $value): static
    {
        $this->options->addOption($option, $value);
        return $this;
    }

}