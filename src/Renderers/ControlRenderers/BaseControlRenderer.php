<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Renderers\HtmlWtf;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\HtmlStringable;
use Nette\Utils\Html;
use Nette;

/**
 *
 * @property-read RendererHtml|null $label to, co reprezentuje label ve struktuře. Netýká se labelů případných items.
 * @property-read RendererHtml $element Reprezentuje element controlu ve stuktuře, což ovšem to není ekvivalentní se samotným
 * controlem, např. u CheckboxList je to wrapper, ve kterém jsou jednotlivé items.
 * @property-read RendererHtml $parent Parent ve smyslu rodiče ve struktuře.
 * @property-read RendererHtml $inputGroupWrapper Další wrapper nad $parent, používaný pro input group.
 * @property-read HtmlWtf $htmlFactory
 */
abstract class BaseControlRenderer
{
    use Nette\SmartObject;

    /** Control formuláře. Předává se metodě render(). */
    protected BaseControl $control;
    /** Container, do kterého se vykreslí control. Předává se metodě render(). */
    protected Html $container;

    private RendererHtml|null $controlLabel = null;
    private RendererHtml|null $controlElement = null;
    private RendererHtml|null $parentElement = null;
    private RendererHtml|null $inputGroupWrapperElement = null;
    protected bool $floatingLabel;
    protected bool $isInputGroup;
    protected bool $firstInGroup;
    protected bool $lastInGroup;

    /* V základním nastavení control floating label nepodporuje */
    protected bool $floatingLabelAllowed = false;

    /* Předchozí item v input group */
    protected ?RendererHtml $prevInputGroupItem = null;


    protected ?Wrappers $wrappers = null;

    public function __construct(protected HtmlWtf|null $htmlFactory = null)
    {
        if($this->htmlFactory)
            $this->wrappers = $this->htmlFactory->wrappers;
    }

    /**
     * Jediná public metoda, vyrenderuje daný control.
     * @param Html $container Sem vyrenderuje daný control.
     * @return void
     */
    public function render(BaseControl $control, Html $container): void
    {
        $this->container = $container;
        $this->control = $control;
        if(!$this->htmlFactory){
            $this->htmlFactory = $control->getOption('htmlFactory');
            $this->wrappers = $this->htmlFactory->wrappers;
        }

        $this->setRenderer();

        if($this->isInputGroup)
            $this->renderToGroup($container);
        else
            $this->renderControl($container);
    }

    protected function setRenderer(): void
    {
        // Option 'floatingLabel' je vždy bool, ošetřeno v rendereru.
        $this->floatingLabel = $this->control->getOption('floatingLabel')  === true;
        // Option 'inputGroup' je vždy bool, ošetřeno v ControlWrappers.
        $this->isInputGroup = $this->control->getOption('inputGroup')  === true;
        // Zbylé 2 jsou buď true/false, nebo neexistují (neměly by).
        $this->firstInGroup = $this->control->getOption('firstInInputGroup') === true;
        $this->lastInGroup = $this->control->getOption('lastInInputGroup') === true;
    }

    /**
     * Vyrenderuje celý control, pokud není v inputGroup.
     * @param Html $container
     */
    abstract protected function renderControl(Html $container): void;

    /**
     * Vyrenderuje celý control, pokud je v inputGroup.
     * @param Html $container
     */
    abstract protected function renderToGroup(Html $container): void;

//---------------------------------------- element controlu -----------------------------------------
    /**
     * Getter pro $this->element.
     * Vrátí element reprezentující control ve struktuře.
     * @return RendererHtml
     */
    protected function getElement(): RendererHtml
    {
        if(!$this->controlElement)
            $this->controlElement = $this->createElement();
        return $this->controlElement;
    }
    /**
     * Vytvoří element pro control.
     * Přetížením v odvozených třídách lze vytvořit jinak.
     * @return RendererHtml
     */
    protected function createElement(): RendererHtml
    {
        return RendererHtml::fromNetteHtml($this->control->getControl());
    }
    /**
     * Renderuje element pro control.
     * @return void
     */
    protected function renderElement(): void
    {
        $this->renderHtmlElement($this->element, $this->parent);
        $this->setupElement();
    }
    /**
     * Slouží k nastavení elementu , reprezentujícího control ve struktuře
     * Pro nastavení jednotlivého formulářového prvku slouží metoda setupControlElement
     * @return void
     */
    abstract protected function setupElement(): void;
    /**
     * Nastaví každému formulářovému prvku potřebné třídy. U Listů se tímto nastavuje každá item.
     * Button toto nevolá.
     * @param RendererHtml $element formulářový prvek
     * @return void
     */
    protected function setupControlElement(RendererHtml $element): void
    {
        // Feedback
        $this->setFeedbackClasses($element, '.control');
        // Classes from $wrappers
        $this->control->isRequired()
            ? $this->htmlFactory->setClasses($element, 'control .required')
            : $this->htmlFactory->setClasses($element, 'control .optional');
        $this->htmlFactory->setClasses($element, 'control .all');
        // Option classes
        $element->setClasses($this->control->getOption('.control'));
    }
//---------------------------------------- parent element -----------------------------------------
    /**
     * Getter pro $this->parent
     * Vrátí element pro parent - rodičovský element elementu pro control.
     * Pokud neexistuje, vytvoří ho.
     * Pokud je uvedeno option "parent", a je do něj nahrána instance Html,
     * použije se tento dodaný element místo výchozího.
     * @return RendererHtml
     */
    protected function getParent(): RendererHtml
    {
        if($this->parentElement)
            return $this->parentElement;
        $this->parentElement = $this->createParentElement();
        return $this->parentElement;
    }
    /**
     * Vytvoří parent element pro control.
     * @return Html
     */
    abstract protected function createParentElement(): RendererHtml;

