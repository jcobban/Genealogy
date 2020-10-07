<?php
namespace Genealogy\GedCom;
use \PDO;
/************************************************************************
 *  Genealogy/Gedcom/Document.inc										*
 *																		*
 *  This file contains the definitions of the Document class for        *
 *  processing a GEDCOM 5.5 genealogy files.                            *
 * 																		*
 *  History: 															*
 *		2018/11/24		created											*
 *																		*
 *  Copyright &copy; 2018 James A. Cobban								*
 ************************************************************************/
require_once "Genealogy/common.inc";
require_once "Genealogy/GedCom/Tag.inc";
require_once "Genealogy/GedCom/HeaderTag.inc";
require_once "Genealogy/GedCom/IndiTag.inc";
require_once "Genealogy/GedCom/FamilyTag.inc";
require_once "Genealogy/GedCom/FtmPlacDefnTag.inc";

/************************************************************************
 *  class Document  													*
 *																		*
 *  This class represents the behavior of a GEDCOM 5.5 document 	    *
 ************************************************************************/
class Document
{
    private	    $nest;		    // hierarchy of tags
    private     $lastLevel;     // level of preceding line
    private     $tableName;     // translation table name

    /********************************************************************
     *  function Document::__constructor                                *
     *																	*
     *	Constructor for an instance of Document.						*
     *																	*
     *	Input:															*
     *	    $filename   file name or array identifier                   *
     *	    $array      if present an array of lines                    *
     ********************************************************************/
    function __construct($filename, $array = null)
    {
        global  $connection;
        global  $userid;

        $matches                = array();
        $result                 = preg_match('/(\w+).ged/i', 
                                             $filename, 
                                             $matches);
        if ($result) 
        {                       // filename is something.ged
            $gedname            = $matches[1];
        }                       // filename is something.ged
        else
        {                       // some other filetype
            $result             = preg_match('/(\w+)\.?(\w*)$/i',
                                             $filename, 
                                             $matches);
            if ($result)
            {                   // include unrecognized file type in name
                // note $matches[2] is empty if there is no dot in the
                // file name, or dot is the last character in the file name
                $gedname        = $matches[1] . $matches[2];
            }                   // include unrecognized file type in name
            else
            {                   //  filename does not end with a word char
                $result         = preg_match('/(\w+)[^\w]*$/i',
                                            $filename, 
                                            $matches);
                if ($result)
                {               // matched last sequence of word chars
                    $gedname    = $matches[1];
                }               // matched last sequence of word chars
                else
                {               // no word chars anywhere in filename
                    throw new \Exception("Genealogy\\GedCom\\Document: '$filename' does not contain a valid name");
                }               // no word chars anywhere in filename
            }                   //  filename does not end with a word char
        }                       // someother filetype

        // create a temporary table to hold reference id translations
        // specific to this user and file name combination
        $username               = preg_replace('[^\w]','',$userid);
        $tableName              = "`{$username}_$gedname`";
        $this->tableName        = $tableName;
        $create                 = "CREATE TABLE IF NOT EXISTS $tableName " .
                                        "(`XrefId` VARCHAR(32)," .
                                        "`ID` INTEGER," .
                                        "PRIMARY KEY(`XrefId`))";
        $stmt                   = $connection->query($create);
        if ($stmt)
        {                       // success
        }                       // success
        else
        {                       // failure
            throw new \Exception("Genealogy\\GedCom\\Document: '$create' failed. " .
                                print_r($connection->errorInfo(),true));
        }                       // failure

        // process the GEDCOM file or array of records
        if (is_array($array))
        {
            foreach($array as $line)
                $this->add($line);
        }
        else
        {
            $handle             = fopen($filename, 'rb');
            if ($handle)
            {                   // file opened
                $this->nest	        = array();
                $this->lastLevel    = 0;
                while(($line = fgets($handle)) !== false){
                    $this->add($line);
                }
                fclose($handle);
            }                   // file opened
            else
            {                   // fopen failed
                throw new \Exception("Genealogy\\GedCom\\Document: fopen('$filename') failed: " . print_r(error_get_last(), true));
            }                   // fopen failed
        }
    }		// function Document::constructor

