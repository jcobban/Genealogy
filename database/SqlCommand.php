<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  SqlCommand.php                                                      *
 *                                                                      *
 *  Issue arbitrary SQL command against the database.                   *
 *                                                                      *
 *  History:                                                            *
 *      2013/11/29      created                                         *
 *      2013/12/12      make IDIR fields into hyperlinks                *
 *      2013/12/20      escape special characters in report             *
 *      2014/01/22      right align all numeric field values            *
 *                      use proper cell style for IDIRs                 *
 *                      interpret IDLRs                                 *
 *      2014/02/07      permit WHERE clause to be empty in SELECT       *
 *                      display actual number of rows returned for      *
 *                      COUNT(*) operands                               *
 *      2014/02/16      reduce input field to 80 columns and use        *
 *                      standard style                                  *
 *      2014/03/11      add support for keyword 'AS'                    *
 *      2014/04/10      interpret IDLR, IDTR, IDMR, IDER, IDNR, IDNX,   *
 *                      and date fields                                 *
 *      2014/04/19      do not generate errors for undefined $fields    *
 *                      if the table name is undefined                  *
 *      2014/05/29      more completely parse SELECT                    *
 *      2014/07/04      interpret IDSX, IDCR                            *
 *                      display name for IDIR                           *
 *      2014/07/11      handle escapes within a string constant         *
 *      2014/11/10      add SOUNDEX to list of reserved words           *
 *      2015/01/06      display name and link for IDCR keys             *
 *      2015/01/08      display name and link for IDNR, and IDNX keys   *
 *      2015/05/09      simplify and standardize <h1>                   *
 *      2015/06/19      support strings enclosed in double quote marks  *
 *                      support numbers with decimal fraction portion   *
 *                      reject table names after AS operator            *
 *                      validate matching parentheses in expressions    *
 *                      fix error in field-name validation if table     *
 *                      name is invalid                                 *
 *      2015/06/30      support MySQL SHOW commands                     *
 *      2015/07/02      access PHP includes using include_path          *
 *      2015/07/16      support definition of new table names in        *
 *                      JOIN clause                                     *
 *                      support multiple JOIN clauses                   *
 *                      fix validation of field names                   *
 *                      only display list of valid field names for      *
 *                      specific tables where an error was detected     *
 *      2015/09/28      migrate from MDB2 to PDO                        *
 *      2016/05/16      interpret idime and type in tblSX               *
 *      2016/06/06      error when displaying IDIME field of tblSX      *
 *                      record if type field invalid                    *
 *      2017/07/27      class LegacyCitation renamed to class Citation  *
 *      2017/07/31      class LegacySurname renamed to class Surname    *
 *      2017/08/08      class LegacyChild renamed to class Child        *
 *      2017/08/16      script legacyIndivid.php renamed to Person.php  *
 *      2017/09/28      change class LegacyEvent to class Event         *
 *      2017/10/10      change class LegacyFamily to class Family       *
 *      2017/10/12      add support for DELETE with table join          *
 *      2017/10/13      class LegacyIndiv renamed to class Person       *
 *      2018/02/08      translate external table names to internal      *
 *                      names in all commands                           *
 *      2019/06/15      support subqueries in SELECT                    *
 *      2020/03/13      use FtTemplate::validateLang                    *
 *      2020/11/02      add limited support for SOURCE                  *
 *      2021/05/15      extend support for SOURCE to include DROP and   *
 *                      CREATE                                          *
 *      2021/10/17      format large numbers with separators            *
 *      2022/03/20      accept INSERT IGNORE                            *
 *                                                                      *
 *  Copyright &copy; 2022 James A. Cobban                               *
 ************************************************************************/
    require_once __NAMESPACE__ . '/Address.inc';
    require_once __NAMESPACE__ . '/Child.inc';
    require_once __NAMESPACE__ . '/Citation.inc';
    require_once __NAMESPACE__ . '/LegacyDate.inc';
    require_once __NAMESPACE__ . '/Person.inc';
    require_once __NAMESPACE__ . '/Family.inc';
    require_once __NAMESPACE__ . '/Event.inc';
    require_once __NAMESPACE__ . '/Location.inc';
    require_once __NAMESPACE__ . '/Name.inc';
    require_once __NAMESPACE__ . '/Source.inc';
    require_once __NAMESPACE__ . '/Surname.inc';
    require_once __NAMESPACE__ . '/Temple.inc';
    require_once __NAMESPACE__ . '/common.inc';

/************************************************************************
 *      $sqlReservedWords                                               *
 *                                                                      *
 *  List of reserved words from a variety of SQL implementations.       *
 ************************************************************************/
