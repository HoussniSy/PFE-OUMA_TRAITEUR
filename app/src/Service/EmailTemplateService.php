<?php

namespace App\Service;

use App\Entity\EmailTemplate;
use App\Entity\Document;
use App\Entity\Client;
use App\Entity\Company;
use App\Repository\CompanyRepository;

class EmailTemplateService
{
    public function __construct(
        private CompanyRepository $companyRepository
    ) {}

    /**
     * Remplace les variables dans le template
     */
    public function render(EmailTemplate $template, array $data = []): array
    {
        $subject = $template->getSubject();
        $body = $template->getBody();

        // Préparer les variables
        $variables = $this->prepareVariables($data);

        // Remplacer les variables dans le sujet et le corps
        foreach ($variables as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $body = str_replace($key, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Prépare les variables depuis les données
     */
    private function prepareVariables(array $data): array
    {
        $variables = [];

        // Récupérer les données de l'entreprise
        $company = $this->companyRepository->findFirst();

        // Variables Document
        if (isset($data['document']) && $data['document'] instanceof Document) {
            $doc = $data['document'];
            $variables['{{numero}}'] = $doc->getNumber();
            $variables['{{type}}'] = $doc->getType() === 'quote' ? 'Devis' : 'Facture';
            $variables['{{date}}'] = $doc->getDate()->format('d/m/Y');
            $variables['{{montant_ht}}'] = number_format((float)$doc->getTotalHt(), 0, ',', ' ');
            $variables['{{montant_ttc}}'] = number_format((float)$doc->getTotalTtc(), 0, ',', ' ');
            $variables['{{tva}}'] = number_format((float)$doc->getTotalHt() * 0.16, 0, ',', ' ');
            $variables['{{taux_tva}}'] = '16%';
            $variables['{{devise}}'] = $doc->getCurrency();
            $variables['{{statut}}'] = $doc->getStatusLabel();
            $variables['{{lieu}}'] = $doc->getLocation() ?? 'Nouakchott';

            // Client du document
            if ($doc->getClient()) {
                $client = $doc->getClient();
                $variables['{{nom_client}}'] = $client->getName();
                $variables['{{email_client}}'] = $client->getEmail() ?? '-';
                $variables['{{telephone_client}}'] = $client->getPhone() ?? '-';
                $variables['{{adresse_client}}'] = $client->getAddress() ?? '-';
            }
        }

        // Variables Client (si fourni directement)
        if (isset($data['client']) && $data['client'] instanceof Client) {
            $client = $data['client'];
            $variables['{{nom_client}}'] = $client->getName();
            $variables['{{email_client}}'] = $client->getEmail() ?? '-';
            $variables['{{telephone_client}}'] = $client->getPhone() ?? '-';
            $variables['{{adresse_client}}'] = $client->getAddress() ?? '-';
        }

        // Variables Entreprise
        if ($company) {
            $variables['{{nom_entreprise}}'] = $company->getName();
            $variables['{{email_entreprise}}'] = 'noreply@oumatraiteur.mr'; // Email par défaut car Company n'a pas de champ email
            $variables['{{telephone_entreprise}}'] = $company->getPhone();
            $variables['{{adresse_entreprise}}'] = $company->getAddress() ?? 'Nouakchott, Mauritanie';
            $variables['{{nom_entreprise_arabe}}'] = $company->getNameArabic() ?? '';
            $variables['{{registre}}'] = $company->getRegistrationNumber();
            $variables['{{nif}}'] = $company->getNif();
            $variables['{{banque}}'] = $company->getBankName();
            $variables['{{compte_bancaire}}'] = $company->getBankAccount();
        }

        // Variables personnalisées
        if (isset($data['custom']) && is_array($data['custom'])) {
            $variables = array_merge($variables, $data['custom']);
        }

        // Variables générales
        $variables['{{message_personnalise}}'] = $data['message'] ?? 'Nous restons à votre disposition pour toute information complémentaire.';
        $variables['{{date_envoi}}'] = date('d/m/Y');
        $variables['{{heure_envoi}}'] = date('H:i');
        $variables['{{expediteur}}'] = $company ? $company->getName() : 'Ouma Traiteur';

        return $variables;
    }

    /**
     * Retourne les variables disponibles par catégorie
     */
    public function getAvailableVariables(): array
    {
        return [
            'document' => [
                'label' => 'Documents',
                'variables' => [
                    '{{numero}}' => 'Numéro du document',
                    '{{type}}' => 'Type (Devis/Facture)',
                    '{{date}}' => 'Date du document',
                    '{{montant_ht}}' => 'Montant HT',
                    '{{montant_ttc}}' => 'Montant TTC',
                    '{{tva}}' => 'Montant TVA',
                    '{{taux_tva}}' => 'Taux de TVA',
                    '{{devise}}' => 'Devise (MRU)',
                    '{{statut}}' => 'Statut du document',
                    '{{lieu}}' => 'Lieu',
                ],
            ],
            'client' => [
                'label' => 'Client',
                'variables' => [
                    '{{nom_client}}' => 'Nom du client',
                    '{{email_client}}' => 'Email du client',
                    '{{telephone_client}}' => 'Téléphone du client',
                    '{{adresse_client}}' => 'Adresse du client',
                ],
            ],
            'entreprise' => [
                'label' => 'Entreprise',
                'variables' => [
                    '{{nom_entreprise}}' => 'Nom de l\'entreprise',
                    '{{email_entreprise}}' => 'Email de l\'entreprise',
                    '{{telephone_entreprise}}' => 'Téléphone de l\'entreprise',
                    '{{adresse_entreprise}}' => 'Adresse de l\'entreprise',
                    '{{registre}}' => 'Numéro de registre',
                    '{{nif}}' => 'NIF',
                    '{{banque}}' => 'Nom de la banque',
                    '{{compte_bancaire}}' => 'Numéro de compte',
                ],
            ],
            'autres' => [
                'label' => 'Autres',
                'variables' => [
                    '{{message_personnalise}}' => 'Message personnalisé',
                    '{{date_envoi}}' => 'Date d\'envoi',
                    '{{heure_envoi}}' => 'Heure d\'envoi',
                    '{{expediteur}}' => 'Nom de l\'expéditeur',
                ],
            ],
        ];
    }

    /**
     * Valide le HTML du template
     */
    public function validateHtml(string $html): array
    {
        $errors = [];
        $warnings = [];

        // Vérifier les balises dangereuses
        $dangerousTags = ['script', 'iframe', 'object', 'embed', 'form'];
        foreach ($dangerousTags as $tag) {
            if (stripos($html, '<' . $tag) !== false) {
                $errors[] = "La balise <$tag> n'est pas autorisée pour des raisons de sécurité.";
            }
        }

        // Vérifier la présence de variables
        if (strpos($html, '{{') === false) {
            $warnings[] = "Aucune variable détectée. Pensez à utiliser des variables comme {{nom_client}}, {{numero}}, etc.";
        }

        // Vérifier les balises non fermées (simple)
        $openTags = preg_match_all('/<([a-z]+)[^>]*>/i', $html, $openMatches);
        $closeTags = preg_match_all('/<\/([a-z]+)>/i', $html, $closeMatches);

        if ($openTags !== $closeTags) {
            $warnings[] = "Certaines balises HTML semblent mal fermées. Vérifiez votre code HTML.";
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Génère des données de test pour la prévisualisation
     */
    public function getTestData(): array
    {
        $company = $this->companyRepository->findFirst();

        return [
            'custom' => [
                '{{numero}}' => 'DEV-001/2026',
                '{{type}}' => 'Devis',
                '{{date}}' => date('d/m/Y'),
                '{{montant_ht}}' => '100 000',
                '{{montant_ttc}}' => '116 000',
                '{{tva}}' => '16 000',
                '{{taux_tva}}' => '16%',
                '{{devise}}' => 'MRU',
                '{{statut}}' => 'Envoyé',
                '{{lieu}}' => 'Nouakchott',
                '{{nom_client}}' => 'Ministère de l\'Éducation Nationale',
                '{{email_client}}' => 'contact@education.gov.mr',
                '{{telephone_client}}' => '+222 45 34 56 78',
                '{{adresse_client}}' => 'Tevragh-Zeina, Nouakchott',
                '{{nom_entreprise}}' => $company ? $company->getName() : 'Ouma Traiteur',
                '{{email_entreprise}}' => 'noreply@oumatraiteur.mr',
                '{{telephone_entreprise}}' => $company ? $company->getPhone() : '46246698',
                '{{adresse_entreprise}}' => $company ? ($company->getAddress() ?? 'Nouakchott, Mauritanie') : 'Nouakchott, Mauritanie',
                '{{message_personnalise}}' => 'Nous restons à votre disposition pour toute information complémentaire.',
                '{{date_envoi}}' => date('d/m/Y'),
                '{{heure_envoi}}' => date('H:i'),
                '{{expediteur}}' => $company ? $company->getName() : 'Ouma Traiteur',
            ],
        ];
    }
}
