<?php

namespace Jdvorak23\Bootstrap5FormRenderer\FormFactory;

use Jdvorak23\Bootstrap5FormRenderer\Bootstrap5FormRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Designers\Designer;
use Jdvorak23\Bootstrap5FormRenderer\HtmlFactory;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Arrays;
use Nette\Utils\Html;

class FormDesign
{
	private array $designs = [];

	protected Form $form;
	protected Designer $designer;

	protected Bootstrap5FormRenderer $renderer;


	/**
	 * Přepsat pro konkrétní nastavení rendereru
	 * @return void
	 */
	protected function setRenderer(): void
	{
		$this->renderer = new Bootstrap5FormRenderer();
		$this->form->setRenderer($this->renderer);
	}


	/**
	 * Přepsat pro nastavení designu konkrétního formuláře
	 * @return void
	 */
	protected function setDesign(): void
	{

	}


	/**
	 * @param Form $form
	 * @return void
	 * @internal
	 */
	public final function execute(Form $form): void
	{
		$this->form = $form;
		$this->designer = new Designer($form);
		$this->setRenderer();
		$this->setDesign();
		// Predefined designs
		Arrays::invoke($this->designs, $this->designer, $form);
	}


	/**
	 * Make a piece of design. Callback stored and called when executed,
	 * or called immediately if already executed
	 * @param callable $design called with parameters (Designer, Form)
	 * @return void
	 */
	public function addDesign(callable $design): void
	{
		if (isset($this->designer)) {
			$design($this->designer, $this->form);
		} else {
			$this->designs[] = $design;
		}
	}


	/**
	 * Vloží nadpis h3 před daný $control tím, že přidá pseudo element před
	 * @param string $title
	 * @param BaseControl $control
	 * @return void
	 */
	public function setTitleBefore(string $title, BaseControl $control): void
	{
		$title = Html::el('h3 class="mb-0"')
			->setHtml($title);
		$titlePseudo = Designer::pseudo()
			->setContent($title)
			->setGroupColumn('div class="col-12"');

		$this->addDesign(function (Designer $design) use ($titlePseudo, $control) {
			$design($control)
				->insertHtmlBefore(new HtmlFactory($titlePseudo));
		});
	}
}