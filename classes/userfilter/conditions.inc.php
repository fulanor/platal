<?php
/***************************************************************************
 *  Copyright (C) 2003-2010 Polytechnique.org                              *
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

// {{{ abstract class UserFilterCondition
/** This class describe objects which filter users based
 *      on various parameters.
 * The parameters of the filter must be given to the constructor.
 * The buildCondition function is called by UserFilter when
 *     actually building the query. That function must call
 *     $uf->addWheteverFilter so that the UserFilter makes
 *     adequate joins. It must return the 'WHERE' condition to use
 *     with the filter.
 */
abstract class UserFilterCondition implements PlFilterCondition
{
    public function export()
    {
        throw new Exception("This class is not exportable");
    }
}
// }}}

// {{{ class UFC_HasProfile
/** Filters users who have a profile
 */
class UFC_HasProfile extends UserFilterCondition
{
    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        return '$PID IS NOT NULL';
    }
}
// }}}

// {{{ class UFC_AccountType
/** Filters users who have one of the given account types
 */
class UFC_AccountType extends UserFilterCondition
{
    private $types;

    public function __construct()
    {
        $this->types = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireAccounts();
        return XDB::format('a.type IN {?}', $this->types);
    }
}
// }}}

// {{{ class UFC_AccountPerm
/** Filters users who have one of the given permissions
 */
class UFC_AccountPerm extends UserFilterCondition
{
    private $perms;

    public function __construct()
    {
        $this->perms = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requirePerms();
        $conds = array();
        foreach ($this->perms as $perm) {
            $conds[] = XDB::format('FIND_IN_SET({?}, IF(a.user_perms IS NULL, at.perms,
                                                        CONCAT(at.perms, \',\', a.user_perms)))',
                                   $perm);
        }
        if (empty($conds)) {
            return self::COND_TRUE;
        } else {
            return implode(' OR ', $conds);
        }
    }
}
// }}}

// {{{ class UFC_Hruid
/** Filters users based on their hruid
 * @param $val Either an hruid, or a list of those
 */
class UFC_Hruid extends UserFilterCondition
{
    private $hruids;

    public function __construct()
    {
        $this->hruids = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireAccounts();
        return XDB::format('a.hruid IN {?}', $this->hruids);
    }
}
// }}}

// {{{ class UFC_Hrpid
/** Filters users based on the hrpid of their profiles
 * @param $val Either an hrpid, or a list of those
 */
class UFC_Hrpid extends UserFilterCondition
{
    private $hrpids;

    public function __construct()
    {
        $this->hrpids = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        return XDB::format('p.hrpid IN {?}', $this->hrpids);
    }
}
// }}}

// {{{ class UFC_Ip
/** Filters users based on one of their last IPs
 * @param $ip IP from which connection are checked
 */
class UFC_Ip extends UserFilterCondition
{
    private $ip;

    public function __construct($ip)
    {
        $this->ip = $ip;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addLoggerFilter();
        $ip = ip_to_uint($this->ip);
        return XDB::format($sub . '.ip = {?} OR ' . $sub . '.forward_ip = {?}', $ip, $ip);
    }
}
// }}}

// {{{ class UFC_Comment
class UFC_Comment extends UserFilterCondition
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        return $uf->getVisibilityCondition('p.freetext_pub') . ' AND p.freetext ' . XDB::formatWildcards(XDB::WILDCARD_CONTAINS, $this->text);
    }
}
// }}}

// {{{ class UFC_Promo
/** Filters users based on promotion
 * @param $comparison Comparison operator (>, =, ...)
 * @param $grade Formation on which to restrict, UserFilter::DISPLAY for "any formation"
 * @param $promo Promotion on which the filter is based
 */
class UFC_Promo extends UserFilterCondition
{

    private $grade;
    private $promo;
    private $comparison;

    public function __construct($comparison, $grade, $promo)
    {
        $this->grade = $grade;
        $this->comparison = $comparison;
        $this->promo = $promo;
        if ($this->grade != UserFilter::DISPLAY) {
            UserFilter::assertGrade($this->grade);
        }
        if ($this->grade == UserFilter::DISPLAY && $this->comparison != '=') {
            // XXX: we might try to guess the grade from the first char of the promo and forbid only '<= 2004', but allow '<= X2004'
            Platal::page()->killError("Il n'est pas possible d'appliquer la comparaison '" . $this->comparison . "' aux promotions sans spécifier de formation (X/M/D)");
        }
    }

