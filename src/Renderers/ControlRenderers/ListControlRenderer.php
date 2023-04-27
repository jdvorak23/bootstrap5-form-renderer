<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Nette\Utils\Html;

class ListControlRenderer extends BaseControlRenderer
{
    protected function renderControl(Html $container)
    {
        $this->renderLabel($this->parent);
        $this->renderParent();
        $this->renderItems($this->element);
        $this->renderDescription($this->parent, "description listContainer");
        $this->renderErrors($this->parent); //$this->element todo
    }

    protected function renderToGroup(Html $container)
    {
        $this->inputGroupWrapper = $this->getWrapper("wrapper", 'inputGroup wrapper shrink', $container);
        $this->renderLabel($this->parent);
        $this->renderParent($this->inputGroupWrapper);
        $this->renderItems($this->element);
        $this->renderDescription($this->parent, 'description inputGroupContainer');
        $this->renderErrors($this->inputGroupWrapper);
    }

    protected function renderItems(Html $container){
        foreach ($this->control->getItems() as $key => $value)
        {
            $itemWrapper = $this->inputGroup
                ? $this->getWrapper('item', 'control listInputGroupItem', $container)  // $this->getDefaultWrapper('control listInputGroupItem', $container)
                : $this->getWrapper('item', 'control listItem', $container); // $this->getDefaultWrapper('control listItem', $container)
            if($this->control->getOption('item') === null ||  $this->control->getOption('item') === true) {
                if ($itemWrapper !== $container && is_string($this->control->getOption('.item'))){
                    $itemWrapper->class($this->control->getOption('.item'), true);
                }
            }
            $item = $this->createControlElementItem($key);
            $itemWrapper->addHtml($item);
            $this->renderItemLabel($key, $itemWrapper);
            // Pokud $container je jenom fragment, a itemWrapper není, musí se pousunout 'is-invalid' třída
            if(!$container->getName() && $itemWrapper->getName())
                $this->setFeedbackClasses($itemWrapper, '.list');

        }
    }

    /**
     * Pokud je error, přiřadí třídu i elementu, aby se správně zobrazoval error container.
     * Pokud neexistuje, pokusí se přiřadit potomkovi v renderItems().
     * @return void
     */
    protected function setupElement()
    {
        parent::setupElement();
        $this->setFeedbackClasses($this->element, '.list');
    }

    /**
     * U CheckBoxList a RadioList je element nikoli control (těch je totiž více), ale wrapper, do kterého jsou vkládány jednotlivé items.
     * Tedy u těchto controlů má smysl vlastní 'element' dodaný (případně) přes option.
     * @return Html
     */
    protected function createElement() : Html
    {
        $ownElement = $this->control->getOption('element');
        $this->isOwnElement = true;
        if($ownElement instanceof Html)
            return clone $ownElement;
        elseif(is_string($ownElement) && $ownElement)
            return Html::el($ownElement);
        $this->isOwnElement = false;
        // Není zadán, veme default.
        $element = $this->inputGroup
            ? $this->getDefaultWrapper('control listInputGroup')
            : $this->getDefaultWrapper('control list');
        // Přidá případnou třídu v option '.element'.
        if (is_string($this->control->getOption('.element')))
            $element->class($this->control->getOption('.element'), true);
        return $element;
    }

    protected function createParentElement(): Html {
        return $this->inputGroup
            ? $this->getWrapper("parent", 'inputGroup container standard')
            : $this->getWrapper("parent", 'pair listContainer') ;
    }

    protected function createControlElementItem($key): Html {
        $item = $this->control->getControlPart($key);
        $item->class($this->wrappers->getValue("control .checkbox"), true);
        $this->setupControlElement($item);
        return $item;
    }

    protected function renderItemLabel($key, Html $container){
        $label = $this->control->getLabelPart($key);
        $label->class($this->wrappers->getValue("label .item"), true);
        //Vlastni styly přidané přes option '.itemLabel'.
        if(is_string($this->control->getOption('.itemLabel')))
            $this->label->class($this->control->getOption('.itemLabel'), true);
        $container->addHtml($label);
    }

}