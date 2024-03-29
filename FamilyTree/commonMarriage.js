/************************************************************************
 *  commonMarriage.js                                                   *
 *                                                                      *
 *  Javascript code to implement dynamic functionality shared between   *
 *  the pages editMarriages.php and editParents.php.                    *
 *                                                                      *
 *  History:                                                            *
 *      2012/01/13      split off from editMarriage.js and              *
 *                      editParents.js                                  *
 *                      change class names                              *
 *                      change orderChildren.php to orderChildrenXml.php*
 *                      change updateMarriage.php to                    *
 *                      updateMarriageXml.php                           *
 *                      add eventFeedback method to encapsulate updates *
 *                      to this form as a result of user actions in the *
 *                      editEvent form.                                 *
 *                      all buttons use id= rather than name= to avoid a*
 *                      problem with IE passing their values            *
 *                      support updating all fields of Family           *
 *                      function record                                 *
 *      2012/02/25      change ids of fields in marriage list to contain*
 *                      IDMR instead of row number                      *
 *                      support mouseover for edit and                  *
 *                      detach child buttons                            *
 *      2012/05/29      identify child row by IDCR instead of IDIR to   *
 *                      handle the same child appearing more than once  *
 *      2012/09/19      invoke editIndivid.php with the IDCR value      *
 *                      rather than the IDMR                            *
 *      2012/10/07      only enable edit spouse buttons if IDIR not     *
 *                      equal zero                                      *
 *                      define constants for event types                *
 *      2012/10/16      fix bugs handling feedback                      *
 *      2012/11/20      change implementation of passing parameters     *
 *                      to the script deleteEventXml.php                *
 *                      completely lay out marriage using javascript to *
 *                      eliminate duplication of functionality with PHP *
 *                      always display a marriage                       *
 *      2013/01/20      use encodeURI on surname passed to              *
 *                      chooseIndivid.php                               *
 *      2013/03/04      handle additional field names that support      *
 *                      changing names and dates of children            *
 *      2013/03/21      sort children by date within the display        *
 *                      without updating the database until the         *
 *                      marriage is saved                               *
 *      2013/03/23      child management buttons now just change        *
 *                      appearance and database is updated only when    *
 *                      marriage is updated                             *
 *      2013/05/02      if the wife or mother in a family does not have *
 *                      a surname, the given name is qualified with the *
 *                      husband's name                                  *
 *      2013/05/09      bug in updating marriage ended date             *
 *      2013/05/20      add support for never married fact and          *
 *                      no children fact                                *
 *                      adding row if necessary to display the value,   *
 *                      and applying new value from eventFeedback       *
 *      2013/05/29      disable edit spouse button for individual for   *
 *                      which the marriage was invoked, since that      *
 *                      individual's edit dialog should still be open.  *
 *      2013/07/31      chooseIndivid.php now passes birthsd value      *
 *                      for child                                       *
 *      2013/08/02      restore button functions on changed child       *
 *      2013/09/23      notify opener if marriage deleted and           *
 *                      no marriages left                               *
 *      2013/10/15      if the user modified the name of a child before *
 *                      clicking on the "Edit Child" button, pass the   *
 *                      new name                                        *
 *      2013/10/25      also pass changes to the birth and death date   *
 *                      when editing an existing child                  *
 *      2013/12/12      display birth sort dates of spouses             *
 *      2014/01/01      clean up comment blocks                         *
 *                      remove unused function validateMarrForm         *
 *                      remove unused function resetMarrForm            *
 *                      merge function addChildRow into addChildToPage  *
 *                      which is the only function that called it       *
 *      2014/02/04      format of XML response to get family changed    *
 *                      function addChild renamed to addExistingChild   *
 *                      to clarify its purpose                          *
 *      2014/02/25      The feedback method for editIndivid to update   *
 *                      a child is made a method of the row containing  *
 *                      the child, rather than the table of children.   *
 *                      This makes it consistent with how feedback      *
 *                      for the parents is handled.                     *
 *                      Each child row, and each input field in the     *
 *                      child row, is now identified by the position    *
 *                      of the child as displayed on the page.  This    *
 *                      is to permit all child rows to be identified    *
 *                      in the same way regardless of whether the row   *
 *                      is backed by an instance of Child and/or        *
 *                      an instance of Person.  This eliminates         *
 *                      the need for there to be separate templates     *
 *                      depending upon whether the Child record         *
 *                      has been created, and allows for simplified     *
 *                      addition of children.                           *
 *                      Remove dependencies upon layout using tables    *
 *                      Consolidate support for feedback from           *
 *                      editIndivid.php by using the same style of      *
 *                      feedback routine for any individual in the      *
 *                      function family                                 *
 *                      validate dates, expand locations                *
 *                      fix bug in sorting children after add existing  *
 *                      simplify addition of children                   *
 *                      use same internal field names for both          *
 *                      editMarriages.php and editParents.php           *
 *      2014/03/14      set gender on name change of existing children  *
 *                      not just those added by the user                *
 *      2014/06/02      pass idcr to editIndivid for a child            *
 *      2014/06/16      do not update the family and close the dialog   *
 *                      if there is an edit child window open           *
 *                      because this caused 2 copies of the child to    *
 *                      be defined, one from the edit child window      *
 *                      and one from the default family update action   *
 *      2014/07/15      only prevent saving marriage once for open      *
 *                      child edit windows, and use popupAlert          *
 *      2014/07/16      better support for checking for open child      *
 *                      function windows                                *
 *      2014/07/19      if not opened as a dialog go back to previous   *
 *                      page instead of closing the window              *
 *      2014/09/12      remove use of obsolete selectOptByValue         *
 *      2014/09/27      deleteMarriage.php script renamed to            *
 *                      deleteMarriageXml.php                           *
 *      2014/10/02      prompt for confirmation before deleting an      *
 *                      event.                                          *
 *      2014/10/10      child windows set to position on top of current *
 *                      if husband's name changed                       *
 *                      then change married names as well               *
 *      2014/10/16      popup a loading indicator while waiting for     *
 *                      family record to be retrieved from the server   *
 *      2014/11/15      missing function to reorder events              *
 *      2014/11/16      rename function marrAdd to addFamily            *
 *                      rename function marrEdit to editFamily          *
 *                      refresh to switch to new family rather than AJAX*
 *                      support clicking on details for event when      *
 *                      family has not been saved to database yet       *
 *      2014/11/28      if the user updates the date of birth of a      *
 *                      child, including adding a date of birth for     *
 *                      a new child, the sort version of the date       *
 *                      is set so Order Children by Birth Date works    * 
 *      2014/12/22      script renamed to orderMarriagesByDateXml.php   *
 *      2015/02/01      get event type text from web page               *
 *      2015/02/02      disable and enable edit buttons when editing    *
 *                      family member in <iframe>                       *
 *      2015/02/10      open all child windows in left hand frame       *
 *      2015/02/19      support extended response from addFamilyXml.php *
 *                      comments still referred to orderMarriagesByDate *
 *      2015/02/23      openFrame returns instance of Window            *
 *                      track open windows for spouse or child to       *
 *                      prevent updating family while open              *
 *                      identify open windows by title                  *
 *      2015/02/28      call checkfunc for change birth date            *
 *      2015/03/02      add alert for bad return from addChildToPage    *
 *      2015/03/06      if IDCR is passed to method changeChild then    *
 *                      update the field in the form.  This permits     *
 *                      the script editIndivid.php to add the child     *
 *                      and present the relationship to parents fields  *
 *                      for an added child                              *
 *      2015/03/20      birthsd was not passed to addChildToPage for    *
 *                      addition of new child                           *
 *      2015/03/25      pass debug flag to editIndivid.php              *
 *                      when updating family member                     *
 *      2015/04/17      use closeFrame to close edit dialog when there  *
 *                      are not families left to display                *
 *      2015/04/28      do not permit editing the child for whom a set  *
 *                      of parents is being created or edited           *
 *      2015/05/27      use absolute URLs for AJAX                      *
 *      2015/06/19      add method childKeyDown which handles key       *
 *                      strokes in input fields in a child row          *
 *                      so Enter key moves to first field of next row   *
 *                      or adds another child, and up and down arrow    *
 *                      move the focus up and down a column             *
 *                      make the notes field a rich-text editor         *
 *      2015/08/12      add support for tree divisions of database      *
 *      2015/08/21      fix failure if delete currently displayed       *
 *                      marriage and there is at least one marriage     *
 *                      left, because deleted family still displayed    *
 *      2015/11/08      parameter removed from function marrDel         *
 *      2016/01/27      value of field CGender in a child was not       *
 *                      changed when the sex of the child was changed   *
 *                      by editIndivid.php                              *
 *      2016/05/08      prevent loop creating marriages                 *
 *      2016/05/09      correct output                                  *
 *      2016/05/31      use common function dateChanged                 *
 *      2017/01/09      put "Wifeof" comment into surname field         *
 *      2017/09/08      section not defined when calling                *
 *                      editIndivid::marriageUpdated                    *
 *                      handle addition of first family when calling    *
 *                      editIndivid::marriageUpdated                    *
 *      2018/05/16      update all child IDIR and IDCR fields on        *
 *                      receipt of update of family so that newly       *
 *                      created children are properly represented       *
 *                      Among other things this permits sorting         *
 *                      children by birth date without requiring that   *
 *                      the user manually edit new children first       *
 *      2018/10/30      use Node.textContent rather than getText        *
 *      2019/01/21      get gender for given name from database         *
 *      2019/05/19      call element.click to trigger button click      *
 *      2019/06/29      first parameter of displayDialog removed        *
 *      2019/07/20      insert spaces into child death date             *
 *      2019/11/07      use keyEvent.key instead of keyEvent.code       *
 *      2019/11/11      correctly add "delete" button when no more      *
 *                      families                                        *
 *      2019/11/15      add language parameter to opening editIndivid   *
 *      2020/02/12      exploit Template                                *
 *      2020/03/18      new implementation of adding events             *
 *      2022/04/16      support new layout implementation               *
 *      2022/08/17      correct extraction of information from response *
 *                      from updateMarriageXML.php                      *
 *      2022/08/20      switch to updateMarriageJSON from               *
 *                      updateMarriageXML for server response           *
 *      2023/07/15      function gotNickname enhanced to set gender     *
 *                      to female for given names ending in 'a'         *
 *                                                                      *
 *  Copyright &copy; 2023 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  function constants                                                  *
 *                                                                      *
 ************************************************************************/

const CHILD_PREFIX            = "child";
const CHILD_PREFIX_LEN        = CHILD_PREFIX.length;
const EDIT_CHILD_PREFIX       = "editChild";
const EDIT_CHILD_PREFIX_LEN   = EDIT_CHILD_PREFIX.length;
const DELETE_PREFIX           = "Delete";
const DELETE_PREFIX_LEN       = DELETE_PREFIX.length;
const EDIT_EVENT_PREFIX       = "EditEvent";
const EDIT_EVENT_PREFIX_LEN   = EDIT_EVENT_PREFIX.length;

/************************************************************************
 *  "constants" for event types                                         *
 *  These definitions must match those in the PHP file                  *
 *  includes/LegacyCitition.php                                         *
 ************************************************************************
 *      IDIME points to Marriage Record tblMR.idmr                      *
 ************************************************************************/
const STYPE_LDSS              = 18;   // Sealed to Spouse
const STYPE_NEVERMARRIED      = 19;   // This individual never married 
const STYPE_MAR               = 20;   // Marriage 
const STYPE_MARNOTE           = 21;   // Marriage Note
const STYPE_MARNEVER          = 22;   // Never Married         
const STYPE_MARNOKIDS         = 23;   // This couple had no children
const STYPE_MAREND            = 24;   // Marriage ended
                
/************************************************************************
 *      IDIME points to Event Record tblER.ider                         *
 ************************************************************************/
const STYPE_EVENT             = 30;   // Individual Event
const STYPE_MAREVENT          = 31;   // Marriage Event

/************************************************************************
 *  function childWindows                                               *
 *                                                                      *
 *  This array keeps track of all child windows opened by the current   *
 *  edit family dialog.                                                 *
 ************************************************************************/
var childWindows    = [];

/************************************************************************
 *  function editChildButtons                                           *
 *                                                                      *
 *  This array keeps track of all editChild buttons in the current      *
 *  dialog.                                                             *
 ************************************************************************/
