<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Nette\Utils\Html;

/**
 * Button je specifický, nemá label, nemá ani error.
 * .required, .optional, .all, .error, .noerror - nic z toho nemá pro Button smysl, nepřiřazuje se.
 */
class ButtonRenderer extends BaseControlRenderer
{
    protected function renderControl(Html $container){
        $this->renderParent();
        $this->renderDescription($this->parent, 'description buttonContainer');
    }
    public function renderToGroup(Html $container){
        $this->inputGroupWrapper = $this->getWrapper("wrapper", 'inputGroup wrapper shrink', $container);
        $this->renderParent($this->inputGroupWrapper);
        $this->renderDescription($this->parent, 'description inputGroupContainer');
    }
    protected function setupElement()
    {
        parent::setupElement();
        //Pokud je v inputGroup, nastaví se tlačítku speciální třída, jinak podle typu cotnrol
        $this->inputGroup
            ? $this->element->class($this->wrappers->getValue("control .inputGroupButton"), true)
            : $this->element->class($this->wrappers->getValue("control .{$this->element->type}"), true);
        // Tlačítku můžeme přidat vlastní třídu, jako všem controls přes option '.control'
        if(is_string($this->control->getOption('.control')) )
            $this->element->class($this->control->getOption('.control'), true);
    }

    protected function createParentElement(): Html
    {
        return $this->inputGroup
            ? $this->getWrapper("parent", 'inputGroup container standard')
            : $this->getWrapper("parent", 'pair buttonContainer') ;
    }

}