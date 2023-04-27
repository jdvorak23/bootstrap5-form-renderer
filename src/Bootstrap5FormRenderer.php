<?php
/**
 * Části kódu jsou zcela totožné nebo jen mírně upravené od původního Nette\Forms\Rendering\DefaultFormRenderer
 * That file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare(strict_types=1);

namespace Jdvorak23\Bootstrap5FormRenderer;


use Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderer;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\GroupRenderer;
use Nette;
use Nette\Forms\ControlGroup;
use Nette\Forms\Form;
use Nette\HtmlStringable;
use Nette\Utils\Html;
/**
 * Funguje na stejném principu jako DefaultFormRenderer.
 * Funkcionalita odd / even, tj. vlastní třída pro .odd elementy zrušena, nemá už podle mě smysl.
 * Původní renderer generuje label pro control vždy, i když je caption null nebo false. Tento v případě těchto hodnot label negeneruje.
 *  Pokud chceme label na nějaký suffix, nastavíme caption na true.
 *  !!V případě, že nastavíme option 'label' na cotrolu (HtmlStringable), bude se brát tento námi dodaný label
 *  a $caption při vytváření nebude bráno v potaz (i kdyby bylo false).
 * -----------------------------------Group options--------------------------------
 * Option 'visual' na Group funguje beze změny - defaultně true, pokud se nastaví false,
 *  případné controls se vykreslí jako by nebyly v Group (na konci formuláře se zbylými controls mimo Group).
 * Option 'container' na Group funguje beze změny - definuje vlastní container,
 *  místo defaultního $wrappers['group']['container']. Html, nebo string pro Html:el(string).
 * Option 'label' na Group beze změny - automaticky vkládáno jako druhý parametr $caption při Form::AddGroup().
 *  !Vždy se vykresluje do $wrappers['group']['label'], i při zadání vlastního HtmlStringable.
 * Option 'embedNext' na Group beze změny, vykreslí další group do této group, tj. do stejného $wrappers['group']['container'].
 * Option 'description' na Group beze změny. Oproti labelu, pokud je zadáno HtmlStringable,
 *  pak ho neobaluje do standardního $wrappers['group']['description'].
 * Nové option 'row' na Group - nastavuje wrapper, který je případně okolo všech elementů v dané Group
 *                              null => nenastaveno, default (podle $row v konstruktoru)
 *                              false => nikdy nebude
 *                              true => bude defaultní z $wrappers['group']['row']
 *                              Html => použije se tento vlastní wrapper
 *                              string => použije se Html::el(string)
 * Nové option 'col' na Group - !smysl! má jen v případě, že existuje předchozí 'row' wrapper, jinak se nikdy negeneruje!
 *  Obalí každý podřízený wrapper do "svého" wrapperu 'col'. Tj. slouží primárne k vytvoření grid, ale může být použito i jinak.
 *                              null,true => vytvoří default wrapper z $wrappers['group']['col']
 *                              false => nebude se defaultně vytvářet pro žádný element v Group
 *                              Html => pro tuto group stanoví tento jako defaultní
 *                              string => použije se Html::el(string) pro tuto group jako defaultní
 *  Pozn. každý podřízený element v tomto Group 'row' si může pro sebe upravit tento 'col', přes option 'groupCol' na jednotlivém control.
 * -----------------------------------Control options--------------------------------
 * Option NextTo předělané
 * Converts a Form into the HTML output.
 */
class Bootstrap5FormRenderer implements Nette\Forms\FormRenderer {

    use Nette\SmartObject;

    /** @var Wrappers Je místo původního array $wrappers, funguje na stejném principu. Více info v komentářích třídy Wrappers */
    public Wrappers $wrappers;

    /** @var Nette\Forms\Form */
    protected Form $form;

    public ControlRenderer $controlRenderer;


    public function __construct(public bool $rows = false,
                                public bool $floatingLabels = false,
                                public bool $inputGroupSingleMode = false,
                                public bool $clientValidation = true,
                                public bool $novalidate = false)
    {
        $this->wrappers = new Wrappers();
    }

