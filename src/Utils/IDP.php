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

namespace App\Utils;

class IDP
{
// IDP::$dev

    public static $dev =
        [
        'Université de Toulouse 3 Paul Sabatier' => 'https://shibboleth.ups-tlse.fr/idp/shibboleth',
        'Comptes CRU - entreprises'  => 'urn:mace:cru.fr:federation:sac',
        'AUTRE'        => 'WAYF'
        ];

    // IDP::$prod

    public static $prod =
        [
        'CNRS'  => 'https://janus.cnrs.fr/idp',
        'Université de Toulouse 3 Paul Sabatier' => 'https://shibboleth.ups-tlse.fr/idp/shibboleth',
        'Comptes CRU - entreprises'  => 'urn:mace:cru.fr:federation:sac',
        'INPT - Institut National Polytechnique de Toulouse' => 'https://idp.inp-toulouse.fr/idp/shibboleth',
        'AUTRE'        => 'WAYF'
        ];
}
