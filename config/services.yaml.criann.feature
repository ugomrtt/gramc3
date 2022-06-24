---

# Learn more about services, parameters and containers at
# https://symfony.com/doc/current/service_container.html
parameters:
    # parameter_name: value

    # Identifications diverses du mésocentre et de la documentation
    mesoc: CRIANN
    mesoc_web: 'https://www.criann.fr/'
    mesoc_cgu: 'https://www.criann.fr/'
    mesoc_visu: 'https://www.criann.fr/'
    mesoc_attrib: 'https://www.criann.fr/'
    mesoc_merci: 'https://www.criann.fr/'
    mesoc_formation: 'https://www.calmip.univ-toulouse.fr/spip.php?rubrique15'
    mesoc_address: 'Technopôle du Madrillet - 745, avenue de l’Université - 76800 Saint-Étienne-du-Rouvray - France'

    # Si la demande en session A est supérieure à attrib_seuil_a (en hcpu), on surveille la demande en B
    attrib_seuil_a: 600000
    
    # Mécanisme de récupération des heures attribuées mais non consommées en Juin
    # recup_attrib_seuil (hcpu)    = on ne récupère que pour des attributions supérieures à ce seuil
    # recup_conso_seuil (%attrib)  = on ne s'occupe que des projets avec cono inférieure à ce seuil
    # recup_attrib_quant (%attrib) = partie de l'attribution récupérée
    recup_attrib_seuil: 300000
    recup_conso_seuil: 30
    recup_attrib_quant: 50
    
    # La récup est proposée entre les mois de Mai et Août (inclus)...
    recup_printemps_d: 5
    recup_printemps_f: 8
    
    #...ou entre les mois de Septembre et Octobre pour les heures attribuées pour l'été (Juillet et Août) et non consommées
    recup_automne_d: '09'
    recup_automne_f: 10

    # En %attribution, permet d'indiquer les projets qui ont bientôt épuisé les quotas (écran projets par année)
    conso_seuil_1: 70
    conso_seuil_2: 90
    
    # format max des figures associées à une description de projet
    max_fig_width: 800
    max_fig_height: 400
    
    # ATTENTION - Il faut choisir: rapport d'activité OU fichier attaché, PAS les deux !
    #             C'est redondant et si on a les deux à true ça va entraîner des dysfonctionnements
    #             Les deux à false, pas de pb
    #             Si mis à true, rapport d'activité (annuel) demandé à la fin de l'année
    rapport_dactivite: false
    
    # Si mis à true, propose de téléverser un fichier attaché à chaque version
    fichier_attache: true
    
    # nb de pages max pour un rapport d'activité ou un fichier attaché
    max_page_nb: 5
    
    # taille max du fichier de rapport d'activité ou fichier attaché
    # ATTENTION - Doit être en cohérence avec le paramètre post_max_size défini dans php.ini !
    #             Et peut-être aussi avec le paramètre équivalent si on est derrière un proxy !
    #             Unité = Mo
    max_size_doc: 10
    
    # Suppression de toutes les fonctionnalités liées à la consommation
    noconso: true
    
    # Suppression de la notion de rattachement administratif
    norattachement: true
    
    # Suppression des formulaires concernant les données (stockage et partage)
    nodata: true
    
    # Suppression d'une étape dans le workflow des sessions
    noedition_expertise: true
    
    # Nombre max de rallonges par projet et par sessoin
    max_rall: 2
    
    # Préfixe associé au nom du projet, dépend du type de projet
    prj_prefix:
        3: '20'
        1: '20'

    # Ressources dont on peut visualiser la consommation
    ressources_conso_group:
        1:
            type: calcul
            ress: 'cpu,gpu'
            nom: 'Heures normalisées'
            unite: h
        2:
            type: stockage
            ress: work_space
            nom: 'Espace work'
            unite: Tio

    ressources_conso_user:
        1:
            type: calcul
            ress: 'cpu,gpu'
            nom: 'Heures normalisées'
            unite: h
        2:
            type: stockage
            ress: home_space
            nom: Home
            unite: Gio
        3:
            type: stockage
            ress: tmpdir_space
            nom: tmpdir
            unite: Gio

    # Nombre max d'experts par version de projet
    max_expertises_nb: 3
    
    # Les experts peuvent entrer un commentaire général entre les mois de mai et de mars
    commentaires_experts_d: 5
    commentaires_experts_f: 3
    
    # Répertoires de données
    signature_directory: '%kernel.root_dir%/../data/fiches'
    rapport_directory: '%kernel.root_dir%/../data/rapports'
    fig_directory: '%kernel.root_dir%/../data/figures'
    dfct_directory: '%kernel.root_dir%/../data/dfct'
    
    # Heures fixes pour les projets tests
    heures_projet_test: 50000
      
    # Seuil à partir duquel on ne peut plus créer de projet au fil de l'eau (ou "agile", ou "dynamique")
    prj_seuil_sess: 100000
    
    # Différents mails
    mailadmin: admin@criann.fr
    mailfrom: ne-pas-repondre-criann@calmip.univ-toulouse.fr
    
    # Utilisé pour le nettoyage du journal et aussi des projets (pour le respect du rgpd)
    # En années
    old_journal: 10
    
    # Durée de vie du mot de passe temporaire géré par gramc
    pwd_duree: P30D
    
    
    # Les IDP les plus importants - Sur la fédération de dev on se limite aux comptes CRU on sait qu'ils fonctionnent
    IDPprod:
#        CNRS: 'https://janus.cnrs.fr/idp'
#        'Université de Toulouse 3 Paul Sabatier': 'https://shibboleth.ups-tlse.fr/idp/shibboleth'
        'Comptes CRU': 'urn:mace:cru.fr:federation:sac'