$sqlReservedWords   = array(
                'A',
                'ABORT',
                'ABS',
                'ABSOLUTE',
                'ACCESS',
                'ACTION',
                'ADA',
                'ADD',
                'ADDDATE',  // MySQL/MariaDB
                'ADDTIME',  // MySQL/MariaDB
                'ADMIN',
                'AES_DECRYPT',  // MySQL/MariaDB
                'AES_ENCRYPT',  // MySQL/MariaDB
                'AFTER',
                'AGGREGATE',
                'ALIAS',
                'ALL',
                'ALLOCATE',
                'ALSO',
                'ALTER',
                'ALWAYS',
                'ANALYSE',
                'ANALYZE',
                'AND',
                'ANY',
                'ANY_VALUE',    // MySQL/MariaDB
                'ARE',
                'ARRAY',
                'AS',
                'ASC',
                'ASCII',    // MySQL/MariaDB
                'ASENSITIVE',
                'ASSERTION',
                'ASSIGNMENT',
                'ASYMMETRIC',
                'AT',
                'ATOMIC',
                'ATTRIBUTE',
                'ATTRIBUTES',
                'AUDIT',
                'AUTHORIZATION',
                'AUTO_INCREMENT',
                'AVG',
                'AVG_ROW_LENGTH',
                'BACKUP',
                'BACKWARD',
                'BEFORE',
                'BEGIN',
                'BERNOULLI',
                'BETWEEN',
                'BIGINT',
                'BIN',          // MySQL/MariaDB
                'BINARY',
                'BIT',
                'BIT_AND',      // MySQL/MariaDB
                'BIT_COUNT',        // MySQL/MariaDB
                'BIT_LENGTH',       // MySQL/MariaDB
                'BIT_OR',       // MySQL/MariaDB
                'BIT_XOR',      // MySQL/MariaDB
                'BITVAR',
                'BLOB',
                'BOOL',
                'BOOLEAN',
                'BOTH',
                'BREADTH',
                'BREAK',
                'BROWSE',
                'BULK',
                'BY',
                'C',
                'CACHE',
                'CALL',
                'CALLED',
                'CARDINALITY',
                'CASCADE',
                'CASCADED',
                'CASE',
                'CAST',
                'CATALOG',
                'CATALOG_NAME',
                'CEIL',
                'CEILING',
                'CHAIN',
                'CHANGE',
                'CHAR',
                'CHAR_LENGTH',
                'CHARACTER',
                'CHARACTER_LENGTH',
                'CHARACTER_SET_CATALOG',
                'CHARACTER_SET_NAME',
                'CHARACTER_SET_SCHEMA',
                'CHARACTERISTICS',
                'CHARACTERS',
                'CHARSET',      // MySQL/MariaDB
                'CHECK',
                'CHECKED',
                'CHECKPOINT',
                'CHECKSUM',
                'CLASS',
                'CLASS_ORIGIN',
                'CLOB',
                'CLOSE',
                'CLUSTER',
                'CLUSTERED',
                'COALESCE',
                'COBOL',
                'COERCIBILITY',     // MySQL/MariaDB
                'COLLATE',
                'COLLATION',
                'COLLATION_CATALOG',
                'COLLATION_NAME',
                'COLLATION_SCHEMA',
                'COLLECT',
                'COLUMN',
                'COLUMN_NAME',
                'COLUMNS',
                'COMMAND_FUNCTION',
                'COMMAND_FUNCTION_CODE',
                'COMMENT',
                'COMMIT',
                'COMMITTED',
                'COMPLETION',
                'COMPRESS',
                'COMPUTE',
                'CONCAT',
                'CONCAT_WS',        // MySQL/MariaDB
                'CONDITION',
                'CONDITION_NUMBER',
                'CONNECT',
                'CONNECTION',
                'CONNECTION_NAME',
                'CONSTRAINT',
                'CONSTRAINT_CATALOG',
                'CONSTRAINT_NAME',
                'CONSTRAINT_SCHEMA',
                'CONSTRAINTS',
                'CONSTRUCTOR',
                'CONTAINS',
                'CONTAINSTABLE',
                'CONTINUE',
                'CONV',         // MySQL/MariaDB
                'CONVERSION',
                'CONVERT',
                'COPY',
                'CORR',
                'CORRESPONDING',
                'COUNT',
                'COVAR_POP',
                'COVAR_SAMP',
                'CREATE',
                'CREATEDB',
                'CREATEROLE',
                'CREATEUSER',
                'CRC32',        // MySQL/MariaDB
                'CROSS',
                'CSV',
                'CUBE',
                'CUME_DIST',
                'CURDATE',      // MySQL/MariaDB
                'CURRENT',
                'CURRENT_DATE',
                'CURRENT_DEFAULT_TRANSFORM_GROUP',
                'CURRENT_PATH',
                'CURRENT_ROLE',
                'CURRENT_TIME',
                'CURRENT_TIMESTAMP',
                'CURRENT_TRANSFORM_GROUP_FOR_TYPE',
                'CURRENT_USER',
                'CURSOR',
                'CURSOR_NAME',
                'CURTIME',      // MySQL/MariaDB
                'CYCLE',
                'DATA',
                'DATABASE',
                'DATABASES',
                'DATE',
                'DATE_ADD',     // MySQL/MariaDB
                'DATE_FORMAT',      // MySQL/MariaDB
                'DATE_SUB',     // MySQL/MariaDB
                'DATEDIFF',     // MySQL/MariaDB
                'DATETIME',
                'DATETIME_INTERVAL_CODE',
                'DATETIME_INTERVAL_PRECISION',
                'DAY',
                'DAY_HOUR',
                'DAY_MICROSECOND',
                'DAY_MINUTE',
                'DAY_SECOND',
                'DAYNAME',      // MySQL/MariaDB
                'DAYOFMONTH',
                'DAYOFWEEK',
                'DAYOFYEAR',
                'DBCC',
                'DEALLOCATE',
                'DEC',
                'DECIMAL',
                'DECLARE',
                'DEFAULT',
                'DEFAULTS',
                'DEFERRABLE',
                'DEFERRED',
                'DEFINED',
                'DEFINER',
                'DEGREE',
                'DELAY_KEY_WRITE',
                'DELAYED',
                'DELETE',
                'DELIMITER',
                'DELIMITERS',
                'DENSE_RANK',
                'DENY',
                'DEPTH',
                'DEREF',
                'DERIVED',
                'DESC',
                'DESCRIBE',
                'DESCRIPTOR',
                'DESTROY',
                'DESTRUCTOR',
                'DETERMINISTIC',
                'DIAGNOSTICS',
                'DICTIONARY',
                'DISABLE',
                'DISCONNECT',
                'DISK',
                'DISPATCH',
                'DISTINCT',
                'DISTINCTROW',
                'DISTRIBUTED',
                'DIV',
                'DO',
                'DOMAIN',
                'DOUBLE',
                'DROP',
                'DUAL',
                'DUMMY',
                'DUMP',
                'DYNAMIC',
                'DYNAMIC_FUNCTION',
                'DYNAMIC_FUNCTION_CODE',
                'EACH',
                'ELEMENT',
                'ELSE',
                'ELSEIF',
                'ELT',          // MySQL/MariaDB
                'ENABLE',
                'ENCLOSED',
                'ENCODING',
                'ENCRYPTED',
                'END',
                'END',
                'ENUM',
                'EQUALS',
                'ERRLVL',
                'ESCAPE',
                'ESCAPED',
                'EVERY',
                'EXCEPT',
                'EXCEPTION',
                'EXCLUDE',
                'EXCLUDING',
                'EXCLUSIVE',
                'EXEC',
                'EXECUTE',
                'EXISTING',
                'EXISTS',
                'EXIT',
                'EXP',
                'EXPLAIN',
                'EXTERNAL',
                'EXTRACT',
                'FALSE',
                'FETCH',
                'FIELD',        // MySQL/MariaDB
                'FIELDS',
                'FILE',
                'FILLFACTOR',
                'FILTER',
                'FINAL',
                'FIND_IN_SET',      // MySQL/MariaDB
                'FIRST',
                'FLOAT',
                'FLOAT4',
                'FLOAT8',
                'FLOOR',
                'FLUSH',
                'FOLLOWING',
                'FOR',
                'FORCE',
                'FOREIGN',
                'FORMAT',       // MySQL/MariaDB
                'FORTRAN',
                'FORWARD',
                'FOUND',
                'FOUND_ROWS',       // MySQL/MariaDB
                'FREE',
                'FREETEXT',
                'FREETEXTTABLE',
                'FREEZE',
                'FROM',
                'FROM_BASE64',      // MySQL/MariaDB
                'FROM_DAYS',        // MySQL/MariaDB
                'FROM_UNIXTIME',    // MySQL/MariaDB
                'FULL',
                'FULLTEXT',
                'FUNCTION',
                'FUSION',
                'G',
                'GENERAL',
                'GENERATED',
                'GET',
                'GET_FORMAT',       // MySQL/MariaDB
                'GET_LOCK',     // MySQL/MariaDB
                'GLOBAL',
                'GO',
                'GOTO',
                'GRANT',
                'GRANTED',
                'GRANTS',
                'GREATEST',
                'GROUP',
                'GROUP_CONCAT',     // MySQL/MariaDB
                'GROUPING',
                'HANDLER',
                'HAVING',
                'HEADER',
                'HEAP',
                'HEX',          // MySQL/MariaDB
                'HIERARCHY',
                'HIGH_PRIORITY',
                'HOLD',
                'HOLDLOCK',
                'HOST',
                'HOSTS',
                'HOUR',
                'HOUR_MICROSECOND',
                'HOUR_MINUTE',
                'HOUR_SECOND',
                'IDENTIFIED',
                'IDENTITY',
                'IDENTITY_INSERT',
                'IDENTITYCOL',
                'IF',
                'IFNULL',       // MySQL/MariaDB
                'IGNORE',
                'ILIKE',
                'IMMEDIATE',
                'IMMUTABLE',
                'IMPLEMENTATION',
                'IMPLICIT',
                'IN',
                'INCLUDE',
                'INCLUDING',
                'INCREMENT',
                'INDEX',
                'INDICATOR',
                'INFILE',
                'INFIX',
                'INHERIT',
                'INHERITS',
                'INITIAL',
                'INITIALIZE',
                'INITIALLY',
                'INNER',
                'INOUT',
                'INPUT',
                'INSENSITIVE',
                'INSERT',
                'INSERT_ID',
                'INSTANCE',
                'INSTANTIABLE',
                'INSTEAD',
                'INSTR',        // MySQL/MariaDB
                'INT',
                'INT1',
                'INT2',
                'INT3',
                'INT4',
                'INT8',
                'INTEGER',
                'INTERSECT',
                'INTERSECTION',
                'INTERVAL',
                'INTO',
                'INVOKER',
                'IS',
                'IS_FREE_LOCK',     // MySQL/MariaDB
                'IS_IPV4',      // MySQL/MariaDB
                'IS_IPV4_COMPAT',   // MySQL/MariaDB
                'IS_IPV4_MAPPED',   // MySQL/MariaDB
                'IS_IPV6',      // MySQL/MariaDB
                'IS_USED_LOCK',     // MySQL/MariaDB
                'ISAM',
                'ISNULL',
                'ISOLATION',
                'ITERATE',
                'JOIN',
                'JSON_APPEND',      // MySQL/MariaDB
                'JSON_ARRAY',       // MySQL/MariaDB
                'JSON_ARRAY_APPEND',    // MySQL/MariaDB
                'JSON_ARRAY_INSERT',    // MySQL/MariaDB
                'JSON_CONTAINS',    // MySQL/MariaDB
                'JSON_CONTAINS_PATH',   // MySQL/MariaDB
                'JSON_DEPTH',       // MySQL/MariaDB
                'JSON_EXTRACT',     // MySQL/MariaDB
                'JSON_INSERT',      // MySQL/MariaDB
                'JSON_KEYS',        // MySQL/MariaDB
                'JSON_LENGTH',      // MySQL/MariaDB
                'JSON_MERGE',       // MySQL/MariaDB
                'JSON_MERGE_PRESERVE',  // MySQL/MariaDB
                'JSON_OBJECT',      // MySQL/MariaDB
                'JSON_QUOTE',       // MySQL/MariaDB
                'JSON_REMOVE',      // MySQL/MariaDB
                'JSON_REPLACE',     // MySQL/MariaDB
                'JSON_SEARCH',      // MySQL/MariaDB
                'JSON_SET',     // MySQL/MariaDB
                'JSON_TYPE',        // MySQL/MariaDB
                'JSON_UNQUOTE',     // MySQL/MariaDB
                'JSON_VALID',       // MySQL/MariaDB
                'K',
                'KEY',
                'KEY_MEMBER',
                'KEY_TYPE',
                'KEYS',
                'KILL',
                'LANCOMPILER',
                'LANGUAGE',
                'LARGE',
                'LAST',
                'LAST_DAY',     // MySQL/MariaDB
                'LAST_INSERT_ID',
                'LATERAL',
                'LCASE',        // MySQL/MariaDB
                'LEADING',
                'LEAST',
                'LEAVE',
                'LEFT',
                'LENGTH',
                'LESS',
                'LEVEL',
                'LIKE',
                'LIMIT',
                'LINENO',
                'LINES',
                'LISTEN',
                'LN',
                'LOAD',
                'LOCAL',
                'LOCALTIME',
                'LOCALTIMESTAMP',
                'LOCATE',
                'LOCATION',
                'LOCATOR',
                'LOCK',
                'LOGIN',
                'LOGS',
                'LONG',
                'LONGBLOB',
                'LONGTEXT',
                'LOOP',
                'LOW_PRIORITY',
                'LOWER',
                'LPAD',         // MySQL/MariaDB
                'LTRIM',        // MySQL/MariaDB
                'M',
                'MAKE_SET',     // MySQL/MariaDB
                'MAKEDATE',     // MySQL/MariaDB
                'MAKETIME',     // MySQL/MariaDB
                'MAP',
                'MATCH',
                'MATCHED',
                'MAX',
                'MAX_ROWS',
                'MAXEXTENTS',
                'MAXVALUE',
                'MEDIUMBLOB',
                'MEDIUMINT',
                'MEDIUMTEXT',
                'MEMBER',
                'MERGE',
                'MESSAGE_LENGTH',
                'MESSAGE_OCTET_LENGTH',
                'MESSAGE_TEXT',
                'METHOD',
                'MICROSECOND',      // MySQL/MariaDB
                'MIDDLEINT',
                'MIN',
                'MIN_ROWS',
                'MINUS',
                'MINUTE',
                'MINUTE_MICROSECOND',
                'MINUTE_SECOND',
                'MINVALUE',
                'MLSLABEL',
                'MOD',
                'MODE',
                'MODIFIES',
                'MODIFY',
                'MODULE',
                'MONTH',
                'MONTHNAME',
                'MORE',
                'MOVE',
                'MULTISET',
                'MUMPS',
                'MYISAM',
                'NAME',
                'NAMES',
                'NATIONAL',
                'NATURAL',
                'NCHAR',
                'NCLOB',
                'NESTING',
                'NEW',
                'NEXT',
                'NO',
                'NO_WRITE_TO_BINLOG',
                'NOAUDIT',
                'NOCHECK',
                'NOCOMPRESS',
                'NOCREATEDB',
                'NOCREATEROLE',
                'NOCREATEUSER',
                'NOINHERIT',
                'NOLOGIN',
                'NONCLUSTERED',
                'NONE',
                'NORMALIZE',
                'NORMALIZED',
                'NOSUPERUSER',
                'NOT',
                'NOTHING',
                'NOTIFY',
                'NOTNULL',
                'NOWAIT',
                'NULL',
                'NULLABLE',
                'NULLIF',
                'NULLS',
                'NUMBER',
                'NUMERIC',
                'OBJECT',
                'OCT',          // MySQL/MariaDB
                'OCTET_LENGTH',
                'OCTETS',
                'OF',
                'OFF',
                'OFFLINE',
                'OFFSET',
                'OFFSETS',
                'OIDS',
                'OLD',
                'ON',
                'ONLINE',
                'ONLY',
                'OPEN',
                'OPENDATASOURCE',
                'OPENQUERY',
                'OPENROWSET',
                'OPENXML',
                'OPERATION',
                'OPERATOR',
                'OPTIMIZE',
                'OPTION',
                'OPTIONALLY',
                'OPTIONS',
                'OR',
                'ORD',          // MySQL/MariaDB
                'ORDER',
                'ORDERING',
                'ORDINALITY',
                'OTHERS',
                'OUT',
                'OUTER',
                'OUTFILE',
                'OUTPUT',
                'OVER',
                'OVERLAPS',
                'OVERLAY',
                'OVERRIDING',
                'OWNER',
                'PACK_KEYS',
                'PAD',
                'PARAMETER',
                'PARAMETER_MODE',
                'PARAMETER_NAME',
                'PARAMETER_ORDINAL_POSITION',
                'PARAMETER_SPECIFIC_CATALOG',
                'PARAMETER_SPECIFIC_NAME',
                'PARAMETER_SPECIFIC_SCHEMA',
                'PARAMETERS',
                'PARTIAL',
                'PARTITION',
                'PASCAL',
                'PASSWORD',
                'PATH',
                'PCTFREE',
                'PERCENT',
                'PERCENT_RANK',
                'PERCENTILE_CONT',
                'PERCENTILE_DISC',
                'PERIOD_ADD',       // MySQL/MariaDB
                'PERIOD_DIFF',      // MySQL/MariaDB
                'PLACING',
                'PLAN',
                'PLI',
                'POSITION',
                'POSTFIX',
                'POWER',
                'PRECEDING',
                'PRECISION',
                'PREFIX',
                'PREORDER',
                'PREPARE',
                'PREPARED',
                'PRESERVE',
                'PRIMARY',
                'PRINT',
                'PRIOR',
                'PRIVILEGES',
                'PROC',
                'PROCEDURAL',
                'PROCEDURE',
                'PROCESS',
                'PROCESSLIST',
                'PUBLIC',
                'PURGE',
                'QUARTER',      // MySQL/MariaDB
                'QUOTE',
                'RAID0',
                'RAISERROR',
                'RAND',         // MySQL/MariaDB
                'RANDOM_BYTES',     // MySQL/MariaDB
                'RANGE',
                'RANK',
                'RAW',
                'READ',
                'READS',
                'READTEXT',
                'REAL',
                'RECHECK',
                'RECONFIGURE',
                'RECURSIVE',
                'REF',
                'REFERENCES',
                'REFERENCING',
                'REGEXP',
                'REGR_AVGX',
                'REGR_AVGY',
                'REGR_COUNT',
                'REGR_INTERCEPT',
                'REGR_R2',
                'REGR_SLOPE',
                'REGR_SXX',
                'REGR_SXY',
                'REGR_SYY',
                'REINDEX',
                'RELATIVE',
                'RELEASE',
                'RELEASE_ALL_LOCKS',    // MySQL/MariaDB
                'RELEASE_LOCK',         // MySQL/MariaDB
                'RELOAD',
                'RENAME',
                'REPEAT',
                'REPEATABLE',
                'REPLACE',
                'REPLICATION',
                'REQUIRE',
                'RESET',
                'RESIGNAL',
                'RESOURCE',
                'RESTART',
                'RESTORE',
                'RESTRICT',
                'RESULT',
                'RETURN',
                'RETURNED_CARDINALITY',
                'RETURNED_LENGTH',
                'RETURNED_OCTET_LENGTH',
                'RETURNED_SQLSTATE',
                'RETURNS',
                'REVERSE',              // MySQL/MariaDB
                'REVOKE',
                'RIGHT',
                'RLIKE',
                'ROLE',
                'ROLLBACK',
                'ROLLUP',
                'ROUND',                // MySQL/MariaDB
                'ROUTINE',
                'ROUTINE_CATALOG',
                'ROUTINE_NAME',
                'ROUTINE_SCHEMA',
                'ROW',
                'ROW_COUNT',
                'ROW_NUMBER',
                'ROWCOUNT',
                'ROWGUIDCOL',
                'ROWID',
                'ROWNUM',
                'RPAD',                 // MySQL/MariaDB
                'RTRIM',                // MySQL/MariaDB
                'ROWS',
                'RULE',
                'SAVE',
                'SAVEPOINT',
                'SCALE',
                'SCHEMA',
                'SCHEMA_NAME',
                'SCHEMAS',
                'SCOPE',
                'SCOPE_CATALOG',
                'SCOPE_NAME',
                'SCOPE_SCHEMA',
                'SCROLL',
                'SEARCH',
                'SEC_TO_TIME',          // MySQL/MariaDB
                'SECOND',
                'SECOND_MICROSECOND',
                'SECTION',
                'SECURITY',
                'SELECT',
                'SELF',
                'SENSITIVE',
                'SEPARATOR',
                'SEQUENCE',
                'SERIALIZABLE',
                'SERVER_NAME',
                'SESSION',
                'SESSION_USER',
                'SET',
                'SETOF',
                'SETS',
                'SETUSER',
                'SHARE',
                'SHOW',
                'SHUTDOWN',
                'SIGN',                 // MySQL/MariaDB
                'SIGNAL',
                'SIMILAR',
                'SIMPLE',
                'SIZE',
                'SLEEP',                // MySQL/MariaDB
                'SMALLINT',
                'SOME',
                'SONAME',
                'SOUNDEX',
                'SOUNDS_LIKE',          // MySQL/MariaDB
                'SOURCE',
                'SPACE',
                'SPATIAL',
                'SPECIFIC',
                'SPECIFIC_NAME',
                'SPECIFICTYPE',
                'SQL',
                'SQL_BIG_RESULT',
                'SQL_BIG_SELECTS',
                'SQL_BIG_TABLES',
                'SQL_CALC_FOUND_ROWS',
                'SQL_LOG_OFF',
                'SQL_LOG_UPDATE',
                'SQL_LOW_PRIORITY_UPDATES',
                'SQL_SELECT_LIMIT',
                'SQL_SMALL_RESULT',
                'SQL_WARNINGS',
                'SQLCA',
                'SQLCODE',
                'SQLERROR',
                'SQLEXCEPTION',
                'SQLSTATE',
                'SQLWARNING',
                'SQRT',
                'SSL',
                'STABLE',
                'START',
                'STARTING',
                'STATE',
                'STATEMENT',
                'STATIC',
                'STATISTICS',
                'STATUS',
                'STDDEV',               // MySQL/MariaDB
                'STDDEV_POP',
                'STDDEV_SAMP',
                'STDIN',
                'STDOUT',
                'STORAGE',
                'STR_TO_DATE',          // MySQL/MariaDB
                'STRAIGHT_JOIN',
                'STRCMP',               // MySQL/MariaDB
                'STRICT',
                'STRING',
                'STRUCTURE',
                'STYLE',
                'SUBCLASS_ORIGIN',
                'SUBDATE',              // MySQL/MariaDB
                'SUBLIST',
                'SUBMULTISET',
                'SUBSTR',               // MySQL/MariaDB
                'SUBSTRING',
                'SUBSTRING_INDEX',      // MySQL/MariaDB
                'SUBTIME',              // MySQL/MariaDB
                'SUCCESSFUL',
                'SUM',
                'SUPERUSER',
                'SYMMETRIC',
                'SYNONYM',
                'SYSDATE',
                'SYSID',
                'SYSTEM',
                'SYSTEM_USER',
                'TABLE',
                'TABLE_NAME',
                'TABLES',
                'TABLESAMPLE',
                'TABLESPACE',
                'TEMP',
                'TEMPLATE',
                'TEMPORARY',
                'TERMINATE',
                'TERMINATED',
                'TEXT',
                'TEXTSIZE',
                'THAN',
                'THEN',
                'TIES',
                'TIME',
                'TIME_FORMAT',          // MySQL/MariaDB
                'TIME_TO_SEC',          // MySQL/MariaDB
                'TIMEDIFF',             // MySQL/MariaDB
                'TIMESTAMP',
                'TIMESTAMPADD',         // MySQL/MariaDB
                'TIMESTAMPDIFF',        // MySQL/MariaDB
                'TIMEZONE_HOUR',
                'TIMEZONE_MINUTE',
                'TINYBLOB',
                'TINYINT',
                'TINYTEXT',
                'TO',
                'TO_BASE64',            // MySQL/MariaDB
                'TO_DAYS',              // MySQL/MariaDB
                'TO_SECONDS',           // MySQL/MariaDB
                'TOAST',
                'TOP',
                'TOP_LEVEL_COUNT',
                'TRAILING',
                'TRAN',
                'TRANSACTION',
                'TRANSACTION_ACTIVE',
                'TRANSACTIONS_COMMITTED',
                'TRANSACTIONS_ROLLED_BACK',
                'TRANSFORM',
                'TRANSFORMS',
                'TRANSLATE',
                'TRANSLATION',
                'TREAT',
                'TRIGGER',
                'TRIGGER_CATALOG',
                'TRIGGER_NAME',
                'TRIGGER_SCHEMA',
                'TRIM',
                'TRUE',
                'TRUNCATE',
                'TRUSTED',
                'TSEQUAL',
                'TYPE',
                'UCASE',                // MySQL/MariaDB
                'UESCAPE',
                'UID',
                'UNBOUNDED',
                'UNCOMMITTED',
                'UNDER',
                'UNDO',
                'UNENCRYPTED',
                'UMHEX',                // MySQL/MariaDB
                'UNION',
                'UNIQUE',
                'UNKNOWN',
                'UNLISTEN',
                'UNLOCK',
                'UNNAMED',
                'UNNEST',
                'UNSIGNED',
                'UNTIL',
                'UPDATE',
                'UPDATETEXT',
                'UPPER',
                'USAGE',
                'USE',
                'USER',
                'USER_DEFINED_TYPE_CATALOG',
                'USER_DEFINED_TYPE_CODE',
                'USER_DEFINED_TYPE_NAME',
                'USER_DEFINED_TYPE_SCHEMA',
                'USING',
                'UTC_DATE',
                'UTC_TIME',
                'UTC_TIMESTAMP',
                'UUID',                 // MySQL/MariaDB
                'UUID_SHORT',           // MySQL/MariaDB
                'VACUUM',
                'VALID',
                'VALIDATE',
                'VALIDATOR',
                'VALUE',
                'VALUES',
                'VAR_POP',
                'VAR_SAMP',
                'VARBINARY',
                'VARCHAR',
                'VARCHAR2',
                'VARCHARACTER',
                'VARIABLE',
                'VARIABLES',
                'VARIANCE',             // MySQL/MariaDB
                'VARYING',
                'VERBOSE',
                'VERSION',              // MySQL/MariaDB
                'VIEW',
                'VOLATILE',
                'WAITFOR',
                'WEEK',                 // MySQL/MariaDB
                'WEEKDAY',              // MySQL/MariaDB
                'WEEKOFYEAR',           // MySQL/MariaDB
                'WHEN',
                'WHENEVER',
                'WHERE',
                'WHILE',
                'WIDTH_BUCKET',
                'WINDOW',
                'WITH',
                'WITHIN',
                'WITHOUT',
                'WORK',
                'WRITE',
                'WRITETEXT',
                'X509',
                'XOR',
                'YEAR',
                'YEAR_MONTH',
                'YEARWEEK',             // MySQL/MariaDB
                'ZEROFILL',
                'ZONE');

