# Changelog

Alle Änderungen an **Endlech.lu** werden in dieser Datei dokumentiert.

![Version](https://img.shields.io/badge/version-v2026.03.08-blue)
![Status](https://img.shields.io/badge/status-beta-green)

## [Unreleased]
*Geplante Features für das nächste Release.*

### 🚀 Features
- **Map:** Kartenansicht der Locations.

---

## [2026.03.08d] – Filterfunktion für Lokale

### 🚀 Features
- **Barrierefreiheits-Filter:** Checkboxen für ♿ Rollstuhlgerecht, 🚻 Barrierefreies WC, 🐕 Assistenzhund, 💡 Helle Beleuchtung.
- **Status-Filter:** „Nur geöffnete Lokale" Checkbox.
- **Ort-Filter:** Freitext-Suche nach Stadt (LIKE).
- **Küchen-Filter:** Freitext-Suche nach Küchentyp (LIKE).
- **Aktive Filter:** Chip-Zeile über Ergebnissen + „Alle zurücksetzen"-Link in der Sidebar.
- **Filter-Persistenz:** Sort- und Pagination-Links behalten alle aktiven Filter bei.

### 🛠 Tech
- **Repository:** `findPaginated()` auf `array $filters` umgestellt (skalierbar, 8 Filter-Keys).
- **Controller:** 8 Query-Parameter ausgelesen und als `$filters`-Array weitergereicht.

---

## [2026.03.08c] – Verifiziertes Lokal
*Blaues Verifikations-Badge für vom Endlech.lu-Team geprüfte Restaurants.*

### 🚀 Features
- **Verifikations-Badge:** Blauer Haken (Cyan-600) für verifizierte Restaurants auf Karte und Detailseite.
- **Tooltip:** „Von Endlech.lu persönlich vor Ort geprüft" via Browser-Tooltip.
- **Filter:** Listenansicht filtert nach „Nur verifizierte Lokale" (?verified=1).
- **Admin:** Verifikations-Checkbox im Bearbeitungsformular mit Auto-Stamping von Datum + Admin-User.
- **Admin:** Quick-Toggle-Button in der Restaurants-Übersicht (verifiziert/unverifiziert).
- **Admin:** Stat-Card „Verifizierte Lokale" im Dashboard.

### 🛠 Tech & Config
- **Entity:** `isVerified`, `verifiedAt`, `verifiedBy` zur `Restaurant`-Entity hinzugefügt.
- **Migration:** `Version20260308100000` – fügt `is_verified`, `verified_at`, `verified_by_id` zur `restaurant`-Tabelle hinzu.
- **Route:** `admin_restaurant_toggle_verified` POST `/admin/restaurants/{id}/verifizieren`.
- **Partial:** `templates/partials/_verified_badge.html.twig` – wiederverwendbares Badge-Template.
- **Fixtures:** 3 Restaurants als verifiziert markiert (Pizzeria Bella Vista, Sushi Zen, Green Bowl).

---

## [2026.03.08b] – Zahlungsmethoden
*Zahlungsmethoden pro Restaurant (Bargeld, Karte, Payconiq).*

### 🚀 Features
- **Zahlungsmethoden:** Drei neue Boolean-Felder in der `Restaurant`-Entity (`acceptsCash`, `acceptsCard`, `acceptsPayconiq`).
- **Detailseite:** Neue Sektion „Zahlungsmethoden" auf `/restaurants/{id}` mit farbigen Badges pro Methode (Grün = akzeptiert, Payconiq in Markenfarbe `#FF4612`).
- **Admin-Formular:** Neue Fieldset „Zahlungsmethoden" mit drei Checkboxen im Restaurant-Bearbeitungsformular.
- **Fixtures:** Alle 11 Fixture-Restaurants mit realistischen Zahlungsmethoden-Daten versehen.

### 🛠 Tech & Config
- **Migration:** `Version20260308000000` – fügt `accepts_cash`, `accepts_card`, `accepts_payconiq` (TINYINT) zur `restaurant`-Tabelle hinzu.

---

## [2026.03.08]
*Brevo Mailer Integration für Transaktions-E-Mails.*

### 🚀 Features
- **Brevo Integration:** `symfony/brevo-mailer` als Produktions-Mail-Provider installiert und konfiguriert.
- **E-Mail-Konfiguration:** Zentraler Absender (`noreply@endlech.lu`) über `mailer.yaml` und Umgebungsvariablen konfigurierbar.
- **Base E-Mail-Template:** Wiederverwendbares Basis-Layout (`email/base.html.twig`) mit Endlech.lu Branding (Gradient-Header, Footer).
- **Fehlerbehandlung:** Try/Catch für `TransportExceptionInterface` in allen E-Mail-sendenden Controllern mit benutzerfreundlichen Flash-Nachrichten.

### 🛠 Tech & Config
- **Dependency:** `symfony/brevo-mailer` v8.0 hinzugefügt.
- **Mailer Config:** Globaler Absender via `envelope.sender` und `headers.From` in `config/packages/mailer.yaml`.
- **Umgebungsvariablen:** `MAILER_SENDER_ADDRESS` und `MAILER_SENDER_NAME` in `.env` für konfigurierbare Absenderadresse.
- **Dev-Umgebung:** `.env.dev` nutzt Mailpit (`smtp://localhost:1025`) für lokales E-Mail-Testing.
- **Templates:** Verification-E-Mail refactored, nutzt jetzt `email/base.html.twig` als Basis-Layout.
- **Controller:** `RegistrationController` und `EmailVerificationController` nutzen globale Absender-Konfiguration statt hardcoded Adressen.

---

## [2026.03.01]
*Admin-Panel für die Verwaltung von Restaurants (CRUD).*

### 🚀 Features
- **Admin-Panel:** Neuer Admin-Bereich unter `/admin` für ROLE_ADMIN Benutzer.
- **Dashboard:** Admin-Dashboard mit Restaurant-Statistiken und Schnellaktionen.
- **Restaurant CRUD:** Restaurants erstellen, bearbeiten und löschen über `/admin/restaurants`.
- **Formular:** `RestaurantType`-Formular mit allen Restaurant-Feldern (Name, Stadt, Küche, Emoji, Bewertung, Status, Barrierefreiheits-Checkboxen, dynamische Hinweise).
- **Barrierefreiheits-Hinweise:** Dynamisches Hinzufügen/Entfernen von Hinweisen im Format `ok:Text` / `warn:Text` via Stimulus-Controller.
- **Navigation:** "Admin"-Link in der Hauptnavigation für Admin-Benutzer.
- **Sicherheit:** `/admin`-Bereich via `access_control` und `#[IsGranted('ROLE_ADMIN')]` geschützt.
- **CSRF-Schutz:** Löschen von Restaurants mit CSRF-Token-Validierung und Bestätigungsdialog.

### 🛠 Tech & Config
- **Controller:** `AdminRestaurantController` mit 5 Aktionen (Dashboard, Index, New, Edit, Delete).
- **Form:** `RestaurantType` mit CollectionType für dynamische accessibilityNotes.
- **Stimulus:** `collection_form_controller.js` für dynamische Formularfelder.
- **Templates:** Admin-Layout mit Sidebar-Navigation (`admin/base.html.twig`), 5 Admin-Templates.
- **Security:** `access_control`-Regel für `/admin`-Pfad in `security.yaml`.

---

## [2026.02.28]
*Startseite als Landing Page neu gestaltet. Detailseite für einzelne Restaurants.*

### 🚀 Features
- **Startseite:** Komplette Neugestaltung als Landing Page mit Hero-Section, „So funktioniert's" (3 Schritte), Top-6 Restaurant-Vorschau, „Warum Endlech.lu?" Wertversprechen und CTA-Banner.
- **Backend:** `RestaurantRepository::findTopRated(int $limit)` für die Top-bewerteten Restaurants.
- **Backend:** `HomeController` zeigt jetzt Top-6 Restaurants statt alle und übergibt Gesamtanzahl ans Template.
- **UI:** Restaurant-Karten auf der Startseite mit Barrierefreiheits-Icons (♿ 🚻 🐕 💡).
- **UI:** Responsive 3-Spalten-Grid (1 Spalte mobil, 2 Tablet, 3 Desktop).
- **CTA:** „Restaurants entdecken" → `/restaurants`, „Mitmachen" / „Restaurant vorschlagen" → `/register`.

### Vorige Änderungen (2026.02.28)
*Detailseite für einzelne Restaurants unter `/restaurants/{id}`.*

### 🚀 Features
- **Backend:** `RestaurantController::show()` mit Route `/restaurants/{id}` (Name: `app_restaurant_show`).
- **Backend:** Automatische 404-Antwort bei nicht existierender Restaurant-ID (Symfony Entity Value Resolver).
- **UI:** Template `restaurant/show.html.twig` mit Emoji-Hero, Status-Badge, Bewertung, Barrierefreiheits-Übersicht (4 Kriterien) und Hinweisen (ok/warn).
- **UI:** Responsive Layout (single-column, max-w-3xl) mit bestehendem Design (Cyan/Purple Gradient).
- **Linking:** "Details ansehen" Links in `restaurant/index.html.twig` und `home/index.html.twig` verlinken jetzt auf die Detailseite.

---

## [2026.02.27]
*Restaurant-Listenansicht unter `/restaurants` mit Pagination und Sortierung.*

### 🚀 Features
- **Backend:** `RestaurantController` mit Route `/restaurants` (Name: `app_restaurant_index`).
- **Backend:** Paginierung via Doctrine `Paginator` (6 Ergebnisse pro Seite).
- **Backend:** Sortierung nach Bewertung (Standard), Name (A–Z) und Neueste via URL-Parameter `?sort=`.
- **UI:** Dediziertes Template `restaurant/index.html.twig` mit Restaurant-Karten, Barrierefreiheits-Icons, Pagination-Navigation und Leer-Zustand.
- **Data:** 3 neue Fixture-Restaurants (Trattoria Roma/Ettelbruck, Green Bowl/Cloche d'Or, Brasserie du Grund/Grund) – jetzt 11 Einträge insgesamt.
- **Nav:** "Restaurants finden" in der Navigation verlinkt jetzt auf `/restaurants`.

### 🛠 Tech & Config
- **Repository:** `RestaurantRepository::findPaginated(string $sort, int $page, int $limit)` hinzugefügt.
- **Data:** `UserFixtures` mit 3 Test-Usern (Admin, verifiziert, unverifiziert) und korrekt gehashten Passwörtern (Symfony PasswordHasher).

---

## [2026.02.25]
*Platform-Launch: Overlay entfernt, echte Datenbank-Anbindung für Restaurant-Karten.*

### 🚀 Features
- **Launch:** "Coming Soon" Overlay entfernt – die Plattform ist jetzt live.
- **Backend:** `Restaurant`-Entity mit Barrierefreiheits-Feldern (Rollstuhl, WC, Assistenzhund, Beleuchtung).
- **Backend:** Doctrine-Migration für die `restaurant`-Tabelle (MySQL 8.0).
- **Data:** 8 Luxemburger Restaurants als initiale Fixtures (Luxembourg-Ville, Esch-Belval, Dudelange, Kirchberg, Grevenmacher, Diekirch, Strassen, Remich).
- **UI:** Dynamische Restaurant-Karten via DB-Abfrage statt hardcoded HTML.
- **UI:** Empty-State bei leerer Restaurantliste.

### 🛠 Tech & Config
- **Dependency:** `doctrine/doctrine-fixtures-bundle` als Dev-Abhängigkeit hinzugefügt.
- **Controller:** `HomeController` injiziert `RestaurantRepository` und übergibt `$restaurants` ans Template.

---

## [2026.01.13]
*Initialer Projektstart und UI-Implementation.*

### 🚀 Features
- **UI:** "Coming Soon" Overlay mit Glassmorphism-Effekt und Animationen implementiert.
- **Layout:** Responsives Grid-Layout mit Sidebar-Filtern und Restaurant-Karten erstellt.
- **Assets:** Logo `images/logo.png` eingebunden.
- **Design:** Modernes Farbschema (Cyan/Purple) definiert.

### 🛠 Tech & Config
- **Core:** Symfony 7 Projektstruktur aufgesetzt.
- **Frontend:** Webpack Encore mit PostCSS und Tailwind CSS konfiguriert.
- **Fix:** Tailwind-Build Prozess repariert (`postcss.config.js` erstellt).
- **Templates:** Base-Layout (`base.html.twig`) mit Navigation und Footer erstellt.

### 📝 Dokumentation
- `README.md` im Mika+ Hub Style erstellt.
- `CHANGELOG.md` mit CalVer-Versionierung initiiert.

---
