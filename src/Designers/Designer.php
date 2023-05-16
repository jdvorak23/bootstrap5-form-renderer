<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Designers;
use Jdvorak23\Bootstrap5FormRenderer\Designers\Multi\MultiControlDesigner;
use Jdvorak23\Bootstrap5FormRenderer\Options;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;

class Designer
{
    public function __construct(protected Form $form)
    {}

    public static function control(Options|null $options = null): ControlDesigner
    {
        return new ControlDesigner($options);
    }

    public static function forGroup(Options|null $options = null): GroupDesigner
    {
        return new GroupDesigner($options);
    }

    public static function pseudo(Options|null $options = null): PseudoDesigner
    {
        return new PseudoDesigner($options);
    }
    public function __invoke(string|BaseControl|null $control = null): ControlDesigner|MultiControlDesigner
    {
        if($control === null)
            return new ControlDesigner();
        if(is_string($control)){
            if(str_contains($control, ' '))
                return $this->getMultiControlDesigner($control);
            $options = $this->createOptionFor($this->getControl($control));
        }else
            $options = $this->createOptionFor($control);

        return new ControlDesigner($options);
    }

    public function all(string|int|null $onlyOfGroup = null, array|string|null $onlyOfTypesOrClasses = null, array|string|null $notOfTypesOrClasses = null): ControlDesigner|MultiControlDesigner
    {
        $options = [];
        $controls = $onlyOfGroup !== null ? $this->getGroup($onlyOfGroup)->getControls() : $this->form->getControls();

        $only = $onlyOfTypesOrClasses ?? [];
        $only = is_array($only) ? $only : preg_split('/ /', $onlyOfTypesOrClasses, -1, PREG_SPLIT_NO_EMPTY);
        $not = $notOfTypesOrClasses ?? [];
        $not = is_array($not) ? $not : preg_split('/ /', $notOfTypesOrClasses, -1, PREG_SPLIT_NO_EMPTY);

        foreach($controls as $control){
            if($control->getOption('type') === 'hidden')
                continue;
            if($only && !$this->isOfTypeOrClass($control, $only))
                continue;
            if($not && $this->isOfTypeOrClass($control, $not))
                continue;
            $options[] = $this->createOptionFor($control);
        }
        if(!count($options))
            return new ControlDesigner();
        elseif(count($options) == 1)
            return new ControlDesigner($options[0]);

        return new MultiControlDesigner(...$options);
    }

    public function group(string|int|ControlGroup|null $group = null): GroupDesigner
    {
        if($group === null)
            return new GroupDesigner();
        $group = (is_string($group) || is_int($group)) ? $this->getGroup($group) : $group;
        return new GroupDesigner($this->createOptionFor($group));
    }
    protected function getMultiControlDesigner(string $controls): MultiControlDesigner
    {
        $names = preg_split('/ /', $controls, -1, PREG_SPLIT_NO_EMPTY);
        $options = [];
        foreach ($names as $name){
            $options[] = $this->createOptionFor($this->getControl($name));
        }
        return new MultiControlDesigner(...$options);
    }

    protected function isOfTypeOrClass(BaseControl $control, array $typesOrClasses): bool
    {
        if(in_array($control->getOption('type'), $typesOrClasses))
            return true;
        if(in_array(get_class($control), $typesOrClasses))
            return true;
        return false;
    }
    protected function getControl(string $name): BaseControl
    {
        $control = $this->form->getComponent($name, false);
        if(!$control instanceof BaseControl)
            throw new \InvalidArgumentException("Control with name '$name' does not exist");
        return $control;
    }
    protected function getGroup(string|int $name): ControlGroup
    {
        $controlGroup = $this->form->getGroup($name);
        if(!$controlGroup)
            throw new \InvalidArgumentException("Group '$name' does not exist");
        return $controlGroup;
    }

    protected function createOptionFor(BaseControl|ControlGroup $optionFor): Options
    {
        $options = new Options();
        $options->setSourceOptions($optionFor);
        return $options;
    }
}