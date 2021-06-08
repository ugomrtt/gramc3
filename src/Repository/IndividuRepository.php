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

namespace App\Repository;

//use App\App;
use App\Entity\Individu;

/**
 * IndividuRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class IndividuRepository extends \Doctrine\ORM\EntityRepository
{
    public function getActiveUsers()
    {
        $users = $this->getEntityManager()
                   ->createQuery('SELECT DISTINCT u FROM App:Individu u, App:Sso s WHERE ( u.admin = true OR u.expert = true OR u.president = true OR u.desactive = true OR s.individu = u.idIndividu )')
                   ->getResult();
        return $users;
    }

    public function getPresidentiables()
    {
        return $this->getEntityManager()
                   ->createQuery("SELECT DISTINCT u FROM App:Individu u  WHERE ( u.admin = true OR u.expert = true OR u.president = true  )")
                   ->getResult();
    }

    public function countAll()
    {
        return $this->createQueryBuilder('l')
                        ->select('COUNT(l)')
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    public function getCollaborateurs($responsable = false)
    {
        $dql  =    'SELECT u FROM App:Individu u INNER JOIN App:CollaborateurVersion c WITH c.collaborateur = u ';
        $dql .=    ' WHERE NOT ( u.admin = true OR u.expert = true OR u.president = true ) ';
        $dql .=    ' AND c.responsable = :responsable';

        $query = $this->getEntityManager()
         ->createQuery($dql)
         ->setParameter('responsable', $responsable);

        return $query->getResult();
    }

    public function liste_mail_like($mail)
    {
        if (is_string($mail) && strlen($mail) > 2) {
            return $this->getEntityManager()
            ->createQuery("SELECT u.mail FROM App:Individu u WHERE u.mail LIKE :key")
            ->setParameter('key', '%' . $mail .'%')
            ->getResult();
        } else {
            return [];
        }
    }

    public function liste_avant($date)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT u FROM App:Individu u WHERE u.creationStamp <= :date")
            ->setParameter('date', $date)
            ->getResult();
    }
}
