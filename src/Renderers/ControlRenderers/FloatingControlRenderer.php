<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\HtmlWtf;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

/**
 * Renderování control, který podporuje floatingLabel.
 */
class FloatingControlRenderer extends StandardControlRenderer
{
    protected bool $floatingLabelAllowed = true;

    protected function setRenderer(): void
    {
        parent::setRenderer();
        // Nastaví placeholder bro bootstrap, placeholder musí být aby fungoval floating label
        if($this->floatingLabel){
            if (is_object($this->control->control) && !array_key_exists("placeholder", $this->control->control->attrs))
                $this->control->setHtmlAttribute("placeholder", $this->control->getCaption()); // todo instanceof
        }
    }
    protected function renderControl(Html $container): void
    {
        if($this->floatingLabel) {
            $this->renderParent();
            $this->renderLabel($this->parent);
        }else{
            $this->renderLabel($this->parent);
            $this->renderParent();
        }
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->parent);
    }
    protected function renderToGroup(Html $container): void
    {
        //$this->inputGroupElement = $this->htmlFactory->createWrapper("wrapper", 'inputGroup wrapper grow', $container);
        $this->renderInputGroupWrapper();
        if($this->floatingLabel) {
            $this->renderParent($this->inputGroupWrapper);
            $this->renderLabel($this->parent);
        }else{
            $this->renderLabel($this->parent);
            $this->renderParent($this->inputGroupWrapper);
        }
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->inputGroupWrapper);
    }
    protected function createInputGroupWrapper(): RendererHtml
    {
        if($this->isInputGroup)
            return $this->htmlFactory->createWrapper("wrapper", 'inputGroup wrapper grow');
        return RendererHtml::el();
    }
    protected function createParentElement(): RendererHtml
    {
        if ($this->isInputGroup)
            return $this->floatingLabel
                ? $this->htmlFactory->createWrapper("parent", 'inputGroup container floating')
                : $this->htmlFactory->createWrapper("parent", 'inputGroup container standard') ;
        else
            return $this->floatingLabel
                ? $this->htmlFactory->createWrapper("parent", 'container floatingLabel')
                : $this->htmlFactory->createWrapper("parent", 'container default') ;
    }

}