var editChildButtons    = [];

/************************************************************************
 *  function pendingButton                                              *
 *                                                                      *
 *  This is a reference to an instance of <button> that should be       *
 *  "clicked" when the family is updated.                               *
 *  This permits deferring functionality until after the Family record  *
 *  is updated in the database.                                         *
 ************************************************************************/
var pendingButton   = null;

/************************************************************************
 *  function clickPref                                                  *
 *                                                                      *
 *  This method is called when the user clicks on a preferred marriage  *
 *  checkbox to identify a new preferred marriage.                      *
 *                                                                      *
 *  Input:                                                              *
 *      this    <input name='Pref9999' type='checkbox'>                 *
 ************************************************************************/
function clickPref()
{
    if (this.checked)
    {       // the current marriage is preferred
        let idmr        = this.name.substring(4);

        // notify the invoking page that the preferred marriage has changed
        let opener  = null;
        if (window.frameElement && window.frameElement.opener)
            opener  = window.frameElement.opener;
        else
            opener  = window.opener;
        if (opener)
            opener.document.indForm.setIdmrPref(idmr);
    
        let form    = this.form;
        let formElts    = form.elements;
        for (let i = 0; i < formElts.length; ++i)
        {
            let element = formElts[i];

            // uncheck all other checkboxes in the preference set
            if ((element.name.substring(0,4) == 'Pref') && (element != this))
                element.checked     = false;
        }       // loop through all elements
    }       // the current marriage is preferred
    else
    {       // do not permit turning the preference off
        this.checked    = true;
    }       // do not permit turning the preference off
    return true;
}   // function clickPref

/************************************************************************
 *  function editFamily                                                 *
 *                                                                      *
 *  This method is called when the user requests to edit                *
 *  information about a specific marriage.                              *
 *                                                                      *
 *  Input:                                                              *
 *      this    <button id='Edit9999'>                                  *
 ************************************************************************/
function editFamily()
{
    // disable buttons in the main form until update is complete
    let idmr        = this.id.substring(4);
    let href        = location.href;
    if (href.indexOf("?") >= 0)
        location.href   = location.href + "&idmr=" + idmr;
    else
        location.href   = location.href + "?idmr=" + idmr;
}       // function editFamily

/************************************************************************
 *  function gotFamily                                                  *
 *                                                                      *
 *  This method is called when the XML document representing            *
 *  a family is retrieved from the server.                              *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc          Family record as an XML document                *
 ************************************************************************/
function gotFamily(xmlDoc)
{
    let notMarriedChecked   = '';
    let noChildrenChecked   = '';
    let noChildrenDisabled  = false;

    // some actions depend upon the value of the idir parameter passed
    // to the script
    let idir    = 0;
    if (args['idir'])
        idir    = args['idir'] - 0;
    else
    if (args['id'])
        idir    = args['id'] - 0;

    hideLoading();  // hide loading indicator
    // get information from XML document
    if (xmlDoc.documentElement)
    {       // XML document
        let root    = xmlDoc.documentElement;
        if (root.tagName == 'added')
        {       // format with enclosing information
            for (let i = 0; i < root.childNodes.length; i++)
            {       // loop through all children
                let node    = root.childNodes[i];
                if (node.tagName == 'family')
                {   // <family>
                    root    = node;
                    break;
                }   // <family>
            }       // loop through all children
        }       // format with enclosing information
        if (root.tagName == 'tblMR' || root.tagName == 'family')
        {       // correctly formatted response
            let childTable  = document.getElementById('children');
            let eventSet    = document.getElementById('EventSet');
            let famForm     = document.famForm;

            for (let i = 0; i < root.childNodes.length; i++)
            {       // loop through all children
                let node    = root.childNodes[i];
                if (node.nodeType == 1)
                {   // element Node
                    let value   = node.textContent;

                    switch(node.nodeName)
                    {   // take action depending upon tag name
                        case 'idmr':
                        {   
                            famForm.idmr.value  = value;
                            break; 
                        }   // function idmr

                        case 'idirhusb':
                        {
                            famForm.IDIRHusb.value  = value;
                            document.getElementById('editHusb').disabled =
                                (value == 0) || (value == idir);
                            break; 
                        }   // function idmr

                        case 'husbsurname':
                        {
                            famForm.HusbSurname.value   = value;
                            break; 
                        }   // function husbsurname

                        case 'husbgivenname':
                        {
                            famForm.HusbGivenName.value = value;
                            break; 
                        }   // function husbgivenname

                        case 'husbbirthsd':
                        {
                            famForm.HusbBirthSD.value   = value;
                            break; 
                        }   // function husbbirthsd

                        case 'idirwife':
                        {
                            let idirwife    = famForm.IDIRWife.value;
                            if (idirwife > 0 &&
                                idirwife != value)
                                alert('commonMarriage.js: 430: famForm.IDIRWife.value ' + idirwife + ' changed to ' + value);
                            famForm.IDIRWife.value  = value;
                            document.getElementById('editWife').disabled =
                                (value == 0) || (value == idir);
                            break; 
                        }   // function idirwife

                        case 'wifesurname':
                        {
                            famForm.WifeSurname.value   = value;
                            break; 
                        }   // function wifesurname

                        case 'wifegivenname':
                        {
                            famForm.WifeGivenName.value = value;
                            break; 
                        }   // function wifegivenname

                        case 'wifebirthsd':
                        {
                            famForm.WifeBirthSD.value   = value;
                            break; 
                        }   // function wifebirthsd

                        case 'mard':
                        {
                            famForm.MarD1.value      = value;
                            break; 
                        }   // marriage date

                        case 'marloc':
                        {
                            famForm.MarLoc1.value    = value;
                            break; 
                        }   // function marloc

                        case 'marendd':
                        {           // obsolete and removed
                            break; 
                        }   // function marendd

                        case 'seald':
                        {           // obsolete and removed
                            break; 
                        }           // field seald

                        case 'idtrseal':
                        {           // obsolete and removed
                            break; 
                        }           // field idtrseal

                        case 'idms':
                        {
                            famForm.IDMS.value  = value;
                            break; 
                        }   // function idms

                        case 'marriednamerule':
                        {
                            famForm.MarriedNameRule.value   = value;
                            break; 
                        }   // function marriednamnerule

                        case 'notes':
                        {
                            tinyMCE.get('Notes').setContent(value);
                            break; 
                        }   // function notes

                        case 'notmarried':
                        {   // not married indicator
                            if (value > 0)
                                addNotMarriedRow();
                            break; 
                        }   // not married indicator

                        case 'nochildren':
                        {   // no children indicator
                            if (value > 0)
                                addNoChildrenRow();
                            break; 
                        }   // no children indicator

                        case 'children':
                        {
                            let numChildren = node.getAttribute('count');
                            if (numChildren > 0)
                            {       // at least one child
                                noChildrenChecked   = false;
                                noChildrenDisabled  = true;
                            }       // at least one child
                            else
                                noChildrenDisabled  = false;
                            addChildrenFromXml(node,
                                               childTable);
                            break;
                        }   // children tag

                        case 'events':
                        {
                            changeEventListsFromXml(node,
                                             eventSet);
                            break;
                        }   // events tag
                    }   // take action depending upon tag name
                }   // element Node
            }       // loop through all first level children
        }       // correctly formatted response
        else
            popupAlert('commonMarriage.js: gotFamily: ' + new XMLSerializer().serializeToString(root),
                        this);
    }       // XML document
    else
        popupAlert('commonMarriage.js: gotFamily: ' + xmlDoc,
                   this);
}   // function gotFamily

/************************************************************************
 *  function addNotMarriedRow                                           *
 *                                                                      *
 *  Insert a row to display the never married fact just before          *
 *  the row to display the date and location of the marriage.           *
 *  This is called by functions gotMarried and eventFeedback.           *
 ************************************************************************/
function addNotMarriedRow()
{
    let famForm = document.famForm;
    if (famForm.NotMarried)
        return;     // already displayed

    let parms   = {'temp'   : ''};
    let newrow  = createFromTemplate('NotMarriedRow$temp',
                                 parms,
                                 null);
    let nextRow = document.getElementById('Marriage');
    if (nextRow === undefined)
        throw "commonMarriage.js: addNotMarriedRow: no element with id 'Marriage'";
    let tbody   = nextRow.parentNode;
    tbody.insertBefore(newrow, nextRow);

    if (famForm.NotMarried)
        actMouseOverHelp(famForm.NotMarried);
    let button  = document.getElementById('neverMarriedDetails');
    if (button)
    {
        actMouseOverHelp(button);
        button.onclick  = neverMarriedDetails;
    }
}       // function addNotMarriedRow

/************************************************************************
 *  function addNoChildrenRow                                           *
 *                                                                      *
 *  Add a row to display the no children fact at the end of the form.   *
 ************************************************************************/
function addNoChildrenRow()
{
    let famForm = document.famForm;
    if (famForm.NoChildren)
        return;     // already displayed

    let parms   = {'temp'   : ''};
    let newrow  = createFromTemplate('NoChildrenRow$temp',
                                 parms,
                                 null);
    let tbody   = document.getElementById('formBody');
    tbody.appendChild(newrow);

    if (famForm.NoChildren)
        actMouseOverHelp(famForm.NoChildren);
    let button  = document.getElementById('noChildrenDetails');
    if (button)
    {
        actMouseOverHelp(button);
        button.onclick  = noChildrenDetails;
    }
}       // function addNoChildrenRow

/************************************************************************
 *  function addChildrenFromXml                                         *
 *                                                                      *
 *  Input:                                                              *
 *      node            <children> tag from XML                         *
 *      childTable      <table id='children'> from page                 *
 ************************************************************************/
function addChildrenFromXml(node,
                            childTable)
{
    // cleanup existing display
    let tbody                   = document.getElementById('childrenBody');
    let child;
    while((child = tbody.firstChild) != null)
    {       // remove all children
        tbody.removeChild(child);
    }       // remove all children

    // add children from XML database record
    let rownum                  = 1;
    for(child = node.firstChild;
        child;
        child = child.nextSibling)
    {       // loop through child tags in XML
        // extract parameters from XML element
        if (child.nodeType == 1 && child.nodeName == 'child')
        {   // element node
            let parms           = getParmsFromXml(child);
            if (parms.gender == 0 || parms.gender == 'M')
                parms.gender    = 'male';
            else
            if (parms.gender == 1 || parms.gender == 'F')
                parms.gender    = 'female';
            else
                parms.gender    = 'unknown';
            parms.rownum        = parms.order;

            // if the parms parameter is invalid throw an exception
            if (parms.givenname === undefined)
            {
                let msg         = "";
                for(parm in parms) { msg += parm + "='" + parms[parm] + "',"; }
                throw "commonMarriage.js: addChildrenFromXml: parms={" + msg +  
                        "} child=" + new XMLSerializer().serializeToString(child);
            }
    
            childTable.addChildToPage(parms,
                                      false);
        }   // element node
    }       // loop through children
}       // function addChildrenFromXml

/************************************************************************
 *  function changeEventListsFromXml                                    *
 *                                                                      *
 *  Add rows to the display to represent family events from tblER.      *
 *                                                                      *
 *  Input:                                                              *
 *      node            <events> tag from XML                           *
 *      fieldSet        <fieldset id='EventSet'> from page              *
 ************************************************************************/
