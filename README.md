# Glad Widgets

Drupali moodul, mis lisab asutuse Glad widgetid (toitlustamine, päevik,
õpilased, tunniplaan jne) Drupali plokkidena, koos live-eelvaatega
seadistuslehel.

Repo: https://github.com/markosiilak/glad_widgets

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
| Nimetus | Sisemine nimi (nt "Toitlustamine") |
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