/************************************************************************
 *  Folding SQL data types to upper case.                               *
 ************************************************************************/
$sqlTypes   = array(
                'bigint',
                'blob',
                'date',
                'datetime',
                'decimal',
                'double',
                'enum',
                'float',
                'longblob',
                'longtext',
                'mediumblob',
                'mediumint',
                'mediumtext',
                'set',
                'smallint',
                'text',
                'time',
                'timestamp',
                'tinyint',
                'tinytext',
                'unsigned',
                'varchar',
                'int',
                'char',
                'year');

$sqlTypesUpper  = array(
                'BIGINT',
                'BLOB',
                'DATE',
                'DATETIME',
                'DECIMAL',
                'DOUBLE',
                'ENUM',
                'FLOAT',
                'LONGBLOB',
                'LONGTEXT',
                'MEDIUMBLOB',
                'MEDIUMINT',
                'MEDIUMTEXT',
                'SET',
                'SMALLINT',
                'TEXT',
                'TIME',
                'TIMESTAMP',
                'TINYINT',
                'TINYTEXT',
                'UNSIGNED',
                'VARCHAR',
                'INT',
                'CHAR',
                'YEAR');

/************************************************************************
 *  Token types                                                             *
 ************************************************************************/
define("FIELD_NAME",    0);
define("TABLE_NAME",    1);
define("STRING_TYPE",   2);
define("NUMBER_TYPE",   3);
define("OPERATOR_TYPE", 4);
define("RESERVED_WORD", 5);
define("PHPSOUNDEX",    6);