    /********************************************************************
     *	function Document::add  										*
     *																	*
     *	This factory method creates instances of class Tag.             *
     *																	*
     *	Input:															*
     *	    $line       string                                          *
     *																	*
     *	Returns:														*
     *	    instance of Tag or null										*
     *																	*
     *	Side Effects:													*
     *	    If this method returns an instance of Tag, that instance    *
     *	    has been inserted into the tag nesting hierarchy.           *
     *	    If the return value is null then either the line was not    *
     *	    a GEDCOM tag, or it was a tag with no children that is      *
     *	    entirely handled by its parent tag, for example 'CONT' and  *
     *	    'CONC'                                                      *
     ********************************************************************/
    function add($line)
    {
        global  $warn;

        $tag                = null;
        $matches            = array();
        $result     = preg_match('/^\s*(\d+)\s+(@[^@]+@|)\s*(\w+)\s*(.*)$/',
                                 $line,
                                 $matches);
        if ($result)
        {                       // valid syntax
            $level          = intval($matches[1]);
            $xref_id        = $matches[2];
            $tag_name       = $matches[3];
            $parms          = $matches[4];

            if($level > ($this->lastLevel + 1))
                $warn       .= "<p>Level $level out of range &lt;= " . 
                                ($this->lastLevel + 1) . " line=" .
                                htmlspecialchars($line) ."</p>\n";
            else
            {                   // expected next level
                if ($level == 0)
                {               // level 0 tag
                    switch(strtoupper($tag_name))
                    {           // act on specific tag
                        case 'HEAD':
                        {       // document header
                            $tag    = new HeaderTag($level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // document header

                        case 'INDI':
                        {       // individual in the family tree
                            $tag    = new IndiTag(  $level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // individual

                        case 'FAM':
                        {       // family
                            $tag    = new FamilyTag($level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // family

                        case 'OBJE':
                        {       // media object
                            $tag    = new ObjeTag(  $level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       //media object

                        case 'NOTE':
                        {       // notes
                            $tag    = new NoteTag(  $level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // notes

                        case 'REPO':
                        {       // repository
                            $tag    = new RepositoryTag($level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // repository

                        case 'SOUR':
                        {       // source document
                            $tag    = new SourceTag($level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // source document

                        case 'SUBM':
                        {       // submitter
                            $tag    = new SubmitterTag($level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // submitter

                        case 'SUBN':
                        {       // submission
                            $tag    = new SubmissionTag($level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            break;
                        }       // submission

                        case '_PLAC_DEFN':
                        {       // FTM tag
                            $tag    = new FtmPlacDefnTag($level, 
                                                         $xref_id, 
                                                         $tag_name, 
                                                         $parms,
                                                         $this);
                            break;
                        }       // FTM tag

                        case 'TRLR':
                        {       // document trailer
                            $tag    = new TrailerTag($level, 
                                                     $xref_id, 
                                                     $tag_name, 
                                                     $parms,
                                                     $this);
                            break;
                        }       // doument trailer

                        default:
                        {       // unsupported tag
                            $tag    = new Tag(      $level, 
                                                    $xref_id, 
                                                    $tag_name, 
                                                    $parms,
                                                    $this);
                            $tag->diagnostic('Unsupported');
                            break;
                        }       // unsupported tag

                    }           // act on specific tag
                }               // level 0 tag
                else
                {               // add the tag onto the immediate parent
                    $tag    = $this->nest[$level - 1]->
                                            add($level,
				                                $xref_id,
				                                $tag_name,
				                                $parms,
				                                $this->nest[$level - 1]);
                }               // add the tag onto the immediate parent
                $this->nest[$level] = $tag;
            }                   // expected next level
            $this->lastLevel    = $level;
        }                       // valid syntax
        else
        {                       // does not match GEDCOM syntax
            $warn           .= "<p>Genealogy\\GedCom\\add: Invalid line=" .
                                htmlspecialchars($line) . "</p>\n";
        }                       // does not match GEDCOM syntax
                                htmlspecialchars($line) ."</p>\n";
        return $tag;
    }       // function Document::add

    /********************************************************************
     *	function Document::setId  										*
     *																	*
     *	This method sets the internal database record key               *
     *	associated with a particular GEDCOM cross-reference id.         *
     *																	*
     *	Input:															*
     *	    $xrefId     string                                          *
     *	    $id         integer                                         *
     *																	*
     *	Returns:														*
     *		true        record inserted									*
     *		integer     id updated, former value                        *
     *		false       failed, global $msg contains messages			*
     ********************************************************************/
    function setId($xrefId, $id)
    {
        global  $connection;
        global  $msg;

        $query          = 'SELECT * FROM ' . $this->tableName .
                                            '  WHERE `XrefId`=:xrefid';
        $stmt           = $connection->prepare($query);
        $sqlParms       = array('xrefid'    => $xrefId);
        if ($stmt->execute($sqlParms))
        {               // success
			$result	        = $stmt->fetch(PDO::FETCH_ASSOC);
			if (is_array($result))
			{		    // existing record
			    $oldId      = $result['id'];
                $update     = 'UPDATE ' . $this->tableName .
                                ' SET ID=:newid WHERE  `XrefId`=:xrefid';
                $stmt       = $connection->prepare($update);
                $sqlParms   = array('xrefid'    => $xrefId,
                                    'newid'     => $id);
		        if ($stmt->execute($sqlParms))
                {       // success
                    return $oldId;
		        }       // success
		        else
		        {       // UPDATE failed
		            $msg    .= "Document::getId: query='$update', " .
		                        "SQLparms=array('xrefid'=> $xrefId, 'newid'=> $id), " .
		                        "error=" . print_r($stmt->errorInfo(),true) .
                                ". ";
                    return false;
                }       // UPDATE failed
            }		    // existing record
            else
            {           // create new record
		        $insert         = 'INSERT INTO ' . $this->tableName .
		                            ' (`XrefId`,`ID`) VALUES(:xrefid,:id)';
		        $stmt           = $connection->prepare($insert);
		        $sqlParms       = array('xrefid'    => $xrefId,
		                                'id'        => $id);
		        if ($stmt->execute($sqlParms))
                {       // successful insert
                    return true;
		        }       // successful insert
		        else
		        {       // INSERT failed
                    $msg        .= "Document::getId: insert='$insert', " .
                            "SQLparms=array('xrefid'=> $xrefId, 'id'=> $id), " .
                            "error=" . print_r($stmt->errorInfo(),true) . ". ";
                }       // INSERT failed
            }           // create new record
        }               // success
        else
        {               // SELECT failed
            $msg        .= "Document::getId: query='$query', " .
                            "SQLparms=array('xrefid'=> $xrefId), " .
                            "error=" . print_r($stmt->errorInfo(),true) .
                            ". ";
            return false;
        }               // SELECT failed

        return false;   // updare not performed
    }       // function Document::setId

    /********************************************************************
     *	function Document::getId  										*
     *																	*
     *	This method returns the internal database record key            *
     *	associated with a particular GEDCOM cross-reference id.         *
     *																	*
     *	Input:															*
     *	    $xrefId     string                                          *
     *																	*
     *	Returns:														*
     *	    integer or null                                             *
     ********************************************************************/
    function getId($xrefId)
    {
        global  $connection;
        global  $msg;

        $query          = 'SELECT ID FROM ' . $this->tableName .
                            ' WHERE `XrefId`=:xrefid';
        $stmt           = $connection->prepare($query);
        $sqlParms       = array('xrefid'    => $xrefId);
        if ($stmt->execute($sqlParms))
        {
            $row        = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($row))
                return $row['id'];
        }
        else
        {
            $msg        .= "Document::getId: query='$query', " .
                            "SQLparms=array('xrefid'=> $xrefId), " .
                            "error=" . print_r($stmt->errorInfo(),true) .
                            ". ";
        }
        return null;
    }       // function Document::getId

    /********************************************************************
     *  function Document::drop                                         *
     *																	*
     *	Called to drop the translation table of GedCom XrefIds to       *
     *	SQL record ids once an entire GedCom file has been processed.   * 
     ********************************************************************/
    function drop()
    {
        global  $connection;

        $drop               = "DROP TABLE " . $this->tableName;
        $connection->query($drop);
    }           // function Document::destructor

}       // class Document
