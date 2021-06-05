/************************************************************************
 *  CommonForm.js                                                       *
 *                                                                      *
 *  This file contains the JavaScript functions that implement the      *
 *  dynamic functionality of the forms used to enter genealogical       *
 *  data.  This file is shared between all forms because most           *
 *  of the functionality is shared by many relevant records.            *
 *                                                                      *
 *  History:                                                            *
 *      2010/12/09      enable submit button on changes                 *
 *      2011/01/02      issue alert if change not called on a form      *
 *                      element.                                        *
 *      2011/04/03      add additional relationships (Servant, ...)     *
 *      2011/04/06      add "Christian Disciple" abbreviation to rlgns  *
 *      2011/04/07      make abbreviation lookup case insensitive       *
 *      2011/05/17      abbreviation lookup expands all matching words  *   
 *      2011/05/21      add table of location abbreviations             *
 *                      add additional abbreviations to some tables     *
 *      2011/06/05      add "Daughter-in-Law" abbreviation              *
 *      2011/06/07      add "Congregationalist" abb ireviations         *
 *                      do not fold non expanded words to lower case    *
 *      2011/07/23      add "or" to location lookup list                *
 *                      do not include trailing punctuation mark in     *
 *                      key lookup                                      *
 *      2011/09/04      add more month abbreviations                    *
 *                      add more birthplace abbreviations               *
 *                      add more given name abbreviations               *
 *      2011/10/09      add in abbreviation tables from                 *
 *                      /database/CensusForm.js                         *
 *                      correct abbreviations for nephew & niece        *
 *      2011/10/16      add table to standardize representation of      *
 *                      fractional year ages                            *
 *      2011/12/17      add relationship abbreviations                  *
 *      2012/04/17      add 1 1/2 stories abbreviation                  *
 *                      change capitalization algorithm to support      *
 *                              acronyms with periods                   *
 *                      reorder code in changeElt to be more efficient  *
 *                              and easier to understand                *
 *      2012/10/23      in function change invoke element.changefunc    *
 *                      if it is defined.                               *
 *      2012/10/31      move input element validation functions here    *
 *      2012/11/05      make checkDate function fussier                 *
 *      2012/11/12      no longer necessary to enable submit button     *
 *                      on field change.                                *
 *                      minor change to function expAbbr.               *
 *                      minor change to function capitalize             *
 *                      more rigorous logic in function changeElt       *
 *                      add abbreviations for death cause words         *
 *      2013/01/08      add abbreviations for location prepositions     *
 *      2013/01/14      do not flag dates starting with between or from *
 *                      as invalid                                      *
 *      2013/01/20      expand characters permitted in name             *
 *      2013/01/29      add more abbreviations for death cause          *
 *      2013/02/19      add more abbreviations for occupation           *
 *      2013/03/23      add method getSortDate                          *
 *      2013/03/26      add more abbreviations                          *
 *      2013/05/30      add more abbreviations                          *
 *      2013/06/10      correct spelling of "inflammation" in causes    *
 *      2013/06/28      use RelAbbrs also for informant relation field  *
 *      2013/08/20      add functions for cell movement in a table      *
 *      2013/08/24      add abbreviations for addresses                 *
 *      2013/08/26      change function exprAbbr so it capitalizes      *
 *                      the first letter even if not first character    *
 *      2013/09/04      move shared function columnClick to here to     *
 *                      ensure common implementation for all tables     *
 *      2013/10/10      add abbreviations for Commercial Traveller occ. *
 *      2013/10/19      fix bug in capitalize algorithm                 *
 *      2013/11/01      fix columnClick to set both header and footer   *
 *                      column text and to support any contents in      *
 *                      the header and footer cell for a column         *
 *      2013/12/02      more location abbreviations, do not             *
 *                      capitalize 'the' or 'through' ...               *
 *                      or time periods                                 *
 *      2013/12/08      accept broader range of age values              *
 *      2013/12/14      add separate time periods to AgeAbbrs           *
 *      2013/12/18      allow zero length value in year field           *
 *      2014/01/16      more entries in cause of death and age          *
 *      2014/01/21      do not include certain opening punctuation      *
 *                      marks in the lookup for abbreviations           *
 *                      allow ampersand in names                        *
 *      2014/02/18      do not include possessive ending in expansion   *
 *                      of abbreviations                                *
 *      2014/02/27      function givenChanged and its support tables    *
 *                      moved here from editIndivid.js so it can be     *
 *                      used by commonMarriage.js                       *
 *      2014/03/11      expand list of male given names                 *
 *      2014/03/24      restore table keyboard handling when restoring  *
 *                      hidden table column                             *
 *      2014/04/24      flags explicit value '0' as an invalid number   *
 *      2014/05/26      add more female names                           *
 *      2014/09/20      permit ½ as a digit in ages                     *
 *      2014/09/22      change some preferred religion expansions       *
 *      2014/10/06      add method checkURL                             *
 *      2014/10/16      add more female given names                     *
 *      2014/11/22      add more connecting words for occupations       *
 *                      add more male given names                       *
 *      2015/01/26      function chkDate did not handle date ranges     *
 *      2015/02/04      add some religion abbreviations                 *
 *      2015/03/17      add some French location abbreciations          *
 *      2015/04/26      in givenChanged treat given names ending in     *
 *                      'a' as female                                   *
 *      2015/06/02      permit accented western european letters in     *
 *                      names, locations, and occupations               *
 *      2015/07/08      move columnWiden function here                  *
 *                      move linkMouseOver function here                *
 *                      move linkMouseOut function here                 *
 *      2015/08/12      add surnamePartAbbrs                            *
 *      2015/09/23      if the given name or surname are changed on     *
 *                      an editIndivid.php page with an empty title     *
 *                      then the title is changed on the fly            *
 *      2016/03/01      in date fields ensure there is a space between  *
 *                      a letter and a digit or a digit and a letter    *
 *      2016/05/31      left and right arrows in table revert to        *
 *                      default movement within a cell rather than      *
 *                      moving between cells, which is supported by tab *
 *      2017/11/01      do not flag '7th Day Adventist' as invalid in   *
 *                      checkName                                       *
 *      2018/01/09      add fractions 1/3 and 2/3                       *
 *      2018/01/19      ensure changed fields displayed with black      *
 *                      text                                            *
 *      2018/02/09      add function setErrorFlag to consolidate        *
 *                      setting class name by whether or not the        *
 *                      field value is valid                            *
 *      2018/02/23      correct validation of half-integral numbers     *
 *                      strings cannot be compared to numbers in JS     *
 *      2018/04/15      limit characters that can be entered in Family  *
 *      2018/05/09      change religion abbreviations to spell "Church" *
 *      2018/05/10      add method checkPositiveNumber                  *
 *      2019/01/21      remove static list of surnames with gender      *
 *                      functionality is moved to Nicknames table       *
 *      2019/04/12      loosen syntax for ages                          *
 *                      add function getNumeric to convert numbers      *
 *                      that include '½' as a digit.                    *
 *      2019/05/19      call element.click to trigger button click      *
 *      2019/05/24      add numericKeyDown to filter non-numeric        *
 *                      keystrokes                                      *
 *                      improve handling of left and right arrow to     *
 *                      go to next cell if no text to move over         *
 *      2019/11/25      change columnClick to support columns with      *
 *                      display: block                                  *
 *      2019/12/09      drop support for IE<9                           *
 *                      use popupAlert in place of alert                *
 *      2020/06/06      add religion abbreviations                      *
 *      2020/06/09      improve date checking                           *
 *      2020/10/14      improve date checking                           *
 *      2020/11/13      change expansion of location "Us"               *
 *      2021/01/11      improve URL check and include sample code       *
 *                      to extract components of the URL                *
 *      2021/01/12      drop support for IE 9 & 10.  That is browsers   *
 *                      that or not compatible with ECMA ES6            *
 *      2021/01/16      use XMLSerializer for diagnostic output         *
 *      2021/02/23      in dates insert a space between digit and [     *
 *                      and between letter and [                        *
 *                                                                      *
 *  Copyright &copy; 2021 James A. Cobban                               *
 ************************************************************************/

/************************************************************************
 *  Initialize nicknames table from server.                             *
 ************************************************************************/
var givenNames       = [];

if (typeof HTTP === 'function')
{                       // load nicknames table from database
    let options             = {"timeout"    : false};
    HTTP.get('/getRecordJSON.php?table=Nicknames',
             gotNicknames,
             options);
}                       // load nicknames table from database

/************************************************************************
 *  function gotNicknames                                               *
 *                                                                      *
 *  This method is called when the JSON document representing           *
 *  the list of given names is received from the server.                *
 ************************************************************************/
function gotNicknames(obj)
{
    if (typeof(obj) == 'object')
    {
        givenNames              = obj;
    }
    else
        alert('CommonForm.js:gotNicknames: ' + typeof obj);
}       // function gotNicknames

/************************************************************************
 *  RelAbbrs                                                            *
 *                                                                      *
 *  Table for expanding abbreviations for Relationships                 *
 *  This table is used in two different contexts:                       *
 *      - for the relation to head of household column in censuses      *
 *      - for the relation of the informant to the deceased in a death  *
 *        record or to the child in a birth record                      *
 ************************************************************************/
const RelAbbrs = {
                "A"         : "Aunt",
                "Ad"        : "Adopted-Daughter",
                "As"        : "Adopted-Son",
                "B"         : "Boarder",
                "Bl"        : "Brother-in-Law",
                "Bo"        : "Boarder",
                "Br"        : "Brother",
                "C"         : "Cousin",
                "D"         : "Daughter",
                "Dl"        : "Daughter-in-Law",
                "Do"        : "Domestic",
                "E"         : "Employee",
                "F"         : "Father",
                "Fl"        : "Father-in-Law",
                "Gd"        : "Grand-Daughter",
                "Gf"        : "Grand-Father",
                "Gm"        : "Grand-Mother",
                "Gs"        : "Grand-Son",
                "H"         : "Head",
                "Hu"        : "Husband",
                "L"         : "Lodger",
                "La"        : "Laborer",
                "M"         : "Mother",
                "Mil"       : "Mother-in-Law",
                "Ml"        : "Mother-in-Law",
                "N"         : "Nephew",
                "Ne"        : "Nephew",
                "Ni"        : "Niece",
                "P"         : "Physician",
                "S"         : "Son",
                "Sd"        : "Step-Daughter",
                "Se"        : "Servant",
                "Si"        : "Sister",
                "Sil"       : "Sister-in-Law",
                "Sl"        : "Sister-in-Law",
                "So"        : "Son",
                "Sol"       : "Son-in-Law",
                "Ss"        : "Step-Son",
                "St"        : "Servant",
                "U"         : "Uncle",
                "Ut"        : "Undertaker",
                "W"         : "Wife",
                "["         : "[blank]"
                };

