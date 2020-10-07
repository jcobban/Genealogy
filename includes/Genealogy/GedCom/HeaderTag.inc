<?php
namespace Genealogy\GedCom;
/************************************************************************
 *  Genealogy/Gedcom/HeaderTag.inc										*
 *																		*
 *  This file contains the definitions of the classes for processing    *
 *  the contents of the HEAD tag in a GEDCOM 5.5 genealogy files.       *
 * 																		*
 *  History: 															*
 *		2018/11/24		created											*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/

require_once "Genealogy/GedCom/Tag.inc";

/************************************************************************
 *  class HeaderTag														*
 *																	    *
 *  This class represents the behavior specific to the HEAD tag			*
 *		0 HEAD															*
 *		  1 SOUR <APPROVED_SYSTEM_ID>									*
 *		    2 VERS <VERSION_NUMBER>									    *
 *		    2 NAME <NAME_OF_PRODUCT>									*
 *		    2 CORP <NAME_OF_BUSINESS>									*
 *		      3 <<ADDRESS_STRUCTURE>>									*
 *		    2 DATA <NAME_OF_SOURCE_DATA>								*
 *		      3 DATE <PUBLICATION_DATE>								    *
 *		      3 COPR <COPYRIGHT_SOURCE_DATA>							*
 *		  1 DEST <RECEIVING_SYSTEM_NAME>								*
 *		  1 DATE <TRANSMISSION_DATE>									*
 *		    2 TIME <TIME_VALUE>										    *
 *		  1 SUBM @XREF:SUBM@											*
 *		  1 SUBN @XREF:SUBN@											*
 *		  1 FILE <FILE_NAME>											*
 *		  1 COPR <COPYRIGHT_GEDCOM_FILE>								*
 *		  1 GEDC														*
 *		    2 VERS <VERSION_NUMBER>									    *
 *		    2 FORM <GEDCOM_FORM>										*
 *		  1 CHAR <CHARACTER_SET>										*
 *		    2 VERS <VERSION_NUMBER>									    *
 *		  1 LANG <LANGUAGE_OF_TEXT>									    *
 *		  1 PLAC														*
 *		    2 FORM <PLACE_HIERARCHY>									*
 *		  1 NOTE <GEDCOM_CONTENT_DESCRIPTION>							*
 *		    2 [CONT|CONC] <GEDCOM_CONTENT_DESCRIPTION>					*
 *																	    *
 *  Example:										                    *
 *     0 HEAD										                    *
 *		1 SOUR Legacy										            *
 *		 2 VERS 7.0										                *
 *		 2 NAME Legacy (R)										        *
 *		 2 CORP Millennia Corp.										    *
 *		  3 ADDR PO Box 9410										    *
 *		   4 CONT Surprise, AZ 85374									*
 *		1 DEST Legacy										            *
 *		1 DATE 13 Dec 2009										        *
 *		1 SUBM @S0@										                *
 *		1 FILE C:\Legacy\Data\RobertCobban.ged							*
 *		1 GEDC										                    *
 *		 2 VERS 5.5										                *
 *		 2 FORM LINEAGE-LINKED										    *
 *		1 CHAR ANSI										                *
 *																	    *
 *  Example:										                    *
 *		0 HEAD									                    	*
 *		 1 SOUR FTW										                *
 *		  2 VERS 9.0										            *
 *		  2 NAME Family Tree Maker for Windows							*
 *		  2 CORP Genealogy.com										    *
 *		   3 ADDR 39500 Stevenson Pl.  #204								*
 *		    4 CONT Fremont, CA 95439									*
 *		   3 PHON (510) 794-6850										*
 *		 1 DEST FTW										                *
 *		 1 DATE 1 JUL 2009										        *
 *		 1 CHAR ANSI										            *
 *		 1 FILE C:\...\My Documents\FamilyTree\Charlton.GED				*
 *		 1 GEDC										                    *
 *		  2 VERS 5.5										            *
 *		  2 FORM LINEAGE-LINKED										    *
 *		 1 _SCHEMA										                *
 *		  2 INDI										                *
 *		   3 _FA1										                *
 *		    4 LABL Marriage fact										*
 *		   3 _FA2										                *
 *		    4 LABL Fact 2										        *
 *		   3 _FA3										                *
 *		    4 LABL Fact 3										        *
 *		   3 _FA4										                *
 *		    4 LABL Fact 4										        *
 *		   3 _FA5										                *
 *		    4 LABL Fact 5										        *
 *		   3 _FA6										                *
 *		    4 LABL Fact 6										        *
 *		   3 _FA7										                *
 *		    4 LABL Fact 7										        *
 *		   3 _FA8										                *
 *		    4 LABL Fact 8										        *
 *		   3 _FA9										                *
 *		    4 LABL Fact 9										        *
 *		   3 _FA10										                *
 *		    4 LABL Fact 10										        *
 *		   3 _FA11										                *
 *		    4 LABL Fact 11										        *
 *		   3 _FA12										                *
 *		    4 LABL Fact 12										        *
 *		   3 _FA13										                *
 *		    4 LABL Fact 13										        *
 *		   3 _MREL										                *
 *		    4 LABL Relationship to Mother								*
 *		   3 _FREL										                *
 *		    4 LABL Relationship to Father								*
 *		  2 FAM										                    *
 *		   3 _FA1										                *
 *		    4 LABL Marriage fact										*
 *		   3 _FA2										                *
 *		    4 LABL Fact 2										        *
 *		   3 _MSTAT										                *
 *		    4 LABL Marriage Beginning Status							*
 *		   3 _MEND										                *
 *		    4 LABL Marriage Ending Status                               *
 ************************************************************************/
