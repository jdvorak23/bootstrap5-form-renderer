<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Nette\Utils\Html;

/**
 * Button je specifický, nemá label, nemá ani error.
 * .required, .optional, .all, .error, .noerror - nic z toho nemá pro Button smysl, nepřiřazuje se.
 */
class ButtonRenderer extends BaseControlRenderer
{
    protected function renderControl(Html $container): void
    {
        $this->renderParent();
        $this->renderDescription($this->parent);
    }
    public function renderToGroup(Html $container): void
    {
       // $this->inputGroupElement = $this->getWrapper("wrapper", 'inputGroup wrapper shrink', $container);
        $this->renderInputGroupWrapper();
        $this->renderParent($this->inputGroupWrapper);
        $this->renderDescription($this->parent);
    }
    protected function setupElement(): void
    {
        //Pokud je v inputGroup, nastaví se tlačítku speciální třída, jinak podle typu cotnrol
        $this->isInputGroup
            ? $this->htmlFactory->setClasses($this->element, 'control .inputGroupButton')
            : $this->htmlFactory->setClasses($this->element, "control .{$this->element->type}");
        // Tlačítku můžeme přidat vlastní třídu, jako všem controls přes option '.control'
        $this->element->setClasses($this->control->getOption('.control'));
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
            : $this->htmlFactory->createWrapper("parent", 'container button') ;
    }


}