    /**
     * Vyrenderuje HTML rodiče, do kterého je vložen element, reprezentující control.
     * Rovnou do tohoto rodiče vyrenderuje control element!!
     * @param Html|null $container
     * @return void
     */
    protected function renderParent(?Html $container = null): void
    {
        if(!$container)
            $container = $this->container;
        // Nastavení parent elementu
        $this->setupParent();
        $container->addHtml($this->parent);
        //!!!Rovnou vyrenderuje element
        $this->renderElement();
    }
    /**
     * Slouží k nastavení parent elementu contorolu.
     * @return void
     */
    protected function setupParent(): void
    {
    }
//---------------------------------------- input group wrapper element -----------------------------------------
    protected function getInputGroupWrapper(): RendererHtml
    {
        if(!$this->inputGroupWrapperElement)
            $this->inputGroupWrapperElement = $this->createInputGroupWrapper();
        return $this->inputGroupWrapperElement;
    }
    abstract protected function createInputGroupWrapper(): RendererHtml;
    protected function renderInputGroupWrapper(?Html $container = null): void
    {
        if(!$container)
            $container = $this->container;
        $this->setupInputGroupWrapper();
        $container->addHtml($this->inputGroupWrapper);
    }
    protected function setupInputGroupWrapper(): void
    {
    }

//---------------------------------------- label element -----------------------------------------
    /**
     * Getter pro $this->label
     * Vrátí element pro label, nebo null, pokud není label definován (není nic v $control->caption).
     * Pokud ještě není label vytvořen, vytvoří se podle následujících pravidel:
     * Pokud je v option 'label' na controlu instance HtmlStringable, použije jako label tuto instanci.
     * V tomto případě s labelem nijak dál nepracuje a vykreslí se tak, jak byl zadán.
     * Pokud je v option 'label' cokoli jiného, nebere se v potaz.
     * Dále pokud je caption na controlu false nebo null, label se nevytváří.
     * Pokud je true, vytváří se label s prázdným textem (true je jako '').
     * A nakonec vytvoří label pomocí $this->createLabel();
     * @return RendererHtml|HtmlStringable|null label
     */
    protected function getLabel(): RendererHtml|HtmlStringable|null
    {
        if($this->controlLabel)
            return $this->controlLabel;

        // Pokud je objekt Html v nastavení 'label', Pak ho rovnou přiřadí.
        // Vůbec už nás dál nezajímá, co bylo v caption.
        $label = $this->control->getOption('label');
        if($label instanceof HtmlStringable){
            $this->controlLabel = $this->htmlFactory->createOwn((string) $label);
            return $this->controlLabel;
        }
        // Pokud je caption implicitně nastaveno na false, nebo null (default), pak nepřidáváme element.
        if($this->control->caption === false || is_null($this->control->caption))
            return null;
        //Pokud je true, vložíme prázdný řetězec.
        elseif($this->control->caption === true)
            $this->control->caption = '';

        $this->controlLabel = $this->createLabel();
        return $this->controlLabel;
    }

    /**
     * Vytvoří element pro label.
     * Přetížením v odvozených třídách lze vytvořit jinak.
     * @return RendererHtml
     */
    protected function createLabel(): RendererHtml
    {
        return RendererHtml::fromNetteHtml($this->control->getLabel());
    }

