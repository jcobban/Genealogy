/************************************************************************
 *  uploadGedcom.js														*
 *																		*
 *  Implement the dynamic functionality of the uploadGedcom page        *
 *  which uploads the contents of a GEDCOM 5.5 family tree document     *
 *  and merges it.                                                      *
 *																		*
 *  History:															*
 *		2018/11/28		created											*
 *		2019/02/10      no longer need to call pageInit                 *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *																		*
 *  Copyright &copy; 2021 James A. Cobban								*
 ************************************************************************/

var reader      = null;         // instance of FileReader
var gedname     = '';           // gedcom name
var lines       = [];           // the contents file to upload
var next        = 0;            // next index in array lines

window.onload	= onLoad;

/************************************************************************
 *  function onLoad														*
 *																		*
 *  Perform initialization after page is loaded.  This page is			*
 *  frequently invoked by the signon script.  If this is the case then	*
 *  the page that invoked the signon script should be refreshed to		*
 *  reflect the change in user status.									*
 *																		*
 ************************************************************************/
function onLoad()
{
    if (window.File && window.FileReader && window.FileList && window.Blob)
    {
        reader                  = new FileReader();
        reader.onload           = processFile;
        var statusElt           = document.getElementById('status');
        var p                   = document.createElement("p");
        p.innerHTML             = "Congratulations, your Browser, " +
                                    navigator.userAgent +
                                    ", supports the W3C draft File API.";
        statusElt.appendChild(p);
    }
    else
    {
        var browser             = navigator.userAgent;
        if ((navigator.userAgent.indexOf("Opera") || 
            navigator.userAgent.indexOf('OPR')) != -1 ) 
        {
            browser             = 'Opera: ' ;
        }
        else if(navigator.userAgent.indexOf("Chrome") != -1 )
        {
            browser		        = 'Chrome: ' + browser;
        }
        else if(navigator.userAgent.indexOf("Safari") != -1)
        {
            browser		        = 'Safari: ' + browser;
        }
        else if(navigator.userAgent.indexOf("Firefox") != -1 ) 
        {
            browser		        = 'Firefox: ' + browser;
        }
        else 
        if ((navigator.userAgent.indexOf("MSIE") != -1 ) ||
            (!!document.documentMode == true )) //IF IE > 10
        {
            browser		        = 'IE: ' + browser; 
        }  
        
        var statusElt           = document.getElementById('status');
        var p                   = document.createElement("p");
        p.className             = "message";
        p.innerHTML             = "Your Browser, " + browser +
            ", does not support W3C draft File, FileHeader, FileList, and Blob.  It is recommended to use an open non-proprietary browser such as Chrome or Firefox.";
        statusElt.appendChild(p);
    }

    var fileElt             = document.getElementById('fileItem');
    fileElt.onchange        = pullfiles;
}		// function onLoad

/************************************************************************
 *  function pullfiles												    *
 *																		*
 *  This method is called when the user selects a file to upload.       *
 *																		*
 *	Input:																*
 *		this points at <input type="file">								*
 ************************************************************************/
function pullfiles()
{
    var fileElt             = this;
    var file                = fileElt.value;
    if (fileElt.files)
        file                = fileElt.files[0];      // instance of File
    var chosenElt           = document.getElementById('chosen');
    chosenElt.innerHTML     = file.name;
    gedname                 = file.name.substring(0, file.name.length - 4);
    if (reader)
        reader.readAsText(file);    // new functionality
    else
        fileElt.form.submit();      // legacy functionality
}           // function pullfiles

/************************************************************************
 *  function processFile											    *
 *																		*
 *  This method is called when the requested file has been completely   *
 *  read.                                                               *
 ************************************************************************/
function processFile(evt)
{
    lines                   = evt.target.result.split(/[\r\n]+/);
    next                    = 0;

    getNextTag();           // process first tag
}           // function processFile

/************************************************************************
 *  function getNextTag 											    *
 *																		*
 *  This method is called to read one GEDCOM top level tag from         *
 *  the input file.                                                     *
 ************************************************************************/
function getNextTag()
{
    var regex                   = /^\s*(\d+)\s+(@\w+@|)\s*(\w+)\s*(.*)$/;
    var statusElt               = document.getElementById('status');
    var currentElt              = document.getElementById('current');
    var useridElt               = document.getElementById('userid');
    var userid                  = useridElt.value;
    var tag                     = [];
    var posted                  = false;
    for(var i = next; i < lines.length; i++)
    {
        var line                = lines[i];
        var matches             = regex.exec(line);
        if (matches)
        {                       // valid syntax
            var level           = matches[1];
            var xrefId          = matches[2];
            var tagname         = matches[3];
            var parms           = matches[4];
            if (level == 0)
            {                   // encountered level 0 tag
                if (tag.length > 0)
                {               // have data to send to server
                    currentElt.innerHTML    = 'Processing: ' + tag[0];
                    next        = i;
                    var parms   = {'userid'     : userid,
                                   'gedname'   : gedname,
                                   'lines'     : JSON.stringify(tag)};
                    HTTP.post("gedcomAddXml.php",
                			  parms,
                			  gotAdd,
                			  noAdd);
                    tag         = [];
                    posted      = true;
                }               // have data to send to server
            }                   // encountered level 0 tag
            tag.push(line);
        }                       // valid syntax
        else
        if (line.length > 0)
        {                       // invalid syntax                       
            var p               = document.createElement("p");
            p.innerHTML         = '<span class="message">failed</span>: ' + 
                                    line;
            statusElt.appendChild(p);
        }                       // invalid syntax
        if (posted)
            break;
    }

    if (i>= lines.length)
    {                           // at end of file
        if (tag.length > 0)
        {                       // have data to send to server
            currentElt.innerHTML    = 'Processing: ' + tag[0];
            next        = i;
            var parms   = {'userid'     : userid,
                            'gedname'   : gedname,
                            'lines'     : JSON.stringify(tag)};
            HTTP.post("gedcomAddXml.php",
        			  parms,
        			  gotAdd,
        			  noAdd);
            tag         = [];
            posted      = true;
            var p               = document.createElement("p");
            p.innerHTML         = 'Sent last tag';
            statusElt.appendChild(p);
        }                       // have data to send to server
    }                           // at end of file
}           // function getNextTag

/************************************************************************
 *  function gotAdd  													*
 *																		*
 *  This method is called when the XML file representing				*
 *  the processing of a GEDCOM tag is received.							*
 *																		*
 *  Input:																*
 *		xmlDoc		XML response file describing the deletion           *
 *		            of the message                                      *
 ************************************************************************/
function gotAdd(xmlDoc)
{
    var	root	= xmlDoc.documentElement;
    if (root)
    {                               // valid XML
        var statusElt               = document.getElementById('status');
        var p                       = document.createElement("p");
        p.innerHTML                 = new XMLSerializer().serializeToString(root).replace(/\&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        statusElt.appendChild(p);
    }                               // valid XML
    else
        alert("uploadGedcom: gotAdd: " + new XMLSerializer().serializeToString(xmlDoc));

    if (next < lines.length)
        getNextTag();               // process next tag
}		// function gotAdd

/************************************************************************
 *  function noAdd													    *
 *																		*
 *  This method is called if there is no script to process the          *
 *  GEDCOM tag.                                                         *
 ************************************************************************/
function noAdd()
{
    alert("uploadGedcom: noAdd: script gedcomAddXml.php not found on server");
}		// function noAdd
