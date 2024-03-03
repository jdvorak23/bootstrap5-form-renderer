<?php
/**
 * Based on https://doc.nette.org/en/forms/rendering#toc-renderer
 * https://api.nette.org/forms/master/Nette/Forms/Rendering/DefaultFormRenderer.html
 * Some methods just copied. Thanks Nette!
 */
declare(strict_types=1);

namespace Jdvorak23\Bootstrap5FormRenderer;


use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers\BaseControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\GroupRenderer;
use Nette;
use Nette\Forms\Container;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\HtmlStringable;
use Nette\Utils\Html;

/**
 * @property Wrappers $wrappers
 * @property ControlRenderer $controlRenderer
 */
class Bootstrap5FormRenderer implements Nette\Forms\FormRenderer {

    use Nette\SmartObject;

    /** @var Wrappers Je místo původního array $wrappers, funguje na stejném principu. */
    protected Wrappers $formWrappers;
    protected ControlRenderer $formControlRenderer;

    protected Form $form;
    public Options $defaultGroupOptions;



    public function __construct(public bool $rows = false,
                                public bool $floatingLabels = false,
                                public bool $inputGroupSingleMode = false,
                                public bool $clientValidation = true,
                                public bool $novalidate = false,
                                public bool $labelsInInputGroup = false)
    {
        $this->defaultGroupOptions = new Options();
    }

    /**
     * Přiřadí několik nastavení z konstruktoru, jinak beze změny.
     * @param  ?string  $mode  'begin', 'errors', 'ownerrors', 'body', 'end' or empty to render all
     */
    public function render(Nette\Forms\Form $form, ?string $mode = null): string {
        if (!isset($this->form) || $this->form !== $form)
            $this->form = $form;
        //--------------------------------------------------------------------------------
        //Nastavení validate
        if ($this->novalidate) {
            $form->getElementPrototype()->setNovalidate("novalidate");
        }
        //--------------------------------------------------------------------------------
        $s = '';
        if (!$mode || $mode === 'begin') {
            $s .= $this->renderBegin(); //Render start form tag, and action attribute if GET
        }
        // Defaultně vypíše ownerrors na celém formu
        if (!$mode || strtolower($mode) === 'ownerrors') {
            $s .= $this->renderErrors();
          // Vypíše všechny chyby na controls najednou,
          // musí být explicitně voláno. Toto beze změny.
        } elseif ($mode === 'errors') {
            $s .= $this->renderErrors(null, false);
        }
        if (!$mode || $mode === 'body') {
            $s .= $this->renderBody();
        }

        if (!$mode || $mode === 'end') {
            $s .= $this->renderEnd();
        }

        return $s;
    }

    /**
     * Stejná jako původní, bez inicializace counteru (odd/even zrušeno).
     * Renders form begin.
     */
    public function renderBegin(): string
    {
        foreach ($this->form->getControls() as $control) {
            $control->setOption('rendered', false);
        }

        //nastavení tagu form, GET
        if ($this->form->isMethod('get')) {
            $el = clone $this->form->getElementPrototype();
            $el->action = (string) $el->action;
            $query = parse_url($el->action, PHP_URL_QUERY) ?: '';
            $el->action = str_replace("?$query", '', $el->action);
            $s = '';
            //hiddens
            foreach (preg_split('#[;&]#', $query, -1, PREG_SPLIT_NO_EMPTY) as $param) {
                $parts = explode('=', $param, 2);
                $name = urldecode($parts[0]);
                $prefix = explode('[', $name, 2)[0];
                if (!isset($this->form[$prefix])) {
                    $s .= Html::el('input', ['type' => 'hidden', 'name' => $name, 'value' => urldecode($parts[1])]);
                }
            }

            return $el->startTag() . ($s ? "\n\t" . $this->getWrapper('hidden container')->setHtml($s) : '');
        } else { //POST
            return $this->form->getElementPrototype()->startTag();
        }
    }

