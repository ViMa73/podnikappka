<?php

namespace Controllers;

class PublicLegalController
{
    private function render(string $view, string $title): void
    {
        $view = 'legal/' . $view;
        $guestWidth = 'max-w-5xl';
        require __DIR__ . '/../Views/layout_guest.php';
    }

    public function terms(): void
    {
        $this->render('terms', 'Obchodní podmínky');
    }

    public function gdpr(): void
    {
        $this->render('gdpr', 'Ochrana osobních údajů');
    }

    public function dpa(): void
    {
        $this->render('dpa', 'Zpracovatelská smlouva (DPA)');
    }
}
