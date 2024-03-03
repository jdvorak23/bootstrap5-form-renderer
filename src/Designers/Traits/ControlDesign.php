<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers\Traits;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\BaseControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\HtmlFactory;
use Nette\HtmlStringable;
use Nette\Utils\Html;

trait ControlDesign
{
    use PseudoDesign;

    abstract public function addToOption(string $option, mixed $value): static;

    /**
     * Sets pseudo-element before this Control, represented by provided HtmlFactory.
     * You can add more of them to the same control, they are automatically added to the array
     * With this designer method, add one-by-one, not array
     * @param HtmlFactory $pseudoElement option 'pseudoBefore'
     * @return $this
     */
    public function insertHtmlBefore(HtmlFactory $pseudoElement): static
    {
        $this->addToOption('pseudoBefore', $pseudoElement);
        return $this;
    }

    /**
     * Sets pseudo-element after this Control, represented by provided HtmlFactory.
     * You can add more of them to the same control, they are automatically added to the array
     * With this designer method, add one-by-one, not array
     * @param HtmlFactory $pseudoElement option 'pseudoAfter'
     * @return $this
     */
    public function insertHtmlAfter(HtmlFactory $pseudoElement): static
    {
        $this->addToOption('pseudoAfter', $pseudoElement);
        return $this;
    }

    /**
     * Sets own HtmlFactory to append options predefined in Options and/or Wrappers,
     * inserted to provided HtmlFactory
     * @param HtmlFactory $htmlFactory option 'htmlFactory'
     * @return $this
     */
    public function setHtmlFactory(HtmlFactory $htmlFactory): static
    {
        $this->setOption('htmlFactory', $htmlFactory);
        return $this;
    }

    /**
     * Sets own renderer for the control.
     * If you provide HtmlFactory to the own renderer, option 'htmlFactory' on control is omitted.
     * @param BaseControlRenderer|null $renderer option 'renderer'
     * @return $this
     */
    public function setRenderer(BaseControlRenderer|null $renderer): static
    {
        $this->setOption('renderer', $renderer);
        return $this;
    }

    /**
     * Set floating label on / off on control
     * @param bool $floatingLabel option 'floatingLabel'
     * @return $this
     */
    public function setFloatingLabel(bool $floatingLabel = true): static
    {
        $this->setOption('floatingLabel', $floatingLabel);
        return $this;
    }

    /**
     * Set label being / not being part of input group for control
     * @param bool $labelInInputGroup option 'labelInInputGroup'
     * @return $this
     */
    public function setLabelInInputGroup(bool $labelInInputGroup): static
    {
        $this->setOption('labelInInputGroup', $labelInInputGroup);
        return $this;
    }

    /**
     * Sets a void label. It's automatically used to fill space when label is not generated
     * (is null or structure doesn't have it, like checkbox)
     * when control is in input group and 'labelInInputGroup' is false and 'floatingLabel' is false
     * When 'labelInInputGroup' is true, you can force creating this void label by option 'forceVoidLabel'
     * Default wrapper is **$wrappers['label']['voidLabel']**
     * @param bool|Html|string|null $voidLabel option 'voidLabel'
     * @return $this
     */
    public function setVoidLabel(null|bool|Html|string $voidLabel): static
    {
        $this->setOption('voidLabel', $voidLabel);
        return $this;
    }

    /**
     * Sets content of 'voidLabel'
     * Default wrapper is **$wrappers['label']['voidLabelContent']**
     * @param bool|Html|string|null $voidLabelContent option 'voidLabelContent'
     * @return $this
     */
    public function setVoidLabelContent(null|bool|Html|string $voidLabelContent): static
    {
        $this->setOption('voidLabelContent', $voidLabelContent);
        return $this;
    }

    /**
     * This have sense only in case when there is input group, 'floatingLabel' is false and 'labelInInputGroup' is true
     * In that case, 'voidLabel' structure will be generated if this option is set to 'true'
     * @param bool $forceVoidLabel option 'forceVoidLabel'
     * @return $this
     */
    public function setForceVoidLabel(bool $forceVoidLabel): static
    {
        $this->setOption('forceVoidLabel', $forceVoidLabel);
        return $this;
    }

