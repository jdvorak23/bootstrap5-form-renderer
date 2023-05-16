<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers\Multi;
use Jdvorak23\Bootstrap5FormRenderer\Designers\Traits\ControlDesign;

class MultiControlDesigner extends MultiComponentDesigner
{
    use ControlDesign;
    public function addToOption(string $option, mixed $value): static
    {
        foreach($this->options as $oneOptions){
            $oneOptions->addOption($option, $value);
        }
        return $this;
    }
}