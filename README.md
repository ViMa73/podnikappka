# PodnikAppka

PodnikAppka je bezplatný self-hosted open-source systém pro malé firmy. Aplikace běží na infrastruktuře provozovatele a data zůstávají v jeho databázi.

## Licence
Zdrojový kód je poskytován pod GNU Affero General Public License v3.0 nebo novější (`AGPL-3.0-or-later`). Viz `LICENSE`.

## Instalace
1. Vytvořte prázdnou MySQL/MariaDB databázi.
2. Nahrajte projekt na webhosting/subdoménu.
3. Otevřete web v prohlížeči a dokončete instalační průvodce.
4. Instalátor vytvoří lokální konfiguraci a databázové tabulky.
5. Přihlaste se účtem vlastníka vytvořeným během instalace.

## Data a odpovědnost
Projekt neposkytuje centrální cloudovou službu. Provozovatel instance odpovídá za hosting, zabezpečení, aktualizace, zálohy, uživatelské účty a zákonnost zpracování dat. Podrobnosti jsou v `DISCLAIMER.md`.

## Aktualizace
Před aktualizací proveďte zálohu databáze a souborů. Po nahrání nové verze spusťte čekající migrace v **Systém / Aktualizace**.