    /**
     * Set client validation on given control on / off
     * When true, validation messages containers are generated always (prepared for validation javascript)
     * When false, validation messages containers are generated only
     * if there is message to display from server-side validation
     * @param bool $clientValidation option 'clientValidation'
     * @return $this
     */
    public function setClientValidation(bool $clientValidation = true): static
    {
        $this->setOption('clientValidation', $clientValidation);
        return $this;
    }
    /**
     * Set classes to control element itself
     * Classes are set to every element, where is **name** attribute generated
     * @param string $controlClasses option '.control'
     * @return $this
     */
    public function setControlClasses(string $controlClasses): static
    {
        $this->setOption('.control', $controlClasses);
        return $this;
    }
    /**
     * Set top wrapper for elements in input group
     * Default wrapper is **$wrappers['inputGroup']['wrapper']['shrink']** or **$wrappers['inputGroup']['wrapper']['grow']**
     * String could be own wrapper, but it also could be name of own 3D wrapper.
     * More info https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Control%20Options#wrapper
     * @param bool|Html|string|null $wrapper option 'wrapper'
     * @return $this
     */
    public function setInputGroupWrapper(null|bool|Html|string $wrapper): static
    {
        $this->setOption('wrapper', $wrapper);
        return $this;
    }
    /**
     * Set classes wor input group wrapper
     * Default wrapper is **$wrappers['inputGroup']['wrapper']['shrink']** or **$wrappers['inputGroup']['wrapper']['grow']**
     * @param string $wrapperClasses option '.wrapper'
     * @return $this
     */
    public function setInputGroupWrapperClasses(string $wrapperClasses): static
    {
        $this->setOption('.wrapper', $wrapperClasses);
        return $this;
    }

    /**
     * Set parent element of control
     * If control is in input group, default wrapper is one of **$wrappers['inputGroup']['container']**
     * If control is not in input group, default wrapper is one of **$wrappers['container']**
     * @param bool|Html|string|null $parent option 'parent'
     * @return $this
     */
    public function setParent(null|bool|Html|string $parent): static
    {
        $this->setOption('parent', $parent);
        return $this;
    }

    /**
     * Set classes of parent element of control
     * If control is in input group, default wrapper is one of **$wrappers['inputGroup']['container']**
     * If control is not in input group, default wrapper is one of **$wrappers['container']**
     * @param string $parentClasses option '.parent'
     * @return $this
     */
    public function setParentClasses(string $parentClasses): static
    {
        $this->setOption('.parent', $parentClasses);
        return $this;
    }
    /**
     * Set wrapper representing control element
     * Works for CheckboxList and RadioList. Also works for Checkbox, but only if it is in input group
     * If not in input group, default wrapper is **$wrappers['control']['list']**
     * If in input group, default wrapper is **$wrappers['control']['listInputGroup']**
     * @param bool|Html|string|null $element option 'element'
     * @return $this
     */
    public function setElement(null|bool|Html|string $element): static
    {
        $this->setOption('element', $element);
        return $this;
    }

    /**
     * Set classes of element representing control
     * Works for CheckboxList and RadioList. Also works for Checkbox, but only if it is in input group
     * If not in input group, default wrapper is **$wrappers['control']['list']**
     * If in input group, default wrapper is **$wrappers['control']['listInputGroup']**
     * @param string $elementClasses option '.element'
     * @return $this
     */
    public function setElementClasses(string $elementClasses): static
    {
        $this->setOption('.element', $elementClasses);
        return $this;
    }

    /**
     * Set wrapper for every item of control element
     * Works for CheckboxList and RadioList. Also works for Checkbox, but only if it is in input group
     * If not in input group, default wrapper is **$wrappers['control']['listItem']**
     * If in input group, default wrapper is **$wrappers['control']['listInputGroupItem']**
     * @param bool|Html|string|null $item option 'item'
     * @return $this
     */
    public function setItem(null|bool|Html|string $item): static
    {
        $this->setOption('item', $item);
        return $this;
    }

    /**
     * Set classes of wrapper for every ITEM of control element
     * Works for CheckboxList and RadioList. Also works for Checkbox, but only if it is in input group
     * If not in input group, default wrapper is **$wrappers['control']['listItem']**
     * If in input group, default wrapper is **$wrappers['control']['listInputGroupItem']**
     * @param string $itemClasses option '.item'
     * @return $this
     */
    public function setItemClasses(string $itemClasses): static
    {
        $this->setOption('.item', $itemClasses);
        return $this;
    }

    /**
     * Set classes of label for every ITEM of control element
     * Works for CheckboxList and RadioList items labels generated by Nette classes.
     * @param string $itemLabelClasses option '.itemLabel'
     * @return $this
     */
    public function setItemLabelClasses(string $itemLabelClasses): static
    {
        $this->setOption('.itemLabel', $itemLabelClasses);
        return $this;
    }

    /**
     * Sets OWN label for Control. Only HtmlStringable could be used and only that provided content is rendered as a label
     * If set, $label or $caption entered by Nette's Form builder are omitted
     * If there is need to have 'for' attribute, you must do it manually.
     * This option have sense mainly for RadioList and CheckboxList, as their overall label doesn't have 'for' attribute
     * If you only want to change text of it, it is a caption, and is not set by the renderer:
     * $form['my_control_name']->setCaption("My new caption").
     * More info https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Control%20Options#label
     * @param HtmlStringable $label option 'label'
     * @return $this
     */
    public function setLabel(HtmlStringable $label): static
    {
        $this->setOption('label', $label);
        return $this;
    }