    /**
     * Beze změny
     * Renders form end.
     */
    public function renderEnd(): string {
        $s = '';
        foreach ($this->form->getControls() as $control) {
            if ($control->getOption('type') === 'hidden' && !$control->getOption('rendered')) {
                $s .= $control->getControl();
            }
        }

        if (iterator_count($this->form->getComponents(true, Nette\Forms\Controls\TextInput::class)) < 2) {
            $s .= '<!--[if IE]><input type=IEbug disabled style="display:none"><![endif]-->';
        }

        if ($s) {
            $s =  $this->getWrapper('hidden container')->setHtml($s) . "\n";
        }

        return $s . $this->form->getElementPrototype()->endTag() . "\n";
    }

    /**
     * Beze změny
     * Renders validation errors (per form or per control).
     */
    public function renderErrors(?Nette\Forms\Control $control = null, bool $own = true): string {
        $errors = $control ? $control->getErrors() : ($own ? $this->form->getOwnErrors() : $this->form->getErrors());
        return $this->doRenderErrors($errors, (bool) $control);
    }

    /**
     * Pouze upraveny wrappery
     */
    protected function doRenderErrors(array $errors, bool $control): string {
        if (!$errors) {
            return '';
        }

        $container = $this->getWrapper($control ? 'error container' : 'form errorContainer');
        $item = $this->getWrapper($control ? 'error item' : 'form errorItem');

        foreach ($errors as $error) {
            $item = clone $item;
            if ($error instanceof HtmlStringable) {
                $item->addHtml($error);
            } else {
                $item->setText($error);
            }

            $container->addHtml($item);
        }

        return $control ? "\n\t" . $container->render() : "\n" . $container->render(0);
    }

    /**
     * Tady se řeší renderování jednotlivých Groups
     * Změna generování wrapperů
     * HtmlStringable label se už nevkládá do 'group label'
     */
    public function renderBody(): string {

        $s = $remains = '';

        $translator = $this->form->getTranslator();

        foreach ($this->form->getGroups() as $group) {
            //option 'visual' je defaultně true
            if (!$group->getControls() || !$group->getOption('visual')) {
                continue;
            }
            // HtmlFactory for group
            $htmlFactory = $this->getHtmlFactory($group);

            $container = $htmlFactory->createWrapper('container', 'group container');

            //Id containeru, tj fieldset
            $id = $group->getOption('id');
            if ($id) {
                $container->id = $id;
            }
            // Adds pseudoElement, if any
            $s .= "\n" . $this->getHtmlFactoryFromOption('pseudoBefore', $htmlFactory->options)?->getPseudoContent();
            //StartTag
            $s .= "\n" . $container->startTag();
            //Group label, container v group label
            $label = $htmlFactory->options->getOption('label');
            if ($label instanceof HtmlStringable) {
                $s .= $label;
            } elseif ($label !== null && $label !== false) {
                if ($translator !== null) {
                    $label = $translator->translate($label);
                }
                $s .= "\n" . $htmlFactory->createWrapper('labelContainer', 'group label', '.label')->setText($label) . "\n";
            }
            //Group description
            $description = $group->getOption('description');
            if ($description instanceof HtmlStringable) {
                $s .= $description;
            } elseif ($description !== null && $description !== false) {
                if ($translator !== null) {
                    $description = $translator->translate($description);
                }
                $s .= $htmlFactory->createWrapper('descriptionContainer', 'group description', '.description')->setText($description) . "\n";
            }

            //Renderování jednotlivých controls v group
            $s .= $this->renderControls($group);

            //Když je u nějakého tohle option 'embedNext', vloží další group POD tuhle group tj. fieldsed ve fieldsetu
            $remains = $container->endTag()
                . "\n" // Adds pseudoElement, if any
                . $this->getHtmlFactoryFromOption('pseudoAfter', $htmlFactory->options)?->getPseudoContent()
                . $remains;
            if (!$group->getOption('embedNext')) {
                $s .= $remains;
                $remains = '';
            }
        }

        //Renderování controls, co nejsou v group
        $s .= $remains . $this->renderControls($this->form);

        $container = $this->getWrapper('form container');
        $container->setHtml($s);
        return (string) $container;
    }

