<?php
namespace Genealogy\GedCom;
/************************************************************************
 *  class FtmPlacDefnTag												*
 *																	    *
 *  This class represents the behavior specific to the _PLAC_DEFN tag	*
 *  at level 0 which is used by Family Tree Maker.                      *
 *																	    *
 *  0 _PLAC_DEFN        												*
 *	 1 PLAC <placename>													*
 *	  2 ABBR <abbreviation>	        									*
 *																	    *
 ************************************************************************/
class FtmPlacDefnTag extends Tag
{
    /********************************************************************
     *  function FtmPlacDefnTag::__constructor                          *
     *																	*
     *	Constructor for an instance of FtmPlacDefnTag:				    *
     *																	*
     *  Input:															*
     *	    $level  	the level in the hierarchy						*
     *	    $id			the reference identifier						*
     *	    $name   	the tag name (type)								*
     *	    $parms  	any parameters									*
     *	    $document       containing instance of Document             *
     ********************************************************************/
    function __construct($level,
						 $id,
						 $name,
                         $parms,
                         $document,
                         $parent = null)
    {
        global  $warn;
        global  $debug;

        if ($debug)
		    $warn   .= "<p>FtmPlacDefnTag::__construct($level," .
									"  '$id'," .
									"  '$name'," .
									"  '$parms')</p>\n";
		parent::__construct($level,
						    $id,
						    $name,
						    $parms,
                            $document,
						    $parent);
        $this->diagnostic('Unsupported');
    }		// FtmPlacDefnTag::__construct

    /********************************************************************
     *  function FtmPlacDefnTag::add								    *
     *																	*
     *	This factory method processes immediate children of the 	    *
     *	FTM _PLAC_DEFN tag.												*
     *																	*
     *  Input:															*
     *	    $level		the level in the hierarchy		                *
     *	    $id			the reference identifier		                *
     *	    $tagname	the tag name (type)	                        	*
     *	    $parms		any parameters		                            *
     *																	*
     *	Returns:														*
     *	    instance of Tag 		                                    *
     ********************************************************************/
    function add($level, $id, $tagname, $parms)
    {
        $childTag       = null;
		$tagname		= strtoupper($tagname);
		switch($tagname)
		{
            case 'PLAC':
            {           // <placename>
                $childTag   = new IgnoreSubTags($level,
                                                $this->id,
                                                $tagname,
                                                $parms,
                                                $this->document,
                                                $this,
                                                array('ABBR'));
				$childTag->diagnostic('Unimplemented');
            	break;
			}           // <placename>

			default:
		    {					// all others unexpected
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
				$childTag->diagnostic('Unexpected');
                break;
		    }					// all others unexpected
		}		// act on specific next level tags

		$this->register($childTag);
        return $childTag;
    }		// FtmPlacDefnTag::add
}		// class FtmPlacDefnTag

