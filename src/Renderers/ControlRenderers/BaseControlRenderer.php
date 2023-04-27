<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers\ControlRenderers;

use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\HtmlStringable;
use Nette\Utils\Html;
use Nette;

/**
 *
 * @property-read Html|HtmlStringable|null $label to, co reprezentuje label ve struktuře. Netýká se labelů případných items.
 * @property-read Html $element Reprezentuje element controlu ve stuktuře, což ovšem to není ekvivalentní se samotným.
 * controlem, např. u CheckboxList je to wrapper, ve kterém jsou jednotlivé items.
 * @property-read Html $parent Parent ve smyslu rodiče ve struktuře.
 */
abstract class BaseControlRenderer
{
    use Nette\SmartObject;

    /** @var BaseControl Control formuláře, předávaný v konstruktoru. */
    protected BaseControl $control;
    /** Container, do kterého se vykreslí control. Předává se metodě render(). */
    protected Html $container;

    private Html|HtmlStringable|null $controlLabel = null;
    private Html|null $controlElement = null;
    private Html|null $parentElement = null;

    protected Html|null $inputGroupWrapper = null;
    protected bool $floatingLabel;
    protected bool $inputGroup;
    protected bool $firstInGroup;
    protected bool $lastInGroup;
    protected bool $floatingLabelAllowed = false;

    protected Html|null $prevInputGroupItem = null;

    /** Pole s wrappery, předávané v konstruktoru. */

    protected Wrappers $wrappers;

    protected bool $isOwnElement = false;

    protected bool $clientValidation = true; //todo

    public function __construct(Wrappers $wrappers, BaseControl $control)
    {
        $this->wrappers = $wrappers;
        $this->control = $control;
        // Option 'floatingLabel' je vždy bool, ošetřeno v rendereru.
        $this->floatingLabel = $this->control->getOption('floatingLabel')  === true;
        // Option 'inputGroup' je vždy bool, ošetřeno v ControlWrappers.
        $this->inputGroup = $this->control->getOption('inputGroup')  === true;
        // Zbylé 2 jsou buď true/false, nebo neexistují (neměly by).
        $this->firstInGroup = $this->control->getOption('firstInInputGroup') === true;
        $this->lastInGroup = $this->control->getOption('lastInInputGroup') === true;
    }

    /**
     * Jediná public metoda, vyrenderuje daný control.
     * @param Html $container Sem vyrenderuje daný control.
     * @return void
     */
    public function render(Html $container) : void
    {
        $this->container = $container;
        // Element musí existovat vždy, reprezentuje control, proto se vytvoří rovnou zde
        // createElement vrací vždy nějaké HTML, přinejhorším Html::el() - má smysl pro Listy a CheckBox v inputGroup.
        $this->controlElement = $this->createElement();

        if($this->inputGroup)
            $this->renderToGroup($container);
        else
            $this->renderControl($container);
    }

    /**
     * Vyrenderuje celý control, pokud není v inputGroup.
     * @param Html $container
     */
    abstract protected function renderControl(Html $container);