/************************************************************************
 *      BpAbbrs                                                         *
 *                                                                      *
 *  Table for expanding abbreviations for birth places                  *
 ************************************************************************/
const  BpAbbrs = {
                "1/4"       : "¼",
                "1/3"       : "&#8531;",
                "1/2"       : "½",
                "2/3"       : "&#8532;",
                "3/4"       : "¾",
                "Ab"        : "Alberta",
                "Au"        : "Australia",
                "Bc"        : "British Columbia",
                "Ca"        : "Canada",
                "Ce"        : "Canada East",
                "Ci"        : "Channel Isles",
                "Con"       : "con",
                "Cw"        : "Canada West",
                "Dk"        : "Denmark",
                "E"         : "England",
                "En"        : "England",
                "F"         : "France",
                "Fr"        : "France",
                "G"         : "Germany",
                "Ge"        : "Germany",
                "Gi"        : "Gibraltar",
                "Gu"        : "Guernsey",
                "H"         : "Holland",
                "I"         : "Ireland",
                "Ia"        : "Iowa, USA",
                "Ir"        : "Ireland",
                "In"        : "India",
                "Im"        : "Isle of Man",
                "Lc"        : "Lower Canada",
                "Lmt"       : "Lambton",
                "Lot"       : "lot",
                "Mb"        : "Manitoba",
                "Mi"        : "Michigan, USA",
                "Msx"       : "Middlesex",
                "Nb"        : "New Brunswick",
                "Nj"        : "New Jersey, USA",
                "Nl"        : "Newfoundland",
                "Ns"        : "Nova Scotia",
                "Nw"        : "North West Territories",
                "Nwt"       : "North West Territories",
                "Ny"        : "New York, USA",
                "Nz"        : "New Zealand",
                "O"         : "Ontario",
                "Of"        : "of",
                "Oh"        : "Ohio, USA",
                "On"        : "on",
                "Ont"       : "Ontario",
                "Pa"        : "Pennsylvania, USA",
                "Pi"        : "P.E.I.",
                "Pei"       : "P.E.I.",
                "Po"        : "Poland",
                "Pr"        : "Prussia",
                "Qc"        : "Quebec",
                "Ru"        : "Russia",
                "S"         : "Scotland",
                "Sc"        : "Scotland",
                "Sk"        : "Saskatchewan",
                "Sw"        : "Sweden",
                "Sl"        : "Switzerland",
                "Swi"       : "Switzerland",
                "Sz"        : "Switzerland",
                "U"         : "United States of America",
                "Uc"        : "Upper Canada",
                "Us"        : "United States of America",
                "Usa"       : "USA",
                "U.s."      : "United States of America",
                "W"         : "Wales",
                "Wi"        : "West Indies",
                "["         : "[blank]"
                };

/************************************************************************
 *      LocAbbrs                                                        *
 *                                                                      *
 *  Table for expanding abbreviations for locations in Canada           *
 *  If changing this table also check BpAbbrs for birth places and      *
 *  AddrAbbrs for the addresses in census records                       *
 ************************************************************************/
const  LocAbbrs = {
                "1/4"       : "¼",
                "1/3"       : "&#8531;",
                "1/2"       : "½",
                "2/3"       : "&#8532;",
                "3/4"       : "¾",
                "1rn"       : "1RN",
                "2rn"       : "2RN",
                "3rn"       : "3RN",
                "4rn"       : "4RN",
                "5rn"       : "5RN",
                "1rs"       : "1RS",
                "2rs"       : "2RS",
                "3rs"       : "3RS",
                "4rs"       : "4RS",
                "5rs"       : "5RS",
                "r1n"       : "1RN",
                "r2n"       : "2RN",
                "r3n"       : "3RN",
                "r4n"       : "4RN",
                "r5n"       : "5RN",
                "r1s"       : "1RS",
                "r2s"       : "2RS",
                "r3s"       : "3RS",
                "r4s"       : "4RS",
                "r5s"       : "5RS",
                "Ab"        : "Alberta",
                "And"       : "and",
                "At"        : "at",
                "Au"        : "au",
                "Bc"        : "British Columbia",
                "By"        : "by",
                "Ca"        : "Canada",
                "Ce"        : "Canada East",
                "Con"       : "con",
                "Cor"       : "cor",
                "Cw"        : "Canada West",
                "De"        : "de",
                "Elg"       : "Elgin",
                "Enroute"   : "enroute",
                "En"        : "en",
                "En-route"  : "en-route",
                "Esx"       : "Essex",
                "Et"        : "et",
                "For"       : "for",
                "From"      : "from",
                "In"        : "in",
                "Lc"        : "Lower Canada",
                "Lmt"       : "Lambton", 
                "Lot"       : "lot",
                "Mb"        : "Manitoba",
                "Msx"       : "Middlesex",
                "Nb"        : "New Brunswick",
                "Ne"        : "NE",
                "Nl"        : "Newfoundland",
                "Ns"        : "Nova Scotia",
                "Nt"        : "N.W.T.",
                "Nw"        : "NW",
                "Nw"        : "NW",
                "Nwt"       : "N.W.T.",
                "Of"        : "of",
                "On"        : "on",
                "Or"        : "or",
                "P.o."      : "P.O.",
                "Pi"        : "P.E.I.",
                "Pei"       : "P.E.I.",
                "Pt"        : "pt",
                "Qc"        : "Quebec",
                "Se"        : "SE",
                "Sk"        : "Saskatchewan",
                "Sw"        : "SW",
                "The"       : "the",
                "Through"   : "through",
                "To"        : "to",
                "Uc"        : "Upper Canada",
                "U"         : "United States of America",
                "Us"        : "United States of America",
                "Usa"       : "USA",
                "["         : "[blank]"
                };

/************************************************************************
 *      RlgnAbbrs                                                       *
 *                                                                      *
 *  Table for expanding abbreviations for religions                     *
 ************************************************************************/
const  RlgnAbbrs = {
                "7"         : "7th Day Adventist",
                "A"         : "Anglican",
                "An"        : "Anglican",
                "B"         : "Baptist",
                "Bc"        : "Bible Christian",
                "C"         : "Roman Catholic",
                "Calv"      : "Calvinistic Baptist",
                "Cb"        : "C. Baptist",
                "Ccb"       : "Close Communion Baptist",
                "Ce"        : "Church of England",
                "Ch"        : "Church of England",
                "Chr"       : "Christian",
                "Ci"        : "Church of Ireland",
                "Cm"        : "Canada Methodist",
                "Coe"       : "Church of England",
                "Con"       : "Congregationalist",
                "Cong"      : "Congregationalist",
                "Cov"       : "Covenanted Baptist",
                "Cp"        : "Canada Presbyterian",
                "Cs"        : "Church of Scotland",
                "D"         : "Disciple",
                "Db"        : "Disciple Baptist",
                "E"         : "Church of England",
                "Em"        : "Methodist Episcopal",
                "Ep"        : "Episcopal",
                "Ev"        : "Evangelist",
                "F"         : "Friends",
                "Fb"        : "Free Will Baptist",
                "Fc"        : "Free Church [of Scotland]",
                "Fw"        : "Free Will Baptist",
                "Fwb"       : "Free Will Baptist",
                "L"         : "Lutheran",
                "Lds"       : "Latter Day Saints",
                "Lu"        : "Lutheran",
                "M"         : "Methodist",
                "Me"        : "Methodist Episcopal",
                "Men"       : "Mennonite",
                "Mep"       : "Methodist Episcopal",
                "Ncm"       : "New Connexion Methodist",
                "Nc"        : "New (Jerusalem) Church",
                "Nj"        : "New Jerusalem",
                "O"         : "Old School Baptist",
                "Of"        : "of",
                "Osb"       : "Old School Baptist",
                "P"         : "Presbyterian",
                "Pb"        : "P. Baptist",
                "Pl"        : "Plymouth Brethren",
                "Pm"        : "Primitive Methodist",
                "Pn"        : "Presbyterian",
                "Pr"        : "Presbyterian",
                "Pro"       : "Protestant",
                "Pt"        : "Protestant",
                "Q"         : "Quaker (Friends)",
                "R"         : "Roman Catholic",
                "Rb"        : "Regular Baptist",
                "Rc"        : "Roman Catholic",
                "Reg"       : "Regular Baptist",
                "Sa"        : "Salvation Army",
                "Sw"        : "Swedenborgian (New Church)",
                "U"         : "Unitarian",
                "The"       : "the",
                "Ub"        : "United Brethren",
                "Uc"        : "United Church",
                "Univ"      : "Universalist",
                "Up"        : "United Presbyterian",
                "W"         : "Wesleyan Methodist",
                "Wm"        : "Wesleyan Methodist",
                "["         : "[blank]"
                };


/************************************************************************
 *      OccAbbrs                                                        *
 *                                                                      *
 *  Table for expanding abbreviations for occupations                   *
 ************************************************************************/
