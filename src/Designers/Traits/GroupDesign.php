<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers\Traits;

use Jdvorak23\Bootstrap5FormRenderer\HtmlFactory;
use Nette\HtmlStringable;
use Nette\Utils\Html;

trait GroupDesign
{
    use ComponentDesign;

    /**
     * Sets pseudo-element before this ControlGroup, represented by provided HtmlFactory.
     * Unlike on Control, on ControlGroup only one could be inserted, as there is no need of more.
     * @param HtmlFactory $pseudoElement option 'pseudoBefore'
     * @return $this
     */
    public function insertHtmlBefore(HtmlFactory $pseudoElement): static
    {
        $this->setOption('pseudoBefore', $pseudoElement);
        return $this;
    }
    /**
     * Sets pseudo-element after this ControlGroup, represented by provided HtmlFactory.
     * Unlike on Control, on ControlGroup only one could be inserted, as there is no need of more.
     * @param HtmlFactory $pseudoElement option 'pseudoAfter'
     * @return $this
     */
    public function insertHtmlAfter(HtmlFactory $pseudoElement): static
    {
        $this->setOption('pseudoAfter', $pseudoElement);
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
     * Set floating label on every control in ControlGroup
     * Can be overriden on every control by option 'floatingLabel'
     * @param bool $floatingLabels option 'floatingLabels'
     * @return $this
     */
    public function setFloatingLabels(bool $floatingLabels): static
    {
        $this->setOption('floatingLabels', $floatingLabels);
        return $this;
    }

    /**
     * When true, every control in ControlGroup will be automatically in new input group
     * When false, standard input group behaviour
     * @param bool $inputGroupSingleMode option 'inputGroupSingleMode'
     * @return $this
     */
    public function setInputGroupSingleMode(bool $inputGroupSingleMode): static
    {
        $this->setOption('inputGroupSingleMode', $inputGroupSingleMode);
        return $this;
    }
    /**
     * Set group level grid for ControlGroup
     * Default wrapper is **$wrappers['group']['row']**
     * @param bool|Html|string|null $row option 'row'
     * @return $this
     */
    public function setRow(null|bool|Html|string $row): static
    {
        $this->setOption('row', $row);
        return $this;
    }
    /**
     * Set classes for group level grid wrapper
     * Default wrapper is **$wrappers['group']['row']**
     * @param string $rowClasses option '.row'
     * @return $this
     */
    public function setRowClasses(string $rowClasses): static
    {
        $this->setOption('.row', $rowClasses);
        return $this;
    }

    /**
     * Alias for setRow();
     * Set group level grid for ControlGroup
     * Default wrapper is **$wrappers['group']['row']**
     * @param bool|Html|string|null $row option 'row'
     * @return $this
     */
    public function setGrid(null|bool|Html|string $row): static
    {
        $this->setOption('row', $row);
        return $this;
    }
    /**
     * Alias for setRowClasses();
     * Sets classes for group level grid wrapper
     * Default wrapper is **$wrappers['group']['row']**
     * @param string $rowClasses option '.row'
     * @return $this
     */
    public function setGridClasses(string $rowClasses): static
    {
        $this->setOption('.row', $rowClasses);
        return $this;
    }

    /**
     * Set default column for group level grid
     * Default wrapper is **$wrappers['group']['col']**
     * Can be changed on control by option 'groupCol'
     * @param bool|Html|string|null $col option 'col'
     * @return $this
     */
    public function setColumn(null|bool|Html|string $col): static
    {
        $this->setOption('col', $col);
        return $this;
    }

    /**
     * Set classes for default group level column wrapper
     * Default wrapper is **$wrappers['group']['col']**
     * @param string $colClasses option '.col'
     * @return $this
     */
    public function setColumnClasses(string $colClasses): static
    {
        $this->setOption('.col', $colClasses);
        return $this;
    }

    /**
     * Set container for controls in ControlGroup
     * Default wrapper is **$wrappers['group']['container']**
     * @param bool|Html|string|null $container option 'container'
     * @return $this
     */
    public function setContainer(null|bool|Html|string $container): static
    {
        $this->setOption('container', $container);
        return $this;
    }

    /**
     * Set classes for container for controls in ControlGroup
     * Default wrapper is **$wrappers['group']['container']**
     * @param string $containerClasses option '.container'
     * @return $this
     */
    public function setContainerClasses(string $containerClasses): static
    {
        $this->setOption('.container', $containerClasses);
        return $this;
    }

    /**
     * Sets label for ControlGroup. If HtmlStringable used, only that provided content is rendered
     * If string is provided, it is rendered by default to the **$wrappers['group']['label']**,
     * or to the wrapper provided through option 'labelContainer'
     * @param bool|string|HtmlStringable|null $label option 'label'
     * @return $this
     */
    public function setLabel(null|bool|string|HtmlStringable $label): static
    {
        $this->setOption('label', $label);
        return $this;
    }

    /**
     * Set container for *textual* label of ControlGroup
     * Default wrapper is **$wrappers['group']['label']**
     * @param bool|Html|string|null $labelContainer option 'labelContainer'
     * @return $this
     */
    public function setLabelContainer(null|bool|Html|string $labelContainer): static
    {
        $this->setOption('labelContainer', $labelContainer);
        return $this;
    }

    /**
     * Set classes for container of *textual* label of ControlGroup
     * Default wrapper is **$wrappers['group']['label']**
     * @param string $labelContainerClasses option '.label'
     * @return $this
     */
    public function setLabelContainerClasses(string $labelContainerClasses): static
    {
        $this->setOption('.label', $labelContainerClasses);
        return $this;
    }
    /**
     * Sets description for ControlGroup. If HtmlStringable used, only that provided content is rendered
     * If string is provided, it is rendered by default to the **$wrappers['group']['description']**,
     * or to the wrapper provided through option 'descriptionContainer'
     * @param bool|string|HtmlStringable|null $description option 'description'
     * @return $this
     */
    public function setDescription(null|bool|string|HtmlStringable $description): static
    {
        $this->setOption('description', $description);
        return $this;
    }
    /**
     * Sets classes for container of *textual* description of ControlGroup
     * Default wrapper is **$wrappers['group']['description']**
     * @param bool|Html|string|null $descriptionContainer option 'descriptionContainer'
     * @return $this
     */
    public function setDescriptionContainer(null|bool|Html|string $descriptionContainer): static
    {
        $this->setOption('descriptionContainer', $descriptionContainer);
        return $this;
    }

    /**
     * Sets classes for container of *textual* description of ControlGroup
     * Default wrapper is **$wrappers['group']['description']**
     * @param string $descriptionContainerClasses option '.description'
     * @return $this
     */
    public function setDescriptionContainerClasses(string $descriptionContainerClasses): static
    {
        $this->setOption('.description', $descriptionContainerClasses);
        return $this;
    }

    /**
     * Default is false, if you set it to true, next ControlGroup will be rendered at the end of this
     * ControlGroup wrapper - **$wrappers['group']['container']**
     * @param bool $embedNext option 'embedNext'
     * @return $this
     */
    public function setEmbedNext(bool $embedNext = true): static
    {
        $this->setOption('embedNext', $embedNext);
        return $this;
    }

}