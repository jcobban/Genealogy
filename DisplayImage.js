/************************************************************************
 *  DisplayImage.js                                                     *
 *                                                                      *
 *  Implement dynamic functionality of page DisplayImage.php.           *
 *                                                                      *
 *  History:                                                            *
 *      2015/04/19      created                                         *
 *      2015/05/01      update value of Image field in the calling      *
 *                      page when user changes image selection through  *
 *                      forward and backward links                      *
 *      2015/05/09      permit invoker to pass name of field to update  *
 *                      with new image file name                        *
 *      2018/02/10      add close frame button                          *
 *      2019/02/10      no longer need to call pageInit                 *
 *      2019/12/12      fix header at top and scroll image such that    *
 *                      the scroll bars are visible all the time        *
 *      2020/06/22      display acknowledgement of owner of image       *
 *                      reenable display image button in caller         *
 *      2021/01/15      use ES2015 syntax                               *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

window.onload               = onLoadImage;
var displayButton           = null;

/************************************************************************
 *  function onLoadImage                                                *
 *                                                                      *
 *  Initialize dynamic functionality of page.                           *
 ************************************************************************/
function onLoadImage()
{
    // activate handling of key strokes in text input fields
    // including support for context specific help
    let element;
    let closeButton;

    for(let i = 0; i < document.forms.length; i++)
    {                   // loop through all forms
        let form                    = document.forms[i];

        for(let j = 0; j < form.elements.length; j++)
        {               // loop through all elements of a form
            element                 = form.elements[j];

            element.onkeydown       = keyDown;

            // an element whose value is passed with the update
            // request to the server is identified by a name= attribute
            // but elements which are used only by this script are
            // identified by an id= attribute
            let name                = element.name;
            if (name.length == 0)
                name                = element.id;

            // set up dynamic functionality based on the name of the element
            switch(name.toLowerCase())
            {
                case 'plus':
                {       // button to increase magnification of image
                    element.onclick = zoomIn;
                    break;
                }       // button to increase magnification of image

                case 'minus':
                {       // button to decrease magnification of image
                    element.onclick = zoomOut;
                    break;
                }       // button to decrease magnification of image

                case "close":
                {
                    closeButton     = this;
                    element.onclick = close;
                    break;
                }       // close

            }           // switch on field name
        }               // loop through all elements in the form
    }                   // loop through forms in the page

    // set size of image viewer
    let header              = document.getElementById('top');
    let headerHeight        = header.offsetHeight;
    let browserHeight       = Math.max(document.documentElement.clientHeight, 
                                       window.innerHeight || 0)
    let viewer              = document.getElementById('viewport');
    viewer.style.height     = (browserHeight - headerHeight) + 'px';

    // activate popup help for forward and backward links
    element                 = document.getElementById('goToPrevImg');
    if (element)
        actMouseOverHelp(element);
    element                 = document.getElementById('goToNextImg');
    if (element)
        actMouseOverHelp(element);

    // update the name of the image in the invoking page
    let opener              = null;
    if (window.frameElement && window.frameElement.opener)
        opener              = window.frameElement.opener;
    else
        opener              = window.opener;
    if (opener)
    {                   // opened from another window
        let fldName         = 'Image';  // default
        if ('fldname' in args)
            fldName         = args.fldname;
        if ('buttonname' in args)
            displayButton   = opener.document.getElementById(args.buttonname);

        let imageElement    = opener.document.getElementById(fldName);
        if ('src' in args)
        {
            if (imageElement)
            {
                if (args.src.substring(0,7) == 'Images/')
                    imageElement.value  = args.src.substring(7);
                else
                    imageElement.value  = args.src;
            }
            else
                alert("DisplayImage.js: " +
                    "opener does not have a field with name '" + fldName + "'");
        }
        else
            alert("DisplayImage.js: missing src= argument");
    }                   // opened from another window
    else
    if (closeButton)
        closeButton.disabled    = true;
}       // function onLoadImage

/************************************************************************
 *  function zoomIn                                                     *
 *                                                                      *
 *  Increase the size of the image element which has the effect of      *
 *  zooming in to the image. This is the click event handler for the    *
 *  "+" button.                                                         *
 *                                                                      *
 *  Input:                                                              *
 *      $this               <button id='plus'>                          *
 ************************************************************************/
function zoomIn()
{
    let image           = document.getElementById('image');
    image.style.height  = 'auto';
    image.style.width   = Math.floor(image.width * 3 / 2) + 'px';
    return false;
}       // function zoomIn

/************************************************************************
 *  function zoomOut                                                    *
 *                                                                      *
 *  Decrease the size of the image element which has the effect of      *
 *  zooming out from the image.  This is the click event handler for    *
 *  the "-" button.                                                     *
 *                                                                      *
 *  Input:                                                              *
 *      $this               <button id='minus'>                         *
 ************************************************************************/
function zoomOut()
{
    let image           = document.getElementById('image');
    image.style.height  = 'auto';
    image.style.width   = Math.floor(image.width * 2 / 3) + 'px';
    return false;
}       // function zoomIn

/************************************************************************
 *  close                                                               *
 *                                                                      *
 *  This method is called when the user clicks on the button to close   *
 *  the dialog.                                                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      this        <button id='Close'>                                 *
 ************************************************************************/
function close()
{
    if (displayButton)
        displayButton.disabled  = false;
    let opener                  = null;
    if (window.frameElement && window.frameElement.opener)
        opener                  = window.frameElement.opener;
    else
        opener                  = window.opener;
    if (opener)
        closeFrame();
}       // function close