    /**
     * Vloží label do zadaného containeru
     * @param Html $container kam se vloží label
     * @return void
     */
    protected function renderLabel(Html $container): void
    {
        // Pokud není element labelu, nerenderuje se.
        if(!$this->label)
            return;
        // todo
        // Pokud je zadaný vlastní Html element, je pouze vložen do containeru
        /*if($this->control->getOption('label') instanceof HtmlStringable){
            // I tak, pokud je element v inputGroup (a není to floating label), se resetují ruční borders předchozího elementu v inputGroup.
            // Možná zbytečné když label je vždy první v inputGroup :D
            if($this->floatingLabelAllowed && $this->floatingLabel)
                $container->addHtml($this->label);
            else
                $this->renderHtmlElement($this->label, $container);
            return;
        }*/
        // Přidělení tříd a stylu labelu, podle toho jestli je v inputGroup a jestli je label floating label.
        if($this->isInputGroup){
            // Pokud je to floating label, a je v inputGroup, přiděluje se i styl na z-index:5,
            // protože to Bootstrap5 podcenil - pokud je floating label zároveň v inputGroup, nefunguje bez toho správně (protože to dávám do 2 input-group :d)
            // A do containeru se nevkládá přes renderHtmlElement, protože není součástí "lajny" inputGroup, tj. nerenderuje se standardně.
            if($this->floatingLabelAllowed && $this->floatingLabel){
                $this->htmlFactory->setClasses($this->label, 'label .inputGroupFloating');
                $style = $this->wrappers->getValue('label ..inputGroupFloatingStyle');
                if($style && is_string($style))
                    $this->label->style .= $style;
                $container->addHtml($this->label);
            }else {
                //Třída pro label v inputGroup (bez floatingLabel)
                $this->htmlFactory->setClasses($this->label,'label .inputGroup');
                $this->renderHtmlElement($this->label, $container);
            }
        }else{
            if($this->floatingLabelAllowed && $this->floatingLabel)
                $this->htmlFactory->setClasses($this->label,'label .floatingLabel');
            else
                $this->htmlFactory->setClasses($this->label,'label .class');
            $this->renderHtmlElement($this->label, $container);
        }
        //Nakonec je voláno obecné nastavení labelu.
        $this->setLabel();
    }
    protected function setLabel(): void
    {
        // Pokud není element labelu, nebo je zadaný vlastní Html element, nic se nenastavuje.
        if(!$this->label || $this->label->isOwn)
            return;
        // Prefixy / suffixy
        $requiredPrefix = $this->control->isRequired() ? $this->wrappers->getContent('label requiredprefix') : '';
        $requiredSuffix = $this->control->isRequired() ? $this->wrappers->getContent('label requiredsuffix') : '';
        $prefix = $this->wrappers->getContent('label prefix');
        $suffix = $this->wrappers->getContent('label suffix');
        $this->label->insert(0, $prefix);
        $this->label->insert(0, $requiredPrefix);
        $this->label->addHtml($suffix);
        $this->label->addHtml($requiredSuffix);
        //'label .required'
        if($this->control->isRequired())
            $this->htmlFactory->setClasses($this->label,'label .required');
        // Vlastni styly přidané přes option '.label'.
        // Přidávají se ručně, label se nezískává přes factory, ale z BaseControl
        $this->label->setClasses($this->control->getOption('.label'));
    }
//---------------------------------------- description element -----------------------------------------
    protected function createDescription(): RendererHtml|null
    {
        $description = $this->control->getOption('description');
        // Pokud je option 'description' implicitně nastaveno na false, pak nepřidáváme element v žádném případě.
        if($description === false)
            return null;
        // Při description, která je Html, se použije pouze toto html
        if($description instanceof HtmlStringable) {
            return $this->htmlFactory->createOwn((string) $description);
        }
        // Pokud je null, může se vytvořit, pokud je zadaný 'description push' nebo je required a je zadaný 'description requiredpush'.
        if($description === null) {
            $push = $this->wrappers->getContent('description push');
            $requiredPush = $this->control->isRequired() ? $this->wrappers->getContent('description requiredpush') : '';
            if (($push || $requiredPush) && !$this->control instanceof Button) { // U buttonu se nevytváří!
                $push = $push ?: '';
                $requiredPush = $requiredPush ?: '';
                return $this->htmlFactory->createOwn($push . $requiredPush);
            } else// Pokud není zadaný nějaký default content, nevytváří se
                return null;
        }
        if($description === true)
            $description = '';
        // Zbývá string
        if(!is_string($description) && !($description instanceof \Stringable))
            return null;
        // Translate
        $description = $this->control->translate($description);
        // Prefixes / Suffixes
        $requiredPrefix = $this->control->isRequired() ? $this->wrappers->getContent('description requiredprefix') : '';
        $requiredSuffix = $this->control->isRequired() ? $this->wrappers->getContent('description requiredsuffix') : '';
        // Přidání
        $description = $requiredPrefix . $this->wrappers->getContent('description prefix') .  $description . $this->wrappers->getContent('description suffix') . $requiredSuffix;
        return RendererHtml::el()->addHtml($description);
    }
    protected function createDescriptionElement(): RendererHtml
    {
        return $this->isInputGroup
            ? $this->htmlFactory->createWrapper('descriptionItem','description inputGroupItem')
            : $this->htmlFactory->createWrapper('descriptionItem','description item');
    }
    protected function createDescriptionWrapper(): RendererHtml
    {
        return $this->htmlFactory->createWrapper('descriptionContainer', 'description container');
    }
    protected function renderDescription(Html $container): void
    {
        $description = $this->createDescription();
        if($description === null)
            return;
        if($description->isOwn){
            $this->renderHtmlElement($description, $container);
            return;
        }
        $element = $this->createDescriptionElement();
        $wrapper = $this->createDescriptionWrapper();

        // AKA !isFragment()
        // Pokud je $wrapper existující wrapper, pak se nastavení pro inputGroup provádí na něm
        if($wrapper->getName()){
            $this->renderHtmlElement($wrapper, $container);
            $wrapper->addHtml($element);
            $element->addHtml($description);
            return;
        }
        // Jinak se nastavení inputGroup provede na elementu description
        $this->renderHtmlElement($element, $wrapper);
        $element->addHtml($description);
        $container->addHtml($wrapper);
    }

