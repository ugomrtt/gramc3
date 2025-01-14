<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Miloslav Grundmann - C.N.R.S. - UMS 3667 - CALMIP
 *            Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Version;
use App\Entity\Individu;
use App\Entity\Session;
use App\Entity\Laboratoire;
use App\Entity\Etablissement;
use App\Entity\Statut;
use App\GramcServices\ServiceJournal;
use App\GramcServices\ServiceMenus;
use App\GramcServices\ServiceProjets;
use App\GramcServices\ServiceSessions;


// Pour debug
//use App\Entity\Compta;

use App\Utils\Functions;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Esxtension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

include_once(__DIR__.'/../../jpgraph/JpGraph.php');

/**
 * Statistiques controller.
 *
 * @Route("statistiques")
 * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
 */
class StatistiquesController extends AbstractController
{
    public function __construct(
        private ServiceJournal $sj,
        private ServiceMenus $sm,
        private ServiceProjets $sp,
        private ServiceSessions $ss
    ) {}

    /**
     * @Route("/symfony", name="homepage",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     * Method({"GET","POST"})
     */
    public function homepageAction(Request $request)
    {
        return $this->render('default/base_test.html.twig');

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

    /**
      * @Route("/{annee}", name="statistiques",methods={"GET","POST"})
      * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
      */
    public function indexAction(Request $request, $annee=null)
    {
        $sm      = $this->sm;
        $ss      = $this->ss;
        $sp      = $this->sp;
        $em      = $this->getDoctrine()->getManager();
        $prj_rep = $em->getRepository(Projet::class);
        $ver_rep = $em->getRepository(Version::class);

        $data = $ss->selectAnnee($request, $annee);
        $annee= $data['annee'];

        $menu[] = $sm->statistiques_laboratoire($annee);
        $menu[] = $sm->statistiques_etablissement($annee);
        $menu[] = $sm->statistiques_thematique($annee);
        $menu[] = $sm->statistiques_metathematique($annee);
        $menu[] = $sm->statistiques_collaborateur($annee);
        $menu[] = $sm->statistiques_repartition($annee);

        $projets_renouvelles = $prj_rep->findProjetsAnnee($annee, Functions::ANCIENS);
        $projets_nouveaux    = $prj_rep->findProjetsAnnee($annee, Functions::NOUVEAUX);

        $conso_nouveaux = 0;
        foreach ($projets_nouveaux as $projet) {
            $conso_nouveaux += $sp->getConsoCalcul($projet, $annee);
        }

        $conso_renouvelles =   0;
        foreach ($projets_renouvelles as $projet) {
            $conso_renouvelles += $sp->getConsoCalcul($projet, $annee);
        }

        $num_projets_renouvelles = count($projets_renouvelles);
        $num_projets_nouveaux    = count($projets_nouveaux);
        $num_projets             = $num_projets_renouvelles    +   $num_projets_nouveaux;

        $heures_tous             = $prj_rep->heuresProjetsAnnee($annee, Functions::TOUS);
        $heures_renouvelles      = $prj_rep->heuresProjetsAnnee($annee, Functions::ANCIENS);
        $heures_nouveaux         = $prj_rep->heuresProjetsAnnee($annee, Functions::NOUVEAUX);

        $versions          = $ver_rep->findVersionsAnnee($annee);
        $individus         = [];
        $individus_uniques = [];
        $labos             = [];
        $lab_hist          = ["== 1" => 0,
                              "<= 5" => 0,
                              "<=10" => 0,
                              "<=20" => 0,
                              "> 20" => 0
                             ];

        # Remplissage de $individus et $individus_uniques
       foreach ($versions as $version) {
            $collaborateurs_versions = $version->getCollaborateurVersion();
            foreach ($collaborateurs_versions as $collaborateurVersion) {
                $individu   = $collaborateurVersion->getCollaborateur();
                if ($individu == null) {
                    continue;
                }
                $idIndividu = $individu->getIdIndividu();

                if (count($collaborateurs_versions) == 1) {
                    $individus_uniques[$idIndividu] = $individu;
                }
                $individus[$idIndividu] = $individu;
                if ($collaborateurVersion->getResponsable()) {
                    # Plutôt que d'utiliser $version->getLabo() qui rejoue la même boucle
                    $lab = $collaborateurVersion->getLabo();
                    if ($lab != null) {
                        $labid = $lab->getId();
                        if (! isset($labos[$labid])) {
                            $labos[$labid] = 0;
                        }
                        $labos[$labid] += 1;
                    }
                }
            }
        }

        # Calcul de l'histogramme
        foreach ($labos as $l=>$v) {
            if ($v > 20) {
                $lab_hist["> 20"] += 1;
            } elseif ($v > 10) {
                $lab_hist["<=20"] += 1;
            } elseif ($v > 5) {
                $lab_hist["<=10"] += 1;
            } elseif ($v > 1) {
                $lab_hist["<= 5"] += 1;
            } else {
                $lab_hist["== 1"] += 1;
            }
        }

        // debug
        //$db_conso = $em->getRepository(Compta::class)->consoTotale( $annee, 'cpu' );
        //$dessin_heures = $this->get('App\GramcServices\GramcGraf\Calcultous');
        //$debut = new \DateTime( $annee . '-01-01');
        //$nannee= $annee + 1;
        //$fin   = new \DateTime( $nannee . '-01-02');
        //$struct_data = $dessin_heures->createStructuredData($debut,$fin,$db_conso);
        //$dessin_heures->derivConso($struct_data);

        return $this->render(
            'statistiques/index.html.twig',
            [
            'form'                    => $data['form']->createView(),
            'annee'                   => $annee,
            'menu'                    => $menu,
            'num_projets'             => $num_projets,
            'num_projets_renouvelles' => $num_projets_renouvelles,
            'num_projets_nouveaux'    => $num_projets_nouveaux,
            'heures_tous'             => $heures_tous,
            'heures_renouvelles'      => $heures_renouvelles,
            'heures_nouveaux'         => $heures_nouveaux,
            'num_individus'           => count($individus),
            'num_individus_uniques'   => count($individus_uniques),
            'conso_nouveaux'          => $conso_nouveaux,
            'conso_renouvelles'       => $conso_renouvelles,
            'lab_hist'                => $lab_hist,
            //'struct_data' => $struct_data
        ]
        );
    }


    /**
     * @Route("/{annee}/repartition", name="statistiques_repartition",methods={"GET"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function repartitionAction(Request $request, $annee)
    {
        $sm = $this->sm;
        $ss = $this->ss;
        $sj = $this->sj;
        $em = $this->getDoctrine()->getManager();

        $data = $ss->selectAnnee($request, $annee);
        $annee= $data['annee'];

        $menu[] = $sm->statistiques_laboratoire($annee);
        $menu[] = $sm->statistiques_etablissement($annee);
        $menu[] = $sm->statistiques_thematique($annee);
        $menu[] = $sm->statistiques_metathematique($annee);
        $menu[] = $sm->statistiques_collaborateur($annee);
        $menu[] = $sm->statistiques_repartition($annee);

        $versions = $em->getRepository(Version::class)->findVersionsAnnee($annee);

        $collaborateurs = [];
        $comptes = [];
        foreach ($versions as $version) {
            $collaborateurVersions = $version->getCollaborateurVersion();
            $compte = 0;
            $personne = 0;
            foreach ($collaborateurVersions as $collaborateurVersion) {
                if ($collaborateurVersion->getCollaborateur() == null) {
                    $sj->errorMessage(__METHOD__ . ':' . __LINE__ . " Collaborateur null dans un collaborateurVersion de la version "  . $version);
                    continue;
                }
                $personne++;
                if ($collaborateurVersion->getLogin()== true) {
                    $compte++;
                }
            }

            $idProjet = $version->getProjet()->getIdProjet();
            $collaborateurs[ $personne ][] = $idProjet;
            $comptes[ $compte ][] = $idProjet;
            //if( $compte != $personne ) return new Response('KO');
        }

        $count_collaborateurs = [];
        foreach ($collaborateurs as $personnes => $projets) {
            $count_collaborateurs[ $personnes ] = count(array_unique($projets));
        }

        ksort($count_collaborateurs);

        $count_comptes = [];
        foreach ($comptes as $compte => $projets) {
            $count_comptes[ $compte ] = count(array_unique($projets));
        }
        ksort($count_comptes);

        //return new Response( Functions::show( $collaborateurs ) );

        return $this->render(
            'statistiques/repartition.html.twig',
            [
            'menu' => $menu,
            //'histogram_collaborateurs' => $this->histogram("Collaborateurs par projet pour l'année " + $data['annee'], $collaborateurs),
            //'histogram_comptes' => $this->histogram("Comptes par projet pour l'année " + $data['annee'], $comptes),
            'histogram_comptes' => $this->line("Répartition des projets par nombre de projets pour l'année " . $data['annee'], $count_comptes),
            'histogram_collaborateurs' => $this->line("Répartition des projets par nombre de collaborateurs pour l'année " . $data['annee'], $count_collaborateurs),
            'collaborateurs'    => $count_collaborateurs,
            'comptes'           => $count_comptes,
            'projets_sans_compte'   => $comptes[ 0 ],
            'annee'             => $annee,
            'form'  =>  $data['form']->createView(),
        ]
        );
    }

    /**
     * @Route("/{annee}/collaborateur", name="statistiques_collaborateur",methods={"GET"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function collaborateurAction(Request $request, $annee)
    {
        $sm = $this->sm;
        $ss = $this->ss;
        $em = $this->getDoctrine()->getManager();

        $data = $ss->selectAnnee($request, $annee);
        $annee= $data['annee'];

        $menu[] = $sm->statistiques_laboratoire($annee);
        $menu[] = $sm->statistiques_etablissement($annee);
        $menu[] = $sm->statistiques_thematique($annee);
        $menu[] = $sm->statistiques_metathematique($annee);
        $menu[] = $sm->statistiques_collaborateur($annee);
        $menu[] = $sm->statistiques_repartition($annee);

        $versions = $em->getRepository(Version::class)->findVersionsAnnee($annee);

        $statuts    = [];
        foreach ($em->getRepository(Statut::class)->findAll() as $statut) {
            $statuts[$statut->getIdStatut()] = [ 'statut' => $statut, 'individus' => [], 'count' => 0 ];
        }

        $laboratoires   =   [];
        foreach ($em->getRepository(Laboratoire::class)->findAll() as $laboratoire) {
            $laboratoires[$laboratoire->getIdLabo()] = [ 'laboratoire' => $laboratoire, 'individus' => [], 'count' => 0 ];
        }

        $etablissements   =   [];
        foreach ($em->getRepository(Etablissement::class)->findAll() as $etablissement) {
            $etablissements[$etablissement->getIdEtab()] = [ 'etablissement' => $etablissement, 'individus' => [], 'count' => 0 ];
        }

        $individusIncomplets = [];
        $individus           =   [];
        foreach ($versions as $version) {
            foreach ($version->getCollaborateurVersion() as $collaborateurVersion) {
                $individu       =  $collaborateurVersion->getCollaborateur();
                $statut         =  $collaborateurVersion->getStatut();
                $laboratoire    =  $collaborateurVersion->getLabo();
                $etablissement  =  $collaborateurVersion->getEtab();

                // Si un responsable de projet a inséré un collaborateur hors session d'attribution, on ne l'a pas obligé
                // à remplir ces trois champs. Il ne pourra cependant pas renouveler son projet s'il ne les complète pas
                // TODO - Arranger ce truc - cf. ticket #223
                if ($statut==null || $laboratoire==null || $etablissement==null) {
                    $individusIncomplets[] = $collaborateurVersion;
                    continue;
                }

                $statuts[$statut->getId()]['individus'][$individu->getIdIndividu()] =  $individu;
                $laboratoires[$laboratoire->getId()]['individus'][$individu->getIdIndividu()] =  $individu;
                $etablissements[$etablissement->getId()]['individus'][$individu->getIdIndividu()] =  $individu;

                $individus[$individu->getIdIndividu()][$collaborateurVersion->getId()] =
                        [
                        'statut'=>$statut,
                        'laboratoire'=>$laboratoire,
                        'etablissement'=>$etablissement,
                        'version'=>$version,
                        'individu'=>$individu,
                        ];
            }
        }

        $anomaliesStatut         =   [];
        $anomaliesLaboratoire    =   [];
        $anomaliesEtablissement  =   [];

        $changementStatut           =   [];
        $changementLaboratoire      =   [];
        $changementEtablissement    =   [];

        foreach ($individus as $key => $individuArray) {
            foreach ($individuArray as  $key1 => $array1) {
                foreach ($individuArray as $key2 =>  $array2) {
                    $version1   =  $array1['version'];
                    $version2   =  $array2['version'];

                    $statut1    =  $array1['statut'];
                    $statut2    =  $array2['statut'];

                    $laboratoire1   =  $array1['laboratoire'];
                    $laboratoire2   =  $array2['laboratoire'];

                    $etablissement1 =  $array1['etablissement'];
                    $etablissement2 =  $array2['etablissement'];

                    if ($key1 < $key2   && $statut1 != $statut2) {
                        if ($version1->typeSession() == $version2->typeSession()) {
                            $anomaliesStatut[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'statut1'   =>  $statut1,
                                            'statut2'   =>  $statut2,
                                            ];
                        } else {
                            $changementStatut[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'statut1'   =>  $statut1,
                                            'statut2'   =>  $statut2,
                                            ];
                        }
                    }

                    if ($key1 < $key2   && $laboratoire1 != $laboratoire2) {
                        if ($version1->typeSession() == $version2->typeSession()) {
                            $anomaliesLaboratoire[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'laboratoire1'   =>  $laboratoire1,
                                            'laboratoire2'   =>  $laboratoire2,
                                            ];
                        } else {
                            $changementLaboratoire[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'laboratoire1'   =>  $laboratoire1,
                                            'laboratoire2'   =>  $laboratoire2,
                                            ];
                        }
                    }

                    if ($key1 < $key2   && $etablissement1 != $etablissement2) {
                        if ($version1->typeSession() == $version2->typeSession()) {
                            $anomaliesEtablissement[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'etablissement1'   =>  $etablissement1,
                                            'etablissement2'   =>  $etablissement2,
                                            ];
                        } else {
                            $changementEtablissement[]   =   [
                                            'version1'  =>  $version1,
                                            'version2'  =>  $version2,
                                            'individu' =>  $array1['individu'],
                                            'etablissement1'   =>  $etablissement1,
                                            'etablissement2'   =>  $etablissement2,
                                            ];
                        }
                    }
                }
            }
        }

        // return new Response( Functions::show(  [ $anomaliesStatut, $anomaliesLaboratoire, $anomaliesEtablissement ] ) );

        $total  =    0;
        $image_data     =   [];
        $acros          =   [];
        foreach ($statuts as $key => $statut) {
            $count              =   count($statut['individus']);
            $statuts[$key]['count']    =   $count;
            if ($count > 0) {
                $total              =   $total  +   $count;
                $image_data[]       =   $count;
                $acros[]            =   $statut['statut']->__toString();
            } else {
                unset($statuts[$key]);
            }
        }
        $statuts_total         =   $total;
        $image_statuts = $this->camembert($image_data, $acros, "Nombre de collaborateurs par statut");
        foreach ($statuts as $key => $statut) {
            $statuts[$key]['percent']   =  100 * $statuts[$key]['count'] / $statuts_total ;
        }


        $total  =    0;
        $image_data     =   [];
        $acros          =   [];
        foreach ($laboratoires as $key=>$laboratoire) {
            $count                  =   count($laboratoire['individus']);
            $laboratoires[$key]['count']   =   $count;
            if ($count > 0) {
                $total                  =   $total  +   $count;
                $image_data[]       =   $count;
                $acros[]            =   $laboratoire['laboratoire']->getAcroLabo();
            } else {
                unset($laboratoires[$key]);
            }
        }
        $laboratoires_total      =   $total;
        $image_laboratoires = $this->camembert($image_data, $acros, "Nombre de collaborateurs par laboratoire");
        foreach ($laboratoires as $key=>$laboratoire) {
            $laboratoires[$key]['percent']  =  100 * $laboratoires[$key]['count'] / $laboratoires_total;
        }



        $total  =    0;
        $image_data     =   [];
        $acros          =   [];
        foreach ($etablissements as $key=>$etablissement) {
            $count                  =   count($etablissement['individus']);
            $etablissements[$key]['count'] =   $count;
            if ($count > 0) {
                $total                  =   $total  +   $count;
                $image_data[]       =   $count;
                $acros[]            =   $etablissement['etablissement']->__toString();
            } else {
                unset($etablissements[$key]);
            }
        }
        $etablissements_total    =   $total;
        $image_etablissements = $this->camembert($image_data, $acros, "Nombre de collaborateurs par établissement");
        foreach ($etablissements as $key=>$etablissement) {
            $etablissements[$key]['percent']  =  100 * $etablissements[$key]['count'] / $etablissements_total;
        }

        //return new Response( Functions::show( $statuts ) );

        return $this->render(
            'statistiques/collaborateur.html.twig',
            [
            'menu'                         => $menu,
            'form'                         =>  $data['form']->createView(),
            'annee'                        =>  $annee,
            'statuts'                      => $statuts,
            'laboratoires'                 => $laboratoires,
            'etablissements'               => $etablissements,
            'statuts_total'                => $statuts_total,
            'laboratoires_total'           => $laboratoires_total,
            'etablissements_total'         => $etablissements_total,
            'image_statuts'                => $image_statuts,
            'image_laboratoires'           => $image_laboratoires,
            'image_etablissements'         => $image_etablissements,
            'individusIncomplets'          => $individusIncomplets,
            'anomaliesStatut'              => $anomaliesStatut,
            'anomaliesLaboratoire'         => $anomaliesLaboratoire,
            'anomaliesEtablissement'       => $anomaliesEtablissement,
            'countChangementStatut'        =>  count($changementStatut),
            'countChangementLaboratoire'   =>  count($changementLaboratoire),
            'countChangementEtablissement' =>  count($changementEtablissement),
            ]
        );
    }

    /* Cette fonction est appelée par laboratoireAction, etablissementAction etc. */
    private function parCritere(Request $request, $annee, $critere, $titre)
    {
        $sm = $this->sm;
        $ss = $this->ss;

        $data = $ss->selectAnnee($request, $annee);
        $annee= $data['annee'];

        $menu[] = $sm->statistiques_laboratoire($annee);
        $menu[] = $sm->statistiques_etablissement($annee);
        $menu[] = $sm->statistiques_thematique($annee);
        $menu[] = $sm->statistiques_metathematique($annee);
        $menu[] = $sm->statistiques_collaborateur($annee);
        $menu[] = $sm->statistiques_repartition($annee);

        $stats = $this->statistiques($annee, $critere, $titre);

        return $this->render(
            'statistiques/parcritere.html.twig',
            [
            'titre'         => $titre,
            'menu'          => $menu,
            'form'          => $data['form']->createView(),
            'annee'         => $annee,
            'acros'         => $stats['acros'],
            'num_projets'   => $stats['num_projets'],
            'dem_heures'    => $stats['dem_heures'],
            'attr_heures'   => $stats['attr_heures'],
            'conso'         => $stats['conso'],
            'image_projets' => $stats['image_projets'],
            'image_dem'     => $stats['image_dem'],
            'image_attr'    => $stats['image_attr'],
            'image_conso'   => $stats['image_conso'],
            ]
        );
    }

    /**
     * @Route("/{annee}/laboratoire", name="statistiques_laboratoire",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function laboratoireAction(Request $request, $annee)
    {
        return $this->parCritere($request, $annee, "getAcroLaboratoire", "laboratoire");
    }

    /**
     * @Route("/{annee}/etablissement", name="statistiques_etablissement",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS')")
     */
    public function etablissementAction(Request $request, $annee)
    {
        return $this->parCritere($request, $annee, "getAcroEtablissement", "établissement");
    }

    /**
     * @Route("/{annee}/thematique", name="statistiques_thematique",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function thematiqueAction(Request $request, $annee)
    {
        return $this->parCritere($request, $annee, "getAcroThematique", "thématique");
    }

    /**
     * @Route("/{annee}/metathematique", name="statistiques_metathematique",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function metathematiqueAction(Request $request, $annee)
    {
        return $this->parCritere($request, $annee, "getAcroMetaThematique", "métathématique");
    }

    /* Cette fonction est appelée par laboratoireCSVAction, etablissementCSVAction etc. */
    private function parCritereCSV(Request $request, $annee, $critere, $titre)
    {
        $em = $this->getDoctrine()->getManager();

        $sortie =   "Statistiques de l'année ". $annee . " par $titre \n";
        $ligne  =   [$titre,"nombre de projets","heures demandées","heures attribuées","heure consommées"];
        $sortie .= join("\t", $ligne) . "\n";

        //$versions = $em->getRepository(Version::class)->findVersionsAnnee($annee);
        $stats = $this->statistiques($annee, $critere, $titre);

        foreach ($stats['acros'] as $acro) {
            $ligne = [ '"' . $acro . '"', $stats['num_projets'][$acro], $stats['dem_heures'][$acro], $stats['attr_heures'][$acro], $stats['conso'][$acro] ];
            $sortie .= join("\t", $ligne) . "\n";
        }

        return Functions::csv($sortie, "statistiques_$titre.csv");
    }

    /**
     * @Route("/{annee}/metathematique_csv", name="statistiques_métathématique_csv",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function metathematiqueCSVAction(Request $request, $annee)
    {
        return $this->parCritereCSV($request, $annee, "getAcroMetaThematique", "métathématique");
    }

    /**
     * @Route("/{annee}/thematique_csv", name="statistiques_thématique_csv",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function thematiqueCSVAction(Request $request, $annee)
    {
        return $this->parCritereCSV($request, $annee, "getAcroThematique", "thématique");
    }

    /**
     * @Route("/{annee}/laboratoire_csv", name="statistiques_laboratoire_csv",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function laboratoireCSVAction(Request $request, $annee)
    {
        return $this->parCritereCSV($request, $annee, "getAcroLaboratoire", "laboratoire");
    }

    /**
     * @Route("/{annee}/etablissement_csv", name="statistiques_établissement_csv",methods={"GET","POST"})
     * @Security("is_granted('ROLE_OBS') or is_granted('ROLE_PRESIDENT')")
     */
    public function etablissementCSVAction(Request $request, $annee)
    {
        return $this->parCritereCSV($request, $annee, "getAcroEtablissement", "établissement");
    }

    /*
     * $annee   = L'année considérée
     * $critere = Un nom de getter de Version permettant de consolider partiellement les données
     *            Le getter renverra un acronyme (laboratoire, établissement etc)
     *            (ex = getAcroLaboratoire())
     * $titre   = Titre du camembert
     */
    private function statistiques($annee, $critere, $titre = "Titre")
    {
        $sp          = $this->sp;
        $stats       = $sp->projetsParCritere($annee, $critere);
        $acros       = $stats[0];
        $num_projets = $stats[1];
        $dem_heures  = $stats[3];
        $attr_heures = $stats[4];
        $conso       = $stats[5];

        $image_data = [];
        foreach ($acros as $key => $acro) {
            $image_data[$key]   =  $num_projets[$acro];
        }
        $image_projets = $this->camembert($image_data, $acros, "Nombre de projets par " . $titre);

        $image_data = [];
        foreach ($acros as $key => $acro) {
            $image_data[$key]   =  $dem_heures[$acro];
        }
        $image_dem = $this->camembert($image_data, $acros, "Nombre d'heures demandées par " . $titre);

        $image_data = [];
        foreach ($acros as $key => $acro) {
            $image_data[$key]   =  $attr_heures[$acro];
        }
        $image_attr = $this->camembert($image_data, $acros, "Nombre d'heures attribuées par " . $titre);

        $image_data = [];
        foreach ($acros as $key => $acro) {
            $image_data[$key]   =  $conso[$acro];
        }
        $image_conso = $this->camembert($image_data, $acros, "Consommation par " . $titre);

        return ["acros"         => $acros,
                "num_projets"   => $num_projets,
                "dem_heures"    => $dem_heures,
                "attr_heures"   => $attr_heures,
                "conso"         => $conso,
                "image_projets" => $image_projets,
                "image_dem"     => $image_dem,
                "image_attr"    => $image_attr,
                "image_conso"   => $image_conso ];
    }

    ///////////////////////////////////////////

    private function camembert($data, $acros, $titre = "Titre")
    {
        $seuil = array_sum($data) * 0.01;
        $autres = 0;
        foreach ($data as $key => $value) {
            if ($data[$key] <= $seuil || $acros[$key] == "Autres" ||  $acros[$key] == "Autre" || $acros[$key] == "autres" || $acros[$key] == "autre" || $acros[$key] == "") {
                $autres = $autres + $data[$key];
                unset($data[$key]);
                unset($acros[$key]);
            }
        }

        if ($autres > 0) {
            $data[]     =   $autres;
            $acros[]    =   "Autres";
        }

        if (array_sum($data) == 0) {
            $data[]     =   1;
            $acros[]    =   "Aucune valeur";
        }

        $data = array_values($data);
        $acros = array_values($acros);

        // bibliothèque graphique

        $x = 900;
        $y = 1000;
        $xcenter=0.3;
        $ycenter=0.9;
        $xlegend = 0.02;
        $ylegend = 0.80;
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('pie');

        // Création du graph Pie. Ce dernier peut être mise en cache  avec PieGraph(300,300,"SomCacheFileName")
        $graph = new \PieGraph($x, $y);
        $graph->SetMargin(60, 60, 50, 50);
        //$graph->SetMargin(160,160,150,150);
        $graph->SetMarginColor("silver");
        $graph->SetFrame(true, 'silver');
        //$graph->legend->SetFrameWeight(1);
        $graph->legend->SetFrameWeight(1);

        //      $graph->SetShadow();

        // Application d'un titre au camembert
        $graph->title->Set($titre);
        $graph->legend->Pos($xlegend, $ylegend);

        // Création du graphe
        $p1 = new \PiePlot($data);
        $p1->SetLegends($acros);
        $p1->SetCenter($xcenter, $ycenter);
        $graph->Add($p1);

        //~ $color = array();


        $p1->SetTheme('earth');
        //$p1->SetSliceColors(color);
        // .. Création effective du fichier

        ob_start();
        $graph->Stroke();
        $image_data = ob_get_contents();
        ob_end_clean();

        $image = base64_encode($image_data);
        return $image;
    }


    ////////////////////////////////////////////////////////////////////////////////


    private function histogram($titre, $donnees, $legende = "abc")
    {
        // Initialisation du graphique
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('bar');
        \JpGraph\JpGraph::module('pie');
        $graph = new \BarPlot(700, 500);
        return null;

        // Echelle lineaire ('lin') en ordonnee et pas de valeur en abscisse ('text')
        // Valeurs min et max seront determinees automatiquement
        $graph->setScale("textlin");
        $graph->SetMargin(60, 60, 50, 50);
        $graph->SetMarginColor("silver");
        $graph->SetFrame(true, 'silver');
        $graph->legend->SetFrameWeight(1);

        // Creation de l'histogramme
        $histo = new \BarPlot($donnees);
        // Ajout de l'histogramme au graphique
        $graph->add($histo);
        //~ $graphe->xaxis->scale->SetAutoMin($legende[0]);
        $graph->xaxis->SetTickLabels($legende);
        // Ajout du titre du graphique
        $graph->title->set($titre);

        ob_start();
        $graph->Stroke();
        $image_data = ob_get_contents();
        ob_end_clean();

        $image = base64_encode($image_data);
        return $image;
    }

    ////////////////////////////////////////////////////////////////////////////////

    private function line($titre, $donnees)
    {
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('line');

        $x  =   [];
        $y  =   [];
        foreach ($donnees as $key => $value) {
            $x[]    =   $key;
            $y[]    =   $value;
        }


        //$legende = [ '','jan','fév','mar','avr','mai','juin','juil','août','sept','oct','nov','déc' ];
        $donnees = [ 1, 3, 4, 3 ];
        $legende = [ 1, 2, 3, 4 ];

        $graph = new \Graph(800, 400);
        $graph->SetScale('textlin');
        $graph->SetMargin(60, 60, 50, 50);
        $graph->SetMarginColor("silver");
        $graph->SetFrame(true, 'silver');
        $graph->title->Set($titre);
        $graph->xgrid->Show();
        $graph->xaxis->SetTickLabels($x);

        //$constante_limite = new \LinePlot($quota);
        //$constante_limite->SetColor('#FF0000');
        //$constante_limite->SetLegend('Quotas');
        //$graph->Add($constante_limite);

        $courbe = new \LinePlot($y);
        $courbe->SetLegend('Projets');
        $courbe->SetColor('#2E64FE');

        //aide a l'affichage du graphique : affiche 10% de la conso max en plus
        //$aff_limite = new \LinePlot($affichage_max);
        //$aff_limite->SetColor('#FFFFFF');

        $graph->Add($courbe);
        //$graph->Add($constante_limite);
        //$graph->Add($aff_limite);

        $graph->legend->SetFrameWeight(1);
        $graph->legend->SetLayout(1); // LEGEND_HOR
        $graph->legend->SetPos(0.5, 0.98, 'center', 'bottom');

        ob_start();
        $graph->Stroke();
        $image_data = ob_get_contents();
        ob_end_clean();

        $image = base64_encode($image_data);
        return $image;

        //$twig = new \Twig_Environment( new \Twig_Loader_String(), array( 'strict_variables' => false ) );
        //$body = $twig->render( '<img src="data:image/png;base64, {{ EncodedImage }}" />' ,  [ 'EncodedImage' => $image,      ] );

        //return new Response($body);
    }
}
