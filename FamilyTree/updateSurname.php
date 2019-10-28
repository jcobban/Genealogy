<?php
/************************************************************************
 *  updateSurname.php													*
 *																		*
 *  Handle a request to update an individual surname in 				*
 *  the Legacy family tree database.									*
 *																		*
 *  Parameters (passed by POST):										*
 *		surname		unique value of surname.							*
 *		others		valid field names within the Surname record.		*
 *																		*
 *  History:															*
 *		2015/05/18		created											*
 *		2015/07/02		access PHP includes using include_path			*
 *		2016/01/19		add id to debug trace							*
 *						include http.js before util.js					*
 *		2016/02/06		use showTrace									*
 *		2017/07/31		class LegacySurname renamed to class Surname	*
 *		2018/02/04		update links									*
 *		2018/11/19      change Helpen.html to Helpen.html               *
 *		2019/07/25      functionality moved into Names.php              *
 *																		*
 *  Copyright &copy; 2019 James A. Cobban								*
 ************************************************************************/