function changeEventListsFromXml(node,
                          fieldSet)
{
    let form        = document.famForm;

    // cleanup existing display
    let msg             = "";
    for(let member in fieldSet)
        msg             += member + ",";
    let row             = fieldSet.firstChild;
    msg                 = "";
    for(let member in row)
        msg             += member + ",";

    while(row)
    {
        let nextChild   = row.nextSibling;
        if (row.id && row.id.substring(0,8) == 'EventRow')
            tbody.removeChild(row);
        row = nextChild;
    }       // loop through existing rows in table

    // find the position at which to add new event rows
    let nextRow = document.getElementById('EndedRow');
    if (nextRow === undefined || nextRow === null)
        nextRow = document.getElementById('AddEventRow');

    // add events from database record
    for(let child = node.firstChild;
        child;
        child = child.nextSibling)
    {   // loop through children
        // extract parameters from XML element
        if (child.nodeType == 1)
        {   // element node
            let parms   = getParmsFromXml(child);
            let typeText    = 'Unknown ' + idet;
            let eventTextElt    = document.getElementById('EventText' + idet);
            if (eventTextElt)
            {               // have element from web page
                typeText    = eventTextElt.innerHTML.trim() + ':';
                typeText    = typeText.substring(0,1).toUpperCase() +
                                      typeText.substring(1);
            }               // have element from web page
            parms['type']   = typeText;
            let descn   = parms['description'];
            if (descn.length > 0)
            {
                descn   = descn.substring(0,1).toUpperCase() + 
                          descn.substring(1);
                parms['description']    = descn;
            }
             
            let newrow          = createFromTemplate('EventRow$ider',
                                                     parms,
                                                     null);
            fieldSet.insertBefore(newrow, nextRow);

            // add handlers for added buttons
            let ider            = parms['ider'];
            let eltName         = "citType" + ider;
            let element         = form.elements[eltName];
            actMouseOverHelp(element);

            eltName             = "Date" + ider;
            element             = form.elements[eltName];
            element.abbrTbl     = MonthAbbrs;
            element.onchange    = dateChanged;
            element.checkfunc   = checkDate;
            actMouseOverHelp(element);

            eltName             = "EventLoc" + ider;
            element             = form.elements[eltName];
            element.abbrTbl     = evtLocAbbrs;
            element.onchange    = locationChanged;
            actMouseOverHelp(element);

            eltName             = "EditEvent" + ider;
            let button          = document.getElementById(eltName);
            button.onclick      = editEvent;    
            actMouseOverHelp(button);

            eltName             = "DelEvent" + ider;
            button              = document.getElementById(eltName);
            button.onclick      = delEvent;    
            actMouseOverHelp(button);

        }   // element node
    }       // loop through children
}       // function changeEventListsFromXml

/************************************************************************
 *  function noFamily                                                   *
 *                                                                      *
 *  This method is called if there is no family response                *
 *  from the server.                                                    *
 ************************************************************************/
function noFamily()
{
    alert('commonMarriage.js: noFamily: Unable to obtain family record from server');
}       // function noFamily

/************************************************************************
 *  function marrDel                                                    *
 *                                                                      *
 *  This method is called when the user requests to delete              *
 *  information about a specific marriage.                              *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Delete9999'>                            *
 ************************************************************************/
function marrDel()
{
    let idmr        = this.id.substring(DELETE_PREFIX_LEN);
    let parms       = { "idmr"  : idmr};

    let idirElement = document.getElementById('idir');
    if (idirElement)
        parms['idir']   = idirElement.value;
    let childElement    = document.getElementById('child');
    if (childElement)
        parms['child']  = childElement.value;

    // invoke script to update Event and return XML result
    popupLoading(this); // display loading indicator
    HTTP.post('/FamilyTree/deleteMarriageXml.php',
              parms,
              gotDelMarr,
              noDelMarr);
}   // function marrDel

/************************************************************************
 *  function gotDelMarr                                                 *
 *                                                                      *
 *  This method is called when the XML document representing            *
 *  a successful delete marriage is retrieved from the database.        *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc          response as an XML document                     *
 *                      with the following structure:                   *
 *                                                                      *
 *      <deleted>                                                       *
 *        <parms>                                                       *
 *          <idmr> requested IDMR to delete </idmr>                     *
 *        </parms>                                                      *
 *        <cmd> an SQL DELETE command </cmd> ... or                     *
 *        <msg> error message </msg>                                    *
 *      </deleted>                                                      *
 ************************************************************************/
function gotDelMarr(xmlDoc)
{
    hideLoading();              // hide loading indicator
    if (xmlDoc.documentElement)
    {                           // XML document
        let root                    = xmlDoc.documentElement;
        if (root.tagName == "deleted")
        {                       // correctly formatted response
            let msgs                = root.getElementsByTagName("msg");
            if (msgs.length == 0)
            {                   // no errors detected
                let parms           = root.getElementsByTagName("parms");
                let idmrtag         = parms[0].getElementsByTagName("idmr");
                let idmr            = idmrtag[0].textContent.trim();
                let row             = document.getElementById("marriage" + idmr);
                let section         = row.parentNode;
                section.removeChild(row);
                let numFamilies     = section.rows.length;
                if (numFamilies == 0)
                {               // deleted last marriage
                    // notify the opener (editIndivid.php)
                    // that there are no marriages left
                    let opener      = null;
                    if (window.frameElement && window.frameElement.opener)
                        opener      = window.frameElement.opener;
                    else
                        opener      = window.opener;

                    if (opener)
                    {           // opened from another dialog
                        let openerForm  = opener.document.indForm;
                        if (openerForm)
                        {       // opened from editIndivid.php
                            try {
                                openerForm.marriageUpdated(0,
                                                           numFamilies);
                            } catch(e)
                            { 
                                alert("commonMarriage.js: 928 e=" + e); 
                            }
                        }       // opened from editIndivid.php
                        closeFrame();
                    }           // opened from another dialog
                    else
                        window.history.back();
                }               // deleted last marriage
                else
                {               // at least one marriage left
                    let famForm     = document.famForm;
                    let currIdmr    = famForm.idmr.value;
                    if (idmr == currIdmr)
                    {           // deleted currently displayed family
                        let row     = section.rows[0];
                        idmr        = row.id.substring(8);
                        let edit    = document.getElementById('Edit' + idmr);
                        edit.click();
                    }           // deleted currently displayed family
                }               // at least one marriage left
            }                   // no errors detected
            else
            {                   // report message
                alert("commonMarriage.js: gotDelMarr: " + new XMLSerializer().serializeToString(msgs[0]));
            }                   // report message
        }                       // correctly formatted response
        else
            alert("commonMarriage.js: gotDelMarr: " + new XMLSerializer().serializeToString(root));
    }                           // XML document
    else
        alert("commonMarriage.js: gotDelMarr: " + xmlDoc);
}       // function gotDelMarr

/************************************************************************
 *  function noDelMarr                                                  *
 *                                                                      *
 *  This method is called if there is no delete marriage response       *
 *  file.                                                               *
 ************************************************************************/
function noDelMarr()
{
    alert('commonMarriage.js: noDelMarr: ' +
          'No response from server to deleteMarriageXml.php');
}       // function noDelMarr

/************************************************************************
 *  function addFamily                                                  *
 *                                                                      *
 *  This method is called when the user requests to add                 *
 *  a new family to an individual                                       *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Add'>                                   *
 ************************************************************************/
function addFamily()
{
    location.href   = location.href + "&new=Y";
}   // function addFamily

/************************************************************************
 *  function marrReorder                                                *
 *                                                                      *
 *  This method is called when the user requests to reorder             *
 *  marriages by date.                                                  *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='Reorder'>                               *
 ************************************************************************/
function marrReorder()
{
    let form        = document.indForm;
    let idir        = form.idir.value;
    let sex     = form.sex.value;

    let parms       = {
                "idir"      : idir,
                "sex"       : sex};

    // invoke script to update Event and return XML result
    popupLoading(this); // display loading indicator
    HTTP.post('/FamilyTree/orderMarriagesByDateXml.php',
              parms,
              gotReorderMarr,
              noReorderMarr);
}   // function marrReorder

/************************************************************************
 *  function gotReorderMarr                                             *
 *                                                                      *
 *  This method is called when the XML document representing            *
 *  a successful marriage reorder is retrieved from the database.       *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc      response from orderMarriagesByDateXml.php as an     *
 *                  XML document                                        *
 ************************************************************************/
function gotReorderMarr(xmlDoc)
{
    window.location.reload();
}       // function gotReorderMarr

/************************************************************************
 *  function noReorderMarr                                              *
 *                                                                      *
 *  This method is called if there is no reorder marriage response      *
 *  file.                                                               *
 ************************************************************************/
function noReorderMarr()
{
    alert('commonMarriage.js: noReorderMarr: ' +
          'script orderMarriagesByDateXml.php not found on server');
}       // function noReorderMarr

/************************************************************************
 *  function noteDetails                                                *
 *                                                                      *
 *  This method is called when the user requests to edit the            *
 *  details, including citations, for the note event.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='noteDetails'> element                   *
 ************************************************************************/
function noteDetails()
{
    editEventMar(STYPE_MARNOTE, this);
}

/************************************************************************
 *  function noChildrenDetails                                          *
 *                                                                      *
 *  This method is called when the user requests to edit the            *
 *  details, including citations, for the No Children fact.             *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='noChildrenDetails'> element             *
 ************************************************************************/
function noChildrenDetails()
{
    editEventMar(STYPE_MARNOKIDS, this);
}

/************************************************************************
 *  function neverMarriedDetails                                            *
 *                                                                      *
 *  This method is called when the user requests to edit the            *
 *  details, including citations, for the never married fact            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='neverMarriedDetails'> element           *
 ************************************************************************/
function neverMarriedDetails()
{
    editEventMar(STYPE_MARNEVER, this);
}

/************************************************************************
 *  function changeHusb                                                 *
 *                                                                      *
 *  This is a feedback method from a sub dialog to change the displayed *
 *  information about the husband in a family.                          *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            <div id='Husb'>                                 *
 *      parms           object with at least the following members      *
 *      idir            IDIR of husband as individual                   *
 *      givenname       new given name of husband                       *
 *      surname         new surname of husband                          *
 *      birthd          new birth date of husband                       *
 *      deathd          new death date of husband                       *
 ************************************************************************/
function changeHusb(parms)
{
    for (let ib = 0; ib < editChildButtons.length; ib++)
    {           // enable all editChild buttons
        editChildButtons[ib].disabled   = false;
    }           // enable all editChild buttons

    for(let iw = 0; iw < childWindows.length; iw++)
    {
        let cw      = childWindows[iw];
        let cloc    = cw.location;
        if (cloc && cloc.search.indexOf('rowid=Husb') >= 0)
        {
            childWindows.splice(iw, 1);
            break;
        }
        else
            alert("changeHusb: cw=" + cw.constructor.name);
    }

    let form                        = document.famForm;
    if (form.IDIRHusb)
    {
        let idirhusb                = form.IDIRHusb.value;
        if (idirhusb > 0 && idirhusb != parms['idir'])
            alert("attempt to changte IDIRHusb from " +
                    idirhusb + " to " + parms['idir']);
        form.IDIRHusb.value         = parms['idir'];
    }
    if (form.HusbSurname)
    {
        if (form.HusbSurname.value != parms['surname'])
            alert("attempt to changte HusbSurbame from " +
                    form.HusbSurname.value + " to " + parms['surname']);
        form.HusbSurname.value      = parms['surname'];
    }
    if (form.HusbMarrSurname)
        form.HusbMarrSurname.value  = parms['surname'];
    if (form.WifeMarrSurname &&
        form.MarriedNameRule &&
        form.MarriedNameRule.value == '1')
        form.WifeMarrSurname.value  = parms['surname'];
    if (form.HusbGivenName)
        form.HusbGivenName.value    = parms['givenname'];
    if (form.HusbBirth)
        form.HusbBirth.value        = parms['birthd'];
    if (form.HusbDeath)
        form.HusbDeath.value        = parms['deathd'];
    if (form.WifeMarrSurname &&
        form.MarriedNameRule && form.MarriedNameRule.value == '1')
        form.WifeMarrSurname.value  = parms['surname'];
    document.getElementById('editHusb').disabled    = false;
    document.getElementById('chooseHusb').disabled  = false;
    document.getElementById('createHusb').disabled  = false;
}       // function changeHusb

/************************************************************************
 *  function changeWife                                                 *
 *                                                                      *
 *  This is a feedback method from a sub dialog to change the displayed *
 *  information about the wife in a family.                             *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <div id='Wife'>                                     *
 *      parms       object with at least the following members          *
 *      idir        IDIR of wife as individual                          *
 *      givenname   new given name of wife                              *
 *      surname     new surname of wife                                 *
 *      birthd      new birth date of wife                              *
 *      deathd      new death date of wife                              *
 ************************************************************************/