    public function buildCondition(PlFilter $uf)
    {
        if ($this->grade == UserFilter::DISPLAY) {
            $sub = $uf->addDisplayFilter();
            return XDB::format('pd' . $sub . '.promo ' . $this->comparison . ' {?}', $this->promo);
        } else {
            $sub = $uf->addEducationFilter(true, $this->grade);
            $field = 'pe' . $sub . '.' . UserFilter::promoYear($this->grade);
            return $field . ' IS NOT NULL AND ' . $field . ' ' . $this->comparison . ' ' . XDB::format('{?}', $this->promo);
        }
    }
}
// }}}

// {{{ class UFC_SchoolId
/** Filters users based on their shoold identifier
 * @param type Parameter type (Xorg, AX, School)
 * @param value School id value
 */
class UFC_SchoolId extends UserFilterCondition
{
    const AX     = 'ax';
    const Xorg   = 'xorg';
    const School = 'school';

    private $type;
    private $id;

    static public function assertType($type)
    {
        if ($type != self::AX && $type != self::Xorg && $type != self::School) {
            Platal::page()->killError("Type de matricule invalide: $type");
        }
    }

    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id   = $id;
        self::assertType($type);
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        $id = $this->id;
        $type = $this->type;
        if ($type == self::School) {
            $type = self::Xorg;
            $id   = Profile::getXorgId($id);
        }
        return XDB::format('p.' . $type . '_id = {?}', $id);
    }
}
// }}}

// {{{ class UFC_EducationSchool
/** Filters users by formation
 * @param $val The formation to search (either ID or array of IDs)
 */
class UFC_EducationSchool extends UserFilterCondition
{
    private $val;

    public function __construct()
    {
        $this->val = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addEducationFilter();
        return XDB::format('pe' . $sub . '.eduid IN {?}', $this->val);
    }
}
// }}}

// {{{ class UFC_EducationDegree
class UFC_EducationDegree extends UserFilterCondition
{
    private $diploma;

    public function __construct()
    {
        $this->diploma = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addEducationFilter();
        return XDB::format('pe' . $sub . '.degreeid IN {?}', $this->diploma);
    }
}
// }}}

// {{{ class UFC_EducationField
class UFC_EducationField extends UserFilterCondition
{
    private $val;

    public function __construct()
    {
        $this->val = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addEducationFilter();
        return XDB::format('pe' . $sub . '.fieldid IN {?}', $this->val);
    }
}
// }}}

// {{{ class UFC_Name
/** Filters users based on name
 * @param $type Type of name field on which filtering is done (firstname, lastname...)
 * @param $text Text on which to filter
 * @param $mode Flag indicating search type (prefix, suffix, with particule...)
 */
class UFC_Name extends UserFilterCondition
{
    const EXACT    = XDB::WILDCARD_EXACT;    // 0x000
    const PREFIX   = XDB::WILDCARD_PREFIX;   // 0x001
    const SUFFIX   = XDB::WILDCARD_SUFFIX;   // 0x002
    const CONTAINS = XDB::WILDCARD_CONTAINS; // 0x003
    const PARTICLE = 0x004;
    const VARIANTS = 0x008;

    private $type;
    private $text;
    private $mode;

    public function __construct($type, $text, $mode)
    {
        $this->type = $type;
        $this->text = $text;
        $this->mode = $mode;
    }

    private function buildNameQuery($type, $variant, $where, UserFilter $uf)
    {
        $sub = $uf->addNameFilter($type, $variant);
        return str_replace('$ME', 'pn' . $sub, $where);
    }

    public function buildCondition(PlFilter $uf)
    {
        $left = '$ME.name';
        if (($this->mode & self::PARTICLE) == self::PARTICLE) {
            $left = 'CONCAT($ME.particle, \' \', $ME.name)';
        }
        $right = XDB::formatWildcards($this->mode & self::CONTAINS, $this->text);

        $cond = $left . $right;
        $conds = array($this->buildNameQuery($this->type, null, $cond, $uf));
        if (($this->mode & self::VARIANTS) != 0 && isset(Profile::$name_variants[$this->type])) {
            foreach (Profile::$name_variants[$this->type] as $var) {
                $conds[] = $this->buildNameQuery($this->type, $var, $cond, $uf);
            }
        }
        return implode(' OR ', $conds);
    }
}
// }}}

// {{{ class UFC_NameTokens
/** Selects users based on tokens in their name (for quicksearch)
 * @param $tokens An array of tokens to search
 * @param $flags Flags the tokens must have (e.g 'public' for public search)
 * @param $soundex (bool) Whether those tokens are fulltext or soundex
 */
