<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  Person.php															*
 *																		*
 *  Display a web page containing details of an particular individual	*
 *  from the Legacy table of individuals.								*
 *																		*
 *  Parameters (passed by method="get")									*
 *		idir	unique numeric identifier of the individual to display	*
 *				Optional if UserRef is specified						*
 *		UserRef	user assigned identifier of the individual to display.	*
 *				Ignored if idir is specified							*
 *																		*
 * History:																*
 *		2010/08/11		Fix blog code so the option to blog appears		*
 *						if there are no existing blog messages on the	*
 *						individual.  Change to use Ajax to add blog		*
 *						message.										*
 *		2010/08/11		Cleanup parameter handling code to avoid		*
 *						PHP warnings if parameter omitted.				*
 *		2010/08/25		fix undefined $deaths							*
 *		2010/10/21		improve separation of HTML and PHP				*
 *						use RecOwners class to validate access			*
 *						use BlogList and Blog classes to access blogs	*
 *		2010/10/23		move connection establishment to common.inc		*
 *		2010/10/30		use verb suggested by marriage status 			*
 *		2010/11/14		include name suffix and prefix in page title	*
 *		2010/11/29		correct text when child has a mother, but no	*
 *						father.											*
 *		2010/12/10		Move all HTML text output to this file to		*
 *						clearly separate HTML and PHP and permit		*
 *						translating the pages into other languages.		*
 *						Various minor improvements to text.				*
 *		2010/12/11		Use easily customized template strings for event*
 *						text.											*
 *		2010/12/18		The link to the nominal index in the header and	*
 *						trailer breadcrumbs points at current name		*
 *		2010/12/20		Handle exception thrown from new LegacyIndiv	*
 *						Handle exception thrown from new LegacyLocation	*
 *		2010/12/25		Improve separation of PHP, HTML, and Javascript	*
 *						in the blogging section							*
 *		2010/12/28		Add support for location 'kind' on LDS			*
 *						sacraments										*
 *		2010/12/29		Add 'button' to invoke descendants report		*
 *						Add 'button' to invoke ancestor report			*
 *						Move Edit URL and make a 'button'.				*
 *		2011/01/01		Break long text notes into paragraphs whereever	*
 *						two new-lines occur in the original				*
 *		2011/01/02		add reporting of LDS individual events			*
 *		2011/01/03		add alternate name list							*
 *		2011/01/07		correct case where there is a mother and no		*
 *						father known									*
 *						Report on alternate names for the individual.	*
 *		2011/01/10		use LegacyRecord::getField method				*
 *		2011/01/16		add 'button' to invoke relationship calculator	*
 *		2011/01/28		pronoun missing from christening event			*
 *		2011/03/10		change Blog button appearance					*
 *		2011/05/10		display events that only have description text	*
 *		2011/05/23		display any notes on the name of the individual	*
 *		2011/05/28		display pictures								*
 *		2011/08/08		use full month names in marriage events			*
 *						preset pronouns and roles for spouse			*
 *		2011/08/12		add buttons for editing and deleting blog		*
 *						messages										*
 *		2011/10/23		use actual buttons for functions previously		*
 *						implemented by hyperlinks made to look like		*
 *						buttons											*
 *						also add keyboard shortcuts for most buttons	*
 *		2011/11/05		adjust indefinite article based on first letter	*
 *						of event description							*
 *		2011/11/14		correct missing object if no spouse or partner	*
 *						in a family										*
 *		2011/12/30		display prefix and suffix on children's names	*
 *						correctly handle unusual location prepositions	*
 *						eliminate redundant 'of' in parents				*
 *		2012/01/08		move notes and user reference number after		*
 *						events											*
 *		2012/01/13		change class names								*
 *		2012/02/26		easier to understand code for parentage			*
 *		2012/05/28		support unknown sex								*
 *		2012/07/25		display general notes for each spouse			*
 *		2012/08/12		add button to display tree picture of family.	*
 *						display sealing to parents if present.			*
 *		2012/10/08		display user reference field for spouse			*
 *		2012/11/20		include father's title and suffix				*
 *		2012/11/25		catch exceptions allocating spouse and children	*
 *		2012/12/08		only show name of LDS temple in events			*
 *		2013/03/03		LegacyIndiv::getNextName now returns all name	*
 *						index entries									*
 *		2013/03/09		permit use of Address in events					*
 *						honour private flag in LegacyIndiv				*
 *		2013/04/04		enclose all location names in <span> tags to	*
 *						support mouseover popup information				*
 *		2013/04/05		use functions pageTop and pageBot to			*
 *						standardize page appearance						*
 *						only use first 2 chars of given name to access	*
 *						index											*
 *						WYSIWYG editor on blogs							*
 *		2013/04/12		add support for displaying boundary in			*
 *						location map									*
 *		2013/04/20		add illegitimate relationship of child			*
 *		2013/04/21		eliminate space around each name in a hyperlink	*
 *						by using the new LegacyIndiv::getName method	*
 *		2013/04/24		add birth, marriage, and death registrations	*
 *		2013/04/27		display never married indicator					*
 *		2013/05/10		honor invisibility								*
 *		2013/05/14		honor cremated flag								*
 *						properly interpret event IDET 65				*
 *		2013/05/17		add IDIR to email subject line					*
 *		2013/05/29		help popup for rightTop button moved to			*
 *						common.inc										*
 *						include all owners in the contact Author email	*
 *		2013/06/01		remove use of deprecated interfaces				*
 *		2013/06/13		use parameter idir= in invoking web pages		*
 *		2013/07/01		use <a class="button"> instead of				*
 *						<td class="button">								*
 *		2013/08/02		show family and parent information in the 		*
 *						individual popup								*
 *		2013/10/26		display citations for marriage ended date		*
 *		2013/10/27		change text for marriage event IDET 70			*
 *		2013/11/15		handle lack of database server connection		*
 *		2013/11/28		defer loading Google(r) maps API to speed up	*
 *						page display									*
 *		2013/12/07		$msg and $debug initialized by common.inc		*
 *		2013/12/19		correct display of child to parent relationships*
 *		2014/01/31		do not use obsolete LegacyIndiv::getChildRecord	*
 *		2014/01/16		show not married indicator before death.		*
 *		2014/03/17		use CSS rather than tables to lay out list		*
 *						of children, and list of footnotes				*
 *						interface to Picture made more intuitive		*
 *						replace deprecated call to LegacyPictureList	*
 *						with call to Picture::getPictures				*
 *		2014/03/25		class BlogList replaced by static method of		*
 *						class Blog										*
 *		2014/04/08		class LegacyAltName renamed to LegacyName		*
 *		2014/04/26		formUtil.inc obsoleted							*
 *		2014/05/16		use Event for all events						*
 *		2014/05/24		support cause of death popover for more than	*
 *						2 individuals									*
 *		2014/06/10		handle change to functionality of				*
 *						LegacyIndiv::getEvents							*
 *		2014/06/18		show final marriage status						*
 *		2014/06/29		always show blog to collect e-mail addresses	*
 *						add id parameter to elements with only name		*
 *		2014/07/11		remove 'Notes:' prefix on marriage notes		*
 *		2014/07/15		support for popupAlert moved to common code		*
 *		2014/07/19		remove 'Note:' prefix from individual events.	*
 *		2014/08/05		add explicit instructions for requestin access	*
 *						to a private individual							*
 *		2014/09/08		strip paragraph tags off event note				*
 *		2014/09/27		RecOwners class renamed to RecOwner				*
 *						use Record method isOwner to check ownership	*
 *		2014/10/03		clean up initialization							*
 *						use Record::displayPictures						*
 *						display pictures associated with birth,			*
 *						christening, death, and burial.					*
 *		2014/10/15		events moved from tblIR and tblMR to tblER		*
 *		2014/11/29		print $warn, which may contain debug trace		*
 *		2014/12/03		handle exception on bad mother idir				*
 *		2014/12/11		LegacyIndiv::getFamilies and ::getParents		*
 *						now return arrays indexed on IDMR				*
 *		2014/12/19		always use LegacyIndiv::getBirthEvent and		*
 *						getDeathEvent, not obsolete fields to get		*
 *						birth and death information.					*
 *		2015/01/11		add support for Ancestry Search					*
 *		2015/01/12		hide support for Ancestry Search as it makes	*
 *						more sense to move that to editIndivid.php		*
 *		2015/01/23		add accessKey attribute to buttons				*
 *		2015/02/04		add e-mail address to blog postings by visitors	*
 *						misspelled sub for givenname in indiv popu		*
 *		2015/02/21		correct reflexive pronoun in ethnicity phrase	*
 *		2015/03/30		provide more explicit instructions for			*
 *						accessing private individuals if user is 		*
 *						not signed on.									*
 *						use LegacyIndiv::getName to format child name	*
 *		2015/04/06		use LegacyIndiv::getName to format title		*
 *						use LegacyIndiv::getBPrivLim and ::getDPrivLim	*
 *						to obtain event privacy limits					*
 *		2015/05/01		missing space after closing period if only		*
 *						the father defined.								*
 *						source popup laid out here instead of built		*
 *						at runtime from template						*
 *						individ popup laid out here instead of built	*
 *						at runtime from template						*
 *						emit comma between footnote ref for name not	*
 *						and footnote refs for name citations			*
 *						functionality for laying out events moved here	*
 *						from class LegacyIndiv							*
 *		2015/05/14		add button to request permission to update		*
 *						if the user is logged on but not an owner		*
 *		2015/05/25		handle URL redirected from old static site		*
 *						move formatting of source citations here from	*
 *						class Citation									*
 *						standardize <h1>								*
 *		2015/05/29		add individuals from event descriptions			*
 *						into popups										*
 *						ensure that links to individuals are absolute	*
 *		2015/06/11		links inserted by tinyMCE use double-quote		*
 *		2015/06/22		make notes label for spouse highlighted like	*
 *						the notes label of the primary individual		*
 *		2015/07/02		access PHP includes using include_path			*
 *		2015/07/06		add a button in the location popup to edit		*
 *						the location description						*
 *		2015/07/16		notes omitted from location popup				*
 *						handle bad birth/death IDLR						*
 *						bad URL in header with surname containing quote	*
 *		2015/07/22		information on parents moved ahead of birth		*
 *						event so all events are handled alike			*
 *						reference to NameNote in tblIR is handled		*
 *						as an ordinary citation							*
 *		2015/07/27		place alternate name information in line after	*
 *						names of parents and before first event			*
 *						move the display of name and parents out of		*
 *						function showEvents for clarity					*
 *		2015/08/08		clean up text for reporting individual as		*
 *						marital status single							*
 *		2015/08/09		if the individual has a parents record, but		*
 *						there are not parents identified by that record	*
 *						indicate no parents and check for siblings.		*
 *		2015/08/11		support treename								*
 *		2015/08/26		suppress article in front of titles of nobility	*
 *						as an occupation								*
 *		2015/09/02		page number was not inserted into match for		*
 *						Wesleyan Methodist Baptisms						*
 *		2015/11/23		exception from unexpected URL					*
 *		2016/01/19		add id to debug trace							*
 *						display notes with style notes					*
 *						insert class="notes" into <p> with no class		*
 *		2016/02/06		use showTrace									*
 *						for some county marriage references provide		*
 *						link to see the transcription					*
 *		2016/03/24		display events with IDET=1						*
 *		2016/11/25		display aka note for alternate name.			*
 *		2016/12/09		determine geocoder search parm for each location*
 *		2016/12/30		undefined $unknownChildRole						*
 *						catch invalid IDIR in showEvent					*
 *		2017/01/03		handle null status value						*
 *		2017/01/23		do not use htmlspecchars to build input values	*
 *		2017/03/19		use preferred parameters for new LegacyIndiv	*
 *		2017/06/03		privatize spouse events according to birth 		*
 *						date of spouse									*
 *						privatise childhood related individual events	*
 *						using birth privacy limit, not death limit		*
 *						prompt the visitor to request access if there	*
 *						are any private items							*
 *		2017/07/07		support popups for links to individuals in		*
 *						notes field.  Support multiple links to			*
 *						individuals both in notes field and in event	*
 *						description.									*
 *		2017/07/23		class LegacyPicture renamed to class Picture	*
 *		2017/07/27		class LegacyCitation renamed to class Citation	*
 *		2017/07/30		class LegacySource renamed to class Source		*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2017/08/16		renamed to Person.php							*
 *		2017/08/18		class LegacyName renamed to Name				*
 *		2017/09/02		class LegacyTemple renamed to Temple			*
 *		2017/09/09		change class LegacyLocation to class Location	*
 *		2017/09/12		use get( and set(								*
 *		2017/09/28		change class LegacyEvent to class Event			*
 *		2017/10/05		change class LegacyFamily to class Family		*
 *						$idir not set if userref parameter used			*
 *						validate parameter values, somebody tried an	*
 *						insertion which generated an exception that		*
 *						would be inexplicable to most users				*
 *		2017/10/13		class LegacyIndiv renamed to class Person		*
 *		2017/11/19		use CitationSet in place of getCitations		*
 *		2017/12/18		no field idarevent in Event						*
 *		2018/01/27		use Template									*
 *		2018/02/10		implement most of internationalization			*
 *		2018/02/16		internationalize "sealed to"					*
 *						create person popups for marriage notes			*
 *		2018/09/15		urlencode subject in contact author				*
 *		2018/12/03      Citation::toHTML changed to return text         *
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once __NAMESPACE__ . '/Address.inc';
require_once __NAMESPACE__ . '/Blog.inc';
require_once __NAMESPACE__ . '/Citation.inc';
require_once __NAMESPACE__ . '/CitationSet.inc';
require_once __NAMESPACE__ . '/County.inc';
require_once __NAMESPACE__ . '/CountySet.inc';
require_once __NAMESPACE__ . '/Language.inc';
require_once __NAMESPACE__ . '/Person.inc';
require_once __NAMESPACE__ . '/Picture.inc';
require_once __NAMESPACE__ . '/Source.inc';
require_once __NAMESPACE__ . '/Template.inc';
require_once __NAMESPACE__ . '/User.inc';
require_once 'customization.php';
require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *																		*
 *  key phrases that need to be translated to support other languages	*
 *																		*
 ***********************************************************************/

/************************************************************************
 *  dateTemplate														*
 *																		*
 *  Template governing the appearance of dates.							*
 ***********************************************************************/

static	$dateTemplate	= '[dd] [Month] [yyyy][BC]';

/************************************************************************
// gender pronouns for the current language								*
 ***********************************************************************/
$malePronoun		= 'He';
$femalePronoun		= 'She';
$otherPronoun		= 'He/She';
$maleChildRole		= 'son';
$femaleChildRole	= 'daughter';
$unknownChildRole	= 'child';
$deathcause		    = array();

/************************************************************************
 *  $childRole															*
 *																		*
 *  Interpret the gender of a child as a role within the family.		*
 *  This table must be translated to match the language of the page.	*
 ***********************************************************************/
static $childRole	= array(
								'son',
								'daughter',
								'child');

/************************************************************************
 *	$locPrepTrans														*
 *																		*
 * Translate location preposition.										*
 * 'at' is the usual preposition for geographically 'small' locations	*
 * such as a town, while 'in' is used for more extensive locations such	*
 * as a county, state, or country.  In French a different preposition is*
 * used for countries that start with a consonant and are grammatically	*
 * 'masculine'.															*
 ***********************************************************************/
static $locPrepTrans	= array(
							'at'		=> 'at',
							'for'		=> 'for',
							'in'		=> 'in',
							'in[masc]'	=> 'in',
							'to'		=> 'to',
							);

/************************************************************************
 *  $intStatus															*
 *																		*
 *  Interpret the child status field.									*
 *  This table must be translated to match the language of the page.	*
 ***********************************************************************/
static $intStatus	= array(
							 1 =>	'',
							 2 =>	'None',
							 3 =>	'Stillborn',
							 4 =>	'Twin',
							 5 =>	'Illegitimate'
								);

/************************************************************************
 *  $intType                                                            *
 *																		*
 *  Interpretation of values of Child to Parent Relationships.			*
 *  This table must be translated to match the language of the page.	*
 ************************************************************************/

static $intType	= array(
							'' => 'unexpected null value',
							 1 => '',
							 2 => 'adopted',
							 3 => 'biological',
							 4 => 'challenged',
							 5 => 'disproved',
							 6 => 'foster',
							 7 => 'guardianship',
							 8 => 'sealing',
							 9 => 'step',
							10 => 'unknown',
							11 => 'private',
							12 => 'family member',
							13 => 'illegitimate');

/************************************************************************
 *  $eventText															*
 *																		*
 *  This table provides a translation from an event type to the text	*
 *  to display to the user.												*
 *  This table must be translated to match the language of the page.	*
 ***********************************************************************/
static $eventText	= array(
		1 => '[Pronoun] [Description] [onDate] [Location].[Citations] [Notes]',
		2 => '[Pronoun] was adopted [onDate] [Location] by [Description].[Citations] [Notes]',
		3 => '[Pronoun] was born [Description] [onDate] [Location].[Citations] [Notes]',
		4 => '[Pronoun] was buried [Description] [onDate] [Location].[Citations] [Notes]',
		5 => '[Pronoun] was christened [Description] [onDate] [Location].[Citations] [Notes]',
		6 => '[Pronoun] died [onDate] [Description] [Location].[Citations] [Notes]',
		7 => '[Pronoun] had a marriage annulled [Description], [onDate] [Location].[Citations] [Notes]',
		8 => '[Pronoun] was baptized [Description] [onDate] [Location].[Citations] [Notes]',
		9 => 'He celebrated his bar mitzvah [Description] [onDate] [Location].[Citations] [Notes]',
		10 => 'She celebrated her bas mitzvah [Description] [onDate] [Location].[Citations] [Notes]',
		11 => '[Pronoun] was blessed [Description] [onDate] [Location].[Citations] [Notes]',
		12 => '[Pronoun] appeared on the [Description] census [onDate] [Location].[Citations] [Notes]',
		13 => '[Pronoun] was circumcised [Description] [onDate] [Location].[Citations] [Notes]',
		14 => '[Pronoun] became a [Description] citizen [onDate] [Location].[Citations] [Notes]',
		15 => '[Pronoun] was confirmed [Description] [onDate] [Location].[Citations] [Notes]',
		16 => '[Pronoun] was confirmed in The Church of Jesus Christ of Latter-day Saints [Description] [onDate] [Location].[Citations] [Notes]',
		17 => '[Pronoun] was involved in a court case about [Description] [onDate] [Location].[Citations] [Notes]',
		18 => '[Pronoun] was cremated [Description] [onDate] [Location].[Citations] [Notes]',
		19 => '[Pronoun] received a degree of [Description] [onDate] [Location].[Citations] [Notes]',
		20 => '[Pronoun] were divorced [Description] [onDate] [Location].[Citations] [Notes]',
		21 => '[Pronoun] filed for divorce [Description] [onDate] [Location].[Citations] [Notes]',
		22 => '[Pronoun] was educated at [Description] [onDate] [Location].[Citations] [Notes]',
		23 => '[Pronoun] emigrated [Description] [onDate] [fromLocation].[Citations] [Notes]',
		24 => '[Pronoun] was employed as a [Description] [onDate] [Location].[Citations] [Notes]',
		25 => '[Pronoun] were engaged [Description] [onDate] [Location].[Citations] [Notes]',
		26 => '[Pronoun] received First Holy Communion [Description] [onDate] [Location].[Citations] [Notes]',
		27 => '[Pronoun] graduated from [Description] [onDate] [Location].[Citations] [Notes]',
		28 => '[Pronoun] enjoyed [Description] [onDate] [Location].[Citations] [Notes]',
		29 => '[Pronoun] was honoured for [Description] [onDate] [Location].[Citations] [Notes]',
		30 => '[Pronoun] was in the hospital for [Description] [onDate] [Location].[Citations] [Notes]',
		31 => '[Pronoun] was ill with [Description] [onDate] [Location].[Citations] [Notes]',
		32 => '[Pronoun] immigrated [Description] [onDate] [toLocation].[Citations] [Notes]',
		33 => '[Pronoun] was interviewed [Description] [onDate] [Location].[Citations] [Notes]',
		34 => '[Pronoun] [Description] land [onDate] [Location].[Citations] [Notes]',
		35 => '[Pronoun] had marriage banns published [Description] [onDate] [Location].[Citations] [Notes]',
		36 => '[Pronoun] signed a marriage contract [Description] [onDate] [Location].[Citations] [Notes]',
		37 => '[HusbFirstName] and [WifeFirstName] obtained a marriage license [Description] [onDate] [Location].[Citations] [Notes]',
		38 => '[Pronoun] published an intent to marry [Description] [onDate] [Location].[Citations] [Notes]',
		39 => '[Pronoun] obtained a marriage settlement [Description] [onDate] [Location].[Citations] [Notes]',
		40 => '[Pronoun] received medical attention for [Description] [onDate] [Location].[Citations] [Notes]',
		41 => '[Pronoun] was a member of [Description] [onDate] [Location].[Citations] [Notes]',
		42 => '[Pronoun] served in the military [Description] [onDate] [Location].[Citations] [Notes]',
		43 => '[Pronoun] served a mission [Description] [onDate] [Location].[Citations] [Notes]',
		44 => '[Pronoun] was named after [Description] [onDate] [Location].[Citations] [Notes]',
		45 => '[Pronoun] was naturalized [Description] [onDate] [Location].[Citations] [Notes]',
		46 => '[Pronoun] obituary was published in the [Description] [onDate] [Location].[Citations] [Notes]',
		47 => '[Pronoun] worked as a [Description] [onDate] [Location].[Citations] [Notes]',
		48 => '[Pronoun] had the LDS ordinance [Description] [onDate] [Location].[Citations] [Notes]',
		49 => '[Pronoun] was ordained a [Description] [onDate] [Location].[Citations] [Notes]',
		50 => '[Pronoun] was described as [Description] [onDate] [Location].[Citations] [Notes]',
		51 => '[Pronoun] had an estate probated [Description] [onDate] [Location].[Citations] [Notes]',
		52 => '[Pronoun] [Description] property [onDate] [Location].[Citations] [Notes]',
		53 => '[Pronoun] belonged to the [Description] faith [onDate] [Location].[Citations] [Notes]',
		54 => '[Pronoun] lived [Description] [onDate] [Location].[Citations] [Notes]',
		55 => '[Pronoun] retired [Description] [onDate] [Location].[Citations] [Notes]',
		56 => '[Pronoun] attended school at [Description] [onDate] [Location].[Citations] [Notes]',
		57 => '[Pronoun] was assigned US Social Security Number [Description] [onDate] [Location].[Citations] [Notes]',
		58 => '[Pronoun] signed a will [Description] [onDate] [Location].[Citations] [Notes]',
		59 => '[Pronoun] had a medical condition of [Description], [onDate], [Location].[Citations] [Notes]',
		60 => '[Pronoun] served in the military: [Description], [onDate], [Location].[Citations] [Notes]',
		61 => '[Pronoun] had a photo taken [Description] [onDate] [Location].[Citations] [Notes]',
		62 => '[Pronoun] was assigned US Social Security Number [Description] [onDate] [Location].[Citations] [Notes]',
		63 => '[Pronoun] had another occupation as [Description] [onDate] [Location].[Citations] [Notes]',
		64 => '[Pronoun] was [Description] [onDate] [Location].[Citations] [Notes]',
		65 => '[Pronoun] belongs to family group [Description] [onDate] [Location].[Citations] [Notes]',
		66 => '[Pronoun] identified [her][!him]self as [Description] [onDate] [Location].[Citations] [Notes]',
		67 => '[Pronoun] funeral was held [Description] [onDate] [Location].[Citations] [Notes]',
		68 => '[Pronoun] was elected as [Description] [onDate] [Location].[Citations] [Notes]',
		69 => '[Pronoun] were married [Description] [onDate] and [Location].[Citations] [Notes]',
		70 => 'Marriage event [Description] [onDate] [Location].[Citations] [Notes]',
		71 => '[Her][!His] birth was registered [Description] [onDate] [Location].[Citations] [Notes]',
		72 => '[Her][!His] death was registered [Description] [onDate] [Location].[Citations] [Notes]',
		73 => 'Their marriage was registered [Description] [onDate] [Location].[Citations] [Notes]',
      2000 => '[Pronoun] was born [onDate] [Location].[Citations] [Notes]',
      3000 => '[Pronoun] was christened [onDate] [Location].[Citations] [Notes]',
      4000 => '[Pronoun] died [onDate] [Location].[Citations] [Notes]',
      5000 => '[Pronoun] was buried [onDate] [Location].[Citations] [Notes]',
      5001 => '[Pronoun] was cremated [onDate] [Location].[Citations] [Notes]',
     15000 => '[Pronoun] received LDS Baptism [onDate] [Location].[Citations] [Notes]',
     16000 => '[Pronoun] received LDS Endowment [onDate] [Location].[Citations] [Notes]',
     26000 => '[Pronoun] received LDS Confirmation [onDate] [Location].[Citations] [Notes]',
     27000 => '[Pronoun] received LDS Initiatory [onDate] [Location].[Citations] [Notes]'
		);

/************************************************************************
 *  $statusText															*
 *																		*
 *  This table provides a translation from an marriage status to		*
 *  the text to display to the user for the pre-defined values.			*
 *	This saves issuing an SQL query to obtain the text for these		*
 *	common values.														*
 ************************************************************************/
static $statusText	= array(
		    1 => '',
		    2 => 'Their marriage was annulled.',
		    3 => 'They were in a common law relationship.',
		    4 => 'Their marriage ended in divorce.',
		    5 => '',
		    6 => 'They were in an unspecified relationship.',
		    7 => 'Their marriage ended in a separation.',
		    8 => 'They were unmarried.',
		    9 => 'They were divorced.',
		   10 => 'They were separated.',
		   11 => '',
		   12 => 'They were partners.',
		   13 => 'Their marriage ended with the death of one spouse.',
		   14 => '',
		   15 => 'They were just friends.'
		);

/************************************************************************
 *  $nextFootnote		next footnote number to use						*
 ************************************************************************/
$nextFootnote	= 1;

/************************************************************************
 *  $citTable			table to map footnote number to citation		*
 *																		*
 *		Each entry in this table is:									*
 *		o  an object implementing the method toHtml, such as an			*
 *		   instance of Citation											*
 *		o  an object implementing the method getNotes					*
 *		o  a string														*
 ************************************************************************/
$citTable	= array();

/************************************************************************
 *  $citByVal		table to map displayed unique value to				*
 *					footnote number										*
 ************************************************************************/
$citByVal	= array();

/************************************************************************
 *  $sourceTable	table to map IDSR to instance of Source				*
 ************************************************************************/
$sourceTable	= array();

/************************************************************************
 *  $individTable	table to map IDIR to instance of Person		        *
 ************************************************************************/
$individTable	= array();

/************************************************************************
 *  $locationTable	table to map IDLR to instance of Location   		*
 ************************************************************************/
$locationTable	= array();

/************************************************************************
 *  $templeTable	table to map IDTR to instance of Temple				*
 ************************************************************************/
$templeTable	= array();

/************************************************************************
 *  $addressTable		table to map IDAR to instance of Address		*
 ************************************************************************/
$addressTable	= array();

/************************************************************************
 *  createPopups														*
 *																		*
 *  Create popups for any individuals identified by hyper-links in		*
 *  the supplied text.													*
 *																		*
 *  Parameters:															*
 *		$desc		text to check for hyper-links to individuals		*
 *																		*
 *  Returns:	the supplied text, ensuring that the hyperlinks use		*
 *				absolute URLs.											*
 ************************************************************************/
function createPopups($desc)
{
    global	$warn;
    global	$individTable;
    global	$lang;

    $pieces		= explode('<a ', $desc);
    $first		= true;
    $retval		= '';
    foreach($pieces as $piece)
    {		// description contains a link
		if ($first)
		{
		    $retval	.= $piece;
		    $first	= false;
		    continue;
		}
		$retval		.= "<a ";
		$urlstart	= strpos($piece, "href=");
		// $quote is either single or double quote
		$quote		= substr($piece, $urlstart + 5, 1);
		$urlstart	+= 6;
		$urlend		= strpos($piece, $quote, $urlstart);
		$url		= substr($piece,
								 $urlstart,
								 $urlend - $urlstart);
		$equalpos	= strrpos($url, "idir=");
		if ($equalpos !== false)
		{		// link to an individual
		    $refidir		= substr($url, $equalpos + 5);
		    $refind		= new Person(array('idir' => $refidir));
		    $individTable[$refidir]	= $refind;
		    if (substr($url, 0, $equalpos) == "Person.php?")
						$retval	.= substr($piece, 0, $urlstart) .
							     "/FamilyTree/" .
							     substr($piece, $urlstart);
		    else
						$retval	.= $piece;
		}
		else
		    $retval	.= $piece;
    }		// description contains a link
    return $retval;
}		// function createPopups

/************************************************************************
 *  addFootnote															*
 *																		*
 *  Add a footnote.														*
 *																		*
 *  Parameters:															*
 *		$key			string representation of the citation			*
 *						for uniqueness check							*
 *		$cit			instance of Citation, or a string				*
 *																		*
 *  Returns:															*
 *		assigned footnote number						  				*
 ************************************************************************/
function addFootnote($key,
					 $cit)
{
    global		$nextFootnote;
    global		$citByVal;
    global		$citTable;

    if (array_key_exists($key, $citByVal))
    {		// citation can use existing footnote number
		$footnote		= $citByVal[$key];
    }		// citation can use existing footnote number
    else
    {		// citation needs new footnote number
		$footnote		= $nextFootnote;
		$nextFootnote++;
		$citByVal[$key]		= $footnote;
		$citTable[$footnote]	= $cit;
    }		// citation needs new footnote number
    return $footnote;
}		// addFootnote

/************************************************************************
 *  showCitations														*
 *																		*
 *  Given the description of an individual event identify the			*
 *  source citations for that event, emit the HTML for a superscript	*
 *  footnote reference, and add the footnote to the current 			*
 *  individual's page.													*
 *																		*
 *  Input:																*
 *		$event			instance of Event or citation type in tblSX		*
 *		$idime			record identifier of the event or object which	*
 *						the citation documents							*
 ************************************************************************/
function showCitations($event,
					   $idime	= null,
					   $comma	= '')
{
    global	$debug;
    global	$warn;
    global	$connection;
    global	$template;

    // query the database
    if ($event instanceof Event)
    {		// citation to event
		$citations	    = $event->getCitations();
    }		// citation to event
    else
    {		// citation to non-event information
		$citType	    = $event;
		$citparms	    = array('idime'	    => $idime,
                                'type'	    => $citType,
                                'template'  => $template);
		$citations	    = new CitationSet($citparms);
    }		// citation to non-event information

    foreach($citations as $idsx => $cit)
    {		// loop through all citation records
		// manage the tables of citations
		$footnote	    = addFootnote($cit->getName(false), $cit);
		print "<sup>$comma<a href=\"#fn$footnote\">$footnote</a></sup>";
		$comma	        = ',';
    }		// loop through all event records

}		// showCitations

/************************************************************************
 *  showCitationTable													*
 *																		*
 *  Dump the accumulated list of citation footnotes.					*
 ************************************************************************/
function showCitationTable()
{
    global $debug;
    global $warn;
    global $lang;
    global $template;
    global $citTable;
    global $citByVal;
    global $sourceTable;

    $parmTable	= array();

    foreach($citTable as $key => $cit)
    {			// loop through all citations
		$entry				= array('key'	=> $key);
		// generate HTML for this footnote
		if ($cit instanceof Citation)
		{		// invoke toHtml method
		    $entry['text']		    = $cit->toHTML($lang);
		    $idsr			        = $cit->getIdsr();
		    if ($idsr > 1)
		    {
                $source			    = $cit->getSource();
                $source->setTemplate($template);
				$sourceTable[$idsr]	= $source;
		    }
		}		// invoke toHtml method
		else
		{		// not instance of Citation
		    if (is_object($cit) && method_exists($cit, 'getNotes'))
		    {		// invoke getNotes method
						$entry['text']		= $cit->getNotes();
		    }		// invoke getNotes method
		    else
		    {		// treat as text
						$entry['text']		= $cit;
		    }		// treat as text
		} 		// not instance of Citation
		$parmTable[$key]		= $entry;
    }			// for each
    $template->updateTag('footnote$key', $parmTable);
}		// showCitationTable

/************************************************************************
 *  showEvent															*
 *																		*
 *  Generate the HTML to display information about an event				*
 *  of an individual.													*
 *																		*
 *  Parameters:															*
 *		$pronoun		pronoun appropriate for described individual	*
 *		$gender			gender of individual being described			*
 *		$event			instance of Event containing the event			*
 *						information										*
 *		$template		string template to fill in						*
 *						This template has substitution points for:		*
 *						[Pronoun]										*
 *						[onDate]										*
 *						[Location]										*
 *						[Description]									*
 *						[Temple]										*
 *						[Notes]											*
 *						[Citations]										*
 *						Anything else inside square brackets is displayed*
 *						only if the gender of the individual is female,	*
 *						except if it starts with an exclamation mark (!)*
 *						in which case the remaining text is included	*
 *						only if the individual is male.					*
 ***********************************************************************/
function showEvent($pronoun,
				   $gender,
				   $event,
				   $template)
{
    global	$tranTab;
    global	$individTable;
    global	$somePrivate;
    global	$debug;
    global	$warn;

    // determine privacy limits from the associated individual
    // previously the privacy limits of the primary individual were used
    // which meant a younger spouse could have private information revealed
    $person	= $event->getPerson();
    $bprivlim	= $person->getBPrivLim();
    $dprivlim	= $person->getDPrivLim();
    if ($debug)
		$warn	.= "<p>Person::showEvent: event=" .
						   $event .
						   ", indiv=" .
						   $person . ', ' .
						   "bprivlim=$bprivlim, " .
						   "dprivlim=$dprivlim, " .
						   ".</p>\n";

    // extract information on event
    // $dateo is an instance of LegacyDate
    // $date is the text expression of the date
    try {
    $dateo		= new LegacyDate($event->get('eventd'));
    } catch(Exception $e) {
		print "<p>" . $e->getMessage() . ": \$event=" .
						print_r($event, true) . "</p>\n";
    }
    $citType		= $event->getCitType();
    $idet		= $event->get('idet');
    if ($citType == Citation::STYPE_BIRTH ||
		$citType == Citation::STYPE_CHRISTEN ||
		$citType == Citation::STYPE_LDSB ||
		($citType == Citation::STYPE_EVENT &&
		 ($idet == Event::ET_BIRTH ||
		  $idet == Event::ET_CHRISTENING ||
		  $idet == Event::ET_LDS_BAPTISM ||
		  $idet == Event::ET_BARMITZVAH ||
		  $idet == Event::ET_BASMITZVAH ||
		  $idet == Event::ET_BLESSING ||
		  $idet == Event::ET_CIRCUMCISION ||
		  $idet == Event::ET_CONFIRMATION ||
		  $idet == Event::ET_LDS_CONFIRMATION ||
		  $idet == Event::ET_FIRST_COMMUNION
		 )
		)
       )		// childhood events
		$date		= $dateo->toString($bprivlim, true, $tranTab);
    else		// adult events
		$date		= $dateo->toString($dprivlim, true, $tranTab);

    if ($debug)
    {
		$warn	.= "<p>showEvent('$pronoun',$gender,event,'$template')</p>\n" .
						   "<p>citType=$citType, IDET=$idet, date=\"$date\"</p>\n";
    }

    // the first letter of the date text string is folded to lower case
    // so it can be in middle of sentence
    if (strlen($date) >= 1 && substr($date, 0, 1) != 'Q')
		$date		= strtolower(substr($date, 0, 1)) . substr($date, 1);

    // resolve the location
    $idlr		= $event->get('idlrevent');
    $kind		= $event->get('kind');	// may be IDTR or IDLR
    if ($idlr > 0)
    {		// Location or Temple used
		if ($kind)
		    $loc	= new Temple(array('idtr' => $idlr));
		else
		    $loc	= new Location(array('idlr' => $idlr));
    }		// Location or Temple used
    else
		$loc		= null;

    $idar		= $event->get('idar');
    if (is_null($idar))
		$idar		= 0;

    if ($idar > 1)
    {		// Address used
		$loc	= new Address(array('idar' => $idar));
    }		// Address used

    // check for description text
    $desc	= $event->get('description');
    if (strlen($date) > 0 ||
		$idlr > 1 ||
		$idar > 1 ||
		strlen($desc) > 0)
    {		// there is a non-empty event of this kind
		// split the template so that each piece, except the first
		// starts with the name of a substitution token
		$pieces		    = explode('[', $template);
		$split		    = false;
		$pendingWord	= '';
		foreach($pieces as $piece)
		{	// loop through template
		    if ($split)
		    {	// split substitution name from text
				$elts	    = explode(']', $piece, 2);
				$subname    = $elts[0];
				$text	    = $elts[1];
				switch($subname)
				{			// act upon name of substitution
				    case 'Pronoun':
				    {			// pronoun he/she
				    	print $pronoun;
				    	break;
				    }			// pronoun he/she

				    case 'onDate':
				    {			// on <date>
				    	print $pendingWord . ' ';
				    	print $date;
				    	if (strtolower($date) == 'private')
				    	    $somePrivate	= true;
				    	break;
				    }			// on <date>

				    case 'Location':
				    {			// display location text here
			    		print $pendingWord . ' ';
			    		if ($loc)
			    		{		// location resolved
			    		    showLocation($loc);
			    		}		// location resolved
			    		break;
				    }			// display location text here

				    case 'toLocation':
				    {			// display location with 'to' prefix
		    			print $pendingWord . ' ';
		    			if ($loc)
		    			{		// location resolved
		    			    showLocation($loc, '', 'to');
		    			}		// location resolved
		    			break;
				    }			// display location with 'to' prefix

				    case 'Description':
				    {			// display description text here
						$descWords	= explode(' ', $desc, 2);
						$firstWord	= $descWords[0];
						if (count($descWords) > 1)
						    $descRest	= $descWords[1];
						else
						    $descRest	= '';
						if ($firstWord == 'the' ||
						    $firstWord == 'King' ||
						    $firstWord == 'Queen' ||
						    $firstWord == 'Lord' ||
						    $firstWord == 'Lady' ||
						    $firstWord == 'Duke' ||
						    $firstWord == 'Duchess' ||
						    $firstWord == 'Earl' ||
						    $firstWord == 'Count' ||
						    $firstWord == 'Countess' ||
						    $firstWord == 'Marquis' ||
						    $firstWord == 'Marchioness')
						{		// do not emit article
						}		// do not emit article
						else
						if (isFirstVowel($desc))
						{		// description begins with a vowel
						    if ($pendingWord == 'a')
						    	print 'an ';
						    else
						        print $pendingWord . ' ';
						}		// description begins with a vowel
						else
						    print $pendingWord . ' ';
	
						// create popups for any hyper-links in the desc
						print createPopups($desc);
						break;
				    }			// display description text here

				    case 'Temple':
				    {			// display temple name here
				    	print $pendingWord . ' ';
				    	if ($loc)
				    	{
				    	    showLocation($loc);
				    	}
				    	break;
				    }			// display temple name here

				    case 'Notes':
				    {			// display notes here
				    	print $pendingWord . ' ';
				    	$note	= $event->get('desc');
				    	if (strlen($note) > 7 &&
				    	    substr($note, 0, 3) == '<p>' &&
				    	    substr($note, strlen($note) - 4) == '</p>')
				    	    $note	= substr($note, 3, strlen($note) - 7);
				    	if (strlen($note) > 0)
				    	    print str_replace("\r\r", "\n<p>", $note);
				    	break;
				    }			// display notes here

				    case 'Citations':
				    {			// display citation references here
				    	print $pendingWord . ' ';
				    	showCitations($event);
				    	break;
				    }			// display citation references here

				    default:
				    {			// male or female only text
				    	if (substr($subname, 0, 1) == '!')
				    	{	// display text if male
				    	    print $pendingWord . ' ';
				    	    if ($gender == Person::MALE)
				        		print substr($subname, 1);
				    	}	// display text if male
				    	else
				    	{	// display text if female
				    	    print $pendingWord . ' ';
				    	    if ($gender == Person::FEMALE)
				    	    	print $subname;
				    	}	// display text if female
				    	break;
				    }			// male or female only text
				}			// act upon name of substitution

				// print the language specific text
				if (substr($text, strlen($text) - 3) == ' a ')
				{	// last word in text is article "a"
				    print substr($text, 0, strlen($text) - 2);
				    $pendingWord	= 'a';
				}	// last word in text is article "a"
				else
				{	// no special final word
				    print $text;
				    $pendingWord	= '';
				}	// no special final word
		    }	// split substitution name from text
		    else
		    {	// no substitution name, just text
				print $piece;
				$split	= true;
		    }	// no substitution name, just text
		}	// loop through template
    }		// there is an event of this kind
    print "\n";
}		// showEvent

/************************************************************************
 *  showLocation														*
 *																		*
 *  Display a location as part of an event in a standard way.			*
 *																		*
 *		Input:															*
 *		    $location	instance of Location, Temple, or				*
 *						Address											*
 *		    $comma		separator between footnote references			*
 *		    $defPrep	default preposition before place names			*
 ************************************************************************/
function showLocation($location,
					  $comma    = '',
					  $defPrep  = 'at')
{
    global	$locPrepTrans;
    global	$locindex;
    global	$locationTable;
    global	$templeTable;
    global	$addressTable;
    global	$lang;

    if ($location instanceof Location)
    {
		$idlr			= $location->getIdlr();
		$locationTable[$idlr]	= $location;
		$idprefix		= 'showLoc';
    }
    else
    if ($location instanceof Temple)
    {
		$idtr			= $location->getIdtr();
		$templeTable[$idtr]	= $location;
		$idprefix		= 'showTpl';
    }
    else
    if ($location instanceof Address)
    {
		$idar			= $location->getIdar();
		$addressTable[$idar]	= $location;
		$idprefix		= 'showAdr';
    }
    else
		throw new Exception("Person.php: showLocation: ".
						"called with invalid object " . print_r($location, true));

    $locname	= $location->toString();
    if (strlen($locname) > 0)
    {
		$prep	= $location->getPreposition();
		if (strlen($prep) > 0)
		{
		    if (array_key_exists($prep, $locPrepTrans))
				print $locPrepTrans[$prep];
		    else
				print $prep;
		}
		else
		    print $locPrepTrans[$defPrep];
		$idime	= $location->getId();
		print " <span id=\"{$idprefix}{$locindex}_{$idime}\">";
		$locindex++;
		print $locname;
		print '</span>';
    }		// location defined
}		// showLocation

/************************************************************************
 *  showParents															*
 *																		*
 *  Generate the HTML to display information about the parents			*
 *  of an individual.													*
 *																		*
 *  Input:																*
 *		$person		reference to an instance of Person					*
 ************************************************************************/
function showParents($person)
{
    global	$debug;
    global	$warn;
    global	$directory;
    global	$childRole;
    global	$pronoun;
    global	$intType;
    global	$tranTab;
    global	$intStatus;
    global	$individTable;
    global	$lang;

    $idir		= $person->getIdir();

    // show information about parents
    $allParents		= $person->getParents();// RecordSet of family records

    if ($allParents->count() > 0)
    {		// at least one set of parents
		// the role the child plays in the family (son, daughter, or child)
		$role	= $childRole[$person->getGender()];

		foreach($allParents as $idmr => $parents)
		{		// loop through all sets of parents
		    $childRec	= $parents->getChildByIdir($idir);

		    // extract values to display from marriage
		    $dadid	= $parents->get('idirhusb');
		    $momid	= $parents->get('idirwife');
		    $dadrel	= '';
		    $momrel	= '';

		    if ($dadid || $momid)
		    {	// at least one parent defined
				// check for non-zero child status
				$status		= $childRec->getStatus();
				if ($status > 0)
				{
				    if (array_key_exists($status, $intStatus))
			    		$status	= $intStatus[$status];
				    else
				    	$status	= "unknown status index '$status'";
				}
				else
				    $status	= '';

				// determine relationship code for each parent
				if ($dadid)
				{		// father is defined
				    $dad		= new Person(
								array('idir' => $dadid));
				    $individTable[$dadid]	= $dad;
				    $dadrel		= $intType[$childRec->getCPRelDad()];
				    if ($momid == 0)
				    {		// mother not recorded
					$momrel		= $dadrel;
					$endofparents	= ".\n";
				    }		// mother not recorded
				    else
					$endofparents	= '';
				    $gender		= $person->getGenderClass();
				    print $tranTab["was the[$gender]"] . ' ' . 
					  $tranTab[$status] . ' ' .
					  $tranTab[$dadrel] . ' ' .
					  $role . ' ' . $tranTab['of'] . "\n";
?>
    <a href="<?php print $directory; ?>Person.php?idir=<?php print $dadid ?>&amp;lang=<?php print $lang; ?>" class="male"><?php print $dad->getName(); ?></a><?php print $endofparents; ?>
<?php
				}		// father is defined

				if ($dadid && $momid)	// both parents defined
				    print " " . $tranTab['and'] . " ";
				if ($momid > 0)
				{		// mother is defined
				  try {
				    $mom		= new Person(
								array('idir' => $momid));
				    $individTable[$momid]	= $mom;
				    $momrel		= $intType[$childRec->getCPRelMom()];
				    if ($dadid == 0 || $momrel != $dadrel)
				    {		// mother's relationship is different
					print "$momrel $role " . $tranTab['of'] . "\n";
				    }		// mother's relationship is different
?>
<a href="<?php print $directory; ?>Person.php?idir=<?php print $momid; ?>&amp;lang=<?php print $lang; ?>" class="female"><?php print $mom->getName(); ?></a>.
<?php
				  } catch (Exception $e) {
				    print "mother: " . $e->getMessage();
				  }
				}		// mother is defined
		    }	// at least one parent defined
		    else
		    {		// no parents defined in family
				print $tranTab['has no recorded parents'] . ". ";
				$siblings	= $parents->getChildren();
				if (count($siblings) > 1)
				{	// has siblings
				    print $pronoun . ' ' . $tranTab['had'];
				    foreach($siblings as $idcr => $sibChildRec)
				    {	// loop through siblings
					$sibIdir	= $sibChildRec->getIdir();
					if ($sibIdir != $idir)
					{	// not self
					    $sibling	= $sibChildRec->getPerson();
					    $sibGender	= $sibling->getGender();
					    // set the class to color hyperlinks
					    if ($sibGender == Person::MALE)
					    {
						$cgender	= $tranTab['male'];
						$sibRole	= $tranTab['brother'];
						$article	= $tranTab['a[masc]'];
					    }
					    else
					    if ($sibGender == Person::FEMALE)
					    {
						$cgender	= $tranTab['female'];
						$sibRole	= $tranTab['sister'];
						$article	= $tranTab['a[fem]'];
					    }
					    else
					    {
						$cgender	= $tranTab['unknown'];
						$sibRole	= $tranTab['sibling'];
						$article	= $tranTab['a[masc]'];
					    }
					    $sibName	= $sibling->getName();
					    print "$article $sibRole";
?>
				<a href="<?php print $directory; ?>Person.php?idir=<?php print $sibIdir; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $cgender; ?>">
				    <?php print $sibName; ?>
				</a>
<?php
					}	// not self
				    }	// loop through siblings
?>
.
<?php
				}	// has siblings
		    }		// no parents defined in family

		    // check for additional information in child record
		    $seald	= $childRec->get('parseald');
		    $idtrseal	= $childRec->get('idtrparseal');
		    $sealnote	= $childRec->get('parsealnote');

		    if (strlen($seald) > 0 ||
				$idtrseal > 1 ||
				strlen($sealnote) > 0)
		    {		// sealed to parents
				$date	= new LegacyDate($seald);
				$datestr= $date->toString($bprivlim, true, $tranTab);
				if (strtolower($datestr) == 'private')
				    $somePrivate	= true;
				$temple	= new Temple(array('idtr' => $idtrseal));
				print $pronoun . ' ' . $tranTab['was sealed to parents'] . ' ' .
					$datestr . ' ' . $tranTab['at'] .
					$temple->getName() . '. ' . $sealnote;
		    }		// sealed to parents
		}		// loop through parents
    }		// at least one set of parents
    else
    {		// no parents defined
		print $tranTab['has no recorded parents'] . ". ";
    }		// no parents defined

}		// showParents

/************************************************************************
 *  showEvents															*
 *																		*
 *  Given the identifier of an individual, extract information			*
 *  about that individual's Events.										*
 *																		*
 *  Parameters:															*
 *		$person		individual whose events are to be displayed.		*
 ************************************************************************/
function showEvents($person)
{
    global	$debug;
    global	$warn;
    global	$template;
    global	$user;
    global	$eventText;	// table of phrases
    global	$dateTemplate;	// template for displaying dates
    global	$months;
    global	$lmonths;
    global	$tranTab;
    global	$malePronoun;
    global	$femalePronoun;
    global	$otherPronoun;
    global	$private;
    global	$somePrivate;
    global	$lang;

    // initialize fields used in the event descriptions
    $idir	= $person->getIdir();
    $givenName	= $person->getGivenName();
    $surname	= $person->getSurname();
    $gender	= $person->getGender();
    if ($person->getGender() == Person::MALE)
		$pronoun	= $malePronoun;
    else
    if ($person->getGender() == Person::FEMALE)
		$pronoun	= $femalePronoun;
    else
		$pronoun	= $otherPronoun;
    $bprivlim	= $person->getBPrivLim();	// birth privacy limit year
    $dprivlim	= $person->getDPrivLim();	// death privacy limit year


    $oldfmt	= LegacyDate::setTemplate($dateTemplate);

    // display the event table entries for this individual
    $events		= $person->getEvents();
    foreach($events as $ider => $event)
    {			// loop through all event records
		// interpret event type
		$idet	= $event->get('idet');
		if ($idet > 0)
		{		// non-empty event
		    showEvent($pronoun,
				      $gender,
				      $event,
				      $eventText[$idet]);		// template
		}		// non-empty event

		// certain index values are used for events that have
		// additional information to display
		if (is_string($ider))
		{			// special events
		    switch($ider)
		    {			// act on specific special entries
				case 'birth':
				{		// buried event
				    $person->displayPictures(Picture::IDTYPEBirth);
				    break;
				}		// buried event

				case 'christening':
				{		// buried event
				    $person->displayPictures(Picture::IDTYPEChris);
				    break;
				}		// buried event

				case 'death':
				{		// on death event also display cause of death
				    global $deathcause;	// array of death causes
				    $cause		= $person->get('deathcause');

				    if (strlen($cause) > 0)
				    {		// death cause present
					$deathcause[]	= $cause;
					$deathid	= 'DeathCause' . count($deathcause);
					print $tranTab['The cause of death was'];
?>
    <span id="<?php print $deathid; ?>">
				<?php print $cause; ?>
<?php
					showCitations(Citation::STYPE_DEATHCAUSE,
						      $idir);
?>
    </span>.
<?php
				    }	// death cause present

				    // also display any pictures associated with the death event
				    $person->displayPictures(Picture::IDTYPEDeath);
				    break;
				}		// death event

				case 'buried':
				{		// buried event
				    $person->displayPictures(Picture::IDTYPEBuried);
				    break;
				}		// buried event

		    }			// act on specific special entries
		}			// special events
		else
		if (is_int($ider))
		{			// standard events
		    switch($idet)
		    {			// act on specific event types
				case Event::ET_BIRTH:
				{		// buried event
				    $person->displayPictures(Picture::IDTYPEBirth);
				    break;
				}		// buried event

				case Event::ET_CHRISTENING:
				{		// buried event
				    $person->displayPictures(Picture::IDTYPEChris);
				    break;
				}		// buried event

				case Event::ET_DEATH:
				{		// on death event also display cause of death
				    global $deathcause;	// array of death causes
				    $cause		= $person->get('deathcause');

				    if (strlen($cause) > 0)
				    {		// death cause present
					$deathcause[]	= $cause;
					$deathid	= 'DeathCause' . count($deathcause);
					print $tranTab['The cause of death was'];
?>
    <span id="<?php print $deathid; ?>">
				<?php print $cause; ?>
<?php
					showCitations(Citation::STYPE_DEATHCAUSE,
						      $idir);
?>
    </span>.
<?php
				    }	// death cause present

				    // also display any pictures associated with the death event
				    $person->displayPictures(Picture::IDTYPEDeath);
				    break;
				}		// death event

				case Event::ET_BURIAL:
				{		// buried event
				    $person->displayPictures(Picture::IDTYPEBuried);
				    break;
				}		// buried event

		    }			// act on specific event types
		}			// standard events
    }				// loop through all event records

    // check if never married
    $nevermarried	= $person->get('nevermarried');
    if ($nevermarried > 0)
		print $pronoun . ' ' . $tranTab['was never married'] . ". ";

    // reset to former date presentation
    LegacyDate::setTemplate($oldfmt);
}		// showEvents

/********************************************************************
 *		  OOO  PPPP  EEEEE N   N    CCC   OOO  DDDD  EEEEE		    *
 *		 O   O P   P E     NN  N   C   C O   O D   D E				*
 *		 O   O PPPP  EEEE  N N N   C     O   O D   D EEEE		    *
 *		 O   O P     E     N  NN   C   C O   O D   D E				*
 *		  OOO  P     EEEEE N   N    CCC   OOO  DDDD  EEEEE		    *
 ********************************************************************/

// generate unique id values for the <span> enclosing each location
// reference
$locindex		= 1;

// process input parameters
$idir	    	= null;
$person		    = null;
$private		= true;
$somePrivate	= false;
$prefix		    = '';
$givenName		= '';
$surname		= '';
$treeName		= '';
// parameter to nominalIndex.php
$nameuri		= '';
$birthDate		= '';
$deathDate		= '';
$lang		    = 'en';
$getParms		= array();

foreach($_GET as $key => $value)
{				// loop through all parameters
	$value		= trim($value);
	switch(strtolower($key))
	{			// act on specific parameters
	    case 'idir':
	    case 'id':
	    {			// get the individual by identifier
			if (is_int($value) || ctype_digit($value))
			{
			    $idir		= $value;
			    $getParms['idir']	= $idir;
			}
			else
			    $msg	.= "Invalid IDIR=$value. ";
			break;
	    }			// get the individual by identifier

	    case 'userref':
	    {			// get the individual by user reference
			if (preg_match('/^[a-zA-Z0-9_ ]{1,50}$/', $value))
			{
			    $getParms['userref']	= $value;
			}
			else
			    $msg	.= "Invalid UserRef=\"$value\". ";
			break;
	    }			// get the individual by user reference

	    case 'lang':
	    {
			$lang		= strtolower(substr($value,0,2));
			break;
	    }
	}			// act on specific parameters
}				// loop through all parameters

// start the template
$tempBase		= $document_root . '/templates/';
$template		= new FtTemplate("${tempBase}page$lang.html");
$includeSub		= "Person$lang.html";
if (!file_exists($tempBase . $includeSub))
{
	$language	= new Language(array('code' => $lang));
	$langName	= $language->get('name');
    $nativeName	= $language->get('nativename');
    $sorry      = $language->getSorry();
    $warn       .= str_replace(array('$langName','$nativeName'),
                               array($langName, $nativeName),
                               $sorry);
	$nativeName	= $language->get('nativename');
	$includeSub	= "Personen.html";
}
$template->includeSub($tempBase . $includeSub,
				  'MAIN');
if (file_exists($tempBase . "Trantab$lang.html"))
    $trtemplate = new Template("${tempBase}Trantab$lang.html");
else
    $trtemplate = new Template("${tempBase}Trantaben.html");

// internationalization support
$monthsTag	= $trtemplate->getElementById('Months');
if ($monthsTag)
{
	$months		= array();
	foreach($monthsTag->childNodes() as $span)
	    $months[]	= trim($span->innerHTML());
}
$lmonthsTag	= $trtemplate->getElementById('LMonths');
if ($lmonthsTag)
{
	$lmonths		= array();
	foreach($lmonthsTag->childNodes() as $span)
	    $lmonths[]	= trim($span->innerHTML());
}
$tranTabTag	= $template->getElementById('tranTab');
if ($tranTabTag)
{
	$tranTab		    = array();
	foreach($tranTabTag->childNodes() as $span)
	{
	    $key		    = $span->attributes['data-key'];
	    $tranTab[$key]	= trim($span->innerHTML());
	}
	$malePronoun		= $tranTab['He'];
	$femalePronoun		= $tranTab['She'];
	$otherPronoun		= $tranTab['He/She'];
	$maleChildRole		= $tranTab['son'];
	$femaleChildRole	= $tranTab['daughter'];
	$unknownChildRole	= $tranTab['child'];
	$childRole		    = array($maleChildRole,
					        	$femaleChildRole,
					        	$unknownChildRole);
	$locPrepTrans	= array(
				'at'		=> $tranTab['at'],
				'for'		=> $tranTab['for'],
				'in'		=> $tranTab['in'],
				'in[masc]'	=> $tranTab['in[masc]'],
				'to'		=> $tranTab['to']
				);
	$intStatus	= array( 1	=> '',
		        		 2	=> $tranTab['None'],
			        	 3	=> $tranTab['Stillborn'],
			        	 4	=> $tranTab['Twin'],
		        		 5	=> $tranTab['Illegitimate']
					);
	$intType	= array(
				'' => $tranTab['intType'],
				 1 => $tranTab['intType1'],
				 2 => $tranTab['intType2'],
				 3 => $tranTab['intType3'],
				 4 => $tranTab['intType4'],
				 5 => $tranTab['intType5'],
				 6 => $tranTab['intType6'],
				 7 => $tranTab['intType7'],
				 8 => $tranTab['intType8'],
				 9 => $tranTab['intType9'],
				10 => $tranTab['intType10'],
				11 => $tranTab['intType11'],
				12 => $tranTab['intType12'],
				13 => $tranTab['intType13']);
	$eventText	= array(
				 1 => $tranTab['eventText1'], 
				 2 => $tranTab['eventText2'], 
				 3 => $tranTab['eventText3'], 
				 4 => $tranTab['eventText4'], 
				 5 => $tranTab['eventText5'], 
				 6 => $tranTab['eventText6'], 
				 7 => $tranTab['eventText7'], 
				 8 => $tranTab['eventText8'], 
				 9 => $tranTab['eventText9'], 
				10 => $tranTab['eventText10'], 
				11 => $tranTab['eventText11'], 
				12 => $tranTab['eventText12'], 
				13 => $tranTab['eventText13'], 
				14 => $tranTab['eventText14'], 
				15 => $tranTab['eventText15'], 
				16 => $tranTab['eventText16'], 
				17 => $tranTab['eventText17'], 
				18 => $tranTab['eventText18'], 
				19 => $tranTab['eventText19'], 
				20 => $tranTab['eventText20'], 
				21 => $tranTab['eventText21'], 
				22 => $tranTab['eventText22'], 
				23 => $tranTab['eventText23'], 
				24 => $tranTab['eventText24'], 
				25 => $tranTab['eventText25'], 
				26 => $tranTab['eventText26'], 
				27 => $tranTab['eventText27'], 
				28 => $tranTab['eventText28'], 
				29 => $tranTab['eventText29'], 
				30 => $tranTab['eventText30'], 
				31 => $tranTab['eventText31'], 
				32 => $tranTab['eventText32'], 
				33 => $tranTab['eventText33'], 
				34 => $tranTab['eventText34'], 
				35 => $tranTab['eventText35'], 
				36 => $tranTab['eventText36'], 
				37 => $tranTab['eventText37'], 
				38 => $tranTab['eventText38'], 
				39 => $tranTab['eventText39'], 
				40 => $tranTab['eventText40'], 
				41 => $tranTab['eventText41'], 
				42 => $tranTab['eventText42'], 
				43 => $tranTab['eventText43'], 
				44 => $tranTab['eventText44'], 
				45 => $tranTab['eventText45'], 
				46 => $tranTab['eventText46'], 
				47 => $tranTab['eventText47'], 
				48 => $tranTab['eventText48'], 
				49 => $tranTab['eventText49'], 
				50 => $tranTab['eventText50'], 
				51 => $tranTab['eventText51'], 
				52 => $tranTab['eventText52'], 
				53 => $tranTab['eventText53'], 
				54 => $tranTab['eventText54'], 
				55 => $tranTab['eventText55'], 
				56 => $tranTab['eventText56'], 
				57 => $tranTab['eventText57'], 
				58 => $tranTab['eventText58'], 
				59 => $tranTab['eventText59'], 
				60 => $tranTab['eventText60'], 
				61 => $tranTab['eventText61'], 
				62 => $tranTab['eventText62'], 
				63 => $tranTab['eventText63'], 
				64 => $tranTab['eventText64'], 
				65 => $tranTab['eventText65'], 
				66 => $tranTab['eventText66'], 
				67 => $tranTab['eventText67'], 
				68 => $tranTab['eventText68'], 
				69 => $tranTab['eventText69'], 
				70 => $tranTab['eventText70'], 
				71 => $tranTab['eventText71'], 
				72 => $tranTab['eventText72'], 
				73 => $tranTab['eventText73'], 
		      2000 => $tranTab['eventText2000'], 
		      3000 => $tranTab['eventText3000'], 
		      4000 => $tranTab['eventText4000'], 
		      5000 => $tranTab['eventText5000'], 
		      5001 => $tranTab['eventText5001'], 
		     15000 => $tranTab['eventText15000'], 
		     16000 => $tranTab['eventText16000'], 
		     26000 => $tranTab['eventText26000'], 
		     27000 => $tranTab['eventText27000']);
}

// must have a parameter
if (count($getParms) == 0)
{				// missing identifier
	$msg		.= "Missing mandatory identifier parameter. ";
	$title		= 'Person Not Found';
}				// missing identifier
else
{				// have an identifier
	// obtain the instance of Person based upon the parameters
	    $person	        = new Person($getParms);
	    $idir	        = $person->getIdir();
	    $evBirth	    = $person->getBirthEvent(false);
	    $evDeath	    = $person->getDeathEvent(false);
	    $bprivlim	    = $person->getBPrivLim();
	    $dprivlim	    = $person->getDPrivLim();

	    // check if current user is an owner of the record and therefore
	    // permitted to see private information and edit the record
	    $isOwner	    = $person->isOwner();

	    // get information for constructing title and
	    // breadcrumbs
	    $givenName		= $person->getGivenName();
	    if (strlen($givenName) > 2)
			$givenPre	= substr($givenName, 0, 2);
	    else
			$givenPre	= $givenName;
	    $surname		= $person->getSurname();
	    $nameuri		= rawurlencode($surname . ', ' . $givenPre);
	    if (strlen($surname) == 0)
			$prefix		= '';
	    else
	    if (substr($surname,0,2) == 'Mc')
			$prefix		= 'Mc';
	    else
			$prefix		= substr($surname,0,1);

	    // format dates for title
	    if ($person->get('private') == 2 && !$isOwner)
	    {
			$title		= "Person is Invisible";
			$surname	= "";
			$birthDate	= 'Private';
			$somePrivate	= true;
	    }
	    else
	    {
			$title		= $person->getName($tranTab);
			$bdoff		= strpos($title, '(');
			if ($bdoff === false)
			    $birthDate	= '';
			else
			{
			    $bdoff++;
			    $mdashoff	= strpos($title, '&', $bdoff);
			    $birthDate	= substr($title, $bdoff, $mdashoff - $bdoff);
			    $ddoff	= $mdashoff + 7;
			    $cboff	= strpos($title, ')', $ddoff);
			    $deathDate	= substr($title, $ddoff, $cboff - $ddoff);
			}
	    }

	    // determine if the individual is private
	    if (($birthDate != 'Private' && $person->get('private') == 0) ||
			$isOwner)
			$private	= false;
}				// have an identifier


$template->set('TITLE',		    $title);
$template->set('SURNAME',		$surname);
$template->set('PREFIX',		$prefix);
$template->set('NAMEURI',		$nameuri);
$template->set('TREENAME',		$treeName);
$template->set('CONTACTSUBJECT',	urlencode($_SERVER['REQUEST_URI']));
$template->set('CONTACTTABLE',	'tblIR');
$template->set('CONTACTKEY',	$idir);

// update tags
if (strlen($treeName) > 0)
	$template->updateTag('inTree', array('treeName' => $treeName));
else
	$template->updateTag('inTree', null);
ob_start();

if (!is_null($person))
{		// individual found

	if ($private)
	{
?>
<p class="label">Information on this individual is Private</p>
<?php
	}
	else
	{		// display public data
	    if ($person->getGender() == Person::MALE)
			$pronoun	= $malePronoun;
	    else
	    if ($person->getGender() == Person::FEMALE)
			$pronoun	= $femalePronoun;
	    else
			$pronoun	= $otherPronoun;

	    // if debugging, dump out details of record
	    $person->dump("Person.php: " . __LINE__);

	    // Print the name of the individual before the first event
?>
<p><?php print $person->getName(); ?>
<?php
	    // print citations for the name
	    showCitations(Citation::STYPE_NAME,
				  $person->getIdir());
	    print ' ';		// separate name from following

	    // show information about the parents of this individual
	    // This is always displayed
	    // so the user can trace up the tree to non-private individuals
	    showParents($person);

	    // display any alternate names
	    $altNames	= $person->getNames(1);
	    foreach($altNames as $idnx => $altName)
	    {
			print $pronoun . ' ' . $tranTab['was also known as'] . ' ' .
				$altName->getName() . '. ';
			showCitations(Citation::STYPE_ALTNAME,
				      $idnx);
			$note	= $altName->get('akanote');
			if (strlen($note) > 0)
			    print $note . ' ';
	    }	// loop through alternate names

	    // show information about events
	    showEvents($person);
?>
	</p>
<?php
	    // display the user reference field if present
	    try
	    {		// userref field present in database
			$userref	= $person->get('userref');
			if (strlen($userref) > 0)
			{		// user reference
?>
	<p>User Reference: <?php print $userref; ?>
	</p>
<?php
			}		// userref field present in database
			else
			    $userref	= '';
	    }
	    catch(Exception $e)
	    {
			$userref	= '';
	    }			// getField failed

	    // display any general notes
	    $notes	= $person->get('notes');
	    if (strlen($notes) > 0 && !$somePrivate)
	    {		// notes defined
			$notes	= createPopups($notes);
?>
	<p class="notes"><b>Notes:</b>
<?php
			if (strpos($notes, '<p>') === false)
			    print str_replace("\n\n", "\n<p>", $notes);
			else
			    print str_replace("<p>", "<p class=\"notes\">", $notes);
			showCitations(Citation::STYPE_NOTESGENERAL,
				      $idir);
?>
	</p>
<?php
	    }		// notes defined

	    // show any images/video files for the main individual
	    if (!$private)
			$person->displayPictures(Picture::IDTYPEPerson);

	    // show information about families in which this individual
	    // is a spouse or partner
	    if ($debug)
			$warn	.= "<p>\$person-&gt;getFamilies()</p>\n";
	    $families	= $person->getFamilies();

	    if (count($families) > 0)
	    {		// include families section of page
			$oldfmt	= LegacyDate::setTemplate($dateTemplate);
			if ($person->getGender() == Person::MALE)
			{
			    $pronoun	= $malePronoun;
			    $spousePronoun	= $femalePronoun;
			    $spouseChildRole= $femaleChildRole;
			}		// Male
			else
			if ($person->getGender() == Person::FEMALE)
			{		// Female
			    $pronoun	= $femalePronoun;
			    $spousePronoun	= $malePronoun;
			    $spouseChildRole= $maleChildRole;
			}		// Female
			else
			{		// Unknown
			    $pronoun		= $otherPronoun;
			    $spousePronoun	= $otherPronoun;
			    $spouseChildRole	= $unknownChildRole;
			}		// Female
?>
	<p>
<?php
			$num	= 1;	// counter for children

			foreach($families as $idmr => $family)
			{		// loop through families
			    if ($person->getGender() == Person::FEMALE)
			    {		// female
				$spsSurname	= $family->get('husbsurname');
				$spsGiven	= $family->get('husbgivenname');
				$spsid	= $family->get('idirhusb');
				$spsclass	= 'male';
			    }		// female
			    else
			    {		// male
				$spsSurname	= $family->get('wifesurname');
				$spsGiven	= $family->get('wifegivenname');
				$spsid	= $family->get('idirwife');
				$spsclass	= 'female';
			    }		// male

			    // information about spouse
			    try {
				if ($spsid > 0)
				{
				    $spouse	= new Person(array('idir' => $spsid));
				    $individTable[$spsid]	= $spouse;
				}
			    } catch(Exception $e)
			    {
?>
	<p class="message">Unable to obtain information about spouse.
			<?php print $e->getMessage(); ?>
	</p>
<?php
				$spouse	= null;
				$spsid	= 0;
			    }
			    $mdateo	= new LegacyDate($family->get('mard'));
			    $mdate	= $mdateo->toString($dprivlim,
							    true,
							    $tranTab);

?>
	<p>
<?php
			    print $pronoun . ' ' . $tranTab[$family->getStatusVerb()];
			    // only display a sentence about the marriage
			    // if there is a spouse defined
			    if ($spsid > 0)
			    {		// have a spouse
?>
	    <a href="<?php print $directory; ?>Person.php?idir=<?php print $spsid; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $spsclass; ?>"><?php print $spouse->getName(); ?></a>
<?php
			    }		// have a spouse
			    else
			    {		// do not have a spouse
				    print " " . $tranTab['an unknown person'];
			    }		// do not have a spouse

			    if (strlen($mdate) > 0)
			    {
				    if (ctype_digit(substr($mdate,0,1)))
				        print ' ' . $tranTab['on'] . ' ';
				    print ' ' . $mdate;
			    }

			    // location of marriage
			    $idlrmar	= $family->get('idlrmar');
			    if ($idlrmar > 1)
			    {		// have location of marriage
				print ' ';	// separate from preceding date
				$marloc	= new Location(array('idlr' => $idlrmar));
				showLocation($marloc, $person);
			    }		// have location of marriage
			    print ".\n";

			    // show citations for this marriage
			    showCitations(Citation::STYPE_MAR,
					  $family->getIdmr());

			    // display the final marriage status
			    $idms		= $family->get('idms');
			    if (array_key_exists($idms, $statusText))
			    {		// marriage status text defined
				$marStatus		= $statusText[$idms];
				if (strlen($marStatus) > 0)
				    print $marStatus . "\n";
			    }		// marriage status text defined

			    // show marriage notes
			    $mnotes	= $family->get('notes');
			    if (strlen($mnotes) > 0)
			    {		// notes defined for this family
				$mnotes		= createPopups($mnotes);
				print str_replace("\n\n", "\n<p>", $mnotes) .
					    "\n";
			    }		// notes defined for this family

			    if ($spsid > 0)
			    {		// have a spouse
				// never married indicator
				if ($family->get('notmarried') > 0)
				{		// never married indicator
				    print "This couple were never married. ";
				}		// never married indicator

				// LDS sealing
				if (strlen($family->get('seald')) > 0)
				{		// LDS sealing present
				    $date	= new LegacyDate($family->get('seald'));
				    $temple	= new Temple(
					    array('idtr' => $family->get('idtrseal')));
				    $locn	= $temple->getName();
				    print $person->getName() . ' ' .
					$tranTab['was sealed to'] . ' ' .
					$spouse->getName() . ' ' .
					$date->toString($dprivlim, true, $tranTab).' '.
					$tranTab['at'] . ' ' .  $locn . '.';
				}		// LDS sealing present

				// display the event table entries for this family
				$events	= $family->getEvents();
				foreach($events as $ie => $event)
				{		// loop through all event records
				    // display event
				    $idet	= $event->getIdet();
				    showEvent('They',		// pronoun for family
					      0,		// not relevant
					      $event,		// record with details
					      $eventText[$idet]);// template
				}		// loop through all event records

							//****************************************************
							//  marriage ended event						     *
							//												     *
							//  The Legacy database contains a marriage end date *
				//  which
							//  is presumably intended to record the unofficial  *
							//  termination of the relationship where there is no*
							//  formal event, such as						     *
							//  Divorce (ET_DIVORCE), or Annulment (ET_ANNULMENT).*
							//  Note that tblER does not define a formal separation*
							//  event although that could be user implemented as *
							//  ET_MARRIAGE_FACT with description "Separation".  *
							//  The Legacy database also does not define a citation*
							//  type in tblSX to be able to document		     *
							//  the information source for knowledge of the end of*
							//  the marriage.								     *
							//  To permit this event to be handled in a manner   *
							//  consistent with all other events, a new citation *
							//  type is defined.							     *
							//												     *
							//****************************************************
				if (strlen($family->get('marendd')) > 0)
				{		// marriage ended date present
				    $date	= new LegacyDate($family->get('marendd'));
?>
	    The marriage ended
<?php
				print $date->toString(9999, true, $tranTab) . '.';
				// show citations for this marriage
				showCitations(Citation::STYPE_MAREND,
					      $family->getIdmr());
			    }		// marriage ended date present
?>
	</p>
<?php
			    // show any images/video files for the family
			    if (!$private)
				$family->displayPictures(Picture::IDTYPEMar);

			    // Print the name of the spouse before the first event
?>
<p><?php print $spouse->getName(); ?>
<?php
			    // print citations for the name
			    showCitations(Citation::STYPE_NAME,
					  $spouse->getIdir());
			    print ' ';		// separate name from following

			    // show information about the parents of the spouse
			    showParents($spouse);

			    // display any alternate names
			    $altNames	= $spouse->getNames(1);
			    foreach($altNames as $idnx => $altName)
			    {	// loop through alternate names
				print $spousePronoun . ' ' .$tranTab['was also known as'] . ' ' .
					$altName->getName() . '.';
				showCitations(Citation::STYPE_ALTNAME,
					      $idnx);
				$note	= $altName->get('akanote');
				if (strlen($note) > 0)
				    print $note;
			    }	// loop through alternate names

			    // show events in the life of the spouse
			    showEvents($spouse);
?>
	</p>
<?php
			    // display the user reference field if present
			    try
			    {		// userref field present in database
				$userref	= $spouse->get('userref');
				if (strlen($userref) > 0)
				{		// user reference
?>
	<p>User Reference: <?php print $userref; ?>
	</p>
<?php
				}		// userref field present in database
				else
				    $userref	= '';
			    }
			    catch(Exception $e)
			    {
				$userref	= '';
			    }			// getField failed

			    // display any general notes for the spouse
			    $notes	= $spouse->get('notes');
			    if (strlen($notes) > 0 && !$somePrivate)
			    {		// notes defined
?>
	<p class="notes"><b>Notes:</b>
<?php
				print str_replace("\n\n", "\n<p>", $notes);
				showCitations(Citation::STYPE_NOTESGENERAL,
					      $spouse->getIdir());
?>
	</p>
<?php
			    }		// notes defined
			    // show any images/video files for the spouse
			    if (!$private)
				$spouse->displayPictures(Picture::IDTYPEPerson);
			}		// have a spouse
			else
			{		// end sentence
?>
.
<?php
			}		// end sentence

			// display information about children
			$children	= $family->getChildren();
			if (count($children) > 0)
			{	// found at least one child record
?>
	<p class="label">
<?php
			    print $tranTab['Children of'] . ' ' . $person->getName();
			    if ($spsid)
			    {
				print " " . $tranTab['and'] . " " . $spouse->getName();
			    }
			    print ":";
?>
	</p>
<?php
try {
	    foreach($children as $idcr => $child)
	    {		// loop through all child records
?>
	  <div class="row">
	    <div class="column1">
			<?php
	    print $num;
	    $num++;
			?>
	    </div>
<?php
	    // display information about child
	    $cid		= $child->get('idir');
	    try {
			$child		= new Person(array('idir' => $cid));
			$individTable[$cid]	= $child;
			$cName	= $child->getName($tranTab);

			// set the class to color hyperlinks
			if ($child->getGender() == Person::MALE)
			    $cgender	= 'male';
			else
			if ($child->getGender() == Person::FEMALE)
			    $cgender	= 'female';
			else
			    $cgender	= 'unknown';
	    } catch (Exception $e)
	    {		// catch on getting child info from database
			$child		= null;
			$cgender	= 'female';	// red for error
			$cName		= "unable to get child information: " .
					  $e->getMessage();
	    }		// catch on getting child info from database
?>
			<a href="<?php print $directory; ?>Person.php?idir=<?php print $cid; ?>&amp;lang=<?php print $lang; ?>" class="<?php print $cgender; ?>">
			    <?php print $cName; ?>
			</a>
	    <div style="clear: both;"></div>
	  </div>
<?php
	    }	// loop through all child records
} catch(Exception $e)
{
    print "<p class=\"message\">failure: " . $e->getMessage();
}
			}	// found at least one child record
	    }		// loop through families
	    LegacyDate::setTemplate($oldfmt);
	}		// at least one marriage

	// give user options if some information is hidden
	if ($somePrivate)
	{
	    if ($userid == '')
	    {		// not logged on
			$template->updateTag('contactOwners', null);
	    }		// not logged on
	    else
	    {		// logged on but not an owner
			$template->updateTag('notloggedon', null);
	    }		// logged on but not an owner
	}		// some data is private
	else
	    $template->updateTag('wishtosee', null);

	// for already logged on users
	if (strlen($userid) == 0)
	{
	    $template->updateTag('reqgrant', null);
	}
	else
	if ($isOwner)
	{
	    $template->updateTag('reqgrant', null);
	}
	else
	{
	    $template->updateTag('edit', null);
	}

	$birthPlace		= '';
	if ($evBirth)
	    $birthPlace		= $evBirth->getLocation()->getName();
	$deathPlace		= '';
	if ($evDeath)
	    $deathPlace		= $evDeath->getLocation()->getName();
	$fatherGivenName	= '';
	$fatherSurname		= '';
	$motherGivenName	= '';
	$motherSurname		= '';
	$parents		= $person->getPreferredParents();
	if ($parents)
	{			// have preferred parents
	    $father		= $parents->getHusband();
	    if ($father)
	    {			// have father
			$fatherGivenName= $father->getGivenName();
			$fatherSurname	= $father->getSurname();
	    }			// have father
	    $mother		= $parents->getWife();
	    if ($mother)
	    {			// have father
			$motherGivenName= $mother->getGivenName();
			$motherSurname	= $mother->getSurname();
	    }			// have father
	}			// have preferred parents
	$template->set('IDIR',			$idir);
	$template->set('GIVENNAME',		$givenName);
	$template->set('SURNAME',		$surname);
	$template->set('TREENAME',		$treeName);
	$template->set('BIRTHDATE',		$birthDate);
	$template->set('BIRTHPLACE',		$birthPlace);
	$template->set('DEATHDATE',		$deathDate);
	$template->set('DEATHPLACE',		$deathPlace);
	$template->set('FATHERGIVENNAME',	$fatherGivenName);
	$template->set('FATHERSURNAME',		$fatherSurname);
	$template->set('MOTHERGIVENNAME',	$motherGivenName);
	$template->set('MOTHERSURNAME',		$motherSurname);

	    // show any blog postings
	    $blogParms	= array('keyvalue'	=> $idir,
			    		'table'		=> 'tblIR');
	    $bloglist	= new RecordSet('Blogs', $blogParms);

	    // display existing blog entries
	    foreach($bloglist as $blid => $blog)
	    {		// loop through all blog entries
			$username		= $blog->getUser();
			if (strlen($username) == 0)
			    $blog->set('username', "**guest**");
			$text	= $blog->getText();
			$blog->set('text', str_replace("\n", "</p>\n<p>", $text));
			if ($username == $userid)
			    $blog->set('showbuttons', $username);
			else
			    $blog->set('showbuttons', '');
	    }		// loop through all blog entries

	    $template->updateTag('blog$blid', $bloglist);
	    if (strlen($userid) > 0)
			$template->updateTag('blogEmailRow', null);

	    // show accumulated citations
	    showCitationTable();
	}		// display public data
}		// individual found

// embed all of the output from the script    
$template->set('BODY', ob_get_clean());

// create popup balloons for each of the individuals referenced on this page
$templateParms	= array();
foreach($individTable as $idir => $individ)
{		// loop through all referenced individuals
	$name	    	= $individ->getName();
	$evBirth	    = $individ->getBirthEvent();
	if ($evBirth)
	{
	    $birthd	    = $evBirth->getDate();
	    $birthloc	= $evBirth->getLocation()->getName();
	    if ($birthloc == '')
	    {
			$birthloc	= array();
			if ($birthd == '')
			    $birthloc	= array();
	    }
	}
	else
	{
	    $birthd     = array();
	    $birthloc	= array();
	}
	$evDeath	    = $individ->getDeathEvent();
	if ($evDeath)
	{
	    $deathd	    = $evDeath->getDate();
	    $deathloc	= $evDeath->getLocation()->getName();
	    if ($deathloc == '')
	    {
			$deathloc	= array();
			if ($deathd == '')
			    $deathloc	= array();
	    }
	}
	else
	{
	    $deathd	= array();
	    $deathloc	= array();
	}
	$families	= $individ->getFamilies();
	$parents	= $individ->getParents();
	$entry	= array('name'			=> $name,
        			'idir'			=> $individ->get('idir'),
        			'birthd'		=> $birthd,
        			'birthloc'		=> $birthloc,
        			'deathd'		=> $deathd,
        			'deathloc'		=> $deathloc,
        			'description'	=> '',
   				    'families'		=> $families,
   				    'parents'		=> $parents);
	$templateParms[$idir]	= $entry;
}		// loop through all referenced individuals

// create popup balloons for each of the people referenced on this page
$template->updateTag('Individ$idir',
		        	 $templateParms);

// create popup balloons for each of the sources referenced on this page
$template->updateTag('Source$idsr',
		        	 $sourceTable);

// create popup balloons for each of the locations referenced on this page
$template->updateTag('showLocDiv$idlr',
		        	 $locationTable);
if (!canUser('edit'))
		$template->updateTag('editLoc$idlr', null);

// create popup balloons for each of the temples referenced on this page
$template->updateTag('showTplDiv$idtr',
		        	 $templeTable);

// create popup balloons for each of the addresss referenced on this page
$template->updateTag('showAdrDiv$idar',
			       	 $addressTable);

ob_start();
include 'DeathCauses.php';
$template->set('DEATHCAUSES', ob_get_clean());
$user	= new User(array('username'	=> $userid));
$template->set('EMAIL', $user->get('email'));

$template->display();
