# Endlech.lu

Eine offene Plattform, um barrierefreie Restaurants in Luxemburg zu finden und zu bewerten. Gebaut für Inklusion, Community und Einfachheit.

![Version](https://img.shields.io/badge/version-v2026.02.25-blue)
![Status](https://img.shields.io/badge/status-beta-green)

<div align="center">
  <img src="Element 1.png" alt="Endlech.lu Logo" width="200">
</div>

## 🚀 Projektstatus

**Die erste Beta-Version ist live.**
Die Restaurant-Listenansicht mit Barrierefreiheits-Informationen ist verfügbar. Als nächstes: Authentifizierung für Restaurant-Besitzer und eine Kartenansicht.

## 🎯 Features & Fortschritt

Hier ist der aktuelle Entwicklungsstand der Plattform.

### 🏗️ Core & Backend
- [x] **Projekt Setup:** Symfony 8.0 Installation & Konfiguration.
- [x] **Frontend Stack:** Tailwind CSS & Webpack Encore Integration.
- [x] **Datenbank:** Schema für Restaurants & User (MySQL 8.0).
- [x] **Daten-Seeding:** Initiale Restaurants für Luxemburg via Fixtures.
- [ ] **Authentifizierung:** Login & Registrierung für User.

### 🍽️ Restaurant Finder
- [x] **Listenansicht:** Anzeige aller Restaurants aus der Datenbank.
- [x] **Barrierefreiheits-Icons:** Anzeige von Kriterien (Rollstuhl, WC, Assistenzhund, Beleuchtung).
- [ ] **Detailseite:** Einzelansicht mit Adresse und weiteren Infos.
- [ ] **Filter:** Filtern nach Kriterien (z. B. "Stufenloser Eingang").

### 👤 User & Community
- [ ] **User Profile:** Speichern von Favoriten.
- [ ] **Crowdsourcing:** Formular, um neue Restaurants vorzuschlagen.
- [ ] **Bewertungen:** Kommentarsystem für Barrierefreiheit.

## 🔮 Roadmap (Zukunft)

Ideen für Version 2.0 (nach dem ersten stabilen Release):

* **Verifizierung:** "Blauer Haken" für geprüfte Restaurants.
* **Fotos:** User können Fotos von Eingängen/Toiletten hochladen.
* **Mehrsprachigkeit:** Interface auf LU, FR, EN.
* **Karte:** Interaktive Map-View (Leaflet/Google Maps).

## 🛠 Tech Stack

* **Backend:** PHP 8.4+, Symfony 8.0
* **Database:** MySQL 8.0 (Doctrine ORM)
* **Frontend:** Twig, Tailwind CSS (via `symfonycasts/tailwind-bundle`)
* **JS:** Stimulus, Turbo (Hotwire)
* **Assets:** Webpack Encore

## ⚙️ Installation & Setup

1.  **Repository klonen:**
    ```bash
    git clone https://github.com/Mukaarts/endlech.lu.git
    cd endlech.lu
    ```

2.  **Dependencies installieren:**
    ```bash
    composer install
    npm install
    ```

3.  **Docker starten (MySQL):**
    ```bash
    docker compose up -d
    ```

4.  **Umgebungsvariablen (.env.local):**
    Erstelle eine `.env.local` Datei:
    ```bash
    DATABASE_URL="mysql://root:root@127.0.0.1:3306/endlech?serverVersion=8.0&charset=utf8mb4"
    APP_SECRET=dein-geheimes-secret
    ```

5.  **Datenbank & Fixtures:**
    ```bash
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ```

6.  **Assets bauen & Server starten:**
    ```bash
    npm run build
    symfony server:start
    ```

    Oder mit `make`:
    ```bash
    make init   # Vollständiges Setup (Docker, composer, npm, DB, Fixtures)
    make start  # Server + Asset-Watcher starten
    ```

## 🌍 Environments

### 🛠 Development
Der Modus für die aktive Entwicklung. Änderungen an Templates und CSS werden sofort erkannt.

```bash
# Terminal 1: Tailwind
php bin/console tailwind:build --watch

# Terminal 2: JS/Encore
npm run watch
```

### 🚀 Production
Optimiert für Performance und Sicherheit.

```bash
php bin/console tailwind:build --minify
npm run build
php bin/console cache:clear
```

## 📂 Struktur

* `/src/Controller` - Logik für die Seiten.
* `/src/Entity` - Datenbank-Modelle (`User`, `Restaurant`).
* `/src/DataFixtures` - Initiale Testdaten.
* `/templates` - Twig Templates.
* `/assets` - Stimulus Controller und CSS.
* `/migrations` - Doctrine Datenbankmigrationen.

---
*Built with ❤️ in Luxembourg.*
