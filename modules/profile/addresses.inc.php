<?php
/***************************************************************************
 *  Copyright (C) 2003-2009 Polytechnique.org                              *
 *  http://opensource.polytechnique.org/                                   *
 *                                                                         *
 *  This program is free software; you can redistribute it and/or modify   *
 *  it under the terms of the GNU General Public License as published by   *
 *  the Free Software Foundation; either version 2 of the License, or      *
 *  (at your option) any later version.                                    *
 *                                                                         *
 *  This program is distributed in the hope that it will be useful,        *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          *
 *  GNU General Public License for more details.                           *
 *                                                                         *
 *  You should have received a copy of the GNU General Public License      *
 *  along with this program; if not, write to the Free Software            *
 *  Foundation, Inc.,                                                      *
 *  59 Temple Place, Suite 330, Boston, MA  02111-1307  USA                *
 ***************************************************************************/

class ProfileAddress extends ProfileGeoloc
{
    private $bool;
    private $pub;
    private $tel;

    public function __construct()
    {
        $this->bool = new ProfileBool();
        $this->pub  = new ProfilePub();
        $this->tel  = new ProfileTel();
    }

    private function cleanAddress(ProfilePage &$page, array &$address, &$success)
    {
        if (@$address['changed']) {
            $address['datemaj'] = time();
        }
        $success = true;
        foreach ($address['tel'] as $t=>&$tel) {
            if (@$tel['removed'] || !trim($tel['tel'])) {
                unset($address['tel'][$t]);
            } else {
                $tel['pub'] = $this->pub->value($page, 'pub', $tel['pub'], $s);
                $tel['tel'] = $this->tel->value($page, 'tel', $tel['tel'], $s);
                if (!$s) {
                    $tel['error'] = true;
                    $success = false;
                }
            }
            unset($tel['removed']);
        }
        $address['checked'] = $this->bool->value($page, 'checked', $address['checked'], $s);
        $address['secondaire'] = $this->bool->value($page, 'secondaire', $address['secondaire'], $s);
        $address['mail'] = $this->bool->value($page, 'mail', $address['mail'], $s);
        $address['temporary'] = $this->bool->value($page, 'temporary', $address['temporary'], $s);
        $address['current'] = $this->bool->value($page, 'current', @$address['current'], $s);
        $address['pub'] = $this->pub->value($page, 'pub', $address['pub'], $s);
        unset($address['parsevalid']);
        unset($address['changed']);
        unset($address['removed']);
        unset($address['display']);
    }

    public function value(ProfilePage &$page, $field, $value, &$success)
    {
        $init = false;
        if (is_null($value)) {
            $value = $page->values['addresses'];
            $init = true;
        }
        foreach ($value as $key=>&$adr) {
            if (@$adr['removed']) {
                unset($value[$key]);
            }
        }
        $current = 0;
        $success = true;
        foreach ($value as $key=>&$adr) {
            if (@$adr['current']) {
                $current++;
            }
        }
        if ($current == 0 && count($value) > 0) {
            foreach ($value as $key=>&$adr) {
                $adr['current'] = true;
                break;
            }
        } else if ($current > 1) {
            $success = false;
        }
        foreach ($value as $key=>&$adr) {
            $ls = true;
            $this->geolocAddress($adr, $s);
            $ls = ($ls && $s);
            $this->cleanAddress($page, $adr, $s);
            $ls = ($ls && $s);
            if (!trim($adr['text'])) {
                unset($value[$key]);
            } else if (!$init) {
                $success = ($success && $ls);
            }
        }
        return $value;
    }

