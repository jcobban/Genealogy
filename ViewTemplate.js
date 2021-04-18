/************************************************************************
 *  ViewTemplate.js                                                     *
 *                                                                      *
 *  Implement the dynamic functionality of the view template page       *
 *                                                                      *
 *  History:                                                            *
 *      2019/04/18      created                                         *
 *      2020/05/02      hide new right-hand column                      *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

window.onload   = onLoad;

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization after page is loaded.                        *
 *                                                                      *
 ************************************************************************/
function onLoad()
{
    var listOfPopups    = document.getElementsByClassName('balloon');
    for (var vi = 0; vi < listOfPopups.length; vi++)
    {           // loop through popups
        var popup                   = listOfPopups[vi];
        popup.style.position        = 'static';
        popup.style.marginTop       = '5px';
        popup.style.display         = 'block';
    }           // loop through popups

    var listOfHidden    = document.getElementsByClassName('hidden');
    for (var vi = 0; vi < listOfHidden.length; vi++)
    {           // loop through popups
        var hidden                  = listOfHidden[vi];
        if (hidden.id != "hideMsgTemplate")
            continue;
        hidden.style.position       = 'static';
        hidden.style.marginTop      = '5px';
        hidden.style.display        = 'block';
    }           // loop through popups

    hideRightColumn();
}       // function onLoad