function changeWife(parms)
{
    for (let ib = 0; ib < editChildButtons.length; ib++)
    {           // enable all editChild buttons
        editChildButtons[ib].disabled   = false;
    }           // enable all editChild buttons

    for(let iw = 0; iw < childWindows.length; iw++)
    {
        let cw      = childWindows[iw];
        let cloc    = cw.location;
        if (cloc && cloc.search.indexOf('rowid=Wife') >= 0)
        {
            childWindows.splice(iw, 1);
            break;
        }
        else
            alert("changeWife: cw=" + cw.constructor.name)
    }

    let form                        = document.famForm;
    if (form.IDIRWife)
    {
        let idirwife                = form.IDIRWife.value;
        if (idirwife > 0 &&
            idirwife != parms['idir'])
            alert('commonMarriage.js: 1111: famForm.IDIRWife.value ' + idirwife.value + ' changed to ' + parms['idir']);
        form.IDIRWife.value         = parms['idir'];
    }
    if (form.WifeSurname)
        form.WifeSurname.value      = parms['surname'];
    if (form.WifeGivenName)
        form.WifeGivenName.value    = parms['givenname'];
    if (form.WifeBirth)
        form.WifeBirth.value        = parms['birthd'];
    if (form.WifeDeath)
        form.WifeDeath.value        = parms['deathd'];
    document.getElementById('editWife').disabled    = false;
    document.getElementById('chooseWife').disabled  = false;
    document.getElementById('createWife').disabled  = false;
}       // changeWife`

/************************************************************************
 *  function changeChild                                                *
 *                                                                      *
 *  Change the displayed information about a child.                     *
 *  This is a callback method of <div id='child$rownum'>                *
 *  that is called by editIndivid.js to update the displayed information*
 *  about a child in the summary list on this page.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            <div id='child$rownum'>                         *
 *      parms           object with at least the following members      *
 *          idir        IDIR of child as individual                     *
 *          idcr        IDCR of child relationship record               *
 *          givenname   new given name of child                         *
 *          surname     new surname of child                            *
 *          birthd      new birth date of child                         *
 *          deathd      new death date of child                         *
 *          gender      new gender of child: "male" or "female"         *
 *          gender      new sex code of child: 0 or 1                   *
 ************************************************************************/
function changeChild(parms)
{
    for (let ib = 0; ib < editChildButtons.length; ib++)
    {           // enable all editChild buttons
        editChildButtons[ib].disabled   = false;
    }           // enable all editChild buttons

    for(let iw = 0; iw < childWindows.length; iw++)
    {
        let cw      = childWindows[iw];
        let cloc    = cw.location;
        
        if (cloc && cloc.search.indexOf('rowid=child') >= 0)
        {
            childWindows.splice(iw, 1);
            break;
        }
        else
            alert("changeChild: cw=" + cw.constructor.name)
    }

    let famForm             = document.famForm;
    let row                 = this;
    let tableBody           = row.parentNode;
    let rownum              = row.id.substring(CHILD_PREFIX_LEN);
    let cIdir               = famForm.elements["CIdir" + rownum];
    let cIdcr               = famForm.elements["CIdcr" + rownum];
    let cGender             = famForm.elements["CGender" + rownum];
    let cGiven              = famForm.elements["CGiven" + rownum];
    let cSurname            = famForm.elements["CSurname" + rownum];
    let cBirth              = famForm.elements["Cbirth" + rownum];
    let cBirthsd            = famForm.elements["Cbirthsd" + rownum];
    let cDeath              = famForm.elements["Cdeath" + rownum];
    let cDeathsd            = famForm.elements["Cdeathsd" + rownum];

    let parmstr             = '';
    let linkstr             = '{';
    for (let key in parms)
    {           // loop through parameters
        let value           = parms[key];
        parmstr             += linkstr + key + "='" + value + "'";
        linkstr             = ',';
        switch(key.toLowerCase())
        {
            case 'idir':    // IDIR of individual
            {
                row.idir        = value;
                if (cIdir)
                    cIdir.value = value;
                break;
            }

            case 'idcr':    // IDCR of child relationship record
            {
                row.idcr        = value;
                if (cIdcr)
                    cIdcr.value = value;
                break;
            }

            case 'givenname':   // new given name of child
            {
                if (cGiven)
                    cGiven.value        = value;
                break;
            }

            case 'surname': // new surname of child 
            {
                if (cSurname)
                    cSurname.value      = value;
                break;
            }

            case 'birthd':  // new birth date of child
            {
                if (cBirth)
                    cBirth.value        = value;
                if (cBirthsd)
                    cBirthsd.value      = getSortDate(value);
                break;
            }

            case 'deathd':  // new death date of child
            {
                if (cDeath)
                    cDeath.value        = value;
                if (cDeathsd)
                    cDeathsd.value      = getSortDate(value);
                break;
            }

            case 'gender':  // new gender of child: "male" or "female"
            {
                if (cGiven)
                    cGiven.className    = value;
                if (cSurname)
                    cSurname.className  = value;
                if (cGender)
                {
                    if (value == 'male')
                        cGender.value   = 0;
                    else
                    if (value == 'female')
                        cGender.value   = 1;
                    else
                        cGender.value   = 2;
                }
                break;
            }

            case 'sex':     // new sex code of child: 0 or 1
            {
                if (cGender)
                    cGender.value       = value;
                break;
            }

        }       // act on specific parm fields
    }           // loop through parameters
    return  row;
}       // function changeChild

/************************************************************************
 *  function eventFeedback                                              *
 *                                                                      *
 *  This is a method of the form object that is called by the script    *
 *  editEvent.php to feedback changes to an event that should be        *
 *  reflected in this form.                                             *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <form> object                                       *
 *      parms       the values of fields from the editEvent.php dialog  *
 ************************************************************************/
function eventFeedback(parms)
{
    let form        = this;
    let type        = parseInt(parms['type']) - 0;

    // update field values in the current dialog based upon values
    // returned from the editEvent.php dialog
    switch(type)
    {           // source fields changed depend on type of event
        case STYPE_MAREVENT: // marriage event in tblER
        {       // marriage event
            redisplayFamily();  // refresh to display
            break;
        }       // marriage event in Event table

        case STYPE_MAR: // Marriage event in tblMR  
        {       // marriage event 
            form.MarD1.value     = parms['date'];
            form.MarLoc1.value   = parms['location'];
            break;
        }       // marriage event

        case STYPE_MARNOTE:
        {       // marriage note
            form.Notes.value    = parms['note'];
            break;
        }       // marriage note

        case STYPE_MARNEVER:
        {       // Never Married
            let notMarried      = parms['notmarried'];
            if (notMarried)
                addNotMarriedRow();
            else
            if (form.NotMarried)
                form.NotMarried.checked = false;
            break;
        }       // Never Married

        case STYPE_MARNOKIDS:
        {       // No Children  
            let noChildren      = parms['nochildren'];
            if (noChildren)
                addNoChildrenRow();
            else
            if (form.NoChildren)
                form.NoChildren.checked = false;
            break;
        }       // No Children  

        case STYPE_LDSS:            // Sealed to Spouse
        case STYPE_NEVERMARRIED:    // Never married 
        case STYPE_MAREND:          // marriage ended
        {       // other marriage event 
            redisplayFamily();  // refresh to display
            break;
        }       // other marriage event

    }           // source fields to refresh depend on type
}       // function eventFeedback

/************************************************************************
 *  function redisplayFamily                                                *
 *                                                                      *
 *  Refresh the dialog and redisplay the current family.                *
 ************************************************************************/
function redisplayFamily()
{   
    let idmr    = document.famForm.idmr.value;
    let url = window.location.search;
    if (url.indexOf('idmr') == -1)
        url = url + '&idmr=' + idmr;
    window.location.search  = url;
}       // function redisplayFamily

/************************************************************************
 *  function addExistingChild                                               *
 *                                                                      *
 *  Prompt the user to choose an existing individual to add as a child      *
 *  of this family.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='addChild'> element                              *
 ************************************************************************/
function addExistingChild()
{
    let form        = this.form;
    let surname     = encodeURI(form.HusbSurname.value);
    let idmr        = form.idmr.value;
    let url     = 'chooseIndivid.php?parentsIdmr=' + idmr + 
                                   '&name=' + surname +
                                   '&treeName=' +
                            encodeURIComponent(form.treename.value);
    let childWindow = openFrame("chooserFrame",
                                url,
                                "left");
}       // function addExistingChild

/************************************************************************
 *  function detachHusb                                                 *
 *                                                                      *
 *  This method is called when the user requests that the current       *
 *  husband be detached with no replacement.                            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='detachHusb'> element                    *
 ************************************************************************/
function detachHusb()
{
    let famForm                 = this.form;

    famForm.IDIRHusb.value      = 0;
    famForm.HusbGivenName.value = '';
    famForm.HusbSurname.value   = '';
    document.getElementById('detachHusb').disabled  = true;
    document.getElementById('editHusb').disabled    = true;
}       // function detachHusb

/************************************************************************
 *  function detachWife                                                 *
 *                                                                      *
 *  This method is called when the user requests that the current       *
 *  wife be detached with no replacement.                               *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='detachWife'> element                    *
 ************************************************************************/
function detachWife()
{
    let famForm                 = this.form;

    famForm.IDIRWife.value      = 0;
    famForm.WifeGivenName.value = '';
    famForm.WifeSurname.value   = '';
    document.getElementById('detachWife').disabled  = true;
    document.getElementById('editWife').disabled    = true;
}       // function detachWife

/************************************************************************
 *  function detChild                                                   *
 *                                                                      *
 *  Detach an existing individual as a child of this family.            *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='detChild....'>                          *
 *      ev          click Event                                         *
 ************************************************************************/
function detChild(ev)
{
    if (!ev)                    // IE < 9
        ev                      = window.event;
    ev.stopPropagation();

    let cell                    = this.parentNode;
    let row                     = cell.parentNode;
    let tableBody               = row.parentNode;

    // remove the editChild button for this row from the array
    // of editChild buttons
    let rowid                   = this.id.substring(8);
    let editButton              = document.getElementById('editChild' + rowid);
    if (editButton)
    {                           // found editChild button
        for (ib = 0; ib < editChildButtons.length; ib++)
        {                       // loop through existing editChild buttons
            if (editChildButtons[ib] == editButton)
            {                   // found matching button
                editChildButtons.splice(ib, 1); // remove from array
                break;
            }                   // found matching button
        }                       // loop through existing editChild buttons
    }                           // found editChild button

    let form                    = this.form;
    let cidcr                   = form.elements['CIdcr' + rowid];
    let idcr                    = cidcr.value;
    let cidir                   = form.elements['CIdir' + rowid];
    let idir                    = cidir.value;

    if (idcr == 0)
    {                           // remove the row from the DOM
        tableBody.removeChild(row);
    }                           // remove the row from the DOM
    else
    {                           // hide the row 
        cidir.value             = -1;
        let cgiven              = form.elements['CGiven' + rowid];
        cgiven.parentNode.removeChild(cgiven);
        // note that it is necessary to start at the end and go back because:
        // 1) the value of row.cells.length changes as cells are deleted
        // 2) the cell referenced by a given index ic changes if cells prior
        //    to that index are deleted
        for(let ic = row.cells.length - 1; ic > 0 ; ic--)
        {                       // delete all but the first cell of the row
            let dcell           = row.cells[ic];
            row.removeChild(dcell);
        }                       // delete all but the first cell of the row
    }                           // hide the row 
}       // function detChild

/************************************************************************
 *  function editChild                                                  *
 *                                                                      *
 *  This method is called when the user requests to edit                *
 *  information about an individual (child) in a family.                *
 *                                                                      *
 *  Input:                                                              *
 *      this            the <button id='editChild...'> element          *
 *      ev              instance of Event                               *
 ************************************************************************/