    /**
     * Vyrenderování elementů jednotlivých Group nebo zbylých controls.
     * @param Container|ControlGroup $parent
     * @return string
     */
    public function renderControls(Container|ControlGroup $parent): string
    {
        // Unification of options for rest controls in From being like Group:
        $groupHtmlFactory = $parent instanceof Nette\Forms\Container
            ? $this->getHtmlFactory($this->defaultGroupOptions)
            : $this->getHtmlFactory($parent);

        // Default Group grid, from option if set, else from constructor:
        if($groupHtmlFactory->options->getOption('row') === null)
            $groupHtmlFactory->options->setOption('row', $this->rows);

        // Default settings of some options which BaseControlRenderer needs to work properly:
        $elementHtmlFactories = [];
        // Need to save previous element for input group settings
        $prevHtmlFactory = null;
        // Need to save if previous iterated element is in input group - to properly set input group
        $inInputGroup = false;
        // Default setting of floating labels for group. It is taken from group option 'floatingLabels',
        // or if not set, then from constructor $floatingLabels parameter.
        $defaultFloatingLabels = $this->getBoolOption('floatingLabels', $groupHtmlFactory, $this->floatingLabels);
        // Similar for 'inputGroupSingleMode' setting
        $singleMode = $this->getBoolOption('inputGroupSingleMode', $groupHtmlFactory, $this->inputGroupSingleMode);
        // Similar for 'labelsInInputGroup' setting
        $labelsInInputGroup = $this->getBoolOption('labelsInInputGroup', $groupHtmlFactory, $this->labelsInInputGroup);
        // Iterates all controls and set default settings to them
        foreach ($parent->getControls() as $control) {
            if(!$control instanceof BaseControl)
                throw new \TypeError("Only controls inherited from Nette's BaseControl are supported!");
            if ($control->getOption('rendered') || $control->getOption('type') === 'hidden' || $control->getForm(false) !== $this->form)
                continue;

            // Get control's HtmlFactory
            $htmlFactory = $this->getHtmlFactory($control);

            // Pseudo elements Before
            foreach($this->getPseudoElements('pseudoBefore', $htmlFactory->options) as $elementHtmlFactory){
                $inInputGroup = $this->setInputGroup($elementHtmlFactory, $prevHtmlFactory, $singleMode, $inInputGroup);
                $prevHtmlFactory = $elementHtmlFactory;
                $elementHtmlFactories[] = ['factory' => $elementHtmlFactory, 'pseudo' => true];
            }

            // Setting up input group
            $inInputGroup = $this->setInputGroup($htmlFactory, $prevHtmlFactory, $singleMode, $inInputGroup);
            $prevHtmlFactory = $htmlFactory;

            // Setting floating label for control.
            $this->fillBoolOption('floatingLabel', $htmlFactory, $defaultFloatingLabels);
            // Setting if label is in input gorup
            $this->fillBoolOption('labelInInputGroup', $htmlFactory, $labelsInInputGroup);

            // Setting of 'clientValidation'. If not set on control, take value from constructor $clientValidation parameter
            $this->fillBoolOption('clientValidation', $htmlFactory, $this->clientValidation);
            // Add control to elements array
            $elementHtmlFactories[] = ['factory' => $htmlFactory, 'pseudo' => false, 'control' => $control];

            // Pseudo elements After
            foreach($this->getPseudoElements('pseudoAfter', $htmlFactory->options) as $elementHtmlFactory){
                $inInputGroup = $this->setInputGroup($elementHtmlFactory, $prevHtmlFactory, $singleMode, $inInputGroup);
                $prevHtmlFactory = $elementHtmlFactory;
                $elementHtmlFactories[] = ['factory' => $elementHtmlFactory, 'pseudo' => true];
            }
        }
        // If there are no elements, do not render void group wrappers
        if(empty($elementHtmlFactories))
            return '';

        // Finally rendering:
        $groupRenderer = new GroupRenderer($groupHtmlFactory);
        foreach($elementHtmlFactories as $element){
            /**@var HtmlFactory $htmlFactory*/
            $htmlFactory = $element['factory'];
            $wrapper = $groupRenderer->getWrapper($htmlFactory);
            if($element['pseudo']){
                $wrapper->addHtml($htmlFactory->getPseudoContent());
            }else{
                $this->renderControl($element['control'], $wrapper);
            }
        }
        // Vyrenderuje celou group
        return $groupRenderer->getContainer()->render(0);
    }

