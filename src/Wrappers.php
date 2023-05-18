<?php

namespace Jdvorak23\Bootstrap5FormRenderer;

use ArrayAccess;
use Countable;
use Jdvorak23\Bootstrap5FormRenderer\Renderers\RendererHtml;
use Nette\Utils\Html;

/**
 * Implementuje pole $wrappers, na stejném principu jako v původním Nette\Forms\Rendering\DefaultFormRenderer.
 * Potřebujeme více wrapperů, takže je samozřejmě obsáhlejší.
 * Pracuje se s ním stejě jako s původním polem $wrappers (implementuje ArrayAccess).
 * Poskytuje několik metod na získávání dat z pole $wrappers, včetně vytváření Html elementů na základě těchto dat (spíše Internal).
 * Jakémukoli (2D) wrapperu můžeme některé třídy definovat zvlášť, což může být užitečné.
 *   Např. se podívej na $wrappers['group']['col'] - tomuto wrapperu budou přiřazeny ještě všechny třídy,
 *   které jsou v $wrappers['group']['.col']. toto se děje automaticky.
 *   Např. $wrappers['group']['.row'] zde není definováno, pokud si ho definujeme, automaticky se třídy přiřadí k wrapperu $wrappers['group']['row'].
 * Pravděpodobná je potřeba změny některých wrapperů - vlastní pole $wrappers - stačí si vytvořit potomka této třídy (Wrappers)
 *   a přetížit pole $wrappers (zkopírovat odtud). Následně se přiřadí $renderer->wrappers = new MyWrappers();
 *
 * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Wrappers
 * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/Layout
 * https://github.com/jdvorak23/bootstrap5-form-renderer/wiki/css
 */
