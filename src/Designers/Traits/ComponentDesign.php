<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers\Traits;
trait ComponentDesign
{

    abstract function setOption(string $option, mixed $value): static;
    public function setOptions(array $options): static
    {
        foreach($options as $key => $value){
            $this->setOption($key, $value);
        }
        return $this;
    }
}