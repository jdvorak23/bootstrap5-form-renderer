<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\HtmlWtf;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
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
    public function renderToGroup(Html $container):void
    {
        //$this->inputGroupElement = $this->getWrapper("wrapper", 'inputGroup wrapper shrink', $container);
        $this->renderInputGroupWrapper();
        $this->renderParent($this->inputGroupWrapper);

        $itemWrapper = $this->htmlFactory->createWrapper('item', 'control listInputGroupItem', false); //$this->getDefaultWrapper('control listInputGroupItem', $this->element)
        $this->element->addHtml($itemWrapper);
        //Přidání item, jediná položka
        $itemWrapper->addHtml($this->createControlElement());
        // Pokud parent je jenom fragment, a itemWrapper není, musí se pousunout 'is-invalid' třída
        if(!$this->parent->getName() && $itemWrapper->getName())
            $this->setFeedbackClasses($itemWrapper, '.list');
        // Třída se přiděluje až tady, vyšší priorita
        $itemWrapper->setClasses($this->control->getOption('.item'));
        $this->renderLabel($itemWrapper);
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->inputGroupWrapper);
    }
    public function renderControl(Html $container): void
    {
        $this->renderParent();
        $this->renderLabel($this->parent);
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->parent);
    }

    /**
     * Pokud je error, přiřadí třídu i elementu, aby se správně zobrazoval error container.
     * Pokud neexistuje, tam kde to je třeba (renderToGroup), pokusí se přiřadit potomkovi.
     * @return void
     */
    protected function setupElement(): void
    {
        if($this->isInputGroup){
            $this->setFeedbackClasses($this->element, '.list');
            // Třída se dává až tady, má vyšší prioritu.
            $this->element->setClasses($this->control->getOption('.element'));
        }
    }

    public function createControlElement(): RendererHtml
    {
        $element = RendererHtml::fromNetteHtml($this->control->getControlPart());
        $this->htmlFactory->setClasses($element, 'control .checkbox');
        $this->setupControlElement($element);
        return $element;
    }

    protected function createElement() : RendererHtml
    {
        // Vlastní element má smysl jen v inputGroup
        return $this->isInputGroup
            ? $this->htmlFactory->createWrapper('element','control listInputGroup', false)
            : $this->createControlElement();
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
            : $this->htmlFactory->createWrapper("parent", 'control listItem') ;
    }

    protected function createLabel() : RendererHtml
    {
        return RendererHtml::fromNetteHtml($this->control->getLabelPart());
    }

    public function renderLabel(Html $container)  : void
    {
        if(!$this->label)
            return;
        $this->htmlFactory->setClasses($this->label,'label .checkbox');
        $container->addHtml($this->label);
        $this->setLabel();
    }
}