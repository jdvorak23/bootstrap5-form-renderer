<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers;
use Jdvorak23\Bootstrap5FormRenderer\Options;

abstract class ComponentDesigner
{
    public Options $options;
    public function __construct(Options|null $options = null)
    {
        $this->options = $options ?? new Options();
    }
    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setOption(string $option, mixed $value): static
    {
        $this->options->setOption($option, $value);
        return $this;
    }
}