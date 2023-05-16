<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers\Multi;

use Jdvorak23\Bootstrap5FormRenderer\Options;

class MultiComponentDesigner
{
    /**@var Options[] */
    protected array $options;
    public function __construct(Options ...$options)
    {
        $this->options = $options;
    }
    public function setOption(string $option, mixed $value): static
    {
        foreach($this->options as $oneOptions){
            $oneOptions->setOption($option, $value);
        }
        return $this;
    }
}