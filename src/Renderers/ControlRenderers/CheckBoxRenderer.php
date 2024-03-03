<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;


use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Nette\Utils\Html;

/**
 * Used for Checkbox. To the input group rendered exactly like RadioList and CheckboxList
 */
class CheckBoxRenderer extends BaseControlRenderer
{
    public function renderToGroup(Html $container):void
    {
        $this->renderInputGroupWrapper();
        $this->renderParent($this->inputGroupWrapper);
        $this->renderItem();
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

    protected function renderItem()
    {
        $itemWrapper = $this->htmlFactory->createWrapper('item', 'control listInputGroupItem', false); //$this->getDefaultWrapper('control listInputGroupItem', $this->element)
        $this->element->addHtml($itemWrapper);
        //Přidání item, jediná položka
        $itemWrapper->addHtml($this->createControlElement());
        // Pokud parent je jenom fragment, a itemWrapper není, musí se pousunout 'is-invalid' třída
        if(!$this->parent->getName() && $itemWrapper->getName())
            $this->setFeedbackClasses($itemWrapper, '.list');
        // Třída se přiděluje až tady, vyšší priorita
        $itemWrapper->setClasses($this->options->getOption('.item'));
        $this->renderLabel($itemWrapper);
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
            $this->element->setClasses($this->options->getOption('.element'));
        }
    }

    public function createControlElement(): RendererHtml
    {
        $element = RendererHtml::fromNetteHtml($this->control->getControlPart());
        $this->htmlFactory->setClasses($element, 'control .all');
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

    protected function setupInputGroupWrapper(): void
    {
        if (!$this->floatingLabel && !$this->labelInInputGroup) {
            $this->inputGroupWrapper->addHtml($this->createVoidLabel());
        }
        if (!$this->floatingLabel && $this->labelInInputGroup && $this->options->getOption('forceVoidLabel') === true) {
            $this->inputGroupWrapper->addHtml($this->createVoidLabel());
        }
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
        if (!$this->label) {
            return;
        }
        $this->htmlFactory->setClasses($this->label,'label .checkbox');
        $container->addHtml($this->label);
        $this->setupLabel();
    }
}