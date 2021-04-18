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
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/common.inc';

// open code
$cc     = 'CA';
$countryName    = 'Canada';
$lang       = 'en';
$province   = '';
$division   = '';

// process all of the input 
$oldrownum  = '';

foreach($_POST as $key => $value)
{
    $rownum     = substr($key, strlen($key) - 2);

    // ignore the common fields, already processed above
    if (ctype_digit($rownum))
    {           // field in individual record
        if ($rownum != $oldrownum)
        { 
            if ($comment && canUser('edit'))
                $comment->save(false);
            if (strlen($msg) == 0)
                $comment    = new FieldComment(array(
                                'fc_census'     => $census,
                                'fc_distid'     => $distID,
                                'fc_sdid'       => $subDistID,
                                'fc_div'        => $division,
                                'fc_page'       => $page,
                                'fc_userid'     => $userid));
        }       // update database
        $oldrownum  = $rownum;

        // the first part of the key is the column name
        $fld    = strtolower(substr($key, 0, strlen($key) - 2));
        $comment->set($fld, $value);
    }           // field in individual record
    else
    {           // global field
        switch(strtolower($key))
        {       // act on specific parameters
            case 'cc':
            {
                $cc     = $value;
                break;
            }

            case 'census':
            {
                $census     = $value;
                break;
            }

            case 'province':
            {
                $province   = $value;
                break;
            }

            case 'district':
            {
                $distID     = $value;
                break;
            }

            case 'subdistrict':
            {
                $subDistID  = $value;
                break;
            }

            case 'division':
            {
                $division   = $value;
                break;
            }

            case 'page':
            {
                $page       = $value;
                break;
            }

            case 'userid':
            {
                $userid     = $value;
                break;
            }

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

        }       // act on specific parameters
    }           // global field
}           // loop through all parameters

$country    = new Country(array('cc'    => $cc));
$countryName    = $country->getName($lang);
if (isset($census))
{
    $censusObj  = new Census(array('censusid' => $census));
}
else
{
    $msg    .= "Census parameter not specified. ";
}
if (isset($distID))
{
    $districtObj    = new District(array('census'   => $censusObj,
                                         'id'   => $distID));
    $districtName   = $districtObj->getName($lang);
}
else
{
    $msg    .= "District parameter not specified. ";
}
if (isset($subDistID))
{
    $subDistrictObj     = new SubDistrict(array('census'    => $censusObj,
                                                'distid'    => $districtObj,
                                                'id'        => $subDistID));
    $subDistrictName    = $subDistrictObj->getName();
    $bypage             = $subDistrictObj->get('bypage');
}
else
{
    $msg    .= "SubDistrict parameter not specified. ";
}
if (isset($page))
{
}
else
{
    $msg    .= "Page parameter not specified. ";
}
if (isset($userid))
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

$template       = new FtTemplate("CensusUpdate$lang.html");

$template->set('CENSUSYEAR',        $censusYear);
$template->set('CC',                $cc);
$template->set('COUNTRYNAME',       $countryName);
$template->set('CENSUSID',          $censusId);
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

if (strlen($province) == 0)
{
    $template->updateTag('frontProv', null);
    $template->updateTag('backProv', null);
}
if (strlen($division) == 0)
{
    $template->updateTag('nextPageDivision', null);
    $template->updateTag('prevPageDivision', null);
    $template->updateTag('resultsDivision', null);
}
if ($nextPage == 0)
    $template->updateTag('nextPagePara', null);
if ($prevPage == 0)
    $template->updateTag('prevPagePara', null);
$promptTag  = $template->getElementById('ImagePrompt');
if (strlen($image) == 0)
    $template->updateTag('ImageButton', null); // hide
else
if ($promptTag)
    $promptTag->update(null); // hide

$template->display();