function editChild(ev)
{
    if (!ev)
        ev                  = window.event;
    ev.stopPropagation();

    let msg                 = 'args={';
    let initIdir            = 0;
    let lang                = 'en';
    for (attr in args)
    {                       // loop through attributes
        msg                 += attr + "='" + args[attr] + "',";
        switch(attr.toLowerCase())
        {
            case 'id':
            case 'idir':
                initIdir    = args[attr];
                break;

            case 'lang':
                lang        = args[attr];
                break;
        }                   // switch on attribute name
    }

    let button              = this;
    let form                = button.form;
    let rownum              = button.id.substr(EDIT_CHILD_PREFIX_LEN);
    let cell                = button.parentNode;
    let row                 = cell.parentNode;
    let rowid               = row.id;
    let script              = 'editIndivid.php?rowid=' + rowid;
    let idmr                = form.idmr.value - 0;
    let cIdir               = form.elements['CIdir' + rownum];
    if (cIdir)
        script              += "&idir=" + cIdir.value;
    if (initIdir && initIdir == cIdir.value)
    {               // edit dialog for this child already open
        // ask user to confirm delete
        let parms           = {
                'givenname' : form.elements['CGiven' + rownum].value,
                'surname'   : form.elements['CSurname' + rownum].value,
                'idir'      : cIdir,
                'template'  : ''};
        displayDialog('AlreadyEditing$template',
                      parms,
                      this,             // position relative to
                      null);            // just close on any button
        return;
    }               // edit dialog for this child already open
    let cIdcr               = form.elements['CIdcr' + rownum];
    let idcr                = 0;
    if (cIdcr)
    {
        idcr                = cIdcr.value - 0;
        script              += "&idcr=" + idcr;
        if (idcr == 0)
            script          += "&parentsIdmr=" + idmr;  // add child
    }
 
    // pass the values of the fields in this row to the edit form
    let cGiven              = form.elements['CGiven' + rownum];
    let cSurname            = form.elements['CSurname' + rownum];
    let cBirth              = form.elements['Cbirth' + rownum];
    let cDeath              = form.elements['Cdeath' + rownum];
    if (cGiven)
    {           // child given name present in row
        script              += '&initGivenName=' + 
                                encodeURIComponent(cGiven.value) +
                                '&initGender=' +
                                cGiven.className;
    }           // child given name present in row
    else
    {           // logic error
        let msg             = "";
        let comma           = "";
        for(let ie=0; ie < form.elements.length; ie++)
        {
            let element     = form.elements[ie];
            msg             += comma + element.name + "='" + 
                                element.value + "'";
            comma           = ",";
        }
        alert("editChild: unable to get form.element['CGiven" +
              rownum + "'] elements={" + msg + "}");
    }           // logic error

    if (cSurname)
        script              += '&initSurname=' + 
                                encodeURIComponent(cSurname.value);
    if (cBirth)
        script              += '&initBirthDate=' + 
                                encodeURIComponent(cBirth.value);
    if (cDeath)
        script              += '&initDeathDate=' + 
                                encodeURIComponent(cDeath.value);
    script                  += '&treeName=' +
                                encodeURIComponent(form.treename.value);
    if (debug.toLowerCase() == 'y')
        script              += '&debug=' + debug;
    script                  += '&lang=' + lang;

    // disable all of the edit family member buttons
    for (let ib = 0; ib < editChildButtons.length; ib++)
    {           // disable all editChild buttons
        editChildButtons[ib].disabled   = true;
    }           // disable all editChild buttons

    // open a dialog window to edit the child
    let childWindow = openFrame("childFrame",
                                script,
                                "left");
    childWindows.push(childWindow);
    return true;
}   // function editChild

/************************************************************************
 *  function addNewChild                                                *
 *                                                                      *
 *  This method is called when the user requests to add                 *
 *  a new individual to the marriage as a child.                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='addNewChild'> element                   *
 ************************************************************************/
function addNewChild()
{
    let form                = this.form;
    let childTable          = document.getElementById('children');
    let parms               = {
                                'idir'          : 0,
                                'givenname'     : '',
                                'surname'       : '',
                                'birthd'        : '',
                                'birthsd'       : -99999999,
                                'deathd'        : '',
                                'genderclass'   : 'unknown',
                                'gender'        : 'unknown'};
    parms.surname           = form.HusbSurname.value;

    let row                 = childTable.addChildToPage(parms,
                                                        false);

    if (row.id)
    {
        let rownum          = row.id.substring(CHILD_PREFIX_LEN);
        let givenName       = form.elements['CGiven' + rownum];
        givenName.onchange  = givenChanged;
        givenName.focus();      // move the cursor to the new name
    }
    else
        alert("commonMarriage.js: addNewChild: row=" +
                new XMLSerializer().serializeToString(row));
}   // function addNewChild

/************************************************************************
 *  function givenChanged                                               *
 *                                                                      *
 *  This method is called when the user modifies the value of the       *
 *  given name of a child.  It adjusts the default gender based         *
 *  upon the name.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this    instance of <input id="CGiven...">                      *
 ************************************************************************/
let givenElt        = null;
function givenChanged()
{
    givenElt        = this;
    let givenName   = this.value.toLowerCase();
    let options             = {};
    options.errorHandler    = function() {alert('script getRecordJSON.php not found')};
    let url         = '/getRecordJSON.php?table=Nicknames&id=' + givenName.replace(/\s+/g, ',');
    HTTP.get(url,
             gotNickname,
             options);
}   // function givenChanged

/************************************************************************
 *  function gotNickname                                                *
 *                                                                      *
 *  This method is called when the user modifies the value of the       *
 *  given name of a child.  It adjusts the default gender based         *
 *  upon the name.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      nickname        JSON object from server                         *
 ************************************************************************/
function gotNickname(nickname)
{
    let form                        = givenElt.form;
    let rownum                      = givenElt.name.substring(6);
    let surnameElt                  = form.elements['CSurname' + rownum];
    let genderElt                   = form.elements['CGender' + rownum];

    if (!('gender' in nickname))
    {                       // array of responses
        let nicknames               = nickname;
        for(let name in nicknames)
        {                   // loop through responses
            nickname                = nicknames[name];
            if ('gender' in nickname && nickname.gender !== null)
                break;
        }                   // loop through responses
    }                       // array of responses

    // at this point nickname is either the first entry with
    // a gender value or the only or last entry
    let gender                      = nickname.gender;
    let given                       = nickname.nickname;
    if (gender == 'F')
    {                           // gender female
        givenElt.className          = 'female'
        if (surnameElt)
            surnameElt.className    = 'female';
        if (genderElt)
            genderElt.value         = 1;
    }                           // gender female
    else
    if (gender == 'M')
    {                           // gender male
        givenElt.className          = 'male'
        if (surnameElt)
            surnameElt.className    = 'male';
        if (genderElt)
            genderElt.value         = 0;
    }                           // gender male
    else
    {                           // gender unresolved
        if (given.substring(given.length - 1) == 'a')
        givenElt.className          = 'female'
        if (surnameElt)
            surnameElt.className    = 'female';
        if (genderElt)
            genderElt.value         = 1;
    }                           // gender unresolved

    // fold according to case rules and expand abbreviations
    changeElt(givenElt);

    if (givenElt.checkfunc)
        givenElt.checkfunc();
}   // function gotNickname

/************************************************************************
 *  function changeHusbSurname                                          *
 *                                                                      *
 *  This function is called when the surname of the husband is changed  *
 *  This may required changing the married surnames of the husband and  *
 *  wife.                                                               *
 *                                                                      *
 *  Input:                                                              *
 *      this    <input type='text' id='HusbSurname'> element            *
 ************************************************************************/
function changeHusbSurname()
{
    let form        = this.form;
    let surname     = this.value;
    if (form.HusbMarrSurname)
        form.HusbMarrSurname.value  = surname;
    if (form.WifeMarrSurname &&
        form.MarriedNameRule &&
        form.MarriedNameRule.value == '1')
        form.WifeMarrSurname.value  = surname;

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
        this.checkfunc();
}       // function changeHusbSurname

/************************************************************************
 *  function changeCBirth                                               *
 *                                                                      *
 *  This function is called when the date of birth of a child changes.  *
 *  This requires updating the sorted birth date used for ordering      *
 *  children.                                                           *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='Cbirth...'> element          *
 ************************************************************************/
function changeCBirth()
{
    let form        = this.form;
    let rowid       = this.name.substring(6);
    let birthd      = this.value;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    birthd          = birthd.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    birthd          = birthd.replace(/(\d)([a-zA-Z])/g,"$1 $2");
    this.value      = birthd;

    let y           = 0;

    let datePattern = /\d{4}/;
    let pieces      = datePattern.exec(birthd);
    if (pieces !== null)
    {
        y       = parseInt(pieces[0]);
        form.elements['Cbirthsd' + rowid].value = y * 10000;
    }

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
        this.checkfunc();
}       // function changeCBirth

/************************************************************************
 *  function changeCDeath                                               *
 *                                                                      *
 *  This function is called when the date of death of a child changes.  *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input type='text' id='Cdeath...'> element          *
 ************************************************************************/
function changeCDeath()
{
    let form        = this.form;
    let rowid       = this.name.substring(6);
    let deathd      = this.value;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    deathd      = deathd.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    deathd      = deathd.replace(/(\d)([a-zA-Z])/g,"$1 $2");
    this.value      = deathd;

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
        this.checkfunc();
}       // function changeCDeath

/************************************************************************
 *  function updateMarr                                                 *
 *                                                                      *
 *  This method is called when the user requests to update              *
 *  the marriage.  A request is sent to the server to perform the       *
 *  update.  This request returns an XML document reporting the results.*
 *                                                                      *
 *  Input:                                                              *
 *      this    <button id='update'> element                            *
 ************************************************************************/
let updateMarriageParms         = "";
let updatingMarriage            = false;

function updateMarr()
{
    if (updatingMarriage)
    {
        updatingMarriage        = false;
        return;
    }
    updatingMarriage            = true;

    // do not submit the update if there are open child edit windows
    // count the number of open child edit windows
    let numOpenChildWindows     = 0;
    let childWindowNames        = "";
    let comma                   = "";
    for(let i   = 0; i < childWindows.length; i++)
    {       // loop through all edit child windows
        let childWindow         = childWindows[i];
        if (!(childWindow.closed))
        {
            numOpenChildWindows++;
            childWindowNames    += comma + "'" +
                                childWindow.document.title + "'"; 
            comma               = ' and ';
        }
    }       // loop through all edit child windows

    // if there are open child edit windows warn the user and skip save
    childWindowNames            = childWindowNames.trim();
    if (childWindowNames.length > 2)
    {       // at least one child window still open
        popupAlert("Warning: subordinate edit window " +
                        childWindowNames + " is still open",
                   this);
        return;
    }       // at least one child window still open

    // request the update of the marriage record in the database
    let form                    = this.form;
    let parms                   = {};
    let msg                     = "";

    // expand incomplete wife or mother's name
    let wifeSurname             = form.WifeSurname;
    let wifeGiven               = form.WifeGivenName;
    let husbSurname             = form.HusbSurname;
    let husbGiven               = form.HusbGivenName;
    if (wifeGiven.value.length > 0 && 
        wifeSurname.value.length == 0 &&
        wifeGiven.value.indexOf("Wifeof") < 0)
    {
let wifeSurnameStr      = "Wifeof" +
                      husbGiven.value.toLowerCase() + 
                      husbSurname.value.toLowerCase();
wifeSurnameStr          = wifeSurnameStr.replace(/\s+/g, '');
wifeSurname.value       = wifeSurnameStr;
    }

    // copy selected information from the form to the parameters
    for (let ei     = 0; ei < form.elements.length; ei++)
    {       // loop through all elements in the form
let element             = form.elements[ei];
let name                = element.name;
if (name == 'Notes')
{
    try {
        parms[name]     = tinyMCE.get(name).getContent();
    } catch(err)
    {
        parms[name]     = element.value; 
        alert(err.message); 
    }
}
else
if (name.length > 0)
    parms[name]         = element.value;
    }               // loop through all elements in the form

    updateMarriageParms         = "parms={";
    for(parm in parms)
    {
updateMarriageParms += parm + "='" + parms[parm] + "',";
    }
    if (debug.toLowerCase() == 'y')
alert("HTTP.post('/FamilyTree/updateMarriageJSON.php', " +
      updateMarriageParms + "}");

    popupLoading(this); // display loading indicator
    HTTP.post('/FamilyTree/updateMarriageJSON.php',
      parms,
      gotUpdatedFamily,
      noUpdatedFamily);
}   // function updateMarr

/************************************************************************
 *  function gotUpdatedFamily                                           *
 *                                                                      *
 *  This method is called when the XML document representing            *
 *  the updated marriage is returned.                                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      jsonDoc         response from server script                     *
 *                      updateMarriageJSON.php as a JSON document       *
 *                      containing a LegacyMarriage record.             *
 ************************************************************************/