$badTables  = array();
 
/************************************************************************
 *  function validateFieldNames                                         *
 *                                                                      *
 *  Examine a string containing expressions including field names       *
 *  against the list of valid fields for the current table.             *
 *                                                                      *
 *  Parameters:                                                         *
 *      $operands       string containing field names                   *
 *                                                                      *
 *  Returns:                                                            *
 *      String containing error message text.                           *
 ************************************************************************/
function validateFieldNames($operands)
{
    global  $fields;        // field names by table
    global  $table;         // main table name
    global  $tableName;     // alternate table name
    global  $tables;        // array of defined tables
    global  $sqlReservedWords;  // 
    global  $connection;
    global  $debug;
    global  $warn;
    global  $badTables;
    global  $template;

    if ($debug)
        $warn           .= "<p>validateFieldNames('$operands')</p>\n";
    $emsg               = '';
    $start              = 0;
    $bracketDepth       = 0;
    $addNextFieldName   = false;
    $lastTokenType      = null;

    while (preg_match("/('.*?')|(\".*?\")|(\d+(.\d+|))|(`\w+`)|(\w+)|([-+*\/()|&!<=>,.])/", 
                      $operands, 
                      $avar, 
                      PREG_OFFSET_CAPTURE, 
                      $start) == 1)
    {       // loop through all words 
        $expr           = $avar[0][0];
        $off            = $avar[0][1];
        $start          = $off + strlen($expr);
        $type           = null;
        for($ip = 1; $ip < count($avar); $ip++)
        {
            if (strlen($avar[$ip][0]) > 0)
                break;
        }

        switch($ip)
        {           // act on index of first match
            case 1:
            {           // string using single quote
                $type       = STRING_TYPE;
                if (substr($expr, strlen($expr) - 2, 2) == "\\'")
                {       // escaped single quote in string
                    for ($p = $start; $p < strlen($operands); $p++)
                    {       // look for real end of string
                        if (substr($operands, $p, 1) == '\'')
                        {   // closing quote of string
                            $p++;
                            $expr   = $expr .
                                          substr($operands, $start, $p-$start);
                            $start  = $p;
                            break;
                        }   // closing quote of string
                        else
                        if (substr($operands, $p, 1) == '\\')
                        {   // escape
                            $p  ++; // skip over next character
                        }   // escape
                    }       // look for real end of string
                }       // escaped single quote in string
                break;
            }           // string using single quote

            case 2:
            {           // string using double quote
                $type       = STRING_TYPE;
                if (substr($expr, strlen($expr) - 2, 2) == '\\"')
                {       // escaped single quote in string
                    for ($p = $start; $p < strlen($operands); $p++)
                    {       // look for real end of string
                        if (substr($operands, $p, 1) == '"')
                        {   // closing quote of string
                            $p++;
                            $expr   = $expr .
                                          substr($operands, $start, $p-$start);
                            $start  = $p;
                            break;
                        }   // closing quote of string
                        else
                        if (substr($operands, $p, 1) == '\\')
                        {   // escape
                            $p  ++; // skip over next character
                        }   // escape
                    }       // look for real end of string
                }       // escaped single quote in string
                break;
            }           // string using double quote

            case 3:
            {
                $type       = NUMBER_TYPE;
                break;
            }

            case 4:
            {           // fractional portion of decimal number
                break;
            }           // fractional portion of decimal number

            case 5:
            {           // name in back-quotes
                $expr   = substr($expr, 1, strlen($expr) - 2);
                if (in_array(strtoupper($expr), $sqlReservedWords))
                    $type   = RESERVED_WORD;
                else
                if (in_array($expr, $tables))
                    $type   = TABLE_NAME;
                else
                    $type   = FIELD_NAME;
                break;
            }           // name in back-quotes

            case 6:
            {           // word
                if (strtoupper($expr) == 'PHPSOUNDEX')
                    $type   = PHPSOUNDEX;
                else
                if (in_array(strtoupper($expr), $sqlReservedWords))
                    $type   = RESERVED_WORD;
                else
                if (in_array($expr, $tables))
                    $type   = TABLE_NAME;
                else
                    $type   = FIELD_NAME;
                break;
            }           // word

            case 7:
            {
                $type   = OPERATOR_TYPE;
                break;
            }
        }           // act on index of first match
        
        switch($type)
        {           // act on token type
            case FIELD_NAME:
            {           // a name
                if ($addNextFieldName)
                {       // preceding word was 'AS'
                    if (in_array($expr, $tables))
                        $emsg   .= "Cannot define table name `$expr` " . 
                                   "as a fieldname using AS. ";
                    else
                    {       // add new field name
                        $fields[$table][]   = $expr;
                        $addNextFieldName   = false;
                    }       // add new field name
                }       // preceding word was 'AS'

                if (!is_null($tableName))
                {       // field name in specific table
                    if (is_array($fields[$tableName]))
                    {
                        if (in_array(strtolower($expr), 
                                     array_map('strtolower', $fields[$tableName])))
                        {
                            if ($debug)
                                $warn   .= "<p>'$expr' at $off is a field name " .
                                        "in $tableName</p>\n";
                        }
                        else
                        {
                            $emsg    .= "`$expr` is not a field name in " .
                                        "`$tableName`. ";
                            $badTables[$tableName]  = true;
                        }
                    }
                    else
                        print "<p>\$fields['$tableName'] is " .
                                $fields[$tableName] . "</p>\n";
                    $tableName  = null;
                }       // field name in specific table
                else
                if (in_array($table, $tables) &&
                    in_array(strtolower($expr), 
                             array_map('strtolower', $fields[$table])))
                {       // field name in main table
                    if ($debug)
                        $warn   .= "<p>'$expr' at $off is a field name</p>\n";
                }       // field name in main table
                else
                {       // invalid field name
                    if ($debug)
                        $warn   .= "<p>`$expr` is not a field name in table `$table`. </p>\n";
                    $mtxt       = $template['badFieldName']->outerHTML;
                    $emsg       .= str_replace(array('$expr','$table'),
                                               array($expr, $table),
                                               $mtxt);
                    $badTables[$table]  = true;
                }       // invalid field name
                break;
            }           // a name

            case TABLE_NAME:
            {           // table name
                if ($debug)
                    $warn   .= "<p>'$expr' at $off is a table name</p>\n";
                $tableName  = $expr;
                break;
            }           // table name

            case RESERVED_WORD:
            {
                if ($debug)
                    $warn   .= "<p>'$expr' at $off is a reserved word</p>\n";
                if (strtoupper($expr) == 'AS' &&
                    $lastTokenType == FIELD_NAME)
                    $addNextFieldName   = true;
                break;
            }           // reserved word

            case STRING_TYPE:
            case NUMBER_TYPE:
            {       // constant
                if ($debug)
                    $warn   .= "<p>$expr at $off is a constant</p>\n";
                break;
            }       // constant

            case OPERATOR_TYPE:
            {       // operator
                if ($debug)
                    $warn   .= "<p>$expr at $off is an operator</p>\n";
                if ($expr == '(')
                    $bracketDepth++;
                else
                if ($expr == ')')
                {
                    $bracketDepth--;
                    if ($bracketDepth < 0)
                        $emsg   .= "Mis-matched parentheses at $off. ";
                }
                break;
            }       // operator

            default:
            {
                if ($debug)
                {
                    if (is_null($type))
                        $warn   .= "<p>\$type is null</p>\n";
                    else
                        $warn   .= "<p>\$type is $type</p>\n";
                }
                break;
            }
        }           // act on token type
        $lastTokenType  = $type;
    }       // loop through all words
    if ($bracketDepth > 0)
        $emsg   .= "Mis-matched parentheses at end. ";

    return $emsg;
}       // function validateFieldNames
 
/************************************************************************
 *  function parseJoin                                                  *
 *                                                                      *
 *  Examine a string containing join clauses                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      $join       string containing table JOINs                       *
 *                                                                      *
 *  Returns:                                                            *
 *      String containing error message text.                           *
 ************************************************************************/
