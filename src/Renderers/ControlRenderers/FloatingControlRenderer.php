<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;

/**
 * Renderování control, který podporuje floatingLabel.
 */
class FloatingControlRenderer extends StandardControlRenderer
{
    protected bool $floatingLabelAllowed = true;
    public function __construct(Wrappers $wrappers, BaseControl $control)
    {
        parent::__construct($wrappers, $control);
        // Nastaví placeholder bro bootstrap, placeholder musí být aby fungoval floating label
        if($this->floatingLabel){
            if (is_object($this->control->control) && !array_key_exists("placeholder", $this->control->control->attrs))
                $this->control->setHtmlAttribute("placeholder", $this->control->getCaption());
        }

    }

    protected function renderControl(Html $container){
        if($this->floatingLabel) {
            $this->renderParent();
            $this->renderLabel($this->parent);
        }else{
            $this->renderLabel($this->parent);
            $this->renderParent();
        }
        $this->renderDescription($this->parent, 'description container');
        $this->renderErrors($this->parent);
    }
    protected function renderToGroup(Html $container){
        $this->inputGroupWrapper = $this->getWrapper("wrapper", 'inputGroup wrapper grow', $container);
        if($this->floatingLabel) {
            $this->renderParent($this->inputGroupWrapper);
            $this->renderLabel($this->parent);
        }else{
            $this->renderLabel($this->parent);
            $this->renderParent($this->inputGroupWrapper);
        }
        $this->renderDescription($this->parent, 'description inputGroupContainer');
        $this->renderErrors($this->inputGroupWrapper);
    }
    protected function createParentElement(): Html {
        if ($this->inputGroup)
            return $this->floatingLabel
                ? $this->getWrapper("parent", 'inputGroup container floating')
                : $this->getWrapper("parent", 'inputGroup container standard') ;
        else
            return $this->floatingLabel
                ? $this->getWrapper("parent", 'pair floatingLabelContainer')
                : $this->getWrapper("parent", 'pair container') ;
    }

}