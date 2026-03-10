<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\Document;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfGeneratorService
{
    public function __construct(
        private Environment $twig
    ) {
    }

    /**
     * Génère un PDF pour un document (devis ou facture)
     */
    public function generateDocumentPdf(Document $document, ?Company $company): Response
    {
        // Configuration de DomPDF
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('chroot', realpath(__DIR__ . '/../../public'));

        $dompdf = new Dompdf($options);

        // Déterminer la couleur principale et le logo adapté au type de document
        $primaryColor = '#00a651';
        $lightColor = '#e6f9f0';
        $documentLogo = null;
        $showWatermark = false;

        if ($company) {
            $primaryColor = $company->getPrimaryColor() ?? '#00a651';

            // Appliquer la couleur du thème si défini
            if ($company->getColorTheme()) {
                $themeColors = [
                    'green' => '#00a651',
                    'ocean' => '#0077b6',
                    'sunset' => '#e07b39',
                    'purple' => '#7c3aed',
                    'red' => '#dc2626',
                    'royal' => '#1d4ed8',
                ];
                if (isset($themeColors[$company->getColorTheme()])) {
                    $primaryColor = $themeColors[$company->getColorTheme()];
                }
            }

            $lightColor = $company->getLightColor();
            $documentLogo = $company->getLogoForType($document->getType());
            $showWatermark = $document->getType() === Document::TYPE_QUOTE && $company->isQuoteWatermark();
        }

        // Génération du HTML à partir du template Twig
        $html = $this->twig->render('document/pdf.html.twig', [
            'document' => $document,
            'company' => $company,
            'totalInWords' => $this->numberToWords((float) $document->getTotalTtc()),
            'primaryColor' => $primaryColor,
            'lightColor' => $lightColor,
            'documentLogo' => $documentLogo,
            'showWatermark' => $showWatermark,
        ]);

        // Chargement et génération du PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Nom du fichier
        $filename = $this->generateFilename($document);

        // Retour de la réponse avec le PDF
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    /**
     * Génère un nom de fichier pour le PDF
     */
    private function generateFilename(Document $document): string
    {
        $type = $document->getType() === Document::TYPE_QUOTE ? 'Devis' : 'Facture';
        $number = str_replace('/', '-', $document->getNumber());
        $date = $document->getDate()->format('Y-m-d');

        return sprintf('%s_%s_%s.pdf', $type, $number, $date);
    }

    /**
     * Convertit un nombre en lettres (français)
     * Utile pour afficher le montant en toutes lettres sur les factures
     */
    public function numberToWords(float $number): string
    {
        $integer = (int) floor($number);
        $decimal = (int) round(($number - $integer) * 100);

        $words = $this->convertIntegerToWords($integer);

        if ($decimal > 0) {
            $words .= ' ouguiyas et ' . $this->convertIntegerToWords($decimal) . ' centimes';
        } else {
            $words .= ' ouguiyas';
        }

        return ucfirst($words);
    }

    /**
     * Convertit un entier en mots français
     */
    private function convertIntegerToWords(int $number): string
    {
        if ($number === 0) {
            return 'zéro';
        }

        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = [
            'dix',
            'onze',
            'douze',
            'treize',
            'quatorze',
            'quinze',
            'seize',
            'dix-sept',
            'dix-huit',
            'dix-neuf'
        ];
        $tens = [
            '',
            '',
            'vingt',
            'trente',
            'quarante',
            'cinquante',
            'soixante',
            'soixante-dix',
            'quatre-vingt',
            'quatre-vingt-dix'
        ];

        $words = [];

        // Millions
        if ($number >= 1000000) {
            $millions = (int) floor($number / 1000000);
            if ($millions > 1) {
                $words[] = $this->convertHundreds($millions);
            }
            $words[] = 'million' . ($millions > 1 ? 's' : '');
            $number %= 1000000;
        }

        // Milliers
        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            if ($thousands > 1) {
                $words[] = $this->convertHundreds($thousands);
            }
            $words[] = 'mille';
            $number %= 1000;
        }

        // Centaines, dizaines, unités
        if ($number > 0) {
            $words[] = $this->convertHundreds($number);
        }

        return implode(' ', $words);
    }

    /**
     * Convertit un nombre < 1000 en mots
     */
    private function convertHundreds(int $number): string
    {
        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $teens = [
            'dix',
            'onze',
            'douze',
            'treize',
            'quatorze',
            'quinze',
            'seize',
            'dix-sept',
            'dix-huit',
            'dix-neuf'
        ];
        $tens = [
            '',
            '',
            'vingt',
            'trente',
            'quarante',
            'cinquante',
            'soixante',
            'soixante-dix',
            'quatre-vingt',
            'quatre-vingt-dix'
        ];

        $words = [];

        // Centaines
        if ($number >= 100) {
            $hundreds = (int) floor($number / 100);
            if ($hundreds > 1) {
                $words[] = $units[$hundreds];
            }
            $words[] = 'cent';
            if ($hundreds > 1 && $number % 100 === 0) {
                $words[count($words) - 1] = 'cents';
            }
            $number %= 100;
        }

        // Dizaines et unités
        if ($number >= 20) {
            $tensDigit = (int) floor($number / 10);
            $words[] = $tens[$tensDigit];
            if ($tensDigit === 8 && $number % 10 === 0) {
                $words[count($words) - 1] = 'quatre-vingts';
            }
            $number %= 10;
            if ($number > 0) {
                if (
                    $number === 1 && ($tensDigit === 2 || $tensDigit === 3 ||
                        $tensDigit === 4 || $tensDigit === 5 || $tensDigit === 6)
                ) {
                    $words[] = 'et';
                }
                $words[] = $units[$number];
            }
        } elseif ($number >= 10) {
            $words[] = $teens[$number - 10];
        } elseif ($number > 0) {
            $words[] = $units[$number];
        }

        return implode(' ', array_filter($words));
    }
}
