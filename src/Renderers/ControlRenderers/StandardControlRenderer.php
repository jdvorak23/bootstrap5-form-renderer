<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Nette\Forms\Controls\TextArea;
use Nette\Utils\Html;

/**
 * Used for MultiSelectBox || UploadControl || any own BaseControl by default.
 */
class StandardControlRenderer extends BaseControlRenderer
{
    protected function renderControl(Html $container): void
    {
        $this->renderLabel($this->parent);
        $this->renderParent();
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->parent);
    }

    protected function renderToGroup(Html $container): void
    {
        $this->renderInputGroupWrapper();
        $this->renderLabel($this->parent);
        $this->renderParent($this->inputGroupWrapper);
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->inputGroupWrapper);
    }
    protected function createInputGroupWrapper(): RendererHtml
    {
        if($this->isInputGroup)
            return $this->htmlFactory->createWrapper("wrapper", 'inputGroup wrapper shrink');
        return RendererHtml::el();
    }
    protected function createParentElement(): RendererHtml
    {
        return $this->isInputGroup
            ? $this->htmlFactory->createWrapper("parent", 'inputGroup container standard')
            : $this->htmlFactory->createWrapper("parent", 'container default') ;
    }
    protected function setupElement(): void
    {
        if ($this->element->getName() === 'input')
            $this->htmlFactory->setClasses($this->element, "control .{$this->element->type}");
        elseif(strtolower($this->element->getName()) == 'select')
            $this->htmlFactory->setClasses($this->element, "control .select");
        elseif($this->control instanceof TextArea)
            $this->htmlFactory->setClasses($this->element, "control .textarea");
        //Přiřadí třídy
        $this->setupControlElement($this->element);
    }

}