function parseJoin($join)
{
    global  $debug;
    global  $warn;
    global  $tables;        // array of defined tables
    global  $fields;        // array of fields by table

    $msgs   = '';
    $joinPattern    = "#^\s*((LEFT|RIGHT)\s+|)((INNER|OUTER|CROSS|)\s+|)(JOIN|STRAIGHT_JOIN)\s+(`(\w+)`|\w+)\s+((AS)\s+|)(\w+)(.*)#i";
    $onPattern      = "#(.*?)(LEFT|RIGHT|INNER|OUTER|CROSS|JOIN|STRAIGHT)(\s.*)#";

    while (strlen($join) > 0)
    {           // something left to JOIN
        $result     = preg_match($joinPattern, $join, $matches);
        //if ($debug)
        //    $warn .= "<p>\$matches=" . print_r($matches,true) . "</p>\n";
    
        if ($result > 0)
        {
            $leftRight  = strtoupper($matches[2]);
            $innerOuter = strtoupper($matches[4]);
            $joinType   = strtoupper($matches[5]);
            if (strlen($matches[7]) > 0)
                $tableName  = $matches[7];
            else
                $tableName  = $matches[6];
            if (!in_array($tableName, $tables))
                $msgs   .= "Table name `$tableName` in JOIN is invalid. " ;
            // $matches[8] is 'AS ' if specified
            // $matches[9] is 'AS' if specified
            if (strtoupper($matches[10]) == 'ON')
                $alias  = '';
            else
            {
                $alias      = $matches[10];
                $tables[]   = $alias;   // add table name
                if ($debug)
                    $warn   .= "<p>Add '$alias' as a table</p>\n";
                $fields[$alias] = $fields[$tableName];
                print_r($fields[$alias], true) . "</p>\n";
            }
            $join   = $matches[11];
            if (strlen($join) > 0)
            {
                $result     = preg_match($onPattern, $join, $matches);
                //if ($debug)
                //    $warn .= "<p>\$matches=" . print_r($matches,true) . "</p>\n";
                if ($result == 1)
                {
                $join       = $matches[2] . $matches[3];
                }
                else
                {
                    $join   = '';
                }
            }       // something after last JOIN
        }       // parse of JOIN succeeded
    }           // something left to JOIN
}       // parseJoin

/************************************************************************
 *  function cellToHtml                                                 *
 *                                                                      *
 *  Examine a string containing join clauses                            *
 *                                                                      *
 *  Parameters:                                                         *
 *      $template       page template                                   *
 *      $table          table name                                      *
 *      $row            associative array of fields                     *
 *      $fldname        field name from database record                 *
 *      $value          field value                                     *
 *      $cellClass      class for displaying cells in this row          *
 *                                                                      *
 *  Returns:                                                            *
 *      String                                                          *
 ************************************************************************/
function cellToHtml($template, $table, $row, $fldname, $value, $cellclass)
{
    global $sqlTypes, $sqlTypesUpper, $warn, $idetTranslate;

    $retval                 = '';
    if ($fldname == 'idime' && 
        $table == 'tblSX' &&
        array_key_exists('type', $row))
    {       // translate to specific record key
        if (array_key_exists($row['type'],
                             Citation::$recType))
        { 
            $fldname = Citation::$recType[$row['type']];
            $fldname = strtolower($fldname);
        }
    }       // translate to specific record key

    if (ctype_digit($value))
    {               // integer value
        switch ($fldname)
        {           // act on specific field names
            case 'idir':
            case 'idirhusb':
            case 'idirwife':
            case 'd_idir':
            case 'm_idir':
            {       // IDIR field
                $person     = new Person(array('idir'=>$value));
                $name       = $person->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/Person.php?idir=$value", $value, $name),
                $ttext);
                break;
            }       // IDIR field

            case 'idlr':
            case 'idlrevent':
            case 'idlrbirth':
            case 'idlrchris':
            case 'idlrdeath':
            case 'idlrburied':
            case 'idlrmar':
            {       // IDLR field
                $location   = new Location(array('idlr' => $value));
                $name       = $location->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/Location.php?idlr=$value", $value, $name),
                $ttext);
                break;
            }       // IDLR field

            case 'idtr':
            case 'idtrbaptism':
            case 'idtrconfirmation':
            case 'idtrinitiatory':
            case 'idtrendow':
            {       // IDTR field
                $temple     = new Temple(array('idtr' => $value));
                $name       = $temple->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/Temple.php?idtr=$value", $value, $name),
                $ttext);
                    break;
            }       // IDTR field

            case 'idmr':
            case 'idmrparents':
            case 'idmrpref':
            case 'marriednamemaridid':
            {       // IDMR field
                $family     = new Family(array('idmr' => $value));
                $name       = $family->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/editMarriages.php?idmr=$value", $value, $name),
                $ttext);
                break;
            }       // IDMR field

            case 'ider':
            {       // IDER field
                $event      = new Event(array('ider' => $value));
                $name       = $event->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/editEvent.php?ider=$value", $value, $name),
                $ttext);
                break;
            }       // IDER field

            case 'idcr':
            {       // IDCR field
                $child      = new Child(array('idcr' => $value));
                $name       = $child->getName(Child::NAME_INCLUDE_DATES);
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/getRecordXml.php?idcr=$value", $value, $name),
                $ttext);
                break;
            }       // IDCR field

            case 'idnr':
            {       // IDNR field
                $surname    = new Surname(array('idnr' => $value));
                $name       = $surname->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/Names?idnr=$value", $value, $name),
                $ttext);
                break;
            }       // IDNR field

            case 'idnx':
            {       // IDNX field
                $namer      = new Name(array('idnx' => $value));
                $name       = $namer->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/editName.php?idnx=$value", $value, $name),
                $ttext);
                break;
            }       // IDNX field

            case 'idsx':
            {       // field displayed using getRecordXml
                $ttext      = $template['recordCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE'),
                array($cellclass, "/getRecordXml.php?$fldname=$value", $value),
                $ttext);
                break;
            }       // field displayed using getRecordXml

            case 'idsr':
            {       // IDSR field
                $source     = new Source(array('idsr' => $value));
                $name       = $source->getName();
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/Source.php?idsr=$value", $value, $name),
                $ttext);
                break;
            }       // IDSR field

            case 'idar':
            case 'idar2':
            {       // IDAR field
                if ($value > 0)
                {
                    $address    = new Address(array('idar' => $value));
                    $name       = $address->getName();
                }
                else
                    $name       = 'Undefined';
                $ttext      = $template['linkCell']->outerHTML;
                $retval     =
    str_replace(array('$CELLCLASS', '$LINK', '$VALUE', '$NAME'),
                array($cellclass, "/FamilyTree/Address.php?idar=$value", $value, $name),
                $ttext);
                break;
            }       // IDAR field

            case 'idet':
            {       // IDET field in Event
                $name       = $idetTranslate[$value];
                $ttext      = $template['interpretCell']->outerHTML;
                $retval     =
                    str_replace(array('$CELLCLASS', '$VALUE', '$TEXT'),
                                array($cellclass, $value, $name),
                                $ttext);
                break;
            }       // IDET field


            case 'birthd':
            case 'chrisd':
            case 'deathd':
            case 'buriedd':
            case 'eventd':
            case 'enteredd':
            {       // date field
                $date       = new LegacyDate($value);
                $ttext      = $template['dateCell']->outerHTML;
                $retval     =
                    str_replace(array('$CELLCLASS', '$DATE'),
                                array($cellclass, $date->toString()),
                                $ttext);
                break;
            }       // date field

            case 'type':
            {       // tblSX citation type field
                if ($table == 'tblSX')
                {
                    $text   = Citation::$intType[$value];
                    $ttext  = $template['interpretCell']->outerHTML;
                    $retval     =
                    str_replace(array('$CELLCLASS', '$VALUE', '$TEXT'),
                                array($cellclass, $value, $text),
                                $ttext);
                }
                else
                {
                    $ttext      = $template['simpleCell']->outerHTML;
                    $retval     =
                    str_replace(array('$CELLCLASS', '$VALUE'),
                                array($cellclass, $value),
                                $ttext);
                }
                break;
            }       // tblSX citation type field

            case 'gender':
            {       // gender code
                switch($value)
                {
                    case 0:
                        $text       = 'male';
                        break;

                    case 1:
                        $text       = 'female';
                        break;

                    default:
                        $text       = 'unknown';
                        break;

                }
                $ttext          = $template['intStyleCell']->outerHTML;
                $retval         =
                    str_replace(array('$CELLCLASS', '$VALUE', '$TEXT'),
                                array($cellclass, $value, $text),
                                $ttext);
                break;
            }       // gender code

            default:
            {       // ordinary numeric field
                $ttext          = $template['simpleCell']->outerHTML;
                $retval         =
                    str_replace(array('$CELLCLASS', '$VALUE'),
                                array($cellclass, $value),
                                $ttext);
                break;
            }       // ordinary numeric field
        }           // act on specific field names
    }               // integer value
    else 
    if ($fldname == 'create table')
    {
        $pos                = strpos($value, '(');
        if ($pos !== false)
            $value          = substr_replace($value,"(<br>",$pos,1);
        $pos                = strrpos($value, ')');
        if ($pos !== false)
            $value = substr_replace($value,")<br>",$pos,1);
        $value              = str_replace(",",",<br>",$value);
        $value              = str_replace($sqlTypes,
                                          $sqlTypesUpper,
                                          $value);
        $ttext              = $template['simpleCell']->outerHTML;
        $retval             =
                    str_replace(array('$CELLCLASS', '$VALUE'),
                                array($cellclass, $value),
                                $ttext);
    }
    else
    {               // normal text field
        $ttext              = $template['simpleCell']->outerHTML;
        $value              = htmlspecialchars($value);
        $retval             =
                    str_replace(array('$CELLCLASS', '$VALUE'),
                                array($cellclass, $value),
                                $ttext);
    }               // normal text field

    return $retval;
}       // function cellToHtml($template, $table, $row, $fldname, $value)

/************************************************************************
 *  Open Code                                                           *
 ************************************************************************/
