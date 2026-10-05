# Glad Widgets

Drupali moodul, mis lisab asutuse Glad widgetid (toitlustamine, päevik,
õpilased, tunniplaan jne) Drupali plokkidena, koos live-eelvaatega
seadistuslehel.

Repo: https://github.com/markosiilak/glad_widgets_drupal

## Nõuded

- Drupal 9.4+, 10 või 11
- Asutuse UUID ja (valikuline) API token Glad süsteemist

## Paigaldamine

Kloonimine otse `web/modules/custom/` alla:

```bash
git clone https://github.com/markosiilak/glad_widgets.git web/modules/custom/glad_widgets
```

Seejärel luba moodul:

```bash
drush pm:enable glad_widgets -y
```

Moodul lisab uue õiguse **Administer Glad Widgets**, mis tuleb kasutajale
või rollile anda `/admin/people/permissions` lehel.

## Seadistamine

1. Mine lehele **Configuration → Web services → Glad Widgets**
   (`/admin/config/services/glad-widgets`).
2. Sisesta:
   - **Asutuse UUID** — nt `5ea30cbc-0b37-4d9f-8912-0ba305040000`
   - **API token** — valikuline; ilma tokenita on nähtavad ainult avalikud andmed
   - **Vaikimisi kõrgus** — widgeti iframe'i kõrgus pikslites
   - **API baas-URL** ja **Widgeti baas-URL** — vaikimisi `https://siilak.com`
3. Salvesta. Kui UUID ja token on kehtivad, kuvab leht "Asutusele on
   saadaval N moodulit" ning eelvaate paneelis saab dropdown-ist valida,
   millist moodulit näidata.

### Live eelvaade

Eelvaate `<iframe>` värskendub automaatselt (ilma lehte laadimata), kui
muudad UUID-d, tokenit, baas-URL-i või valid dropdown-ist teise mooduli.
JS-loogika asub failis `js/preview.js` ja on kasutusel nii seadistuslehel
kui ka widgeti lisamise/muutmise vormil.

## Widgetite haldus

Widgetid on `glad_widget` konfiguratsiooni-entiteedid. Halda neid lehel
**Configuration → Web services → Glad Widgets → Widgets**
(`/admin/config/services/glad-widgets/widgets`).

Iga widget koosneb väljadest:

| Väli | Kirjeldus |
|------|-----------|
| Nimetus | Sisemine nimi (nt "Toitlustamine"). Mooduli valimisel täidetakse automaatselt API mooduli sildiga, aga jääb käsitsi muudetavaks |
| Moodul | Valik API-st laetud lubatud moodulite seast (nt `catering`) |
| Kõrgus | Iframe'i kõrgus pikslites, ülekirjutab vaikimisi väärtuse |
| Aktiivne | Kas widget kuvatakse plokis |

Moodulite loend laetakse dünaamiliselt API otspunktist
`/api/admin/institutions/{uuid}/features-by-institution` — käsitsi
hooldatavat nimekirja pole. Kui asutusel pole mingit moodulit lubatud, seda
dropdown-is ei näidata.

## Ploki lisamine lehele

1. Loo vähemalt üks widget (vt eelmine peatükk).
2. Mine **Structure → Block layout** (`/admin/structure/block`).
3. Lisa plokk soovitud piirkonda ja vali plokitüübiks **Glad Widget**.
4. Ploki seadistuses vali dropdown-ist, milline loodud widget kuvada.
5. Vajadusel piira nähtavus konkreetsetele lehtedele (nt `<front>` ainult
   esilehele).

Näide (drush eval):

```php
$block = \Drupal\block\Entity\Block::create([
  'id' => 'glad_toitlustamine',
  'theme' => 'my_theme',
  'region' => 'content',
  'plugin' => 'glad_widget_block',
  'settings' => [
    'id' => 'glad_widget_block',
    'label' => 'Toitlustamine',
    'label_display' => 'visible',
    'widget_id' => 'toitlustamine',
  ],
  'visibility' => [
    'request_path' => [
      'id' => 'request_path',
      'negate' => FALSE,
      'pages' => '<front>',
    ],
  ],
]);
$block->save();
```

## Arhitektuur

| Fail | Vastutus |
|------|----------|
| `src/Form/GladWidgetsSettingsForm.php` | UUID/token/URL seaded + live eelvaade |
| `src/GladApiClient.php` | Pärib lubatud moodulid API-st (`features-by-institution`) |
| `src/Entity/GladWidget.php` | Widgeti konfiguratsiooni-entiteet |
| `src/GladWidgetListBuilder.php` | Widgetite halduse loend |
| `src/Form/GladWidgetForm.php` | Widgeti lisamise/muutmise vorm, koos eelvaatega |
| `src/Form/GladWidgetDeleteForm.php` | Widgeti kustutamise kinnitus |
| `src/Plugin/Block/GladWidgetBlock.php` | Drupali plokk, renderdab valitud widgeti |
| `src/Controller/GladWidgetsController.php` | AJAX-otspunkt lubatud moodulite jaoks |
| `templates/glad-widget.html.twig` | Widgeti iframe'i väljundmall |
| `js/preview.js` | Live eelvaate uuendamine väljade muutmisel |

## Turvalisus

- API token salvestatakse Drupali konfiguratsioonis (`glad_widgets.settings`).
  Kaitse ligipääs `administer glad widgets` õigusega.
