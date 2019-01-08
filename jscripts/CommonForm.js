/************************************************************************
 *  CommonForm.js														*
 *																		*
 *  This file contains the JavaScript functions that implement the		*
 *  dynamic functionality of the forms used to enter genealogical		*
 *  data.  This file is shared between all forms because most			*
 *  of the functionality is shared by many relevant records.			*
 *																		*
 *  History:															*
 *		2010/12/09		enable submit button on changes					*
 *		2011/01/02		issue alert if change not called on a form		*
 *						element.										*
 *		2011/04/03		add additional relationships (Servant, ...)		*
 *		2011/04/06		add "Christian Disciple" abbreviation to rlgns	*
 *		2011/04/07		make abbreviation lookup case insensitive		*
 *	    2011/05/17	    abbreviation lookup expands all matching words	*	
 *		2011/05/21		add table of location abbreviations				*
 *						add additional abbreviations to some tables		*
 *		2011/06/05		add "Daughter-in-Law" abbreviation				*
 *		2011/06/07		add "Congregationalist" abb ireviations			*
 *						do not fold non expanded words to lower case	*
 *		2011/07/23		add "or" to location lookup list				*
 *						do not include trailing punctuation mark in		*
 *						key lookup										*
 *		2011/09/04		add more month abbreviations					*
 *						add more birthplace abbreviations				*
 *						add more given name abbreviations				*
 *		2011/10/09		add in abbreviation tables from					*
 *						/database/CensusForm.js							*
 *						correct abbreviations for nephew & niece		*
 *		2011/10/16		add table to standardize representation of		*
 *						fractional year ages							*
 *		2011/12/17		add relationship abbreviations					*
 *		2012/04/17		add 1 1/2 stories abbreviation					*
 *						change capitalization algorithm to support		*
 *								acronyms with periods					*
 *						reorder code in changeElt to be more efficient	*
 *								and easier to understand				*
 *		2012/10/23		in function change invoke element.changefunc	*
 *						if it is defined.								*
 *		2012/10/31		move input element validation functions here	*
 *		2012/11/05		make checkDate function fussier					*
 *		2012/11/12		no longer necessary to enable submit button		*
 *						on field change.								*
 *						minor change to function expAbbr.				*
 *						minor change to function capitalize				*
 *						more rigorous logic in function changeElt		*
 *						add abbreviations for death cause words			*
 *		2013/01/08		add abbreviations for location prepositions		*
 *		2013/01/14		do not flag dates starting with between or from	*
 *						as invalid										*
 *		2013/01/20		expand characters permitted in name				*
 *		2013/01/29		add more abbreviations for death cause			*
 *		2013/02/19		add more abbreviations for occupation			*
 *		2013/03/23		add method getSortDate							*
 *		2013/03/26		add more abbreviations							*
 *		2013/05/30		add more abbreviations							*
 *		2013/06/10		correct spelling of "inflammation" in causes	*
 *		2013/06/28		use RelAbbrs also for informant relation field	*
 *		2013/08/20		add functions for cell movement in a table		*
 *		2013/08/24		add abbreviations for addresses					*
 *		2013/08/26		change function exprAbbr so it capitalizes		*
 *						the first letter even if not first character	*
 *		2013/09/04		move shared function columnClick to here to		*
 *						ensure common implementation for all tables		*
 *		2013/10/10		add abbreviations for Commercial Traveller occ.	*
 *		2013/10/19		fix bug in capitalize algorithm					*
 *		2013/11/01		fix columnClick to set both header and footer	*
 *						column text and to support any contents in		*
 *						the header and footer cell for a column			*
 *		2013/12/02		more location abbreviations, do not				*
 *						capitalize 'the' or 'through' ...				*
 *						or time periods									*
 *		2013/12/08		accept broader range of age values				*
 *		2013/12/14		add separate time periods to AgeAbbrs			*
 *		2013/12/18		allow zero length value in year field			*
 *		2014/01/16		more entries in cause of death and age			*
 *		2014/01/21		do not include certain opening punctuation		*
 *						marks in the lookup for abbreviations			*
 *						allow ampersand in names						*
 *		2014/02/18		do not include possessive ending in expansion	*
 *						of abbreviations								*
 *		2014/02/27		function givenChanged and its support tables	*
 *						moved here from editIndivid.js so it can be		*
 *						used by commonMarriage.js						*
 *		2014/03/11		expand list of male given names					*
 *		2014/03/24		restore table keyboard handling when restoring	*
 *						hidden table column								*
 *		2014/04/24		flags explicit value '0' as an invalid number	*
 *		2014/05/26		add more female names							*
 *		2014/09/20		permit ½ as a digit in ages						*
 *		2014/09/22		change some preferred religion expansions		*
 *		2014/10/06		add method checkURL								*
 *		2014/10/16		add more female given names						*
 *		2014/11/22		add more connecting words for occupations		*
 *						add more male given names						*
 *		2015/01/26		function chkDate did not handle date ranges		*
 *		2015/02/04		add some religion abbreviations					*
 *		2015/03/17		add some French location abbreciations			*
 *		2015/04/26		in givenChanged treat given names ending in		*
 *						'a' as female									*
 *		2015/06/02		permit accented western european letters in		*
 *						names, locations, and occupations				*
 *		2015/07/08		move columnWiden function here					*
 *						move linkMouseOver function here				*
 *						move linkMouseOut function here					*
 *		2015/08/12		add surnamePartAbbrs							*
 *		2015/09/23		if the given name or surname are changed on		*
 *						an editIndivid.php page with an empty title		*
 *						then the title is changed on the fly			*
 *		2016/03/01		in date fields ensure there is a space between	*
 *						a letter and a digit or a digit and a letter	*
 *		2016/05/31		left and right arrows in table revert to		*
 *						default movement within a cell rather than		*
 *						moving between cells, which is supported by tab	*
 *		2017/11/01		do not flag '7th Day Adventist' as invalid in	*
 *						checkName										*
 *		2018/01/09		add fractions 1/3 and 2/3						*
 *		2018/01/19		ensure changed fields displayed with black		*
 *						text											*
 *		2018/02/09		add function setErrorFlag to consolidate		*
 *						setting class name by whether or not the		*
 *						field value is valid							*
 *		2018/02/23		correct validation of half-integral numbers		*
 *						strings cannot be compared to numbers in JS		*
 *		2018/04/15		limit characters that can be entered in Family	*
 *		2018/05/09		change religion abbreviations to spell "Church"	*
 *		2018/05/10		add method checkPositiveNumber					*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

/************************************************************************
 *		RelAbbrs														*
 *																		*
 *  Table for expanding abbreviations for Relationships					*
 *  This table is used in two different contexts:						*
 *		- for the relation to head of household column in censuses		*
 *		- for the relation of the informant to the deceased in a death	*
 *		  record or to the child in a birth record						*
 ************************************************************************/