$title                      = 'Issue SQL Command';
$emsg                       = '';
$sqlCommand                 = '';
$execute                    = false;
$lang                       = 'en';
if (count($_POST) > 0)
{               // interpret parameters passed by POST
    $parmsText  = "<p class='label'>\$_POST</p>\n" .
                  "<table class='summary'>\n" .
                  "<tr><th class='colhead'>key</th>" .
                      "<th class='colhead'>value</th></tr>\n";
    foreach($_POST as $key => $value)
    {           // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'sqlcommand':
            {
                $sqlCommand     = $value;
                break;
            }       // SqlCommand

            case 'confirm':
            {
                if (strtolower($value) == 'y')
                    $execute    = true;
                break;
            }       // Confirm permission to go ahead

            case 'submit':
                break;

            case 'debug':
            {
                if (strtolower($value) == 'y')
                    $debug          = true;
                break;
            }       // previously handled

            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

            default:
            {
                $warn   .= "Unexpected parameter $key='$value'. ";
                break;
            }       // other
        }
    }           // loop through all parameters
    if ($debug)
        $warn   .= $parmsText . "</table>\n";
}               // parameters passed by method=post
else
if (count($_GET) > 0)
{               // interpret parameters passed by GET
    $parmsText      = "<p class='label'>\$_GET</p>\n" .
                      "<table class='summary'>\n" .
                      "<tr><th class='colhead'>key</th>" .
                          "<th class='colhead'>value</th></tr>\n";
    foreach($_GET as $key => $value)
    {           // loop through all parameters
        $safevalue          = htmlspecialchars($value);
        $parmsText  .= "<tr><th class='detlabel'>$key</th>" .
                        "<td class='white left'>$safevalue</td></tr>\n"; 
        switch(strtolower($key))
        {
            case 'lang':
            {
                $lang               = FtTemplate::validateLang($value);
                break;
            }

            case 'debug':
            {
                if (strtolower($value) == 'y')
                    $debug          = true;
                break;
            }       // previously handled

        }
    }           // loop through all parameters
    if ($debug)
        $warn               .= $parmsText . "</table>\n";
}               // parameters passed by method=post

if ($debug)
    $warn       .= "<p>debugging activated</p>\h";
$template                   = new FtTemplate("SqlCommand$lang.html");
$formatter                  = $template->getFormatter();
$translate                  = $template->getTranslate();
$idetTranslate              = $translate['idetTitleText'];

$template->set('DEBUG',             $debug ? 'Y' : 'N');
$template->set('LANG',              $lang);

// parse patterns for SQL commands
$cmdPattern                 = '/^\s*(\w+)\s+(.*)$/';
$deletePattern              = '/^(\w*)\s*FROM\s+(\w+)\s+(.*)$/i';
$insertPattern              = '/^(IGNORE\s+|)INTO\s+(\w+)\s+(.*)$/i';
$updatePattern              = '/^(\w+)\s+(.*)\s+WHERE\s+(.*)$/i';

// results of parse
$command                    = null;
$table                      = null;
$join                       = null;
$operands                   = null;
$where                      = null;
$count                      = null;
$groupby                    = null;
$having                     = null;
$orderby                    = null;
$source                     = null;
$limit                      = 999999999;
$offset                     = null;
$query                      = false;

$tables                     = array();
$stmt                       = $connection->query("SHOW TABLES");
$tableRes                   = $stmt->fetchAll(PDO::FETCH_NUM);
foreach($tableRes as $row)
{
    $ttable                 = $row[0];
    array_push($tables, $ttable);
    $colStmt                = $connection->query("SHOW COLUMNS FROM `$ttable`");
    $colRes                 = $colStmt->fetchAll(PDO::FETCH_ASSOC);
    $fieldList              = array();
    foreach($colRes as $field)
        array_push($fieldList, $field['field']);
    $fields[$ttable]            = $fieldList;
}

