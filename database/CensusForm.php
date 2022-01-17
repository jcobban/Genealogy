<?php
namespace Genealogy;
use \PDO;
use \Exception;
use \Templating\Template;

/************************************************************************
 *  CensusForm.php                                                      *
 *                                                                      *
 *  Display every field from a page of a census in tabular form         *
 *  so it can be editted.                                               *
 *                                                                      *
 *  History (as CensusForm1881.php):                                    *
 *      2010/09/05      Fix warning on use of 'Province'                *
 *                      Reformat to new page layout.                    *
 *      2010/10/05      correct warning on Division parameter           *
 *      2010/11/18      services from CensusForm.inc expanded           *
 *      2011/01/15      only display next arrow if there is a next page *
 *      2011/02/16      remove extra arrow outside of <a>               *
 *      2011/05/04      use CSS in place of tables for header/trailer   *
 *                      layout                                          *
 *      2011/07/02      validate transcriber                            *
 *                      permit transcriber to update image URL          *
 *      2011/07/04      report any proofreader comments                 *
 *      2011/09/10      improve separation of HTML & PHP                *
 *                      improve separation of HTML & Javascript         *
 *                      enforce maximum field lengths                   *
 *      2011/09/24      use button for "Display Original Census Image"  *
 *                      implement Alt-I and Alt-C keyboard shortcuts    *
 *      2011/10/09      do not capitalize some fields                   *
 *      2011/10/13      support diagnostic messages                     *
 *                      add help divisions for additional fields and    *
 *                      buttons                                         *
 *      2011/10/19      add mouseover for forward and backward links    *
 *      2011/11/18      improve presentation of numeric fields by       *
 *                      aligning the input field to the right in the    *
 *                      cell                                            *
 *                      improve presentation of flag fields by aligning *
 *                      the input field and its value in the            *
 *                      center of the cell                              *
 *      2011/11/29      do not initialize family number if surname is   *
 *                      [....                                           *
 *      2012/04/01      add button for managing IDIR value              *
 *      2012/04/04      add help for IDIR button                        *
 *      2012/04/13      use id= rather than name= on buttons to prevent *
 *                      them being passed to the action scripts         *
 *                      add help for treeMatch button                   *
 *                      use templates to support i18n                   *
 *                      move common popup divisions to an include file  *
 *      2012/04/27      extend $rowClass array to support lines         *
 *                      squeezed in by the enumerator                   *
 *      2012/07/30      add button to clear IDIR association for a line *
 *      2012/09/28      expand remarks field to 255 characters          *
 *      2013/04/08      suppress family tree button for blank lines     *
 *      2013/05/17      shrink vertical button size by using            *
 *                      class='button'                                  *
 *      2013/06/21      expand maximum size of surname and givenname to *
 *                      match the family tree limits                    *
 *      2013/07/01      setting row class fails for line number out of  *
 *                      normal range                                    *
 *      2013/07/03      correct capitalization of variable names        *
 *      2013/10/19      share code for running through rows of table    *
 *      2013/11/29      let common.inc set initial value of $debug      *
 *      2014/04/24      always show "Clear" button if supported         *
 *      2014/05/22      reduce width of some columns                    *
 *  History:                                                            *
 *      2018/01/11      use class Template                              *
 *      2018/02/09      support multilingual popups by iso code         *
 *      2018/02/23      ensure censusId of individual colony used       *
 *                      for pre-confederation censuses                  *
 *                      ensure RecordSet compares full values of        *
 *                      parameters for FieldComments                    *
 *      2018/02/27      make addition of extra information to           *
 *                      CensusLineSet for page more efficient           *
 *                      ignore bad province parm for post-confederation *
 *      2018/10/16      lang parameter was ignored                      *
 *                      get language apology text from Languages        *
 *                      address performance problem                     *
 *      2019/02/19      use new FtTemplate constructor                  *
 *      2019/04/06      use new FtTemplate::includeSub                  *
 *      2019/12/01      improve parameter checking                      *
 *      2020/03/24      correct setting of owner/tenant in 1921         *
 *      2020/03/29      avoid warning when user not authorized          *
 *      2020/05/10      hide Find button on blank lines                 *
 *      2020/10/10      remove field prefix for Pages table             *
 *      2020/12/01      eliminate XSS vulnerabilities                   *
 *      2021/04/16      handle subdistrict id with colon better         *
 *      2021/04/25      add autofill parameter                          *
 *      2021/07/26      support displaying message if no image for page *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/
require_once __NAMESPACE__ . '/FtTemplate.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Census.inc';
require_once __NAMESPACE__ . '/Domain.inc';
require_once __NAMESPACE__ . '/SubDistrict.inc';
require_once __NAMESPACE__ . '/Page.inc';
require_once __NAMESPACE__ . '/CensusLineSet.inc';
require_once __NAMESPACE__ . '/FieldComment.inc';
require_once __NAMESPACE__ . '/common.inc';

// open code
ob_start();         // ensure no output before redirection

// check for abnormal behavior of Internet Explorer
if ($browser->browser == 'IE' && $browser->majorver < 8)
{       // IE < 8 does not support CSS replacement of cellspacing
    $cellspacing                = "cellspacing='0px'";
}       // IE < 8 does not support CSS replacement of cellspacing
else
{
    $cellspacing                = "";
}

// variables for constructing the main SQL SELECT statement
$cc                             = 'CA';
$countryName                    = 'Canada';
$provinceName                   = '';
$censusId                       = null;
$censusIdText                   = null;
$census                         = null;     // instance of Census
$censusYear                     = 1881;
$province                       = null;
$distID                         = null;
$distIDtext                     = null;
$districtName                   = '';
$subDistrictName                = '';
$subDistID                      = '';
$division                       = '';
$page                           = null;
$pagetext                       = null;
$lang                           = 'en';
$transcriber                    = '';
$proofreader                    = '';
$bypage                         = 1;
$npPrev                         = '';
$npNext                         = '';
$fcSet                          = null;
$censusRec                      = null;
$autofill                       = true;

// get parameter values into local variables
// validate all parameters passed to the server and construct the
// various portions of the SQL SELECT statement
$getParms                       = array('order' => 'line');
if (isset($_GET) && count($_GET) > 0)
{                       // invoked by URL to display current status of account
    $parmsText              = "<p class='label'>\$_GET</p>\n" .
                               "<table class='summary'>\n" .
                                  "<tr><th class='colhead'>key</th>" .
                                    "<th class='colhead'>value</th></tr>\n";
    foreach ($_GET as $key => $value)
    {                   // loop through all parameters
        if (is_array($value))
        {
            if (count($value) == 0)
            {
                $value      = '';
                $safevalue  = '';
            }
            else
            if (count($value) == 1)
            {
                $value      = $value[0];
                $safevalue  = htmlspecialchars($value);
            }
            else
                $safevalue  = htmlspecialchars(var_export($value, true));
        }
        else
        {
            $value          = trim($value);
            $safevalue      = htmlspecialchars($value);
        }
        $parmsText          .= "<tr><th class='detlabel'>$key</th>" .
                                "<td class='white left'>" .
                                $safevalue . "</td></tr>\n";
        if (strlen($value) == 0)
            continue;
        switch(strtolower($key))
        {
            case 'census':
            case 'censusid':
            {           // supported field name
                if (preg_match('/^[a-zA-Z]{2,4}[0-9]{4}$/', $value))
                    $censusId       = strtoupper($value);
                else
                    $censusIdText   = $savevalue;
                break;
            }           // Census Identifier
    
            case 'province':
            {           // Province code (pre-confederation)
                if (preg_match('/^[a-zA-Z]{2}$/', $value))
                    $province       = strtoupper($value);
                else
                    $provinceText   = $safevalue;
                break;
            }           // Province code
    
            case 'district':
            {           // District number
                if (is_array($value))
                {
                    if (count($value) != 1)
                    {
                        $distIDtext = print_r($value,true);
                        break;
                    }
                    $value          = current($value);
                }
                $distpat    = '/^\d+(\.5|\.0)?$/';
                if (preg_match($distpat, $value) == 1)
                {       // valid district number
                    $distID                 = $value;
                    $getParms['district']   = $value;
                }       // valid district number
                else
                    $distIDtext             = htmlspecialchars($value);
                break;
            }           // District number
    
            case 'subdistrict':
            {           // subdistrict id
                $subDistID                      = $safevalue;
                $getParms['subdistrict']        = $subDistID;
                break;
            }           // subdistrict id
    
            case 'division':
            case 'div':
            {           // division
                if (preg_match('/(\d+):(\d+)/', $value, $matches))
                {
                    $distID                     = $matches[1];
                    $subDistID                  = $matches[2];
                    $getParms['district']       = $distID;
                    $getParms['subdistrict']    = $subDistID;
                }
                else
                {
                    $division                   = $safevalue;
                    $getParms['division']       = $division;
                }
                break;
            }           // division
    
            case 'page':
            {           // "Page"
                if (ctype_digit($value))
                {
                    $page                       = $value;
                    $getParms['page']           = $page;
                }
                else
                    $pagetext                   = $safevalue;
                break;
            }           // "Page"
    
            case 'lang':
            {
                    $lang           = FtTemplate::validateLang($value);
            }

            case 'showclear':
            {           // to clear or not to clear
                // obsolete
                break;
            }           // to clear or not to clear

            case 'autofill':
            {
                if (is_string($value))
                {
                    switch(strtolower($value))
                    {
                        case '':
                        case '0':
                        case 'n':
                        case 'no':
                        case 'false':
                            $autofill   = false;
                            break;

                        case '1':
                        case 'y':
                        case 'yes':
                        case 'true':
                            $autofill   = true;
                            break;
                    }
                }
                break;
            }

            case 'debug':
            {           // already handled
                break;
            }           // already handled 
        }               // already handled
    }                   // loop through parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}                       // invoked by URL to display current status of account

// manage transcriber and proofreader status
$transcriber                    = '';
$proofreader                    = '';
$image                          = '';
if (canUser('all'))
{
    if (strlen($transcriber) == 0)
        $transcriber            = $userid;
    $action                     = 'Update';
}
else
if (canUser('edit'))
{
    if (strlen($transcriber) == 0 || $userid == $transcriber)
        $action                 = 'Update';
    else
        $action                 = 'Proofread';
}
else
    $action                     = 'Display';

// get template
$warn           .= ob_get_clean();  // ensure previous output in page
if (is_string($censusId))
{
    $census                 = new Census(array('censusid' => $censusId));
    $censusYear             = $census->get('year');
}
$template       = new FtTemplate("CensusForm$censusYear$action$lang.html");
$translate      = $template->getTranslate();
$t              = $translate['tranTab'];

// validate parameters 
if (is_string($censusId))
{
    $census                 = new Census(array('censusid' => $censusId));
    if ($census->isExisting())
    {
        $censusYear         = $census->get('year');
        if ($censusYear < 1867 && substr($censusId, 0, 2) == 'CA')
        {
            if (is_null($province))
            {
                $censusId   = 'CW' . $censusYear;
                $msg    .= 'Missing mandatory parameter Province. ';
            }
            else
                $censusId   = $province . $censusYear;
            $census         = new Census(array('censusid' => $censusId));
        }
        if (strlen($province) == 0)
            $province       = $census->get('province');
        $getParms['censusid']   = $censusId;
    }
    else
        $msg        .= "Census='$censusId' invalid. ";
}
else
if (is_string($censusIdText))
    $msg            .= "Census='$censusIdText' invalid. ";
else
    $msg    .= 'Missing mandatory parameter Census. ';

if (is_string($province))
{
    $domain             = new Domain(array('code' => "CA$province"));
    $provinceName       = $domain->getName();
}

if (is_string($distIDtext))
    $msg    .= "Invalid value of District='$distIDtext'. ";
else
if (is_string($distID))
{
    $district   = new District(array('census'   => $census,
                                     'id'       => $distID));
    if ($lang == 'fr')
        $districtName   = $district->get('nom');
    else
        $districtName   = $district->get('name');
}
else
{       // missing mandatory parameter
    $msg    .= 'Missing mandatory parameter District. ';
}       // missing mandatory parameter

if (!is_string($subDistID) || strlen($subDistID) == 0)
{       // missing mandatory parameter
    $msg    .= 'Missing mandatory parameter SubDistrict. ';
}       // missing mandatory parameter
else
if (strlen($distID) > 0)
{
    if (preg_match('/^([0-9.]+):(.+)$/', $subDistID, $matches))
    {
        if ($matches[1] == $distID)
            $subDistID      = $matches[2];
    }
    // get information about the sub-district
    $parms  = array('sd_census' => $census, 
                    'sd_distid' => $distID, 
                    'sd_id'     => $subDistID,
                    'sd_div'    => $division);

    $subDistrict    = new SubDistrict($parms);
    if (!$subDistrict->isExisting())
        $msg        .= "Invalid identification of sub-district:".
                        " sd_census=" . $censusId .
                        ", sd_distid=" . htmlspecialchars($distID) . 
                        ", sd_id=" . htmlspecialchars($subDistID) . 
                        ", sd_div=" . htmlspecialchars($division);
    $subDistrictName    = $subDistrict->get('sd_name');
    if (strlen($subDistrictName) > 48)
        $subDistrictName    = substr($subDistrictName, 0, 45) . '...';
}

if (is_string($pagetext))
    $msg            .= "Invalid value of Page='$pagetext'. ";
else
if (strlen($page) == 0)
{       // missing mandatory parameter
    $msg            .= 'Missing mandatory parameter Page. ';
}       // missing mandatory parameter

if (strlen($msg) == 0)
{       // no messages, do search
    $page1              = $subDistrict->get('sd_page1');
    $imageBase          = $subDistrict->get('sd_imagebase');
    $relFrame           = $subDistrict->get('sd_relframe');
    $pages              = $subDistrict->get('sd_pages');
    $bypage             = $subDistrict->get('sd_bypage');

    // obtain information about the page
    $pageRec            = new Page($subDistrict, $page);

    $image              = $pageRec->get('image');
    $numLines           = $pageRec->get('population');
    $transcriber        = $pageRec->get('transcriber');
    $proofreader        = $pageRec->get('proofreader');

    if ($page > $page1)
    {           // not the first page in the division
        $npPrev = "?CensusId=$censusId&District=$distID" .
                      "&SubDistrict=$subDistID&Division=$division" .
                      "&Page=" . ($page - $bypage);
    }           // not the first page in the division
    else
        $npPrev = '';       // previous selection
    if ($page < ($page1 + $pages * $bypage))
    {           // not the last page in the division
        $npNext = "?CensusId=$censusId&District=$distID" .
                      "&SubDistrict=$subDistID&Division=$division" .
                      "&Page=" . ($page + $bypage);
    }           // not the last page in the division
    else
        $npNext = '';       // next selection

    // get field comments
    // note that RecordSet knows only that the census, subdistrict, and
    // division fields are strings and so must be told to match the
    // whole values
    $fcSet      = new RecordSet('FieldComments',
                                array(  'fc_Census' => "^$censusId$",
                                        'fc_DistId' => $distID,
                                        'fc_SdId'   => "^$subDistID$",
                                        'fc_Div'    => "^$division$", 
                                        'fc_Page'   => $page,
                                        'order'     => "FC_Line, FC_FldName"));

}       // no errors, continue with request
else
{
}

$popups             = "CensusFormPopups$lang.html";
$template->includeSub($popups,
                      'POPUPS');
$template->set('CENSUSYEAR',        $censusYear);
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
$template->set('ROWS',              $count);
$template->set('TRANSCRIBER',       $transcriber);
$template->set('PROOFREADER',       $proofreader);
$template->set('CENSUS',            $censusYear);
$template->set('SEARCH',            '');
$template->set('CONTACTTABLE',      'Census' . $censusYear);
$template->set('CONTACTSUBJECT',    '[FamilyTree]' . $_SERVER['REQUEST_URI']);
$template->set('IMAGE',             $image);

if (strlen($province) == 0)
{
    $tag        = $template->getElementById('frontProv');
    if ($tag)
        $tag->update(null);
    $tag        = $template->getElementById('backProv');
    if ($tag)
        $tag->update(null);
}

if (strlen($division) == 0)
{
    $template['frontDiv']->update(null);
    $template['backDiv']->update(null);
}
$promptTag              = $template->getElementById('ImagePrompt');
$noneTag                = $template->getElementById('NoImage');
if (strlen($image) == 0)
    $template['ImageButton']->update(null); // hide
else
{
    if ($promptTag)
        $promptTag->update(null); // hide
    if ($noneTag)
        $noneTag->update(null); // hide
}

if (strlen($msg) > 0)
{
    $template['frontPager']->update(null);
    $template['backPager']->update(null);
    $template['censusForm']->update(null);
}
else
{           // no errors
    if (strlen($npPrev) > 0)
    {
        $template['npPrevFront']->update(
                             array('npPrev' => $npPrev));
        $template['npPrevBack']->update(
                             array('npPrev' => $npPrev));
    }
    else
    {
        $template['npPrevFront']->update(null);
        $template['npPrevBack']->update(null);
    }
    if (strlen($npNext) > 0)
    {
        $template['npNextFront']->update(
                             array('npNext' => $npNext));
        $template['npNextBack']->update(
                            array('npNext' => $npNext));
    }
    else
    {
        $template['npNextFront']->update(null);
        $template['npNextBack']->update(null);
    }
}           // no errors

// update the popup for explaining the action taken by arrows
if (strlen($msg) == 0)
{
    if (!preg_match('/^https?:/', $image))
        $template['imageButton']->update(null);

    if ($numLines > 0)
    {
        $template->set('NUMLINES', $numLines);
        $pageSizeP                  = $template['pageSize'];
        if ($pageSizeP instanceof \Templating\TemplateTag)
            $pageSizeP->update(null);
        $rowElt                     = $template->getElementById('Row$line');
        $rowHtml                    = $rowElt->outerHTML();
        $data                       = '';
        $getParms['autofill']       = $autofill;
        $lineSet                    = new CensusLineSet($getParms);
        $info                       = $lineSet->getInformation();
        $count                      = $info['count'];
        $groupLines                 = $census->get('grouplines');
        $lastunderline              = $census->get('lastunderline');
        $oldFamily                  = '';
        $oldSurname                 = '';
        $oldReligion                = '';
        $oldOrigin                  = '';
        $oldNationality             = '';
        foreach($lineSet as $censusLine)
        {
            $rtemplate                  = new Template($rowHtml);
            $doIdirHidden               = false;
            foreach($censusLine as $field => $value)
            {
                switch($field)
                {       // act on specific field names
                    case 'line':
                    {
                        $line           = $value;
                        if (($line % $groupLines) == 0 &&
                             $line < $lastunderline)
                            $censusLine->set('cellclass', 'underline');
                        else
                            $censusLine->set('cellclass', 'cell');
                        if ($line < 10)
                            $line       = '0' . $line;
                        break;
                    }       // line number on page
    
                    case 'family':
                    {
                        if ($value == $oldFamily)
                            $censusLine->set('famclass', 'same');
                        else
                        {
                            $censusLine->set('famclass', 'black');
                            $oldFamily      = $value;
                        }
                        if (strlen($value) == 0)
                        {
                            $button         = $rtemplate['doIdir$line'];
                            $doIdirHidden   = true;
                            if ($button)
                                $button->update(null);
                        }
                        break;
                    }       // family number
    
                    case 'surname':
                    {
                        if ($value == $oldSurname)
                            $censusLine->set('surclass', 'same');
                        else
                        {
                            $censusLine->set('surclass', 'black');
                            $oldSurname     = $value;
                        }
                        if ($doIdirHidden == false && 
                            strtolower($value) == '[blank]')
                        {
                            $button         = $rtemplate['doIdir$line'];
                            $doIdirHidden   = true;
                            if ($button)
                                $button->update(null);
                        }
                        break;
                    }       // surname
    
                    case 'origin':
                    {
                        if ($value == $oldOrigin)
                            $censusLine->set('orgclass', 'same');
                        else
                        {
                            $censusLine->set('orgclass', 'black');
                            $oldOrigin  = $value;
                        }
                        break;
                    }       // ethnic origin
    
                    case 'nationality':
                    {
                        if ($value == $oldNationality)
                            $censusLine->set('natclass', 'same');
                        else
                        {
                            $censusLine->set('natclass', 'black');
                            $oldNationality = $value;
                        }
                        break;
                    }       // nationality
    
                    case 'religion':
                    {
                        if ($value == $oldReligion)
                            $censusLine->set('relclass', 'same');
                        else
                        {
                            $censusLine->set('relclass', 'black');
                            $oldReligion    = $value;
                        }
                        break;
                    }       // religion
    
                    // default value of Mother's birthplace is
                    // Father's birthplace
                    // so if they are equal Mother's birthplace is default value
                    case 'mothersbplace';
                    {
                        if ($value == $censusLine['fathersbplace'])
                            $censusLine->set('mbpclass', 'same');
                        else
                            $censusLine->set('mbpclass', 'black');
                        break;
                    }       // mothersbirthplace
    
                    case 'idir':
                    {
                        if (is_null($value))
                        {
                            $value      = 0;
                            $censusLine->set('idir',        0);
                        }
                        if ($value > 0)
                        {
                            $censusLine->set('idirtext',    'Show');
                            $censusLine->set('idirclear',   $line);
                        }
                        else
                        {
                            $censusLine->set('idirtext',    'Find');
                            $button         = $rtemplate['clearIdir$idirclear'];
                            if ($button)
                                $button->update(null);
                        }
                        break;
                    }       // idir
    
                    case 'ownertenant':
                    {
                        if (is_string($value) && strlen($value) > 1)
                            $value              = substr($value, 0, 1);
                        if (is_null($value))
                            $censusLine->set($field, '');
                        else
                            $censusLine->set($field, strtoupper($value));
                        break;
                    }
    
                    default:
                    {
                        if (is_null($value))
                            $censusLine->set($field, '');
                        break;
                    }
                }       // act on specific field names
            }           // loop through all fields in record
            $rtemplate['Row$line']->update($censusLine);
            $data           .= $rtemplate->compile() . "\n";
        }           // loop through records in page
        $rowElt->update($data);
    }                   // the page is not empty
    else
    {
        $template['form']->update(null);            // remove whole table
        $template['treeMatch']->update(null);       // remove button
        $template['showImportant']->update(null);   // remove button
        $template['reset']->update(null);           // remove button
        $template['addRow']->update(null);          // remove button
        $template->set('NUMLINES', $t['No']);
    }
}

// display field comments
if ($fcSet && $fcSet->count() > 0)
{
    $template['comment$index']->update($fcSet);
}
else
{
    $template['comments']->update(null);
}

set_time_limit(90);
$template->display();
