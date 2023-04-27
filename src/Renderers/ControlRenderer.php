<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers;


use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\ButtonRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\CheckBoxRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\FloatingControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\ListControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\StandardControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\Html;
use Nette;

class ControlRenderer
{
    protected Wrappers $wrappers;
    public function __construct(Wrappers $wrappers)
    {
        $this->wrappers = $wrappers;
    }

    /**
     * Vyrenderuje $control do jeho adekvátních wrapperů, podle nastavení a podle typu control.
     */
    public function render(Nette\Forms\Controls\BaseControl $control, Html $container) : void
    {
        if ($control instanceof CheckboxList || $control instanceof RadioList) {
            $renderer = new ListControlRenderer($this->wrappers, $control);
        } elseif ($control instanceof Checkbox) {
            $renderer = new CheckBoxRenderer($this->wrappers, $control);
        } elseif ($control instanceof Button) {
            $renderer = new ButtonRenderer($this->wrappers, $control);
        } elseif($control instanceof TextBase || $control instanceof SelectBox) {
            $renderer = new FloatingControlRenderer($this->wrappers, $control);
        } else { // MultiSelectBox || UploadControl || BaseControl
            $renderer = new StandardControlRenderer($this->wrappers, $control);
        }
        // Renderování control.
        $renderer->render($container);
        // Control je již vyrenderovaný.
        $control->setOption('rendered', true);
    }
}