    //---------------------------------------- error element -----------------------------------------
    /**
     * Vypíše chyby v poli errors na contolu
     * @param Html $container kam se vloží
     * @return void
     */
    protected function renderFeedback(Html $container): void
    {
        $errors = $this->control->getErrors();
        $clientValidation = (bool) $this->control->getOption('clientValidation'); //Is already bool value
        $feedbackCreated = false;

        if($errors)
            $this->renderErrors($container, $errors);
        elseif($this->isSubmitted())
            $feedbackCreated = $this->renderValidFeedback($container);
        // Pokud je client validation, vyrenderuje se template pro javascript
        if($clientValidation){
            if(!$errors)
                $this->renderErrors($container, ['']);
            if(!$feedbackCreated)
                $this->renderValidFeedback($container, true);
        }
        // Pokud je $container stejný, jako parent element controlu, vše je podle "snadardního"
        // Bootstrap5, pokud není, musí se přidat na parent
        // Pokud parent neexistuje, nemá efekt, a v tu chvíli ho ani nepotřebujeme
        if($container !== $this->parent){
            $this->setFeedbackClasses($this->parent, '.parent');
        }
    }

    protected function renderErrors(Html $container, array $errors): void {
        //Získání wrapperu podle inputGroup/floatingLabels
        if($this->isInputGroup)
            $wrapper = $this->htmlFactory->createWrapper("errorContainer", 'error inputGroup');
        elseif($this->floatingLabel && $this->floatingLabelAllowed)
            $wrapper = $this->htmlFactory->createWrapper("errorContainer", 'error floatingLabel');
        else
            $wrapper = $this->htmlFactory->createWrapper("errorContainer", 'error container');
        $container->addHtml($wrapper);
        // Přidání všech chyb
        foreach ($errors as $error) {
            if($this->isInputGroup)
                $errorItem = $this->htmlFactory->createWrapper("error", 'error inputGroupItem');
            elseif($this->floatingLabel && $this->floatingLabelAllowed)
                $errorItem = $this->htmlFactory->createWrapper("error", 'error floatingLabelItem');
            else
                $errorItem = $this->htmlFactory->createWrapper("error", 'error item');
            $wrapper->addHtml($errorItem);
            $errorItem ->addText($error);
        }
    }
    protected function renderValidFeedback(Html $container, $force = false): bool
    {
        $customFeedback = $this->control->getOption('feedback');
        if($customFeedback === null){ // Pokud je null, vezme zprávu z 'valid message'. Pro prázdný řetězec se nerenderuje.
            $feedback = $this->wrappers->getValue('valid message');
            if(!is_string($feedback) || !$feedback){
                if($force)
                    $feedback = '';
                else
                    return false;
            }
        }elseif($customFeedback === true){ // Pokud je true, bude se vytvářet, i kdyby ve 'valid message' nic nebylo.
            $feedback = $this->wrappers->getValue('valid message');
            if(!is_string($feedback))
                $feedback = '';
        }elseif(!is_string($customFeedback)){ // Pokud není string ani true|null, špatně zadáno, nic se nerenderuje.
            if($force)
                $feedback = '';
            else
                return false;
        }else{ // Pokud je jakýkoli řetězec, bude se renderovat.
            $feedback = $customFeedback;
        }

        //Translate
        $feedback = $this->control->translate($feedback);

        // Získání wrapperu a item podle inputGroup/floatingLabels
        if($this->isInputGroup) {
            $wrapper = $this->htmlFactory->createWrapper("validContainer", 'valid inputGroup');
            $item = $this->htmlFactory->createWrapper("valid", 'valid inputGroupItem');
        }elseif($this->floatingLabel && $this->floatingLabelAllowed) {
            $wrapper = $this->htmlFactory->createWrapper("validContainer", 'valid floatingLabel');
            $item = $this->htmlFactory->createWrapper("valid", 'valid floatingLabelItem');
        }else{
            $wrapper = $this->htmlFactory->createWrapper("validContainer", 'valid container');
            $item = $this->htmlFactory->createWrapper("valid", 'valid item');
        }
        $container->addHtml($wrapper);
        $wrapper->addHtml($item);
        // Přidání feedbacku
        $item->addText($feedback);
        return true;
    }
    protected function setFeedbackClasses(RendererHtml $element, string $name2D): void
    {
        if($this->control->hasErrors())
            $element->setClasses($this->wrappers->getValue('error ' . $name2D));
        elseif($this->isSubmitted())
            $element->setClasses($this->wrappers->getValue('valid ' . $name2D));
    }