class Wrappers implements ArrayAccess
{
    /** @var array definice wrapperů, stejný princip jako u DefaultFormRenderer */
    protected array $wrappers = [
        'form' => [
            // Funguje stejně jako u DefaultFormRenderer. Pozor, tento wrapper se generuje do formu
            'container' => null,
            // Toto je pro errory na celém formu. Errory na jednotlivých controls se řeší pod 'error'
            // Wrapper for all errors on whole form. 'form-errors' is selector for client validation javascript
            'errorContainer' => 'div class="form-errors"',
            'errorItem' => 'div class="alert alert-danger"', // Wrapper pro jednotlivou chybu.
        ],
        // Třídy a wrappery pro Group, 'row' a 'col' se týká i zbylých controls, co nejsou v Group
        'group' => [
            'container' => 'fieldset class="mb-3"', // Container pro prvky v Group, nejčastěji fieldset.
            'label' => 'legend class="mb-0 lh-1"', // Wrapper pro label, pouze pokud je label string.
            'description' => 'p', // Wrapper pro description, pouze pokud je description string.
            // Defaultní grid wrapper na úrovni jednotlivé Group (nebo zbylých controls co nejsou v Group)
            // To, jestli bude generován, závisí na $row z konstruktoru (stanovuje default)
            //  a dále na option 'row' na dané Group - pok je definovaný, bere se tento, jinak defaultní podle konstruktoru
            'row' => 'div class="row g-3"', // mt-0 gy-3 mb-3
            // Defaultní wrapper pro 'row' o řádek výše. Smysl má jen, pokud je tento 'row' existuje.
            'col' => 'div class="col-md-6"',
            // Jakémukoli 2D wrapperu v poli wrappers se automaticky přiřadí třídy,
            //   které jsou v klíči se stejným jménem, pouze začínajícím tečkou.
            // Tj. třída mb-3 se přidá ke třídám wrapperu $wrappers['group']['col'] o řádek výše.
            '.col' =>''
        ],
        // Třídy a wrappery pro conrols

        // Renderer umožňuje až 2 nezávislé grid systémy. Jeden je definovaný výše na úrovni Group,
        //   tento je na úrovni jednotlivých controls. Nastavuje se na prvním conrol, kde chceme mít grid
        // Např. $form->addText(...)->setOption('row', true). Každý další control v dané Group (nebo zbylých controls co v Group nejsou),
        //   bude automaticky vkládán do této grid, obalen 'col' wrapperem.
        // V případě, že chceme mít v grid jenom nějaké controls, na prvním control, které už v tomto grid nechceme,
        //   nastavíme $control->setOption('row', false) a tento control už v grid nebude.
        // Nebo voláme $control->setOption('row', true), což má za následek ukončení předchozího grid
        //   a rovnou nastartování nového.
        'controls' => [
            'row' => 'div class="row gy-3"',
            'col' => 'div class="col-12"',
            //'.col' => 'mb-3',
            // Všechny controls, které jsou součástí jedné inputGroup, budou vloženy do tohoto jednoho wrapperu.
            // Pokud je nastavený grid systém výše, bude jedna inputGroup právě v jednom wrrapperu 'col' (controls v tom případě patří k sobě).
            // To má za následek, že jakékoli option 'row', nebo 'col' na controlu, který je druhý a další v inputGroup, nemají smysl a budou igrnorovány.
            'inputGroup' => 'div class="input-group flex-nowrap"',
            // Toto nastavení je pro všechny buttons, které NEJSOU v inputGroup (jinak jsou přiřazovány do inputGroup o řádek výše).
            // Všechna tlačítka, která jsou ve formuláři za sebou, budou vložena do jednoho stejného wrapperu definovaného zde (velmi podobně funguje i DefaultFormRenderer).
            // Pro option 'row' a 'col' tedy v tomto případě platí všechna omezení jako o řádek výše u inputGroup -
            //   - u druhého a dalšího tlačítka v řadě nemají smysl a budou ignorovány.
            'buttons' => 'div class="d-flex justify-content-center align-items-end p-3"',
        ],

        // Až potud byly definovány wrappery, někde v kódu definovány jako 'horní'. Tyto wrappery renderuje třída GroupRenderer
        // Odtud jsou definovány wrappery, občas označené jako 'dolní' - ty už patří ke každému jednotlivému control,
        //   a jsou renderovány jednotlivými renderery, potomky BaseControlRenderer.

        // Základní rozdělení při renderování jednotlivého control je v tom, jestli je v inputGroup, nebo není.
        // Defaultně není, pokud chceme control v Bootstrapové(5) inputGroup, nastavíme mu option 'inputGroup'.
        // Např. $control->setOption('inputGroup', true). Tímto "nastartujeme" inputGroup a každý další
        //   control v dané ControlGroup (nebo zbylých controls co v ControlGroup nejsou) bude automaticky přidán do této stejné inputGroup.
        // Pokud už nějaké control v inputGroup nechceme, nastavíme mu $control->setOption('inputGroup', false).
        // Pokud nějaké control v inputGroup chceme, ale chceme, aby začínalo novou inpitGroup, prostě mu nastavíme zase
        //   $control->setOption('inputGroup', true).
        // Správné vyrenderování prvku do inputGroup je nejsložitější na layout, tj. v případě inputGroup
        //   se jednomu control renderuje struktura:
        // <wrapper>
        //    <container>
        //       <label.../>
        //       <element.../>
        //       <description.../>
        //    </container>
        //    <errorContainer><errors on control.../></errorContainer>
        // </wrapper>
        //  ,zde pod 'inputGroup' je definice toho, co bude reprezentovat <wrapper> a <container>:
        'inputGroup' => [
            // "Horní" 'wrapper', do kterého se vkládá container a error.
            // Podle třídy controlu se defaultně zvolí buď wrapper 'shrink' nebo 'grow'.
            // Často chceme jemnější rozlišení, např. pokud vedle sebe máme formulářové inputy pro ulici a číslo popisné,
            //   nejspíše chceme více místa pro ulici a stačí nám malý input pro číslo.
            // Toho dosáhneme nejsnáze Bootstrapovými třídami col-*
            // Zde si můžeme definovat jakékoli vlastní "přesnějsí" wrappery (pár jsem jich už přidal - 'xshort' - 'xlong').
            // Pak nám stačí nastavit na jednotlivém control option 'wrapper'. Tj.:
            // $control('street_number')->setOption('wrapper', 'xshort');
            // Option 'wrapper' má samozřejmě smysl jedině v případe, že je control v inputGroup, jinak je ignorováno.
            'wrapper' => [
                // Pro controls v dané inputGroup, které se nemají roztahovat.
                'shrink' => 'div class=""',
                // Pro všechny elementy v dané inputGroup, které se mají roztahovat (a tím vyplnit zbávající prostor).
                'grow' => 'div class="flex-fill"',
                // Own specific wrappers
                'xxshort' => 'div class="col-6 col-sm-3 col-md-3 col-lg-2"',
                'xshort' => 'div class="col-6 col-sm-4 col-md-3 col-lg-2"',
                'short' => 'div class="col-12 col-sm-5 col-md-4 col-lg-3"',
                'medium' => 'div class="col-12 col-sm-6 col-md-6 col-lg-4"',
                'long' => 'div class="col-12 col-sm-12 col-md-8 col-lg-6"',
                'xlong' => '',
                // Class appended to all OWN wrappers
                '.own' =>  'flex-fill',
            ],
            // U 3D wrapperů toto funguje malinko jinak. '.wrapper' se přiřadí všem defaultním, tj 'shrink' nebo 'grow'
            // Ale vlastním wrapperům se toto nepřiřadí, těm se přiřadí .own v 3D poli.
            '.wrapper' => '',
            // Parent (container) jednotlivého control elementu, přidává se sem label (před) a description (za).
            'container' => [
                //Pro všechny, pokud není nastaven floatingLabel
                'standard' => 'div class="input-group flex-nowrap"',
                //Pokud je nastavený floatingLabel, a control ho podporuje (tj. všechny inputy, select, textarea).
                'floating' => 'div class="form-floating input-group flex-nowrap"',
            ],
            // Třídy pro jednotlivé elementy v inputGroup. Pomocí nich se ručně zaoblují správně rohy.
            // Toto je nutné kvůli zvolenému layoutu, aby se správně vykreslovaly errors.
            // Layout, který je v základu v manuálu Bootstrap5, který automaticky zaobluje rohy je nepoužitelný,
            //   kvůli správnému renderování elementů pro výpis chyb (errors).
            // Třídy v .item dostanou všechny items, tím se i resetují borders tam, kde je nechceme
            // 'item' je jednotlivá položka v inputGroup. Je to label, samotný control element, nebo description.
            '.item' => 'rounded-0',
            // Přiřadí se prvnímu prvku v inputGroup
            '.firstItem' => 'rounded-start',
            // Přiřadí se poslednímu prvku v inputGroup
            '.lastItem' => 'rounded-end',
            // Styly - Bohužel, Bootstrap5 nedefinoval žádné třídy pro height, které používá.
            // Pokud nemáme v inputGroup nastavené floatingLabel, ještě to jde, drtivá většina elementů
            //   má správnou height díky třídám input-group (resp. input-group-sm a input-group-lg).
            // Problém nastane pouze v případě, že se do inputGroup snažíme nacpat něco, s čím Bootstratp5 nepočítal,
            //   jako např. TextArea nebo MultiSelectBox, tyto elementy budou "přečuhovat".
            // Stylem '..height' je usměrníme do stejné height, jakou mají ostatní prvky v inputGroup.
            // Elementy pod třídou input-group mají height = calc(1.5em + 0.75rem + 2px);
            //                     input-group-sm = calc(1.5em + 0.5rem + 2px);
            //                     input-group-lg = calc(1.5em + 1rem + 2px);
            '..height' => 'height: calc(1.5em + 0.75rem + 2px);',
            // To samé pro elementy, které mají floatingLabel - ty jsou ještě vyšší.
            '..floatingLabelHeight' => 'height: calc(3.5rem + 2px);',
            // TODO Because of lack of Bootstrap5 proper classes, You should define your own classes for elements in inputGroup:,
            //'.height' => 'my-height', // e.g.: .my-height {height: calc(1.5em + 0.75rem + 2px);}
            // TODO and for elements in inputGroup with floatingLabel:
            //'.floatingLabelHeight' => 'my-floating-label-height' //e.g.: .my-floating-label-height{height: calc(3.5rem + 2px);}
            // TODO After that, uncomment those, and comment '..height' and '..floatingLabelHeight' - they are not needed anymore.
        ],

        //Pro jednotlivé controls, !mimo! inputGroup
        'container' =>[
            // Pro CheckboxList a RadioList. Checkbox se vykresluje jako jednotlivá item, tj. wrapper pro checkbox mimo inputGroup je 'control listItem'
            'list' => 'div class="d-flex flex-column justify-content-center h-100"',
            // Wrapper pro elementy s floatingLabel
            'floatingLabel' => 'div class="form-floating"',
            // Wrapper pro jednotlivý button
            'button' => 'div class="me-2"',
            // Wrapper pro všechny ostatní controls
            'default' => 'div',
        ],
        'control' => [
//Elements representing control and items
            // Element reprezentující CheckboxList a RadioList.
            'list' => null,
            // Jednotlivý checkbox či radio mimo inputGroup.
            'listItem' => 'div class="form-check"',
            // Wrapper reprezentující element CheckboxList, RadioList a Checkbox (checkbox se do inputGroup vykresluje stejně jako CheckboxList s jednou item)
            'listInputGroup' => 'div class="input-group-text gap-2 form-control list-element"', // list-element = selector pro javascript
            // Wraper pro jednotlivý checkbox či radio v inputGroup
            'listInputGroupItem' => 'div',
//Classes - Button only
            // Nejdříve tlačítka, ta jsou samostatně. Platí pro ně pouze jejich třídy.
            // Žádné další třídy, uvedené dále, se tlačítkům nepřiřazují.
            // Button v inputGroup
            '.inputGroupButton' => 'btn btn-outline-secondary',
            // Mimo inputGroup
            '.submit' => 'btn btn-primary',
            '.reset' => 'btn btn-secondary',
            '.button' => 'btn btn-secondary',
            '.image' => '',
            // Tyto třídy dostanou všechny jednotlivé elementy, reprezentující control ve formuláři:
//Classes - general (but not for buttons)
            '.all' => '', // Všechny formulářové prvky, mimo buttons.
            '.required' => '',
            '.optional' => '',
//Classes - individual
            // A tyto jsou pro zbylé jednotlivé elementy, podle druhu
            // Každý chceckbox
            '.checkbox' => 'form-check-input',
            // Každé radio
            '.radio' => 'form-check-input',
            '.select' => 'form-select',
            '.textarea' => 'form-control',
            '.text' => 'form-control',
            '.password' => 'form-control',
            '.email' => 'form-control',
            '.file' => 'form-control',
            '.number' => 'form-control text-end',
            '.search' => 'form-control',
            '.color' => 'form-control form-control-color',
            '.range' => 'form-range',
            '.date' => 'form-control',
            '.datetime-local' => 'form-control',
            '.month' => 'form-control',
            '.week' => 'form-control',
            '.time' => 'form-control',
            '.tel' => 'form-control',
            '.url' => 'form-control',
        ],
        'label' => [
//Label classes
            // Třída pro floating label v inputGroup.
            '.inputGroupFloating' => 'form-label', //TODO add class for z-index 5
            // Třída pro label v inputGroup (bez floating label).
            '.inputGroup' => 'input-group-text',
            // Třída pro floating label mimo inputGroup
            '.floatingLabel' => 'form-label',
            //Třída pro label v Checkbox.
            '.checkbox' => 'form-check-label',
            //Třída pro labely jednotlivých item v CheckboxList a RadioList.
            '.item' => 'form-check-label',
            //Třída pro všechny ostatní
            '.class' => 'form-label',
            // Styl pro floating label, který je v inputGroup - z-index se musí nastavit, Bootstrap nesprávně přiděluje.
            // Lepší vytvořit třídu, a tu pak přiřadit do '.inputGroupFloating' o řádek výše.
            '..inputGroupFloatingStyle' => 'z-index: 5;', //TODO after you added  class for z-index 5 to $wrappers['label']['.inputGroupFloating'], comment this line
            // Třída přidáváná k labelu který je k required controlu
            '.required' => '',
//Label affixes
            // Řetězce, přidávané před / za element v labelu. //
            // Vloží před / za každý label, pokud neni definován vlastní label element (setOption('label', HtmlStringable $element);
            // Prefix přidaný na začátek každého labelu (nikoli do jednotlivých labelů items u ChecboxList a RadioList).
            'prefix' => '',
            // Jako výše, který je required. Vkládá se před případný 'prefix'.
            'requiredprefix' => '',
            // Suffix přidaný do každého labelu (nikoli do jednotlivých labelů items u ChecboxList a RadioList).
            'suffix' => '',
            // To samé, ale jen pro ty, co jsou nastavené jako required (dává se za případný suffix).
            'requiredsuffix' => '',
        ],
        // Description se do inputGroup renderuje na panel.
        // Pokud je description HtmlStringable, nic se nevytváří, pouze se vloží, tj. žádné následující nastaveni nemá vliv.
        'description' => [
//Description item
            // Element reprezentující description v inputGroup
            'inputGroupItem' => 'div class="input-group-text"',
            // Element reprezentující description mimo inputGroup
            'item' => 'small class="ms-1 d-inline-block"',
//Description container
            'container' => null,
//Description affixes
            // Řetězce, přidávané před / za description. Může být i HtmlStringable.
            // Vloží před každou generovanou description.
            'prefix' => '',
            //Vloží za každou generovanou description.
            'suffix' => '',
            // Vloží před každou generovanou description controlu, který je required. Vkládá se před případný 'prefix'.
            'requiredprefix' => '',
            // Vloží za každou generovanou description controlu, který je required. Vkládá se za případný 'suffix'.
            'requiredsuffix' => '',
//Automatic content instead of void description (===null):
            // Pokud je option 'description' null, tedy vůbec se nenastavuje, normálně by nebyla vytvořena.
            // Pokud je ale zadáno 'push' (tj. nikoli prázdný řetězec), vytvoří se s hodnotou 'push' a případně 'requiredpush'.
            // Nevytváří se u Button!
            // Nevytváří žádný standatdní wrapper pro description, počítá se zde s vlastním Html!
            'push' => '',
            // To samé, ale jen pro povinné controls (pokud je obojí, vloží se $push . $requiredpush)
            'requiredpush' => '',
        ],
        // Týká se errorů na jednotlivých controls.
        'error' => [
//Classes - validation
            // .error a .noerror třídy se přiřadí všude tam, kde je konkrétní element controlu, pro List ke každé item.
            // Jinak řečeno, všude kde je attribut name, budou přidány tyto třídy podle validace.
            '.control' => 'is-invalid', //Pouze, pokud je formulář validován
            // Pro správnou funkci zobrazovaní Bootstap5 validační zprávy (invalid-feedback, valid-feedback), musí být element
            // validační zprávy sibling elementu, kde je is-invalid (is-valid) třída.
            // Vzhledem k tomu je potřeba is-valid/invalid propagovat na wrapper, který je siblingem error containeru.
            // U CheckBoxList, RadioList, a Checkbox v inputGroup se proto přidává ještě .listError na prvek,
            // reprezentující element. Pokud tento neexistuje (jako ve standardním wrapperu např. 'control list',
            // bude tato třída přiřazena potomkům, v tomhle případě každému wrapperu 'control listItem' (pokud existuje).
            '.list' => 'is-invalid',
            // Vzhledem ke složitějším layoutům, např. v inputGroup, někdy je potřeba, aby tuto třídu měl až rodič,
            // který je v tom případě siblingem error containeru - ten dostane třídu .parentError (pouze, je-li třeba).
            '.parent' => 'is-invalid',
//Errors container
            // Wrappery pro všechny chyby na daném control. Do něj budou vypsány jednotlivé errory:
            // Pro errory na control v inputGroup.
            'inputGroup' => 'div class="invalid-feedback mx-1"',
            // Pro errory na control s floatingLabel (nikoli v inputGroup).
            'floatingLabel' => 'div class="invalid-feedback mx-1"',
            // Pro všechny zbylé.
            'container' => 'div class="invalid-feedback mx-1"', //wrapper error na controlu
//Error item
            // Elementy, reprezentující jednotlivou chybu (do nich bude vypsán text erroru):
            // Pro errory na control v inputGroup.
            'inputGroupItem' => 'span class="me-1"',
            // Pro errory na control s floatingLabel (nikoli v inputGroup).
            'floatingLabelItem' => 'span class="me-1"',
            // Pro všechny zbylé.
            'item' => 'span class="me-1"',
        ],
        // Týká se errorů na jednotlivých controls.
        'valid' => [
//Classes - validation
            // .error a .noerror třídy se přiřadí všude tam, kde je konkrétní element controlu, pro List ke každé item.
            // Jinak řečeno, všude kde je attribut name, budou přidány tyto třídy podle validace.
            '.control' => 'is-valid', //Pouze, pokud je formulář validován
            // Pro správnou funkci zobrazovaní Bootstap5 validační zprávy (invalid-feedback, valid-feedback), musí být element
            // validační zprávy sibling elementu, kde je is-invalid (is-valid) třída.
            // Vzhledem k tomu je potřeba is-valid/invalid propagovat na wrapper, který je siblingem error containeru.
            // U CheckBoxList, RadioList, a Checkbox v inputGroup se proto přidává ještě .listError na prvek,
            // reprezentující element. Pokud tento neexistuje (jako ve standardním wrapperu např. 'control list',
            // bude tato třída přiřazena potomkům, v tomhle případě každému wrapperu 'control listItem' (pokud existuje).
            '.list' => 'is-valid',
            // Vzhledem ke složitějším layoutům, např. v inputGroup, někdy je potřeba, aby tuto třídu měl až rodič,
            // který je v tom případě siblingem error containeru - ten dostane třídu .parentError (pouze, je-li třeba).
            '.parent' => 'is-valid',
//Valid feedback container
            // Wrappery pro všechny chyby na daném control. Do něj budou vypsány jednotlivé errory:
            // Pro errory na control v inputGroup.
            'inputGroup' => 'div class="valid-feedback mx-1"',
            // Pro errory na control s floatingLabel (nikoli v inputGroup).
            'floatingLabel' => 'div class="valid-feedback mx-1"',
            // Pro všechny zbylé.
            'container' => 'div class="valid-feedback mx-1"', //wrapper error na controlu
//Valid feedback item
            // Elementy, reprezentující jednotlivou chybu (do nich bude vypsán text erroru):
            // Pro errory na control v inputGroup.
            'inputGroupItem' => 'span class="me-1"',
            // Pro errory na control s floatingLabel (nikoli v inputGroup).
            'floatingLabelItem' => 'span class="me-1"',
            // Pro všechny zbylé.
            'item' => 'span class="me-1"',
//Valid feedback default message
            'message' => 'It looks good.',
        ],
        'hidden' => [
            'container' => null,
        ],
    ];

