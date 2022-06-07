<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  CensusProofUpdate.php                                               *
 *                                                                      *
 *  Simulate the update of a page of a census for the case where the    *
 *  current user is not the original transcriber of the page and the    *
 *  values of the updated fields are written to the FieldComments table.*
 *                                                                      *
 *  History:                                                            *
 *      2011/07/02      created                                         *
 *      2012/01/24      use default.js for initialization               *
 *      2013/11/26      handle database server failure gracefully       *
 *      2013/11/29      let common.inc set initial value of $debug      *
 *      2014/04/26      remove formUtil.inc obsolete                    *
 *      2015/05/09      simplify and standardize <h1>                   *
 *      2015/07/02      access PHP includes using include_path          *
 *      2017/11/24      use prepared statement for insert               *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2021/04/04      escape CONTACTSUBJECT                           *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FieldComment.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/District.inc';
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// open code
$censusID           = null;
$distID             = null;
$subDistID          = null;
$cc                 = 'CA';
$countryName        = 'Canada';
$lang               = 'en';
$province           = null;
$division           = null;

// process all of the input 
$oldrownum          = '';

foreach($_POST as $key => $value)
{
    if (preg_match('/^([a-zA-Z$_]+)(\d*)$/', $key, $matches))
    {
        $key                = strtolower($matches[1]);
        $rownum             = $matches[2];

        switch($key)
        {
            case "census":
                $censusID   = $value;
                break;

            case "province":
                $province   = $value;
                break;

            case "district":
                $distID     = $value;
                break;

            case "subdistrict":
                $subDistID  = $value;
                break;

            case "division":
                $division   = $value;
                break;

            case "page":
                $page       = $value;
                break;

            case 'line':
                $line       = $value;
                $comment    = new FieldComment(array(
                                        'fc_census'     => $censusID,
                                        'fc_distid'     => $distID,
                                        'fc_sdid'       => $subDistID,
                                        'fc_div'        => $division,
                                        'fc_page'       => $page,
                                        'fc_userid'     => $userid,
                                        'fc_line'       => $line));
                break;

            case 'fldname':
                $comment['fldname']     = $value;
                break;

            case 'oldvalue':
                $comment['oldvalue']    = $value;
                break;

            case 'newvalue':
                $comment['newvalue']    = $value;
                break;

            case 'comment':
                $comment['comment']     = $value;
                $comment->save();
                $comment                = null;
                break;

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

        }       // act on specific parameters
    }           // parse field name
}               // loop through all parameters

if (is_string($censusID))
{
    $censusObj  = new Census(array('censusid' => $censusID));
}
else
{
    $msg    .= "Census parameter not specified. ";
}

if (is_string($distID))
{
    $districtObj    = new District(array('census'   => $censusObj,
                                         'id'   => $distID));
    $districtName   = $districtObj['name'];
}
else
{
    $msg    .= "District parameter not specified. ";
}

if (is_string($subDistID))
{
    if (isset($division))
    $subDistrictObj     = new SubDistrict(array('census'    => $censusObj,
                                                'distid'    => $districtObj,
                                                'id'        => $subDistID,
                                                'division'  => $division));
    else
    $subDistrictObj     = new SubDistrict(array('census'    => $censusObj,
                                                'distid'    => $districtObj,
                                                'id'        => $subDistID));
    $subDistrictName    = $subDistrictObj['name'];
    $bypage             = $subDistrictObj['bypage'];
}
else
{
    $msg    .= "SubDistrict parameter not specified. ";
}

if (is_string($page))
{
}
else
{
    $msg    .= "Page parameter not specified. ";
}

if (is_string($userid))
{
}
else
{
    $msg    .= "Userid parameter not specified. ";
}

// expand only if user is authorized
if (!canUser('edit'))
{
    $msg    .= "You are not currently authorized to update the database.
            Sign in and then refresh this page to apply the changes.";
}

$template       = new FtTemplate("CensusProofUpdate$lang.html");

$template->set('CENSUSYEAR',        $censusYear);
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('CENSUSID',          $censusID);
$template->set('PROVINCE',          $province);
$template->set('PROVINCENAME',      $provinceName);
$template->set('LANG',              $lang);
$template->set('DISTRICT',          $distID);
$template->set('DISTRICTNAME',      $districtName);
$template->set('SUBDISTRICT',       $subDistID);
$template->set('SUBDISTRICTNAME',   $subDistrictName);
$template->set('DIVISION',          $division);
$template->set('PAGE',              $page);
$template->set('PREVPAGE',          $page - $bypage);
$template->set('NEXTPAGE',          $page + $bypage);
$template->set('CENSUS',            $censusYear);
$template->set('CONTACTTABLE',      'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . 
                                    urlencode($_SERVER['REQUEST_URI']));
$template->set('IMAGE',             $image);

if ($promptTag)
    $promptTag->update(null); // hide

$template->display();