    //---------------------------------------- helper methods -----------------------------------------
    protected function isSubmitted(): bool
    {
        if(!$this->control->getForm(false))
            return false;
        return (bool) $this->control->getForm()->isSubmitted();
    }

    /**
     * @param RendererHtml $htmlElement Html element ke vložení
     * @param RendererHtml $container Kam se vkládá
     * @param string|null $wrapper Případný wrapper elementu, zadaný stringem - bere se z pole $wrappers
     * @return RendererHtml
     */
    protected function renderHtmlElement(RendererHtml $htmlElement, Html $container): RendererHtml
    {
        //Pokud je element v inputGroup, nastaví se mu okraje (aby byly ty na okraji rounded) a výška elementu,
        //aby byly všechny elementy v inputGroup stejně vysoké
        if($this->isInputGroup){
            if($htmlElement->isOwn){
                $this->setBorders(RendererHtml::el());
            }else{
                $this->setBorders($htmlElement);
                $this->setHeight($htmlElement);
            }
        }
        //Vloží element do struktury
        $container->addHtml($htmlElement);
        return $container;
    }
    private function setHeight(RendererHtml $element): void
    {
        if($this->floatingLabel){
            $style = $this->wrappers->getValue('inputGroup ..floatingLabelHeight');
            $this->htmlFactory->setClasses($element,'inputGroup .floatingLabelHeight');

        }else{
            $style = $this->wrappers->getValue('inputGroup ..height');
            $this->htmlFactory->setClasses($element,'inputGroup .height');
        }
        if($style)
            $element->style .= $style;
    }

    private function setBorders(RendererHtml $element): void
    {

        // Každému prvku se přiřadí třídy v $wrappers['inputGroup']['.item']
        $this->htmlFactory->setClasses($element,'inputGroup .item');

        // Pokud není control první nebo poslední v inputGroup, nic nebude zaoblené
        if(!$this->firstInGroup && !$this->lastInGroup)
            return;
        // Uplně první prvek v každé inputGroup je .firstItem
        if($this->firstInGroup && !$this->prevInputGroupItem) {
            $this->htmlFactory->setClasses($element,'inputGroup .firstItem');
        }
        // U posledního control v inputGroup přiřazujeme .lastItem, pokud je předchozí prvek, tak mu bude zase odebrána
        if($this->lastInGroup){
            $this->htmlFactory->setClasses($element,'inputGroup .lastItem');
            if($this->prevInputGroupItem)
                $this->htmlFactory->setClasses($this->prevInputGroupItem,'inputGroup .lastItem', true);
        }
        $this->prevInputGroupItem = $element;
    }
}