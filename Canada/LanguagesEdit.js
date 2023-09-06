/************************************************************************
 *  LanguagesEdit.js                                                    *
 *                                                                      *
 *  Javascript code to implement dynamic functionality of the           *
 *  page LanguagesEdit.php.                                             *
 *                                                                      *
 *  History:                                                            *
 *      2022/09/27      created                                         *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/

window.addEventListener("load", onLoad);

/************************************************************************
 *  function onLoad                                                     *
 *                                                                      *
 *  Perform initialization of all fields in the form.                   *
 *                                                                      *
 *  Input:                                                              *
 *      this        Window object                                       *
 ************************************************************************/
function onLoad()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    for(var i = 0; i < document.forms.length; i++)
    {       // loop through all forms
        var form    = document.forms[i];

        for(var j = 0; j < form.elements.length; j++)
        {   // loop through all elements of a form
            var element     = form.elements[j];

            element.addEventListener("keydown", keyDown);
            element.addEventListener("change", change); // default handling

            // pop up help balloon if the mouse hovers over a field
            // for more than 2 seconds
            if (element.parentNode.nodeName == 'TD')
            {       // set mouseover on containing cell
                element.parentNode.addEventListener("mouseover", eltMouseOver);
                element.parentNode.addEventListener("onmouseout", eltMouseOut);
            }       // set mouseover on containing cell
            else
            {       // set mouseover on input element itself
                element.addEventListener("mouseover", eltMouseOver);
                element.addEventListener("mouseout", eltMouseOut);
            }       // set mouseover on input element itself

            // an element whose value is passed with the update
            // request to the server is identified by a name= attribute
            // but elements which are used only by this script are
            // identified by an id= attribute
            let name                = element.name;
            if (name.length == 0)
                name                = element.id;
            let prefix              = name.toLowerCase().substr(0,name.length - 2);

            // set up dynamic functionality based on the name of the element
            switch(prefix)
            {       // switch on field prefix
                case "code":
                    element.helpDiv = 'Code';
                    break;

                case "code3":
                    element.helpDiv = 'Code3';
                    break;

                case "name":
                    element.helpDiv = 'Name';
                    break;

                case "nativename":
                    element.helpDiv = 'NativeName';
                    break;

                case "article":
                    element.helpDiv = 'Article';
                    break;

                case "possessive":
                    element.helpDiv = 'Possessive';
                    break;

                case "sorry":
                    element.helpDiv = 'Sorry';
                    break;

            }       // switch on field name
        }           // loop through all elements in the form
    }               // loop through forms in the page

}       // function onLoad

