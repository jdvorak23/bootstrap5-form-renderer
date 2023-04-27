<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers;

use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Utils\Html;

class GroupRenderer
{
    /** @var Wrappers pole standardních wrapperů, předávané konstruktorem. */
    protected Wrappers $wrappers;

    /** @var Html Určí se v konstruktoru. Top container, obsahuje celou strukturu prvků Group (nebo všech zbylých, co nejsou v Group). */
    protected Html $container;

    /** @var Html|null "Horní group row". Určí se v konstruktoru (podle parametru $groupRow), pokud existuje,
     * je stejný pro všechny controls v Group (nebo zbylé). */
    protected ?Html $parentRow;

    /**
     * @var Html|null Option 'col' na Group nám může změnit defaultní 'group col'.
     * Tedy, že se nebude brát jako default $wrappers['group']['col'] , ale ten dodaný v option.
     * Vytváří se v konstruktoru podle $defaultGroupCol parametru a dále se klonuje. */
    protected ?Html $defaultGroupCol;

    /**
     * @var Html|null se taky určí v konstruktoru podle $defaultGroupCol - jestli byl
     * zadán přes option 'col' na Group vlastní wrapper, nebo ne. */
    protected bool $isDefaultGroupCol;

    /** @var Html|null Vytváří se podle potřeby - vždy pro první prvek nějaké definované inputGroup, další jsou do něj vkládány. */
    protected ?Html $inputGroup = null;

    /** @var Html|null Vytváří se podle potřeby - vždy pro první button. Pokud je buttonů více za sebou, další jsou vkládány do tohoto wrapperu.
     * Používá se pouze, pokud dané tlačítko není v inputGroup. */
    protected ?Html $button = null;

    /** @var Html|null Reprezentuje aktuální (pokud nějaký je) 'dolní row' wrapper. Může obsahovat víc controls. */
    protected ?Html $row = null;

    /** @var Html|null Resetuje se vždy pro každý control. Poté, co proběhne metoda getWrapper, je v něm instance toho wrapperu,
     * do kterého má být vložen control (to co se vrací).*/
    protected ?Html $wrapper;

    /** @var Html|null Resetuje se vždy pro každý control. Struktura wrapperů se vytváří odspodu,
     * sem se ukládá nejvyšší wrapper, který ještě nebyl zařazen do $this->container při zpracování metodou getWrapper. */
    protected ?Html $topWrapper;

    /** @var BaseControl contol, pro který je vytvářena struktura wrapperů. */
    protected BaseControl $control;

    /**
     * @param Wrappers $wrappers Třída pro obecnou práci s wrappery.
     * @param Html|string|bool $groupRow Definice 'group row'
     * @param Html|string|bool|null $defaultGroupCol Definice defaultního 'group col'
     */
    public function __construct(Wrappers $wrappers, Html|string|bool $groupRow, Html|string|bool|null $defaultGroupCol = null)
    {
        $this->wrappers = $wrappers;
        $this->setParentRow($groupRow);
        $this->setDefaultGroupCol($defaultGroupCol);
    }