const  OccAbbrs = {
                "At"        : "at",
                "And"       : "and",
                "App"       : "Apprentice",
                "B"         : "Blacksmith",
                "Bk"        : "Bookkeeper",
                "Brother"   : "brother",
                "C"         : "Carpenter",
                "Clk"       : "Clerk",
                "Cm"        : "Cabinet Maker",
                "Com"       : "Commercial",
                "D"         : "Dressmaker",
                "Dg"        : "Dry Goods",
                "Dm"        : "Dressmaker",
                "Do"        : "Domestic",
                "En"        : "Engineer",
                "F"         : "Farmer",
                "Father"    : "father",
                "Fl"        : "Farm Laborer",
                "For"       : "for",
                "F's"       : "Farmer's",
                "Fs"        : "Farmer's Son",
                "G"         : "Gardener",
                "Gen"       : "General",
                "Har"       : "Harness",
                "Her"       : "her",
                "His"       : "his",
                "Hst"       : "High School Teacher",
                "In"        : "in",
                "K"         : "Keeper",
                "L"         : "Laborer",
                "M"         : "Maker",
                "Ma"        : "Mason",
                "Mac"       : "Machinist",
                "Md"        : "Medical Doctor",
                "Mec"       : "Mechanic",
                "Mfr"       : "Manufacturer",
                "Mgr"       : "Manager",
                "Mil"       : "Miller",
                "Mili"      : "Miliner",
                "Min"       : "Minister",
                "Mt"        : "Merchant",
                "Ng"        : "N.G.",
                "Nu"        : "Nurse",
                "Of"        : "of",
                "On"        : "on",
                "Or"        : "or",
                "P"         : "Physician",
                "Pst"       : "Public School Teacher",
                "Pvt"       : "Private",
                "Ret"       : "retired",
                "Retired"   : "retired",
                "Rr"        : "Railroad",
                "Ry"        : "Railway",
                "Se"        : "Servant",
                "Sic"       : "sic",
                "Sh"        : "Shoemaker",
                "Sm"        : "Stone Mason",
                "St"        : "School Teacher",
                "Steno"     : "Stenographer",
                "Stu"       : "Student",
                "T"         : "Teacher",
                "Tai"       : "Tailor",
                "Tan"       : "Tanner",
                "Team"      : "Teamster",
                "Th"        : "Thresher",
                "The"       : "the",
                "To"        : "to",
                "Tr"        : "Traveller",
                "Ts"        : "Tinsmith",
                "U"         : "Undertaker",
                "Un"        : "Undertaker",
                "Up"        : "Upholsterer",
                "Vs"        : "Veterinary Surgeon",
                "W"         : "Weaver",
                "With"      : "with",
                "Y"         : "Yeoman",
                "["         : "[blank]"
                };

/************************************************************************
 *      SurnAbbrs                                                       *
 *                                                                      *
 *  Table for expanding abbreviations for surnames                      *
 ************************************************************************/
const  SurnAbbrs = {
                "B"         : "Brown",
                "Came"      : "Cameron",
                "C"         : "Campbell",
                "Cl"        : "Clark",
                "G"         : "Graham",
                "H"         : "Harris",
                "He"        : "Henderson",
                "J"         : "Johnston",
                "Mca"       : "McArthur",
                "Mcc"       : "McCallum",
                "Mcd"       : "McDonald",
                "Mci"       : "McIntyre",
                "Mckel"     : "McKellar",
                "Mck"       : "McKenzie",
                "Mcl"       : "McLean",
                "Mcr"       : "McRae",
                "McA"       : "McArthur",
                "McC"       : "McCallum",
                "McD"       : "McDonald",
                "McI"       : "McIntyre",
                "McKel"     : "McKellar",
                "McK"       : "McKenzie",
                "McL"       : "McLean",
                "McR"       : "McRae",
                "Mo"        : "Moore",
                "P"         : "Patterson",
                "R"         : "Robinson",
                "Sc"        : "Scott",
                "Sm"        : "Smith",
                "Su"        : "Sutherland",
                "Th"        : "Thomas",
                "Wa"        : "Walker",
                "Wi"        : "Wilson",
                "Z"         : "Zavitz",
                ""          : "[blank]",
                "["         : "[blank]",
                "[d"        : "[delete]",
                "[D"        : "[delete]"
                };

/************************************************************************
 *      GivnAbbrs                                                       *
 *                                                                      *
 *  Table for expanding abbreviations for given names                   *
 ************************************************************************/
const  GivnAbbrs = {
                "A"         : "Annie",
                "Al"        : "Alexander",
                "Ad"        : "Archibald",
                "An"        : "Ann",
                "Ar"        : "Archibald",
                "As"        : "Agnes",
                "At"        : "Albert",
                "Aw"        : "Andrew",
                "B"         : "Benjamin",
                "Ca"        : "Catherine",
                "Ch"        : "Charles",
                "Da"        : "David",
                "Do"        : "Donald",
                "Du"        : "Duncan",
                "E"         : "Elizabeth",
                "Ed"        : "Edward",
                "El"        : "Ellen",
                "Es"        : "Elisabeth",
                "Em"        : "Emma",
                "Ez"        : "Eliza",
                "F"         : "Francis",
                "Fk"        : "Frederick",
                "Fl"        : "Florence",
                "Fn"        : "Frank",
                "G"         : "George",
                "Geo"       : "George",
                "H"         : "Henry",
                "Hu"        : "Hugh",
                "Hy"        : "Henry",
                "I"         : "Isabella",
                "J"         : "John",
                "Ja"        : "Jane",
                "Jas"       : "James",
                "Jno"       : "John",
                "Jos"       : "Joseph",
                "Js"        : "James",
                "Jt"        : "Janet",
                "M"         : "Mary",
                "Mg"        : "Maggie",
                "Mi"        : "Minnie",
                "Mt"        : "Margaret",
                "Mth"       : "Martha",
                "N"         : "Nancy",
                "P"         : "Peter",
                "R"         : "Robert",
                "Ra"        : "Rachel",
                "Rc"        : "Rebecca",
                "Ri"        : "Richard",
                "Ro"        : "Robert",
                "S"         : "Sarah",
                "Sm"        : "Samuel",
                "Sn"        : "Susan",
                "Sr"        : "Sarah",
                "Sl"        : "Samuel",
                "T"         : "Thomas",
                "W"         : "William",
                "Wm"        : "William",
                "Wr"        : "Walter",
                "["         : "[blank]"
                };

/************************************************************************
 *      OrigAbbrs                                                       *
 *                                                                      *
 *  Table for expanding abbreviations for ethnic origins                *
 ************************************************************************/
const  OrigAbbrs = {
                "Af"        : "African (Negro)",
                "Am"        : "American",
                "Au"        : "Austrian",
                "C"         : "Canadian",
                "Da"        : "Danish",
                "Du"        : "Dutch",
                "E"         : "English",
                "F"         : "French",
                "G"         : "German",
                "In"        : "Indian (Native)",
                "I"         : "Irish",
                "M"         : "Manx",
                "N"         : "Native",
                "No"        : "Norwegian",
                "Ng"        : "Not given",
                "Not"       : "Not given",
                "Pr"        : "Prussian",
                "S"         : "Scotch",
                "Sp"        : "Spanish",
                "Swe"       : "Swedish",
                "Swi"       : "Swiss",
                "W"         : "Welsh",
                "["         : "[blank]"
                };

/************************************************************************
 *      MonthAbbrs                                                      *
 *                                                                      *
 *  Table for expanding abbreviations for months                        *
 ************************************************************************/
const  MonthAbbrs = {
                "A"         : "Apr",
                "Ap"        : "Apr",
                "Au"        : "Aug",
                "D"         : "Dec",
                "F"         : "Feb",
                "G"         : "Aug",
                "J"         : "Jan",
                "Ja"        : "Jan",
                "Jl"        : "July",
                "Jn"        : "June",
                "Jun"       : "June",
                "Ju"        : "July",
                "Jul"       : "July",
                "L"         : "July",
                "M"         : "Mar",
                "Ma"        : "May",
                "Mr"        : "Mar",
                "My"        : "May",
                "N"         : "Nov",
                "O"         : "Oct",
                "S"         : "Sept",
                "Y"         : "May",
                "["         : "[blank]"
                };

/************************************************************************
 *      ResTypeAbbrs                                                    *
 *                                                                      *
 *  Table for expanding abbreviations for residence types               *
 *  in pre-confederation census forms.                                  *
 ************************************************************************/
const  ResTypeAbbrs = {
                "B"         : "Brick",
                "F"         : "Frame",
                "L"         : "Log",
                "S"         : "Shanty",
                "Sh"        : "Shanty",
                "St"        : "Stone",
                "W"         : "Wood",
                "["         : "[blank]"
                };

/************************************************************************
 *      AddrAbbrs                                                       *
 *                                                                      *
 *  Table for expanding abbreviations for places                        *
 ************************************************************************/
const  AddrAbbrs = {
                "1/4"       : "¼",
                "1/3"       : "&#8531;",
                "1/2"       : "½",
                "2/3"       : "&#8532;",
                "3/4"       : "¾",
                "Bf"        : "BF",
                "Con"       : "con",
                "Lot"       : "lot",
                "Of"        : "of",
                "Ne"        : "NE",
                "Nw"        : "NW",
                "Se"        : "SE",
                "Sw"        : "SW",
                "1rn"       : "1RN",
                "2rn"       : "2RN",
                "3rn"       : "3RN",
                "4rn"       : "4RN",
                "5rn"       : "5RN",
                "1rs"       : "1RS",
                "2rs"       : "2RS",
                "3rs"       : "3RS",
                "4rs"       : "4RS",
                "5rs"       : "5RS",
                "R1n"       : "R1N",
                "R2n"       : "R2N",
                "R3n"       : "R3N",
                "R4n"       : "R4N",
                "R5n"       : "R5N",
                "R1s"       : "R1S",
                "R2s"       : "R2S",
                "R3s"       : "R3S",
                "R4s"       : "R4S",
                "R5s"       : "R5S",
                "Pt"        : "pt",
                "Part"      : "part",
                "Sb"        : "SB",
                "S.b."      : "S.B.",
                "["         : "[blank]"
                };

/************************************************************************
 *      StoriesAbbrs                                                    *
 *                                                                      *
 *  Table for expanding abbreviations for number of stories.  This is   *
 *  mostly to assist with insertion of symbol for 1/2.                  *
 ************************************************************************/