    /**
     * Vrátí hodnotu v poli $wrappers definovanu klíči oddělenými mezerou v parametru $name.
     * Např. pokud je $name 'group container', vrátí hodnotu z  $wrappers['group']['container']
     * @param string $name Wrapper reprezentovaný klíči ve stringu oddělené mezerou. např. 'group container' => $wrappers['group']['container']
     * @return mixed nalezenou hodnotu, nebo null, pokud klíče neexistují.
     */
    public function getValue(string $name): mixed
    {
        $name = explode(' ', $name, 3);
        if(count($name) == 3)
            $data = &$this->wrappers[$name[0]][$name[1]][$name[2]];
        elseif(count($name) == 2)
            $data = &$this->wrappers[$name[0]][$name[1]];
        else
            $data = null;
        return $data;
    }

    /**
     * Nalezne wrapper v poli $wrappers podle parametrů a vrátí ho.
     * Wrapperu přiřadí třídy, pokud jsou definovány. Např. $name = 'group container',
     *  pokud existuje $wrappers['group']['.container'], veme tuto hodnotu a přiřadí třídy k wrapperu.
     * @param string $name Wrapper reprezentovaný klíči ve stringu oddělené mezerou. např. 'group container' => $wrappers['group']['container']
     * @param string $different3D Pokud $name obsahuje 3 klíče např 'inputGroup wrapper grow' podívá se,
     *  jestli existuje $wrappers['inputGroup']['wrapper'][$different3D]. Pokud ano, vrací toto, pokud ne, vrací $wrappers['inputGroup']['wrapper']['grow']
     * @return RendererHtml|null Vrací wrapper definovaný v poli $wrappers, nebo null, pokud je wrapper definovaný jako null nebo neexistuje
     */
    public function getWrapper(string $name, string $different3D = ''): RendererHtml|null
    {
        $keys = explode(' ', $name, 3);
        // Existuje vlastní 3D wrapper?
        $isOwnWrapper = $different3D && count($keys) == 3 && isset($this->wrappers[$keys[0]][$keys[1]][$different3D]);
        // Pokud je zadané $different3D, pokusí se získat tento prvek místo uvedeného defaultního (3ti v $name).
        $data = $isOwnWrapper ? $this->wrappers[$keys[0]][$keys[1]][$different3D] : $this->getValue($name);
        // V případě, že není wrapper, nebo je prázdný.
        if(!$data)
            return null;
        // Vytvoří wrapper, nebo naklonuje Html objekt uložený ve $wrappers.
        $wrapper = $data instanceof Html ? RendererHtml::fromNetteHtml($data) : RendererHtml::el($data);
        // Vytvořenému wrapperu přiřadí třídu, pokud existuje.
        // Ať už chceme hodnotu 2D nebo 3D wrapperu, třídu hledá ve 2D.
        // Např. pokud je $name 'inputGroup wrapper grow', třídu bude hledat ve $wrappers['inputGroup']['.wrapper']
        // Ovšem pokud máme-li vlastní 3D wrapper (definovaný ve wrappers), vezme místo toho .own, pokud existuje
        if($isOwnWrapper)
            $wrapper->setClasses($this->getValue($keys[0] . ' ' . $keys[1] . ' .own'));
        elseif(isset($this->wrappers[$keys[0]]['.' . $keys[1]]))
            $wrapper->setClasses($this->wrappers[$keys[0]]['.' . $keys[1]]);
        return $wrapper;
    }

