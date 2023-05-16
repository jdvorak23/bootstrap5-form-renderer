<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers\Traits;
use Nette\Utils\Html;

trait PseudoDesign
{
    use ComponentDesign;

    /**
     * Sets single group level grid column
     * Default wrapper for this option is **$wrappers['group']['col']**
     * @param bool|Html|string|null $groupCol sets option 'groupCol'
     * @return $this
     */
    public function setGroupColumn(null|bool|Html|string $groupCol): static
    {
        $this->setOption('groupCol', $groupCol);
        return $this;
    }

    /**
     * Adds classes to single group level grid column
     * Default wrapper for this option is **$wrappers['group']['col']**
     * @param string $groupColClasses sets option '.groupCol'
     * @return $this
     */
    public function setGroupColumnClasses(string $groupColClasses): static
    {
        $this->setOption('.groupCol', $groupColClasses);
        return $this;
    }

    /**
     * Set control level grid start / end on control
     * Default wrapper is **$wrappers['controls']['row']**
     * Alias for setRow
     * @param bool|Html|string|null $row option 'row'
     * @return $this
     */
    public function setGrid(null|bool|Html|string $row = true): static
    {
        return $this->setRow($row);
    }
    /**
     * Set classes for control level grid wrapper
     * Default wrapper is **$wrappers['controls']['row']**
     * Alias for setRowClasses
     * @param string $rowClasses option '.row'
     * @return $this
     */
    public function setGridClasses(string $rowClasses): static
    {
        return $this->setRowClasses($rowClasses);
    }

    /**
     * Set control level grid start / end on control
     * Default wrapper is **$wrappers['controls']['row']**
     * @param bool|Html|string|null $row option 'row'
     * @return $this
     */
    public function setRow(null|bool|Html|string $row = true): static
    {
        $this->setOption('row', $row);
        return $this;
    }
    /**
     * Set classes for control level grid wrapper
     * Default wrapper is **$wrappers['controls']['row']**
     * @param string $rowClasses option '.row'
     * @return $this
     */
    public function setRowClasses(string $rowClasses): static
    {
        $this->setOption('.row', $rowClasses);
        return $this;
    }

    /**
     * Set default column wrapper for control level grid
     * Works and have sense only on first control (or pseudo), where control level grid is set
     * If not set, default wrapper is taken from **$wrappers['controls']['col']**
     * Can be changed on every control individually by option 'col'
     * @param bool|Html|string|null $defaultCol option 'defaultCol'
     * @return $this
     */
    public function setDefaultColumn(null|bool|Html|string $defaultCol): static
    {
        $this->setOption('defaultCol', $defaultCol);
        return $this;
    }

    /**
     * Add classes to **default** column wrapper for control level grid
     * Works and have sense only on first control (or pseudo), where control level grid is set
     * If **'defaultCol'** is set, add classes to it (not much sense use this way)
     * If **'defaultCol'** is not set, add classes to default wrapper, which is **$wrappers['controls']['col']**
     * @param string $defaultColClasses option '.defaultCol'
     * @return $this
     */
    public function setDefaultColumnClasses(string $defaultColClasses): static
    {
        $this->setOption('.defaultCol', $defaultColClasses);
        return $this;
    }
    /**
     * Set column wrapper for control level grid for this control
     * Default wrapper is **$wrappers['controls']['col']** or what was set by **option 'defaultCol'**
     * @param bool|Html|string|null $col option 'col'
     * @return $this
     */
    public function setColumn(null|bool|Html|string $col): static
    {
        $this->setOption('col', $col);
        return $this;
    }

    /**
     * Set classes for column wrapper for control level grid
     * @param string $colClasses option '.col'
     * @return $this
     */
    public function setColumnClasses(string $colClasses): static
    {
        $this->setOption('.col', $colClasses);
        return $this;
    }
    /**
     * If **inputGroupSingleMode** is false, option 'inputGroup' starts (true|Html|string) input group,
     *   ends (false) it, or it depends on previous control (null) - if previous control is in input group,
     *   this will be added to it, else this won't be in input group
     * If **inputGroupSingleMode** is true, option 'inputGroup' starts (null|Html|string) input group,
     *   ends (false) it, or it depends on previous control (true) - if previous control is in input group,
     *   this will be added to it, else this starts new input group
     * Default wrapper is **$wrappers['controls']['inputGroup']**
     * @param bool|Html|string|null $inputGroup option 'inputGroup'
     * @return $this
     */
    public function setInputGroup(null|bool|Html|string $inputGroup = true): static
    {
        $this->setOption('inputGroup', $inputGroup);
        return $this;
    }
    /**
     * Set classes for input group wrapper
     * Default wrapper is **$wrappers['controls']['inputGroup']**
     * Works and have sense only on first control (or pseudo) in given input group
     * @param string $inputGroupClasses option '.inputGroup'
     * @return $this
     */
    public function setInputGroupClasses(string $inputGroupClasses): static
    {
        $this->setOption('.inputGroup', $inputGroupClasses);
        return $this;
    }
    /**
     * Every button, which is **not** in input group, is automatically added to this wrapper
     * If there are more buttons one-by-one, they are automatically added to the **same** wrapper
     * You can change it by setting this 'buttonGroup' on button type control
     * Default wrapper is **$wrappers['controls']['buttons']**
     * @param bool|Html|string|null $buttonGroup option 'buttonGroup'
     * @return $this
     */
    public function setButtonGroup(null|bool|Html|string $buttonGroup): static
    {
        $this->setOption('buttonGroup', $buttonGroup);
        return $this;
    }
    /**
     * Set classes of button group wrapper
     * Default wrapper is **$wrappers['controls']['buttons']**
     * Works and have sense only on first control (or pseudo) in given button group
     * @param string $buttonGroupClasses option '.buttonGroup'
     * @return $this
     */
    public function setButtonGroupClasses(string $buttonGroupClasses): static
    {
        $this->setOption('.buttonGroup', $buttonGroupClasses);
        return $this;
    }
}