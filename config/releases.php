<?php

return [
    [
        'version' => '1.2.7',
        'released_at' => '2026-09-25',
        'title' => 'Uzamčení služeb ve svátky',
        'important' => false,
        'changes' => [
            'U každého místa v modulu Služby lze nově nastavit, zda se má ve svátek automaticky uzamknout.',
            'Uzamčený sváteční den se v přehledu služeb zobrazí stejně jako zavřený den, doplněný o označení Svátek a jeho název.',
            'Do uzamčeného svátečního dne nelze službu zapsat ani přímým odesláním formuláře.',
        ],
    ],
    [
        'version' => '1.2.6',
        'released_at' => '2026-09-25',
        'title' => 'Automatická evidence svátků',
        'important' => false,
        'changes' => [
            'České státní a ostatní svátky se nově rozpoznávají automaticky bez ručního zadávání.',
            'Pohyblivé velikonoční svátky (Velký pátek a Velikonoční pondělí) se počítají automaticky pro každý rok.',
            'Svátek připadající na pracovní den se v docházce automaticky eviduje jako S, pokud pro den neexistuje vlastní záznam.',
            'Pokud zaměstnanec ve svátek skutečně pracuje, lze automatický svátek přepsat běžnou docházkou a výpočet mzdy jej vyhodnotí jako práci ve svátek.',
            'Automatické svátky se promítají také do exportu docházky, výpočtu výplaty a měsíčního přehledu na dashboardu.',
        ],
    ],
    [
        'version' => '1.2.5',
        'released_at' => '2026-09-25',
        'title' => 'Vylepšení exportu evidence docházky',
        'important' => false,
        'changes' => [
            'Export evidence docházky se nově otevírá v samostatném okně.',
            'Po dokončení tisku nebo uložení do PDF se okno exportu automaticky zavře.',
            'Rozložení vysvětlivek bylo upraveno a rozšířeno pro lepší čitelnost.',
        ],
    ],
    [
        'version' => '1.2.4',
        'released_at' => '2026-09-25',
        'title' => 'Export evidence docházky',
        'important' => false,
        'changes' => [
            'Na stránku Exporty přibyl export měsíční evidence docházky podle osoby a měsíce.',
            'Přehled docházky je připraven pro tisk nebo uložení do PDF a obsahuje měsíční souhrn i podpisová pole.',
            'Na stránce Docházka přibylo tlačítko Exportovat pro aktuálně zobrazený měsíc přihlášeného uživatele.',
        ],
    ],
    [
        'version' => '1.2.3',
        'released_at' => '2026-09-24',
        'title' => 'Přehlednější informace k aktualizacím',
        'important' => false,
        'changes' => [
            'Sekce Ruční aktualizace byla nahrazena přehlednější sekcí Aktualizace.',
            'Doplněn popis doporučené aktualizace na kliknutí přímo z GitHub Releases.',
            'Ruční aktualizace zůstává popsána jako záložní způsob včetně ochrany lokální konfigurace a dat.',
        ],
    ],
    [
        'version' => '1.2.2',
        'released_at' => '2026-09-24',
        'title' => 'Sjednocení ikony aktualizací',
        'important' => false,
        'changes' => [
            'Ikona položky Systém / Aktualizace byla sjednocena s vizuálním stylem ostatních položek menu.',
            'Nově používá ikonu Cloud Download ze SVG Repo.',
        ],
    ],
    [
        'version' => '1.2.1',
        'released_at' => '2026-09-24',
        'title' => 'Pevný oficiální GitHub zdroj aktualizací',
        'important' => false,
        'changes' => [
            'Zdroj vzdálených aktualizací je nyní pevně nastaven na oficiální repozitář ViMa73/podnikappka.',
            'Běžný administrátor už nemusí GitHub repozitář ručně nastavovat.',
            'Odstraněna možnost změnit zdroj aktualizací z administrace aplikace.',
        ],
    ],
    [
        'version' => '1.2.0',
        'released_at' => '2026-09-24',
        'title' => 'Vzdálené aktualizace z GitHubu',
        'important' => true,
        'changes' => [
            'Přidána kontrola nových verzí přes GitHub Releases.',
            'Vlastník může novou verzi nainstalovat jedním kliknutím přímo ze stránky Systém / Aktualizace.',
            'Aktualizace se nikdy nespouštějí automaticky na pozadí.',
            'Před výměnou aplikačních souborů se automaticky vytvoří jejich lokální záloha.',
            'Při aktualizaci se zachová lokální konfigurace, storage a uživatelské uploady.',
        ],
    ],
    [
        'version' => '1.1.2',
        'released_at' => '2026-09-24',
        'title' => 'Úprava informací k aktualizacím',
        'important' => false,
        'changes' => [
            'Doplněna verze 1.1.0 do přehledu Co je nového.',
            'Návod k aktualizaci aplikace byl přepracován pro běžného administrátora self-hosted instalace.',
        ],
    ],
    [
        'version' => '1.1.0',
        'released_at' => '2026-09-24',
        'title' => 'Změna licencování aplikace',
        'important' => true,
        'changes' => [
            'Aplikace je nyní nově open-source.',
        ],
    ],
    [
        'version' => '1.1.1',
        'released_at' => '2026-09-24',
        'title' => 'Oprava self-hosted konfigurace',
        'important' => true,
        'changes' => [
            'Opraveno rozpoznání self-hosted edice po aktualizaci.',
            'Aplikace již nespadne na chybě Undefined constant DB_HOST při chybějící lokální konfiguraci.',
            'Doplněna bezpečnější kontrola databázové konfigurace před spuštěním aplikace.',
        ],
    ],
    [
        'version' => '1.0.3',
        'released_at' => '2026-04-08',
        'title' => 'Úprava stránky detail uživatele',
        'important' => false,
        'changes' => [
            'Úprava zobrazení stránky detail uživatele',
            'Rozšíření evidence údajů u uživatele',
            'Výpis narozenin kolegy v záhlaví stránky',
        ],
    ],
    [
        'version' => '1.0.2',
        'released_at' => '2026-03-31',
        'title' => 'Úprava mobilního menu',
        'important' => true,
        'changes' => [
            'Oprava scrollování menu na mobilu',
            'Opraveno ukládání poznámek v modulu Služby.',
            'Úprava identity aplikace.',
        ],
    ],
    [
        'version' => '1.0.1',
        'released_at' => '2026-03-31',
        'title' => 'Automatické zobrazení novinek',
        'important' => false,
        'changes' => [
            'Automatické zobrazení modalu "Co je nového" po aktualizaci',
            'Přidána tabulka migrations',
            'Vylepšený systém migrací',
            'Oprava CSRF u admin akcí',
        ],
    ],
    [
        'version' => '1.0.0',
        'released_at' => '2026-03-30',
        'title' => 'První release systém',
        'important' => true,
        'changes' => [
            'Prvnotní spuštění aplikace.',
        ],
    ],
];