    /**
     * Vytvoří a vloží do struktrury (~$this->container) wrapper, do kterého má být vložen control (a vrátí ho).
     * 'Horní row' ($wrappers['group']['row'], nebo vlastní, nebo žádný) se případně vytvořil už konstruktorem - pro všechny prvky group je stejný.
     * Dále tedy může být potřeba vytvořit hned několik wraperrů - 'groupCol' <- 'row' <- 'col' <- 'inputGroup' (popř 'buttons').
     * Jak a jestli se vytváří, záleží na konkrétním nastavení daného controls a pole $wrappers.
     * Algoritmus běží "odspodu", dle výše uedeného řetězu. Skupina vytvořených wrapperů se buď vloží do již nějakého existujícího wrapperu,
     * nebo je novou "větví" v $this->container.
     * @param BaseControl $control control, pro které má být vrácen jeho wrapper
     * @return Html wrapper, do kterého má být vložen daný $control. Wrapper je správně vložen do "horní" stuktury.
     */
    public function getWrapper(BaseControl $control) : Html
    {
        // Při každém vloženém control se zresetují vlastnosti.
        $this->control = $control;
        $this->wrapper = null;
        $this->topWrapper = null;

        // Nejdříve se řeší inputGroup. Pokud existuje inputGroup, a na daném control není nastavení nové,
        // přidává se do stávající.
        if(!$this->setInputGroup() && $this->inputGroup)
            return $this->inputGroup;
        // Pokud je na daném control nastavení nové inputGroup, pomocí insert se začne vytvářet výsledný strom wrapperů.
        elseif($this->inputGroup)
            $this->insert($this->inputGroup);

        // Pokud control je tlačítko a není v inputGroup.
        if(!$this->inputGroup && $control instanceof Button) {
            // Pokud je  control druhé a další tlačítko v řadě, přidá se do stávajícího wraperu pro buttons.
            if($this->button)
                return $this->button;
            // Pokud je první, vytvoří se tlačítkový wrapper
            $this->button = $this->wrappers->getWrapper('controls buttons');
            // A začne vytvářet výsledný strom wrapperů...
            $this->insert($this->button);
        }else// Pokud je inputGroup, nebo control není button, reset buttons wrapperu
            $this->button = null;

        // Dále se řeší option "row" a "col" na controlu - pokud jsou.
        // Pokud existuje $this->row wrapper, a zároveň na controlu není nastaveno nové "row",
        // přidává do tohoto stávajícího (s případným wrapperem 'col').
        if(!$this->setRow() && $this->row) {
            $this->insert($this->row, $this->getCol());
            return $this->wrapper;
        }// Pokud je na controlu nastaveno nové "row", je vloženo do vytvářeného stromu (s případným wrapperem 'col').
        elseif($this->row)
            $this->insert($this->row, $this->getCol());

        // Dále řeší nastavení "row" na jednotlivé group ('group row').
        // Pokud je nastavena, vkládá se struktura do nového "group col".
        if($this->parentRow) {
            $this->insert($this->parentRow, $this->getCol(true));
            return $this->wrapper;
        }

        // Pokud dosud nebyla struktura zařazena, vloží se do containeru.
        $this->insert($this->container);
        return $this->wrapper;
    }

    /**
     * Metoda na vytváření struktury (která zatím není napojena na $this->container) v průběhu jednoho cyklu metody getWrapper.
     * Tj. prvky jsou vkládány odspodu nahoru. Postupně přiřazuje instance wrapperů tak,
     * že v $this->wrapper je vždy to, co chceme nakonec vrátit (kam se má vkládat control),
     * tedy vytvoří se už při prvním volání insert při zpracovávání jednotlivého controlu.
     * A v  $this->topWrapper je vždy nejhornější wrapper vytvářené struktury.
     * @param Html $container kam se vkládá (případně) již existující struktura.
     * @param Html|string|null $wrapper pokud je zadán $wrapper vloží se "mezi", tj. vytvoří se $container->$wrapper->existující
     * @return void
     */
    private function insert(Html $container, Html|string|null $wrapper = null) : void
    {
        if(is_null($wrapper))
            $wrapper = $container;
        elseif($wrapper instanceof Html)
            $container->addHtml($wrapper);
        else
            $container->addHtml(Html::el($wrapper));

        // Pokud ještě není $this->wrapper, stává se jím $wrapper
        if(is_null($this->wrapper))
            $this->wrapper = $wrapper;
        // Pokud je, vkládá se aktuální topWrapper do $wrapper
        elseif($this->topWrapper)
            $wrapper->addHtml($this->topWrapper);
        // A topWrapperem se stává nejvyšší prvek, tj. $container (vytváříme odpodu).
        $this->topWrapper = $container;
    }

    /**
     * Vrací vytvořený strom.
     * @return Html
     */
    public function getContainer() : Html
    {
        return $this->container;
    }

    /**
     * Vrací wrapper 'controls col', nebo 'group col'. U groupCol může vrátit i jiný default - pokud byl nastaven v option 'col' na Group.
     * @param bool $groupCol jestli má vrátit groupCol nebo col
     * @return Html|null
     */
    protected function getCol(bool $groupCol = false) : Html|null
    {
        $col = $groupCol ? $this->control->getOption('groupCol') : $this->control->getOption('col');
        if($col === false)
            return null;
        elseif($col === null || $col === true) {
            if($groupCol && $this->isDefaultGroupCol)
                return $this->defaultGroupCol ? clone $this->defaultGroupCol : null;
            return $groupCol ? $this->wrappers->getWrapper('group col') : $this->wrappers->getWrapper('controls col');
        } elseif($col instanceof Html)
            return clone $col;
        else
            return Html::el($col);
    }

