<?php

namespace Controllers;

class LegalController
{
    public function terms(): void
    {
        $view = 'legal/terms';
        $title = 'Obchodní podmínky';
        require __DIR__ . '/../Views/layout.php';
    }

    public function gdpr(): void
    {
        $view = 'legal/gdpr';
        $title = 'Ochrana osobních údajů';
        require __DIR__ . '/../Views/layout.php';
    }

    public function dpa(): void
    {
        $view = 'legal/dpa';
        $title = 'Zpracovatelská smlouva';
        require __DIR__ . '/../Views/layout.php';
    }
}