function gotUpdatedFamily(jsonDoc)
{
    if (jsonDoc === null)
    {
        hideLoading();  // hide loading indicator
        alert("gotUpdateMarr: jsonDoc is null");
        return;
    }

    let authElt         = document.getElementById('UserInfoAuthorized');
    if (authElt && authElt.innerText.toLowerCase() == 'yes')
        console.log("gotUpdatedFamily: jsonDoc=" + JSON.stringify(jsonDoc));

    let idmr                    = 0;
    let spsIdir                 = 0;
    let fatherid                = 0;
    let motherid                = 0;
    let spsSurname              = '';
    let spsGivenname            = '';
    let fatherSurname           = '';
    let fatherGiven             = '';
    let motherSurname           = '';
    let motherGiven             = '';
    let spsclass                = 'male';
    let marDate                 = 'Unknown';

    hideLoading();      // hide loading indicator
    updatingMarriage            = false;
    if (typeof jsonDoc == 'object')
    {                   // JSON document
        let form                = document.indForm;
        let sex                 = 0;            // 0 for male, 1 for female
        if (form.sex)
            sex                 = form.sex.value;
        if (sex == 0)
            spsclass            = 'female';
        else
            spsclass            = 'male';
        
        let msg                 = jsonDoc.msg;
        let parms               = jsonDoc.parms;
        let actions             = jsonDoc.actions;
        let family              = jsonDoc.family;
           
        if (msg)
        {       // error message
            alert ("commonMarriage.js: gotUpdatedFamily: Error: " +
                msg + ", " + updateMarriageParms);
            let para        = document.getElementById('MarrButtonLine');
            para.appendChild(document.createTextNode(
            new JSON.stringify(jsonDoc).replace('/</g', '&lt;').replace('/>/g', '&lt;')));
            return;
        }       // error message
        
        if (idmr == 0 && family && family.idmr)
        {       // key of the record
            idmr                = family.idmr;
        }       // key of the record
        
        if (actions)
        {       // parameter processing
            processActions(actions);
        }       // parameter processing


        // take appropriate action
        let opener                      = null;
        if (window.frameElement && window.frameElement.opener)
            opener                      = window.frameElement.opener;
        else
            opener                      = window.opener;

        if (pendingButton)
        {                   // another action to perform
            form                        = pendingButton.form;
            form.idmr.value             = idmr;
            let tmp                     = pendingButton;
            pendingButton               = null;
            tmp.click();
        }                   // another action to perform
        else
        if (opener)
        {                   // notify the opener (editIndivid.php)
            if (opener.document.indForm)
            {
                let section         = document.getElementById('marriageListBody');
                let numFamilies     = section.rows.length;
                if ('new' in args && args['new'].toLowerCase() == 'y')
                    numFamilies++;
                else
                if (numFamilies == 0)
                    numFamilies     = 1;    // adding first family
                opener.document.indForm.marriageUpdated(idmr,
                                                        numFamilies);
            }

            closeFrame();
        }                   // notify the opener (editIndivid.php)
        else
        {                   // not invoked from another page
            console.log("commonMarriage.js: gotUpdatedFamily: " +
                            "window.history.back()"); 
            window.history.back();
        }                   // not invoked from another page
    }                       // JSON document
    else
    {                       // not a JSON document, display text
        alert("commonMarriage.js: gotUpdatedFamily: Unexpected: " +
                            jsonDoc + ", " + updateMarriageParms);
    }                       // not a JSON document, display text
}       // function gotUpdatedFamily

/************************************************************************
 *  function processActions                                             *
 *                                                                      *
 *  This method is called to process the actions member from the        *
 *  JSON document response from the script updateMarriageJSON.php.      *
 *                                                                      *
 *  Parameters:                                                         *
 *      actions          array containing actions taken by server       *
 ************************************************************************/
function processActions(actions)
{
    let idir                        = 0;
    let idcr                        = 0;

    for (const action in actions)
    {                       // loop through individual actions
        let actionobj               = actions[action];
        let namePattern             = /^([a-zA-Z_]+)(\d*)$/;
        let name                    = action.toLowerCase();
        let rowNum                  = '';
        let pieces                  = namePattern.exec(name);
        if (pieces)
        {                   // separate column and row
            name                    = pieces[1];
            rowNum                  = pieces[2];
        }                   // separate column and row

        switch(name)
        {                   // act on individual object
            case 'cidir':
                // need to get actual IDIR, IDCR, and BirthSD of
                // child updated with all of the information from
                // the preceding parameters
                for (const childname in actionobj)
                {           // loop through children
                    let value               = actionobj[childname];
                    let cname               = childname.toLowerCase();
                    let pieces              = namePattern.exec(cname);
                    let crowNum             = '';
                    if (pieces)
                    {       // separate column and row
                        cname               = pieces[1];
                        crowNum             = pieces[2];
                    }       // separate column and row

                    switch(cname)
                    {       // act on specific sub-parameter
                        case 'idir':
                        {
                            let fldId       = 'CIdir' + crowNum;
                            let idirElt     = document.getElementById(fldId);
                            if (idirElt && idirElt.value == 0)
                                idirElt.value   = value;
                            break;
                        }   // idir

                        case 'idcr':
                        case 'newidcr':
                        {
                            let fldId       = 'CIdcr' + crowNum;
                            let idcrElt     = document.getElementById(fldId);
                            if (idcrElt && idcrElt.value == 0)
                                idcrElt.value   = value;
                            break;
                        }   // idcr

                        case 'birthsd':
                        {
                            let fldId       = 'Cbirthsd' + crowNum;
                            let birthsdElt  = document.getElementById(fldId);
                            if (birthsdElt)
                                birthsdElt.value    = value;
                            else
                                alert('commonMarriage.js: processActions: ' +
                                      'cannot find <input id="' + fldId + '">');
                            break;
                        }   // birthsd

                    }       // act on specific sub-parameter
                }           // loop through children
                break;      // first update to child

            case 'cidcr':
                idcr                = child;
                break;      // IDCR of a child


        }                   // act on individual parm
    }                       // loop through individual parameters
}       // function processActions

/************************************************************************
 *  function noUpdatedFamily                                            *
 *                                                                      *
 *  This method is called if the server does not return                 *
 *  a JSON document response from the script updateMarriageJSON.php.    *
 ************************************************************************/
function noUpdatedFamily()
{
    alert("commonMarriage.js: noUpdatedFamily: script updateMarriageJSON.php not found on server");
}       // function noUpdatedFamily

/************************************************************************
 *  function orderChildren                                              *
 *                                                                      *
 *  This method is called when the user requests to reorder             *
 *  the children by birth date.  This method only changes the order     *
 *  in which the children appear in the display.  The family must be    *
 *  updated to apply the change to the database.                        *
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='orderChildren'>                         *
 ************************************************************************/
function orderChildren()
{
    let children            = document.getElementById('children');
    let body                = children.tBodies[0];
    let bodyRows            = Array();
    for (let i = 0; i < body.rows.length; i++)
    {
        let row             = body.rows[i];
        let rowId           = row.id.substring(5);
        let idirElt         = document.getElementById('CIdir' + rowId);
        if (typeof(idirElt) != 'undefined' && idirElt.value == 0)
        {       // child is not yet in database
            pendingButton   = this;
            this.form.update.click();   // save the family first
            return;
        }
        bodyRows[i]         = body.rows[i];
    }
    bodyRows.sort(childOrder);
    while (body.hasChildNodes())
        body.removeChild(body.firstChild);
    for (let ri = 0; ri < bodyRows.length; ri++)
        body.appendChild(bodyRows[ri]);
}   // function orderChildren

/************************************************************************
 *  function childOrder                                                 *
 *                                                                      *
 *  This function is called by the Array sort method to determine       *
 *  the relative order of a pair of children in the array of children   *
 *  based upon their dates of birth.                                    *
 *                                                                      *
 *  Input:                                                              *
 *      first           instance of <tr>                                *
 *      second          instance of <tr>                                *
 *                                                                      *
 *  Returns:                                                            *
 *      >0 if the first child was born first                            *
 *      0 if the children were born on the same date                    *
 *      <0 if the second child was born first                           *
 ************************************************************************/

function childOrder(first, second)
{
    let e1, e2;
    let sd1, sd2;
    let firstElements   = first.getElementsByTagName("input");
    for(e1 = 0; e1 < firstElements.length; e1++)
    {
        let e1Name  = firstElements[e1].name.substring(0,8);
        if (e1Name == 'Cbirthsd')
        {
            sd1     = firstElements[e1].value;
            break;
        }
    }       // loop through input elements
    let secondElements  = second.getElementsByTagName("input");
    for(e2 = 0; e2 < secondElements.length; e2++)
    {
        let e2Name  = secondElements[e2].name.substring(0,8);
        if (e2Name == 'Cbirthsd')
        {
            sd2     = secondElements[e2].value;
            break;
        }
    }       // loop through input elements
    // alert("childOrder: sd1=" + sd1 + ", sd2=" + sd2 +
    //      ", return=" + (sd1 - 0 - sd2));
    return sd1 - 0 - sd2;
}       // function childOrder

/************************************************************************
 *  function editEvent                                                  *
 *                                                                      *
 *  This is the onclick method of the "Edit Event" button.              *
 *  It is called when the user requests to edit                         *
 *  information about an event of the current family that is            *
 *  recorded in an instance of Event.                                   *
 *                                                                      *
 *  Input:                                                              *
 *      this    <button id='EditEvent9999'> where the number is the     *
 *              row number in the display.                              *
 *      ev      instance of Javascript click Event                      *
 ************************************************************************/
function editEvent(ev)
{
    let form            = this.form;
    let matches         = this.id.match(/\d*$/);
    let rownum          = matches[0];
    let iderElt         = form.elements['ider' + rownum];
    if (iderElt)
    {
        let ider        = iderElt.value;
        let idet        = form.elements['idet' + rownum].value;
        let idmr        = form.idmr.value;
        if (idmr && idmr > 0)
        {           // existing family
            let url     = 'editEvent.php?idmr=' + idmr +
                                    '&ider=' + ider +
                                    '&idet=' + idet +
                                    '&type=31';

            let eventWindow = openFrame("eventLeft",
                                        url,
                                        "left");
        }           // existing family
        else
        {           // family needs to be saved first
            pendingButton   = this;
            form.update.click();    // save the family first
        }           // family needs to be saved first
    }
    else
        popupAlert("cannot find element id='ider" + rownum + "'", this); 
    return true;
}   // function editEvent

/************************************************************************
 *  function editIEvent                                                 *
 *                                                                      *
 *  This is the onclick method of an "Edit Event" button.               *
 *  It is called when the user requests to edit                         *
 *  information about an event of the current family that is            *
 *  recorded in the instance of Family.                                 *
 *                                                                      *
 *  Parameters:                                                         *
 *      this    <button id='EditIEvent9999'> where the number is        *
 *              a citation type as defined in Citation.inc              *
 *      ev      instance of Javascript click Event                      *
 ************************************************************************/
function editIEvent(ev)
{
    if (!ev)
        ev              = window.event;
    ev.stopPropagation();

    let form            = this.form;
    let ider            = 0;
    let rownum          = this.id.substring(10);
    let citTypeId       = 'CitType' + rownum;
    let citTypeElt      = document.getElementById(citTypeId);
    if (citTypeElt)
        citType         = citTypeElt.value;
    else
        citType         = rownum;
    if (citType == 31)
    {
        let iderElt     = document.getElementById('IDER' + rownum)
        ider            = iderElt.value;
    }

    let idmr            = form.idmr.value;
    if (ider > 0)
    {
        if (idmr && idmr > 0)
        {           // existing family
            let url     = 'editEvent.php?idmr=' + idmr +
                                    '&ider=' + ider +
                                    '&type=31';
            let eventWindow = openFrame("eventLeft",
                                        url,
                                        "left");
        }           // existing family
        else
        {           // family needs to be saved first
            pendingButton   = this;
            form.update.click();    // save the family first
        }           // family needs to be saved first
    }
    else
    if (idmr && idmr > 0)
    {               // existing family
        let url         = 'editEvent.php?idmr=' + idmr +
                                '&type=' + citType;
        let MarD        = document.getElementById('MarD' + rownum);
        let MarLoc      = document.getElementById('MarLoc' + rownum);
        if (citType == STYPE_MAR)
            url         += "&date=" +
                           encodeURIComponent(MarD.value) +
                           "&location=" +
                           encodeURIComponent(MarLoc.value);
        let eventWindow = openFrame("openLeft",
                                url,
                                "left");
    }               // existing family
    else
    {               // family needs to be saved first
        pendingButton   = this;
        form.update.click();    // save the family first
    }               // family needs to be saved first
    return true;
}   // function editIEvent

