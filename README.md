Le module de paiement Atos/Sips pour Magento 1.5+ implémente la solution de paiement à distance SIPS disponible chez Atos.
Il permet l'échange d'informations de paiement, avec un très haut niveau de sécurité, entre votre banque et Magento.

Le module prend en charge la communication avec les banques suivantes (liste non exhaustive) :

- BNP Paribas : solution Mercanet
- HSBC CCF : solution Elysnet
- Société Générale : solution Sogenactif
- Crédit Lyonnais / LCL : solution Sherlock's
- Crédit du Nord / Kolb : solution Webaffaires
- Banque Postale : solution Scelliusnet
- Natixis
- Crédit mutuel de Bretagne : solution Citelis
- Credit Agricole : solution E-transactions


Fonctionnalités disponibles :

Le module de paiement Atos/Sips met à votre disposition quatre méthodes de paiement Magento :

- Atos/Sips
- Aurore
- Paiement en plusieurs fois
- Atos 1Euro.com


La méthode que vous choisirez d'utiliser sur votre site dépendra du contrat Atos que vous aurez souscrit auprès de votre banque.

Il est possible de :

- paramétrer le statut initial d'une commande
- définir les pays qui bénéficieront de ce mode de paiement


Pré-requis techniques :

Vous devez dans un premier temps souscrire à un contrat qui permettra l'utilisation du paiement Atos auprès de votre banque .

Dans un deuxième temps, votre banque vous fournira des fichiers exécutables, exploités par le module Atos/Sips, que vous devrez placer dans le répertoire lib/atos/ qui se trouve à la racine de votre Magento.
Attention : ces fichiers binaires ne sont fournis que pour les noyaux linux 2.4 et 2.6.

Côté serveur, l'utilisation de la fonction exec() doit être autorisé car le module y fait appel. Pensez également à désactiver le safe_mode.