class UFC_NameTokens extends UserFilterCondition
{
    /* Flags */
    const FLAG_PUBLIC = 'public';

    private $tokens;
    private $flags;
    private $soundex;
    private $exact;

    public function __construct($tokens, $flags = array(), $soundex = false, $exact = false)
    {
        if (is_array($tokens)) {
            $this->tokens = $tokens;
        } else {
            $this->tokens = array($tokens);
        }
        if (is_array($flags)) {
            $this->flags = $flags;
        } else {
            $this->flags = array($flags);
        }
        $this->soundex = $soundex;
        $this->exact = $exact;
    }

    public function buildCondition(PlFilter $uf)
    {
        $conds = array();
        foreach ($this->tokens as $i => $token) {
            $sub = $uf->addNameTokensFilter($token);
            if ($this->soundex) {
                $c = XDB::format($sub . '.soundex = {?}', soundex_fr($token));
            } else if ($this->exact) {
                $c = XDB::format($sub . '.token = {?}', $token);
            } else {
                $c = $sub . '.token ' . XDB::formatWildcards(XDB::WILDCARD_PREFIX, $token);
            }
            if ($this->flags != null) {
                $c .= XDB::format(' AND ' . $sub . '.flags IN {?}', $this->flags);
            }
            $conds[] = $c;
        }

        return implode(' AND ', $conds);
    }
}
// }}}

// {{{ class UFC_Nationality
class UFC_Nationality extends UserFilterCondition
{
    private $val;

    public function __construct()
    {
        $this->val = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        $nat = XDB::formatArray($this->val);
        $conds = array(
            'p.nationality1 IN ' . $nat,
            'p.nationality2 IN ' . $nat,
            'p.nationality3 IN ' . $nat,
        );
        return implode(' OR ', $conds);
    }
}
// }}}

// {{{ class UFC_Dead
/** Filters users based on death date
 * @param $comparison Comparison operator
 * @param $date Date to which death date should be compared (DateTime object, string or timestamp)
 */
class UFC_Dead extends UserFilterCondition
{
    private $comparison;
    private $date;

    public function __construct($comparison = null, $date = null)
    {
        $this->comparison = $comparison;
        $this->date = make_datetime($date);
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        $str = 'p.deathdate IS NOT NULL';
        if (!is_null($this->comparison)) {
            $str .= ' AND p.deathdate ' . $this->comparison . ' ' . XDB::format('{?}', $this->date->format('Y-m-d'));
        }
        return $str;
    }
}
// }}}

// {{{ class UFC_Registered
/** Filters users based on registration state
 * @param $active Whether we want to use only "active" users (i.e with a valid redirection)
 * @param $comparison Comparison operator
 * @param $date Date to which users registration date should be compared
 */
class UFC_Registered extends UserFilterCondition
{
    private $active;
    private $comparison;
    private $date;

    public function __construct($active = false, $comparison = null, $date = null)
    {
        $this->active = $active;
        $this->comparison = $comparison;
        $this->date = make_datetime($date);
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireAccounts();
        if ($this->active) {
            $date = '$UID IS NOT NULL AND a.state = \'active\'';
        } else {
            $date = '$UID IS NOT NULL AND a.state != \'pending\'';
        }
        if (!is_null($this->comparison)) {
            $date .= ' AND a.registration_date != \'0000-00-00 00:00:00\' AND a.registration_date ' . $this->comparison . ' ' . XDB::format('{?}', $this->date->format('Y-m-d'));
        }
        return $date;
    }
}
// }}}

// {{{ class UFC_ProfileUpdated
/** Filters users based on profile update date
 * @param $comparison Comparison operator
 * @param $date Date to which profile update date must be compared
 */
class UFC_ProfileUpdated extends UserFilterCondition
{
    private $comparison;
    private $date;

    public function __construct($comparison = null, $date = null)
    {
        $this->comparison = $comparison;
        $this->date = $date;
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        return 'p.last_change ' . $this->comparison . XDB::format(' {?}', date('Y-m-d H:i:s', $this->date));
    }
}
// }}}

// {{{ class UFC_Birthday
/** Filters users based on next birthday date
 * @param $comparison Comparison operator
 * @param $date Date to which users next birthday date should be compared
 */
class UFC_Birthday extends UserFilterCondition
{
    private $comparison;
    private $date;

    public function __construct($comparison = null, $date = null)
    {
        $this->comparison = $comparison;
        $this->date = $date;
    }

    public function buildCondition(PlFilter $uf)
    {
        $uf->requireProfiles();
        return 'p.next_birthday ' . $this->comparison . XDB::format(' {?}', date('Y-m-d', $this->date));
    }
}
// }}}

