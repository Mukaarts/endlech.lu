# Changelog

Alle Änderungen an **Endlech.lu** werden in dieser Datei dokumentiert.

![Version](https://img.shields.io/badge/version-v2026.02.25-blue)
![Status](https://img.shields.io/badge/status-beta-green)

## [Unreleased]
*Geplante Features für das nächste Release.*

### 🚀 Features
- **Auth:** Login & Registrierung für Restaurant-Besitzer.
- **Map:** Kartenansicht der Locations.
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
