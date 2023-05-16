<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers;

use Jdvorak23\Bootstrap5FormRenderer\HtmlFactory;
use Nette\Utils\Html;

class GroupRenderer
{

    /** Určí se v konstruktoru. Top container, obsahuje celou strukturu prvků Group (nebo všech zbylých, co nejsou v Group). */
    protected RendererHtml $container;

    /** "Horní group row". Určí se v konstruktoru (podle parametru $groupRow), pokud existuje,
     * je stejný pro všechny controls v Group (nebo zbylé). */
    protected ?RendererHtml $parentRow;

    /**
     * Option 'col' na Group nám může změnit defaultní 'group col'.
     * Tedy, že se nebude brát jako default $wrappers['group']['col'] , ale ten dodaný v option.
     * Vytváří se v konstruktoru podle $defaultGroupCol parametru a dále se klonuje. */
    protected ?RendererHtml$defaultGroupCol;

    /** Reprezentuje aktuální (pokud nějaký je) 'dolní row' wrapper.*/
    protected ?RendererHtml $row = null;

    protected ?RendererHtml$defaultCol;

    /** Vytváří se podle potřeby - vždy pro první button. Pokud je buttonů více za sebou, další jsou vkládány do tohoto wrapperu.
     * Používá se pouze, pokud dané tlačítko není v inputGroup. */
    protected ?RendererHtml $buttonGroup = null;

    /** Vytváří se podle potřeby - vždy pro první prvek nějaké definované inputGroup, další jsou do něj vkládány. */
    protected ?RendererHtml $inputGroup = null;

    /** Resetuje se vždy pro každý control. Poté, co proběhne metoda getWrapper, je v něm instance toho wrapperu,
     * do kterého má být vložen control (to co se vrací).*/
    protected ?RendererHtml $wrapper;

    /** Resetuje se vždy pro každý control. Struktura wrapperů se vytváří odspodu,
     * sem se ukládá nejvyšší wrapper, který ještě nebyl zařazen do $this->container při zpracování metodou getWrapper. */
    protected ?RendererHtml $topWrapper;


    /** Control's Html factory */
    protected HtmlFactory $controlHtmlFactory;

    /**
     * @param HtmlFactory $htmlFactory Group's Html factory
     */
    public function __construct(protected HtmlFactory $htmlFactory)
    {
        $this->container = RendererHtml::el();
        $this->setParentRow();
        $this->setDefaultGroupCol();
    }

    /**
     * Vytvoří a vloží do struktrury (~$this->container) wrapper, do kterého má být vložen control (a vrátí ho).
     * 'Horní row' ($wrappers['group']['row'], nebo vlastní, nebo žádný) se případně vytvořil už konstruktorem - pro všechny prvky group je stejný.
     * Dále tedy může být potřeba vytvořit hned několik wraperrů - 'groupCol' <- 'row' <- 'col' <- 'inputGroup' (popř 'buttons').
     * Jak a jestli se vytváří, záleží na konkrétním nastavení daného controls a pole $wrappers.
     * Algoritmus běží "odspodu", dle výše uedeného řetězu. Skupina vytvořených wrapperů se buď vloží do již nějakého existujícího wrapperu,
     * nebo je novou "větví" v $this->container.
     * @param HtmlFactory $controlHtmlFactory
     * @return RendererHtml wrapper, do kterého má být vložen daný $control. Wrapper je správně vložen do "horní" stuktury.
     */
    public function getWrapper(HtmlFactory $controlHtmlFactory): RendererHtml
    {
        // Při každém vloženém control se zresetují vlastnosti.
        $this->controlHtmlFactory = $controlHtmlFactory;
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
        if(!$this->inputGroup && $this->controlHtmlFactory->options->getOption('type') === 'button') { //$control instanceof Button
            if(!$this->setButtonGroup() && $this->buttonGroup)
                return $this->buttonGroup;
            elseif($this->buttonGroup)
                $this->insert($this->buttonGroup);
        }else// Pokud je inputGroup, nebo control není button, reset buttons wrapperu
            $this->buttonGroup = null;

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
            $this->insert($this->parentRow, $this->getGroupCol());
            return $this->wrapper;
        }