// {{{ class UFC_Sex
/** Filters users based on sex
 * @parm $sex One of User::GENDER_MALE or User::GENDER_FEMALE, for selecting users
 */
class UFC_Sex extends UserFilterCondition
{
    private $sex;
    public function __construct($sex)
    {
        $this->sex = $sex;
    }

    public function buildCondition(PlFilter $uf)
    {
        if ($this->sex != User::GENDER_MALE && $this->sex != User::GENDER_FEMALE) {
            return self::COND_FALSE;
        } else {
            $uf->requireProfiles();
            return XDB::format('p.sex = {?}', $this->sex == User::GENDER_FEMALE ? 'female' : 'male');
        }
    }
}
// }}}

// {{{ class UFC_Group
/** Filters users based on group membership
 * @param $group Group whose members we are selecting
 * @param $anim Whether to restrict selection to animators of that group
 */
class UFC_Group extends UserFilterCondition
{
    private $group;
    private $anim;
    public function __construct($group, $anim = false)
    {
        $this->group = $group;
        $this->anim = $anim;
    }

    public function buildCondition(PlFilter $uf)
    {
        // Groups have AX visibility.
        if ($uf->getVisibilityLevel() == ProfileVisibility::VIS_PUBLIC) {
            return self::COND_TRUE;
        }
        $sub = $uf->addGroupFilter($this->group);
        $where = 'gpm' . $sub . '.perms IS NOT NULL';
        if ($this->anim) {
            $where .= ' AND gpm' . $sub . '.perms = \'admin\'';
        }
        return $where;
    }
}
// }}}

// {{{ class UFC_Binet
/** Selects users based on their belonging to a given (list of) binet
 * @param $binet either a binet_id or an array of binet_ids
 */
class UFC_Binet extends UserFilterCondition
{
    private $val;

    public function __construct()
    {
        $this->val = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        // Binets are private.
        if ($uf->getVisibilityLevel() != ProfileVisibility::VIS_PRIVATE) {
            return self::CONF_TRUE;
        }
        $sub = $uf->addBinetsFilter();
        return XDB::format($sub . '.binet_id IN {?}', $this->val);
    }
}
// }}}

// {{{ class UFC_Section
/** Selects users based on section
 * @param $section ID of the section
 */
class UFC_Section extends UserFilterCondition
{
    private $section;

    public function __construct()
    {
        $this->section = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        // Sections are private.
        if ($uf->getVisibilityLevel() != ProfileVisibility::VIS_PRIVATE) {
            return self::CONF_TRUE;
        }
        $uf->requireProfiles();
        return XDB::format('p.section IN {?}', $this->section);
    }
}
// }}}

// {{{ class UFC_Email
/** Filters users based on an email or a list of emails
 * @param $emails List of emails whose owner must be selected
 */
class UFC_Email extends UserFilterCondition
{
    private $emails;
    public function __construct()
    {
        $this->emails = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $foreign = array();
        $virtual = array();
        $aliases = array();
        $cond = array();

        if (count($this->emails) == 0) {
            return PlFilterCondition::COND_TRUE;
        }

        foreach ($this->emails as $entry) {
            if (User::isForeignEmailAddress($entry)) {
                $foreign[] = $entry;
            } else if (User::isVirtualEmailAddress($entry)) {
                $virtual[] = $entry;
            } else {
                @list($user, $domain) = explode('@', $entry);
                $aliases[] = $user;
            }
        }

        if (count($foreign) > 0) {
            $sub = $uf->addEmailRedirectFilter($foreign);
            $cond[] = XDB::format('e' . $sub . '.email IS NOT NULL OR a.email IN {?}', $foreign);
        }
        if (count($virtual) > 0) {
            $sub = $uf->addVirtualEmailFilter($virtual);
            $cond[] = 'vr' . $sub . '.redirect IS NOT NULL';
        }
        if (count($aliases) > 0) {
            $sub = $uf->addAliasFilter($aliases);
            $cond[] = 'al' . $sub . '.alias IS NOT NULL';
        }
        return '(' . implode(') OR (', $cond) . ')';
    }
}
// }}}

// {{{ class UFC_Address
abstract class UFC_Address extends UserFilterCondition
{
    /** Valid address type ('hq' is reserved for company addresses)
     */
    const TYPE_HOME = 1;
    const TYPE_PRO  = 2;
    const TYPE_ANY  = 3;

    /** Text for these types
     */
    protected static $typetexts = array(
        self::TYPE_HOME => 'home',
        self::TYPE_PRO  => 'pro',
    );