/************************************************************************
 *  function delEvent                                                   *
 *                                                                      *
 *  This is the onclick method of the "Delete Event" button.            *
 *  It is called when the user requests to delete                       *
 *  information about an existing event in the current family that is   *
 *  recorded by an instance of Event.                                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <button id='DelEvent9999'> where the number is the  *
 *                  row identifier of the displayed event               *
 *      ev          instance of Event                                   *
 ************************************************************************/
function delEvent(ev)
{
    if (!ev)
        ev          = window.event;
    ev.stopPropagation();

    let form        = this.form;
    let matches     = this.id.match(/\d*$/);
    let rownum      = matches[0];
    let iderElt     = form.elements['ider' + rownum];
    let ider        = iderElt.value;
    let parms       = {"type"       : '31',
                       "formname"   : form.name, 
                       "template"   : "",
                       "ider"       : ider,
                       "msg"        :
                            "Are you sure you want to delete this event?"};

    // ask user to confirm delete
    displayDialog('ClrInd$template',
                  parms,
                  this,             // position relative to
                  confirmEventDel); // 1st button confirms Delete
}       // function delEvent

/************************************************************************
 *  function confirmEventDel                                            *
 *                                                                      *
 *  This method is called when the user confirms the request to delete  *
 *  an event which is defined in an instance of Event.                  *
 *  A request is sent to the server to delete the instance.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='confirmClear...'>                   *
 ************************************************************************/
function confirmEventDel()
{
    // get the parameter values hidden in the dialog
    let form        = this.form;
    let rownum      = this.id.substr(12);
    let ider        = form.ider.value;
    dialogDiv.style.display = 'none';


    if (form)
    {       // have the form
        let parms   = {"idime"      : ider,
                       "cittype"    : 31};

        // invoke script to update Event and return XML result
        popupLoading(this); // display loading indicator
        HTTP.post('/FamilyTree/deleteEventXml.php',
                  parms,
                  gotDelEvent,
                  noDelEvent);
    }       // have the form
    else
        alert("commonMarriage.js: confirmEventDel: unable to get form");
    return true;
}   // function confirmEventDel

/************************************************************************
 *  function delIEvent                                                  *
 *                                                                      *
 *  This is the onclick method of the "Delete Internal Event" button.   *
 *  It is called when the user requests to delete                       *
 *  information about an existing event in the current family that is   *
 *  recorded by data inside the instance of Family.                     *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            <button id='DelIEvent9999'> where the number    *
 *                      is a citation type                              *
 *      ev              instance of Event                               *
 ************************************************************************/
function delIEvent(ev)
{
    if (!ev)
        ev              = window.event;
    ev.stopPropagation();

    let form            = this.form;
    let ider            = 0;
    let rownum          = this.id.substring(9);
    let citTypeId       = 'CitType' + rownum;
    let citTypeElt      = document.getElementById(citTypeId);
    if (citTypeElt)
        citType         = citTypeElt.value;
    else
        citType         = rownum;
    if (citType == '31')
    {
        let iderElt     = document.getElementById('IDER' + rownum)
        ider            = iderElt.value;
    }

    let idmr            = form.idmr.value;
    let parms       = {"type"       : citType,
                       "formname"   : form.name, 
                       "template"   : "",
                       "ider"       : ider,
                       "msg"        :
                        "Are you sure you want to delete this event?"};

    // ask user to confirm delete
    displayDialog('ClrInd$template',
                  parms,
                  this,             // position relative to
                  confirmDelIEvent);    // 1st button confirms Delete
}       // function delIEvent

/************************************************************************
 *  function confirmDelIEvent                                           *
 *                                                                      *
 *  This method is called when the user confirms the request to delete  *
 *  an event which is defined inside the Familyrecord.                  *
 *  The contents of the fields describing the event are cleared.        *
 *  The user still needs to update the individual to apply the changes. *
 *                                                                      *
 *  Input:                                                              *
 *      this            <button id='confirmClear...'>                   *
 ************************************************************************/
function confirmDelIEvent()
{
    // get the parameter values hidden in the dialog
    let form        = this.form;
    let citType     = this.id.substr(12);
    let ider        = form.ider.value;
    let formname    = form.elements['formname' + citType].value;

    dialogDiv.style.display = 'none';

    if (form)
    {       // have the form
        let parms   = { "idime"     : form.idmr.value,
                        "cittype"   : citType};
        if (ider > 0)
            parms   = { "idime"     : ider,
                        "cittype"   : '31'};
        popupLoading(this); // display loading indicator
        HTTP.post('/FamilyTree/deleteEventXml.php',
                  parms,
                  gotDelEvent,
                  noDelEvent);
    }       // have the form
    else
        alert("commonMarriage.js: confirmDelIEvent: unable to get form");
    return true;
}   // function confirmDelIEvent

/************************************************************************
 *  function gotDelEvent                                                *
 *                                                                      *
 *  This method is called when the XML document representing            *
 *  a successful delete family event is retrieved from the database.    *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc      response from the server script                     *
 *                  deleteEventXml.php as an XML document               *
 ************************************************************************/
function gotDelEvent(xmlDoc)
{
    hideLoading();  // hide loading indicator
    if (xmlDoc.documentElement)
    {       // XML document
        let root    = xmlDoc.documentElement;
        if (root.tagName == 'deleted')
        {       // correctly formatted response
            let msgs    = root.getElementsByTagName('msg');
            if (msgs.length == 0)
            {       // no errors detected
                redisplayFamily();
                // notify the opener (editIndivid.php) of the updated marriage
                let opener  = null;
                if (window.frameElement && window.frameElement.opener)
                    opener  = window.frameElement.opener;
                else
                    opener  = window.opener;
                if (opener && opener.document.indForm)
                {
                    try {
                        let section         = 
                            document.getElementById('marriageListBody');
                        let numFamilies     = section.rows.length;
                        opener.document.indForm.marriageUpdated(0,
                                        numFamilies);
                    } catch(e)
                    { 
                        alert("commonMarriage.js: 2388 e=" + e); 
                    }
                }
            }       // no errors detected
            else
            {       // report message
                alert('commonMarriage.js: gotDelEvent: ' + new XMLSerializer().serializeToString(msgs[0]));
            }       // report message
        }       // correctly formatted response
        else
            alert('commonMarriage.js: gotDelEvent: ' + new XMLSerializer().serializeToString(root));
    }       // XML document
    else
        alert('commonMarriage.js: gotDelEvent: ' + xmlDoc);
}       // function gotDelEvent

/************************************************************************
 *  function noDelEvent                                                 *
 *                                                                      *
 *  This method is called if there is no server response from the       *
 *  deleteEventXml.php script                                           *
 ************************************************************************/
function noDelEvent()
{
    alert('commonMarriage.js: noDelEvent: No server response from deleteEventXml.php');
}       // function noDelEvent

/************************************************************************
 *  function changeEventList                                            *
 *                                                                      *
 *  This is the onchange method of the "eventList" selection.           *
 *  It is called when the user chooses a new event to add               *
 *  to the current family that is recorded by an instance of Event      *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <select id='eventList'>                             *
 *      ev          instance of Javascript change Event                 *
 ************************************************************************/
function changeEventList(ev)
{
    let form                    = this.form;
    let idet                    = this.value;
    if (idet > 0)
    {
        let selectedIndex       = this.selectedIndex;
        let text                = this.options[selectedIndex].text;
        let addEventRow         = this.parentNode;
        let fieldset            = addEventRow.parentNode;
        let events              = document.getElementById('events');
        let rownum              = events.childElementCount + 1;
        let template            = document.getElementById('EventRow$rownum');
        if (template)
        {
            template            = template.outerHTML;
            
            template            = template.replace(/\$rownum/g, rownum);
            template            = template.replace(/\$type/g, text);
            template            = template.replace(/\$ider/g, 0);
            template            = template.replace(/\$idet/g, idet);
            template            = template.replace(/\$eventd/g, '');
            template            = template.replace(/\$eventloc/g, '');
            events.innerHTML    = events.innerHTML + template;
            let dateElt         = document.getElementById('Date' + rownum);
            dateElt.abbrTbl     = MonthAbbrs;
            dateElt.onchange    = dateChanged;
            dateElt.checkfunc   = checkDate;
            actMouseOverHelp(dateElt);
            let locElt          = document.getElementById('EventLoc' + rownum);
            locElt.abbrTbl      = evtLocAbbrs;
            if (idet == 76)
                locElt.onchange = templeChanged;
            else
                locElt.onchange = locationChanged;
            actMouseOverHelp(locElt);
            let editBtn         = document.getElementById('EditEvent' + rownum);
            editBtn.onclick     = editEvent;
            let delBtn          = document.getElementById('DelEvent' + rownum);
            delBtn.onclick      = delEvent;
        }
        else
            alert("no template EventRow$rownum");
    }
    this.value                  = 0;    // rest back to "choose"
    return true;                        // continue propagate event
}   // function changeEventList

/************************************************************************
 *  function gotAddEvent                                                *
 *                                                                      *
 *  This method is called when the JSON document representing           *
 *  an Eventd added to the family is retrieved from the server.         *
 *                                                                      *
 *  Parameters:                                                         *
 *      jsonObj     Event record as a JSON document                     *
 ************************************************************************/
function gotAddEvent(jsonObj)
{
    hideLoading();  // hide loading indicator
    alert("commonMarriage.js: gotAddEvent: " + JSON.stringify(jsonObj));
}

/************************************************************************
 *  function noAddEvent                                                 *
 *                                                                      *
 *  This method is called when the JSON document representing           *
 *  an Event added to the family is not available from the server.      *
 *                                                                      *
 ************************************************************************/
function noAddEvent()
{
    hideLoading();  // hide loading indicator
    alert('commonMarriage.js: noAddEvent: script addEventJSON.php not found on server');
}       // function noAddEvent

/************************************************************************
 *  function orderEvents                                                *
 *                                                                      *
 *  This method is called when the user requests to reorder             *
 *  the events by event date.  This method only changes the order       *
 *  in which the events appear in the display.                          * 
 *                                                                      *
 *  Input:                                                              *
 *      this        <button id='orderEvents'>                           *
 ************************************************************************/
function orderEvents()
{
    popupAlert("Sorry, this functionality is not yet implemented", this);
}       // function orderEvents

/************************************************************************
 *  function editPictures                                               *
 *                                                                      *
 *  This is the onclick method of the "Edit Pictures" button.           *
 *  It is called when the user requests to edit                         *
 *  information about the Pictures of the current family that are       *
 *  recorded by instances of Picture.                                   *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <button id='Pictures'                               *
 ************************************************************************/
function editPictures()
{
    let form    = this.form;

    if (form)
    {
        let idmr    = form.idmr.value;
        if (idmr && idmr > 0)
        {
            let url = "editPictures.php?idir=" + idmr + "&idtype=Mar"; 
            let childWindow = openFrame("picturesLeft",
                                    url,
                                    "left");
        }           // existing family
        else
        {           // family needs to be saved first
            pendingButton   = this;
            form.update.click();    // save the family first
        }           // family needs to be saved first
    }
    else
        alert("commonMarriage.js: editPictures: unable to get form");
    return true;
}   // function editPictures

/************************************************************************
 *  function changeNameRule                                             *
 *                                                                      *
 *  The user has altered the selection of MarriageNameRule.             *
 *                                                                      *
 *  Input:                                                              *
 *      this        <select name='MarriedNameRule'>                     *
 ************************************************************************/
