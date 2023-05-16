<?php

namespace Jdvorak23\Bootstrap5FormRenderer;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\BaseControl;

class Options
{
    protected array|BaseControl|ControlGroup|null $sourceOptions = null;
    protected array $defaultOptions;

    public function __construct(array|Options $defaultOptions = [])
    {
        $this->defaultOptions = is_array($defaultOptions) ? $defaultOptions : $defaultOptions->getOptions();
    }

    public function getOption(string $option): mixed
    {
        return $this->getSourceOption($option) ?? $this->getDefaultOption($option);
    }

    public function setOption(string $option, mixed $value): void
    {
        if ($this->sourceOptions !== null)
            $this->setSourceOption($option, $value);
        else
            $this->setDefaultOption($option, $value);
    }

    public function getOptions(): array
    {
        if ($this->sourceOptions === null) {
            $sourceOptions = [];
        } elseif (is_array($this->sourceOptions)) {
            $sourceOptions = $this->sourceOptions;
        } else {// BaseControl || ControlGroup
            $sourceOptions = $this->sourceOptions->getOptions();
        }

        return array_merge($this->defaultOptions, $sourceOptions);
    }

    public function addOption(string $option, mixed $value): void
    {
        $valuesArray = $this->sourceOptions !== null
            ? $this->getSourceOption($option)
            : $this->getDefaultOption($option);
        if(is_array($valuesArray)){
            $valuesArray[] = $value;
        }elseif($valuesArray !== null){
            $valuesArray = [$valuesArray, $value];
        }else{
            $valuesArray = [$value];
        }
        $this->setOption($option, $valuesArray);
    }
    //-----
    public function getSourceOption(string $option): mixed
    {
        if($this->sourceOptions === null)
            return null;
        if(is_array($this->sourceOptions))
            return array_key_exists($option, $this->sourceOptions) ? $this->sourceOptions[$option] : null;

        return $this->sourceOptions->getOption($option);
    }
    public function setSourceOption(string $option, mixed $value): void
    {
        if($this->sourceOptions === null)
            return;
        elseif(is_array($this->sourceOptions))
            $this->sourceOptions[$option] = $value;
        else
            $this->sourceOptions->setOption($option, $value);
    }

    public function hasSource(): bool
    {
        return $this->sourceOptions !== null;
    }
    //-----
    public function getDefaultOption(string $option): mixed
    {
        return $this->defaultOptions[$option] ?? null;
    }
    public function setDefaultOption(string $option, mixed $value): void
    {
        $this->defaultOptions[$option] = $value;
    }
    //-----
    public function setSourceOptions(array|BaseControl|ControlGroup|null $options): void
    {
        $this->sourceOptions = $options;
    }
    public function setDefaultOptions(array $options): void
    {
        $this->defaultOptions = $options;
    }
}