- Widget-iframe'id laetakse otse Gladi serverist; Drupal ise ei näe ega
  töötle asutuse andmeid, vaid ainult koostab URL-i.

## Tõrkeotsing

- **"Mooduleid ei leitud"** — kontrolli, et UUID ja token on õiged ning
  `api_base_url` on kättesaadav. Vea põhjus logitakse kanalisse
  `glad_widgets` (`/admin/reports/dblog`).
- **Iframe on tühi** — kontrolli brauseri konsoolist, kas widgeti server
  blokeerib `X-Frame-Options` päisega; Gladi `/widget/*` teed peaksid
  selle päise ära jätma.

---

# Glad Widgets (English)

A Drupal module that adds Glad widgets (catering, journal, students, timetable, etc.) as Drupal blocks, with a live preview on the configuration page.

Repository: https://github.com/markosiilak/glad_widgets

## Requirements

- Drupal 9.4+, 10, or 11
- Institution UUID and (optional) API token from the Glad system

## Installation

Clone directly into `web/modules/custom/`:

```bash
git clone https://github.com/markosiilak/glad_widgets.git web/modules/custom/glad_widgets
```

Then enable the module:

```bash
drush pm:enable glad_widgets -y
```

The module adds a new permission **Administer Glad Widgets**, which must be assigned to a user or role on the `/admin/people/permissions` page.

## Configuration

1. Go to **Configuration → Web services → Glad Widgets** (`/admin/config/services/glad-widgets`).
2. Enter:
   - **Institution UUID** — e.g. `5ea30cbc-0b37-4d9f-8912-0ba305040000`
   - **API token** — optional; without a token only public data is visible
   - **Default height** — widget iframe height in pixels
   - **API base URL** and **Widget base URL** — defaults to `https://siilak.com`
3. Save. If the UUID and token are valid, the page displays "N modules available for this institution" and the preview panel lets you select a module from the dropdown.

### Live Preview

The preview `<iframe>` updates automatically (without a page reload) when you change the UUID, token, base URL, or select a different module from the dropdown. The JS logic is in `js/preview.js` and is used on both the configuration page and the widget add/edit form.

## Widget Management

Widgets are `glad_widget` configuration entities. Manage them at **Configuration → Web services → Glad Widgets → Widgets** (`/admin/config/services/glad-widgets/widgets`).

Each widget consists of the following fields:

| Field | Description |
|-------|-------------|
| Label | Internal name (e.g. "Catering"). Auto-filled when selecting a module from the API module tag, but remains editable |
| Module | Selected from the list of allowed modules loaded from the API (e.g. `catering`) |
| Height | Iframe height in pixels; overrides the default value |
| Active | Whether the widget is displayed in a block |

The module list is loaded dynamically from the API endpoint `/api/admin/institutions/{uuid}/features-by-institution` — there is no manually maintained list. If an institution has no modules enabled, they will not appear in the dropdown.

## Adding a Block to a Page

1. Create at least one widget (see the previous section).
2. Go to **Structure → Block layout** (`/admin/structure/block`).
3. Add a block to the desired region and choose **Glad Widget** as the block type.
4. In the block settings, select which created widget to display from the dropdown.
5. Optionally restrict visibility to specific pages (e.g. `<front>` for the front page only).

Example (drush eval):

```php
$block = \Drupal\block\Entity\Block::create([
  'id' => 'glad_catering',
  'theme' => 'my_theme',
  'region' => 'content',
  'plugin' => 'glad_widget_block',
  'settings' => [
    'id' => 'glad_widget_block',
    'label' => 'Catering',
    'label_display' => 'visible',
    'widget_id' => 'catering',
  ],
  'visibility' => [
    'request_path' => [
      'id' => 'request_path',
      'negate' => FALSE,
      'pages' => '<front>',
    ],
  ],
]);
$block->save();
```

## Architecture

| File | Responsibility |
|------|----------------|
| `src/Form/GladWidgetsSettingsForm.php` | UUID/token/URL settings + live preview |
| `src/GladApiClient.php` | Fetches allowed modules from the API (`features-by-institution`) |
| `src/Entity/GladWidget.php` | Widget configuration entity |
| `src/GladWidgetListBuilder.php` | Widget admin list |
| `src/Form/GladWidgetForm.php` | Widget add/edit form with preview |
| `src/Form/GladWidgetDeleteForm.php` | Widget delete confirmation |
| `src/Plugin/Block/GladWidgetBlock.php` | Drupal block that renders the selected widget |
| `src/Controller/GladWidgetsController.php` | AJAX endpoint for allowed modules |
| `templates/glad-widget.html.twig` | Widget iframe output template |
| `js/preview.js` | Live preview update on field changes |

## Security

- The API token is stored in Drupal configuration (`glad_widgets.settings`). Access is protected by the `administer glad widgets` permission.
- Widget iframes are loaded directly from the Glad server; Drupal does not see or process institution data — it only assembles the URL.

## Troubleshooting

- **"No modules found"** — verify that the UUID and token are correct and that `api_base_url` is reachable. The error reason is logged to the `glad_widgets` channel (`/admin/reports/dblog`).
- **Iframe is empty** — check the browser console to see if the widget server is blocking with an `X-Frame-Options` header; the Glad `/widget/*` routes should omit that header.