    /**
     * V konstruktoru, příjmá parameter z option 'col' na Group, nastavuje jiný defaultní wrapper, než je v poli $wrappers.
     * @param mixed $defaultGroupCol parameter z option 'col' na Group
     * @return void
     */
    private function setDefaultGroupCol(mixed $defaultGroupCol) : void
    {
        if ($defaultGroupCol === false)// Nastavena, že je defaultní - žádný
            $this->defaultGroupCol = null;
        elseif ($defaultGroupCol === null || $defaultGroupCol === true){ // Není nastaveno a true je ve skutečnosti nesmyslný, nastavylo by default na default :D
            $this->isDefaultGroupCol = false; //Není nastavený jiný defaultGroupCol - bude se brát default z wrappers.
            return;
        }elseif($defaultGroupCol instanceof Html)
            $this->defaultGroupCol = $defaultGroupCol; // Klonuje se až v getCol().
        else
            $this->defaultGroupCol = Html::el($defaultGroupCol);
        //Je nastavený jiný defaultGroupCol.
        $this->isDefaultGroupCol = true;
    }

    /**
     * Podle option 'row' na controlu (případně) nastaví $this->row, tj. vytvoří wrapper.
     * @return bool Důležité. Vrací true, pokud byl $this->row nějak přenastaven a false, pokud nebyl.
     */
    protected function setRow()
    {
        $row = $this->control->getOption('row');
        if($row === false) // Nastavena, že není.
            $this->row = null;
        elseif($row === null)
            return false; // Nenastavena - bere se podle předchozího control.
        elseif($row === true) //Zde a dále -> Nastaveno, že je nový (buď default, nebo vlastní).
            $this->row = $this->wrappers->getWrapper('controls row');
        elseif($row instanceof Html)
            $this->row = clone $row;
        else
            $this->row = Html::el($row);
        //V tomto případě byl $this->row nějak přenastaven.
        return true;
    }

    /**
     * Podle option 'inputGroup' na control (případně) nastaví (případnou) inputGroup do $this->inputGroup, vše záleží na nastavení.
     * !!Dále přenastavuje option 'inputGroup'!! Control už nějaký wrapper v option 'inputGroup' nezajímá,
     * zajímá ho, jestli je nebo není v inputGroup, takže je u všech controls přenastaveno na true/false.
     * @return bool Důležité. Vrací true, pokud byla aktuální inputGroup ($this->inputGroup) nějak přenastavena a false, pokud nebyla.
     */
    protected function setInputGroup() : bool
    {
        $inputGroup = $this->control->getOption('inputGroup');
        if($inputGroup === false) { // Hodnota false ukončuje případně existující inputGroup a nastavuje že tento element v inputGroup není.
            $this->inputGroup = null;
            return true; // InputGroup byla nastavena - na žádnou.
        }elseif($inputGroup === null) { // Hodnota null (nenastaveno) - Pokud je předchozí control v inputGroup, tento je ve stejné.
            if($this->inputGroup) //Pokud je inputGroup
                $this->control->setOption('inputGroup', true);
            return false; // V tomto případě buď inputGroup není, nebo se použije předchozí - nastavena tedy nebyla.
        // V tomto a dalších je inputGroup nově nastavena u tohoto elementu (první v inputGroup).
        }elseif($inputGroup === true) // true znamená defaultní wrapper.
            $this->inputGroup = $this->wrappers->getWrapper('controls inputGroup');//$this->makeWrapper('controls inputGroup');
        elseif($inputGroup instanceof Html) // vlastní wrapper v Html
            $this->inputGroup = clone $inputGroup;
        else
            $this->inputGroup = Html::el($inputGroup);
        $this->control->setOption('inputGroup', true);
        return true; // Byla nastavena inputGroup.
    }


    /**
     * $groupRow musí už být předem ošetřen a nesmí být null!
     * Nastaví $this->parentRow a $this->container
     * @param Html|string|bool $groupRow false -> žádný parent, true -> defaultní z wrappers['group']['row']
     * instanceof Html -> použije tuto instanci, string -> použije Html:el($groupRow)
     * @return void
     */
    protected function setParentRow(Html|string|bool $groupRow) : void
    {
        if($groupRow === false) { //false = nebude existovat
            $this->parentRow = null;
            //Jakmile není groupRow, container pro vnitřek fieldsetu není - použije se prázdný
            $this->container = Html::el();
            return;
        }
        elseif($groupRow === true) // Standardní wrapper
            $this->parentRow = $this->wrappers->getWrapper('group row'); //$this->makeWrapper('group row');
        elseif($groupRow instanceof Html) // Vlastní wrapper Html
            $this->parentRow = clone $groupRow;
        else
            $this->parentRow = Html::el($groupRow); // Vlastní wrapper string
        // Tady parent row existuje, stává se tedy jako nejvyšší container
        $this->container = $this->parentRow;
    }
}