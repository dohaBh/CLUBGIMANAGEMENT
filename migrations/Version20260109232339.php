<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109232339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cellule (id INT AUTO_INCREMENT NOT NULL, responsable_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, nb_membres INT NOT NULL, UNIQUE INDEX UNIQ_F493DEDF53C59D72 (responsable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demande_adhesion (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, cellule_id INT DEFAULT NULL, traite_par_id INT DEFAULT NULL, date_demande DATE NOT NULL, motivation LONGTEXT NOT NULL, statut_dmd VARCHAR(255) NOT NULL, INDEX IDX_8355EF98A76ED395 (user_id), INDEX IDX_8355EF9896299DA6 (cellule_id), INDEX IDX_8355EF98167FABE8 (traite_par_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, en_collab TINYINT(1) NOT NULL, partenaire VARCHAR(255) NOT NULL, programme LONGTEXT NOT NULL, date DATE NOT NULL, lieu VARCHAR(255) NOT NULL, nb_organisateurs INT NOT NULL, statut_event VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, module VARCHAR(255) NOT NULL, date DATE NOT NULL, lieu VARCHAR(255) NOT NULL, nb_participants INT NOT NULL, statut_formation VARCHAR(255) NOT NULL, is_approved TINYINT(1) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_404021BFB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation_formateurs (formation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_528364D5200282E (formation_id), INDEX IDX_528364DA76ED395 (user_id), PRIMARY KEY(formation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formation_participants (formation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_603786EE5200282E (formation_id), INDEX IDX_603786EEA76ED395 (user_id), PRIMARY KEY(formation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, cellule_id INT DEFAULT NULL, event_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, niveau VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, date_inscription DATE NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', is_pilotage TINYINT(1) NOT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, INDEX IDX_8D93D64996299DA6 (cellule_id), INDEX IDX_8D93D64971F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cellule ADD CONSTRAINT FK_F493DEDF53C59D72 FOREIGN KEY (responsable_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE demande_adhesion ADD CONSTRAINT FK_8355EF98A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE demande_adhesion ADD CONSTRAINT FK_8355EF9896299DA6 FOREIGN KEY (cellule_id) REFERENCES cellule (id)');
        $this->addSql('ALTER TABLE demande_adhesion ADD CONSTRAINT FK_8355EF98167FABE8 FOREIGN KEY (traite_par_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BFB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE formation_formateurs ADD CONSTRAINT FK_528364D5200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_formateurs ADD CONSTRAINT FK_528364DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_participants ADD CONSTRAINT FK_603786EE5200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation_participants ADD CONSTRAINT FK_603786EEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64996299DA6 FOREIGN KEY (cellule_id) REFERENCES cellule (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64971F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cellule DROP FOREIGN KEY FK_F493DEDF53C59D72');
        $this->addSql('ALTER TABLE demande_adhesion DROP FOREIGN KEY FK_8355EF98A76ED395');
        $this->addSql('ALTER TABLE demande_adhesion DROP FOREIGN KEY FK_8355EF9896299DA6');
        $this->addSql('ALTER TABLE demande_adhesion DROP FOREIGN KEY FK_8355EF98167FABE8');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BFB03A8386');
        $this->addSql('ALTER TABLE formation_formateurs DROP FOREIGN KEY FK_528364D5200282E');
        $this->addSql('ALTER TABLE formation_formateurs DROP FOREIGN KEY FK_528364DA76ED395');
        $this->addSql('ALTER TABLE formation_participants DROP FOREIGN KEY FK_603786EE5200282E');
        $this->addSql('ALTER TABLE formation_participants DROP FOREIGN KEY FK_603786EEA76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64996299DA6');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64971F7E88B');
        $this->addSql('DROP TABLE cellule');
        $this->addSql('DROP TABLE demande_adhesion');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE formation_formateurs');
        $this->addSql('DROP TABLE formation_participants');
        $this->addSql('DROP TABLE user');
    }
}
