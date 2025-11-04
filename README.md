Bootstrap5FormRenderer
=============================

Form renderer for Nette Forms, wrappers are pre-prepared for Bootstrap5.
Nice forms could be done with less than one line of code per one control!


Installation
------------

```sh
composer require jdvorak23/bootstrap5-form-renderer
```

Requirements
------------

    "php": ">=8.0",
    "nette/forms": "^3.1"

Wiki
----

A lot of information and reference [in the wiki](https://github.com/jdvorak23/bootstrap5-form-renderer/wiki).

Tutorials
---------

There is a web with [example forms and tutorials](http://bootstrap-5-form-renderer.cz). You can [download the whole web](https://github.com/jdvorak23/bootstrap5-form-renderer-web) 
and instantly play with examples.

Changelog 0.9
---------

* Input group (without floating labels) behavior changed. Label is not by default a part of the input group, but is normally above control. When the label is not set, or the control does not support it (checkbox, button), there is structure defined to fill that space due to layout balancing. This structure is defined in [label][voidLabel] and can be set independently on control by option voidLabel on control. Also it needs to have content, which default is defined in [label][voidLabelContent], and can be set independently on control by oprion voidLabelContent. When you want set label being in the input group, there is constructor parameter  to set that for whole form, group option labelsInInputGroup to set it independently for any ControlGroup, and new option on control labelInInputGroup to set it independently on any control. When you want voidLabel on control which have label in input group (to equalize layout with other controls which have label above), there is new option on control, forceVoidLabel. If you set it to true, that voidLabel structure will be pushed to generate. Remember all these features works only when floating labels are off. Even when control does not support floating labels, when floating labels are set on it.
* FormFactory for building forms possibly with design. All parameters in FormFactory::create() must be named