    protected function getPseudoElements(string $option, Options $options): array
    {
        $elements = [];
        $values = $options->getOption($option);
        if(!is_array($values))
            $values = [$values];
        foreach($values as $value){
            if($value instanceof HtmlFactory) {
                // init
                $factory = clone $value;
                $factory->wrappers = $factory->wrappers ?? $this->wrappers;
                $elements[] = $factory;
            }
        }
        return $elements;
    }

    /**
     * Vyrenderuje $control (a jeho 'dolní' wrappery) do wrapperu $container
     * @param BaseControl $control
     * @param Html $container
     */
    protected function renderControl(BaseControl $control, Html $container): void
    {
        $this->controlRenderer->render($control, $container);
    }
//------
    /**
     * Vrací wrapper definovaný v poli $wrappers, reprezentovaný klíči ve stringu oddělené mezerou.
     * V případě, že  wrapper neexistuje, vrací Html::el() (documentFragment)
     * @param string $name např. 'group container' => $wrappers['group']['container']
     * @return Html vytvořený wrapper
     */
    public function getWrapper(string $name): Html
    {
        return $this->wrappers->getWrapper($name)?: Html::el();
    }

    protected function getHtmlFactory(BaseControl|ControlGroup|Options $for): HtmlFactory
    {
        $renderer = $for->getOption('renderer');
        $htmlFactory = $for->getOption('htmlFactory');
        if($for instanceof BaseControl && $renderer instanceof BaseControlRenderer && $renderer->htmlFactory){
            $htmlFactory = clone $renderer->htmlFactory;
        }elseif(!$htmlFactory instanceof HtmlFactory){
            $htmlFactory = new HtmlFactory();
        }else{
            $htmlFactory = clone $htmlFactory;
        }
        if($for instanceof BaseControl && $renderer instanceof BaseControlRenderer){
            $renderer = clone $renderer;
            $for->setOption('renderer', $renderer);
        }
        $for->setOption('htmlFactory', $htmlFactory);
        //init
        $htmlFactory->wrappers = $htmlFactory->wrappers ?? $this->wrappers;
        $htmlFactory->options->setSourceOptions($for instanceof Options ? $for->getOptions() : $for);
        return $htmlFactory;
    }

    protected function getHtmlFactoryFromOption(string $option, Options $options): HtmlFactory|null
    {
        $htmlFactory = $options->getOption($option);
        if(!$htmlFactory instanceof HtmlFactory)
            return null;
        //init
        $htmlFactory = clone $htmlFactory;
        $htmlFactory->wrappers = $htmlFactory->wrappers ?? $this->wrappers;
        return $htmlFactory;
    }

    /**
     * None of options is required. For true / false only options we sometime need to set these not set (null) options to some default value
     * Also, when value of option should be only true / false this converts other than strict false values to boolean true
     * @param string $option name of the option
     * @param HtmlFactory $htmlFactory
     * @param bool $default
     * @return void
     */
    protected function fillBoolOption(string $option, HtmlFactory $htmlFactory, bool $default): void
    {
        $value = $this->getBoolOption($option, $htmlFactory, $default);
        $htmlFactory->options->setOption($option, $value);
    }

