<?php

namespace Jdvorak23\Bootstrap5FormRenderer\Renderers;
use Jdvorak23\Bootstrap5FormRenderer\Wrappers;
use Nette\Forms\ControlGroup;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\Html;
use Nette;
/**
 *
 * @property-read array $options
 */
class HtmlWtf
{
    use Nette\SmartObject;

    protected array|BaseControl|ControlGroup $optionsSource;
    public function __construct(public Wrappers $wrappers,
                                array $options = [])
    {
        $this->optionsSource = $options;
    }

    /**
     * Vytvoří wrapper buď zadaný přes $option, nebo $default. Pokud není wrapper, vrací fragment.
     * @param string $option Nastavení, ze kterého se bere případný vlastní wrapper, či vlastní nastavení wrapperu.
     * @param string $default Adresa defaultního wrapperu v poli $wrappers.
     * @param string|bool $classOption K vytvořenému wrapperu přiřadí třídy, které jsou v option $classOption. Pokud je true (default), vezme: '.' . $option
     * Tedy pokud chceme wrapper 'parent', pokusí se přiřadit třídy v option '.parent'
     * @return RendererHtml
     */
    public function createWrapper(string $option, string $default, string|bool $classOption = true): RendererHtml
    {
        if($classOption === true)
            $classOption = '.' . $option;
        elseif($classOption === false)
            $classOption = '';
        // Získá option na controlu, jehož klíčem je $option.
        $setting = &$this->options[$option];
        if($setting === null || $setting === true) { // null nebo true tu má stejný význam - bere se defaultní wrapper z pole $wrappers;
            $wrapper = $this->createDefaultWrapper($default);
            $this->setOptionClasses($wrapper, $classOption);
            return $wrapper;
        }elseif($setting === false) // false znamená, že se vytvářet nebude.
            return RendererHtml::el();
        elseif($setting instanceof Html) { // Pokud je Html - own wrapper, "naklonuje se"
            $wrapper = RendererHtml::fromNetteHtml($setting);
            $wrapper->isOwn = true;
            $this->setOptionClasses($wrapper, $classOption);
            return $wrapper;
        }
        // Jinak zbývá string. Buď je v něm stringová reprezentace wrapperu, nebo klíč třetí dimenze v poli $wrappers.
        // Pokud je v $setting klíč třetí dimenze v poli $wrappers, použije se tento wrapper.
        if($this->wrappers->isChosenWrapper($default, $setting)){
            $wrapper = $this->createDefaultWrapper($default, $setting);
            $this->setOptionClasses($wrapper, $classOption);
            return $wrapper;
        }
        // Pokud není, je v $setting stringová definice wrapperu - own wrapper.
        $wrapper = RendererHtml::el($setting);
        $wrapper->isOwn = true;
        $this->setOptionClasses($wrapper, $classOption);
        return $wrapper;
    }

    protected function setOptionClasses(RendererHtml $wrapper, string $classOption): RendererHtml
    {
        if(!$classOption)
            return $wrapper;
        $setting = &$this->options[$classOption];
        $wrapper->setClasses($setting);
        return $wrapper;
    }

    /**
     * Získá wrapper $name definovaný v $this->wrappers a vloží ho do $container.
     * Pokud není zadán $container, vytvoří prázdný Html element.
     * Pokud neexistuje wrapper, nebo je null, vrací $container.
     * @param string $name 2-3 řetězce, oddělené mezerou, reprezentující klíče v poli $this->wrappers. Např. 'pair container'.
     * @param string $different3D Pokud chceme u 3D pole vybrat jiný 3tí klíč, než je uveden v $name.
     * @return RendererHtml Vytvořený wrapper, nebo $container pokud nebyl vytvořen.
     */
    public function createDefaultWrapper(string $name, string $different3D = ''): RendererHtml
    {
        //Načtení z pole $wrappers
        $wrapper = $this->wrappers->getWrapper($name, $different3D);
        //V případě, že není wrapper vrací fragment
        if(!$wrapper)
            return RendererHtml::el();
        return RendererHtml::fromNetteHtml($wrapper);
    }

    public function createOwn(mixed $content): RendererHtml
    {
        $el = RendererHtml::el();
        $el->addHtml($content);
        $el->isOwn = true;
        return $el;
    }

    public function setClasses(RendererHtml $element, string $name, bool $reverse = false): void
    {
        $element->setClasses($this->wrappers->getValue($name), $reverse);
    }

    public function getOptions(): array
    {
        return is_array($this->optionsSource) ? $this->optionsSource : $this->optionsSource->getOptions();
    }
    public function setOptions(array|BaseControl|ControlGroup $options): void
    {
        $this->optionsSource = $options;
    }

}