    protected $type;

    /** Flags for addresses
     */
    const FLAG_CURRENT = 0x0001;
    const FLAG_TEMP    = 0x0002;
    const FLAG_SECOND  = 0x0004;
    const FLAG_MAIL    = 0x0008;
    const FLAG_CEDEX   = 0x0010;

    // Binary OR of those flags
    const FLAG_ANY     = 0x001F;

    /** Text of these flags
     */
    protected static $flagtexts = array(
        self::FLAG_CURRENT => 'current',
        self::FLAG_TEMP    => 'temporary',
        self::FLAG_SECOND  => 'secondary',
        self::FLAG_MAIL    => 'mail',
        self::FLAG_CEDEX   => 'cedex',
    );

    protected $flags;

    public function __construct($type = null, $flags = null)
    {
        $this->type  = $type;
        $this->flags = $flags;
    }

    protected function initConds($sub, $vis_cond)
    {
        $conds = array($vis_cond);

        $types = array();
        foreach (self::$typetexts as $flag => $type) {
            if ($flag & $this->type) {
                $types[] = $type;
            }
        }
        if (count($types)) {
            $conds[] = XDB::format($sub . '.type IN {?}', $types);
        }

        if ($this->flags != self::FLAG_ANY) {
            foreach(self::$flagtexts as $flag => $text) {
                if ($flag & $this->flags) {
                    $conds[] = 'FIND_IN_SET(' . XDB::format('{?}', $text) . ', ' . $sub . '.flags)';
                }
            }
        }
        return $conds;
    }

}
// }}}

// {{{ class UFC_AddressText
/** Select users based on their address, using full text search
 * @param $text Text for filter in fulltext search
 * @param $textSearchMode Mode for search (one of XDB::WILDCARD_*)
 * @param $type Filter on address type
 * @param $flags Filter on address flags
 * @param $country Filter on address country
 * @param $locality Filter on address locality
 */
class UFC_AddressText extends UFC_Address
{

    private $text;
    private $textSearchMode;

    public function __construct($text = null, $textSearchMode = XDB::WILDCARD_CONTAINS,
        $type = null, $flags = self::FLAG_ANY, $country = null, $locality = null)
    {
        parent::__construct($type, $flags);
        $this->text           = $text;
        $this->textSearchMode = $textSearchMode;
        $this->country        = $country;
        $this->locality       = $locality;
    }

    private function mkMatch($txt)
    {
        return XDB::formatWildcards($this->textSearchMode, $txt);
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addAddressFilter();
        $conds = $this->initConds($sub, $uf->getVisibilityCondition($sub . '.pub'));
        if ($this->text != null) {
            $conds[] = $sub . '.text' . $this->mkMatch($this->text);
        }

        if ($this->country != null) {
            $subc = $uf->addAddressCountryFilter();
            $subconds = array();
            $subconds[] = $subc . '.country' . $this->mkMatch($this->country);
            $subconds[] = $subc . '.countryFR' . $this->mkMatch($this->country);
            $conds[] = implode(' OR ', $subconds);
        }

        if ($this->locality != null) {
            $subl = $uf->addAddressLocalityFilter();
            $conds[] = $subl . '.name' . $this->mkMatch($this->locality);
        }

        return implode(' AND ', $conds);
    }
}
// }}}

// {{{ class UFC_AddressField
/** Filters users based on their address,
 * @param $val Either a code for one of the fields, or an array of such codes
 * @param $fieldtype The type of field to look for
 * @param $type Filter on address type
 * @param $flags Filter on address flags
 */
class UFC_AddressField extends UFC_Address
{
    const FIELD_COUNTRY    = 1;
    const FIELD_ADMAREA    = 2;
    const FIELD_SUBADMAREA = 3;
    const FIELD_LOCALITY   = 4;
    const FIELD_ZIPCODE    = 5;

    /** Data of the filter
     */
    private $val;
    private $fieldtype;

    public function __construct($val, $fieldtype, $type = null, $flags = self::FLAG_ANY)
    {
        parent::__construct($type, $flags);

        if (!is_array($val)) {
            $val = array($val);
        }
        $this->val       = $val;
        $this->fieldtype = $fieldtype;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addAddressFilter();
        $conds = $this->initConds($sub, $uf->getVisibilityCondition($sub . '.pub'));

        switch ($this->fieldtype) {
        case self::FIELD_COUNTRY:
            $field = 'countryId';
            break;
        case self::FIELD_ADMAREA:
            $field = 'administrativeAreaId';
            break;
        case self::FIELD_SUBADMAREA:
            $field = 'subAdministrativeAreaId';
            break;
        case self::FIELD_LOCALITY:
            $field = 'localityId';
            break;
        case self::FIELD_ZIPCODE:
            $field = 'postalCode';
            break;
        default:
            Platal::page()->killError('Invalid address field type: ' . $this->fieldtype);
        }
        $conds[] = XDB::format($sub . '.' . $field . ' IN {?}', $this->val);

        return implode(' AND ', $conds);
    }
}
// }}}