class HeaderTag extends Tag
{
    private     $fileFormat     = '';

    /********************************************************************
     *  function Tag::__constructor                                     *
     *																	*
     *	Constructor for an instance of HeaderTag:						*
     *																	*
     *  Input:															*
     *	    $level		    the level in the hierarchy					*
     *	    $id				the reference identifier					*
     *	    $name		    the tag name (type)							*
     *	    $parms		    any parameters								*
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
		    $warn   .= "<p>HeaderTag::__construct($level," .
									"  '$id'," .
									"  '$name'," .
									"  '$parms')</p>\n";
		parent::__construct($level,
						    $id,
						    $name,
						    $parms,
						    $document,
						    $parent);
    }		// HeaderTag::__construct

    /********************************************************************
     *  function HeaderTag::add							        		*
     *																	*
     *	This factory method processes immediate children of the HEAD	*
     *	tag.															*
     *																	*
     *  Input:															*
     *	    $level		the level in the hierarchy						*
     *	    $id			the reference identifier						*
     *	    $tagname	the tag name (type)								*
     *	    $parms		any parameters									*
     *																	*
     *	Returns:														*
     *	    instance of Tag 											*
     ********************************************************************/
    function add($level,
					$id,
					$tagname,
					$parms)
    {
        $childTag               = null;
		$tagname		        = strtoupper($tagname);
		switch($tagname)
		{
			case 'SOUR':	// <APPROVED_SYSTEM_ID>
		    {		// ignore subtags
				$childTag	= new HeaderSourTag($level,
						             			$this->id,
						            			$tagname,
						            			$parms,
                                                $this->document,
                                                $this);
                $this->fileFormat   = $parms;
				break;
		    }		// ignore subtags	

			case 'DATE':	// <TRANSMISSION_DATE>
		    {		// ignore subtags
				$childTag	= new IgnoreSubtags($level,
									            $this->id,
									            $tagname,
									            $parms,
									            $this->document,
									            $this,
									            array('TIME'));
				break;
		    }		// ignore subtags
				
			case 'GEDC':	// 
		    {		// ignore subtags
				$childTag	= new IgnoreSubtags($level,
									            $this->id,
									            $tagname,
									            $parms,
									            $this->document,
									            $this,
									            array('VERS',
									                  'FORM'));
				break;
		    }		// ignore subtags	

			case '_SCHEMA':	// FTM extension
		    {		// ignore subtags
				$childTag	= new IgnoreSubtags($level,
									            $this->id,
									            $tagname,
									            $parms,
									            $this->document,
									            $this,
									            array('INDI',
                                                'FAM'));
                $childTag->diagnostic('Unimplemented');
				break;
		    }		// ignore subtags	

			case 'DEST':	// <RECEIVING_SYSTEM_NAME>
                $this->fileFormat   = $parms;
			case 'SUBM':	// @XREF:SUBM@
			case 'SUBN':	// @XREF:SUBN@
			case 'FILE':	// <FILE_NAME>
			case 'COPR':	// <COPYRIGHT_GEDCOM_FILE>
            case 'LANG':	// <LANGUAGE_OF_TEXT>
            {               // tags with no children
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
				break;
            }               // tags with no children

			case 'CHAR':	// <CHARACTER_SET>
			{		// ignore subtags
				$childTag	= new IgnoreSubtags($level,
			            						$this->id,
			            						$tagname,
							    				$parms,
						    					$this->document,
						    					$this,
								    			array('VERS'));
				break;
		    }		// ignore subtags	

            // 1 PLAC
            //  2 FORM <PLACE_HIERARCHY>
			case 'PLAC':	// 
		    {		// ignore subtags
				$childTag	= new IgnoreSubtags($level,
					      	    	      		$this->id,
					        		     		$tagname,
					    		         		$parms,
					    		         		$this->document,
					    		         		$this,
					    		     	    	array('FORM'));
				break;
		    }		// ignore subtags
				
			case 'NOTE':	// <GEDCOM_CONTENT_DESCRIPTION>
		    {
				$childTag	    = new TextTag($level,
				    	        	    	  $this->id,
				    		             	  $tagname,
                                              $parms,
                                              $this->document,
                                              $this,
						             	      null,
						            		  null);
				break;
		    }

			default:
		    {               // unexpected
                $childTag           = new Tag($level,
                                              $this->id,
                                              $tagname,
                                              $parms,
                                              $this->document,
                                              $this);
                $childTag->diagnostic('Unexpected');
                break;
		    }		        // unrecognized
		}		            // act on specific next level tags

		$this->register($childTag);
        return $childTag;
    }		// HeaderTag::add