    /**
     * Pomůcka co nastaví na všech controls v $group dané $option na $value.
     * Nastavuje pouze v případě, že dané option na controlu není nastaveno!
     * @param ControlGroup $group
     * @param string $option
     * @param $value
     * @return void
     */
    public static function setAllControlsInGroup(Nette\Forms\ControlGroup $group, string $option, $value) : void
    {
        foreach ($group->getControls() as $control)
        {
            if($control->getOption('type') === 'hidden')
                continue;
            if(is_null($control->getOption($option)))
                $control->setOption($option, $value);
        }
    }

    /**
     * Přiřadí několik nastavení z konstruktoru, jinak beze změny.
     * @param  string  $mode  'begin', 'errors', 'ownerrors', 'body', 'end' or empty to render all
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
    private function doRenderErrors(array $errors, bool $control): string {
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
     * Beze změny
     */
    public function renderBody(): string {

        $s = $remains = '';

        $defaultContainer = $this->getWrapper('group container');
        $translator = $this->form->getTranslator();

        foreach ($this->form->getGroups() as $group) {
            //option 'visual' je defaultně true
            if (!$group->getControls() || !$group->getOption('visual')) {
                continue;
            }
            //Container pro groups, tj. fieldset, může být vlastní v option 'container'
            $container = $group->getOption('container', $defaultContainer);
            $container = $container instanceof Html ? clone $container : Html::el($container);
            //Id containeru, tj fieldset
            $id = $group->getOption('id');
            if ($id) {
                $container->id = $id;
            }
            //StartTag
            $s .= "\n" . $container->startTag();
            //Group label, container v group label
            $text = $group->getOption('label');
            if ($text instanceof HtmlStringable) {
                $s .= $this->getWrapper('group label')->addHtml($text);
            } elseif ($text != null) { // intentionally ==
                if ($translator !== null) {
                    $text = $translator->translate($text);
                }

                $s .= "\n" . $this->getWrapper('group label')->setText($text) . "\n";
            }
            //Group description
            $text = $group->getOption('description');
            if ($text instanceof HtmlStringable) {
                $s .= $text;
            } elseif ($text != null) { // intentionally ==
                if ($translator !== null) {
                    $text = $translator->translate($text);
                }

                $s .= $this->getWrapper('group description')->setText($text) . "\n";
            }

            //Renderování jednotlivých controls v group
            $s .= $this->renderControls($group);

            //Když je u nějakého tohle option 'embedNext', vloží další group POD tuhle group tj. fieldsed ve fieldsetu
            $remains = $container->endTag() . "\n" . $remains;
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
     * Kompletně přepsáno.
     * @param  Nette\Forms\Container|Nette\Forms\ControlGroup  $parent
     */
    public function renderControls($parent): string {
        if (!($parent instanceof Nette\Forms\Container || $parent instanceof Nette\Forms\ControlGroup)) {
            throw new Nette\InvalidArgumentException('Argument must be Nette\Forms\Container or Nette\Forms\ControlGroup instance.');
        }
        // Nastavení inputGroup - nejdříve nutno proiterovat všechna controls v Group
        // a nastavit jim options 'firstInInputGroup' a 'lastInInputGroup'.
        // Bez těchto nastavení by pak ControlRenderer nevěděl, že má/nemá zaoblit rohy.
        // Dále se nastaví floatingLabels a clientValidation
        $prevControl = null;
        $inInputGroup = false;
        $defaultFloatingLabels = $this->floatingLabels;
        if($parent instanceof Nette\Forms\ControlGroup && !is_null($parent->getOption('floatingLabels')))
            $defaultFloatingLabels = (bool) $parent->getOption('floatingLabels');
        $singleMode = $this->inputGroupSingleMode;
        if($parent instanceof Nette\Forms\ControlGroup && !is_null($parent->getOption('inputGroupSingleMode')))
            $singleMode = (bool) $parent->getOption('inputGroupSingleMode');
        foreach ($parent->getControls() as $control) {
            if ($control->getOption('rendered') || $control->getOption('type') === 'hidden' || $control->getForm(false) !== $this->form)
                continue;
            // Nastavení inputGroup
            $inputGroup = $control->getOption('inputGroup');
            // Stavy mohou znamenat něco jiného podle inputGroupSingleMode
            if ($inputGroup === null) { //Pokud je null, tak záleží na předchozím controlu, jestli je nebo není v inputGroup.
                if($singleMode){ // Pokud je null a je singleMode, vytváří zde inputGroup
                    $control->setOption('firstInInputGroup', true);
                    $control->setOption('lastInInputGroup', true);
                    $control->setOption('inputGroup', true); // Přenastavuje option podle reality vykreslování
                    $inInputGroup = true;
                }elseif($inInputGroup) { // Jinak, pokud je v inputGroup, pak předchozí není last, jako last je nastaven aktuálně iterovaný control.
                    $prevControl->setOption('lastInInputGroup', false);
                    $control->setOption('lastInInputGroup', true);
                }
            } elseif ($inputGroup === false) { //Není v inputGroup v každém případě
                $inInputGroup = false;
            }else{ // Pokud je 'inputGroup' nastavena na true, singleMode je zapnuté a je existující inputGroup, pak se do ní vloží
                if($inputGroup === true && $singleMode && $inInputGroup){
                    $prevControl->setOption('lastInInputGroup', false);
                    $control->setOption('lastInInputGroup', true);
                    $control->setOption('inputGroup', null); // Přenastavuje option podle reality vykreslování
                }else { //Jinak je v inputGroup, a je první v této inputGroup - nastavení first a last
                    $control->setOption('firstInInputGroup', true);
                    $control->setOption('lastInInputGroup', true);
                    $inInputGroup = true;
                }
            }
            $prevControl = $control;

            // Nastavení floatingLabels.
            // Pokud je option null, nastaví podle globálního $defaultFloatingLabels (buď podle option na Group, nebo podle konstruktoru).
            // Pokud je cokoli mimo false a null, je nastaveno na true.
            if (is_null($control->getOption('floatingLabel')))
                $control->setOption('floatingLabel', $defaultFloatingLabels);
            elseif ($control->getOption('floatingLabel') !== false)
                $control->setOption('floatingLabel', true);
            // Tj. teď už je option 'floatingLabel' vždy nastavené a bool.
            // Nastavení clientValidation
            $control->setOption('clientValidation', $this->clientValidation);
        }

        // $row se nastaví podle globálního $this->rows - aby bylo nastaveno i pro "virtuální Group",
        // tedy skupinu controls, co jsou přímo ve formu a ne v Group.
        $row = $this->rows;
        // Pokud je $parent ControlGroup a option 'row' není null, nastaví se toto option do $row
        if ($parent instanceof Nette\Forms\ControlGroup && !is_null($parent->getOption('row')))
            $row = $parent->getOption('row');
        // Vytvoří model pro vytváření struktury horních wrapperů.
        $groupRenderer = new GroupRenderer(
            $this->wrappers,
            $row,
            $parent instanceof Nette\Forms\ControlGroup ? $parent->getOption('col') : null);

        foreach ($parent->getControls() as $control) {
            if ($control->getOption('rendered') || $control->getOption('type') === 'hidden' || $control->getForm(false) !== $this->form)
                continue;
            // Model vytvoří strukturu 'horních' wrapperů pro dané control, a vrátí wrapper, do kterého má být vložen.
            $wrapper = $groupRenderer->getWrapper($control);
            // Vyrenderuje control do wrapperu.
            $this->renderControl($control, $wrapper);
        }
        return $groupRenderer->getContainer()->render(0);
    }

    /**
     * Vyrenderuje $control (a jeho 'dolní' wrappery) do wrapperu $container
     * @param Nette\Forms\Controls\BaseControl $control
     * @param Html $container
     */
    public function renderControl(Nette\Forms\Controls\BaseControl $control, Html $container)
    {
        $this->controlRenderer = $this->controlRenderer ?? new ControlRenderer($this->wrappers);
        $this->controlRenderer->render($control, $container);
    }

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

}