// {{{ class UFC_Corps
/** Filters users based on the corps they belong to
 * @param $corps Corps we are looking for (abbreviation)
 * @param $type Whether we search for original or current corps
 */
class UFC_Corps extends UserFilterCondition
{
    const CURRENT   = 1;
    const ORIGIN    = 2;

    private $corps;
    private $type;

    public function __construct($corps, $type = self::CURRENT)
    {
        $this->corps = $corps;
        $this->type  = $type;
    }

    public function buildCondition(PlFilter $uf)
    {
        /** Tables shortcuts:
         * pc for profile_corps,
         * pceo for profile_corps_enum - orginal
         * pcec for profile_corps_enum - current
         */
        $sub = $uf->addCorpsFilter($this->type);
        $cond = $sub . '.abbreviation = ' . $corps;
        $cond .= ' AND ' . $uf->getVisibilityCondition($sub . '.corps_pub');
        return $cond;
    }
}
// }}}

// {{{ class UFC_Corps_Rank
/** Filters users based on their rank in the corps
 * @param $rank Rank we are looking for (abbreviation)
 */
class UFC_Corps_Rank extends UserFilterCondition
{
    private $rank;
    public function __construct($rank)
    {
        $this->rank = $rank;
    }

    public function buildCondition(PlFilter $uf)
    {
        /** Tables shortcuts:
         * pc for profile_corps
         * pcr for profile_corps_rank
         */
        $sub = $uf->addCorpsRankFilter();
        $cond = $sub . '.abbreviation = ' . $rank;
        // XXX(x2006barrois): find a way to get rid of that hardcoded
        // reference to 'pc'.
        $cond .= ' AND ' . $uf->getVisibilityCondition('pc.corps_pub');
        return $cond;
    }
}
// }}}

// {{{ class UFC_Job_Company
/** Filters users based on the company they belong to
 * @param $type The field being searched (self::JOBID, self::JOBNAME or self::JOBACRONYM)
 * @param $value The searched value
 */
class UFC_Job_Company extends UserFilterCondition
{
    const JOBID = 'id';
    const JOBNAME = 'name';
    const JOBACRONYM = 'acronym';

    private $type;
    private $value;

    public function __construct($type, $value)
    {
        $this->assertType($type);
        $this->type = $type;
        $this->value = $value;
    }

    private function assertType($type)
    {
        if ($type != self::JOBID && $type != self::JOBNAME && $type != self::JOBACRONYM) {
            Platal::page()->killError("Type de recherche non valide.");
        }
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addJobCompanyFilter();
        $cond  = $sub . '.' . $this->type . XDB::formatWildcards(XDB::WILDCARD_CONTAINS, $this->value);
        $jsub = $uf->addJobFilter();
        $cond .= ' AND ' . $uf->getVisibilityCondition($jsub . '.pub');
        return $cond;
    }
}
// }}}

// {{{ class UFC_Job_Terms
/** Filters users based on the job terms they assigned to one of their
 * jobs.
 * @param $val The ID of the job term, or an array of such IDs
 */
class UFC_Job_Terms extends UserFilterCondition
{
    private $val;

    public function __construct($val)
    {
        if (!is_array($val)) {
            $val = array($val);
        }
        $this->val = $val;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addJobTermsFilter(count($this->val));
        $conditions = array();
        foreach ($this->val as $i => $jtid) {
            $conditions[] = $sub[$i] . '.jtid_1 = ' . XDB::escape($jtid);
        }
        $jsub = $uf->addJobFilter();
        $conditions[] = $uf->getVisibilityCondition($jsub . '.pub');
        return implode(' AND ', $conditions);
    }
}
// }}}

// {{{ class UFC_Job_Description
/** Filters users based on their job description
 * @param $description The text being searched for
 * @param $fields The fields to search for (CV, user-defined)
 */
class UFC_Job_Description extends UserFilterCondition
{

    private $description;
    private $fields;

    public function __construct($description, $fields)
    {
        $this->fields = $fields;
        $this->description = $description;
    }

