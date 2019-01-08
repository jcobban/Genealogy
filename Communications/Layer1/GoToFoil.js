
var	foils	= ["Title.html",
		"Foil2.html",
		"Foil4.html",
		"Foil6.html",
		"Foil7.html",
		"Foil8.html",
		"Foil9.html",
		"Foil10.html",
		"Foil11.html"];

GoToFoil(index)
{
	if (index < 0) index = 0;
	if (index >= foils.length) index = foils.length - 1;
	prev	= index - 1;
	if (prev < 0) prev = 0;
	next	= index + 1;
	if (next >= foils.length) next = foils.length - 1;
	parent.notes.location.href="Foil2Notes.html";
	parent.navbar.document.prev.href="Title.html";
	parent.navbar.document.next.href="Foil3.html";
}
