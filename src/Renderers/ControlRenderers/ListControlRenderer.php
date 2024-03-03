<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Nette\Utils\Html;

/**
 * Used for CheckboxList and RadioList
 */
class ListControlRenderer extends BaseControlRenderer
{
    protected function renderControl(Html $container): void
    {
        $this->renderLabel($this->parent);
        $this->renderParent();
        $this->renderItems();
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->parent); //$this->element todo
    }

    protected function renderToGroup(Html $container): void
    {
        $this->renderInputGroupWrapper();
        $this->renderLabel($this->labelInInputGroup || $this->floatingLabel ? $this->parent : $this->inputGroupWrapper);
        $this->renderParent($this->inputGroupWrapper);
        $this->renderItems();
        $this->renderDescription($this->parent);
        $this->renderFeedback($this->inputGroupWrapper);
    }

    /**
     * Items se renderují do this->element
     * @return void
     */
    protected function renderItems(): void
    {
        foreach ($this->control->getItems() as $key => $value)
        {
            $itemWrapper = $this->isInputGroup
                ? $this->htmlFactory->createWrapper('item', 'control listInputGroupItem', false)  // $this->getDefaultWrapper('control listInputGroupItem', $container)
                : $this->htmlFactory->createWrapper('item', 'control listItem', false); // $this->getDefaultWrapper('control listItem', $container)
            $this->element->addHtml($itemWrapper);

            $item = $this->createControlElementItem($key);
            $itemWrapper->addHtml($item);
            $this->renderItemLabel($key, $itemWrapper);
            // Pokud $container je jenom fragment, a itemWrapper není, musí se pousunout 'is-invalid' třída
            if(!$this->element->getName() && $itemWrapper->getName())
                $this->setFeedbackClasses($itemWrapper, '.list');
            // Třída se přiděluje až tady, vyšší priorita
            $itemWrapper->setClasses($this->options->getOption('.item'));
        }
    }

    /**
     * Pokud je error, přiřadí třídu i elementu, aby se správně zobrazoval error container.
     * Pokud neexistuje, pokusí se přiřadit potomkovi v renderItems().
     * @return void
     */
    protected function setupElement(): void
    {
        $this->setFeedbackClasses($this->element, '.list');
        // Třída se dává až tady, má vyšší prioritu.
        $this->element->setClasses($this->options->getOption('.element'));
    }

    protected function createInputGroupWrapper(): RendererHtml
    {
        if($this->isInputGroup)
            return $this->htmlFactory->createWrapper("wrapper", 'inputGroup wrapper shrink');
        return RendererHtml::el();
    }
    /**
     * U CheckBoxList a RadioList je element nikoli control (těch je totiž více), ale wrapper, do kterého jsou vkládány jednotlivé items.
     * Tedy u těchto controlů má smysl vlastní 'element' dodaný (případně) přes option.
     * @return RendererHtml
     */
    protected function createElement() : RendererHtml
    {
        return $this->isInputGroup
            ? $this->htmlFactory->createWrapper('element','control listInputGroup', false)
            : $this->htmlFactory->createWrapper('element','control list', false);
    }

    protected function createParentElement(): RendererHtml
    {
        return $this->isInputGroup
            ? $this->htmlFactory->createWrapper("parent", 'inputGroup container standard')
            : $this->htmlFactory->createWrapper("parent", 'container list') ;
    }

    protected function createControlElementItem($key): RendererHtml
    {
        $item = RendererHtml::fromNetteHtml($this->control->getControlPart($key));
        $this->htmlFactory->setClasses($item, 'control .all');
        $this->htmlFactory->setClasses($item, 'control .checkbox');
        $this->setupControlElement($item);
        return $item;
    }

    protected function renderItemLabel($key, Html $container): void
    {
        $label = RendererHtml::fromNetteHtml($this->control->getLabelPart($key));
        $this->htmlFactory->setClasses($label, 'label .item');
        // Třídy se přidají ručně, není přes factory
        $label->setClasses($this->options->getOption('.itemLabel'));
        $container->addHtml($label);
    }

}