    /**
     * Existuje vlastní 3D wrapper v poli wrappers?
     * @param string $name Wrapper reprezentovaný klíči ve stringu oddělené mezerou. např. 'inputGroup wrapper grow' => $wrappers['inputGroup']['wrapper']['grow']
     * @param string $different3D Pokud $name obsahuje 3 klíče např 'inputGroup wrapper grow' podívá se,
     *  jestli existuje $wrappers['inputGroup']['wrapper'][$different3D]. Pokud ano, vrací true, pokud ne, false
     * @return bool true, je-li definován $different3D, false, pokud není.
     */
    public function isChosenWrapper(string $name, string $different3D): bool
    {
        $keys = explode(' ', $name, 3);
        return count($keys) == 3 && isset($this->wrappers[$keys[0]][$keys[1]][$different3D]);
    }

    /**
     * Získá z pole wrappers obsah, tj. je li na pozici v poli wrappers, definované pomocí parametru $name,
     * instance Html, vrátí ji. Pokud tam je cokoli jiného, pokusí se to převést na string a vrátit.
     * Používá se na prefixy/suffixy u labelů a description, to je jediný content v poli wrappers.
     * @param string $name
     * @return RendererHtml|string
     */
    public function getContent(string $name) : RendererHtml|string
    {
        $data = $this->getValue($name);
        return $data instanceof Html ? RendererHtml::fromNetteHtml($data) : (string) $data;
    }
    /*
     * Dále jsou už jen implementace ArrayAccess
     */

    public function offsetExists(mixed $offset) : bool
    {
        return isset($this->wrappers[$offset]);
    }

    public function &offsetGet(mixed $offset) : mixed
    {
        return $this->wrappers[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value) : void
    {
        if (is_null($offset)) {
            $this->wrappers[] = $value;
        } else {
            $this->wrappers[$offset] = $value;
        }
    }
    public function offsetUnset(mixed $offset) : void
    {
        unset($this->wrappers[$offset]);
    }
}