const  StoriesAbbrs = {
                "1/2"       : "½",
                "11/2"      : "1½",
                "21/2"      : "2½",
                "["         : "[blank]"
                };


/************************************************************************
 *      AgeAbbrs                                                        *
 *                                                                      *
 *  Table for expanding abbreviations for age to standardize            *
 *  representation of fractional ages.                                  *
 ************************************************************************/
const  AgeAbbrs = {
                "1/12"      : "1m",
                "2/12"      : "2m",
                "3/12"      : "3m",
                "4/12"      : "4m",
                "5/12"      : "5m",
                "6/12"      : "6m",
                "7/12"      : "7m",
                "8/12"      : "8m",
                "9/12"      : "9m",
                "10/12"     : "10m",
                "11/12"     : "11m",
                "12/12"     : "12m",
                "1/4"       : "3m",
                "1/2"       : "6m",
                "1/3"       : "4m",
                "3/4"       : "9m",
                "1M"        : "1m",
                "2M"        : "2m",
                "3M"        : "3m",
                "4M"        : "4m",
                "5M"        : "5m",
                "6M"        : "6m",
                "7M"        : "7m",
                "8M"        : "8m",
                "9M"        : "9m",
                "10M"       : "10m",
                "11M"       : "11m",
                "12M"       : "12m",
                "Abt"       : "about",
                "About"     : "about",
                "After"     : "after",
                "D"         : "days",
                "Day"       : "day",
                "Days"      : "days",
                "Few"       : "few",
                "H"         : "hour",
                "M"         : "months",
                "M1"        : "1m",
                "M2"        : "2m",
                "M3"        : "3m",
                "M4"        : "4m",
                "M5"        : "5m",
                "M6"        : "6m",
                "M7"        : "7m",
                "M8"        : "8m",
                "M9"        : "9m",
                "M10"       : "10m",
                "M11"       : "11m",
                "M12"       : "12m",
                "Month"     : "month",
                "Months"    : "months",
                "Of"        : "of",
                "One"       : "one",
                "Sev"       : "several",
                "Sev."      : "several",
                "Several"   : "several",
                "Some"      : "some",
                "W"         : "weeks",
                "Week"      : "week",
                "Weeks"     : "weeks",
                "Y"         : "years",
                "Year"      : "year",
                "Years"     : "years",
                "["         : "[blank]"
                };

/************************************************************************
 *  surnamePartAbbrs                                                    *
 *                                                                      *
 *  Table for expanding abbreviations in a surname.                     *
 *  Most of these are to override the default capitalization for        *
 *  prepositions used with surnames.                                    *
 ************************************************************************/
const  surnamePartAbbrs = {
                        "De"        : "de",
                        "Of"        : "of",
                        "Van"       : "van",
                        "Von"       : "von"};

/************************************************************************
 *      CauseAbbrs                                                      *
 *                                                                      *
 *  Table for expanding abbreviations in cause of death field           *
 *  Some of these are to override the default capitalization for        *
 *  prepositions and conjunctions and articles.  Others are to assist   *
 *  in corekt speling.                                                  *
 ************************************************************************/
const  CauseAbbrs = {
                "1/2"           : "½",
                "A"             : "a",
                "Abt"           : "about",
                "About"         : "about",
                "After"         : "after",
                "An"            : "an",
                "And"           : "and",
                "As"            : "as",
                "At"            : "at",
                "Be"            : "before",
                "Before"        : "before",
                "By"            : "by",
                "C"             : "Cancer",
                "D"             : "days",
                "Day"           : "day",
                "Days"          : "days",
                "Dia"           : "Diarrhoea",
                "Dip"           : "Diphtheria",
                "F"             : "Fever",
                "Few"           : "few",
                "For"           : "for",
                "From"          : "from",
                "H"             : "hour",
                "Ha"            : "Haemorrhage",
                "Inflamation"   : "Inflammation",
                "In"            : "in",
                "Is"            : "is",
                "M"             : "months",
                "Many"          : "many",
                "Month"         : "month",
                "Months"        : "months",
                "Near"          : "near",
                "Not"           : "not",
                "Of"            : "of",
                "On"            : "on",
                "One"           : "one",
                "Or"            : "or",
                "Ph"            : "Phthisis",
                "Sev"           : "several",
                "Sev."          : "several",
                "Several"       : "several",
                "Some"          : "some",
                "The"           : "the",
                "To"            : "to",
                "Tu"            : "Tuberculosis",
                "W"             : "weeks",
                "Week"          : "week",
                "Weeks"         : "weeks",
                "With"          : "with",
                "Y"             : "years",
                "Year"          : "year",
                "Years"         : "years",
                "["             : "[blank]"
                };

/************************************************************************
 *  monTab                                                              *
 *                                                                      *
 *  Translate month names and abbreviations to indices.                 *
 ************************************************************************/
const  monTab  = {
                ""          : 0,
                "ja"        : 1,
                "jan"       : 1,
                "jany"      : 1,
                "january"   : 1,
                "fe"        : 2,
                "feb"       : 2,
                "feby"      : 2,
                "february"  : 2,
                "mr"        : 3,
                "mar"       : 3,
                "march"     : 3,
                "al"        : 4,
                "apr"       : 4,
                "aprl"      : 4,
                "april"     : 4,
                "ma"        : 5,
                "may"       : 5,
                "jn"        : 6,
                "jun"       : 6,
                "june"      : 6,
                "jl"        : 7,
                "jul"       : 7,
                "july"      : 7,
                "au"        : 8,
                "aug"       : 8,
                "augt"      : 8,
                "august"    : 8,
                "se"        : 9,
                "sep"       : 9,
                "sept"      : 9,
                "september" : 9,
                "oc"        : 10,
                "oct"       : 10,
                "octr"      : 10,
                "october"   : 10,
                "no"        : 11,
                "nov"       : 11,
                "novr"      : 11,
                "november"  : 11,
                "de"        : 12,
                "dec"       : 12,
                "decr"      : 12,
                "december"  : 12 };

/************************************************************************
 *  preTab                                                              *
 *                                                                      *
 *  Prefix value for dates based upon initial reserved word.            *
 ************************************************************************/
const  preTab  = {
                ""              : 0,
                'in'            : '0',
                'on'            : '0',
                'abt'           : '1',
                'about'         : '1',
                'cir'           : '2',
                'circa'         : '2',
                'bef'           : '3',
                'before'        : '3',
                'aft'           : '4',
                'after'         : '4',
                'between'       : '5',
                'bet'           : '5',
                'wft est'       : '8',
                'est'           : 'g',
                'cal'           : 'h',
                'calculated'    : 'h',
                'from'          : 'F',
                'to'            : 'T',
                'q'             : 'Q'
                };

/************************************************************************
 *  function capitalize                                                 *
 *                                                                      *
 *  Capitalize the value of a HTML input element.                       *
 *                                                                      *
 *  Input:                                                              *
 *      element     an HTML Input element from a form                   *
 ************************************************************************/
function capitalize(element)
{
    var tmp             = element.value;
    var needCap         = true;     // capitalize 1st letter
    var msg             = "";
    for (var e = 0; e < tmp.length; e++)
    {               // scan value
        if (needCap &&
            "abcdefghijklmnopqrstuvwxyz".indexOf(tmp.charAt(e)) >= 0)
        {           // only upper case OK
            msg         += "upper case tmp[" + e + "]='" + 
                            tmp.charAt(e) + "', ";
            tmp         = tmp.substring(0,e) + tmp.charAt(e).toUpperCase() +
                            tmp.substring(e+1);
            needCap     = false;    // do not capitalize rest of word
        }           // only upper case OK
        else
        {           // other letters
            needCap     = " .,;:+".indexOf(tmp.charAt(e)) >= 0;
        }           // other letters
    }               // scan value
    //alert("CommonForm.js: capitalize('" + element.value + "'): " + msg +
    //      ", returns '" + tmp + "'");
    element.value       = tmp;      // replace with capitalized value
    return tmp;
}       // function capitalize

/************************************************************************
 *  function setErrorFlag                                               *
 *                                                                      *
 *  Set the error flag on a field value by altering the class of        *
 *  the input field to either contain or not contain 'error'            *
 *                                                                      *
 *  Input:                                                              *
 *      element     element to act on                                   *
 *      valid       true if the field value is valid                    *
 *                  false if the field value is invalid                 *
 ************************************************************************/
function setErrorFlag(element, valid)
{
    var className   = element.className;
    // clear or set the error indicator if required by changing class name
    var errpos      = className.indexOf(' error');
    if (errpos >= 0)
    {           // error currently flagged
        // if valid value, clear the flag
        if (valid)
            element.className   = className.substring(0, errpos) +
                                    className.substring(errpos + 6);
    }           // error currently flagged
    else
    {           // error not currently flagged
        if (!valid)
        {       // if in error add flag to class name
            element.className   = className + ' error';
        }       // if in error add flag to class name
    }           // error not currently flagged
}       // function setErrorFlag

/************************************************************************
 *  function expAbbr                                                    *
 *                                                                      *
 *  Expand abbreviations.  This method modifies the value               *
 *  of the element that is passed to it.  If the value contains         *
 *  words that are abbreviated, they are expanded,                      *
 *  otherwise the value is capitalized.                                 *
 *                                                                      *
 *  Input:                                                              *
 *      element         an input text element in the form               *
 *      table           table of abbreviations                          *
 *                                                                      *
 *  Returns:                                                            *
 *      updated value of element                                        *
 ************************************************************************/