    public function buildCondition(PlFilter $uf)
    {
        $conds = array();

        $jsub = $uf->addJobFilter();
        // CV is private => if only CV requested, and not private,
        // don't do anything. Otherwise restrict to standard job visibility.
        if ($this->fields == UserFilter::JOB_CV) {
           if ($uf->getVisibilityLevel() != ProfileVisibility::VIS_PRIVATE) {
               return self::CONF_TRUE;
           }
        } else {
            $conds[] = $uf->getVisibilityCondition($jsub . '.pub');
        }

        if ($this->fields & UserFilter::JOB_USERDEFINED) {
            $conds[] = $jsub . '.description ' . XDB::formatWildcards(XDB::WILDCARD_CONTAINS, $this->description);
        }
        if ($this->fields & UserFilter::JOB_CV && $uf->getVisibilityLevel() == ProfileVisibility::VIS_PRIVATE) {
            $uf->requireProfiles();
            $conds[] = 'p.cv ' . XDB::formatWildcards(XDB::WILDCARD_CONTAINS, $this->description);
        }
        return implode(' OR ', $conds);
    }
}
// }}}

// {{{ class UFC_Networking
/** Filters users based on network identity (IRC, ...)
 * @param $type Type of network (-1 for any)
 * @param $value Value to search
 */
class UFC_Networking extends UserFilterCondition
{
    private $type;
    private $value;

    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addNetworkingFilter();
        $conds = array();
        $conds[] = $uf->getVisibilityCondition($sub . '.pub');
        $conds[] = $sub . '.address ' . XDB::formatWildcards(XDB::WILDCARD_CONTAINS, $this->value);
        if ($this->type != -1) {
            $conds[] = $sub . '.nwid = ' . XDB::format('{?}', $this->type);
        }
        return implode(' AND ', $conds);
    }
}
// }}}

// {{{ class UFC_Phone
/** Filters users based on their phone number
 * @param $num_type Type of number (pro/user/home)
 * @param $phone_type Type of phone (fixed/mobile/fax)
 * @param $number Phone number
 */
class UFC_Phone extends UserFilterCondition
{
    const NUM_PRO   = 'pro';
    const NUM_USER  = 'user';
    const NUM_HOME  = 'address';
    const NUM_ANY   = 'any';

    const PHONE_FIXED   = 'fixed';
    const PHONE_MOBILE  = 'mobile';
    const PHONE_FAX     = 'fax';
    const PHONE_ANY     = 'any';

    private $num_type;
    private $phone_type;
    private $number;

    public function __construct($number, $num_type = self::NUM_ANY, $phone_type = self::PHONE_ANY)
    {
        $phone = new Phone(array('display' => $number));
        $phone->format();
        $this->number = $phone->search();
        $this->num_type = $num_type;
        $this->phone_type = $phone_type;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addPhoneFilter();
        $conds = array();

        $conds[] = $uf->getVisibilityCondition($sub . '.pub');

        $conds[] = $sub . '.search_tel = ' . XDB::format('{?}', $this->number);
        if ($this->num_type != self::NUM_ANY) {
            $conds[] = $sub . '.link_type = ' . XDB::format('{?}', $this->num_type);
        }
        if ($this->phone_type != self::PHONE_ANY) {
            $conds[] = $sub . '.tel_type = ' . XDB::format('{?}', $this->phone_type);
        }
        return implode(' AND ', $conds);
    }
}
// }}}

// {{{ class UFC_Medal
/** Filters users based on their medals
 * @param $medal ID of the medal
 * @param $grade Grade of the medal (null for 'any')
 */
class UFC_Medal extends UserFilterCondition
{
    private $medal;
    private $grade;

    public function __construct($medal, $grade = null)
    {
        $this->medal = $medal;
        $this->grade = $grade;
    }

    public function buildCondition(PlFilter $uf)
    {
        $conds = array();

        // This will require profiles => table 'p' will be available.
        $sub = $uf->addMedalFilter();

        $conds[] = $uf->getVisibilityCondition('p.medals_pub');

        $conds[] = $sub . '.mid = ' . XDB::format('{?}', $this->medal);
        if ($this->grade != null) {
            $conds[] = $sub . '.gid = ' . XDB::format('{?}', $this->grade);
        }
        return implode(' AND ', $conds);
    }
}
// }}}

// {{{ class UFC_Photo
/** Filters profiles with photo
 */
class UFC_Photo extends UserFilterCondition
{
    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addPhotoFilter();
        return $sub . '.attach IS NOT NULL AND ' . $uf->getVisibilityCondition($sub . '.pub');
    }
}
// }}}

