<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Nette\Forms\Controls\TextArea;
use Nette\Utils\Html;


class StandardControlRenderer extends BaseControlRenderer
{
    protected function renderControl(Html $container)
    {
        $this->renderLabel($this->parent);
        $this->renderParent();
        $this->renderDescription($this->parent, 'description container');
        $this->renderErrors($this->parent);
    }

    protected function renderToGroup(Html $container)
    {
        $this->inputGroupWrapper = $this->getWrapper("wrapper", 'inputGroup wrapper shrink', $container);
        $this->renderLabel($this->parent);
        $this->renderParent($this->inputGroupWrapper);
        $this->renderDescription($this->parent, 'description inputGroupContainer');
        $this->renderErrors($this->inputGroupWrapper);
    }

    protected function createParentElement(): Html{
        return $this->inputGroup
            ? $this->getWrapper("parent", 'inputGroup container standard')
            : $this->getWrapper("parent", 'pair container') ;
    }

    protected function setupElement()
    {
        parent::setupElement();
        if ($this->element->getName() === 'input')
            $this->element->class($this->wrappers->getValue("control .{$this->element->type}"), true);
        elseif(strtolower($this->element->getName()) == 'select')
            $this->element->class($this->wrappers->getValue("control .select"), true);
        elseif($this->control instanceof TextArea)
            $this->element->class($this->wrappers->getValue("control .textarea"), true);
        //Přiřadí třídy
        $this->setupControlElement($this->element);
    }
}