<?php

namespace Jdvorak23\Bootstrap5FormRenderer\FormFactory;

use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Form;

trait FormBuild
{
	protected function addHiddenId(Container $container, string $idName = 'id'): HiddenField
	{
		return $container->addHidden($idName)
			->setNullable()
			->addFilter(function ($value) {
				return $value ? (int) $value : null; // todo retyp?
			});
	}


	/**
	 * Nastavení required pro $control
	 * @param BaseControl $control
	 * @param bool $required
	 * @param string $message
	 * @param BaseControl|null $conditionControl
	 * @param mixed $conditionValue
	 * @return BaseControl
	 */
	protected function setRequired(
		BaseControl $control,
		bool $required = true,
		string $message = '',
		?BaseControl $conditionControl = null,
		mixed $conditionValue = ''
	): BaseControl
	{
		if ($required) {
			if ($conditionControl) {
				$control->addConditionOn($conditionControl, Form::EQUAL, $conditionValue)
					->setRequired($message);
			} else {
				$control->setRequired($message);
			}
		} elseif ($control instanceof TextBase){
			$control->setNullable();
		}
		return $control;
	}


	/**
	 * Přidá error na $control, ale jen pokud už na něm error není, aby se nekupily error messages
	 * @param BaseControl $control
	 * @param string $message
	 * @return void
	 */
	protected function addErrorIfNo(BaseControl $control, string $message): void
	{
		if ( ! $control->hasErrors()) {
			$control->addError($message);
		}
	}
}