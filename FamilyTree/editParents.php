<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  editParents.php                                                     *
 *                                                                      *
 *  Display a web page for displaying the parents of a particular       *
 *  individual from the Legacy database                                 *
 *                                                                      *
 *  Parameters (passed by method=get)                                   *
 *      idir            unique numeric key of individual                *
 *      given           given name of individual in case that           *
 *                      information is not already written to the       *
 *                      database                                        *
 *      surname         surname of individual in case that information  *
 *                      is not already written to the database          *
 *      idmr            numeric key of specific marriage to initially   *
 *                      display                                         *
 *      treename        name of tree subdivision of database            *
 *      new             parameter to add a new set of parents           *
 *                                                                      *
 *  History:                                                            *
 *      2010/08/21      Change to use new page format                   *
 *      2010/09/05      Make Close button work                          *
 *      2010/10/21      use RecOwners class to validate access          *
 *      2010/10/23      move connection establishment to common.inc     *
 *      2010/11/27      add parameters given and surname because the    *
 *                      user may have modified the name in the          *
 *                      invoking editIndivid.php web page but not       *
 *                      updated the database record yet.                *
 *      2010/12/04      add link to help panel                          *
 *                      improve separation of HTML and JS               *
 *      2010/12/12      escape title                                    *
 *      2010/12/20      accept parameter idir= as well as id=           *
 *                      handle exception from new LegacyIndiv           *
 *      2011/01/10      use LegacyRecord::getField method               *
 *      2011/03/26      support shortcut keys                           *
 *      2011/08/21      do not initialize given name of father          *
 *                      for new set of parents                          *
 *      2011/11/26      support database assisted location name         *
 *                      add buttons to edit Husband or Wife as individuals*
 *                      support editing married surnames                *
 *      2012/01/13      change class names                              *
 *                      make changes to match editMarriages.php         *
 *      2012/02/25      change ids of fields in marriage list to contain*
 *                      IDMR instead of row number                      *
 *                      use id= keyword on buttons to avoid passing     *
 *                      them to the action script                       *
 *      2012/05/30      specify explicit class on all input fields      *
 *                      identify child's row by IDCR rather than IDIR   *
 *      2012/11/17      initialize $family for display of specific      *
 *                      marriage                                        *
 *                      display family events from event table on       *
 *                      requested marriage.                             *
 *                      change implementation so event type or IDER     *
 *                      value is contained in the name of the button,   *
 *                      not from a hidden field matching the rownum     *
 *      2012/11/27      for consistency the marriage details form is    *
 *                      always filled in dynamically as a result of     *
 *                      receiving the response to an AJAX request,      *
 *                      rather than sometimes filled in by PHP and some *
 *                      times by javascript.                            *
 *                      the location of the sealed to spouse event is   *
 *                      made a selection list to permit updating.       *
 *      2013/01/14      remove reference to obsolete var $enotes        *
 *      2013/02/26      move IDIR fields for parents to hide from mouse *
 *                      help                                            *
 *      2013/03/03      make children's names and dates editable        *
 *      2013/03/25      complete editability of children                *
 *      2013/05/20      add templates for never married and no children *
 *                      facts                                           *
 *      2013/12/07      $msg and $debug initialized by common.inc       *
 *      2014/02/08      standardize appearance of <select>              *
 *      2014/02/24      use dialog to choose from range of locations    *
 *                      instead of inserting <select> into the form     *
 *                      location support moved to locationCommon.js     *
 *                      rename buttons to choose an existing individual *
 *                      as husband or wife to id="choose..."            *
 *                      handle all child rows the same with the fields  *
 *                      uniquely identified by the order value of the   *
 *                      corresponding LegacyChild records               *
 *                      use internal names with Husb/Wife, not          *
 *                      Father/Mother to simplify implementation        *
 *      2014/03/19      use CSS rather than tables to layout form       *
 *      2014/04/26      formUtil.inc obsoleted                          *
 *      2014/06/02      restore value of IDCR for each child            *
 *      2014/07/15      add help balloon for Order Events button        *
 *                      add msgDiv                                      *
 *      2014/07/15      support for popupAlert moved to common code     *
 *      2014/09/27      RecOwners class renamed to RecOwner             *
 *                      use Record method isOwner to check ownership    *
 *      2014/10/02      add prompt to confirm deletion                  *
 *      2014/11/16      initialize display of family without requiring  *
 *                      AJAX                                            *
 *      2014/11/29      print $warn, which may contain debug trace      *
 *      2015/02/01      get temple select options from database         *
 *                      get event texts from class Event and            *
 *                      make them available to Javascript               *
 *      2015/02/19      remove user of deprecated interface to          *
 *                      LegacyFamily constructor                        *
 *                      change remaining debug code to add to $warn     *
 *      2015/02/25      do not access name and birth date of spouses    *
 *                      from the family record                          *
 *      2015/04/28      add warning dialog that a child is already      *
 *                      edited when attempt to edit the child for whom  *
 *                      a set of parents is being created or edited     *
 *      2015/06/20      display error messages                          *
 *                      failed if IDMRParents set in individual to bad  *
 *                      family value                                    *
 *                      document action of enter key in child row       *
 *                      Make the notes field a rich-text editor.        *
 *      2015/07/02      access PHP includes using include_path          *
 *                      For new set of parents surname displayed        *
 *                      incorrectly if child's surname contains quote   *
 *      2015/08/12      add support for tree division of database       *
 *      2016/02/06      use showTrace                                   *
 *      2016/03/14      given name and surname of children were not     *
 *                      escaped for quotes.                             *
 *                      wrong class used for edit and detach buttons    *
 *      2017/01/23      do not use htmlspecchars to build input values  *
 *      2017/03/19      use preferred parameters for new LegacyIndiv    *
 *                      use preferred parameters for new LegacyFamily   *
 *      2017/09/02      class LegacyTemple renamed to class Temple      *
 *      2017/09/12      use get( and set(                               *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2017/11/18      use RecordSet instead of Temple::getTemples     *
 *      2018/11/19      change Helpen.html to Helpen.html               *
 *      2019/07/20      rearrange order of fields to simplify           *
 *                      updateMarriageXml.php                           *
 *      2020/12/14      obsolete, merged into editMarriages.php         *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/