function changeNameRule()
{
    if (this.selectedIndex >= 0)
    {       // user has selected a rule
        let option      = this.options[this.selectedIndex];
        let form        = this.form;
        let husbMarrSurname = form.HusbMarrSurname;
        let wifeMarrSurname = form.WifeMarrSurname;
        let husbSurname = form.HusbSurname.value;
        let wifeSurname = form.WifeSurname.value;

        if (option.value == 0)
        {   // display explicit married surname fields
            husbMarrSurname.readonly    = false;
            husbMarrSurname.className   = 'actleft';
            husbMarrSurname.value   = husbSurname;
            wifeMarrSurname.readonly    = false;
            wifeMarrSurname.className   = 'actleft';
            wifeMarrSurname.value   = wifeSurname;
        }   // display explicit married surname fields
        else
        if (option.value == 1)
        {   // hide traditional married surname fields
            husbMarrSurname.readonly    = true;
            husbMarrSurname.className   = 'ina left';
            husbMarrSurname.value   = husbSurname;
            wifeMarrSurname.readonly    = true;
            wifeMarrSurname.className   = 'ina left';
            wifeMarrSurname.value   = husbSurname;
        }   // hide traditional married surname fields
        else
        {   // display explicit married surname fields
            husbMarrSurname.readonly    = false;
            husbMarrSurname.className   = 'white left';
            husbMarrSurname.value   = husbSurname;
            wifeMarrSurname.readonly    = false;
            wifeMarrSurname.className   = 'white left';
            wifeMarrSurname.value   = wifeSurname;
        }   // display explicit married surname fields
    }       // user has selected a rule
}       // function changeNameRule

/************************************************************************
 *  function gotAddChild                                                *
 *                                                                      *
 *  This method is called when the XML document representing            *
 *  a child added to the family is retrieved from the server.           *
 *                                                                      *
 *  Parameters:                                                         *
 *      xmlDoc          Family record as an XML document                *
 ************************************************************************/
function gotAddChild(xmlDoc)
{
    hideLoading();  // hide loading indicator
    // get information from XML document
    if (xmlDoc.documentElement)
    {       // XML document
        let root    = xmlDoc.documentElement;
        if (root.tagName == 'child')
        {       // correctly formatted response
            let parms       = getParmsFromXml(root);
            let childTable  = document.getElementById('children');
            childTable.addChildToPage(parms);
        }       // correctly formatted response
    }       // XML document
    else
        alert("gotAddChild: " + xmlDoc);
}       // function gotAddChild

/************************************************************************
 *  function noAddChild                                                 *
 *                                                                      *
 *  This method is called if there is no add child response             *
 *  from the server.                                                    *
 ************************************************************************/
function noAddChild()
{
    alert('commonMarriage.js: noAddChild: script addChildXml.php not found on server');
}       // function noAddChild

/************************************************************************
 *  function addChildToPage                                             *
 *                                                                      *
 *  This method is called to add information about a child              *
 *  as a visible row in the web page.  If requested it also adds the    *
 *  child to the database. This is a callback method of the             *
 *  <table id='children'> element that is called by editIndivid.js      *
 *  to display information about a child that is being added to the     *
 *  family.                                                             *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            table element with id='children'                *
 *      parms           object with at least the following membets      *
 *          idir        IDIR of child to update or object               *
 *          givenname   given name of new child                         *
 *          surname     surname of new child                            *
 *          birthd      birth date of new child as text                 *
 *          birthsd     birth date of new child as yyyymmdd             *
 *          deathd      death date of new child as text                 *
 *          gender      gender of new child: "male" or "female"         *
 *      updateDb        no longer used                                  *
 *                                                                      *
 *  Returns:                                                            *
 *      <div id='childnnn> or <tr id='childnnn'> element added to page  *
 ************************************************************************/
function addChildToPage(parms,
                        updateDb,
                        debug)
{
    let msg             = "";   // trace message
    for(parm in parms) { msg += parm + "='" + parms[parm] + "',"; }
    if (parms.givenname === undefined)
        throw "commonMarriage.js: addChildToPage: parms=" + msg;
    if (parms.idcr === undefined)
        parms.idcr      = '';
    
    // add information about the  child as a visible row in the web page. 
    let table           = this;
    let famForm         = document.famForm;

    // ensure that No Children checkbox is cleared and disabled
    // so the user cannot accidentally set it
    if (famForm.NoChildren)
    {
        famForm.NoChildren.checked  = false;
        document.getElementById('NoChildren').disabled  = true;
    }

    // get the IDMR value for the current family
    let idmr            = famForm.idmr.value;

    // get the body of the table of children
    let tableBody       = table.tBodies[0];
    
    // insert new row of information into the web page 
    // at the end of the body section of the table
    let rownum          = tableBody.rows.length + 1;
    parms.rownum        = rownum;
    if (parms.gender == 'male')
        parms.sex       = 0;
    else
    if (parms.gender == 'female')
        parms.sex       = 1;
    else
        parms.sex       = 2;
    let row     = createFromTemplate('child$rownum',
                                     parms,
                                     null,
                                     debug);
    row         = tableBody.appendChild(row);   // add to end of body
    if (parms.idir)
        row.idir        = parms.idir;
    if (parms.idcr)
        row.idcr        = parms.idcr;
    row.changePerson    = changeChild;      // feedback method
    let inputElements   = row.getElementsByTagName("*");
    for(let ei = 0; ei < inputElements.length; ei++)
    {
        let element     = inputElements[ei];
        let nodeName    = element.nodeName.toLowerCase();
        let name;
        if (element.name && element.name.length > 0)
            name        = element.name;
        else
            name        = element.id;
        if (nodeName != 'input' && nodeName != 'button')
            continue;

        let rowNum      = '';
        let namePattern = /^([a-zA-Z_]+)(\d+)$/;
        let pieces      = namePattern.exec(name);
        if (pieces)
        {       // separate column and row
            name        = pieces[1];
            rowNum      = pieces[2];
        }       // separate column and row

        // pop up help balloon if the mouse hovers over a field
        // for more than 2 seconds
        actMouseOverHelp(element);
        element.onkeydown   = keyDown;
        switch(name)
        {       // act on specific fields
            case "CGiven":
            {
                element.onkeydown   = childKeyDown;
                element.checkfunc   = checkName;
                element.onchange    = givenChanged;
                break;
            }

            case "CSurname":
            {
                element.onkeydown   = childKeyDown;
                element.checkfunc   = checkName;
                element.onchange    = change;   // default handler
                break;
            }

            case "Cbirth":
            {
                element.onkeydown   = childKeyDown;
                element.abbrTbl     = MonthAbbrs;
                element.checkfunc   = checkDate;
                element.onchange    = changeCBirth;
                break;
            }

            case "Cbirthsd":
            {
                break;
            }

            case "Cdeath":
            {
                element.onkeydown   = childKeyDown;
                element.abbrTbl     = MonthAbbrs;
                element.checkfunc   = checkDate;
                element.onchange    = dateChanged;
                break;
            }

            case "Cdeathsd":
            {
                break;
            }

            case "editChild":
            {
                editChildButtons.push(element);
                element.onclick     = editChild;
                break;
            }

            case "detChild":
            {
                editChildButtons.push(element);
                element.onclick     = detChild;
            }

            default:
            {
                element.onchange    = change;   // default handler
                break;
            }
        }       // act on specific fields
    
    }       // loop through input tags

    return  row;
}       // function addChildToPage

/************************************************************************
 *  function editEventMar                                               *
 *                                                                      *
 *  This method is called when the user requests to edit                *
 *  information about an event of the current family                    *
 *  that is described by fields within the Family record itself.        *
 *                                                                      *
 *  Parameters:                                                         *
 *      type        the event type, used to distinguish between the     *
 *                  events that are recorded inside the                 *
 *                  Family record                                       *
 *      button      invoking instance of <button>                       *
 *                                                                      *
 ************************************************************************/
function editEventMar(type, button)
{
    let form                = document.famForm;
    if (form)
    {
        let idmr            = form.idmr.value;
        if (idmr && idmr > 0)
        {           // existing family
            let url         = "editEvent.php?idmr=" + idmr +
                                            "&type=" + type;

            switch(type)
            {       // add parameters dependent upon type
                case STYPE_LDSS:
                {   // sealed event
                    url     += "&date=" +
                               encodeURIComponent(form.SealD.value);
                    break;
                }   // sealed event

            }       // add parameters dependent upon type

            let childWindow = openFrame("eventLeft",
                                        url, 
                                        "left");
        }           // existing family
        else
        {           // family needs to be saved first
            pendingButton   = button;
            form.update.click();    // save the family first
        }           // family needs to be saved first
    }       // have form
    else
        alert("editEventMar: unable to get form");
    return true;
}   // function editEventMar

/************************************************************************
 *  function childKeyDown                                               *
 *                                                                      *
 *  Handle key strokes in text input fields in a child line.            *
 *                                                                      *
 *  Parameters:                                                         *
 *      e       W3C compliant browsers pass an event as a parameter     *
 ************************************************************************/
function childKeyDown(e)
{
    if (!e)
    {                           // browser is not W3C compliant
        e   =  window.event;    // IE
    }                           // browser is not W3C compliant
    let key         = e.key;
    let element     = e.target;
    let form        = element.form;

    // hide the help balloon on any keystroke
    if (helpDiv)
    {                           // helpDiv currently displayed
        helpDiv.style.display   = 'none';
        helpDiv         = null; // no longer displayed
    }                           // helpDiv currently displayed
    clearTimeout(helpDelayTimer);   // clear pending help display
    helpDelayTimer      = null;

    // take action based upon code
    switch (key)
    {
        case "F1":              // F1
        {
            displayHelp(this);      // display help page
            return false;       // suppress default action
        }                       // F1

        case "Enter":
        {                       // enter key
            if (element)
            {
                let cell            = element.parentNode;
                let row             = cell.parentNode;
                let body            = row.parentNode;
                let rownum          = row.sectionRowIndex;
                if (rownum < (body.rows.length - 1))
                {               // not the last row
                    rownum++;
                    row             = body.rows[rownum];
                    cell            = row.cells[0];
                    let children= cell.children;
                    for(let ic = 0; ic < children.length; ic++)
                    {           // loop through children of cell
                        let child   = children[ic];
                        if (child.nodeName.toLowerCase() == 'input' &&
                            child.type == 'text')
                        {       // first <input type='text'>
                            child.focus();
                            break;
                        }       // first <input type='text'>
                    }           // loop through children of cell
                }               // not the last row
                else
                    form.addNewChild.click();
            }
            else
                alert("commonMarriage.js: childKeyDown: element is null.");
            return false;       // suppress default action
        }                       // enter key

        case "ArrowUp":
        {                       // arrow up key
            if (element)
            {
                let cell    = element.parentNode;
                let row = cell.parentNode;
                let body    = row.parentNode;
                let rownum  = row.sectionRowIndex;
                if (rownum > 0)
                {               // not the first row
                    rownum--;
                    row     = body.rows[rownum];
                    cell    = row.cells[cell.cellIndex];
                    let children= cell.children;
                    for(let ic = 0; ic < children.length; ic++)
                    {           // loop through children of cell
                        let child   = children[ic];
                        if (child.nodeName.toLowerCase() == 'input' &&
                            child.type == 'text')
                        {       // first <input type='text'>
                            child.focus();
                            break;
                        }       // first <input type='text'>
                    }           // loop through children of cell
                }               // not the first row
            }
            else
                alert("commonMarriage.js: childKeyDown: element is null.");
            return false;       // suppress default action
        }                       // arrow up key

        case "ArrowDown":
        {                       // arrow down key
            if (element)
            {
                let cell    = element.parentNode;
                let row = cell.parentNode;
                let body    = row.parentNode;
                let rownum  = row.sectionRowIndex;
                if (rownum < (body.rows.length - 1))
                {               // not the last row
                    rownum++;
                    row     = body.rows[rownum];
                    cell    = row.cells[cell.cellIndex];
                    let children= cell.children;
                    for(let ic = 0; ic < children.length; ic++)
                    {           // loop through children of cell
                        let child   = children[ic];
                        if (child.nodeName.toLowerCase() == 'input' &&
                            child.type == 'text')
                        {       // first <input type='text'>
                            child.focus();
                            break;
                        }       // first <input type='text'>
                    }           // loop through children of cell
                }               // not the last row
            }
            else
                alert("commonMarriage.js: childKeyDown: element is null.");
            return false;       // suppress default action
        }                       // arrow down key
    }                           // switch on key code

    return;
}       // function childKeyDown