    /**
     * Get bool from option  which is intended to be boolean. If it is not set (null), default will be taken
     * All other values than false and null are converted to true
     * @param string $option
     * @param HtmlFactory $htmlFactory
     * @param bool $default if option value is null, it returns $default
     * @return bool
     */
    protected function getBoolOption(string $option, HtmlFactory $htmlFactory, bool $default): bool
    {
        $optionValue = $htmlFactory->options->getOption($option);
        return $optionValue === null ? $default : $optionValue !== false;
    }

    /**
     * Sets all needed for input group.
     * @param HtmlFactory $htmlFactory
     * @param HtmlFactory|null $prevHtmlFactory
     * @param bool $singleMode mode of input group creating, true means every control is in its own input group by default
     * @param bool $inInputGroup if previous is in input group
     * @return bool if control is in input group
     */
    protected function setInputGroup(HtmlFactory $htmlFactory, HtmlFactory|null $prevHtmlFactory, bool $singleMode, bool $inInputGroup): bool
    {
        // Nastavení inputGroup
        $inputGroup = $htmlFactory->options->getOption('inputGroup');
        // Stavy mohou znamenat něco jiného podle inputGroupSingleMode
        if ($inputGroup === null) { //Pokud je null, tak záleží na předchozím controlu, jestli je nebo není v inputGroup.
            if($singleMode){ // Pokud je null a je singleMode, vytváří zde inputGroup
                $htmlFactory->options->setOption('firstInInputGroup', true);
                $htmlFactory->options->setOption('lastInInputGroup', true);
                $htmlFactory->options->setOption('inputGroup', true); // Přenastavuje option podle reality vykreslování
                $inInputGroup = true;
            }elseif($inInputGroup) { // Jinak, pokud je v inputGroup, pak předchozí není last, jako last je nastaven aktuálně iterovaný control.
                $prevHtmlFactory?->options->setOption('lastInInputGroup', false);
                $htmlFactory->options->setOption('lastInInputGroup', true);
            }
        } elseif ($inputGroup === false) { //Není v inputGroup v každém případě
            $inInputGroup = false;
        }else{ // Pokud je 'inputGroup' nastavena na true, singleMode je zapnuté a je existující inputGroup, pak se do ní vloží
            if($inputGroup === true && $singleMode && $inInputGroup){
                $prevHtmlFactory?->options->setOption('lastInInputGroup', false);
                $htmlFactory->options->setOption('lastInInputGroup', true);
                // Přenastavuje option podle reality vykreslování:
                $htmlFactory->options->setOption('inputGroup', null);
                // BaseControl nenastaví null do 'inputGroup', ale unset, musí se přenastavit i v default:
                $htmlFactory->options->setDefaultOption('inputGroup', null);
            }else { //Jinak je v inputGroup, a je první v této inputGroup - nastavení first a last
                $htmlFactory->options->setOption('firstInInputGroup', true);
                $htmlFactory->options->setOption('lastInInputGroup', true);
                $inInputGroup = true;
            }
        }
        return $inInputGroup;
    }

    public function getWrappers(): Wrappers
    {
        $this->formWrappers = $this->formWrappers ?? new Wrappers();
        return $this->formWrappers;
    }
    public function setWrappers(Wrappers $wrappers): void
    {
        $this->formWrappers = $wrappers;
    }
    public function getControlRenderer(): ControlRenderer
    {
        $this->formControlRenderer = $this->formControlRenderer ?? new ControlRenderer();
        return $this->formControlRenderer;
    }
    public function setControlRenderer(ControlRenderer $controlRenderer): void
    {
        $this->formControlRenderer = $controlRenderer;
    }
}
