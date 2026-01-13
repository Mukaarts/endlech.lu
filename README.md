# Endlech.lu

Eine offene Plattform, um barrierefreie Restaurants in Luxemburg zu finden und zu bewerten. Gebaut für Inklusion, Community und Einfachheit.

![Version](https://img.shields.io/badge/version-v2026.01.13-blue)
![Status](https://img.shields.io/badge/status-development-orange)

<div align="center">
  <img src="Element 1.png" alt="Endlech.lu Logo" width="200">
</div>

## 🚧 Projektstatus

**Dieses Projekt befindet sich aktuell im Aufbau.**
Wir arbeiten gerade am Grundgerüst (MVP). Ziel ist es, eine erste nutzbare Version für Luxemburg bereitzustellen.

## 🎯 Geplante Features & Fortschritt

Hier ist der aktuelle Entwicklungsstand der Plattform.

### 🏗️ Core & Backend
- [x] **Projekt Setup:** Symfony 7 Installation & Konfiguration.
- [x] **Frontend Stack:** Tailwind CSS & Webpack Encore Integration.
- [x] **Datenbank:** Grundlegendes Schema für Restaurants & User.
- [ ] **Authentifizierung:** Login & Registrierung für User.
- [ ] **Daten-Seeding:** Erste Test-Daten für Restaurants in Luxemburg.

### 🍽️ Restaurant Finder (MVP)
- [ ] **Listenansicht:** Anzeige aller Restaurants.
- [ ] **Detailseite:** Einzelansicht mit Adresse und Infos.
- [ ] **Barrierefreiheits-Icons:** Anzeige von Kriterien (Rollstuhl, WC, etc.).
- [ ] **Filter:** Filtern nach Kriterien (z. B. "Stufenloser Eingang").

### 👤 User & Community
- [ ] **User Profile:** Speichern von Favoriten.
- [ ] **Crowdsourcing:** Formular, um neue Restaurants vorzuschlagen.
- [ ] **Bewertungen:** Kommentarsystem für Barrierefreiheit.

## 🔮 Roadmap (Zukunft)

Ideen für Version 2.0 (nach dem ersten Release):

* **Verifizierung:** "Blauer Haken" für geprüfte Restaurants.
* **Fotos:** User können Fotos von Eingängen/Toiletten hochladen.
* **Mehrsprachigkeit:** Interface auf LU, FR, EN.
* **Karte:** Interaktive Map-View (Leaflet/Google Maps).

## 🛠 Tech Stack

* **Backend:** PHP 8.2+, Symfony 7
* **Database:** MySQL (Doctrine ORM)
* **Frontend:** Twig, Tailwind CSS (via `symfonycasts/tailwind-bundle`)
* **JS:** Stimulus, Turbo (Hotwire)
* **Assets:** Webpack Encore

## ⚙️ Installation & Setup

1.  **Repository klonen:**
    ```bash
    git clone [https://github.com/dein-username/endlech-lu.git](https://github.com/dein-username/endlech-lu.git)
    cd endlech-lu
    ```

2.  **Dependencies installieren:**
    ```bash
    composer install
    npm install
    ```

3.  **Umgebungsvariablen (.env.local):**
    Erstelle eine `.env.local` Datei und konfiguriere deine Datenbank:
    ```bash
    # Database
    DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/endlech_db?serverVersion=8.0&charset=utf8mb4"
    ```

4.  **Datenbank & Assets:**
    ```bash
    # Tailwind Binary init
    php bin/console tailwind:init
    
    # DB Setup
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    
    # Frontend Build
    npm run build
    php bin/console tailwind:build
    ```

5.  **Server starten:**
    ```bash
    symfony server:start
    ```

## 🌍 Environments

### 🛠 Development
Der Modus für die aktive Entwicklung. Änderungen an Templates und CSS werden sofort erkannt.

1.  Watcher starten (2 Terminals empfohlen):
    ```bash
    # Terminal 1: Tailwind
    php bin/console tailwind:build --watch
    
    # Terminal 2: JS/Encore
    npm run watch
    ```

### 🚀 Production
Optimiert für Performance und Sicherheit.

1.  Assets bauen:
    ```bash
    php bin/console tailwind:build --minify
    npm run build
    ```
2.  Cache leeren:
    ```bash
    php bin/console cache:clear
    ```

## 📂 Struktur

* `/src/Controller` - Logik für die Seiten.
* `/src/Entity` - Datenbank-Modelle.
* `/templates` - Twig Templates.
* `/assets` - Stimulus Controller und CSS.

---
*Built with ❤️ in Luxembourg.*
