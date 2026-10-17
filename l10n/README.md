# Translations

CiviNeo Notify uses CiviCRM's standard PHP Gettext integration. English source
strings are the fallback language. Translations belong in
`l10n/<locale>/LC_MESSAGES/civineonotify.po` and must be compiled to the
adjacent `civineonotify.mo` file before deployment.

Refresh the source catalogue whenever user-visible strings change, then merge
and compile each locale:

```bash
civistrings -o l10n/civineonotify.pot .
msgmerge --update l10n/de_DE/LC_MESSAGES/civineonotify.po l10n/civineonotify.pot
msgfmt --check --output-file=l10n/de_DE/LC_MESSAGES/civineonotify.mo l10n/de_DE/LC_MESSAGES/civineonotify.po
```

Use CiviCRM locale names such as `de_DE`, `fr_FR`, or `nl_NL`. New locales copy
the template into their own `LC_MESSAGES` directory and commit both `.po` and
`.mo` files. User-visible PHP must use
`CRM_Civineonotify_ExtensionUtil::ts()` (or its `E::ts()` alias), and Smarty
templates must use `{ts}`. Protocol identifiers, stored status keys, and raw
external responses are not translated.
