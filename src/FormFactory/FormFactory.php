<?php

namespace Jdvorak23\Bootstrap5FormRenderer\FormFactory;

use Nette\Application\UI\Form;
use Nette\InvalidArgumentException;
use Nette\Utils\ArrayHash;
use ReflectionClass;

abstract class FormFactory
{
	private FormDesign $formDesign;

	protected abstract function createForm(Form $form): void;


	protected function createFormDesign(): FormDesign
	{
		return new FormDesign();
	}


	protected function getFormDesign(): FormDesign
	{
		return $this->formDesign;
	}


	/**
	 * Is cast only if there is no error on the form from base validation
	 * @param Form $form
	 * @param ArrayHash $values
	 * @return void
	 */
	protected function validate(Form $form, ArrayHash $values): void
	{}

	/**
	 * @param Form $form
	 * @param ArrayHash $values
	 * @return void
	 */
	protected function success(Form $form, ArrayHash $values): void
	{}
	/*
	 * Implement to inject dependencies in create method
	 */
	//protected function inject(){}


	public function create(... $parameters): Form
	{
		$form = new Form();
		$this->formDesign = $this->createFormDesign();
		$this->injectParameters(... $parameters);
		$this->createForm($form);
		$this->formDesign->execute($form);
		$form->onValidate[] = function(Form $form, ArrayHash $values) {
			if(!$form->hasErrors()) {
				$this->validate($form, $values);
			}
		};
		$form->onSuccess[] = function (Form $form, ArrayHash $values) {
			$this->success($form, $values);
		};

		return $form;
	}

	private function injectParameters(... $parameters): void
	{
		$rc = new ReflectionClass(static::class);
		if ($rc->hasMethod('inject')) {
			$inject = $rc->getMethod('inject');
			$appliedParameters = [];
			foreach($inject->getParameters() as $parameter){
				$name = $parameter->getName();
				if( ! array_key_exists($name , $parameters)) {
					if ($parameter->isDefaultValueAvailable()) {
						continue;
					}
					throw new InvalidArgumentException("Named parameter '{$name}' must be provided to "
						. static::class . "::create() method, as defined in " . static::class . "::inject() method.");
				}
				/*$type = match(gettype($parameters[$name])){
					'boolean' => 'bool',
					'integer' => 'int',
					'double' => 'float',
					'NULL' => 'null',
					default => gettype($parameters[$name])
				};
				if($type === 'object'){
					$type = get_class($parameters[$name]);
				}
				$parameterType = Type::fromReflection($parameter);
				if(!$parameterType->allows($type)){
					$types = (string) $parameterType;
					throw new InvalidArgumentException("Named parameter '{$name}' must be of type '{$types}', '{$type}' provided.");
				}*/
				$appliedParameters[$name] = $parameters[$name];
			}
			$inject->invokeArgs($this, $appliedParameters);
		}
	}
}