    /********************************************************************
     *  function HeaderTag::getFileFormat				        		*
     *																	*
     *	This method provides access to the style of the GEDCOM.         *
     *	Each manufacturer generates GEDCOM files which it only          *
     *	expects to be read by its own product, and into which it        *
     *	inserts genealogical information which it supports for its      *
     *	customers.                                                      *
     *																	*
     *		ANSTFILE	    Ancestral File					            *
     *		BROSKEEP	    Brother's Keeper					        *
     *		CFTREE	        Cumberland Family Tree					    *
     *		FTL	            Family Tree Legends					        *
     *		GEDFAN	        The GEDCOM Fan Creator					    *
     *		GRAMPS	        GRAMPS					                    *
     *		KITHKIN_PRO	    Kith and Kin Pro					        *
     *		PAF	Personal    Ancestral File				            	*
     *		FTW	Family      Tree Maker for Windows					    *
     *		TMG	            The Master Genealogist					    *
     *		UFTREE	        Ultimate Family Tree					    *
     *		AncestQuest	    Ancestral Quest					            *
     *		FamilyOrigins	Family Origins for Windows					*
     *		FamTreesQE	    Family Trees Quick & Easy					*
     *		FamTreeHrtg	    Family Tree Heritage					    *
     *		Legacy	        Legacy Family Tree					        *
     *		Reunion	        Reunion					                    *
     *		RootsMagic	    RootsMagic					                *
     *																	*
     *	Returns:														*
     *	    a string containing a product code                          *
     ********************************************************************/
    function getFileFormat()
    {
        return $this->fileFormat;
    }       // function HeaderTag::getFileFormat

}		// class HeaderTag