function expAbbr(element, table)
{
    if (element.value.length == 0)
        return "";

    // capitalize words in value if presentation style requires it
    var textTransform   = "";
    if (element.currentStyle)       // try IE API
        textTransform   = element.currentStyle.textTransform;
    else
    if (window.getComputedStyle)    // W3C API
        textTransform   = window.getComputedStyle(element, null).textTransform;

    // break into words and check each word for keyword to expand
    var words   = element.value.split(" ");
    var result  = "";

    for(var i = 0; i < words.length; i++)
    {
        var word    = words[i];
        var key     = word;
        for (var e = 0; e < key.length; e++)
        {       // scan word
            if (key.substring(e,e+1).search(/[A-Z0-9]/) == 0)
                break;
            else
            if (key.substring(e,e+1).search(/[a-z]/) == 0)
            {       // fold initial lower case letter to upper case
                key = key.substring(0,e) + key.charAt(e).toUpperCase() +
                          key.substring(e+1);
                break;
            }       // fold initial lower case letter to upper case
        }       // scan word

        // if word starts with an open square bracket, do not include it
        var firstChar   = word.charAt(0);
        if (textTransform == "capitalize")
            firstChar       = key.charAt(0);
        if (key.length > 1 && "['\"".indexOf(firstChar) >= 0)
        {       // key starts with punctuation mark
            // do not include punctuation mark in key value
            key     = key.substring(1);
        }       // key ends with punctuation mark
        else
        {       // key does not start with special char
            firstChar   = "";
        }       // key does not start with special char

        // if word ends with a punctuation mark, do not include it
        var lastChar= key.charAt(key.length - 1);
        if (key.substring(key.length - 2) == "'s")
        {       // possessive
            key     = key.substring(0, key.length - 2);
            lastChar    = "'s";
        }       // possessive
        else
        if (",;:]".indexOf(lastChar) >= 0)
        {       // key ends with punctuation mark
            // do not include punctuation mark in key value
            key     = key.substring(0, key.length - 1);
        }       // key ends with punctuation mark
        else
        {       // key does not end with special char
            lastChar    = "";
        }       // key does not end with special char

        // do a table lookup in the table of abbreviations
        var exp = table[key];
        if (exp)
        {       // substitute word from abbreviation table
            words[i]    = firstChar + exp + lastChar;
        }       // substitute word from abbreviation table
        else
        {       // substitute folded word
            words[i]    = firstChar + key + lastChar;
        }       // substitute folded word

        // separate words with a space
        if (i > 0)
            result  += " ";
        result  += words[i];
    }       // loop through all words
    //alert("expAbbr: result='" + result + "'");
    element.value   = result;
    return result;
}       // function expAbbr

/************************************************************************
 *  function changeElt                                                  *
 *                                                                      *
 *  Take action when the user changes a field to implement              *
 *  assists such as converting to upper case and expanding              *
 *  abbreviations.                                                      *
 *  This is a static function that provides the base functionality for  *
 *  the onchange method of any <input> element.                         *
 *                                                                      *
 *  Parameters:                                                         *
 *      element         an instance of an HTML Input element            *
 ************************************************************************/
function changeElt(element)
{
    if (element.form)
    {       // parameter is a form element
        var id  = element.name;
        if (id.length == 0)
            id      = element.id;

        // trim off leading and trailing spaces
        element.value   = element.value.trim();

        // expand abbreviations if required
        if (element.abbrTbl)
            expAbbr(element,
                    element.abbrTbl);
        else
        if (element.value == '[')
            element.value   = '[Blank]';
        else
        {   // capitalize words in value if presentation style requires it
            var style       = null;
            if (element.currentStyle)       // try IE API
                style   = element.currentStyle;
            else
            if (window.getComputedStyle)    // W3C API
                style   = window.getComputedStyle(element, null);

            if (style)
            {       // have style
                var textTransform   = style.textTransform;
                // the browser only capitalizes the appearance of the text
                // this ensures that the text value is capitalized
                // when saved to the database
                if (textTransform == "capitalize")
                    capitalize(element);
            }       // have style
            else
                alert("CommonForm.js: changeElt: unable to get style for element '" + id + "'");
        }   // capitalize words in value if presentation style requires it

        // change the presentation class to highlight the changed field
        var className       = element.className;
        if (className.substr(0, 5) == 'same ')
            element.className   = 'black ' + className.substr(5);
        else
        if (className.substr(0, 3) == 'dft')
            element.className   = 'black ' + className.substr(3);
        else
        if (className.substr(0, 6) != 'black ')
        {
            var spcpos      = className.indexOf(' ');
            if (spcpos >= 0)
                element.className   = 'black' + className.substring(spcpos);
            else
                element.className   = 'black ' + className;
        }
    }       // parameter is a form element
    else
        alert("'CommonForm.js: changeElt: unable to get form, element '" +
                id + "'");
}       // function changeElt

/************************************************************************
 *  function change                                                     *
 *                                                                      *
 *  Take action when the user changes a field to implement common       *
 *  assists such as converting to upper case and expanding              *
 *  abbreviations.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function change()
{
    changeElt(this);

    if (this.checkfunc)
        this.checkfunc();
}       // function change

/************************************************************************
 *  function dateChanged                                                *
 *                                                                      *
 *  Take action when the user changes a date field                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        an instance of an HTML input element.               *
 *      ev          instance of 'change' Event                          *
 ************************************************************************/
