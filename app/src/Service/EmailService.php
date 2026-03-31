<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Company;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\Response;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Envoie un document (devis ou facture) par email
     */
    public function sendDocument(
        Document $document,
        Company $company,
        string $recipientEmail,
        ?string $recipientName = null,
        ?string $message = null
    ): void {
        $type = $document->getType() === Document::TYPE_QUOTE ? 'Devis' : 'Facture';

        // Génération du PDF
        $pdfResponse = $this->pdfGenerator->generateDocumentPdf($document, $company);
        $pdfContent = $pdfResponse->getContent();

        $filename = $this->generateFilename($document);

        // Préparation de l'email
        $email = (new Email())
            ->from(new Address('noreply@oumatraiteur.mr', $company->getName()))
            ->to(new Address($recipientEmail, $recipientName ?? $document->getClient()->getName()))
            ->subject($this->getEmailSubject($document, $company))
            ->text($this->getEmailTextBody($document, $company, $message))
            ->html($this->getEmailHtmlBody($document, $company, $message))
            ->attach($pdfContent, $filename, 'application/pdf');

        // Envoi de l'email
        $this->mailer->send($email);
    }

    /**
     * Génère le nom du fichier PDF
     */
    private function generateFilename(Document $document): string
    {
        $type = $document->getType() === Document::TYPE_QUOTE ? 'Devis' : 'Facture';
        $number = str_replace('/', '-', $document->getNumber());
        $date = $document->getDate()->format('Y-m-d');

        return sprintf('%s_%s_%s.pdf', $type, $number, $date);
    }

    /**
     * Génère le sujet de l'email
     */
    private function getEmailSubject(Document $document, Company $company): string
    {
        $type = $document->getType() === Document::TYPE_QUOTE ? 'Devis' : 'Facture';

        return sprintf(
            '%s N° %s - %s',
            $type,
            $document->getNumber(),
            $company->getName()
        );
    }

    /**
     * Génère le corps de l'email en texte brut
     */
    private function getEmailTextBody(Document $document, Company $company, ?string $message): string
    {
        $type = $document->getType() === Document::TYPE_QUOTE ? 'devis' : 'facture';
        $typeArticle = $document->getType() === Document::TYPE_QUOTE ? 'votre' : 'votre';

        $text = "Bonjour,\n\n";
        $text .= sprintf("Veuillez trouver ci-joint %s %s N° %s.\n\n", $typeArticle, $type, $document->getNumber());

        if ($message) {
            $text .= $message . "\n\n";
        }

        $text .= sprintf(
            "Date : %s\n",
            $document->getDate()->format('d/m/Y')
        );

        if ($document->getLocation()) {
            $text .= sprintf("Lieu : %s\n", $document->getLocation());
        }

        $text .= sprintf(
            "Montant total : %s %s\n\n",
            number_format((float) $document->getTotalTtc(), 0, ',', ' '),
            $document->getCurrency()
        );

        if ($document->getType() === Document::TYPE_INVOICE) {
            $text .= "Merci de procéder au règlement dans les meilleurs délais.\n\n";
        }

        $text .= "Cordialement,\n";
        $text .= $company->getName() . "\n";
        $text .= "Tél : " . $company->getPhone() . "\n";

        if ($company->getAddress()) {
            $text .= $company->getAddress();
        }

        return $text;
    }

    /**
     * Génère le corps de l'email en HTML
     */
    private function getEmailHtmlBody(Document $document, Company $company, ?string $message): string
    {
        $type = $document->getType() === Document::TYPE_QUOTE ? 'devis' : 'facture';
        $typeArticle = $document->getType() === Document::TYPE_QUOTE ? 'votre' : 'votre';
        $badgeColor = $document->getType() === Document::TYPE_QUOTE ? '#0066cc' : '#00a651';

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #00a651; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #ddd; border-top: none; }
        .badge { display: inline-block; background-color: ' . $badgeColor . '; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px; font-weight: bold; }
        .info-table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .info-table td { padding: 10px; border: 1px solid #ddd; }
        .info-table td:first-child { background-color: #f5f5f5; font-weight: bold; width: 40%; }
        .total { background-color: #e6f9f0; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; }
        .total-amount { font-size: 28px; color: #00a651; font-weight: bold; }
        .message-box { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #00a651; margin: 20px 0; }
        .footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($company->getName()) . '</h1>';

        if ($company->getNameArabic()) {
            $html .= '<p style="font-size: 16px; margin: 5px 0; direction: rtl;">' . htmlspecialchars($company->getNameArabic()) . '</p>';
        }

        $html .= '
        </div>

        <div class="content">
            <p>Bonjour,</p>
            <p>Veuillez trouver ci-joint ' . $typeArticle . ' ' . $type . ' <strong>' . htmlspecialchars($document->getNumber()) . '</strong>.</p>';

        if ($message) {
            $html .= '
            <div class="message-box">' . nl2br(htmlspecialchars($message)) . '</div>';
        }

        $html .= '
            <table class="info-table">
                <tr>
                    <td>Type</td>
                    <td><span class="badge">' . ($document->getType() === Document::TYPE_QUOTE ? 'Devis' : 'Facture') . '</span></td>
                </tr>
                <tr>
                    <td>Numéro</td>
                    <td><strong>' . htmlspecialchars($document->getNumber()) . '</strong></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>' . $document->getDate()->format('d/m/Y') . '</td>
                </tr>';

        if ($document->getLocation()) {
            $html .= '
                <tr>
                    <td>Lieu</td>
                    <td>' . htmlspecialchars($document->getLocation()) . '</td>
                </tr>';
        }

        $html .= '
            </table>

            <div class="total">
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Montant total</p>
                <div class="total-amount">' . number_format((float) $document->getTotalTtc(), 0, ',', ' ') . ' ' . $document->getCurrency() . '</div>
            </div>';

        if ($document->getType() === Document::TYPE_INVOICE) {
            $html .= '<p style="background-color: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <strong>⚠️ Paiement :</strong> Merci de procéder au règlement dans les meilleurs délais.
            </p>';
        }

        $html .= '
            <p style="margin-top: 30px;">Cordialement,<br><strong>' . htmlspecialchars($company->getName()) . '</strong></p>
        </div>

        <div class="footer">
            <p><strong>' . htmlspecialchars($company->getName()) . '</strong></p>
            <p>📞 ' . htmlspecialchars($company->getPhone()) . '</p>';

        if ($company->getAddress()) {
            $html .= '<p>📍 ' . htmlspecialchars($company->getAddress()) . '</p>';
        }

        $html .= '
            <hr style="border: none; border-top: 1px solid #ddd; margin: 15px 0;">
            <p>Cet email a été généré automatiquement. Merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Envoie un document par email via l'API (méthode simplifiée sans Company en paramètre).
     * Récupère la Company directement depuis le document.
     */
    public function sendDocumentByEmail(
        Document $document,
        string $recipientEmail,
        ?string $message = null
    ): void {
        $company = $document->getCompany();

        if (!$company) {
            // Créer une entreprise par défaut temporaire pour ne pas bloquer l'envoi
            $company = new Company();
            $company->setName('Ouma Traiteur');
            $company->setPhone('+222 45 67 89 00');
        }

        $this->sendDocument($document, $company, $recipientEmail, null, $message);
    }

    /**
     * Envoie un email de notification simple
     */
    public function sendNotification(
        string $to,
        string $subject,
        string $message,
        ?Company $company = null,
        ?string $recipientName = null
    ): void {
        $fromAddress = $company
            ? new Address('noreply@oumatraiteur.mr', $company->getName())
            : new Address('noreply@oumatraiteur.mr', 'Ouma Traiteur');

        $email = (new Email())
            ->from($fromAddress)
            ->to(new Address($to, $recipientName))
            ->subject($subject)
            ->text($message)
            ->html('<div style="font-family: Arial, sans-serif; padding: 20px;">' . nl2br(htmlspecialchars($message)) . '</div>');

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de relance pour un paiement
     */
    public function sendPaymentReminder(
        Document $document,
        Company $company,
        string $recipientEmail,
        ?string $recipientName = null,
        ?string $message = null
    ): void {
        $subject = "Rappel : Paiement de la facture N° " . $document->getNumber();

        $textBody = "Bonjour,\n\n";
        $textBody .= "Nous vous rappelons que la facture N° " . $document->getNumber() . " d'un montant de ";
        $textBody .= number_format((float) $document->getTotalTtc(), 0, ',', ' ') . " " . $document->getCurrency();
        $textBody .= " n'a pas encore été réglée.\n\n";
        $textBody .= "Merci de procéder au règlement dans les meilleurs délais.\n\n";
        $textBody .= "Cordialement,\n" . $company->getName();

        $htmlBody = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background-color: #ffffff; padding: 30px; border: 1px solid #ddd; border-top: none; }
        .warning { background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; }
        .total { background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; }
        .total-amount { font-size: 28px; color: #dc3545; font-weight: bold; }
        .footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rappel de paiement</h1>
        </div>
        <div class="content">
            <p>Bonjour,</p>
            <div class="warning">
                <p><strong>⚠️ Rappel :</strong> La facture N° <strong>' . htmlspecialchars($document->getNumber()) . '</strong> n\'a pas encore été réglée.</p>
            </div>
            <div class="total">
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666;">Montant à régler</p>
                <div class="total-amount">' . number_format((float) $document->getTotalTtc(), 0, ',', ' ') . ' ' . $document->getCurrency() . '</div>
            </div>
            <p>Merci de procéder au règlement dans les meilleurs délais.</p>
            <p>Cordialement,<br><strong>' . htmlspecialchars($company->getName()) . '</strong></p>
        </div>
        <div class="footer">
            <p><strong>' . htmlspecialchars($company->getName()) . '</strong></p>
            <p>📞 ' . htmlspecialchars($company->getPhone()) . '</p>
            <hr style="border: none; border-top: 1px solid #ddd; margin: 15px 0;">
            <p>Cet email a été généré automatiquement. Merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>';

        $this->sendNotification($recipientEmail, $subject, $textBody, $company, $recipientName);
        $this->mailer->send((new Email())
            ->from(new Address('noreply@oumatraiteur.mr', $company->getName()))
            ->to(new Address($recipientEmail, $recipientName))
            ->subject($subject)
            ->html($htmlBody));
    }
}
