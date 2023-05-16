<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers;

use Jdvorak23\Bootstrap5FormRenderer\HtmlFactory;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\BaseControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\ButtonRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\CheckBoxRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\FloatingControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\ListControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\StandardControlRenderer;
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

    /**
     * Renders control with its default renderer, or with renderer provided through option 'renderer'
     */
    public function render(Nette\Forms\Controls\BaseControl $control, Html $container) : void
    {
        // HtmlFactory has been already cloned, and is always prepared, no need to check
        /**@var HtmlFactory $htmlFactory */
        $htmlFactory = $control->getOption('htmlFactory');
        if($htmlFactory->options->getOption('renderer') instanceof BaseControlRenderer){
            // Own renderer has been already cloned
            $renderer = $htmlFactory->options->getOption('renderer');
        }elseif ($control instanceof CheckboxList || $control instanceof RadioList) {
            $renderer = new ListControlRenderer();
        } elseif ($control instanceof Checkbox) {
            $renderer = new CheckBoxRenderer();
        } elseif ($control instanceof Button) {
            $renderer = new ButtonRenderer();
        } elseif($control instanceof TextBase || $control instanceof SelectBox) {
            $renderer = new FloatingControlRenderer();
        } else { // MultiSelectBox || UploadControl || BaseControl
            $renderer = new StandardControlRenderer();
        }
        // Render of control
        $renderer->render($control, $container);
        // Have to be set
        $control->setOption('rendered', true);
    }
}