    private function saveTel($adrid, $telid, array &$tel)
    {
        XDB::execute("INSERT INTO  tels (uid, adrid, telid,
                                         tel_type, tel_pub, tel)
                           VALUES  ({?}, {?}, {?},
                                    {?}, {?}, {?})",
                    S::i('uid'), $adrid, $telid,
                    $tel['type'], $tel['pub'], $tel['tel']);
    }

    private function saveAddress($adrid, array &$address)
    {
        $flags = new PlFlagSet();
        if ($address['secondaire']) {
            $flags->addFlag('res-secondaire');
        }
        if ($address['mail']) {
            $flags->addFlag('courrier');
        }
        if ($address['temporary']) {
            $flags->addFlag('temporaire');
        }
        if ($address['current']) {
            $flags->addFlag('active');
        }
        if ($address['checked']) {
            $flags->addFlag('coord-checked');
        }
        XDB::execute("INSERT INTO  adresses (adr1, adr2, adr3,
                                              postcode, city, cityid,
                                              country, region, regiontxt,
                                              pub, datemaj, statut,
                                              uid, adrid, glat, glng)
                           VALUES  ({?}, {?}, {?},
                                    {?}, {?}, {?},
                                    {?}, {?}, {?},
                                    {?}, FROM_UNIXTIME({?}), {?},
                                    {?}, {?}, {?}, {?})",
                     $address['adr1'], $address['adr2'], $address['adr3'],
                     $address['postcode'], $address['city'], $address['cityid'],
                     $address['country'], $address['region'], $address['regiontxt'],
                     $address['pub'], $address['datemaj'], $flags,
                     S::i('uid'), $adrid, $address['precise_lat'], $address['precise_lon']);
        foreach ($address['tel'] as $telid=>&$tel) {
            $this->saveTel($adrid, $telid, $tel);
        }
    }

    public function save(ProfilePage &$page, $field, $value)
    {
        XDB::execute("DELETE FROM  adresses
                            WHERE  uid = {?}",
                     S::i('uid'));
        XDB::execute("DELETE FROM  tels
                            WHERE  uid = {?}",
                     S::i('uid'));
        foreach ($value as $adrid=>&$address) {
            $this->saveAddress($adrid, $address);
        }
    }
}

class ProfileAddresses extends ProfilePage
{
    protected $pg_template = 'profile/adresses.tpl';

    public function __construct(PlWizard &$wiz)
    {
        parent::__construct($wiz);
        $this->settings['addresses'] = new ProfileAddress();
        $this->watched['addresses'] = true;
    }

    protected function _fetchData()
    {
        // Build the addresses tree
        $res = XDB::query("SELECT  a.adrid AS id, a.adr1, a.adr2, a.adr3,
                                   UNIX_TIMESTAMP(a.datemaj) AS datemaj,
                                   a.postcode, a.city, a.cityid, a.region, a.regiontxt,
                                   a.pub, a.country, gp.pays AS countrytxt, gp.display,
                                   FIND_IN_SET('coord-checked', a.statut) AS checked,
                                   FIND_IN_SET('res-secondaire', a.statut) AS secondaire,
                                   FIND_IN_SET('courrier', a.statut) AS mail,
                                   FIND_IN_SET('temporaire', a.statut) AS temporary,
                                   FIND_IN_SET('active', a.statut) AS current,
                                   a.glat AS precise_lat, a.glng AS precise_lon
                             FROM  adresses AS a
                       INNER JOIN  geoloc_pays AS gp ON(gp.a2 = a.country)
                            WHERE  uid = {?} AND NOT FIND_IN_SET('pro', statut)
                         ORDER BY  adrid",
                           S::i('uid'));
        if ($res->numRows() == 0) {
            $this->values['addresses'] = array();
        } else {
            $this->values['addresses'] = $res->fetchAllAssoc();
        }

        $res = XDB::iterator("SELECT  adrid, tel_type AS type, tel_pub AS pub, tel
                                FROM  tels
                               WHERE  uid = {?}
                            ORDER BY  adrid",
                             S::i('uid'));
        $i = 0;
        $adrNb = count($this->values['addresses']);
        while ($tel = $res->next()) {
            $adrid = $tel['adrid'];
            unset($tel['adrid']);
            while ($i < $adrNb && $this->values['addresses'][$i]['id'] < $adrid) {
                $i++;
            }
            if ($i >= $adrNb) {
                break;
            }
            $address =& $this->values['addresses'][$i];
            if (!isset($address['tel'])) {
                $address['tel'] = array();
            }
            if ($address['id'] == $adrid) {
                $address['tel'][] = $tel;
            }
        }
        foreach ($this->values['addresses'] as $id=>&$address) {
            if (!isset($address['tel'])) {
                $address['tel'] = array();
            }
            unset($address['id']);
        }
    }
}

// vim:set et sw=4 sts=4 sws=4 foldmethod=marker enc=utf-8:
?>