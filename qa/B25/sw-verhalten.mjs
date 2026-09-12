/**
 * Führt die Handler von public/sw.js gegen nachgebaute Ereignisse aus (QA B25).
 *
 * Warum so: Service Worker registrieren im headless Chrome dieser Umgebung nicht
 * (der Promise von register() bleibt pending) — und „im Code geprüft" ist kein
 * Nachweis. Hier läuft der echte Quelltext, nur mit kontrollierter Umgebung.
 */
import { readFileSync } from 'node:fs';

const ergebnis = [];
const sag = (ak, text) => ergebnis.push(`${ak}\t${text}`);

// ── Nachbau der Worker-Umgebung ────────────────────────────────────────────
class FakeResponse {
    constructor(url, ok = true) { this.url = url; this.ok = ok; this._geklont = 0; }
    clone() { this._geklont++; return new FakeResponse(this.url, this.ok); }
}

class FakeCache {
    constructor(name) { this.name = name; this.inhalt = new Map(); }
    async addAll(urls) { urls.forEach((u) => this.inhalt.set(u, new FakeResponse(u))); }
    async match(req) { const k = typeof req === 'string' ? req : req.url; return this.inhalt.get(k); }
    async put(req, res) { this.inhalt.set(typeof req === 'string' ? req : req.url, res); }
    async keys() { return [...this.inhalt.keys()]; }
}

const speicher = new Map();
const caches = {
    async open(name) { if (!speicher.has(name)) speicher.set(name, new FakeCache(name)); return speicher.get(name); },
    async keys() { return [...speicher.keys()]; },
    async delete(name) { return speicher.delete(name); },
    async match(req) {
        for (const c of speicher.values()) { const t = await c.match(req); if (t) return t; }
        return undefined;
    },
};

let netzAn = true;
let antwortOk = true;
let netzAufrufe = [];
const fetchFake = async (req) => {
    const url = typeof req === 'string' ? req : req.url;
    netzAufrufe.push(url);
    if (!netzAn) throw new Error('offline');
    return new FakeResponse(url, antwortOk);
};

const handler = {};
let skipWaitingGerufen = false;
let claimGerufen = false;

const self_ = {
    addEventListener: (typ, fn) => { handler[typ] = fn; },
    location: { origin: 'https://endlech.lu' },
    skipWaiting: async () => { skipWaitingGerufen = true; },
    clients: { claim: async () => { claimGerufen = true; } },
};

// ── sw.js ausführen ────────────────────────────────────────────────────────
const quelle = readFileSync(new URL('../../public/sw.js', import.meta.url), 'utf8');
const fabrik = new Function('self', 'caches', 'fetch', 'URL', 'Promise', 'Error', quelle);
fabrik(self_, caches, fetchFake, URL, Promise, Error);

const ereignis = (request) => {
    let versprochen;
    return {
        request,
        waitUntil: (p) => { versprochen = p; },
        respondWith: (p) => { versprochen = p; },
        get ergebnis() { return versprochen; },
    };
};
const anfrage = (url, { method = 'GET', mode = 'no-cors', destination = '' } = {}) =>
    ({ url, method, mode, destination });

// ── AK-04 · install cacht die App-Shell, ruft skipWaiting ──────────────────
{
    const e = ereignis();
    handler.install(e);
    await e.ergebnis;
    const cache = await caches.open('endlech-v3');
    sag('AK-04', `cache=${cache.name} inhalt=${JSON.stringify(await cache.keys())} skipWaiting=${skipWaitingGerufen}`);
}

// ── AK-05 · activate löscht fremde Caches, ruft clients.claim ──────────────
{
    (await caches.open('endlech-v1')).inhalt.set('/alt', new FakeResponse('/alt'));
    (await caches.open('fremder-cache')).inhalt.set('/x', new FakeResponse('/x'));
    const e = ereignis();
    handler.activate(e);
    await e.ergebnis;
    sag('AK-05', `verbleibende_caches=${JSON.stringify(await caches.keys())} claim=${claimGerufen}`);
}

// ── AK-09 · Nicht-GET und /api/ werden nicht angefasst ─────────────────────
{
    for (const [name, req] of [
        ['POST', anfrage('https://endlech.lu/de/login', { method: 'POST' })],
        ['/api/v1', anfrage('https://endlech.lu/api/v1/restaurants')],
        ['/api/cuisines', anfrage('https://endlech.lu/api/cuisines/search?q=x')],
    ]) {
        const e = ereignis(req);
        handler.fetch(e);
        sag('AK-09', `${name}: eingegriffen=${e.ergebnis !== undefined ? 'JA (Befund)' : 'nein'}`);
    }
}

// ── AK-10 · fremde Herkunft ────────────────────────────────────────────────
{
    const e = ereignis(anfrage('https://fremd.example/bild.png'));
    handler.fetch(e);
    sag('AK-10', `eingegriffen=${e.ergebnis !== undefined ? 'JA (Befund)' : 'nein'}`);
}

// ── AK-06 · Navigation offline → offline.html ──────────────────────────────
{
    netzAn = false;
    const e = ereignis(anfrage('https://endlech.lu/de/restaurants', { mode: 'navigate' }));
    handler.fetch(e);
    const res = await e.ergebnis;
    sag('AK-06', `offline_navigation_liefert=${res ? res.url : 'nichts'}`);
    netzAn = true;
}

