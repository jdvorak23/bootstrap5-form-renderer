<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers;
use Nette\Utils\Html;

class RendererHtml extends Html
{
    public bool $isOwn = false;

    public static function fromNetteHtml(Html $netteHtml): static
    {
        $html = new static();
        $html->setName($netteHtml->getName());
        $html->attrs = $netteHtml->attrs;
        $html->copyChildren($netteHtml);
        return $html;
    }

    public function setClasses(mixed $classes, bool $reverse = false) : void
    {
        if (!is_string($classes) || !$classes)
            return;
        $this->splitAllClasses();
        foreach (explode(' ', $classes) as $class) {
            if (str_starts_with($class, '!')) {
                $this->class(substr($class, 1, strlen($class) - 1), $reverse);
            } else
                $this->class($class, !$reverse);
        }
    }

    public function splitAllClasses() : void
    {
        if(!isset($this->attrs['class']) || !$this->attrs['class'])
            return;

        if(!is_array($this->attrs['class']))
            $this->attrs['class'] = [$this->attrs['class'] => true];

        foreach($this->attrs['class'] as $class => $state){
            $this->splitClasses($class, $state);
        }
    }

    protected function splitClasses(string $classes, bool $state = true): void
    {
        $arr = explode(' ', $classes);
        if(count($arr) <= 1)
            return;
        foreach ($arr as $class) {
            if($class)
                $this->class($class, $state);
        }
        unset($this->attrs['class'][$classes]);
    }

    protected function copyChildren(Html $netteHtml): void
    {
        foreach ($netteHtml as $key => $child) {
            $this->children[$key] = is_object($child) ? clone $child : $child;
        }
    }

}