var	RelAbbrs = {
				"A" 		: "Aunt",
				"Ad" 		: "Adopted-Daughter",
				"As" 		: "Adopted-Son",
				"B" 		: "Boarder",
				"Bl" 		: "Brother-in-Law",
				"Bo" 		: "Boarder",
				"Br" 		: "Brother",
				"C" 		: "Cousin",
				"D" 		: "Daughter",
				"Dl" 		: "Daughter-in-Law",
				"Do" 		: "Domestic",
				"E" 		: "Employee",
				"F" 		: "Father",
				"Fl" 		: "Father-in-Law",
				"Gd" 		: "Grand-Daughter",
				"Gf" 		: "Grand-Father",
				"Gm" 		: "Grand-Mother",
				"Gs" 		: "Grand-Son",
				"H" 		: "Head",
				"Hu" 		: "Husband",
				"L" 		: "Lodger",
				"La" 		: "Laborer",
				"M" 		: "Mother",
				"Mil" 		: "Mother-in-Law",
				"Ml" 		: "Mother-in-Law",
				"N" 		: "Nephew",
				"Ne" 		: "Nephew",
				"Ni" 		: "Niece",
				"P" 		: "Physician",
				"S" 		: "Son",
				"Sd" 		: "Step-Daughter",
				"Se" 		: "Servant",
				"Si" 		: "Sister",
				"Sil" 		: "Sister-in-Law",
				"Sl" 		: "Sister-in-Law",
				"So" 		: "Son",
				"Sol" 		: "Son-in-Law",
				"Ss" 		: "Step-Son",
				"St" 		: "Servant",
				"U" 		: "Uncle",
				"Ut" 		: "Undertaker",
				"W" 		: "Wife",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		BpAbbrs															*
 *																		*
 *  Table for expanding abbreviations for birth places					*
 ************************************************************************/
var	BpAbbrs = {
				"1/4" 		: "¼",
				"1/3" 		: "&#8531;",
				"1/2" 		: "½",
				"2/3" 		: "&#8532;",
				"3/4" 		: "¾",
				"Ab" 		: "Alberta",
				"Au" 		: "Australia",
				"Bc" 		: "British Columbia",
				"Ca" 		: "Canada",
				"Ce" 		: "Canada East",
				"Ci" 		: "Channel Isles",
				"Con" 		: "con",
				"Cw" 		: "Canada West",
				"Dk" 		: "Denmark",
				"E" 		: "England",
				"En" 		: "England",
				"F" 		: "France",
				"Fr" 		: "France",
				"G" 		: "Germany",
				"Ge" 		: "Germany",
				"Gi" 		: "Gibraltar",
				"Gu" 		: "Guernsey",
				"H" 		: "Holland",
				"I" 		: "Ireland",
				"Ia" 		: "Iowa, USA",
				"Ir" 		: "Ireland",
				"In" 		: "India",
				"Im" 		: "Isle of Man",
				"Lc" 		: "Lower Canada",
				"Lmt" 		: "Lambton",
				"Lot" 		: "lot",
				"Mb" 		: "Manitoba",
				"Mi" 		: "Michigan, USA",
				"Msx" 		: "Middlesex",
				"Nb" 		: "New Brunswick",
				"Nj" 		: "New Jersey, USA",
				"Nl" 		: "Newfoundland",
				"Ns" 		: "Nova Scotia",
				"Nw" 		: "North West Territories",
				"Nwt" 		: "North West Territories",
				"Ny" 		: "New York, USA",
				"Nz" 		: "New Zealand",
				"O" 		: "Ontario",
				"Of" 		: "of",
				"Oh" 		: "Ohio, USA",
				"On" 		: "on",
				"Ont" 		: "Ontario",
				"Pa" 		: "Pennsylvania, USA",
				"Pi" 		: "P.E.I.",
				"Pei" 		: "P.E.I.",
				"Po" 		: "Poland",
				"Pr" 		: "Prussia",
				"Qc" 		: "Quebec",
				"Ru" 		: "Russia",
				"S" 		: "Scotland",
				"Sc" 		: "Scotland",
				"Sk" 		: "Saskatchewan",
				"Sw" 		: "Sweden",
				"Sl" 		: "Switzerland",
				"Swi" 		: "Switzerland",
				"Sz" 		: "Switzerland",
				"U" 		: "U. States",
				"Uc" 		: "Upper Canada",
				"Us" 		: "U. States",
				"Usa" 		: "USA",
				"U.s."      : "U. States",
				"W" 		: "Wales",
				"Wi" 		: "West Indies",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		LocAbbrs														*
 *																		*
 *  Table for expanding abbreviations for locations in Canada			*
 *  If changing this table also check BpAbbrs for birth places and		*
 *  AddrAbbrs for the addressin census records							*
 ************************************************************************/
var	LocAbbrs = {
				"1/4" 		: "¼",
				"1/3" 		: "&#8531;",
				"1/2" 		: "½",
				"2/3" 		: "&#8532;",
				"3/4" 		: "¾",
				"1rn" 		: "1RN",
				"2rn" 		: "2RN",
				"3rn" 		: "3RN",
				"4rn" 		: "4RN",
				"5rn" 		: "5RN",
				"1rs" 		: "1RS",
				"2rs" 		: "2RS",
				"3rs" 		: "3RS",
				"4rs" 		: "4RS",
				"5rs" 		: "5RS",
				"r1n" 		: "1RN",
				"r2n" 		: "2RN",
				"r3n" 		: "3RN",
				"r4n" 		: "4RN",
				"r5n" 		: "5RN",
				"r1s" 		: "1RS",
				"r2s" 		: "2RS",
				"r3s" 		: "3RS",
				"r4s" 		: "4RS",
				"r5s" 		: "5RS",
				"Ab" 		: "Alberta",
				"And" 		: "and",
				"At" 		: "at",
				"Au" 		: "au",
				"Bc" 		: "British Columbia",
				"By" 		: "by",
				"Ca" 		: "Canada",
				"Ce" 		: "Canada East",
				"Con" 		: "con",
				"Cor" 		: "cor",
				"Cw" 		: "Canada West",
				"De" 		: "de",
				"Elg" 		: "Elgin",
				"Enroute"   : "enroute",
				"En" 		: "en",
				"En-route"  : "en-route",
				"Esx" 		: "Essex",
				"Et" 		: "et",
				"For" 		: "for",
				"From"      : "from",
				"In" 		: "in",
				"Lc" 		: "Lower Canada",
				"Lmt" 		: "Lambton", 
				"Lot" 		: "lot",
				"Mb" 		: "Manitoba",
				"Msx" 		: "Middlesex",
				"Nb" 		: "New Brunswick",
				"Ne" 		: "NE",
				"Nl" 		: "Newfoundland",
				"Ns" 		: "Nova Scotia",
				"Nt" 		: "N.W.T.",
				"Nw" 		: "NW",
				"Nw" 		: "NW",
				"Nwt" 		: "N.W.T.",
				"Of" 		: "of",
				"On" 		: "on",
				"Or" 		: "or",
				"P.o."      : "P.O.",
				"Pi" 		: "P.E.I.",
				"Pei" 		: "P.E.I.",
				"Pt" 		: "pt",
				"Qc" 		: "Quebec",
				"Se" 		: "SE",
				"Sk" 		: "Saskatchewan",
				"Sw" 		: "SW",
				"The" 		: "the",
				"Through"   : "through",
				"To" 		: "to",
				"Uc" 		: "Upper Canada",
				"Us" 		: "USA",
				"Usa" 		: "USA",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		RlgnAbbrs														*
 *																		*
 *  Table for expanding abbreviations for religions						*
 ************************************************************************/
var	RlgnAbbrs = {
				"7" 		: "7th Day Adventist",
				"A" 		: "Anglican",
				"An" 		: "Anglican",
				"B" 		: "Baptist",
				"Bc" 		: "Bible Christian",
				"C" 		: "Roman Catholic",
				"Calv"      : "Calvinistic Baptist",
				"Cb" 		: "C. Baptist",
				"Ccb" 		: "Close Communion Baptist",
				"Ce" 		: "Church of England",
				"Ch" 		: "Church of England",
				"Chr" 		: "Christian",
				"Ci" 		: "Church of Ireland",
				"Cm" 		: "Canada Methodist",
				"Coe" 		: "Church of England",
				"Con" 		: "Congregationalist",
				"Cong"      : "Congregationalist",
				"Cov" 		: "Covenanted Baptist",
				"Cp" 		: "Canada Presbyterian",
				"Cs" 		: "Church of Scotland",
				"D" 		: "Disciple",
				"Db" 		: "Disciple Baptist",
				"E" 		: "Church of England",
				"Em" 		: "Methodist Episcopal",
				"Ep" 		: "Episcopal",
				"Ev" 		: "Evangelist",
				"F" 		: "Friends",
				"Fb" 		: "Free Will Baptist",
				"Fc" 		: "Free Church [of Scotland]",
				"Fw" 		: "Free Will Baptist",
				"Fwb" 		: "Free Will Baptist",
				"L" 		: "Lutheran",
				"Lds" 		: "Latter Day Saints",
				"Lu" 		: "Lutheran",
				"M" 		: "Methodist",
				"Me" 		: "Methodist Episcopal",
				"Men" 		: "Mennonite",
				"Mep" 		: "Methodist Episcopal",
				"Ncm" 		: "New Connexion Methodist",
				"New" 		: "New Church",
				"O" 		: "Old School Baptist",
				"Of" 		: "of",
				"Osb" 		: "Old School Baptist",
				"P" 		: "Presbyterian",
				"Pb" 		: "P. Baptist",
				"Pl" 		: "Plymouth Brethren",
				"Pm" 		: "Primitive Methodist",
				"Pn" 		: "Presbyterian",
				"Pr" 		: "Presbyterian",
				"Pro" 		: "Protestant",
				"Pt" 		: "Protestant",
				"Q" 		: "Quaker (Friends)",
				"R" 		: "Roman Catholic",
				"Rb" 		: "Regular Baptist",
				"Rc" 		: "Roman Catholic",
				"Reg" 		: "Regular Baptist",
				"Sa" 		: "Salvation Army",
				"Sw" 		: "Swedenborgian (New Church)",
				"U" 		: "Unitarian",
				"The" 		: "the",
				"Uc" 		: "United Church",
				"Univ"      : "Universalist",
				"Up" 		: "United Presbyterian",
				"W" 		: "Wesleyan Methodist",
				"Wm" 		: "Wesleyan Methodist",
				"[" 		: "[blank]"
				};


/************************************************************************
 *		OccAbbrs														*
 *																		*
 *  Table for expanding abbreviations for occupations					*
 ************************************************************************/
var	OccAbbrs = {
				"At" 		: "at",
				"And" 		: "and",
				"App" 		: "Apprentice",
				"B" 		: "Blacksmith",
				"Bk" 		: "Bookkeeper",
				"Brother"   : "brother",
				"C" 		: "Carpenter",
				"Clk" 		: "Clerk",
				"Cm" 		: "Cabinet Maker",
				"Com" 		: "Commercial",
				"D" 		: "Dressmaker",
				"Dg" 		: "Dry Goods",
				"Dm" 		: "Dressmaker",
				"Do" 		: "Domestic",
				"En" 		: "Engineer",
				"F" 		: "Farmer",
				"Father"    : "father",
				"Fl" 		: "Farm Laborer",
				"For" 		: "for",
				"F's" 		: "Farmer's",
				"Fs" 		: "Farmer's Son",
				"G" 		: "Gardener",
				"Gen" 		: "General",
				"Har" 		: "Harness",
				"Her" 		: "her",
				"His" 		: "his",
				"Hst" 		: "High School Teacher",
				"In" 		: "in",
				"K" 		: "Keeper",
				"L" 		: "Laborer",
				"M" 		: "Maker",
				"Ma" 		: "Mason",
				"Mac" 		: "Machinist",
				"Md" 		: "Medical Doctor",
				"Mec" 		: "Mechanic",
				"Mfr" 		: "Manufacturer",
				"Mgr" 		: "Manager",
				"Mil" 		: "Miller",
				"Mili"      : "Miliner",
				"Min" 		: "Minister",
				"Mt" 		: "Merchant",
				"Ng" 		: "N.G.",
				"Nu" 		: "Nurse",
				"Of" 		: "of",
				"On" 		: "on",
				"Or" 		: "or",
				"P" 		: "Physician",
				"Pst" 		: "Public School Teacher",
				"Pvt" 		: "Private",
				"Ret" 		: "retired",
				"Retired"   : "retired",
				"Rr" 		: "Railroad",
				"Ry" 		: "Railway",
				"Se" 		: "Servant",
				"Sic" 		: "sic",
				"Sh" 		: "Shoemaker",
				"Sm" 		: "Stone Mason",
				"St" 		: "School Teacher",
				"Steno"     : "Stenographer",
				"Stu" 		: "Student",
				"T" 		: "Teacher",
				"Tai" 		: "Tailor",
				"Tan" 		: "Tanner",
				"Team"      : "Teamster",
				"Th" 		: "Thresher",
				"To" 		: "to",
				"Tr" 		: "Traveller",
				"Ts" 		: "Tinsmith",
				"U" 		: "Undertaker",
				"Un" 		: "Undertaker",
				"Up" 		: "Upholsterer",
				"Vs" 		: "Veterinary Surgeon",
				"W" 		: "Weaver",
				"With"      : "with",
				"Y" 		: "Yeoman",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		SurnAbbrs														*
 *																		*
 *  Table for expanding abbreviations for surnames                      *
 ************************************************************************/
var	SurnAbbrs = {
				"B" 		: "Brown",
				"Came"      : "Cameron",
				"C" 		: "Campbell",
				"Cl" 		: "Clark",
				"G" 		: "Graham",
				"H" 		: "Harris",
				"He" 		: "Henderson",
				"J" 		: "Johnston",
				"Mca" 		: "McArthur",
				"Mcc" 		: "McCallum",
				"Mcd" 		: "McDonald",
				"Mci" 		: "McIntyre",
				"Mckel"     : "McKellar",
				"Mck" 		: "McKenzie",
				"Mcl" 		: "McLean",
				"Mcr" 		: "McRae",
				"McA" 		: "McArthur",
				"McC" 		: "McCallum",
				"McD" 		: "McDonald",
				"McI" 		: "McIntyre",
				"McKel"     : "McKellar",
				"McK" 		: "McKenzie",
				"McL" 		: "McLean",
				"McR" 		: "McRae",
				"Mo" 		: "Moore",
				"P" 		: "Patterson",
				"R" 		: "Robinson",
				"Sc" 		: "Scott",
				"Sm" 		: "Smith",
				"Su" 		: "Sutherland",
				"Th" 		: "Thomas",
				"Wa" 		: "Walker",
				"Wi" 		: "Wilson",
				"Z" 		: "Zavitz",
				"" 		    : "[blank]",
				"[" 		: "[blank]",
				"[d" 		: "[delete]",
				"[D" 		: "[delete]"
				};

/************************************************************************
 *		GivnAbbrs														*
 *																		*
 *  Table for expanding abbreviations for given names					*
 ************************************************************************/
var	GivnAbbrs = {
				"A" 		: "Annie",
				"Al" 		: "Alexander",
				"Ad" 		: "Archibald",
				"An" 		: "Ann",
				"Ar" 		: "Archibald",
				"As" 		: "Agnes",
				"At" 		: "Albert",
				"Aw" 		: "Andrew",
				"B" 		: "Benjamin",
				"Ca" 		: "Catherine",
				"Ch" 		: "Charles",
				"Da" 		: "David",
				"Do" 		: "Donald",
				"Du" 		: "Duncan",
				"E" 		: "Elizabeth",
				"Ed" 		: "Edward",
				"El" 		: "Ellen",
				"Es" 		: "Elisabeth",
				"Em" 		: "Emma",
				"Ez" 		: "Eliza",
				"F" 		: "Francis",
				"Fk" 		: "Frederick",
				"Fl" 		: "Florence",
				"Fn" 		: "Frank",
				"G" 		: "George",
				"Geo" 		: "George",
				"H" 		: "Henry",
				"Hu" 		: "Hugh",
				"Hy" 		: "Henry",
				"I" 		: "Isabella",
				"J" 		: "John",
				"Ja" 		: "Jane",
				"Jas" 		: "James",
				"Jno" 		: "John",
				"Jos" 		: "Joseph",
				"Js" 		: "James",
				"Jt" 		: "Janet",
				"M" 		: "Mary",
				"Mg" 		: "Maggie",
				"Mi" 		: "Minnie",
				"Mt" 		: "Margaret",
				"Mth" 		: "Martha",
				"N" 		: "Nancy",
				"P" 		: "Peter",
				"R" 		: "Robert",
				"Ra" 		: "Rachel",
				"Rc" 		: "Rebecca",
				"Ri" 		: "Richard",
				"Ro" 		: "Robert",
				"S" 		: "Sarah",
				"Sm" 		: "Samuel",
				"Sn" 		: "Susan",
				"Sr" 		: "Sarah",
				"Sl" 		: "Samuel",
				"T" 		: "Thomas",
				"W" 		: "William",
				"Wm" 		: "William",
				"Wr" 		: "Walter",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		OrigAbbrs														*
 *																		*
 *  Table for expanding abbreviations for ethnic origins				*
 ************************************************************************/
var	OrigAbbrs = {
				"Af" 		: "African (Negro)",
				"Am" 		: "American",
				"Au" 		: "Austrian",
				"C" 		: "Canadian",
				"Da" 		: "Danish",
				"Du" 		: "Dutch",
				"E" 		: "English",
				"F" 		: "French",
				"G" 		: "German",
				"In" 		: "Indian (Native)",
				"I" 		: "Irish",
				"M" 		: "Manx",
				"N" 		: "Native",
				"No" 		: "Norwegian",
				"Ng" 		: "Not given",
				"Not" 		: "Not given",
				"Pr" 		: "Prussian",
				"S" 		: "Scotch",
				"Sp" 		: "Spanish",
				"Swe" 		: "Swedish",
				"Swi" 		: "Swiss",
				"W" 		: "Welsh",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		MonthAbbrs														*
 *																		*
 *  Table for expanding abbreviations for months						*
 ************************************************************************/
var	MonthAbbrs = {
				"A" 		: "Apr",
				"Ap" 		: "Apr",
				"Au" 		: "Aug",
				"D" 		: "Dec",
				"F" 		: "Feb",
				"G" 		: "Aug",
				"J" 		: "Jan",
				"Ja" 		: "Jan",
				"Jl" 		: "July",
				"Jn" 		: "June",
				"Jun" 		: "June",
				"Ju" 		: "July",
				"Jul" 		: "July",
				"L" 		: "July",
				"M" 		: "Mar",
				"Ma" 		: "May",
				"Mr" 		: "Mar",
				"My" 		: "May",
				"N" 		: "Nov",
				"O" 		: "Oct",
				"S" 		: "Sept",
				"Y" 		: "May",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		ResTypeAbbrs													*
 *																		*
 *  Table for expanding abbreviations for residence types				*
 *  in pre-confederation census forms.									*
 ************************************************************************/
var	ResTypeAbbrs = {
				"B" 		: "Brick",
				"F" 		: "Frame",
				"L" 		: "Log",
				"S" 		: "Shanty",
				"Sh" 		: "Shanty",
				"St" 		: "Stone",
				"W" 		: "Wood",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		AddrAbbrs														*
 *																		*
 *  Table for expanding abbreviations for places						*
 ************************************************************************/
var	AddrAbbrs = {
				"1/4" 		: "¼",
				"1/3" 		: "&#8531;",
				"1/2" 		: "½",
				"2/3" 		: "&#8532;",
				"3/4" 		: "¾",
				"Bf" 		: "BF",
				"Con" 		: "con",
				"Lot" 		: "lot",
				"Of" 		: "of",
				"Ne" 		: "NE",
				"Nw" 		: "NW",
				"Se" 		: "SE",
				"Sw" 		: "SW",
				"1rn" 		: "1RN",
				"2rn" 		: "2RN",
				"3rn" 		: "3RN",
				"4rn" 		: "4RN",
				"5rn" 		: "5RN",
				"1rs" 		: "1RS",
				"2rs" 		: "2RS",
				"3rs" 		: "3RS",
				"4rs" 		: "4RS",
				"5rs" 		: "5RS",
				"R1n" 		: "R1N",
				"R2n" 		: "R2N",
				"R3n" 		: "R3N",
				"R4n" 		: "R4N",
				"R5n" 		: "R5N",
				"R1s" 		: "R1S",
				"R2s" 		: "R2S",
				"R3s" 		: "R3S",
				"R4s" 		: "R4S",
				"R5s" 		: "R5S",
				"Pt" 		: "pt",
				"Part"      : "part",
				"Sb" 		: "SB",
				"S.b."      : "S.B.",
				"[" 		: "[blank]"
				};

/************************************************************************
 *		StoriesAbbrs													*
 *																		*
 *  Table for expanding abbreviations for number of stories.  This is	*
 *  mostly to assist with insertion of symbol for 1/2.					*
 ************************************************************************/
var	StoriesAbbrs = {
				"1/2"		: "½",
				"11/2"		: "1½",
				"21/2"		: "2½",
				"[" 		: "[blank]"
				};


/************************************************************************
 *		AgeAbbrs														*
 *																		*
 *  Table for expanding abbreviations for age to standardize			*
 *  representation of fractional ages.									*
 ************************************************************************/
var	AgeAbbrs = {
				"1/12"		: "1m",
				"2/12"		: "2m",
				"3/12"		: "3m",
				"4/12"		: "4m",
				"5/12"		: "5m",
				"6/12"		: "6m",
				"7/12"		: "7m",
				"8/12"		: "8m",
				"9/12"		: "9m",
				"10/12"	    : "10m",
				"11/12"	    : "11m",
				"12/12"	    : "12m",
				"1/4"		: "3m",
				"1/2"		: "6m",
				"1/3"		: "4m",
				"3/4"		: "9m",
				"1M"		: "1m",
				"2M"		: "2m",
				"3M"		: "3m",
				"4M"		: "4m",
				"5M"		: "5m",
				"6M"		: "6m",
				"7M"		: "7m",
				"8M"		: "8m",
				"9M"		: "9m",
				"10M"		: "10m",
				"11M"		: "11m",
				"12M"		: "12m",
				"Abt"		: "about",
				"About"	    : "about",
				"After"	    : "after",
				"D"		    : "days",
				"Day"		: "day",
				"Days"		: "days",
				"Few"		: "few",
				"H"		    : "hour",
				"M"		    : "months",
				"M1"		: "1m",
				"M2"		: "2m",
				"M3"		: "3m",
				"M4"		: "4m",
				"M5"		: "5m",
				"M6"		: "6m",
				"M7"		: "7m",
				"M8"		: "8m",
				"M9"		: "9m",
				"M10"		: "10m",
				"M11"		: "11m",
				"M12"		: "12m",
				"Month"	    : "month",
				"Months"	: "months",
				"Of"		: "of",
				"One"		: "one",
				"Sev"		: "several",
				"Sev."		: "several",
				"Several"	: "several",
				"Some"		: "some",
				"W"		    : "weeks",
				"Week"		: "week",
				"Weeks"	    : "weeks",
				"Y"		    : "years",
				"Year"		: "year",
				"Years"	    : "years",
				"["		    : "[blank]"
				};

/************************************************************************
 *  surnamePartAbbrs													*
 *																		*
 *  Table for expanding abbreviations in a surname.						*
 *  Most of these are to override the default capitalization for		*
 *  prepositions used with surnames.									*
 ************************************************************************/
var	surnamePartAbbrs = {
        				"De"		: "de",
        				"Of"		: "of",
        				"Van"		: "van",
        				"Von"		: "von"};

/************************************************************************
 *		CauseAbbrs														*
 *																		*
 *  Table for expanding abbreviations in cause of death field			*
 *  Some of these are to override the default capitalization for		*
 *  prepositions and conjunctions and articles.  Others are to assist	*
 *  in corekt speling.													*
 ************************************************************************/
var	CauseAbbrs = {
				"1/2"			: "½",
				"A"		    	: "a",
				"Abt"			: "about",
				"About"     	: "about",
				"After"     	: "after",
				"An"			: "an",
				"And"			: "and",
				"As"			: "as",
				"At"			: "at",
				"Before"    	: "before",
				"By"			: "by",
				"C"		    	: "Cancer",
				"D"		    	: "days",
				"Day"			: "day",
				"Days"			: "days",
				"Dia"			: "Diarrhoea",
				"Dip"			: "Diphtheria",
				"F"		    	: "Fever",
				"Few"			: "few",
				"For"			: "for",
				"From"			: "from",
				"H"		    	: "hour",
				"Ha"			: "Haemorrhage",
				"Inflamation"	: "Inflammation",
				"In"			: "in",
				"M"		    	: "months",
				"Many"			: "many",
				"Month"     	: "month",
				"Months"    	: "months",
				"Near"			: "near",
				"Not"			: "not",
				"Of"			: "of",
				"On"			: "on",
				"One"			: "one",
				"Or"			: "or",
				"Ph"			: "Phthisis",
				"Sev"			: "several",
				"Sev."			: "several",
				"Several"   	: "several",
				"Some"			: "some",
				"The"			: "the",
				"To"			: "to",
				"Tu"			: "Tuberculosis",
				"W"		    	: "weeks",
				"Week"			: "week",
				"Weeks"     	: "weeks",
				"With"			: "with",
				"Y"		    	: "years",
				"Year"			: "year",
				"Years"     	: "years",
				"["		    	: "[blank]"
				};

/************************************************************************
 *  monTab																*
 *																		*
 *  Translate month names and abbreviations to indices. 				*
 ************************************************************************/
var	monTab	= {
				""		    : 0,
				"ja"		: 1,
				"jan"		: 1,
				"jany"		: 1,
				"january"	: 1,
				"fe"		: 2,
				"feb"		: 2,
				"feby"		: 2,
				"february"	: 2,
				"mr"		: 3,
				"mar"		: 3,
				"march"		: 3,
				"al"		: 4,
				"apr"		: 4,
				"aprl"		: 4,
				"april"		: 4,
				"ma"		: 5,
				"may"		: 5,
				"jn" 		: 6,
				"jun" 		: 6,
				"june" 		: 6,
				"jl"		: 7,
				"jul"		: 7,
				"july"		: 7,
				"au"		: 8,
				"aug"		: 8,
				"augt"		: 8,
				"august"	: 8,
				"se"		: 9,
				"sep"		: 9,
				"sept"		: 9,
				"september"	: 9,
				"oc"		: 10,
				"oct"		: 10,
				"octr"		: 10,
				"october"	: 10,
				"no"		: 11,
				"nov"		: 11,
				"novr"		: 11,
				"november"	: 11,
				"de"		: 12,
				"dec"		: 12,
				"decr"		: 12,
				"december"	: 12 };

/************************************************************************
 *  preTab																*
 *																		*
 *  Prefix value based upon initial reserved word.						*
 ************************************************************************/
var	preTab	= {
				""			    : 0,
				'in' 			: '0',
				'on' 			: '0',
				'abt' 			: '1',
				'about' 		: '1',
				'cir' 			: '2',
				'circa' 		: '2',
				'bef' 			: '3',
				'before' 		: '3',
				'aft'			: '4',
				'after'			: '4',
				'between' 		: '5',
				'bet'	 		: '5',
				'wft est' 		: '8',
				'est' 			: 'g',
				'cal' 			: 'h',
				'calculated' 	: 'h',
				'from' 			: 'F',
				'to' 			: 'T'
				};

/************************************************************************
 *  femaleNames															*
 *																		*
 *  This table contains the common female given names					*
 *  in the database.  If the given name of the individual is changed	*
 *  and contains one of the following, the sex of the individual		*
 *  is changed to female.												*
 ************************************************************************/
var femaleNames	= {
				'abagail'			: 16,
				'abbie'				: 16,
				'abby'				: 16,
				'abigail'			: 16,
				'ada'				: 28,
				'adalaide'			: 28,
				'adaline'			: 23,
				'addie'				: 23,
				'adelaide'			: 28,
				'adele'				: 28,
				'adelia'			: 23,
				'adeline'			: 23,
				'aggie'				: 2,
				'agnes'				: 143,
				'agness'			: 15,
				'aileen'			: 5,
				'alberta'			: 20,
				'alceste'			: 2,
				'alexandra'			: 2,
				'alexandrina'		: 2,
				'alice'				: 115,
				'allison'			: 1,
				'alma'				: 20,
				'almeda'			: 20,
				'almina'			: 20,
				'almira'			: 20,
				'alvira'			: 20,
				'amanda'			: 21,
				'amelia'			: 39,
				'amey'				: 21,
				'amy'				: 21,
				'anabel'			: 1,
				'anabell'			: 1,
				'andrea'			: 1,
				'angela'			: 1,
				'angelina'			: 1,
				'angeline'			: 1,
				'ann'				: 392,
				'anna'				: 64,
				'anne'				: 86,
				'annette'			: 6,
				'annie'				: 304,
				'annis'				: 3,
				'arabella'			: 3,
				'araminta'			: 3,
				'arvilla'			: 3,
				'audrey'			: 3,
				'augusta'			: 3,
				'aurelia'			: 3,
				'aurilla'			: 3,
				'avis'				: 3,
				'barbara'			: 59,
				'bathsheba'			: 5,
				'beatrice'			: 29,
				'belinda'			: 1,
				'bella'				: 16,
				'berenice'			: 16,
				'berenise'			: 16,
				'bernadette'		: 16,
				'bernice'			: 16,
				'bertha'			: 43,
				'bessie'			: 34,
				'beth'				: 31,
				'betsy'				: 31,
				'betty'				: 15,
				'beulah'			: 15,
				'blanch'			: 17,
				'blanche'			: 17,
				'brenda'			: 17,
				'bridget'			: 17,
				'candace'			: 11,
				'carmen'			: 11,
				'carol'				: 11,
				'caroline'			: 113,
				'carolyn'			: 11,
				'carrie'			: 19,
				'cassie'			: 19,
				'catharine'			: 128,
				'catherine'			: 415,
				'cecelia'			: 19,
				'celia'				: 19,
				'charity'			: 19,
				'charlotte'			: 118,
				'cheryl'			: 19,
				'christena'			: 51,
				'christie'			: 5,
				'christina'			: 148,
				'christine'			: 16,
				'christy'			: 27,
				'clara'				: 55,
				'clarissa'			: 18,
				'constance'			: 18,
				'cora'				: 18,
				'cordelia'			: 18,
				'cynthia'			: 18,
				'daisy'				: 15,
				'daughter'			: 15,
				'debbie'			: 14,
				'deborah'			: 14,
				'delia'				: 14,
				'delilah'			: 14,
				'della'				: 14,
				'diana'				: 14,
				'dinah'				: 14,
				'donna'				: 14,
				'dora'				: 16,
				'doris'				: 16,
				'dorothea'			: 42,
				'dorothy'			: 42,
				'edith'				: 77,
				'edna'				: 22,
				'effie'				: 34,
				'elaine'			: 6,
				'eleanor'			: 46,
				'eleanora'			: 46,
				'elisabeth'			: 39,
				'eliza'				: 214,
				'elizabeth'			: 949,
				'ella'				: 32,
				'ellen'				: 241,
				'elma'				: 32,
				'elsie'				: 15,
				'elspet'			: 21,
				'elva'				: 32,
				'emaline'			: 16,
				'emeline'			: 16,
				'emiline'			: 16,
				'emily'				: 77,
				'emma'				: 125,
				'estella'			: 7,
				'estelle'			: 7,
				'ester'				: 7,
				'esther'			: 57,
				'ethel'				: 83,
				'etta'				: 7,
				'eunice'			: 7,
				'euphemia'			: 89,
				'euretta'			: 89,
				'eva'				: 32,
				'evelina'			: 32,
				'eveline'			: 32,
				'evelyn'			: 32,
				'fannie'			: 16,
				'fanny'				: 52,
				'flora'				: 102,
				'florence'			: 74,
				'frances'			: 66,
				'georgina'			: 23,
				'gertrude'			: 44,
				'gladys'			: 29,
				'grace'				: 62,
				'hannah'			: 187,
				'harriet'			: 111,
				'hattie'			: 15,
				'hazel'				: 29,
				'helen'				: 89,
				'helena'			: 16,
				'hellen'			: 16,
				'henrietta'			: 40,
				'hester'			: 17,
				'ida'				: 59,
				'ilene'				: 22,
				'infant'			: 22,
				'irene'				: 22,
				'isabel'			: 27,
				'isabella'			: 383,
				'isabelle'			: 3,
				'jane'				: 583,
				'janet'				: 192,
				'jannet'			: 15,
				'jean'				: 91,
				'jeanette'			: 91,
				'jemima'			: 25,
				'jennet'			: 33,
				'jennie'			: 53,
				'jessie'			: 93,
				'joanna'			: 22,
				'johanna'			: 15,
				'josephine'			: 25,
				'julia'				: 48,
				'kate'				: 21,
				'katherine'			: 18,
				'kathleen'			: 25,
				'katie'				: 18,
				'kezia'				: 15,
				'laura'				: 38,
				'lavina'			: 17,
				'lena'				: 19,
				'leona'				: 19,
				'letitia'			: 18,
				'lillian'			: 22,
				'lillie'			: 20,
				'lilly'				: 18,
				'loretta'			: 9,
				'lorraine'			: 9,
				'louisa'			: 93,
				'louise'			: 24,
				'lovena'			: 24,
				'lovina'			: 24,
				'lovinia'			: 24,
				'lucille'			: 33,
				'lucinda'			: 33,
				'lucy'				: 44,
				'luella'			: 44,
				'lydia'				: 47,
				'mabel'				: 58,
				'mabelena'			: 8,
				'mable'				: 21,
				'madaline'			: 6,
				'madeline'			: 6,
				'madge'				: 6,
				'madora'			: 6,
				'mae'				: 6,
				'magdalen'			: 6,
				'magdalena'			: 6,
				'magdalene'			: 6,
				'magdaline'			: 6,
				'maggie'			: 46,
				'maggy'				: 6,
				'mahala'			: 6,
				'mahalia'			: 6,
				'mahhitable'		: 6,
				'maisie'			: 6,
				'malinda'			: 6,
				'malisa'			: 6,
				'malissa'			: 6,
				'malvina'			: 6,
				'mamie'				: 6,
				'mandana'			: 6,
				'mandy'				: 6,
				'manetta'			: 6,
				'manette'			: 6,
				'marcella'			: 6,
				'margaret'			: 955,
				'margareta'			: 5,
				'margarete'			: 5,
				'margarett'			: 5,
				'margaretta'		: 5,
				'margarette'		: 5,
				'margary'			: 5,
				'margerie'			: 5,
				'margery'			: 5,
				'margorie'			: 5,
				'marguerite'		: 14,
				'maria'				: 105,
				'mariah'			: 5,
				'mariam'			: 5,
				'marian'			: 5,
				'marianne'			: 5,
				'marie'				: 5,
				'marietta'			: 5,
				'marilla'			: 5,
				'marilyn'			: 5,
				'marina'			: 5,
				'marinda'			: 5,
				'marion'			: 29,
				'marjery'			: 17,
				'marjorie'			: 17,
				'marjory'			: 14,
				'marleen'			: 14,
				'marlene'			: 14,
				'martha'			: 190,
				'mary'				: 1351,
				'matilda'			: 95,
				'maud'				: 41,
				'may'				: 37,
				'melissa'			: 25,
				'meryl'				: 25,
				'meta'				: 25,
				'michelle'			: 22,
				'mildred'			: 22,
				'millicent'			: 22,
				'millie'			: 22,
				'milly'				: 22,
				'mina'				: 22,
				'minerva'			: 22,
				'minetta'			: 22,
				'minney'			: 8,
				'minnie'			: 78,
				'miranda'			: 8,
				'miriam'			: 8,
				'mirtle'			: 3,
				'mizie'				: 3,
				'mona'				: 3,
				'monica'			: 3,
				'muriel'			: 3,
				'myrtle'			: 33,
				'myzie'				: 3,
				'nadine'			: 3,
				'nancy'				: 141,
				'naomi'				: 3,
				'natalie'			: 3,
				'nellie'			: 39,
				'nelly'				: 3,
				'nettie'			: 3,
				'netty'				: 3,
				'neva'				: 3,
				'nichole'			: 3,
				'nicole'			: 3,
				'nina'				: 3,
				'nora'				: 3,
				'norah'				: 3,
				'norine'			: 3,
				'norma'				: 3,
				'olga'				: 3,
				'olive'				: 34,
				'olivia'			: 3,
				'ora'				: 3,
				'orpha'				: 3,
				'pamela'			: 3,
				'pamelia'			: 3,
				'patience'			: 3,
				'patricia'			: 3,
				'paula'				: 3,
				'pauline'			: 3,
				'pearl'				: 36,
				'peggy'				: 3,
				'penelope'			: 3,
				'permila'			: 3,
				'permilla'			: 3,
				'pheba'				: 4,
				'phebe'				: 4,
				'phidelia'			: 4,
				'philena'			: 4,
				'phillipa'			: 4,
				'phoeba'			: 4,
				'phoebe'			: 24,
				'phyllis'			: 4,
				'polly'				: 4,
				'priscilla'			: 4,
				'prudence'			: 4,
				'rachael'			: 4,
				'rachel'			: 83,
				'rebecca'			: 98,
				'reita'				: 4,
				'rena'				: 4,
				'reta'				: 4,
				'rhea'				: 4,
				'rhoda'				: 16,
				'rita'				: 4,
				'robena'			: 4,
				'roberta'			: 4,
				'robin'				: 4,
				'rosa'				: 4,
				'rosalie'			: 4,
				'rosamond'			: 4,
				'rosana'			: 4,
				'rosann'			: 4,
				'rosanna'			: 18,
				'rose'				: 22,
				'rosella'			: 4,
				'roseltha'			: 4,
				'rosemary'			: 4,
				'rosetta'			: 4,
				'rosey'				: 4,
				'rosie'				: 4,
				'rosina'			: 4,
				'rossana'			: 4,
				'rowena'			: 4,
				'roxana'			: 4,
				'roxane'			: 4,
				'roxanna'			: 4,
				'roxanne'			: 4,
				'roxie'				: 4,
				'roxey'				: 4,
				'roxy'				: 4,
				'rubena'			: 4,
				'rubie'				: 4,
				'rubina'			: 4,
				'ruby'				: 19,
				'ruth'				: 41,
				'sarah'				: 571,
				'sophia'			: 29,
				'stella'			: 16,
				'stillborndaughter'	: 35,
				'susan'				: 110,
				'susanna'			: 39,
				'susannah'			: 35,
				'theresa'			: 35,
				'unnameddaughter'	: 45,
				'vera'				: 14,
				'viola'				: 16,
				'violet'			: 29,
				'winnifred'			: 15};

/************************************************************************
 *  maleNames															*
 *																		*
 *  This table contains the 132 most common male given names			*
 *  in the database.  If the given name of the individual is changed	*
 *  and contains one of the following, the sex of the individual		*
 *  is changed to male.													*
 *  The value of each name is the number of occurrences in the database	*
 ************************************************************************/
var maleNames	= {
				'aaron'			: 22,
				'abraham'		: 46,
				'abram'			: 44,
				'adam'			: 97,
				'albert'		: 472,
				'alex'			: 170,
				'alexander'		: 660,
				'alfred'		: 313,
				'allan'			: 83,
				'allen'			: 45,
				'alonzo'		: 16,
				'alvin'			: 32,
				'amos'			: 32,
				'andrew'		: 349,
				'angus'			: 227,
				'anthony'		: 43,
				'archibald'		: 307,
				'archie'		: 167,
				'arthur'		: 408,
				'benjamin'		: 152,
				'bruce'			: 50,
				'calvin'		: 30,
				'carl'			: 31,
				'cecil'			: 74,
				'charles'		: 1265,
				'charley'		: 30,
				'charlie'		: 81,
				'chester'		: 76,
				'christian'		: 13,
				'christopher'	: 83,
				'clarance'		: 36,
				'clarence'		: 134,
				'clifford'		: 93,
				'colin'			: 53,
				'cornelius'		: 34,
				'cyrenius'		: 34,
				'dan'			: 48,
				'daniel'		: 376,
				'david'			: 629,
				'dennis'		: 34,
				'donald'		: 385,
				'dougald'		: 44,
				'douglas'		: 32,
				'dugald'		: 87,
				'duncan'		: 462,
				'earl'			: 96,
				'earnest'		: 112,
				'edgar'			: 71,
				'edmund'		: 21,
				'edward'		: 766,
				'edwin'			: 136,
				'eli'			: 31,
				'elias'			: 17,
				'elijah'		: 40,
				'elmer'			: 30,
				'emerson'		: 13,
				'ephraim'		: 17,
				'ernest'		: 169,
				'ezra'			: 13,
				'francis'		: 178,
				'frank'			: 620,
				'franklin'		: 53,
				'fred'			: 276,
				'frederick'		: 337,
				'fredrick'		: 193,
				'freeman'		: 17,
				'george'		: 2313,
				'gilbert'		: 67,
				'gorden'		: 36,
				'gordon'		: 202,
				'grant'			: 202,
				'harold'		: 133,
				'harry'			: 341,
				'harvey'		: 81,
				'hector'		: 59,
				'henery'		: 68,
				'henry'			: 757,
				'herbert'		: 196,
				'herman'		: 33,
				'hiram'			: 67,
				'horace'		: 13,
				'howard'		: 67,
				'hugh'			: 312,
				'isaac'			: 166,
				'ivan'			: 14,
				'jacob'			: 127,
				'jack'			: 19,
				'james'			: 2572,
				'jeremiah'		: 35,
				'jesse'			: 18,
				'john'			: 4651,
				'jonathan'		: 23,
				'joseph'		: 931,
				'josiah'		: 14,
				'joshua'		: 33,
				'keith'			: 13,
				'kenneth'		: 54,
				'lachlin'		: 17,
				'lawrence'		: 54,
				'leo'			: 36,
				'leonard'		: 71,
				'leslie'		: 59,
				'levi'			: 41,
				'lewis'			: 71,
				'lloyd'			: 71,
				'lorne'			: 63,
				'louis'			: 58,
				'malcolm'		: 156,
				'mark'			: 40,
				'martin'		: 81,
				'mathew'		: 65,
				'matthew'		: 31,
				'melvin'		: 32,
				'michael'		: 156,
				'milton'		: 70,
				'moses'			: 50,
				'murray'		: 14,
				'nathan'		: 24,
				'nathaniel'		: 40,
				'neil'			: 216,
				'nelson'		: 95,
				'nicholas'		: 18,
				'norman'		: 177,
				'oliver'		: 74,
				'orville'		: 18,
				'oscar'			: 52,
				'patrick'		: 164,
				'paul'			: 164,
				'percy'			: 80,
				'peter'			: 389,
				'philip'		: 88,
				'phillip'		: 15,
				'ralph'			: 67,
				'reginald'		: 31,
				'reuben'		: 27,
				'richard'		: 519,
				'robert'		: 1296,
				'roderick'		: 14,
				'ross'			: 40,
				'roy'			: 260,
				'russel'		: 61,
				'russell'		: 66,
				'samuel'		: 559,
				'sidney'		: 45,
				'silas'			: 18,
				'simon'			: 40,
				'solomon'		: 22,
				'stanley'		: 104,
				'stephen'		: 88,
				'stewart'		: 35,
				'sylvester'		: 21,
				'theophilus'	: 16,
				'thomas'		: 1659,
				'timothy'		: 38,
				'victor'		: 38,
				'wallace'		: 54,
				'walter'		: 408,
				'warren'		: 15,
				'wayne'			: 14,
				'wellington'	: 45,
				'wesley'		: 123,
				'wilbert'		: 65,
				'wilfred'		: 56,
				'william'		: 4305,
				'willie'		: 61,
				'wilson'		: 40};

/************************************************************************
 *  capitalize															*
 *																		*
 *  Capitalize the value of a HTML input element.						*
 *																		*
 *  Input:																*
 *		element		an HTML Input element from a form					*
 ************************************************************************/
function capitalize(element)
{
    var tmp	= element.value;
    var needCap	= true;		// capitalize 1st letter
    var	msg	= "";
    for (var e = 0; e < tmp.length; e++)
    {		// scan value
		if (needCap &&
		    "abcdefghijklmnopqrstuvwxyz".indexOf(tmp.charAt(e)) >= 0)
		{	// only upper case OK
		    msg	+= "upper case tmp[" + e + "]='" + tmp.charAt(e) + "', ";
		    tmp	= tmp.substring(0,e) + tmp.charAt(e).toUpperCase() +
						tmp.substring(e+1);
		    needCap	= false;	// do not capitalize rest of word
		}	// only upper case OK
		else
		{	// any letter OK
		    needCap	= " .,;:+".indexOf(tmp.charAt(e)) >= 0;
		}	// any letter OK
    }		// scan value
    //alert("CommonForm.js: capitalize('" + element.value + "'): " + msg +
    //		", returns '" + tmp + "'");
    element.value	= tmp;		// replace with capitalized value
    return tmp;
}		// capitalize

/************************************************************************
 *  setErrorFlag														*
 *																		*
 *  Set the error flag on a field value by altering the class of		*
 *  the input field to either contain or not contain 'error'			*
 *																		*
 *  Input:																*
 *		element		element to act on									*
 *		valid		true if the field value is valid					*
 *				false if the field value is invalid						*
 ************************************************************************/
function setErrorFlag(element, valid)
{
    var	className	= element.className;
    // clear or set the error indicator if required by changing class name
    var errpos		= className.indexOf('error');
    if (errpos >= 0)
    {		// error currently flagged
		// if valid value, clear the flag
		if (valid)
		    element.className	= className.substring(0, errpos) + 'black' +
							  className.substring(errpos + 5);
    }		// error currently flagged
    else
    {		// error not currently flagged
		// if in error add flag to class name
		if (!valid)
		{
		    var spcpo		= className.indexOf(' ');
		    element.className	= 'error' + className.substring(spcpo);
		}
    }		// error not currently flagged
}		// function setErrorFlag

/************************************************************************
 *  expAbbr																*
 *																		*
 *  Expand abbreviations.  This method modifies the value				*
 *  of the element that is passed to it.  If the value contains			*
 *  words that are abbreviated, they are expanded,						*
 *  otherwise the value is capitalized.									*
 *																		*
 *  Input:																*
 *		element			an input text element in the form				*
 *		table			table of abbreviations							*
 *																		*
 *  Returns:															*
 *		updated value of element										*
 ************************************************************************/
function expAbbr(element, table)
{
    if (element.value.length == 0)
		return "";

    // capitalize words in value if presentation style requires it
    var textTransform	= "";
    if (element.currentStyle)		// try IE API
		textTransform	= element.currentStyle.textTransform;
    else
    if (window.getComputedStyle)	// W3C API
		textTransform	= window.getComputedStyle(element, null).textTransform;

    // break into words and check each word for keyword to expand
    var	words	= element.value.split(" ");
    var	result	= "";

    for(var i = 0; i < words.length; i++)
    {
		var word	= words[i];
		var key		= word;
		for (var e = 0; e < key.length; e++)
		{		// scan word
		    if (key.substring(e,e+1).search(/[A-Z0-9]/) == 0)
				break;
		    else
		    if (key.substring(e,e+1).search(/[a-z]/) == 0)
		    {		// fold initial lower case letter to upper case
				key	= key.substring(0,e) + key.charAt(e).toUpperCase() +
						  key.substring(e+1);
				break;
		    }		// fold initial lower case letter to upper case
		}		// scan word

		// if word starts with an open square bracket, do not include it
		var	firstChar	= word.charAt(0);
		if (textTransform == "capitalize")
		    firstChar		= key.charAt(0);
		if (key.length > 1 && "['\"".indexOf(firstChar) >= 0)
		{		// key starts with punctuation mark
		    // do not include punctuation mark in key value
		    key		= key.substring(1);
		}		// key ends with punctuation mark
		else
		{		// key does not start with special char
		    firstChar	= "";
		}		// key does not start with special char

		// if word ends with a punctuation mark, do not include it
		var	lastChar= key.charAt(key.length - 1);
		if (key.substring(key.length - 2) == "'s")
		{		// possessive
		    key		= key.substring(0, key.length - 2);
		    lastChar	= "'s";
		}		// possessive
		else
		if (",;:]".indexOf(lastChar) >= 0)
		{		// key ends with punctuation mark
		    // do not include punctuation mark in key value
		    key		= key.substring(0, key.length - 1);
		}		// key ends with punctuation mark
		else
		{		// key does not end with special char
		    lastChar	= "";
		}		// key does not end with special char

		// do a table lookup in the table of abbreviations
		var	exp	= table[key];
		if (exp)
		{		// substitute word from abbreviation table
		    words[i]	= firstChar + exp + lastChar;
		}		// substitute word from abbreviation table
		else
		{		// substitute folded word
		    words[i]	= firstChar + key + lastChar;
		}		// substitute folded word

		// separate words with a space
		if (i > 0)
		    result	+= " ";
		result	+= words[i];
    }		// loop through all words
    //alert("expAbbr: result='" + result + "'");
    element.value	= result;
    return result;
}		// expAbbr

/************************************************************************
 *  changeElt															*
 *																		*
 *  Take action when the user changes a field to implement				*
 *  assists such as converting to upper case and expanding				*
 *  abbreviations.														*
 *  This is a static function that provides the base functionality for	*
 *  the onchange method of any <input> element.							*
 *																		*
 *  Parameters:															*
 *		element			an instance of an HTML Input element			*
 ************************************************************************/
function changeElt(element)
{
    if (element.form)
    {		// parameter is a form element
		var	id	= element.name;
		if (id.length == 0)
		    id		= element.id;

		// trim off leading and trailing spaces
		element.value	= element.value.trim();
    
		// expand abbreviations if required
		if (element.abbrTbl)
		    expAbbr(element,
				    element.abbrTbl);
		else
		if (element.value == '[')
		    element.value	= '[Blank]';
		else
		{	// capitalize words in value if presentation style requires it
		    var	style		= null;
		    if (element.currentStyle)		// try IE API
				style	= element.currentStyle;
		    else
		    if (window.getComputedStyle)	// W3C API
				style	= window.getComputedStyle(element, null);

		    if (style)
		    {		// have style
				var textTransform	= style.textTransform;
				// the browser only capitalizes the appearance of the text
				// this ensures that the text value is capitalized
				// when saved to the database
				if (textTransform == "capitalize")
				    capitalize(element);
		    }		// have style
		    else
				alert("CommonForm.js: changeElt: unable to get style for element '" + id + "'");
		}	// capitalize words in value if presentation style requires it

		// change the presentation class to highlight the changed field
		var className		= element.className;
		if (className.substr(0, 5) == 'same ')
		    element.className	= 'black ' + className.substr(5);
		else
		if (className.substr(0, 3) == 'dft')
		    element.className	= 'black ' + className.substr(3);
		else
		if (className.substr(0, 6) != 'black ')
		{
		    var spcpos		= className.indexOf(' ');
		    if (spcpos >= 0)
				element.className	= 'black' + className.substring(spcpos);
		    else
				element.className	= 'black ' + className;
		}
    }		// parameter is a form element
    else
		alert("'CommonForm.js: changeElt: unable to get form, element '" +
				id + "'");
}		// changeElt

/************************************************************************
 *  change																*
 *																		*
 *  Take action when the user changes a field to implement common		*
 *  assists such as converting to upper case and expanding				*
 *  abbreviations.														*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function change()
{
    changeElt(this);

    if (this.checkfunc)
		this.checkfunc();
}		// change

/************************************************************************
 *  dateChanged															*
 *																		*
 *  Take action when the user changes a date field						*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function dateChanged()
{
    var	form		= this.form;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    var	value		= this.value;
    value		= value.replace(/([a-zA-Z])(\d)/g,"$1 $2");
    this.value		= value.replace(/(\d)([a-zA-Z])/g,"$1 $2");

    changeElt(this);	// change case and expand abbreviations

    if (this.checkfunc)
		this.checkfunc();
}		// dateChanged

/************************************************************************
 *  surnameChanged														*
 *																		*
 *  Take action when the user changes the surname field						*
 *																		*
 *  Input:																*
 *		this				an instance of an HTML input element. 				*
 ************************************************************************/
function surnameChanged()
{
    var	form		= this.form;

    changeElt(this);

    if (this.checkfunc)
		this.checkfunc();

    // if the page title is empty, modify it to include the name fields
    // that have been filled in so far
    if (this.name == 'Surname' && updateTitle)
    {
		var newName	= '';
		var givennameElt	= form.GivenName;
		if (givennameElt)
		    newName	+= givennameElt.value + ' ';
		newName		+= this.value;
		newName		+= ' (';
		var birthElt	= form.BirthDate;
		if (birthElt)
		    newName	+= birthElt.value;
		newName		+= "\u2014";
		var deathElt	= form.DeathDate;
		if (deathElt)
		    newName	+= deathElt.value;
		newName		+= ')';
        var	titleElement	= document.getElementById('title');
		titleElement.innerHTML	= titlePrefix + newName;
    }
}		// surnameChanged

/************************************************************************
 *  givenChanged														*
 *																		*
 *  This method is called when the user modifies the value of the		*
 *  given name of the individual.  It adjusts the default gender based	*
 *  upon the name.														*
 *																		*
 *  Input:																*
 *		this	instance of <input> that invoked this function			*
 *																		*
 ************************************************************************/
function givenChanged()
{
    var	form		= this.form;
    if (form.Gender)
    {			// there is a Gender selection list
		var	givenName	= this.value.toLowerCase();
		var	names		= givenName.split(" ");
		for (var i = 0; i < names.length; i++)
		{		// loop through individual given names
		    var	aName	= names[i];
		    if (maleNames[aName] > 0)
		    {
				form.Gender.selectedIndex	= 0;
				form.Gender.className		= 'male';
				break;
		    }
		    else
		    if (aName.substring(aName.length - 1) == 'a' ||
				femaleNames[aName] > 0)
		    {
				form.Gender.selectedIndex	= 1;
				form.Gender.className		= 'female';
				break;
		    }
		}		// loop through individual given names
    }			// there is a Gender selection list

    // fold to upper case and expand abbreviations
    changeElt(this);

    if (this.checkfunc)
		this.checkfunc();

    // if the page title is empty, modify it to include the name fields
    // that have been filled in so far
    if (this.name == 'GivenName' && updateTitle)
    {
		var newName	= this.value;
		var surnameElt	= form.Surname;
		if (surnameElt)
		    newName	+= ' ' + surnameElt.value;
		newName		+= ' (';
		var birthElt	= form.BirthDate;
		if (birthElt)
		    newName	+= birthElt.value;
		newName		+= "\u2014";
		var deathElt	= form.DeathDate;
		if (deathElt)
		    newName	+= deathElt.value;
		newName		+= ')';
        var	titleElement	= document.getElementById('title');
		titleElement.innerHTML	= titlePrefix + newName;
    }
}	// givenChanged

/************************************************************************
 *  goToLink															*
 *																		*
 *  This function may be set as the onclick handler for an element.		*
 *  It requires that the 'href' attribute of the element has been set	*
 *  to the URL of a page to be displayed.  If the 'target' attribute has*
 *  also been set, then the page is displayed in the window with that	*
 *  name, otherwise the new page replaces the current page.				*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML <button> element.		*
 ************************************************************************/
function goToLink()
{
    if (this.href)
    {		// new URL defined
		if (this.target)
		{
		    window.open(this.href,
						this.target);
		}
		else
		{
		    location	= this.href;
		}	
    }		// new URL defined
    else
    {		// new URL not defined
		alert("href attribute was not set for this button");
    }		// new URL not defined
}		// goToLink

/************************************************************************
 *  checkName															*
 *																		*
 *  Validate the current value of a field containing a name.			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkName()
{
    var	element		= this;
    var	re		= /^[a-zA-Z7\u00c0-\u00ff .'"()\-&\[\]?]*$/;
    var	name		= element.value;
    setErrorFlag(element, re.test(name));
}		// checkName

/************************************************************************
 *  checkProvince														*
 *																		*
 *  Validate the current value of a field containing a province code.	*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkProvince()
{
    var	element		= this;
    var	name		= element.value;
    setErrorFlag(element, (offset & 1) == 0);
}		// checkProvince

/************************************************************************
 *  checkOccupation														*
 *																		*
 *  Validate the current value of a field containing a occupation.		*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkOccupation()
{
    var	element		= this;
    var	re		= /^[a-zA-Z\u00c0-\u00ff .'&\-\[\]?]*$/;
    var	occupation	= element.value;
    setErrorFlag(element, re.test(occupation));
}		// checkOccupation

/************************************************************************
 *  checkAddress														*
 *																		*
 *  Validate the current value of a field containing a address.			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkAddress()
{
    var	element		= this;
    var	re		= /^[-a-zA-Z\u00c0-\u00ff0-9 .,'½¼¾&\[\]\/?]*$/;
    var	address		= element.value;
    setErrorFlag(element, re.test(address));
}		// checkAddress

/************************************************************************
 *  checkText															*
 *																		*
 *  Validate the current value of a field containing text.				*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkText()
{
    var	element		= this;
    var	re		= /^[a-zA-Z\u00c0-\u00ff0-9 .,:;'"()½/\[\]\-&?]*$/;
    var	text		= element.value;
    setErrorFlag(element, re.test(text));
}		// checkText

/************************************************************************
 *  checkSex															*
 *																		*
 *  Validate the current value of a field containing a sex.				*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkSex()
{
    var	element		= this;
    var	re		= /^[MFmf?]?$/;
    var	sex		= element.value;
    setErrorFlag(element, re.test(sex));
}		// checkSex

/************************************************************************
 *  checkMStat															*
 *																		*
 *  Validate the current value of a field containing a mstat.			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkMStat()
{
    var	element		= this;
    var	re		= /^[BDMSWVCbdmswvc? ]?$/;
    var	mstat		= element.value;
    setErrorFlag(element, re.test(mstat));
}		// checkMStat

/************************************************************************
 *  checkFlag															*
 *																		*
 *  Validate the current value of a field containing a flag.			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkFlag()
{
    var	element		= this;
    var	re		= /^[ynYN1 ]?$/;
    var	flag		= element.value;
    setErrorFlag(element, re.test(flag));
}		// checkFlag

/************************************************************************
 *  checkFlagSex														*
 *																		*
 *  Validate the current value of a field containing a flag or a gender.*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkFlagSex()
{
    var	element		= this;
    var	re		= /^[ynmfYNMF1 ]?$/;
    var	flag		= element.value;
    setErrorFlag(element, re.test(flag));
}		// checkFlagSex

/************************************************************************
 *  checkDate															*
 *																		*
 *  Validate the current value of a field containing a date.			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkDate()
{
    var	element		= this;
    var	re		= /^\[?\s*([A-Za-z]*)\s*([0-9]*)\s*([A-Za-z]*)\s*([0-9]*).*\]?$/;
    var	date		= element.value;
    if (date.length == 0)
		return true;
    var	result		= re.exec(date);
    var	matched		= (typeof result === 'object') &&
						  (result instanceof Array);
    var	l0, n1, l2, l3, n3, pi, mi;
    if (matched)
    {
		l0	= result[1].length;
		pi	= preTab[result[1].toLowerCase()];
		if (pi === undefined)
		    pi	= monTab[result[1].toLowerCase()];

		if (result[2].length > 0)
		    n1	= result[2] - 0;
		else
		    n1	= 0;

		l2	= result[3].length;
		if (l2 == 0)
		    mi	= pi;
		else
		    mi	= monTab[result[3].toLowerCase()];

		l3	= result[4].length;
		if (l3 > 0)
		    n3	= result[4] - 0;
		else
		    n3	= 0;
    }

    if (matched)
    {
		matched	= (((n1 > 31 && l2 > 0 && n3 <= 31) ||	// yyyy mmm [dd]
				  (n1 <= 31 && l2 > 0 && n3 > 0) ||	// [dd] mmmm yyyy
				  (n1 <= 31 && l2 > 0 && l3 == 0) ||	// [dd] mmmm
				  (n1 == 0 && l0 > 0 && n3 <= 31) ||	// mmmm dd
				  (n1 == 0 && l0 > 0 && n3 == 0) ||	// mmmm
				  (n1 > 0 && l2 == 0 && n3 == 0)) &&	// yyyy
				 pi !== undefined &&
				 mi !== undefined) ||
				pi == '5' ||
				pi == 'F';
    }

    setErrorFlag(element, matched);
}		// checkDate

/************************************************************************
 *  checkNumber															*
 *																		*
 *  Validate the current value of a field containing a number.			*
 *  A number of fields that use this validation occasionally have		*
 *  half-integral values, these include:								*
 *		census district numbers											*
 *		pre-confederation marriage registration report numbers			*
 *		pre-confederation number of stories fields in census			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkNumber()
{
    var	element		= this;
    var	re		= /^[0-9½]*$/;
    var	number		= element.value.trim();
    if (number == '')
		setErrorFlag(element, true);
    else
		setErrorFlag(element, re.test(number));
}		// checkNumber

/************************************************************************
 *  checkPositiveNumber													*
 *																		*
 *  Validate the current value of a field containing a positive number.	*
 *  A number of fields that use this validation occasionally have		*
 *  half-integral values, these include:								*
 *		census district numbers											*
 *		pre-confederation marriage registration report numbers			*
 *		pre-confederation number of stories fields in census			*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkPositiveNumber()
{
    var	element		= this;
    var	re		= /^[0-9½]*$/;
    var	number		= element.value.trim();
    if (number == '')
		setErrorFlag(element, true);
    else
		setErrorFlag(element, re.test(number) && (number != '0'));
}		// checkPositiveNumber

/************************************************************************
 *  checkFamily															*
 *																		*
 *  Validate the current value of a field containing a family number.	*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkFamily()
{
    var	element		= this;
    var	cell		= this.parentNode;	// table cell
    var	col		= cell.cellIndex;	// column number
    var row		= cell.parentNode;	// table row
    var	rowIndex	= row.sectionRowIndex;	// position in body section
    var	section		= row.parentNode;	// table section
    var	prevRow;
    var	prevCell;
    var	prevFamily	= null;
    var	expFamily	= null;
    if (rowIndex > 0)
    {
		for(var ir = rowIndex - 1; ir >= 0; ir--)
		{		// loop back until find family number
		    prevRow		= section.rows[ir];
		    prevCell	= prevRow.cells[col];
		    for(var child = prevCell.firstChild;
						child; child = child.nextSibling)
		    {
				if (child.nodeType == 1 &&
				    child.nodeName.toLowerCase() == 'input' &&
				    child.value.length > 0)
				{
				    prevFamily	= child.value;
				    expFamily	= (child.value - 0) + 1;
				    break;
				}
		    }
		    if (prevFamily !== null)
				break;
		}		// loop back until find family number
    }
    else
    {		// first row, nothing precedes
		prevRow		= null;
		prevFamily	= element.value;
		expFamily	= element.value;
    }		// first row, nothing precedes

    var	re		= /^[0-9]*$/;
    var	family		= element.value.trim();
    if (family != '')
		setErrorFlag(element, re.test(family) && 
						      (family == prevFamily || family == expFamily));
}		// checkFamily

/************************************************************************
 *  checkYear															*
 *																		*
 *  Validate the current value of a field containing a year.			*
 *  Should be 4 digit numeric year, possibly enclosed in editorial		*
 *  square brackets, a question mark, or [blank].						*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkYear()
{
    var	element		= this;
    var	re		= /^([?]|[0-9]{4}|na|\[blank\])?$/;
    var	year		= element.value;
		// if valid value, clear the flag
    setErrorFlag(element, re.test(year));
}		// checkYear

/************************************************************************
 *  checkAge															*
 *																		*
 *  Validate the current value of an age field.  Should be numeric age	*
 *  in years, age in months (with a suffix 'm'), a question mark,		*
 *  or [blank].															*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkAge()
{
    var	element		= this;
    var	re		= /^[mM]?[0-9]+[mM]?$/;
    var	re2		= /^([0-9½]+[yY]|)\s*([0-9½]+[mM]|)\s*([0-9½]+[wW]|)\s*([0-9½]+[dD]|)$/;
    var	age		= element.value;
    setErrorFlag(element, age.length == 0 ||
						  age == '?' ||
						  age.toLowerCase() == '[blank]' ||
						  re.test(age) || 
						  re2.test(age));
}		// checkAge

/************************************************************************
 *  checkURL															*
 *																		*
 *  Validate the current value of a field containing a Uniform			*
 *  Record Location (URL).												*
 *																		*
 *  Input:																*
 *		this			an instance of an HTML input element. 			*
 ************************************************************************/
function checkURL()
{
    var	element		= this;
    var	re		= new RegExp("^(http:|https:|ftp:|)(//[A-Za-z0-9_.]*/|)(.*/|)(.+)$");
    var	url		= element.value;
    setErrorFlag(element, url.length == 0 ||re.test(url));
}		// checkAge

/************************************************************************
 *  getSortDate															*
 *																		*
 *  Given a textual date, compute a sort date YYYYMMDD					*
 *																		*
 *  Returns:															*
 *		integer value YYYYMMDD											*
 ************************************************************************/
function getSortDate(date)
{
    var	re	= /([A-Za-z]*) *([0-9]*) *([A-Za-z]*) *([0-9]*)/;
    var	result	= re.exec(date);
    var	matched	= typeof result === 'object' && result instanceof Array;
    var	l1, l2, l3;
    var	di	= 15;
    var	mi	= 6;
    var	yi	= -9999;

    if (matched)
    {
		if (result[2].length == 4)
		{
		    yi	= result[2];
		    if (result[4].length == 1 || result[4].length == 2)
				di	= result[4];
		}
		else
		if (result[4].length == 4)
		{
		    yi	= result[4];
		    if (result[2].length == 1 || result[2].length == 2)
				di	= result[2];
		}
		mi	= monTab[result[1].toLowerCase()];
		if (mi === undefined || mi == 0)
		    mi	= monTab[result[3].toLowerCase()];
		if (mi === undefined || mi == 0)
		    mi	= 6;
		retval	= yi*10000 + mi*100 + (di - 0);
		return retval;
    }
    else
		return -99999999;
}		// getSortDate

/************************************************************************
 *  getCellRelRow														*
 *																		*
 *  Get the element in another cell in the current row at a				*
 *  relative column position.											*
 *																		*
 *  Input:																*
 *		curr	the current form input element							*
 *		rel		the relative column to move to.  For example			*
 *				rel = -1 moves 1 column to the left, while				*
 *				rel = 2 moves 2 columns to the right.  Columns			*
 *				wrap around at the end of the row.						*
 *																		*
 *  Returns:															*
 *		The input element in the requested cell.  In the event of		*
 *		any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellRelRow(	curr,
						rel)
{
    var	td;		// table cell containing input element
    var	col;		// current column index
    var	tr;		// table row containing input element
    var	row;		// current row index
    var	field;		// returned value

    td	= curr.parentNode;
    if (td.cellIndex === undefined)
    {
		alert("CensusForm.js: getCellRelRow: current element is not in a table cell");
		return curr;	// curr is not contained in a table cell
    }
    col	= td.cellIndex;
    tr	= td.parentNode;
    row	= tr.rowIndex;	// row index of current row

    // move to the requested relative column, and wrap the value
    // to the table width
    while(true)
    {
		col	+= rel;		// move in requested direction
		while(col < 0)
		    col += tr.cells.length;
		while(col >= tr.cells.length)
		    col -= tr.cells.length;

		// identify the first element node of the requested cell
		td	= tr.cells[col];
		field	= td.firstChild;

		// the first child may not be the desired input element
		// for example if there is some text at the beginning of the cell
		while(field && field.nodeType != 1)
		    field	= field.nextSibling;

		if (field && field.type && field.type == "text")
		    break;
    }		// while moving

    // return requested field
    return field;
}		// getCellRelRow

/************************************************************************
 *  getCellFirstRow														*
 *																		*
 *  Get the element in the first cell in the current row.				*
 *																		*
 *  Input:																*
 *		curr	the current form input element							*
 *																		*
 *  Returns:															*
 *		The input element in the requested cell.  In the event of		*
 *		any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellFirstRow(curr)
{
    var	td;		// table cell containing input element
    var	col;		// current column index
    var	tr;		// table row containing input element
    var	row;		// current row index
    var	field;		// returned value

    td	= curr.parentNode;
    if (td.cellIndex === undefined)
    {
		alert("CensusForm.js: getCellFirstRow: current element is not in a table cell");
		return curr;	// curr is not contained in a table cell
    }
    col	= 1;		// 2nd column, 1st contains line number
    tr	= td.parentNode;
    row	= tr.rowIndex;	// row index of current row

    // identify the first element node of the requested cell
    td		= tr.cells[col];
    field	= td.firstChild;
    // the first child may not be the desired input element
    // for example if there is some text at the beginning of the cell
    while(field && field.nodeType != 1)
		field	= field.nextSibling;

    if (!field)
		alert("CensusForm.js: getCellFirstRow: unable to locate element for row=" + row +
				", col=" + col);
    // return requested field
    return field;
}		// getCellFirstRow

/************************************************************************
 *  getCellLastRow														*
 *																		*
 *  Get the element in the last cell in the current row.				*
 *																		*
 *  Input:																*
 *		curr		the current form input element						*
 *																		*
 *  Returns:															*
 *		The input element in the requested cell.  In the event of		*
 *		any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellLastRow(curr)
{
    var	td;		// table cell containing input element
    var	col;		// current column index
    var	tr;		// table row containing input element
    var	row;		// current row index
    var	field;		// returned value

    td	= curr.parentNode;
    if (td.cellIndex === undefined)
    {
		alert("CensusForm.js: getCellLastRow: current element is not in a table cell");
		return curr;	// curr is not contained in a table cell
    }
    tr	= td.parentNode;
    // get index of 3rd last column, last contains citation button, second last
    // displays line number
    col	= tr.cells.length - 3;
    row	= tr.rowIndex;	// row index of current row

    // identify the first element node of the requested cell
    td		= tr.cells[col];
    field	= td.firstChild;
    // the first child may not be the desired input element
    // for example if there is some text at the beginning of the cell
    while(field && field.nodeType != 1)
		field	= field.nextSibling;

    if (!field)
		alert("CensusForm.js: getCellLastRow: unable to locate element for row=" + row +
				", col=" + col);
    // return requested field
    return field;
}		// getCellLastRow

/************************************************************************
 *  getCellRelCol														*
 *																		*
 *  Get the element in another cell in another row at the				*
 *  same column position.												*
 *																		*
 *  Input:																*
 *		curr	the current form input element							*
 *		rel		the relative row to move to.  For example				*
 *				rel = -1 moves 1 row up, while							*
 *				rel = 2 moves 2 rows down.  Rows						*
 *				wrap around at the end of the table.					*
 *																		*
 *  Returns:															*
 *		The input element in the requested cell.  In the event of		*
 *		any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellRelCol(	curr,
						rel)
{
    var	td;		// table cell containing input element
    var	col;		// current column index
    var	row;		// current row index
    var	tr;		// table row containing input element
    var tb;		// body section containing this row
    var	field;		// returned value

    td	= curr.parentNode;
    if (td.cellIndex === undefined)
    {
		alert("CensusForm.js: getCellRelCol: current element is not in a table cell: " + tagToString(td));
		return curr;	// curr is not contained in a table cell
    }

    col	= td.cellIndex;	// column index of current cell
    tr	= td.parentNode;
    row	= tr.rowIndex;	// row index of current row
    tb	= tr.parentNode;// table body tag
var msg	= "rel=" + rel + ", td.cellIndex=" + col + ", tr.rowIndex=" + row;
//alert("tb: " + tagToString(tb));

    // move to the requested relative row and wrap the value
    // to the table height
    // note that row 0 contains the column header, not input fields
    row	+= rel;
    while(row < 1)
		row += tb.rows.length;
    while(row > tb.rows.length)
		row -= tb.rows.length;
msg += ", newrow=" + row;

    // identify the first element node of the requested cell
    tr		= tb.rows[row-1];
    td		= tr.cells[col];
    field	= td.firstChild;
    // the first child may not be the desired input element
    // for example if there is some text at the beginning of the cell
    while(field && field.nodeType != 1)
		field	= field.nextSibling;

    if (!field)
		alert("CensusForm.js: getCellRelCol: " + msg + 
		      "unable to locate element for row=" + row +
		      ", col=" + col);
    //    else
    //	alert("getCellRelCol: " + msg + ", newtd: " +
    //	      tagToString(td));

    // return requested field
    return field;
}		// getCellRelCol

/************************************************************************
 *  isChanged															*
 *																		*
 *  Editting state variable used by function tableKeyDown				*
 *  true if the current field has been modified							*
 ************************************************************************/
var	isChanged	= false;

/************************************************************************
 *  tableKeyDown														*
 *																		*
 *  Handle key strokes in input fields.  The objective is to emulate	*
 *  the behavior of cursor movement keys in a spreadsheet.				*
 *																		*
 *  Input:																*
 *		e		In a W3C compliant browser, the keydown event			*
 *		this	<input> element											*
 ************************************************************************/

function tableKeyDown(e)
{
    if (!e)
		e	=  window.event;
    var	code	= e.keyCode;

    // identify the column name and row number of the input element
    var colName		= this.name;
    var rowNum		= '';
    if (colName.length == 0)
		colName		= this.id;
    var matches		= /([a-zA-Z#_]+)(\d*)/.exec(colName);
    colName		= matches[1];
    rowNum		= matches[2];

    var form		= this.form;
    var formElts	= form.elements;
    var	field;

    // hide the help balloon on any keystroke
    if (helpDiv)
		helpDiv.style.display	= 'none';

    // take action based upon code
    switch (code)
    {
		case 9:		// tab
		{
		    isChanged	= false;
		    return true;
		}

		case 13:	// return
		case 40:	// down
		{
		    isChanged	= false;
		    field	= getCellRelCol(this, 1);
		    if (field === undefined)
				return false;
		    field.focus();		// set focus on same column next row
		    if (field.select)
				field.select();		// select all of the text to replace
		    return false;		// suppress default action
		}		// return

		case 35:
		{		// End key
		    isChanged	= false;
		    if (e.ctrlKey)
		    {		// ctrl-End
				field	= getCellLastRow(this);
				if (field === undefined)
				    return false;
				field.focus();		// set focus on last column current row
				field.select();		// select all of the text to replace
				return false;		// suppress default action
		    }		// ctrl-End
		    break;
		}		// End key

		case 36:
		{		// Home key
		    isChanged	= false;
		    if (e.ctrlKey)
		    {		// ctrl-Home
				field	= getCellFirstRow(this);
				if (field === undefined)
				    return false;
				field.focus();	// set focus on first column current row
				field.select();	// select all of the text to replace
				return false;	// suppress default action

		    }		// ctrl-Home
		    break;
		}		// Home key

		case 37:	// left
		{
		    //if (isChanged)
				return true;
		    //field	= getCellRelRow(this, -1);
		    //field.focus();		// set focus on previous col same row
		    //field.select();		// select all of the text to replace
		    //return false;		// suppress default action
		}		// return

		case 38:	// up
		{
		    isChanged	= false;
		    field	= getCellRelCol(this, -1);
		    if (field === undefined)
				return false;
		    field.focus();		// set focus on same column next row
		    field.select();		// select all of the text to replace
		    return false;		// suppress default action
		}	   	 // up

		case 39:	// right
		{
		    //if (isChanged)
				return true;
		    //field	= getCellRelRow(this, 1);
		    //field.focus();		// set focus on next col same row
		    //field.select();		// select all of the text to replace
		    //return false;		// suppress default action
		}		// return

		case 112:	// F1
		{
		    displayHelp(this);
		    return false;		// suppress default action
		}		// F1

		case 67:
		{		// letter 'C'
		    if (e.altKey)
		    {		// alt-C
				var correctImage= document.getElementById('correctImage');
				if (correctImage)
				    correctImageUrl();
				return false;
		    }		// alt-C
		    else
		    if (colName == 'Family')
		    {
				if (e.preventDefault) e.preventDefault();
				return false;
		    }
		    else
				isChanged	= true;
		    break;
		}		// letter 'C'

		case 73:
		{		// letter 'I'
		    if (e.altKey)
		    {		// alt-I
				var imageButton	= document.getElementById('imageButton');
				if (imageButton)
				    imageButton.onclick();
				return false;
		    }		// alt-I
		    else
		    if (colName == 'Family')
		    {
				if (e.preventDefault) e.preventDefault();
				return false;
		    }
		    else
				isChanged	= true;
		    break;
		}		// letter 'I'

		case 83:
		{		// letter 'S'
		    if (e.ctrlKey)
		    {		// ctrl-S
				form.submit();
				return false;
		    }		// ctrl-S
		    else
		    if (colName == 'Family')
		    {
				if (e.preventDefault) e.preventDefault();
				return false;
		    }
		    else
				isChanged	= true;
		    break;
		}		// letter 'S'

		case 85:
		{		// letter 'U'
		    if (e.altKey)
		    {		// alt-U
				form.submit();
		    }		// alt-U
		    else	// letter U
		    if (colName == 'Family')
		    {
				if (e.preventDefault) e.preventDefault();
				return false;
		    }
		    else
				isChanged	= true;
		    break;
		}		// letter 'U'

		case 90:
		{		// letter 'Z'
		    if (e.ctrlKey)
		    {		// ctrl-Z
				this.value	= this.defaultValue;
				return false;
		    }		// ctrl-Z
		    else	// letter Z
		    if (colName == 'Family')
		    {
				if (e.preventDefault) e.preventDefault();
				return false;
		    }
		    else
				isChanged	= true;
		    break;
		}		// letter 'Z'

		case 17:	// ctrl key
		case 18:	// alt key
		{		// only handled in conjunction with other key
		    break;
		}		// only handled in conjunction with other key

		case 8:		// back-space
		case 48:	// 0
		case 49:	// 1
		case 50:	// 2
		case 51:	// 3
		case 52:	// 4
		case 53:	// 5
		case 54:	// 6
		case 55:	// 7
		case 56:	// 8
		case 57:	// 9
		case 61:	// = duplicate previous value
		{
		    isChanged	= true;
		    break;
		}

		default:
		{		// other keystrokes
		    if (colName == 'Family')
		    {
				if (e.preventDefault) e.preventDefault();
				return false;
		    }
		    isChanged	= true;
		    break;
		}		// other keystrokes
    }	    // switch on key code

    return;
}		// tableKeyDown

/************************************************************************
 *  columnClick															*
 *																		*
 *  User clicked left button on a column header.						*
 *  Hide or unhide the column.											*
 *																		*
 *  Input:																*
 *		this	instance of <th> for which this is the onclick method	*
 ************************************************************************/
function columnClick()
{
    var	colIndex	= this.cellIndex;
    var	row		= this.parentNode;
    var	section		= row.parentNode;
    var	table		= section.parentNode;
    var	body		= table.tBodies[0];
    var	footer		= table.tFoot;
    var	footerRow	= null;
    if (footer)
		footerRow	= footer.rows[0];
    var	footerCell	= null;
    if (footerRow)
		footerCell	= footerRow.cells[colIndex];
    var newElt;
    var dataCell;
    var	element;

    // hide or reveal the label text in the header and footer of the column
    if (this.holdtext && this.holdtext.length > 0)
    {		// header has been hidden
		this.innerHTML			= this.holdtext;
		if (footerCell)
		    footerCell.innerHTML	= this.holdtext;
		this.holdtext	= "";
    }		// header has been hidden
    else
    {		// hide header
		this.holdtext			= this.innerHTML;
		this.innerHTML			= "";
		if (footerCell)
		    footerCell.innerHTML	= "";
    }		// hide header

    // if a cell in the column contains an <input type='text'> replace it
    // with an <input type='hidden'> with the same attributes
    // Note: in some browsers, which shall not be named,
    // it is not possible to just change the type attribute
    // of an <input> tag while it is in the DOM
    for(var i = 0; i < body.rows.length; i++)
    {				// loop through all rows of table body
		dataCell	= body.rows[i].cells[colIndex];

		for(element = dataCell.firstChild;
		    element;
		    element = element.nextSibling)
		{			// loop through all children of table cell
		    if (element.nodeType == 1)
		    {			// element node
				if (element.nodeName == 'INPUT')
				{
				    if (element.type == 'text')
				    {		// <input type='text'>
						// hide the text element
						newElt		= document.createElement('INPUT');
						newElt.type		= 'hidden';
						newElt.value		= element.value;
						newElt.name		= element.name;
						newElt.className	= element.className;
						newElt.size		= element.size;
						newElt.onchange		= element.onchange;
						newElt.checkfunc	= element.checkfunc;
						newElt.abbrTbl		= element.abbrTbl;
						dataCell.replaceChild(newElt, element);
				    }		// <input type='text'>
				    else
				    if (element.type == 'hidden')
				    {		// <input type='hidden'>
						// unhide the hidden element
						newElt		= document.createElement('INPUT');
						newElt.type		= 'text';
						newElt.value		= element.value;
						newElt.name		= element.name;
						newElt.className	= element.className;
						newElt.size		= element.size;
						newElt.onchange		= element.onchange;
						newElt.checkfunc	= element.checkfunc;
						newElt.abbrTbl		= element.abbrTbl;
						newElt.onkeydown	= tableKeyDown;
						dataCell.replaceChild(newElt, element);
				    }		// <input type='hidden'>
				    break;	// stop searching
				}		// <input>
		    }			// element node
		}			// loop through all children of cell
    }				// loop through all rows of body
}		// columnClick

/************************************************************************
 *  columnWiden															*
 *																		*
 *  User clicked right button on a column header.  Widen the column.	*
 *																		*
 *  Input:																*
 *		this		instance of <th> 									*
 ************************************************************************/
function columnWiden()
{
    var	colIndex	= this.cellIndex;
    var	row		= this.parentNode;
    var	section		= row.parentNode;
    var	table		= section.parentNode;
    var	body		= table.tBodies[0];
    var newElt;	
    var dataCell;
    var	element;

    // if a cell in the column contains an <input type='text'> increase
    // the width of the field
    for(var i = 0; i < body.rows.length; i++)
    {				// loop through all rows of table body
		dataCell	= body.rows[i].cells[colIndex];

		for(element = dataCell.firstChild;
		    element;
		    element = element.nextSibling)
		{			// loop through all children of table cell
		    if (element.nodeType == 1)
		    {			// element node
				if (element.nodeName == 'INPUT')
				{
				    if (element.type == 'text')
				    {		// <input type='text'>
						element.size		= element.size + 
									  Math.floor(element.size / 2);
				    }		// <input type='text'>
				    break;	// stop searching
				}		// <input>
		    }			// element node
		}			// loop through all children of cell
    }				// loop through all rows of body
    return false;		// do not display menu
}		// columnWiden

/************************************************************************
 *  linkMouseOver														*
 *																		*
 *  This function is called if the mouse moves over a forward or		*
 *  backward hyperlink on the invoking page.							*
 *																		*
 *  Parameters:															*
 *		this			element the mouse moved on to					*
 ************************************************************************/
function linkMouseOver()
{
    var	msgDiv	= document.getElementById('mouse' + this.id);
    if (msgDiv)
    {		// support for dynamic display of messages
		// display the messages balloon in an appropriate place on the page
		var leftOffset		= getOffsetLeft(this);
		if (leftOffset > 500)
		    leftOffset	-= 200;
		msgDiv.style.left	= leftOffset + "px";
		msgDiv.style.top	= (getOffsetTop(this) - 60) + 'px';
		msgDiv.style.display	= 'block';

		// so key strokes will close window
		helpDiv			= msgDiv;
		helpDiv.onkeydown	= tableKeyDown;
    }		// support for dynamic display of messages
}		// linkMouseOver

/************************************************************************
 *  linkMouseOut														*
 *																		*
 *  This function is called if the mouse moves off a forward or			*
 *  backward hyperlink on the invoking page.							*
 *																		*
 *  Parameters:															*
 *		this			element the mouse moved on to					*
 ************************************************************************/
function linkMouseOut()
{
    if (helpDiv)
    {
		helpDiv.style.display	= 'none';
		helpDiv			= null;
    }
}		// function linkMouseOut