// ── AK-18 · Auch eine zuvor besuchte Seite liefert offline.html ────────────
{
    const cache = await caches.open('endlech-v3');
    await cache.put('https://endlech.lu/de/restaurants', new FakeResponse('/de/restaurants-GECACHT'));
    netzAn = false;
    const e = ereignis(anfrage('https://endlech.lu/de/restaurants', { mode: 'navigate' }));
    handler.fetch(e);
    const res = await e.ergebnis;
    sag('AK-18', `besuchte_seite_offline=${res ? res.url : 'nichts'}`);
    netzAn = true;
    cache.inhalt.delete('https://endlech.lu/de/restaurants');
}

// ── AK-07 · /build/ stale-while-revalidate ─────────────────────────────────
{
    netzAufrufe = [];
    const cache = await caches.open('endlech-v3');
    await cache.put('https://endlech.lu/build/app.abc.css', new FakeResponse('/build/app.abc.css-ALT'));
    const e = ereignis(anfrage('https://endlech.lu/build/app.abc.css'));
    handler.fetch(e);
    const res = await e.ergebnis;
    await new Promise((r) => setTimeout(r, 20));
    sag('AK-07', `sofort_aus_cache=${res.url} netzaufruf_im_hintergrund=${netzAufrufe.length > 0}`);
}

// ── AK-08 · Cold start offline: sauber ablehnen ────────────────────────────
{
    netzAn = false;
    const e = ereignis(anfrage('https://endlech.lu/build/neu.xyz.js'));
    handler.fetch(e);
    let ausgang;
    try { const r = await e.ergebnis; ausgang = r === undefined ? 'undefined (Befund)' : `Antwort ${r.url}`; }
    catch (err) { ausgang = `abgelehnt (${err.message})`; }
    sag('AK-08', `cold_start_offline=${ausgang}`);
    netzAn = true;
}

// ── AK-19 · Was landet beim Bildabruf im Cache? ────────────────────────────
{
    const e = ereignis(anfrage('https://endlech.lu/uploads/restaurants/foto.jpg', { destination: 'image' }));
    handler.fetch(e);
    await e.ergebnis;
    await new Promise((r) => setTimeout(r, 20));
    const cache = await caches.open('endlech-v3');
    sag('AK-19', `cache_nach_bildabruf=${JSON.stringify(await cache.keys())}`);
}

console.log(ergebnis.join('\n'));

// ── Angriff 1 · API-Wege, mit und ohne Sprachpräfix (BF-141) ───────────────
{
    const faelle = [
        ['/api/v1', 'https://endlech.lu/api/v1/restaurants'],
        ['/de/api/cuisines', 'https://endlech.lu/de/api/cuisines/search?q=pizza'],
        ['/lb/api/cuisines', 'https://endlech.lu/lb/api/cuisines/search?q=pizza'],
        ['/open.json (kein API-Pfad)', 'https://endlech.lu/open.json'],
    ];
    for (const [name, url] of faelle) {
        const e = ereignis(anfrage(url));
        handler.fetch(e);
        console.log(`ANGRIFF-1\t${name}: eingegriffen=${e.ergebnis !== undefined ? 'JA' : 'nein'}`);
        if (e.ergebnis !== undefined) { await e.ergebnis.catch(() => {}); }
    }
}

// ── Angriff 2 · Welche Bilder landen im Cache? (BF-140) ────────────────────
// Erwartet nach der Reparatur: Avatar NICHT, Restaurantfoto und Porträt JA.
{
    const faelle = [
        ['avatar', 'https://endlech.lu/uploads/avatars/nutzer-7.jpg'],
        ['restaurantfoto', 'https://endlech.lu/uploads/restaurants/haus.jpg'],
        ['portraet', 'https://endlech.lu/uploads/team/michael.jpg'],
        ['app-icon', 'https://endlech.lu/icons/icon-192.png'],
        ['fremder-uploads-pfad', 'https://endlech.lu/uploads/irgendwas-neues/datei.jpg'],
    ];
    for (const [name, url] of faelle) {
        const e = ereignis(anfrage(url, { destination: 'image' }));
        handler.fetch(e);
        await e.ergebnis;
        await new Promise((r) => setTimeout(r, 20));
        const cache = await caches.open('endlech-v3');
        const drin = (await cache.keys()).includes(url);
        console.log(`ANGRIFF-2\t${name}_im_cache=${drin ? 'JA' : 'nein'}`);
    }
}

// ── Angriff 3 · Antwort mit Fehlerstatus darf nicht gecacht werden ─────────
// ⚠ Der erste Anlauf war ein Messfehler: Der nachgebaute `fetch` lieferte immer
// `ok: true`, der Lauf prüfte also nichts. Jetzt antwortet er mit `ok: false`.
{
    antwortOk = false;
    const cache = await caches.open('endlech-v3');
    const vorher = new Set(await cache.keys());
    const e = ereignis(anfrage('https://endlech.lu/build/kaputt.js'));
    handler.fetch(e);
    await e.ergebnis.catch(() => {});
    await new Promise((r) => setTimeout(r, 20));
    const neuHinzu = (await cache.keys()).filter((k) => !vorher.has(k));
    console.log(`ANGRIFF-3\tbei_ok_false_gecacht=${neuHinzu.length ? JSON.stringify(neuHinzu) : 'nein'}`);
    antwortOk = true;
}
