/************************************************************************
 *  DateTime.js                                                         *
 *                                                                      *
 *  Implement the dynamic functionality of the DateTime.html page       *
 *                                                                      *
 *  History:                                                            *
 *      2011/06/24      created                                         *
 *                                                                      *
 *  Copyright &copy; 2011 James A. DateTime                             *
 ************************************************************************/

window.onload   = onLoad;
var timer   = null;

/************************************************************************
 *  onLoad                                                              *
 *                                                                      *
 *  Perform initialization after page is loaded                         *
 ************************************************************************/
function onLoad()
{
    update();   // set initial date and time
}       // onLoad

/************************************************************************
 *  update                                                              *
 *                                                                      *
 *  This function is called when the timer pops.                        *
 ************************************************************************/
function update()
{
    var time    = new Date();
    var heading = document.getElementById('time');

    // remove all existing children
    while(heading.firstChild != null)
    heading.removeChild(heading.firstChild);

    var hh  = time.getHours();
    if (hh < 10)
    hh  = '0' + hh;
    var mm  = time.getMinutes();
    if (mm < 10)
    mm  = '0' + mm;
    var ss  = time.getSeconds();
    if (ss < 10)
    ss  = '0' + ss;
    var dateTimeStr = 'Time: ' + hh + ':' + mm + ':' + ss;

    heading.appendChild(document.createTextNode(dateTimeStr));

    // also post a message if required
    var args    = getArgs();    // get args from location search
    heading = document.getElementById('msg');
    if (args['msg'] !== undefined && heading !== undefined)
    {
    // remove all existing children
    while(heading.firstChild != null)
        heading.removeChild(heading.firstChild);

    heading.appendChild(document.createTextNode(args['msg']));
    }       // display posted message

    timer   = setTimeout(update, 1000);
}

/************************************************************************
 *  getArgs                                                             *
 *                                                                      *
 *  Get search arguments from location                                  *
 ************************************************************************/
function getArgs()
{
    var args    = new Object();

    if (location.search.length > 1)
    {
    var query   = location.search.substring(1);
    var pairs   = query.split("&");
    for(var i = 0; i < pairs.length; i++)
    {
        var pos = pairs[i].indexOf('=');
        if (pos == -1)
        continue;
        var argname = pairs[i].substring(0, pos);
        var value   = pairs[i].substring(pos + 1);
        value   = decodeURIComponent(value);
        value   = value.replace(/\+/g, " ");
        args[argname]   = value;
    }   // loop through all parameters
    }       // examine the search string
    return args;
}       // getArgs