    /**
     * Sets classes to the **generated label**.
     * If own label is used through option 'label', these classes are NOT appended.
     * @param string $labelClasses option '.label'
     * @return $this
     */
    public function setLabelClasses(string $labelClasses): static
    {
        $this->setOption('.label', $labelClasses);
        return $this;
    }

    /**
     * Sets description for Control. If HtmlStringable used, only that provided content is rendered
     * If string is provided, it is rendered to the layout:
     * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Layout#description
     * @param bool|HtmlStringable|string|null $description option 'description'
     * @return $this
     */
    public function setDescription(null|bool|HtmlStringable|string $description): static
    {
        $this->setOption('description', $description);
        return $this;
    }

    /**
     * Sets wrapper for textual description.
     * Default wrapper (no input group on Control) **$wrappers['description']['item']**
     * Default wrapper if Control in input group **$wrappers['description']['inputGroupItem']**
     * @param bool|Html|string|null $descriptionItem option 'descriptionItem'
     * @return $this
     */
    public function setDescriptionItem(null|bool|Html|string $descriptionItem): static
    {
        $this->setOption('descriptionItem', $descriptionItem);
        return $this;
    }

    /**
     * Sets classes for 'descriptionItem' wrapper (method above)
     * @param string $descriptionItemClasses option '.description'
     * @return $this
     */
    public function setDescriptionItemClasses(string $descriptionItemClasses): static
    {
        $this->setOption('.description', $descriptionItemClasses);
        return $this;
    }

    /**
     * Sets container for 'descriptionItem'.
     * Default wrapper is **$wrappers['description']['container']** and is null
     * Sometimes needed to be set to handle description properly in the layout
     * @param bool|Html|string|null $descriptionContainer option 'descriptionContainer'
     * @return $this
     */
    public function setDescriptionContainer(null|bool|Html|string $descriptionContainer): static
    {
        $this->setOption('descriptionContainer', $descriptionContainer);
        return $this;
    }

    /**
     * Sets classes for 'descriptionContainer' wrapper, method above
     * @param string $descriptionContainerClasses option '.descriptionContainer'
     * @return $this
     */
    public function setDescriptionContainerClasses(string $descriptionContainerClasses): static
    {
        $this->setOption('.descriptionContainer', $descriptionContainerClasses);
        return $this;
    }

    /**
     * Sets wrapper for single error item
     * Layout with default wrappers:
     * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Layout#feedback-messages
     * @param bool|Html|string|null $error option 'error'
     * @return $this
     */
    public function setErrorItem(null|bool|Html|string $error): static
    {
        $this->setOption('error', $error);
        return $this;
    }

    /**
     * Sets classes for error item, method above
     * @param string $errorClasses option '.error'
     * @return $this
     */
    public function setErrorItemClasses(string $errorClasses): static
    {
        $this->setOption('.error', $errorClasses);
        return $this;
    }

    /**
     * Sets container for all error items.
     * Layout with default wrappers:
     * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Layout#feedback-messages
     * @param bool|Html|string|null $errorContainer option 'errorContainer'
     * @return $this
     */
    public function setErrorContainer(null|bool|Html|string $errorContainer): static
    {
        $this->setOption('errorContainer', $errorContainer);
        return $this;
    }

    /**
     * Sets classes for 'errorContainer', method above
     * @param string $errorContainerClasses
     * @return $this
     */
    public function setErrorContainerClasses(string $errorContainerClasses): static
    {
        $this->setOption('.errorContainer', $errorContainerClasses);
        return $this;
    }

    /**
     * Sets message for valid feedback message
     * @param string|bool $feedback option 'feedback'
     * @return $this
     */
    public function setFeedbackMessage(string|bool $feedback): static
    {
        $this->setOption('feedback', $feedback);
        return $this;
    }

    /**
     * Sets wrapper for single valid feedback message
     * Layout with default wrappers:
     * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Layout#feedback-messages
     * @param bool|Html|string|null $valid option 'valid'
     * @return $this
     */
    public function setValidItem(null|bool|Html|string $valid): static
    {
        $this->setOption('valid', $valid);
        return $this;
    }

    /**
     * Sets classes for valid feedback item, method above
     * @param string $validClasses option '.valid'
     * @return $this
     */
    public function setValidItemClasses(string $validClasses): static
    {
        $this->setOption('.valid', $validClasses);
        return $this;
    }

    /**
     * Sets container for valid feedback item
     * Layout with default wrappers:
     * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Layout#feedback-messages
     * @param bool|Html|string|null $validContainer option 'validContainer'
     * @return $this
     */
    public function setValidContainer(null|bool|Html|string $validContainer): static
    {
        $this->setOption('validContainer', $validContainer);
        return $this;
    }

    /**
     * Sets classes for 'validContainer', method above
     * @param string $validContainerClasses option '.validContainer'
     * @return $this
     */
    public function setValidContainerClasses(string $validContainerClasses): static
    {
        $this->setOption('.validContainer', $validContainerClasses);
        return $this;
    }
}