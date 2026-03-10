<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Company;
use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ========================================
        // 1. CRÉATION DE L'ENTREPRISE
        // ========================================
        $company = new Company();
        $company->setName('Ouma Traiteur');
        $company->setNameArabic('أوما للخدمات');
        $company->setRegistrationNumber('120001/999');
        $company->setNif('01326610');
        $company->setPhone('46246698');
        $company->setAddress('Nouakchott, Mauritanie');
        $company->setBankName('BMCI');
        $company->setBankAccount('0426763050 - 14');
        // Le logo sera uploadé manuellement

        $manager->persist($company);

        // ========================================
        // 2. CRÉATION DES UTILISATEURS
        // ========================================

        // Admin
        $admin = new User();
        $admin->setEmail('admin@oumatraiteur.mr');
        $admin->setNom('Admin');
        $admin->setPrenom('Ouma');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsActive(true);
        $admin->setIsVerified(true);

        $manager->persist($admin);

        // Comptable
        $comptable = new User();
        $comptable->setEmail('comptable@oumatraiteur.mr');
        $comptable->setNom('Comptable');
        $comptable->setPrenom('Mohamed');
        $comptable->setRoles(['ROLE_COMPTABLE']);
        $comptable->setPassword($this->passwordHasher->hashPassword($comptable, 'compta123'));
        $comptable->setIsActive(true);
        $comptable->setIsVerified(true);

        $manager->persist($comptable);

        // Utilisateur standard
        $user = new User();
        $user->setEmail('user@oumatraiteur.mr');
        $user->setNom('Utilisateur');
        $user->setPrenom('Test');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setIsActive(true);
        $user->setIsVerified(true);

        $manager->persist($user);

        // ========================================
        // 3. CRÉATION DES CLIENTS
        // ========================================
        $clientsData = [
            ['CIVIC', 'Quartier TEVRAGH ZEINA, Nouakchott', '+222 45 12 34 56', 'contact@civic.mr'],
            ['Total Mauritanie', 'Avenue Gamal Abdel Nasser, Nouakchott', '+222 45 23 45 67', 'info@total.mr'],
            ['Ministère de l\'Éducation Nationale', 'Tevragh-Zeina, Nouakchott', '+222 45 34 56 78', 'contact@education.gov.mr'],
            ['Banque Centrale de Mauritanie', 'Avenue de l\'Indépendance, Nouakchott', '+222 45 45 67 89', 'info@bcm.mr'],
            ['Air Mauritanie', 'Aéroport International de Nouakchott', '+222 45 56 78 90', 'contact@airmauritanie.mr'],
            ['Société Mauritanienne des Industries de Raffinage (SMIR)', 'Zone Industrielle, Nouakchott', '+222 45 67 89 01', 'smir@smir.mr'],
            ['Mauritel', 'Avenue Gamal Abdel Nasser, Nouakchott', '+222 45 78 90 12', 'contact@mauritel.mr'],
            ['Banque Nationale de Mauritanie', 'Centre-ville, Nouakchott', '+222 45 89 01 23', 'bnm@bnm.mr'],
        ];

        foreach ($clientsData as [$name, $address, $phone, $email]) {
            $client = new Client();
            $client->setName($name);
            $client->setAddress($address);
            $client->setPhone($phone);
            $client->setEmail($email);

            $manager->persist($client);
        }

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès !\n";
        echo "👤 Utilisateurs créés :\n";
        echo "   - Admin : admin@oumatraiteur.mr / admin123 (ROLE_ADMIN)\n";
        echo "   - Comptable : comptable@oumatraiteur.mr / compta123 (ROLE_COMPTABLE)\n";
        echo "   - User : user@oumatraiteur.mr / user123 (ROLE_USER)\n";
        echo "🏢 Entreprise : Ouma Traiteur\n";
        echo "👥 " . count($clientsData) . " clients créés\n\n";
    }
}