#        'INPT - Institut National Polytechnique de Toulouse': 'https://idp.inp-toulouse.fr/idp/shibboleth'
        AUTRE: WAYF

    # NE RIEN CHANGER A PARTIR DE POINT !!!!
    # ======================================

    # Parametres de connexion du supercalculateur pour envoyer la consommation
    # voir .env.local
    password_consoupload: '%env(PASSWORD_CONSOUPLOAD)%'
    
    # Paramétrage du système de mail
    # voir .env.local
    mailer_transport: '%env(MAILER_TRANSPORT)%'
    mailer_host: '%env(MAILER_HOST)%'
    mailer_user: '%env(MAILER_USER)%'
    mailer_password: '%env(MAILER_PASSWORD)%'

    # Pour crypter les cookies
    secret: '%env(APP_SECRET)%'

services:
    _defaults:
        public: false
        autowire: true
        autoconfigure: true

    #    service_name:
    #        class: App\Directory\ClassName
    #        arguments: ["@another_service_name", "plain_value", "%parameter_name%"]
     
    # Tous les controleurs sont des services, ils peuvent utiliser la dependency injection
    App\Controller\:
        resource: '../src/Controller/*'
        tags: [controller.service_arguments]

    # TODO - Il faut déclarer ce repository, pas les autres: POURQUOI ?
    App\Repository\FormationRepository:
        autowire: true
        tags: ['doctrine.repository_service']

    # La plupart des services gramc sont dans le répertoire GramcServices
    App\GramcServices\:
        resource: '../src/GramcServices/*'
        exclude: '../src/GramcServices/Workflow/*'

    # Workflows
    # Seuls certains objets définis dans le répertoire Workflows sont des services
    App\GramcServices\Workflow\:
        resource: '../src/GramcServices/Workflow/*/*Workflow.php'
        
    app.gramc_user_provider:
        class: App\Security\User\GramcUserProvider

    app.user_checker:
        class: App\Security\User\UserChecker

    App\Security\User\UserChecker: '@app.user_checker'
    
     # cf https://stackoverflow.com/questions/47613979/symfony-3-4-0-could-not-find-any-fixture-services-to-load
    # makes classes in src/AppBundle/DataFixtures available to be used as services
    # and have a tag that allows actions to type-hint services
    App\DataFixtures\:
        resource: '../src/DataFixtures'
        tags: ['doctrine.fixture.orm']

    App\EventListener\ExceptionListener:
        arguments: ["%kernel.debug%"]
        tags:
            - { name: kernel.event_listener, event: kernel.exception }

    # Evenements Doctrine lorsqu'on met à jour une version
    App\EventListener\VersionStamp:
        tags:
            - # these are the options required to define the entity listener
                name: 'doctrine.orm.entity_listener'
                event: 'preUpdate'
                entity: 'App\Entity\Version'

    App\EventListener\ProjetDerniereVersion:
        tags:
            - # these are the options required to define the entity listener
                name: 'doctrine.orm.entity_listener'
                event: 'postPersist'
                entity: 'App\Entity\Version'
            -   name: 'doctrine.orm.entity_listener'
                event: 'postRemove'
                entity: 'App\Entity\Version'
            -   name: 'doctrine.orm.entity_listener'
                event: 'postUpdate'
                entity: 'App\Entity\Version'

    # SERVICES GRAMC
    App\GramcServices\GramcDate:
        arguments: ["%recup_printemps_d%","%recup_printemps_f%",
                    "%recup_automne_d%",  "%recup_automne_f%"]

    App\GramcServices\DonneesFacturation:
        arguments: ['%dfct_directory%']

    App\GramcServices\GramcGraf\Calcul:
        arguments: ['ressources_conso_group','ressources_conso_user']
     
    App\GramcServices\GramcGraf\Stockage:
        arguments: ['ressources_conso_group','ressources_conso_user']

    App\GramcServices\GramcGraf\CalculTous:
        arguments: ['ressources_conso_group','ressources_conso_user']
     
    App\GramcServices\ServiceSessions:
        arguments: ['%recup_attrib_seuil%','%recup_conso_seuil%','%recup_attrib_quant%']
 
    App\GramcServices\ServiceProjets:
        arguments: ["%prj_prefix%",
                    "%ressources_conso_group%",
                    "%signature_directory%",
                    "%rapport_directory%",
                    "%fig_directory%",
                    "%dfct_directory%"]

    App\GramcServices\ServiceVersions:
        arguments: [ '%attrib_seuil_a%', "%prj_prefix%", "%fig_directory%", "%signature_directory%" ]
        
    App\GramcServices\ServiceMenus:
        arguments: [ "%max_rall%","%nodata%" ]
        
    App\GramcServices\ServiceNotifications:
        arguments: [ "%mailfrom%" ]

    App\GramcServices\ServiceExperts\ServiceExperts:
        arguments: [ "%max_expertises_nb%" ]

    App\GramcServices\ServiceExperts\ServiceExpertsRallonge:
        arguments: [ "%max_expertises_nb%" ]

    App\Controller\ExpertiseController:
        arguments: [ '%max_expertises_nb%' ]

    # Formulaires, validateurs
    App\Form\:
        resource: '../src/Form/*'
        tags: ["form.type"]

    App\Validator\Constraints\PagesNumberValidator:
        arguments: [ "%max_page_nb%" ]
        tags: ["validator.constraint_validator" ]
        
    # Commandes
    app.gramc.sendamail:
        class: App\Command\Sendamail
        #arguments: ["@twig","@app.gramc.ServiceNotifications"]
        arguments: ["%kernel.environment%"]
        tags: ["console.command"]
    App\Command\Sendamail: '@app.gramc.sendamail'
        