if (strlen($msg) == 0 && $sqlCommand && strlen($sqlCommand) > 0)
{                       // no errors and work to do
    $getCount               = true;
    $matches                = array();
    $sqlCommand             = trim($sqlCommand);
    $explain                = false;
    $result                 = preg_match($cmdPattern, $sqlCommand, $matches);
    if ($result == 1)
    {                   // SQL command recognized
        $command            = strtoupper($matches[1]);
        $therest            = trim($matches[2]);
        if ($command == 'EXPLAIN')
        {
            $explain        = true;
            $sqlCommand     = trim($therest);
            $result         = preg_match($cmdPattern, $sqlCommand, $matches);
            $command        = strtoupper($matches[1]);
            $therest        = trim($matches[2]);
        }

        switch($command)
        {               // check syntax of command
            case 'DELETE':
            {
                // validate authorization
                if (!canUser('all'))
                    $msg    .=
                            'You are not authorized to use this feature. ';

                $result     = preg_match($deletePattern,
                                         $therest,
                                         $matches);
                if ($result == 1)
                {
                    $operands   = $matches[1];
                    $table      = $matches[2];
                    $rest       = $matches[3];
                    $info       = Record::getInformation($table);
                    if ($info)
                    {
                        $table  = $info['table'];
                        $sqlCommand = "DELETE $operands FROM $table $rest";
                    }
                    if ($debug)
                        $warn   .= "<p> " . __LINE__ .
            " operands='$operands', table='$table', rest='$rest'</p>\n";
                    $result     = preg_match('/^(.*)\s*WHERE\s+(.*)$/i',
                                             $rest,
                                             $matches);
                    if ($result == 1)
                    {
                        $join   = $matches[1];
                        $where  = $matches[2];
                        if ($debug)
                            $warn   .= "<p> " . __LINE__ .
                                    " join='$join', where='$where'</p>\n";
                    }
                    else
                        $msg    .= 'Unsupported syntax for DELETE command. ' . __LINE__;
                    $query      = false;
                }
                else
                    $msg    .= 'Unsupported syntax for DELETE command. ' . __LINE__;
                break;
            }               // DELETE command

            case 'INSERT':
            {
                // validate authorization
                if (!canUser('all'))
                    $msg    .=
                            'You are not authorized to use this feature. ';

                $result     = preg_match($insertPattern,
                                         $therest,
                                         $matches);
                if ($result == 1)
                {
                    $ignore     = $matches[1];
                    $table      = $matches[2];
                    $operands   = $matches[3];
                    $info       = Record::getInformation($table);
                    if ($info)
                    {
                        $table  = $info['table'];
                        $sqlCommand = "INSERT $ignore INTO $table $operands";
                    }
                    $query      = false;
                    $count      = 1;
                }
                else
                    $msg    .= 'Unsupported syntax for INSERT command. ';
                break;
            }               // INSERT command

            case 'SELECT':
            {
                $operands           = '';
                $parms              = explode(',', $therest);
                $therest            = '';
                $comma              = '';
                $accum              = '';
                $afterexpr          = false;
                foreach ($parms as $parm)
                {               // loop through comma separated list
                    if ($afterexpr)
                    {           // reassemble the remainder of the command
                        $therest    .= $comma . $parm;
                        continue;
                    }           // reassemble the remainder of the command

                    $accum                  = trim($accum);
                    if (strlen($accum) > 0)
                        $accum              = "$accum,$parm";
                    else
                        $accum              = $parm;
                    $numlbraks              = substr_count($accum, '(');
                    $numrbraks              = substr_count($accum, ')');
                    if ($numlbraks == $numrbraks)
                    {               // matching parentheses in expression
                        if (substr($accum, 0, 1) == '(')
                        {           // subquery
                            $lpos           = strrpos($accum, ')');
                            $after          = trim(substr($accum, $lpos + 1));
                            $accum          = substr($accum, 0, $lpos + 1);
                            $operands       .= $comma . $accum;
                            $accum          = $after;
                            $comma          = ',';
                        }           // subquery
                        else
                        {
                            $frompos    = strpos(strtoupper($accum),
                                                 'FROM ');
                            if ($frompos !== false)
                            {           // end of list of select expressions
                                $operands   .= $comma . 
                                               substr($accum, 0, $frompos);
                                $therest    = substr($accum, $frompos);
                                $afterexpr  = true;
                            }           // end of list of select expressions
                            else
                            {           // not end of list of select expressions
                                $operands   .= $comma . $accum;
                            }           // not end of list of select expressions
                            $comma          = ',';
                            $accum          = '';
                        }
                    }               // matching parentheses in expression
                }                   // loop through comma separated list

                $result         = preg_match('/^FROM\s+(\w+)(.*)$/i',
                                             $therest,
                                             $matches);
                if ($result == 1)
                {
                    $table          = $matches[1];
                    $join           = $matches[2];  
                    $info           = Record::getInformation($table);
                    if ($info)
                    {
                        $table      = $info['table'];
                        $sqlCommand = "SELECT $operands FROM $table $join";
                    }
                    if ($debug)
                    {
                        $warn       .= "<p>\$operands='$operands'</p>\n";
                        $warn       .= "<p>\$table='$table'</p>\n";
                        $warn       .= "<p>\$join='$join'</p>\n";
                    }
                    $where          = '';
                    $groupby        = '';
                    $having         = '';
                    $orderby        = '';
                    $limit          = '';
                    $offset         = '';

                    // parse the portion of the SELECT after the
                    // first table name in the FROM clause
                    if (strlen($join) > 0)
                    {
                        $delimPatt  = 
    '/\s+(WHERE|GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT|PROCEDURE)\s+/i';
                        $matches    = preg_split($delimPatt,
                                             $join, 
                                             2, 
                                             PREG_SPLIT_DELIM_CAPTURE);
                        if (count($matches) == 3)
                        {
                            $join       = $matches[0];
                            switch (strtoupper(substr($matches[1],0,5)))
                            {
                                case 'WHERE':
                                {
                                    $where  = $matches[2];
                                    break;
                                }

                                case 'GROUP':
                                {
                                    $groupby    = $matches[2];
                                    break;
                                }

                                case 'HAVIN':
                                {
                                    $having     = $matches[2];
                                    break;
                                }

                                case 'ORDER':
                                {
                                    $orderby    = $matches[2];
                                    break;
                                }

                                case 'LIMIT':
                                {
                                    $limit      = $matches[2];
                                    break;
                                }

                                case 'PROCE':
                                {
                                    $procedure  = $matches[2];
                                    break;
                                }

                            }       // switch
                        }       // found delimiter of FROM clause
                    }       // have text after the table name

                    // parse the JOIN clause
                    if ($debug)
                        $warn   .= "<div class='warning'>parseJoin('$join')</div>\n";
                    $msg    .= parseJoin($join);

                    // parse the portion of the SELECT after the
                    // WHERE clause
                    if (strlen($where) > 0)
                    {
                        $delimPatt  = 
    '/\s+(GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT|PROCEDURE)\s+/i';
                        $matches    = preg_split($delimPatt,
                                             $where, 
                                             2, 
                                             PREG_SPLIT_DELIM_CAPTURE);
                        if (count($matches) == 3)
                        {
                            $where      = $matches[0];
                            switch (strtoupper(substr($matches[1],0,5)))
                            {
                                case 'GROUP':
                                {
                                    $groupby    = $matches[2];
                                    break;
                                }

                                case 'HAVIN':
                                {
                                    $having     = $matches[2];
                                    break;
                                }

                                case 'ORDER':
                                {
                                    $orderby    = $matches[2];
                                    break;
                                }

                                case 'LIMIT':
                                {
                                    $limit      = $matches[2];
                                    break;
                                }

                                case 'PROCE':
                                {
                                    $procedure  = $matches[2];
                                    break;
                                }

                            }       // switch
                        }       // found delimiter of WHERE clause
                    }       // have text in the WHERE clause

                    // parse the portion of the SELECT after the
                    // GROUP BY clause
                    if (strlen($groupby) > 0)
                    {
                        $delimPatt  = 
            '/\s+(HAVING|ORDER\s+BY|LIMIT|PROCEDURE)\s+/i';
                        $matches    = preg_split($delimPatt,
                                             $groupby,
                                             2, 
                                             PREG_SPLIT_DELIM_CAPTURE);
                        if (count($matches) == 3)
                        {
                            $groupby    = $matches[0];
                            switch (strtoupper(substr($matches[1],0,5)))
                            {
                                case 'HAVIN':
                                {
                                    $having     = $matches[2];
                                    break;
                                }

                                case 'ORDER':
                                {
                                    $orderby    = $matches[2];
                                    break;
                                }

                                case 'LIMIT':
                                {
                                    $limit      = $matches[2];
                                    break;
                                }

                                case 'PROCE':
                                {
                                    $procedure  = $matches[2];
                                    break;
                                }

                            }       // switch
                        }       // found delimiter of GROUP BY clause
                    }       // have text in the GROUP BY clause

                    // parse the portion of the SELECT after the
                    // HAVING clause
                    if (strlen($having) > 0)
                    {
                        $delimPatt  = 
                            '/\s+(ORDER\s+BY|LIMIT|PROCEDURE)\s+/i';
                        $matches    = preg_split($delimPatt,
                                             $having,
                                             2, 
                                             PREG_SPLIT_DELIM_CAPTURE);
                        if (count($matches) == 3)
                        {
                            $having     = $matches[0];
                            switch (strtoupper(substr($matches[1],0,5)))
                            {
                                case 'ORDER':
                                {
                                    $orderby    = $matches[2];
                                    break;
                                }

                                case 'LIMIT':
                                {
                                    $limit      = $matches[2];
                                    break;
                                }

                                case 'PROCE':
                                {
                                    $procedure  = $matches[2];
                                    break;
                                }

                            }       // switch
                        }       // found delimiter of HAVING clause
                    }       // have text in the HAVING clause

                    // parse the portion of the SELECT after the
                    // ORDERBY clause
                    if (strlen($orderby) > 0)
                    {
                        $delimPatt  = '/\s+(LIMIT|PROCEDURE)\s+/i';
                        $matches    = preg_split($delimPatt,
                                             $orderby, 
                                             2, 
                                             PREG_SPLIT_DELIM_CAPTURE);
                        if (count($matches) == 3)
                        {
                            $orderby    = $matches[0];
                            switch (strtoupper(substr($matches[1],0,5)))
                            {
                                case 'LIMIT':
                                {
                                    $limit      = $matches[2];
                                    break;
                                }

                                case 'PROCE':
                                {
                                    $procedure  = $matches[2];
                                    break;
                                }

                            }       // switch
                        }       // found delimiter of ORDERBY clause

                        // validate ORDER BY clause
                        $validateOrder  =
                            '/\s(FROM|WHERE|GROUP|HAVING|ORDER)\s/i';
                        $result = preg_match($validateOrder,
                                             $orderby,
                                             $matches);
                        if ($result == 1)
                        {
                            $resword    = strtoupper($matches[1]);
                            $msg    .= "Unexpected keyword $resword after ORDER BY. ";
                        }

                    }       // have text in the ORDERBY clause

                    // parse the portion of the SELECT after the
                    // LIMIT clause
                    if (strlen($limit) > 0)
                    {
                        $delimPatt  = '/\s+(PROCEDURE)\s+/i';
                        $matches    = preg_split($delimPatt,
                                             $limit,
                                             2, 
                                             PREG_SPLIT_DELIM_CAPTURE);
                        if (count($matches) == 3)
                        {
                            $limit  = $matches[0];
                            switch (strtoupper(substr($matches[1],0,5)))
                            {
                                case 'PROCE':
                                {
                                    $procedure  = $matches[2];
                                    break;
                                }

                            }       // switch
                        }       // found delimiter of LIMIT clause

                        // validate LIMIT clause
                        $validateLimit  =
            '/^\s*((\d+)|(\d+),\s*(\d+)|(\d+)\s+OFFSET\s+(\d+))\s*$/i';
                        $result = preg_match($validateLimit,
                                             $limit,
                                             $matches);
                        if ($result == 1)
                        {       // parse of LIMIT clause succeeded
                            if (count($matches) == 3 &&
                                strlen($matches[2] > 0))
                            {
                                $limit  = $matches[2];
                            }
                            else
                            if (count($matches) == 5 &&
                                strlen($matches[3]) > 0 &&
                                strlen($matches[4] > 0))
                            {
                                $limit  = $matches[4];
                                $offset = $matches[3];
                            }
                            else
                            if (count($matches) == 7 &&
                                strlen($matches[5]) > 0 &&
                                strlen($matches[6] > 0))
                            {
                                $limit  = $matches[5];
                                $offset = $matches[6];
                            }
                            else
                            {
                                $msg    .=
                                    "Unexpected value LIMIT \$matches=" .
                                    print_r($matches, true);
                                $limit  = '';
                            }
                        }       // parse of LIMIT clause succeeded
                        else
                        {       // parse failed
                            $msg    .= "Unexpected value 'LIMIT $limit'. ";
                            $limit  = '';
                        }       // parse failed

                    }       // have text in the LIMIT clause
                }           // parse succeeded
                else
                {           // no FROM clause
                    $table      = 'DUAL';
                }           // no FROM clause
                $query          = true;
                break;
            }               // SELECT command

            case 'UPDATE':
            {
                // validate authorization
                if (!canUser('all'))
                {
                    $msg    .=
                        'You are not authorized to use this feature. ';
                }

                $result     = preg_match($updatePattern,
                                         $therest,
                                         $matches);
                if ($result == 1)
                {
                    $table      = $matches[1];
                    $operands   = $matches[2];
                    $where      = $matches[3];
                    $info       = Record::getInformation($table);
                    if ($info)
                    {
                        $table  = $info['table'];
                        $sqlCommand = "UPDATE $table $operands WHERE $where";
                    }
                    $query      = false;
                }
                else
                    $msg    .= 'Unsupported syntax for UPDATE command. ';
                break;
            }               // UPDATE command

            case 'SHOW':
            {
                // validate authorization
                if (!canUser('all'))
                    $msg    .=
                            'You are not authorized to use this feature. ';

                $result     = preg_match('/^CREATE\s+TABLE\s+(\w+)(.*)/i',
                                         $therest,
                                         $matches);
                if ($result == 1)
                {
                    $table      = $matches[1];
                    $rest       = $matches[2];
                    $info       = Record::getInformation($table);
                    if ($info)
                    {
                        $table  = $info['table'];
                        $sqlCommand = "SHOW CREATE TABLE $table$rest";
                    }
                    $query          = true;
                    $execute        = true;
                    $getCount       = false;
                }
                else
                {
                    $warn       .= "<p>Unsupported options 'SHOW $therest'</p>\n";
                    $query          = false;
                    $execute        = false;
                    $getCount       = false;
                }
                $sresult        = null;
                break;
            }               // SHOW

            case 'ALTER':
            {
                // validate authorization
                if (!canUser('all'))
                    $msg    .=
                            'You are not authorized to use this feature. ';

                $result     = preg_match('/^TABLE\s+(\w+)(.*)/i',
                                         $therest,
                                         $matches);

                if ($result == 1)
                {
                    $table      = $matches[1];
                    $rest       = $matches[2];
                    $info       = Record::getInformation($table);
                    if ($info)
                    {
                        $table  = $info['table'];
                        $sqlCommand = "ALTER TABLE $table$rest";
                    }
                }
                $query          = true;
                $execute        = true;
                $getCount       = false;
                $sresult        = null;
                break;
            }               // ALTER

            case 'SOURCE':
            {
                // validate authorization
                if (!canUser('all'))
                    $msg    .=
                        'You are not authorized to use this feature. ';
                if (!file_exists($therest))
                    $msg    .= "File $therest does not exist.  ";
                else
                    $source = fopen($therest, 'r');
                $execute    = false;
                $query      = false;
                $getCount   = false;
                break;
            }               // SOURCE

            default:
            {
                $msg        .= "'$command' is not a valid command verb. ";
                $execute    = false;
                break;
            }

        }               // check syntax of command
    }                   // SQL command recognized
    else
    {
        if (preg_match("/^([^\s]+)/", $sqlCommand, $matches) == 1)
            $command    = strtoupper($matches[1]);
        else
        if (strlen($sqlCommand) < 10)
            $command    = $sqlCommand;
        else
            $command    = substr($sqlCommand, 0, 10) . "..."; 
        $msg    .= "'$command' is not a valid command verb. ";
        $execute    = false;
    }

    if (is_string($table))
        $template->set('TABLE',         $table);
    else
    {
        $template['table']->update(null);
        $template['badtable']->update(null);
    }

    // before actually issuing a new command let the user know
    // how many lines of the database it will effect
    if (strlen($msg) == 0 && $getCount)
    {                   // command valid and includes WHERE clause
        if (strtoupper($operands) == 'COUNT(*)')
        {               // returning only count
            print "<p>$sqlCommand</p>\n";
            $stmt           = $connection->query($sqlCommand);
            if ($stmt === false)
            {           // error on request
                $msg        .= __LINE__ . " query='" . htmlentities($sqlCommand) .
                    "', " .  print_r($connection->errorInfo(),true);
            }           // error on request
            else
            {
                $sresult    = $stmt->fetchAll(PDO::FETCH_NUM);
                $count      = count($sresult);
            }
        }               // returning only count
        else
        {               // returning actual rows
            if ($command == 'INSERT' || $command == 'SHOW')
            {
                $count      = 1;
            }
            else
            {
                $sresult    = null;
                $countQuery = "SELECT COUNT(*) FROM $table $join";
                if (is_string($where) && strlen($where) > 0)
                    $countQuery .= " WHERE $where";
                $stmt       = $connection->query($countQuery);
                if ($stmt)
                {
                    $row    = $stmt->fetch(PDO::FETCH_NUM);
                    if ($row)
                        $count  = $row[0];
                    if (strlen($limit) > 0 && $count > $limit)
                        $count  = $limit;
                }
                else
                {
                    $msg    .= __LINE__ . " query='" . htmlentities($countQuery) .
                        "', " .  print_r($connection->errorInfo(),true);
                }       // error on request
            }
        }               // returning actual rows
    }                   // command valid and includes WHERE clause

    // if authorized actually issue the command
    if ($execute)
    {                   // OK to execute command
        $phpfield           = null; 
        if ($query)
        {               // SELECT command, issue query
            if (is_null($sresult))
            {           // query not issued yet
                if (preg_match("/(.*)(phpsoundex\(\w+\))(.*)/i", 
                               $sqlCommand, 
                               $parts) == 1)
                {
                    $before     = $parts[1];
                    $phpfield   = substr($parts[2],11);
                    $phpfield   = substr($phpfield, 0, strlen($phpfield) - 1);
                    $after      = $parts[3];
                    $sqlCommand = $before . $phpfield . $after;
                }
                if ($explain)
                    $sqlCommand = 'EXPLAIN ' . $sqlCommand;
                $stmt           = $connection->query($sqlCommand);

                if ($stmt)
                {
                    $sresult    = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                else
                {
                    $msg        .= __LINE__ . " query='" . htmlentities($sqlCommand) .
                                    "', " .  
                                    print_r($connection->errorInfo(),true);
                }       // error on request
            }           // query not issued yet
        }               // SELECT command, issue query     
        else
        {               // DELETE, INSERT, or UPDATE, issue exec
            $stmt       = $connection->query($sqlCommand);
            if ($stmt === false)
            {
                $msg    .= "exec='" . htmlentities($sqlCommand) . "', " .
                            print_r($connection->errorInfo(),true);
            }           // error on request
            else
            {           // log that the update has been performed
                $sresult    = $stmt->rowCount();
                if ($sresult > 0)
                    logSqlUpdate($sqlCommand,
                                 '',
                                 0,
                                 '',
                                 '');
            }           // log that the update has been performed
        }               // DELETE, INSERT, or UPDATE, issue exec
    }                   // execute command
    else
    if ($source)
    {                   // execute a script
        $execute            = true;
        $query              = true;
        $phpfield           = null; 
        $sresult            = array();
        $line               = 1;
        while(!feof($source))
        {
            $sqlCommand     = preg_replace('/--.*/', '', fgets($source));
            while(!feof($source) && 
                  preg_match('/;\s*$/', $sqlCommand) == 0)
                $sqlCommand .= preg_replace('/--.*/', '', fgets($source));
            $sqlCommand     = preg_replace('/;\s*$/', '', $sqlCommand);
            $result         = preg_match($cmdPattern . 'm',
                                         $sqlCommand, 
                                         $matches);
            if ($result)
            {
                $command        = strtoupper($matches[1]);
                $sresult[]      = array('line' => $line, 'cmd' => $sqlCommand, 'error' => '');
                if ($command == 'UPDATE' ||
                    $command == 'INSERT' || 
                    $command == 'DELETE' ||
                    $command == 'CREATE' ||
                    $command == 'DROP')
                {
                    $stmt       = $connection->query($sqlCommand);
                    if ($stmt === false)
                    {
                        $sresult[]      = array('line' => '', 'cmd' => 
                            print_r($connection->errorInfo(),true), 'error' => 'Y');
                    }           // error on request
                    else
                    {           // log that the update has been performed
                        $count      = $stmt->rowCount();
                        $sresult[]  = array('line' => '', 'cmd' => "Updated $count records", 'error' => '');
                        if ($count > 0)
                            logSqlUpdate($sqlCommand,
                                         '',
                                         0,
                                         '',
                                         '');
                    }
                }
                else
                    $sresult[]      = array('line' => '', 'cmd' => "$command Unsupported", 'error' => 'Y');
            }
            else
            if ($sqlCommand == '' || substr($sqlCommand, 0, 2) == '--')
            {
                $sresult[]      = array('line' => $line, 'cmd' => $sqlCommand, 'error' => '');
            }
            else
            {
                $sresult[]      = array('line' => $line, 'cmd' => $sqlCommand, 'error' => 'Y');
                if (strlen($sqlCommand) > 200)
                    $sqlAbbr    = substr($sqlCommand, 0, 150) . ' ... ' .
                                    substr($sqlCommand, -50);
                else
                    $sqlAbbr    = $sqlCommand; 
                $warn           .= "<p>SqlCommand.php: " . __LINE__ . "parse failed for pattern '$cmdPattern' string '$sqlAbbr'</p>\n";
            }
            $line++;
        }
    }                   // execute a script
}                       // no errors and work to do
else
{
    $template['table']->update(null);
    $template['badtable']->update(null);
}

// display the verb from the command
if (is_string($command))
    $template->set('COMMAND',       $command);
else
    $template['command']->update(null);

if (is_string($table))
{           // have parsed table name out of command
    $template->set('TABLE',         $table);
    if (strtoupper($table) == 'DUAL')
    {       // reserved word for no table
    }       // reserved word for no table
    else
    if (in_array($table, $tables))
    {       // table defined on server
        $template['badtable']->update(null);
    }       // table defined on server
    else
    {       // table not defined on server
        $template->set('TABLECOUNT',    count($tables));
        $tableParms = array();
        foreach($tables as $i => $tablename)
            $tableParms[]   = array('I'             => $i,
                                    'TABLENAME'     => $tablename);
        $template['tableName$I']->update($tableParms);
    }
}           // have parsed table name out of command

if (is_string($join) && strlen($join) > 0)
{               // have a join specification
    $template->set('JOIN',              $join);
    $emsg   .= validateFieldNames($join);
}               // have a join specification
else
    $template['join']->update(null);

if (is_string($operands) && strlen($operands) > 0)
{           // have a list of operands/expressions      
    $template->set('OPERANDS',          $operands);
    $emsg   .= validateFieldNames($operands);
}           // have a list of operands/expressions      
else
    $template['operands']->update(null);

if (is_string($where))
{           // have a WHERE expression  
    $template->set('WHERE',             $where);
    $emsg   .= validateFieldNames($where);
}           // have a WHERE expression  
else
    $template['where']->update(null);

if (is_string($groupby) && strlen($groupby) > 0)
{           // GROUP BY
    $template->set('GROUPBY',           $groupby);
    $emsg   .= validateFieldNames($groupby);
}           // have a groupby expression  
else
    $template['groupby']->update(null);

if (is_string($having) && strlen($having) > 0)
{           // HAVING
    $template->set('HAVING',            $having);
    $emsg   .= validateFieldNames($having);
}           // have a having expression  
else
    $template['having']->update(null);

if (is_string($orderby) && strlen($orderby) > 0)
{           // ORDER BY
    $template->set('ORDERBY',           $orderby);
    $emsg   .= validateFieldNames($orderby);
}           // have a orderby expression  
else
    $template['orderby']->update(null);

if (is_string($limit) && strlen($limit) > 0)
{           // LIMIT
    $template->set('LIMIT',             $limit);
}           // LIMIT
else
    $template['limit']->update(null);

if (is_string($offset) && strlen($offset) > 0)
{   // OFFSET
    $template->set('OFFSET',             $offset);
}   // OFFSET
else
    $template['offset']->update(null);

if (strlen($emsg) > 0)
    $template->set('EMSG',              $emsg);
else
    $template['emsg']->update(null);

$fieldParms             = array();
if (count($badTables) > 0)
{
	foreach($badTables as $tableName => $val)
	{                   // loop through all tables with bad field names
	    if (array_key_exists($tableName, $fields))
	    {               // defined table
	        $fieldsList     = $fields[$tableName];
	        $fieldParms[]   = array('TABLENAME'     => $tableName,
	                                'FIELDCOUNT'    => count($fieldsList),
	                                'FIELDSNAME'    => implode(', ', $fieldsList));
	    }               // defined table
	    $template['fieldnames']->update($fieldParms);
    }                   // loop through all table
}
else
    $template['fieldnames']->update(null);

if (!is_null($count))
{      
    $template->set('COUNT',             $formatter->format($count));
    if ($query) 
        $template['updatestats']->update(null);
    else
        $template['displaystats']->update(null);
}
else
{
    $template['stats']->update(null);
}

$template->set('SQLCOMMAND', htmlspecialchars($sqlCommand, ENT_QUOTES));
if ($execute && strlen($msg) == 0)
{                           // command performed
    if ($query)
    {                       // display response to query
        $firstRow               = true;
        $cellClass              = 'odd';
        if (is_array($sresult))
        {                   // have response to display
            $count              = count($sresult);
            if ($count == 0)
                $count          = 'No';
            $template->set('COUNT',     $formatter->format($count));
            $phpcolumn          = null;
            $data               = '';
            foreach($sresult as $row)
            {               // loop through rows
                if ($firstRow)
                {           // first row in table
                    $headers    = array();
                    foreach($row as $name => $value)
                    {       // loop through fields in row
                        $headers[]  = array('NAME'  => $name);
                    }       // loop through fields in row
                    $template['respCol']->update($headers);
                }           // first row in table
                $firstRow       = false;

                $data           .= "\n         <tr>\n";

                // display data
                foreach($row as $fldname => $value)
                {           // loop through fields in row
                    $data       .= 
                cellToHtml($template, $table, $row, $fldname, $value, $cellClass);
                }           // loop through fields in row

                if ($phpfield)
                {
                    $ttext      = $template['simpleCell']->outerHTML;
                    $data       .=
                    str_replace(array('$CELLCLASS', '$VALUE'),
                                array($cellclass, soundex($row[$phpfield])),
                                $ttext);
                }
                $data           .= "\n        </tr>\n";
                if ($cellClass == 'odd')
                    $cellClass  = 'even';
                else
                    $cellClass  = 'odd';
            }       // loop through rows
            if (strlen($data) > 0)
                $template['data$ROW']->update($data);
            else
                $template['respTable']->update(null);
            $template['sresult']->update(null);
        }           // have response to query as array
        else
        {           // response is not an array
            $template->set('SRESULT',       $sresult);
        }           // response is not an array
    }               // select command performed
    else
    if (is_int($sresult) || is_string($result))
    {
        $template->set('SRESULT',       $formatter->format($sresult));
        $template['respCount']->update(null);
        $template['respTable']->update(null);
    }
    else
        $template['response']->update(null);
}                           // command performed
else
{
    $template['response']->update(null);
}

$template->display();