// {{{ class UFC_Mentor
class UFC_Mentor extends UserFilterCondition
{
    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addMentorFilter(UserFilter::MENTOR);
        return $sub . '.expertise IS NOT NULL';
    }
}
// }}}


// {{{ class UFC_Mentor_Expertise
/** Filters users by mentoring expertise
 * @param $expertise Domain of expertise
 */
class UFC_Mentor_Expertise extends UserFilterCondition
{
    private $expertise;

    public function __construct($expertise)
    {
        $this->expertise = $expertise;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addMentorFilter(UserFilter::MENTOR_EXPERTISE);
        return $sub . '.expertise ' . XDB::formatWildcards(XDB::WILDCARD_CONTAINS, $this->expertise);
    }
}
// }}}

// {{{ class UFC_Mentor_Country
/** Filters users by mentoring country
 * @param $country Two-letters code of country being searched
 */
class UFC_Mentor_Country extends UserFilterCondition
{
    private $country;

    public function __construct()
    {
        $this->country = pl_flatten(func_get_args());
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addMentorFilter(UserFilter::MENTOR_COUNTRY);
        return $sub . '.country IN ' . XDB::format('{?}', $this->country);
    }
}
// }}}

// {{{ class UFC_Mentor_Terms
/** Filters users based on the job terms they used in mentoring.
 * @param $val The ID of the job term, or an array of such IDs
 */
class UFC_Mentor_Terms extends UserFilterCondition
{
    private $val;

    public function __construct($val)
    {
        $this->val = $val;
    }

    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addMentorFilter(UserFilter::MENTOR_TERM);
        return $sub . '.jtid_1 = ' . XDB::escape($this->val);
    }
}
// }}}

// {{{ class UFC_UserRelated
/** Filters users based on a relation toward a user
 * @param $user User to which searched users are related
 */
abstract class UFC_UserRelated extends UserFilterCondition
{
    protected $user;
    public function __construct(PlUser &$user)
    {
        $this->user =& $user;
    }
}
// }}}

// {{{ class UFC_Contact
/** Filters users who belong to selected user's contacts
 */
class UFC_Contact extends UFC_UserRelated
{
    public function buildCondition(PlFilter $uf)
    {
        $sub = $uf->addContactFilter($this->user->id());
        return 'c' . $sub . '.contact IS NOT NULL';
    }
}
// }}}

// {{{ class UFC_WatchRegistration
/** Filters users being watched by selected user
 */
class UFC_WatchRegistration extends UFC_UserRelated
{
    public function buildCondition(PlFilter $uf)
    {
        if (!$this->user->watchType('registration')) {
            return PlFilterCondition::COND_FALSE;
        }
        $uids = $this->user->watchUsers();
        if (count($uids) == 0) {
            return PlFilterCondition::COND_FALSE;
        } else {
            return XDB::format('$UID IN {?}', $uids);
        }
    }
}
// }}}

// {{{ class UFC_WatchPromo
/** Filters users belonging to a promo watched by selected user
 * @param $user Selected user (the one watching promo)
 * @param $grade Formation the user is watching
 */
class UFC_WatchPromo extends UFC_UserRelated
{
    private $grade;
    public function __construct(PlUser &$user, $grade = UserFilter::GRADE_ING)
    {
        parent::__construct($user);
        $this->grade = $grade;
    }

    public function buildCondition(PlFilter $uf)
    {
        $promos = $this->user->watchPromos();
        if (count($promos) == 0) {
            return PlFilterCondition::COND_FALSE;
        } else {
            $sube = $uf->addEducationFilter(true, $this->grade);
            $field = 'pe' . $sube . '.' . UserFilter::promoYear($this->grade);
            return XDB::format($field . ' IN {?}', $promos);
        }
    }
}
// }}}

// {{{ class UFC_WatchContact
/** Filters users watched by selected user
 */
class UFC_WatchContact extends UFC_Contact
{
    public function buildCondition(PlFilter $uf)
    {
        if (!$this->user->watchContacts()) {
            return PlFilterCondition::COND_FALSE;
        }
        return parent::buildCondition($uf);
    }
}
// }}}

// {{{ class UFC_MarketingHash
/** Filters users using the hash generated
 * to send marketing emails to him.
 */
class UFC_MarketingHash extends UserFilterCondition
{
    private $hash;

    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    public function buildCondition(PlFilter $uf)
    {
        $table = $uf->addMarketingHash();
        return XDB::format('rm.hash = {?}', $this->hash);
    }
}
// }}}

// vim:set et sw=4 sts=4 sws=4 foldmethod=marker enc=utf-8:
?>