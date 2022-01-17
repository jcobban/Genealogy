/************************************************************************
 *  DateTime.js                                                         *
 *                                                                      *
 *  Implement the dynamic functionality of the DateTime.html page       *
 *                                                                      *
 *  History:                                                            *
 *      2011/06/24      created                                         *
 *      2021/08/12      ES2015 compliant                                *
 *                                                                      *
 *  Copyright &copy; 2011 James A. Cobban                               *
 ************************************************************************/

window.onload   = onLoad;
var timer       = null;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization after page is loaded                         *
 ************************************************************************/
function onLoad()
{
    update();   // set initial date and time
}       // function onLoad

/************************************************************************
 *  function update                                                     *
 *                                                                      *
 *  This function is called when the timer pops.                        *
 ************************************************************************/
function update()
{
    let time        = new Date();
    let heading     = document.getElementById('time');

    // remove all existing children
    while(heading.firstChild != null)
        heading.removeChild(heading.firstChild);

    let hh          = time.getHours();
    if (hh < 10)
        hh          = '0' + hh;
    let mm          = time.getMinutes();
    if (mm < 10)
        mm          = '0' + mm;
    let ss          = time.getSeconds();
    if (ss < 10)
        ss          = '0' + ss;
    let dateTimeStr = 'Time: ' + hh + ':' + mm + ':' + ss;

    heading.appendChild(document.createTextNode(dateTimeStr));

    // also post a message if required
    let args        = getArgs();    // get args from location search
    heading         = document.getElementById('msg');
    if (args['msg'] !== undefined && heading !== undefined)
    {
        // remove all existing children
        while(heading.firstChild != null)
            heading.removeChild(heading.firstChild);
    
        heading.appendChild(document.createTextNode(args['msg']));
    }       // display posted message

    if (timer)
        clearTimeout(timer);
    timer           = setTimeout(update, 1000);
}       // function update

/************************************************************************
 *  function getArgs                                                    *
 *                                                                      *
 *  Get search arguments from location                                  *
 ************************************************************************/
function getArgs()
{
    let args                = new Object();

    if (location.search.length > 1)
    {                   // examine the search string
        let query           = location.search.substring(1);
        let pairs           = query.split("&");
        for(let i = 0; i < pairs.length; i++)
        {               // loop through all parameters
            let pos         = pairs[i].indexOf('=');
            if (pos == -1)
                continue;
            var argname     = pairs[i].substring(0, pos);
            var value       = pairs[i].substring(pos + 1);
            value           = decodeURIComponent(value);
            value           = value.replace(/\+/g, " ");
            args[argname]   = value;
        }               // loop through all parameters
    }                   // examine the search string
    return args;
}       // function getArgs