        // Pokud dosud nebyla struktura zařazena, vloží se do containeru.
        $this->insert($this->container);
        return $this->wrapper;
    }

    /**
     * Vrací vytvořený strom.
     * @return Html
     */
    public function getContainer(): RendererHtml
    {
        return $this->container;
    }

    /**
     * Metoda na vytváření struktury (která zatím není napojena na $this->container) v průběhu jednoho cyklu metody getWrapper.
     * Tj. prvky jsou vkládány odspodu nahoru. Postupně přiřazuje instance wrapperů tak,
     * že v $this->wrapper je vždy to, co chceme nakonec vrátit (kam se má vkládat control),
     * tedy vytvoří se už při prvním volání insert při zpracovávání jednotlivého controlu.
     * A v  $this->topWrapper je vždy nejhornější wrapper vytvářené struktury.
     * @param RendererHtml $container kam se vkládá (případně) již existující struktura.
     * @param RendererHtml|null $wrapper pokud je zadán $wrapper vloží se "mezi", tj. vytvoří se $container->$wrapper->existující
     * @return void
     */
    protected function insert(RendererHtml $container, RendererHtml|null $wrapper = null) : void
    {
        if($wrapper === null)
            $wrapper = $container;
        else
            $container->addHtml($wrapper);
        // Pokud ještě není $this->wrapper, stává se jím $wrapper
        if($this->wrapper === null)
            $this->wrapper = $wrapper;
        elseif($this->topWrapper)// Pokud je, vkládá se aktuální topWrapper do $wrapper
            $wrapper->addHtml($this->topWrapper);
        // A topWrapperem se stává nejvyšší prvek, tj. $container (vytváříme odpodu).
        $this->topWrapper = $container;
    }
    protected function getCol(): RendererHtml|null
    {
        return $this->col('col', $this->defaultCol);
    }

    protected function getGroupCol(): RendererHtml|null
    {
        return $this->col('groupCol', $this->defaultGroupCol);
    }

    protected function col(string $option, RendererHtml|null $default): RendererHtml|null
    {
        $setting = $this->controlHtmlFactory->options->getOption($option);
        if($setting === false)
            $wrapper = null;
        elseif($setting === null || $setting === true) {
            $wrapper = $default ? clone $default : null;
        } elseif($setting instanceof Html)
            $wrapper = RendererHtml::fromNetteHtml($setting);
        else
            $wrapper = RendererHtml::el($setting);
        if(!$wrapper)
            return null;
        // Vytváříme ručne, musí se nastavit třídy
        $wrapper->setClasses($this->controlHtmlFactory->options->getOption('.' . $option));
        return $wrapper;
    }

    /**
     * V konstruktoru, příjmá parameter z option 'col' na Group, nastavuje jiný defaultní wrapper, než je v poli $wrappers.
     * @param mixed $defaultGroupCol parameter z option 'col' na Group
     * @return void
     */
    private function setDefaultGroupCol(): void
    {
        $defaultGroupCol = $this->htmlFactory->options->getOption('col');

        if ($defaultGroupCol === false)// Nastavena, že je defaultní - žádný
            $this->defaultGroupCol = null;
        else
        $this->defaultGroupCol = $this->htmlFactory->createWrapper('col', 'group col');
    }

    /**
     * Podle option 'row' na controlu (případně) nastaví $this->row, tj. vytvoří wrapper.
     * @return bool Důležité. Vrací true, pokud byl $this->row nějak přenastaven a false, pokud nebyl.
     */
    protected function setRow(): bool
    {
        $row = $this->controlHtmlFactory->options->getOption('row');
        if($row === false) { // Nastavena, že není.
            $this->row = null;
            return true;
        }elseif($row === null) {
            return false; // Nenastavena - bere se podle předchozího control.
        }
        $this->row = $this->controlHtmlFactory->createWrapper('row', 'controls row');

        // Tady je tedy nový control-level grid. U tohoto prvního controlu se podívá na 'defaultCol' a případně nastaví:
        if ($this->controlHtmlFactory->options->getOption('defaultCol') === false)// Nastavena, že je defaultní - žádný
            $this->defaultCol = null;
        else
            $this->defaultCol =  $this->controlHtmlFactory->createWrapper('defaultCol', 'controls col');
        //V tomto případě byl $this->row nějak přenastaven.
        return true;
    }

    /**
     * Podle option 'inputGroup' na control (případně) nastaví (případnou) inputGroup do $this->inputGroup, vše záleží na nastavení.
     * !!Dále přenastavuje option 'inputGroup'!! Control už nějaký wrapper v option 'inputGroup' nezajímá,
     * zajímá ho, jestli je nebo není v inputGroup, takže je u všech controls přenastaveno na true/false.
     * @return bool Důležité. Vrací true, pokud byla aktuální inputGroup ($this->inputGroup) nějak přenastavena a false, pokud nebyla.
     */
    protected function setInputGroup(): bool
    {
        $inputGroup = $this->controlHtmlFactory->options->getOption('inputGroup');
        if($inputGroup === false) { // Hodnota false ukončuje případně existující inputGroup a nastavuje že tento element v inputGroup není.
            $this->inputGroup = null;
            return true; // InputGroup byla nastavena - na žádnou.
        }elseif($inputGroup === null) { // Hodnota null (nenastaveno) - Pokud je předchozí control v inputGroup, tento je ve stejné.
            if($this->inputGroup) //Pokud je inputGroup
                $this->controlHtmlFactory->options->setOption('inputGroup', true);
            return false; // V tomto případě buď inputGroup není, nebo se použije předchozí - nastavena tedy nebyla.
        }
        // Zde už je inputGroup nově nastavena u tohoto elementu (první v inputGroup).
        $this->inputGroup = $this->controlHtmlFactory->createWrapper('inputGroup', 'controls inputGroup');
        $this->controlHtmlFactory->options->setOption('inputGroup', true);
        return true; // Byla nastavena inputGroup.
    }
    protected function setButtonGroup(): bool
    {
        $buttonGroup = $this->controlHtmlFactory->options->getOption('buttonGroup');
        if($buttonGroup === false) { // Hodnota false ukončuje případně existující buttonGroup a nastavuje že tento element v buttonGroup není.
            $this->buttonGroup = null;
            return true; // buttonGroup byla nastavena - na žádnou.
        }elseif($buttonGroup === null) { // Hodnota null (nenastaveno) - Pokud je předchozí control v buttonGroup, tento je ve stejné.
            if($this->buttonGroup) // Pokud existuje buttonGroup a není nastaveno, půjde do stejné
                return false; // V tomto případě se buttonGroup použije předchozí - nastavena tedy nebyla.
        }
        $this->buttonGroup = $this->controlHtmlFactory->createWrapper('buttonGroup', 'controls buttons');
        return true; // Byla nastavena buttonGroup.
    }

    /**
     * $option 'row' na group musí už být předem ošetřen a nesmí být null!
     * Nastaví $this->parentRow a $this->container
     * @return void
     */
    protected function setParentRow(): void
    {

        $groupRow = $this->htmlFactory->options->getOption('row');

        if($groupRow === false) { //false = nebude existovat
            $this->parentRow = null;
            return;
        }
        $this->parentRow = $this->htmlFactory->createWrapper('row', 'group row');
        // Tady parent row existuje, stává se tedy jako nejvyšší container (ten je jinak fragment z konstruktoru)
        $this->container = $this->parentRow;
    }
}