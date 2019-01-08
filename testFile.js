/************************************************************************
 *  genealogy.js														*
 *																		*
 *  Implement the dynamic functionality of the genealogy.html page		*
 *																		*
 *  History:															*
 *		2011/06/24		created											*
 *		2012/01/08		can only change location of page from same host	*
 *		2012/05/26		use location.reload() to refresh page that		*
 *						invoked signon.									*
 *		2013/07/30		defer facebook initialization until after load	*
 *		2015/07/28		close comment blocks							*
 *																		*
 *  Copyright &copy; 2015 James A. Cobban								*
 ************************************************************************/

window.onload	= onLoad;

/************************************************************************
 *  onLoad																*
 *																		*
 *  Perform initialization after page is loaded.  This page is			*
 *  frequently invoked by the signon script.  If this is the case then	*
 *  the page that invoked the signon script should be refreshed to		*
 *  reflect the change in user status.									*
 *																		*
 ************************************************************************/
var reader;                 // instance of FileReader
var gedname;                // gedcom name
function onLoad()
{
    pageInit();
    if (window.File && window.FileReader && window.FileList && window.Blob)
    {
        reader              = new FileReader();
        reader.onload       = processFile;
    }
    else
        alert("Browser does not support W3C draft File, FileHeader, FileList, and Blob");

    var fileElt             = document.getElementById('fileItem');
    fileElt.onchange        = pullfiles;
}		// onLoad

function pullfiles()
{
    var fileElt             = this;
    var file                = fileElt.files[0];      // instance of File
    var chosenElt           = document.getElementById('chosen');
    chosenElt.innerHTML     = file.name;
    gedname                 = file.name.substring(0, file.name.length - 4);
    reader.readAsText(file);
}           // function pullfiles

function processFile(evt)
{
    var regex               = /^\s*(\d+)\s+(@\w+@|)\s*(\w+)\s*(.*)$/;
    var useridElt           = document.getElementById('userid');
    var userid              = useridElt.value;
    var statusElt           = document.getElementById('status');
    var lines               = evt.target.result.split(/[\r\n]+/);
    var xml                 = "";
    for(var i = 0; i < lines.length; i++)
    {
        var line            = lines[i];
        var matches         = regex.exec(line);
        if (matches)
        {
            var level           = matches[1];
            var xrefId          = matches[2];
            var tagname         = matches[3];
            var parms           = matches[4];
            if (level == 0)
            {
                if (xml.length > 0)
                {
                    xml         = "<tag>\n" +
                                "    <userid>" + userid + "</userid>\n" +
                                "    <gedname>" + gedname + "</gedname>\n" +
                                "    <input>\n" + xml +
                                    "</input>\n  </tag>\n";
                    var xp      = document.createElement("p");
                    xp.innerHTML= xml.replace(/\&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g, '<br/>');
                    statusElt.appendChild(xp);
                    xml         = '';
                }
            }
            xml                 += "<line>" + line + "</line>\n";
        }
        else
        {
            var p           = document.createElement("p");
            p.innerHTML     = 'failed: ' + line;
            statusElt.appendChild(p);
        }
    }
}
