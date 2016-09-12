# PROXYCAD
Proxy permettant à partir d'un seul point d'accès de disposer de l'intégralité des flux WMS (un par commune) depuis cadastre.gouv.fr.
Ce service s'utilise comme une sercice OGC de type WMS. exemple : (http://kartenn.region-bretagne.fr/ws/cadastre/france.wms?SERVICE=WMS&REQUEST=GetCapabilities)
Il est recommandé d'utiliser une clé premium cadastre.gouv.fr : (https://www.cadastre.gouv.fr/scpc/afficherServiceWMS.do?CSRF_TOKEN=0QSM-EWVC-RJ2P-ETJA-FS83-ZWFS-GQES-BYMR)

** Prérequis
Disposer d'une base de données POSTGIS avec une couche des communes disposant des champs suivants : 
 - code INSEE
 - géométrie
 
 Exemple d'utilisation : (http://geobretagne.fr/mapfishapp/map/b391ec0966a3ff4da4c78bcfbc5688bf)