    /**
     * Vyrenderuje celý control, pokud je v inputGroup.
     * @param Html $container
     */
    abstract protected function renderToGroup(Html $container);

//---------------------------------------- element controlu -----------------------------------------
    /**
     * Getter pro $this->element.
     * Vrátí element reprezentující control ve struktuře.
     * @return Html
     */
    protected function getElement() : Html
    {
        return $this->controlElement;
    }
    /**
     * Vytvoří element pro control.
     * Přetížením v odvozených třídách lze vytvořit jinak.
     * @return Html
     */
    protected function createElement() : Html
    {
        return $this->control->getControl();
    }
    /**
     * Renderuje element pro control.
     * @return void
     */
    protected function renderElement() : void
    {
        // Pokud je definovaný vlastní 'element', tj. u CheckBox v inputGroup, a všech CheckBoxList a RadioList.
        if($this->isOwnElement) {
            $this->parent->addHtml($this->element);
            if($this->inputGroup) // Pokud jsme v inputGroup, resetují se borders předchozího prvku.
                $this->setBorders(Html::el());
        }else {
            $this->renderHtmlElement($this->element, $this->parent);
        }
        $this->setupElement();
    }
    /**
     * Slouží k nastavení elementu , reprezentujícího control ve struktuře
     * Pro nastavení jednotlivého formulářového prvku slouží metoda setupControlElement
     * @return void
     */
    protected function setupElement()
    {
        /*if(!$this->isOwnElement){

        }*/
    }
    /**
     * Nastaví každému formulářovému prvku potřebné třídy. U Listů se tímto nastavuje každá item.
     * Button toto nevolá.
     * @param Html $element formulářový prvek
     * @return void
     */
    protected function setupControlElement(Html $element) : void
    {
        //Přiřadí třídu, pokud je na controlu error
       /* $element->class($this->wrappers->getValue('control .error'), $this->control->hasErrors());
        if($this->control->getForm(false))
            $element->class($this->wrappers->getValue('control .noerror'), !$this->control->hasErrors() && $this->control->getForm(false)->isSubmitted());*/ //todo
        $this->setFeedbackClasses($element, '.control');
        $element->class($this->wrappers->getValue('control .required'), $this->control->isRequired());
        $element->class($this->wrappers->getValue('control .optional'), !$this->control->isRequired());
        $element->class($this->wrappers->getValue('control .all'), true);
        // Přiřadí třídy, pokud jsou v option '.control'
        if(is_string($this->control->getOption('.control')) )
            $element->class($this->control->getOption('.control'), true);
    }
//---------------------------------------- parent element -----------------------------------------
    /**
     * Getter pro $this->parent
     * Vrátí element pro parent - rodičovský element elementu pro control.
     * Pokud neexistuje, vytvoří ho.
     * Pokud je uvedeno option "parent", a je do něj nahrána instance Html,
     * použije se tento dodaný element místo výchozího.
     * @return Html
     */
    protected function getParent() : Html
    {
        if($this->parentElement)
            return $this->parentElement;
        // Metoda create nám vždy vrací nějaké Html, pokud není definovaný parent wrapper (nebo je false či null)
        // i tak vrátí fragment Html::el()
        $this->parentElement = $this->createParentElement();
        return $this->parentElement;
    }
    /**
     * Vytvoří parent element pro control.
     * @return Html
     */
    abstract protected function createParentElement() : Html;
    /**
     * Vyrenderuje HTML rodiče, do kterého je vložen element, reprezentující control.
     * Rovnou do tohoto rodiče vyrenderuje control element!!
     * @return void
     */
    protected function renderParent(?Html $container = null)
    {
        if(!$container)
            $container = $this->container;
        // Nastavení parent elementu
        $this->setupParent();
        // Vložení do containeru
        $container->addHtml($this->parent);
        //!!!Rovnou vyrenderuje element
        $this->renderElement();
    }
    /**
     * Slouží k nastavení parent elementu contorolu.
     * @return void
     */
    protected function setupParent()
    {
        // Nastavení třídy u parent elementu, pokud je nastaven a je nastavena tato třída přes option ".parent".
        // Tj. pokud je parentElement fragment, nemá nastavení třídy žádný efekt, jak potřebujeme
        if(is_string($this->control->getOption('.parent')))
            $this->parent->class($this->control->getOption('.parent'), true);
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
     * @return Html|HtmlStringable|null label
     */
    protected function getLabel() : Html|HtmlStringable|null
    {
        if($this->controlLabel)
            return $this->controlLabel;

        // Pokud je objekt Html v nastavení 'label', Pak ho rovnou přiřadí.
        // Vůbec už nás dál nezajímá, co bylo v caption.
        $label = $this->control->getOption('label');
        if($label instanceof HtmlStringable){
            $this->controlLabel = clone $label;
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
     * @return Html
     */
    protected function createLabel() : Html
    {
        return $this->control->getLabel();
    }

    /**
     * Vloží label do zadaného containeru
     * @param Html $container kam se vloží label
     * @return void
     */
    protected function renderLabel(Html $container) : void
    {
        // Pokud není element labelu, nerenderuje se.
        if(!$this->label)
            return;
        // Pokud je zadaný vlastní Html element, je pouze vložen do containeru
        if($this->control->getOption('label') instanceof HtmlStringable){
            // I tak, pokud je element v inputGroup (a není to floating label), se resetují ruční borders předchozího elementu v inputGroup.
            // Možná zbytečné když label je vždy první v inputGroup :D
            if($this->inputGroup && !($this->floatingLabelAllowed && $this->floatingLabel))
                $this->setBorders(Html::el());
            $container->addHtml($this->label);
            return;
        }
        // Přidělení tříd a stylu labelu, podle toho jestli je v inputGroup a jestli je label floating label.
        if($this->inputGroup){
            // Pokud je to floating label, a je v inputGroup, přiděluje se i styl na z-index:5,
            // protože to Bootstrap5 podcenil - pokud je floating label zároveň v inputGroup, nefunguje bez toho správně.
            // A do containeru se nevkládá přes renderHtmlElement, protože není součástí "lajny" inputGroup, tj. nerenderuje se standardně.
            if($this->floatingLabelAllowed && $this->floatingLabel){
                $this->label->class($this->wrappers->getValue('label .inputGroupFloating'), true);
                $this->label->style .= $this->wrappers->getValue('label ..inputGroupFloatingStyle');
                $container->addHtml($this->label);
            }else {
                //Třída pro label v inputGroup (bez floatingLabel)
                $this->label->class($this->wrappers->getValue('label .inputGroup'), true);
                $this->renderHtmlElement($this->label, $container);
            }
        }else{
            if($this->floatingLabelAllowed && $this->floatingLabel)
                $this->label->class($this->wrappers->getValue('label .floatingLabel'), true);
            else
                $this->label->class($this->wrappers->getValue('label .class'), true);
            $this->renderHtmlElement($this->label, $container);
        }
        //Nakonec je voláno obecné nastavení labelu.
        $this->setLabel();
    }
    protected function setLabel()
    {
        // Pokud není element labelu, nebo je zadaný vlastní Html element, nic se nenastavuje.
        if(!$this->label || $this->control->getOption('label') instanceof HtmlStringable)
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
            $this->label->class($this->wrappers->getValue('label .required'), true);
        //Vlastni styly přidané přes option '.label'.
        if(is_string($this->control->getOption('.label')))
            $this->label->class($this->control->getOption('.label'), true);
    }
//---------------------------------------- description element -----------------------------------------
    protected function renderDescription(Html $container, string $wrapper = null) : void
    {
        //Description v option 'description'.
        $description = $this->control->getOption('description');
        // Pokud je option 'description' implicitně nastaveno na false, pak nepřidáváme element v žádném případě.
        if($description === false)
            return;
        //Při description, která je Html, se vloží pouze toto html
        if($description instanceof HtmlStringable) {
            //Reset tříd pro zaoblení rohů předchozího elementu.
            //Vkládá se prázdný, protože nastavení description je jen na uživateli.
            if($this->inputGroup)
                $this->setBorders(Html::el());
            $container->addHtml((string) $description);
            return;
        }
        //Translate
        $description = $this->control->translate($description);
        // Pokud není, nejdřív zjistíme, jestli se bude vytvářet.
        // I když není description definována, může se vytvářet, pokud je definovaný 'push' nebo 'requiredpush'.
        $push = $this->wrappers->getContent('description push');
        $requiredPush = $this->control->isRequired() ? $this->wrappers->getContent('description requiredpush') : '';
        // Pokud je null, může se vytvořit, pokud je zadaný 'description push' nebo je required a je zadaný 'description requiredpush'.
        if(is_null($description)){
            if(($push || $requiredPush) && !$this->control instanceof Button){
                $push = $push ?: '';
                $requiredPush = $requiredPush?: '';
                $description = $push . $requiredPush;
                $element = Html::el();
            }
            else// Pokud není zadaný, nevytváří se
                return;
        }else{ // Pokud není null, vytváří se z dané description
            if($description === true)
                $description = '';
            $requiredPrefix = $this->control->isRequired() ? $this->wrappers->getContent('description requiredprefix') : '';
            $requiredSuffix = $this->control->isRequired() ? $this->wrappers->getContent('description requiredsuffix') : '';
            // Přidání
            $description = $requiredPrefix . $this->wrappers->getContent('description prefix') .  $description . $this->wrappers->getContent('description suffix') . $requiredSuffix;
            $element = $this->inputGroup
                ? $this->getDefaultWrapper('description inputGroupItem')
                : $this->getDefaultWrapper('description item');
        }



        $element->addHtml($description);

        $wrapper = $wrapper ? $this->getDefaultWrapper($wrapper, $container) : $container;

        $this->renderHtmlElement($element, $wrapper);
    }
    //---------------------------------------- error element -----------------------------------------
    /**
     * Vypíše chyby v poli errors na contolu
     * @param Html $container kam se vloží
     * @return void
     */
    protected function renderErrors(Html $container) : void {
        $errors = $this->control->getErrors();
        $clientValidation = (bool) $this->control->getOption('clientValidation');
        $feedbackCreated = false;

        if($errors)
            $this->renderErrors2($container, $errors);
        elseif($this->isSubmitted())
            $feedbackCreated = $this->renderValidFeedback($container);

        if($clientValidation){
            if(!$errors)
                $this->renderErrors2($container, ['']);
            if(!$feedbackCreated)
                $this->renderValidFeedback($container, true);
        }
        // Zde využijeme toho, že errors se vždy renderují až nakonec, jinak by se to muselo udělat jinak
        // Pokud je $container stejný, jako parent element controlu, vše je podle "snadardního"
        // Bootstrap5, pokud není, musí se přidat na parent
        // Pokud parent neexistuje, nemá efekt, a v tu chvíli ho ani nepotřebujeme
        if($container !== $this->parent){
            $this->setFeedbackClasses($this->parent, '.parent');
        }
    }
    protected function renderErrors2(Html $container, array $errors) : void {
        //Získání wrapperu podle inputGroup/floatingLabels
        if($this->inputGroup)
            $wrapper = $this->getWrapper("errorContainer", 'error inputGroup', $container);
        elseif($this->floatingLabel && $this->floatingLabelAllowed)
            $wrapper = $this->getWrapper("errorContainer", 'error floatingLabel', $container);
        else
            $wrapper = $this->getWrapper("errorContainer", 'error container', $container);
        // Přidání všech chyb
        foreach ($errors as $error) {
            if($this->inputGroup)
                $errorItem = $this->getWrapper("error", 'error inputGroupItem', $wrapper);
            elseif($this->floatingLabel && $this->floatingLabelAllowed)
                $errorItem = $this->getWrapper("error", 'error floatingLabelItem', $wrapper);
            else
                $errorItem = $this->getWrapper("error", 'error item', $wrapper);
            $errorItem ->addText($error);
        }
    }
    protected function renderValidFeedback(Html $container, $force = false) : bool
    {
        // Pokud není zadán parametr $feedback, pokusí se ho získat z option 'feedback'.

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
        if($this->inputGroup) {
            $wrapper = $this->getWrapper("validContainer", 'valid inputGroup', $container);
            $item = $this->getWrapper("valid", 'valid inputGroupItem', $wrapper);
        }elseif($this->floatingLabel && $this->floatingLabelAllowed) {
            $wrapper = $this->getWrapper("validContainer", 'valid floatingLabel', $container);
            $item = $this->getWrapper("valid", 'valid floatingLabelItem', $wrapper);
        }else{
            $wrapper = $this->getWrapper("validContainer", 'valid container', $container);
            $item = $this->getWrapper("valid", 'valid item', $wrapper);
        }
        // Přidání feedbacku
        $item->addText($feedback);
        return true;
    }
    protected function setFeedbackClasses(Html $element, string $name2D) : void
    {
        if($this->control->hasErrors())
            $element->class($this->wrappers->getValue('error ' . $name2D), true);
        elseif($this->isSubmitted())
            $element->class($this->wrappers->getValue('valid ' . $name2D), true);
    }

    //---------------------------------------- helper methods -----------------------------------------

    protected function isSubmitted() : bool
    {
        if(!$this->control->getForm(false))
            return false;
        return (bool) $this->control->getForm()->isSubmitted();
    }

    /**
     * @param Html $htmlElement Html element ke vložení
     * @param Html $container Kam se vkládá
     * @param string|null $wrapper Případný wrapper elementu, zadaný stringem - bere se z pole $wrappers
     * @return Html
     */
    protected function renderHtmlElement(Html $htmlElement, Html $container, string|null $wrapper = null) : Html
    {
        //Pokud je zadán wrapper, vytvoří se a vloží do containeru
        $wrapper = $wrapper ? $this->getDefaultWrapper($wrapper, $container) : $container;
        //Pokud je element v inputGroup, nastaví se mu okraje (aby byly ty na okraji rounded) a výška elementu,
        //aby byly všechny elementy v inputGroup stejně vysoké
        if($this->inputGroup){
            $this->setBorders($htmlElement);
            $this->setHeight($htmlElement);
        }
        //Vloží element do struktury
        $wrapper->addHtml($htmlElement);
        return $wrapper;
    }


    private function setHeight(Html $el){
        if($this->floatingLabel){
            $style = $this->wrappers->getValue('inputGroup ..floatingLabelHeight');
            $el->class($this->wrappers->getValue('inputGroup .floatingLabelHeight'), true);
        }else{
            $style = $this->wrappers->getValue('inputGroup ..height');
            $el->class($this->wrappers->getValue('inputGroup .height'), true);
        }
        if($style)
            $el->style .= $style;
    }

    private function setBorders(Html $el){

        // Každému prvku se přiřadí třídy v $wrappers['inputGroup']['.item']
        $el->class($this->wrappers->getValue('inputGroup .item'), true);

        // Pokud není control první nebo poslední v inputGroup, nic nebude zaoblené
        if(!$this->firstInGroup && !$this->lastInGroup)
            return;
        // Uplně první prvek v každé inputGroup je .firstItem
        if($this->firstInGroup && !$this->prevInputGroupItem) {
            $el->class($this->wrappers->getValue('inputGroup .firstItem'), true);
        }
        // U posledního control v inputGroup přiřazujeme .lastItem, pokud je předchozí prvek, tak mu bude zase odebrána
        if($this->lastInGroup){
            $el->class($this->wrappers->getValue('inputGroup .lastItem'), true);
            if($this->prevInputGroupItem)
                $this->prevInputGroupItem->class($this->wrappers->getValue('inputGroup .lastItem'), false);
        }
        $this->prevInputGroupItem = $el;
    }

    /**
     * Získá wrapper, buď zadaný uživatelem (přes option), nebo defaultní z pole $wrappers.
     * Daný wrapper taky může být neexistující, v tom případě nic nevytváří a vrací $container.
     * @param string $option option na daném control, které se týká konkrétního wrapperu (co je v $default).
     * @param string $default cesta k defaultnímu wrapperu v poli $wrappers.
     * @param Html|null $container kam se vloží vytvořený wrapper. Pokud není zadán, vytvoří se fragment.
     * @return Html Vytvořený wrapper (přidaný do $container), nebo $container, pokud se wrapper nevytvářel.
     */
    protected function getWrapper(string $option, string $default, Html|null $container = null) : Html
    {
        if(is_null($container))
            $container = Html::el();
        // Získá option na controlu, jehož klíčem je $option.
        $setting = $this->control->getOption($option);
        if(is_null($setting) || $setting === true) // null nebo true tu má stejný význam - bere se defaultní wrapper z pole $wrappers;
            return $this->getDefaultWrapper($default, $container);
        elseif($setting === false) // false znamená, že se vytvářet nebude.
            return $container;
        elseif($setting instanceof Html) { // Pokud je Html, naklonuje se a vloží do $container.
            $wrapper = clone $setting;
            $container->addHtml($wrapper);
            return $wrapper;
        }
        // Jinak zbývá string. Buď je v něm stringová reprezentace wrapperu, nebo klíč třetí dimenze v poli $wrappers.
        // Pokud je v $setting klíč třetí dimenze v poli $wrappers, použije se tento wrapper.
        if($this->wrappers->isChosenWrapper($default, $setting))
            return $this->getDefaultWrapper($default, $container, $setting);
        // Pokud není, je v $setting stringová definice wrapperu.
        $wrapper = Html::el($setting);
        $container->addHtml($wrapper);
        return $wrapper;
    }

    /**
     * Získá wrapper $name definovaný v $this->wrappers a vloží ho do $container.
     * Pokud není zadán $container, vytvoří prázdný Html element.
     * Pokud neexistuje wrapper, nebo je null, vrací $container.
     * @param string $name 2-3 řetězce, oddělené mezerou, reprezentující klíče v poli $this->wrappers. Např. 'pair container'.
     * @param Html|null $container Container, do kterého se vloží vytvořený (pokud) wrapper.
     * @param string $different3D Pokud chceme u 3D pole vybrat jiný 3tí klíč, než je uveden v $name.
     * @return Html Vytvořený wrapper, nebo $container pokud nebyl vytvořen.
     */
    protected function getDefaultWrapper(string $name, Html $container = null, string $different3D = '') : Html
    {
        //Pokud není zadán container, vytvoří prázdný element.
        $container = $container ?: Html::el();
        //Načtení z pole $wrappers
        $wrapper = $this->wrappers->getWrapper($name, $different3D);
        //V případě, že není wrapper vrací container
        if(!$wrapper)
            return $container;
        //Vytvořený wrapper přidá do containeru
        $container->addHtml($wrapper);
        return $wrapper;
    }
}