/************************************************************************
 *  class HeaderSourTag													*
 *																	    *
 *  This class represents the behavior specific to the SOUR tag         *
 *  under the HEAD tag which is used to describe the generator of       *
 *  the GEDCOM file, which permits handling product specific issues     *
 *  with particular products.                                           *
 *																	    *
 *		0 HEAD															*
 *		  1 SOUR <APPROVED_SYSTEM_ID>									*
 *		    2 VERS <VERSION_NUMBER>									    *
 *		    2 NAME <NAME_OF_PRODUCT>									*
 *		    2 CORP <NAME_OF_BUSINESS>									*
 *		      3 <<ADDRESS_STRUCTURE>>									*
 *		    2 DATA <NAME_OF_SOURCE_DATA>								*
 *		      3 DATE <PUBLICATION_DATE>								    *
 *		      3 COPR <COPYRIGHT_SOURCE_DATA>							*
 ************************************************************************/
class HeaderSourTag extends Tag
{
    /********************************************************************
     *  function HeaderSourTag::__construct								*
     *																	*
     *	There is no official published list of the <APROVED_SYSTEM_ID>  *
     *	values which are permitted in this tag.  Some of the known      *
     *	values are:                                                     *
     *																	*
     *		ANSTFILE	    Ancestral File					            *
	 *		BROSKEEP	    Brother's Keeper					        *
	 *		CFTREE	        Cumberland Family Tree					    *
	 *		FTL	            Family Tree Legends					        *
	 *		GEDFAN	        The GEDCOM Fan Creator					    *
	 *		GRAMPS	        GRAMPS					                    *
	 *		KITHKIN_PRO	    Kith and Kin Pro					        *
	 *		PAF	Personal    Ancestral File				            	*
	 *		FTW	Family      Tree Maker for Windows					    *
	 *		TMG	            The Master Genealogist					    *
	 *		UFTREE	        Ultimate Family Tree					    *
	 *		AncestQuest	    Ancestral Quest					            *
	 *		FamilyOrigins	Family Origins for Windows					*
	 *		FamTreesQE	    Family Trees Quick & Easy					*
	 *		FamTreeHrtg	    Family Tree Heritage					    *
	 *		Legacy	        Legacy Family Tree					        *
	 *		Reunion	        Reunion					                    *
	 *		RootsMagic	    RootsMagic					                *
     *																	*
     *  Input:															*
     *	    $level		    the level in the hierarchy					*
     *	    $id				the reference identifier					*
     *	    $name		    the tag name (type)						    *
     *	    $parms		    any parameters						        *
     *	    $parent		    parent tag		                            *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
						 $document,
						 $parent)
    {
        global  $warn;
        global  $debug;

        if ($debug)
		    $warn   .= "<p>HeaderSourTag::__construct($level," .
									"  '$id'," .
									"  '$name'," .
									"  '$parms'," .
									"  $parent)</p>\n";
        parent::__construct($level,
						    $id,
						    $name,
						    $parms,
						    $document,
						    $parent);
    }		// function HeaderSourTag::__construct

    /********************************************************************
     *	function HeaderSourTag::add								        *
     *																	*
     *	This factory method processes immediate children of the SOUR	*
     *	tag under the HEAD tag. 										*
     *																	*
     *  Input:															*
     *	    $level		the level in the hierarchy						*
     *	    $id			the reference identifier						*
     *	    $tagname	the tag name (type)						    	*
     *	    $parms		any parameters						            *
     *																	*
     *  Returns:														*
     *	    instance of Tag 		                                    *
     ********************************************************************/
    function add($level,
				 $id,
				 $tagname,
				 $parms)
    {
        $childTag               = null;
		$tagname		        = strtoupper($tagname);
		switch($tagname)
		{
			case 'VERS':
			{			// <VERSION_NUMBER>
                $childTag       = new Tag($level,
                                          $this->id,
                                          $tagname,
                                          $parms,
                                          $this->document,
                                          $this);
                break;
            }			// <VERSION_NUMBER>

			case 'NAME':
			{			// <NAME_OF_PRODUCT>
                $childTag       = new Tag($level,
                                          $this->id,
                                          $tagname,
                                          $parms,
                                          $this->document,
                                          $this);
                break;
            }			// <NAME_OF_PRODUCT>

			case 'CORP':
			{			// <NAME_OF_BUSINESS>
                // 2 CORP <NAME_OF_BUSINESS>
                //  3 <<ADDRESS_STRUCTURE>>
                $childTag       = new HeaderSourCorpTag($level,
                                                        $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }			// <NAME_OF_BUSINESS>

			case 'DATA':
			{			// <NAME_OF_SOURCE_DATA>
                // 2 DATA <NAME_OF_SOURCE_DATA>
                //  3 DATE <PUBLICATION_DATE>
                //  3 COPR <COPYRIGHT_SOURCE_DATA>
                $childTag       = new Tag($level,
                                          $this->id,
                                          $tagname,
                                          $parms,
                                          $this->document,
                                          $this);
                break;
            }			// <NAME_OF_SOURCE_DATA>

			default:
		    {					// all others unexpected
                $childTag       = new Tag($level,
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
    }		// function HeaderSourTag::add

}		// class HeaderSourTag

/************************************************************************
 *  class HeaderSourCorpTag												*
 *																	    *
 *  This class represents the behavior specific to the CORP tag         *
 *  under the SOUR tag under the HEAD tag which is used to describe     *
 *  corporation which markets the the generator of                      *
 *  the GEDCOM file, which permits handling product specific issues     *
 *  with particular products.                                           *
 *																	    *
 *		0 HEAD															*
 *		  1 SOUR <APPROVED_SYSTEM_ID>									*
 *		    2 CORP <NAME_OF_BUSINESS>									*
 *		      3 <<ADDRESS_STRUCTURE>>									*
 ************************************************************************/
class HeaderSourCorpTag extends Tag
{
    /********************************************************************
     *  function HeaderSourCorpTag::__construct							*
     *																	*
     *	This tag and its children contain contact information for       *
     *	the corporation which supports the generator of the file.       *
     *																	*
     *  Input:															*
     *	    $level		    the level in the hierarchy					*
     *	    $id				the reference identifier					*
     *	    $name		    the tag name (type)						    *
     *	    $parms		    any parameters						        *
     *	    $parent		    parent tag -> SOUR tag                      *
     ********************************************************************/
    function __construct($level,
                         $id,
                         $name,
                         $parms,
						 $document,
						 $parent)
    {
        global  $warn;
        global  $debug;

        if ($debug)
		    $warn   .= "<p>HeaderSourCorpTag::__construct($level," .
									"  '$id'," .
									"  '$name'," .
									"  '$parms'," .
									"  $parent)</p>\n";
        parent::__construct($level,
						    $id,
						    $name,
						    $parms,
						    $document,
						    $parent);
    }		// function HeaderSourCorpTag::__construct

    /********************************************************************
     *	function HeaderSourCorpTag::add								    *
     *																	*
     *	This factory method processes immediate children of the CORP	*
     *	tag under the HEAD tag. 										*
     *																	*
     *  Input:															*
     *	    $level		the level in the hierarchy						*
     *	    $id			the reference identifier						*
     *	    $tagname	the tag name (type)						    	*
     *	    $parms		any parameters						            *
     *																	*
     *  Returns:														*
     *	    instance of Tag 		                                    *
     ********************************************************************/
    function add($level,
				 $id,
				 $tagname,
				 $parms)
    {
        $childTag               = null;
		$tagname		        = strtoupper($tagname);
		switch($tagname)
		{
			case 'ADDR':
			{			// <Address Structure>
                $childTag       = new AddressStructure($level,
                                                       $this->id,
                                                        $tagname,
                                                        $parms,
                                                        $this->document,
                                                        $this);
                break;
            }			//  <Address Structure>

			default:
		    {					// all others unexpected
                $childTag       = new Tag($level,
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
    }		// function HeaderSourCorpTag::add

}		// class HeaderSourCorpTag
