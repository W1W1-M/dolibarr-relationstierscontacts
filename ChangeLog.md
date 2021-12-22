# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).


## [1.0.0] - 31-09-2018
- Version initial.

## [1.0.1] - 11-06-2019
- Compatibilité avec Dolibarr v9

## [1.0.2] - 15-01-2021
- Correction de la modification de la fiche d'un contact pour enlever la sélection du tiers lorsqu'on a un filtre de recherche au lieu d'un select

## [1.0.3] - 27-01-2021
- Modification des boutons d'ajout sur les onglets des "Contacts/Adresses" d'un tiers et sur l'onglet des "Tiers" d'un contact

## [1.0.4] - 29-03-2021
- Correction de la modification de la fiche d'un contact lorsqu'il est déjà lié à un tiers

## [1.0.5] - 15-12-2021
- Ajout de l'acces au contact lié dans le cas ou l'on se connecte avec un utilisateur lié a un contact + variable globale RELATIONSTIERSCONTACTS_CONTACT_MASTER (=1 si A est master, =2 si B est master)

## [2.0.0] - 22-12-2021
- Compatibilité avec Dolibarr v14
- Ajout de la relation tiers/contact dans le formulaire de création de "tiers+contact" lorsque la constante "THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION" est activée.
