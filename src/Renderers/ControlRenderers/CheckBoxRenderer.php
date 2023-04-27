<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Nette\HtmlStringable;
use Nette\Utils\Html;

/**
 * Checkbox se do inputGroup renderuje jako jednopoložkový ListControlRenderer, mimo inputGroup jako jednotlivá item.
 * Tj. Pokud není v input group, vyrenderuje se:
 * <'control listItem'>
 *    <control/>
 *    <label/>
 *    <description/>
 *    <errors/>
 * </'control listItem'>
 * Chceme-li tedy zmenit defaultní 'control listItem', uděláme to přes option 'parent'.
 *
 * Naopak pokud je v inputGroup, kvůli layoutu je jednodušší, když se chová stejně jako ListControlRenderer:
 * <'inputGroup wrapper shrink'> - vlastní definujeme přes option 'wrapper'
 *    <'inputGroup container standard'> - vlastní definujeme přes option 'parent'
 *       <'control listInputGroup'> - vlastní definujeme přes option 'element'
 *          <'control listInputGroupItem'> - vlastní definujeme přes option 'item'
 *              <label/>
 *              <control>
 *          <'control listInputGroupItem'>
 *       </'control listInputGroup'>
 *       <description/>
 *    </'inputGroup container standard'>
 *    <errors/>
 * </'inputGroup wrapper shrink'>
 */
class CheckBoxRenderer extends BaseControlRenderer
{
    public function renderToGroup(Html $container)
    {
        $this->inputGroupWrapper = $this->getWrapper("wrapper", 'inputGroup wrapper shrink', $container);
        $this->renderParent($this->inputGroupWrapper);
        $itemWrapper = $this->getWrapper('item', 'control listInputGroupItem', $this->element); //$this->getDefaultWrapper('control listInputGroupItem', $this->element)
        if($this->control->getOption('item') === null ||  $this->control->getOption('item') === true)
            if($itemWrapper !== $container && is_string($this->control->getOption('.item')))
                $itemWrapper->class($this->control->getOption('.item'), true);
        //Přidání item, jediná položka
        $itemWrapper->addHtml($this->createControlElement());
        // Pokud parent je jenom fragment, a itemWrapper není, musí se pousunout 'is-invalid' třída
        if(!$this->parent->getName() && $itemWrapper->getName())
            $this->setFeedbackClasses($itemWrapper, '.list');
        $this->renderLabel($itemWrapper);
        $this->renderDescription($this->parent, 'description inputGroupContainer');
        $this->renderErrors($this->inputGroupWrapper);
    }
    public function renderControl(Html $container)
    {
        $this->renderParent();
        $this->renderLabel($this->parent);
        $this->renderDescription($this->parent, "description checkboxContainer");
        $this->renderErrors($this->parent);
    }

    /**
     * Pokud je error, přiřadí třídu i elementu, aby se správně zobrazoval error container.
     * Pokud neexistuje, tam kde to je třeba (renderToGroup), pokusí se přiřadit potomkovi.
     * @return void
     */
    protected function setupElement()
    {
        parent::setupElement();
        $this->setFeedbackClasses($this->element, '.list');
    }

    public function createControlElement(): Html
    {
        $element = $this->control->getControlPart();
        $element->class($this->wrappers->getValue("control .checkbox"), true);
        $this->setupControlElement($element);
        return $element;
    }

    protected function createElement() : Html
    {
        // Vlastní element má smysl jen v inputGroup
        if($this->inputGroup){
            $ownElement = $this->control->getOption('element');
            $this->isOwnElement = true;
            if($ownElement instanceof Html)
                return clone $ownElement;
            elseif(is_string($ownElement) && $ownElement)
                return Html::el($ownElement);
            $this->isOwnElement = false;
        }
        $element = $this->inputGroup ? $this->getDefaultWrapper('control listInputGroup') : $this->createControlElement();
        if (is_string($this->control->getOption('.element')))
            $element->class($this->control->getOption('.element'), true);

        return $element;
    }

    protected function createParentElement(): Html
    {
        return $this->inputGroup
            ? $this->getWrapper("parent", 'inputGroup container standard')
            : $this->getWrapper("parent", 'control listItem') ;
    }

    protected function createLabel() : Html
    {
        return $this->control->getLabelPart();
    }

    public function renderLabel(Html $container)  : void
    {
        // Pokud není element labelu, nerenderuje se. Asi nonses tady
        if(!$this->label)
            return;
        // Pokud je zadaný vlastní Html element, je pouze vložen do containeru
        if($this->control->getOption('label') instanceof HtmlStringable){
            $container->addHtml($this->label);
            return;
        }
        $this->label->class($this->wrappers->getValue('label .checkbox'), true);
        $container->addHtml($this->label);
        $this->setLabel();
    }
}