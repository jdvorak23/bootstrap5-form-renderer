<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Nette\Utils\Html;

/**
 * Used for SelectBox and TextBase (TextInput and TextArea)
 * All supports floating labels
 */
class FloatingControlRenderer extends StandardControlRenderer
{
    protected bool $floatingLabelAllowed = true;

    protected function setRenderer(): void
    {
        parent::setRenderer();
        // Nastaví placeholder bro bootstrap, placeholder musí být aby fungoval floating label
        if($this->floatingLabel && !$this->labelInInputGroup) {
            if (is_object($this->control->control) && !array_key_exists("placeholder", $this->control->control->attrs))
                $this->control->setHtmlAttribute("placeholder", $this->control->getCaption());
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
        $this->renderInputGroupWrapper();
        if($this->floatingLabel) {
            if ($this->labelInInputGroup) {
                $this->renderLabel($this->parent);
                $this->renderParent($this->inputGroupWrapper);
            } else {
                $this->renderParent($this->inputGroupWrapper);
                $this->renderLabel($this->parent);
            }

        }else{
            $this->renderLabel($this->labelInInputGroup ? $this->parent : $this->inputGroupWrapper);
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
        if ($this->isInputGroup) {
            return (!$this->floatingLabel || $this->labelInInputGroup)
                ? $this->htmlFactory->createWrapper("parent", 'inputGroup container standard')
                : $this->htmlFactory->createWrapper("parent", 'inputGroup container floating');
        } else {
            return $this->floatingLabel
                ? $this->htmlFactory->createWrapper("parent", 'container floatingLabel')
                : $this->htmlFactory->createWrapper("parent", 'container default');
        }
    }

}