function dateChanged(ev)
{
    var form        = this.form;

    // ensure that there is a space between a letter and a digit
    // or a digit and a letter
    var value       = this.value;
    value           = value.replace(/([a-zA-Z])([\[0-9])/g,"$1 $2");
    this.value      = value.replace(/(\d)([\[a-zA-Z])/g,"$1 $2");

    changeElt(this);    // change case and expand abbreviations

    if (this.checkfunc)
        this.checkfunc();
}       // function dateChanged

/************************************************************************
 *  function surnameChanged                                             *
 *                                                                      *
 *  Take action when the user changes the surname field                 *
 *                                                                      *
 *  Input:                                                              *
 *      this        an instance of an HTML input element.               *
 *      ev          instance of 'change' Event                          *
 ************************************************************************/
function surnameChanged(ev)
{
    var form        = this.form;

    changeElt(this);

    if (this.checkfunc)
        this.checkfunc();

    // if the page title is empty, modify it to include the name fields
    // that have been filled in so far
    if (this.name == 'Surname' && updateTitle)
    {
        var newName = '';
        var givennameElt    = form.GivenName;
        if (givennameElt)
            newName += givennameElt.value + ' ';
        newName     += this.value;
        newName     += ' (';
        var birthElt    = form.BirthDate;
        if (birthElt)
            newName += birthElt.value;
        newName     += "\u2014";
        var deathElt    = form.DeathDate;
        if (deathElt)
            newName += deathElt.value;
        newName     += ')';
        var titleElement    = document.getElementById('title');
        titleElement.innerHTML  = titlePrefix + newName;
    }
}       // function surnameChanged

/************************************************************************
 *  function givenChanged                                               *
 *                                                                      *
 *  This method is called when the user modifies the value of the       *
 *  given name of the individual.  It adjusts the default gender based  *
 *  upon the name.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <input> that invoked this function      *
 *      ev          instance of 'change' Event                          *
 ************************************************************************/
function givenChanged(ev)
{
    var form                                    = this.form;
    var givenName                               = this.value;
    if (form.Gender)
    {                   // there is a Gender selection list
        var givenNameLc                         = givenName.toLowerCase();
        var names                               = givenNameLc.split(/\s+/);
        for (var i = 0; i < names.length; i++)
        {               // loop through individual given names
            var aName   = names[i];
            if (aName in givenNames)
            {
                var givenName                   = givenNames[aName];
                if (givenName.gender == 'M')
                {
                    form.Gender.selectedIndex   = 0;
                    form.Gender.className       = 'male';
                }
                else
                if (givenName.gender == 'F')
                {
                    form.Gender.selectedIndex   = 1;
                    form.Gender.className       = 'female';
                }
                break;
            }
            else
            if (aName.substring(aName.length - 1) == 'a')
            {
                form.Gender.selectedIndex   = 1;
                form.Gender.className       = 'female';
                break;
            }
        }               // loop through individual given names
    }                   // there is a Gender selection list

    // fold to upper case and expand abbreviations
    changeElt(this);
    capitalize(this);

    if (this.checkfunc)
        this.checkfunc();

    // if the page title is empty, modify it to include the name fields
    // that have been filled in so far
    if (this.name == 'GivenName' && updateTitle)
    {
        var newName = this.value;
        var surnameElt  = form.Surname;
        if (surnameElt)
            newName += ' ' + surnameElt.value;
        newName     += ' (';
        var birthElt    = form.BirthDate;
        if (birthElt)
            newName += birthElt.value;
        newName     += "\u2014";
        var deathElt    = form.DeathDate;
        if (deathElt)
            newName += deathElt.value;
        newName     += ')';
        var titleElement    = document.getElementById('title');
        titleElement.innerHTML  = titlePrefix + newName;
    }
}   // function givenChanged

/************************************************************************
 *  function goToLink                                                   *
 *                                                                      *
 *  This function may be set as the onclick handler for an element.     *
 *  It requires that the 'href' attribute of the element has been set   *
 *  to the URL of a page to be displayed.  If the 'target' attribute has*
 *  also been set, then the page is displayed in the window with that   *
 *  name, otherwise the new page replaces the current page.             *
 *                                                                      *
 *  Input:                                                              *
 *      this        an instance of an HTML <button> element.            *
 *      ev          instance of 'click' Event                           *
 ************************************************************************/
function goToLink(ev)
{
    ev.stopPropagation();
    if (this.href)
    {       // new URL defined
        if (this.target)
        {
            window.open(this.href,
                        this.target);
        }
        else
        {
            location    = this.href;
        }   
    }       // new URL defined
    else
    {       // new URL not defined
        alert("href attribute was not set for this button");
    }       // new URL not defined
}       // function goToLink

/************************************************************************
 *  function checkName                                                  *
 *                                                                      *
 *  Validate the current value of a field containing a name.            *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkName()
{
    var element     = this;
    var re      = /^[a-zA-Z7\u00c0-\u00ff .,'"()\-&\[\]?]*$/;
    var name        = element.value;
    setErrorFlag(element, re.test(name));
}       // function checkName

/************************************************************************
 *  function checkProvince                                              *
 *                                                                      *
 *  Validate the current value of a field containing a province code.   *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkProvince()
{
    var element     = this;
    let re          = /^[a-zA-Z]{2,3}$/;
    var name        = element.value;
    setErrorFlag(element, re.test(name));
}       // function checkProvince

/************************************************************************
 *  function checkOccupation                                            *
 *                                                                      *
 *  Validate the current value of a field containing a occupation.      *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkOccupation()
{
    var element     = this;
    var re      = /^[a-zA-Z\u00c0-\u00ff\s\.,'&\-\[\]?()]*$/;
    var occupation  = element.value;
    setErrorFlag(element, re.test(occupation));
}       // function checkOccupation

/************************************************************************
 *  function checkAddress                                               *
 *                                                                      *
 *  Validate the current value of a field containing a address.         *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkAddress()
{
    var element     = this;
    var re      = /^[-a-zA-Z\u00c0-\u00ff0-9 .,'½¼¾&(){}\[\]\/?]*$/;
    var address     = element.value;
    setErrorFlag(element, re.test(address));
}       // function checkAddress

/************************************************************************
 *  function checkText                                                  *
 *                                                                      *
 *  Validate the current value of a field containing text.              *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkText()
{
    var element     = this;
    var re      = /^[a-zA-Z\u00c0-\u00ff0-9 .,:;'"()½/\[\]\-&?]*$/;
    var text        = element.value;
    setErrorFlag(element, re.test(text));
}       // function checkText

/************************************************************************
 *  function checkSex                                                   *
 *                                                                      *
 *  Validate the current value of a field containing a sex.             *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkSex()
{
    var element     = this;
    var re      = /^[MFmf?]?$/;
    var sex     = element.value;
    setErrorFlag(element, re.test(sex));
}       // function checkSex

/************************************************************************
 *  function checkMStat                                                 *
 *                                                                      *
 *  Validate the current value of a field containing a mstat.           *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkMStat()
{
    var element     = this;
    var re      = /^[BDMSWVCbdmswvc? ]?$/;
    var mstat       = element.value;
    setErrorFlag(element, re.test(mstat));
}       // function checkMStat

/************************************************************************
 *  function checkFlag                                                  *
 *                                                                      *
 *  Validate the current value of a field containing a flag.            *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkFlag()
{
    var element     = this;
    var re      = /^[ynYNMF1 ]?$/;
    var flag        = element.value;
    setErrorFlag(element, re.test(flag));
}       // function checkFlag

/************************************************************************
 *  function checkFlagSex                                               *
 *                                                                      *
 *  Validate the current value of a field containing a flag or a gender.*
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkFlagSex()
{
    var element     = this;
    var re      = /^[ynmfYNMF1 ]?$/;
    var flag        = element.value;
    setErrorFlag(element, re.test(flag));
}       // function checkFlagSex

/************************************************************************
 *  function checkDate                                                  *
 *                                                                      *
 *  Validate the current value of a field containing a date.            *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkDate()
{
    var element         = this;
    var re      = /^([A-Za-z]*)\s*([0-9]*)\s*([A-Za-z]*)\s*([0-9]*)/;
    var date            = element.value.trim();
    if (date.substring(0,1) == '[')
        date            = date.substring(1);
    if (date.slice(-1) == ']')
        date            = date.slice(0,-1);
    date                = date.trim();
    if (date.length == 0)
        return true;
    var result          = re.exec(date);
    var matched         = (typeof result === 'object') &&
                              (result instanceof Array);
    var l0, n1, l2, l3, n3, pi, mi;
    if (matched)
    {
        //console.log('matched: ' + result.toString());
        var pref        = result[1].toLowerCase();  // prefix on date or month
        l0              = pref.length;
        pi              = preTab[pref];
        if (pi === undefined)
            pi          = monTab[pref];

        var fstnum      = result[2];
        if (fstnum.length > 0)
            n1          = fstnum - 0;
        else
            n1          = 0;

        if ((pi == 'Q' || result[3].toLowerCase() == 'q') && 
            n1 >= 1 && n1 <= 4)
        {
            mi              = (3 * n1) - 2;
            l2              = 1;
        }
        else
        {
            var month       = result[3].toLowerCase();
            l2              = month.length;
            if (l2 == 0)
                mi          = pi;
            else
                mi          = monTab[month];
        }

        var sndnum      = result[4];
        l3              = sndnum.length;
        if (l3 > 0)
            n3          = sndnum - 0;
        else
            n3          = 0;
    }

    if (matched)
    {
        //console.log('checkDate: l0=' + l0 + ', n1=' + n1 + ', l2=' + l2 + ', l3=' + l3 + ', n3=' + n3 + ', pi=' + pi + ', mi=' + mi);
        matched = (((n1 > 31 && n1 < 2030 && l2 > 0 && n3 <= 31) ||  // yyyy mmm [dd]
                  (n1 <= 31 && l2 > 0 && n3 > 1000 && n3 < 2030) ||     // [dd] mmmm yyyy
                  (n1 <= 31 && l2 > 0 && l3 == 0) ||    // [dd] mmmm
                  (n1 == 0 && l0 > 0 && n3 <= 31) ||    // mmmm dd
                  (n1 == 0 && l0 > 0 && n3 == 0) ||     // mmmm
                  (n1 > 1000 && n1 < 2030 && l2 == 0 && n3 == 0)) &&    // yyyy
                  pi !== undefined &&
                  mi !== undefined) ||
                  pi == '5' ||
                  pi == 'F';
    }

    setErrorFlag(element, matched);
}       // function checkDate

/************************************************************************
 *  function checkNumber                                                *
 *                                                                      *
 *  Validate the current value of a field containing a number.          *
 *  A number of fields that use this validation occasionally have       *
 *  half-integral values, these include:                                *
 *      census district numbers                                         *
 *      pre-confederation marriage registration report numbers          *
 *      pre-confederation number of stories fields in census            *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkNumber()
{
    var element     = this;
    var re      = /^[0-9½]*$/;
    var number      = element.value.trim();
    if (number == '')
        setErrorFlag(element, true);
    else
        setErrorFlag(element, re.test(number));
}       // function checkNumber

/************************************************************************
 *  function checkPositiveNumber                                        *
 *                                                                      *
 *  Validate the current value of a field containing a positive number. *
 *  A number of fields that use this validation occasionally have       *
 *  half-integral values, these include:                                *
 *      census district numbers                                         *
 *      pre-confederation marriage registration report numbers          *
 *      pre-confederation number of stories fields in census            *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkPositiveNumber()
{
    var element     = this;
    var re      = /^[0-9½]*$/;
    var number      = element.value.trim();
    if (number == '')
        setErrorFlag(element, true);
    else
        setErrorFlag(element, re.test(number) && (number != '0'));
}       // function checkPositiveNumber

/************************************************************************
 *  function checkFamily                                                *
 *                                                                      *
 *  Validate the current value of a field containing a family number.   *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkFamily()
{
    var element     = this;
    var cell        = this.parentNode;  // table cell
    var col     = cell.cellIndex;   // column number
    var row     = cell.parentNode;  // table row
    var rowIndex    = row.sectionRowIndex;  // position in body section
    var section     = row.parentNode;   // table section
    var prevRow;
    var prevCell;
    var prevFamily  = null;
    var expFamily   = null;
    if (rowIndex > 0)
    {
        for(var ir = rowIndex - 1; ir >= 0; ir--)
        {       // loop back until find family number
            prevRow     = section.rows[ir];
            prevCell    = prevRow.cells[col];
            for(var child = prevCell.firstChild;
                        child; child = child.nextSibling)
            {
                if (child.nodeType == 1 &&
                    child.nodeName.toLowerCase() == 'input' &&
                    child.value.length > 0)
                {
                    prevFamily  = child.value;
                    expFamily   = (child.value - 0) + 1;
                    break;
                }
            }
            if (prevFamily !== null)
                break;
        }       // loop back until find family number
    }
    else
    {       // first row, nothing precedes
        prevRow     = null;
        prevFamily  = element.value;
        expFamily   = element.value;
    }       // first row, nothing precedes

    var re      = /^[0-9]*$/;
    var family      = element.value.trim();
    if (family != '')
        setErrorFlag(element, re.test(family) && 
                              (family == prevFamily || family == expFamily));
}       // function checkFamily

/************************************************************************
 *  function checkYear                                                  *
 *                                                                      *
 *  Validate the current value of a field containing a year.            *
 *  Should be 4 digit numeric year, possibly enclosed in editorial      *
 *  square brackets, a question mark, or [blank].                       *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkYear()
{
    var element     = this;
    var re      = /^([?]|[0-9]{4}|na|\[blank\])?$/;
    var year        = element.value;
        // if valid value, clear the flag
    setErrorFlag(element, re.test(year));
}       // function checkYear

/************************************************************************
 *  function checkAge                                                   *
 *                                                                      *
 *  Validate the current value of an age field.  Should be numeric age  *
 *  in years, age in months (with a suffix 'm'), a question mark,       *
 *  or [blank].                                                         *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkAge()
{
    var element     = this;
    var re      = /^[mM]?[0-9]+[mM]?$/;
    var re2     = /^(([0-9½]+)\s*[yY][a-zA-Z]*|)\s*(([0-9½]+)\s*[mM][a-zA-Z]*|)\s*(([0-9½]+)\s*[wW][a-zA-Z]*|)\s*(([0-9½]+)\s*[dD][a-zA-Z]*|)\s*(([0-9½]+)\s*[hH]|)/;
    var age     = element.value;
    setErrorFlag(element, age.length == 0 ||
                          age == '?' ||
                          age.toLowerCase() == '[blank]' ||
                          re.test(age) || 
                          re2.test(age));
}       // function checkAge

/************************************************************************
 *  function getNumeric                                                 *
 *                                                                      *
 *  Given a string consisting of decimal digits and '½' return the      *
 *  numeric value.                                                      *
 *                                                                      *
 *  Input:                                                              *
 *      number      string                                              *
 ************************************************************************/
function getNumeric(number)
{
    if (typeof number === 'undefined')
        return 0;

    var retval      = 0;
    number          = number.trim();
    var num         = 0;        // accumulate number
    for(var i = 0; i < number.length; i++)
    {           // loop through digits
        var c   = number.charAt(i);
        switch(c)
        {       // act on character
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
            {       // numeric digit
                num     = (num * 10) + (c - '0');
                break;
            }       // numeric digit

            case '½':
            {       // half
                num     += 0.5;
                break;
            }       // half
        }           // act on specific characters
    }               // loop through digits
    return num;
}       // function getNumeric

/************************************************************************
 *  function checkURL                                                   *
 *                                                                      *
 *  Validate the current value of a field containing a Uniform          *
 *  Record Location (URL).                                              *
 *  This is assigned to the checkfunc method of an element.             *
 *                                                                      *
 *  Input:                                                              *
 *      this            an instance of an HTML input element.           *
 ************************************************************************/
function checkURL()
{
    var element     = this;
    var re      = new RegExp("^(http://|https://|ftp://|)([-A-Za-z0-9_.]*)/(.*/|)(.+)$");
    var url     = element.value;
    //let result  = re.exec(url);
    //if (result)
    //    alert("checkURL: re.exec('" + url + "')=protocol=" + result[1] + ",domain=" + result[2] + ",folder=" + result[3] + ",file=" + result[4]);
    setErrorFlag(element, url.length == 0 ||re.test(url));
}       // function checkAge

/************************************************************************
 *  function getSortDate                                                *
 *                                                                      *
 *  Given a textual date, compute a sort date YYYYMMDD                  *
 *                                                                      *
 *  Returns:                                                            *
 *      integer value YYYYMMDD                                          *
 ************************************************************************/
function getSortDate(date)
{
    var re  = /([A-Za-z]*) *([0-9]*) *([A-Za-z]*) *([0-9]*)/;
    var result  = re.exec(date);
    var matched = typeof result === 'object' && result instanceof Array;
    var l1, l2, l3;
    var di  = 15;
    var mi  = 6;
    var yi  = -9999;

    if (matched)
    {
        if (result[2].length == 4)
        {
            yi  = result[2];
            if (result[4].length == 1 || result[4].length == 2)
                di  = result[4];
        }
        else
        if (result[4].length == 4)
        {
            yi  = result[4];
            if (result[2].length == 1 || result[2].length == 2)
                di  = result[2];
        }
        mi  = monTab[result[1].toLowerCase()];
        if (mi === undefined || mi == 0)
            mi  = monTab[result[3].toLowerCase()];
        if (mi === undefined || mi == 0)
            mi  = 6;
        retval  = yi*10000 + mi*100 + (di - 0);
        return retval;
    }
    else
        return -99999999;
}       // function getSortDate

/************************************************************************
 *  function getCellRelRow                                              *
 *                                                                      *
 *  Get the element in another cell in the current row at a             *
 *  relative column position.                                           *
 *                                                                      *
 *  Input:                                                              *
 *      curr    the current form input element                          *
 *      rel     the relative column to move to.  For example            *
 *              rel = -1 moves 1 column to the left, while              *
 *              rel = 2 moves 2 columns to the right.  Columns          *
 *              wrap around at the end of the row.                      *
 *                                                                      *
 *  Returns:                                                            *
 *      The input element in the requested cell.  In the event of       *
 *      any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellRelRow( curr,
                        rel)
{
    var td;         // table cell containing input element
    var col;        // current column index
    var tr;         // table row containing input element
    var row;        // current row index
    var field;      // returned value

    td          = curr.parentNode;
    if (td.cellIndex === undefined)
    {
        popupAlert("CensusForm.js: getCellRelRow: current element is not in a table cell",
                    curr);
        return curr;    // curr is not contained in a table cell
    }
    col         = td.cellIndex;
    tr          = td.parentNode;
    row         = tr.rowIndex;  // row index of current row

    // move to the requested relative column, and wrap the value
    // to the table width
    while(true)
    {
        col     += rel;     // move in requested direction
        while(col < 0)
            col += tr.cells.length;
        while(col >= tr.cells.length)
            col -= tr.cells.length;

        // identify the first element node of the requested cell
        td      = tr.cells[col];
        field   = td.firstChild;

        // the first child may not be the desired input element
        // for example if there is some text at the beginning of the cell
        while(field && field.nodeType != 1)
            field   = field.nextSibling;

        if (field && field.type && field.type == "text")
            break;
    }       // while moving

    // return requested field
    return field;
}       // function getCellRelRow

/************************************************************************
 *  function getCellFirstRow                                            *
 *                                                                      *
 *  Get the element in the first cell in the current row.               *
 *                                                                      *
 *  Input:                                                              *
 *      curr    the current form input element                          *
 *                                                                      *
 *  Returns:                                                            *
 *      The input element in the requested cell.  In the event of       *
 *      any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellFirstRow(curr)
{
    var td;         // table cell containing input element
    var col;        // current column index
    var tr;         // table row containing input element
    var row;        // current row index
    var field;      // returned value

    td  = curr.parentNode;
    if (td.cellIndex === undefined)
    {
        popupAlert("CensusForm.js: getCellFirstRow: current element is not in a table cell",
                    curr);
        return curr;    // curr is not contained in a table cell
    }
    col         = 1;        // 2nd column, 1st contains line number
    tr          = td.parentNode;
    row         = tr.rowIndex;  // row index of current row

    // identify the first element node of the requested cell
    td          = tr.cells[col];
    field       = td.firstChild;
    // the first child may not be the desired input element
    // for example if there is some text at the beginning of the cell
    while(field && field.nodeType != 1)
        field   = field.nextSibling;

    if (!field)
        alert("CensusForm.js: getCellFirstRow: unable to locate element for row=" + row +
                ", col=" + col);
    // return requested field
    return field;
}       // function getCellFirstRow

/************************************************************************
 *  function getCellLastRow                                             *
 *                                                                      *
 *  Get the element in the last cell in the current row.                *
 *                                                                      *
 *  Input:                                                              *
 *      curr        the current form input element                      *
 *                                                                      *
 *  Returns:                                                            *
 *      The input element in the requested cell.  In the event of       *
 *      any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellLastRow(curr)
{
    var td;         // table cell containing input element
    var col;        // current column index
    var tr;         // table row containing input element
    var row;        // current row index
    var field;      // returned value

    td          = curr.parentNode;
    if (td.cellIndex === undefined)
    {
        popupAlert("CensusForm.js: getCellLastRow: current element is not in a table cell",
                    curr);
        return curr;    // curr is not contained in a table cell
    }
    tr          = td.parentNode;
    // get index of 3rd last column, last contains citation button, second last
    // displays line number
    col         = tr.cells.length - 3;
    row         = tr.rowIndex;  // row index of current row

    // identify the first element node of the requested cell
    td          = tr.cells[col];
    field       = td.firstChild;
    // the first child may not be the desired input element
    // for example if there is some text at the beginning of the cell
    while(field && field.nodeType != 1)
        field   = field.nextSibling;

    if (!field)
        alert("CensusForm.js: getCellLastRow: unable to locate element for row=" + row +
                ", col=" + col);
    // return requested field
    return field;
}       // function getCellLastRow

/************************************************************************
 *  function getCellRelCol                                              *
 *                                                                      *
 *  Get the element in another cell in another row at the               *
 *  same column position.                                               *
 *                                                                      *
 *  Input:                                                              *
 *      curr    the current form input element                          *
 *      rel     the relative row to move to.  For example               *
 *              rel = -1 moves 1 row up, while                          *
 *              rel = 2 moves 2 rows down.  Rows                        *
 *              wrap around at the end of the table.                    *
 *                                                                      *
 *  Returns:                                                            *
 *      The input element in the requested cell.  In the event of       *
 *      any error in the input parameters, the current cell is returned.*
 ************************************************************************/
function getCellRelCol( curr,
                        rel)
{
    var td;         // table cell containing input element
    var col;        // current column index
    var row;        // current row index
    var tr;         // table row containing input element
    var tb;         // body section containing this row
    var field;      // returned value

    td          = curr.parentNode;
    if (td.cellIndex === undefined)
    {
        popupAlert("CensusForm.js: getCellRelCol: current element is not in a table cell: " + new XMLSerializer().serializeToString(td),
                    curr);
        return curr;    // curr is not contained in a table cell
    }

    col         = td.cellIndex; // column index of current cell
    tr          = td.parentNode;
    row         = tr.rowIndex;  // row index of current row
    tb          = tr.parentNode;// table body tag
    var msg     = "rel=" + rel + ", td.cellIndex=" + col + ", tr.rowIndex=" + row;
    //alert("tb: " + new XMLSerializer().serializeToString(tb));

    // move to the requested relative row and wrap the value
    // to the table height
    // note that row 0 contains the column header, not input fields
    row         += rel;
    while(row < 1)
        row     += tb.rows.length;
    while(row > tb.rows.length)
        row     -= tb.rows.length;
    msg += ", newrow=" + row;

    // identify the first element node of the requested cell
    tr          = tb.rows[row-1];
    td          = tr.cells[col];
    field       = td.firstChild;
    // the first child may not be the desired input element
    // for example if there is some text at the beginning of the cell
    while(field && field.nodeType != 1)
        field   = field.nextSibling;

    if (!field)
        popupAlert("CensusForm.js: getCellRelCol: " + msg + 
              "unable to locate element for row=" + row +
              ", col=" + col,
                    curr);
    //    else
    //        popupAlert("getCellRelCol: " + msg + ", newtd: " +
    //                  new XMLSerializer().serializeToString(td), curr);

    // return requested field
    return field;
}       // function getCellRelCol

/************************************************************************
 *  function tableKeyDown                                               *
 *                                                                      *
 *  Handle key strokes in input fields.  The objective is to emulate    *
 *  the behavior of cursor movement keys in a spreadsheet.              *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input> element                                     *
 *      ev          instance of 'keydown' Event                         *
 ************************************************************************/
function tableKeyDown(ev)
{
    var code                    = ev.key;

    // identify the column name and row number of the input element
    var colName                 = this.name;
    var rowNum                  = '';
    if (colName.length == 0)
        colName                 = this.id;
    var matches                 = /([a-zA-Z#_]+)(\d*)/.exec(colName);
    colName                     = matches[1];
    rowNum                      = matches[2];

    var form                    = this.form;
    var formElts                = form.elements;
    var field;

    // hide the help balloon on any keystroke
    if (helpDiv)
        helpDiv.style.display   = 'none';

    // take action based upon code
    switch (code)
    {
        case 'Tab':
        {           // go to next cell in row
            return true;
        }           // go to next cell in row

        case 'Enter':   
        case 'ArrowDown':
        {           // go to same column next row
            field               = getCellRelCol(this, 1);
            ev.preventDefault();
            if (field === undefined)
                return false;
            field.focus();          // set focus on same column next row
            if (field.select)
                field.select();     // select all of the text to replace
            return false;           // suppress default action
        }           // go to same column next row

        case 'End':
        {           // End key
            if (ev.ctrlKey)
            {       // ctrl-End
                field           = getCellLastRow(this);
                ev.preventDefault();
                if (field === undefined)
                    return false;
                field.focus();      // set focus on last column current row
                field.select();     // select all of the text to replace
                return false;       // suppress default action
            }       // ctrl-End
            break;
        }           // End key

        case 'Home':
        {           // Home key
            if (ev.ctrlKey)
            {       // ctrl-Home
                field           = getCellFirstRow(this);
                ev.preventDefault();
                if (field === undefined)
                    return false;
                field.focus();  // set focus on first column current row
                field.select(); // select all of the text to replace
                return false;   // suppress default action

            }       // ctrl-Home
            break;
        }           // Home key

        case 'ArrowLeft':
        {           // arrow left
            if ('selectionStart' in this)
            {
                if (this.selectionStart == 0)
                {
                    field       = getCellRelRow(this, -1);
                    field.focus();      // set focus on prev col same row
                    field.select();     // select all of the text to replace
                    ev.preventDefault();
                    return false;       // suppress default action
                }
            }
            break;
        }           // arrow left

        case 'ArrowUp':
        {           // arrow up
            field               = getCellRelCol(this, -1);
            ev.preventDefault();
            if (field === undefined)
                return false;
            field.focus();              // set focus on same column prev row
            field.select();             // select all of the text to replace
            return false;               // suppress default action
        }           // arrow up

        case 'ArrowRight':
        {           // arrow right
            if ('selectionStart' in this)
            {
                if (this.selectionStart == this.value.length)
                {
                    field       = getCellRelRow(this, 1);
                    field.focus();      // set focus on next col same row
                    field.select();     // select all of the text to replace
                    ev.preventDefault();
                    return false;       // suppress default action
                }
            }
            break;
        }           // right

        case 'F1':  // F1
        {
            displayHelp(this);
            ev.preventDefault();
            return false;       // suppress default action
        }           // F1

        case 'c':
        case 'C':
        {           // letter 'C'
            if (ev.altKey)
            {       // alt-C
                var correctImage    = document.getElementById('correctImage');
                if (correctImage)
                    correctImage.click();
                ev.preventDefault();
                return false;
            }       // alt-C
            break;
        }           // letter 'C'

        case 'i':
        case 'I':
        {           // letter 'I'
            if (ev.altKey)
            {       // alt-I
                var imageButton = document.getElementById('imageButton');
                if (imageButton)
                    imageButton.click();
                ev.preventDefault();
                return false;
            }       // alt-I
            break;
        }           // letter 'I'

        case 's':
        case 'S':
        {           // letter 'S'
            if (ev.ctrlKey)
            {       // ctrl-S
                form.submit();
                ev.preventDefault();
                return false;
            }       // ctrl-S
            break;
        }           // letter 'S'

        case 'u':
        case 'U':
        {           // letter 'U'
            if (ev.altKey)
            {       // alt-U
                form.submit();
            }       // alt-U
            break;
        }           // letter 'U'

        case 'z':
        case 'Z':
        {           // letter 'Z'
            if (ev.ctrlKey)
            {       // ctrl-Z
                this.value  = this.defaultValue;
                ev.preventDefault();
                return false;
            }       // ctrl-Z
            break;
        }           // letter 'Z'

        case 'Control': // ctrl key
        case 'Alt':     // alt key
        {           // only handled in conjunction with other key
            break;
        }           // only handled in conjunction with other key

        default:
        {           // other keystrokes
            break;
        }           // other keystrokes
    }               // switch on key code

    return true;
}       // function tableKeyDown

/************************************************************************
 *  function numericKeyDown                                             *
 *                                                                      *
 *  Handle key strokes in input fields that only accept integers.       *
 *                                                                      *
 *  Input:                                                              *
 *      this        <input> element                                     *
 *      ev          a 'keydown' Event                                   *
 ************************************************************************/
function numericKeyDown(ev)
{
    var key             = ev.key;
    if (/\d/.test(key))
        return true;
    if (key == '+')
        return true;
    if (key.length == 1)
    {
        ev.preventDefault();
        return false;
    }
    else
        return true;
}       // function numericKeyDown

/************************************************************************
 *  function columnClick                                                *
 *                                                                      *
 *  User clicked left button on a column header.                        *
 *  Hide or unhide the column.                                          *
 *                                                                      *
 *  Input:                                                              *
 *      this        instance of <th>                                    *
 *      ev          instance of 'click' Event                           *
 ************************************************************************/
function columnClick(ev)
{
    ev.stopPropagation();
    var colIndex    = this.cellIndex;
    var row         = this.parentNode;
    var section     = row.parentNode;
    var table       = section.parentNode;
    var body        = table.tBodies[0];
    var footer      = table.tFoot;
    var footerRow   = null;
    if (footer)
        footerRow   = footer.rows[0];
    var footerCell  = null;
    if (footerRow)
        footerCell  = footerRow.cells[colIndex];
    var newElt;
    var dataCell;
    var element;

    // hide or reveal the label text in the header and footer of the column
    if (this.holdtext && this.holdtext.length > 0)
    {       // header has been hidden
        this.style.width        = this.oldwidth;
        this.innerHTML          = this.holdtext;
        if (footerCell)
            footerCell.innerHTML    = this.holdtext;
        this.holdtext   = "";
    }       // header has been hidden
    else
    {       // hide header
        this.holdtext           = this.innerHTML;
        this.oldwidth           = this.style.width;
        this.style.width        = '0px';
        this.innerHTML          = "";
        if (footerCell)
            footerCell.innerHTML    = "";
    }       // hide header

    // if a cell in the column contains an <input type='text'> replace it
    // with an <input type='hidden'> with the same attributes
    // Note: in some browsers, which shall not be named,
    // it is not possible to just change the type attribute
    // of an <input> tag while it is in the DOM
    for(var i = 0; i < body.rows.length; i++)
    {               // loop through all rows of table body
        dataCell    = body.rows[i].cells[colIndex];

        for(element = dataCell.firstChild;
            element;
            element = element.nextSibling)
        {           // loop through all children of table cell
            if (element.nodeType == 1)
            {           // element node
                if (element.nodeName == 'INPUT')
                {
                    if (element.type == 'text')
                    {       // <input type='text'>
                        // hide the text element
                        element.type        = 'hidden';
                    }       // <input type='text'>
                    else
                    if (element.type == 'hidden')
                    {       // <input type='hidden'>
                        // unhide the hidden element
                        element.type        = 'text';
                    }       // <input type='hidden'>
                    break;  // stop searching
                }       // <input>
            }           // element node
        }           // loop through all children of cell
    }               // loop through all rows of body
}       // function columnClick

/************************************************************************
 *  function columnWiden                                                *
 *                                                                      *
 *  User clicked right button on a column header.  Widen the column.    *
 *                                                                      *
 *  Input:                                                              *
 *      this            instance of <th>                                *
 *      ev              instance of 'click' event                       *
 ************************************************************************/
function columnWiden(ev)
{
    var colIndex    = this.cellIndex;
    var row         = this.parentNode;
    var section     = row.parentNode;
    var table       = section.parentNode;
    var body        = table.tBodies[0];
    var newElt; 
    var dataCell;
    var element;

    // if a cell in the column contains an <input type='text'> increase
    // the width of the field
    for(var i = 0; i < body.rows.length; i++)
    {               // loop through all rows of table body
        dataCell    = body.rows[i].cells[colIndex];

        for(element = dataCell.firstChild;
            element;
            element = element.nextSibling)
        {           // loop through all children of table cell
            if (element.nodeType == 1)
            {           // element node
                if (element.nodeName == 'INPUT')
                {
                    if (element.type == 'text')
                    {       // <input type='text'>
                        element.size        = element.size + 
                                      Math.floor(element.size / 2);
                    }       // <input type='text'>
                    break;  // stop searching
                }       // <input>
            }           // element node
        }           // loop through all children of cell
    }               // loop through all rows of body
    return false;       // do not display menu
}       // function columnWiden

/************************************************************************
 *  function linkMouseOver                                              *
 *                                                                      *
 *  This function is called if the mouse moves over a forward or        *
 *  backward hyperlink on the invoking page.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            element the mouse moved on to                   *
 *      ev              instance of 'mouse' event                       *
 ************************************************************************/
function linkMouseOver(ev)
{
    var msgDiv  = document.getElementById('mouse' + this.id);
    if (msgDiv)
    {       // support for dynamic display of messages
        // display the messages balloon in an appropriate place on the page
        var leftOffset      = getOffsetLeft(this);
        if (leftOffset > 500)
            leftOffset  -= 200;
        msgDiv.style.left   = leftOffset + "px";
        msgDiv.style.top    = (getOffsetTop(this) - 60) + 'px';
        msgDiv.style.display    = 'block';

        // so key strokes will close window
        helpDiv         = msgDiv;
        helpDiv.onkeydown   = tableKeyDown;
    }       // support for dynamic display of messages
}       // function linkMouseOver

/************************************************************************
 *  function linkMouseOut                                               *
 *                                                                      *
 *  This function is called if the mouse moves off a forward or         *
 *  backward hyperlink on the invoking page.                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      this            element the mouse moved on to                   *
 *      ev              instance of 'mouse' event                       *
 ************************************************************************/
function linkMouseOut(ev)
{
    if (helpDiv)
    {
        helpDiv.style.display   = 'none';
        helpDiv         = null;
    }
}       // function linkMouseOut
