<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  test class Date                                                     *
 *                                                                      *
 ************************************************************************/
require_once __NAMESPACE__ . '/Record.inc';
require_once __NAMESPACE__ . '/LegacyDate.inc';
require_once __NAMESPACE__ . '/common.inc';

$dateObj    = null;
$date       = '';

if (count($_POST) > 0)
{       // have a date to process
    $date   = $_POST['date'];

    $dateObj    = new LegacyDate(' ' . $date);
}       // have a date to process
htmlHeader('Test LegacyDate Class');
?>
  <body>
    <h1>Test LegacyDate Class</h1>
<?php
    showTrace();

    if ($dateObj)
    {
?>
    <p>
    Date    = <?php print $dateObj; ?>
    </p>
    <p>
    Internal = <?php print $dateObj->getDate(); ?>
    </p>
    <p>
    Sort    = <?php print $dateObj->getSortDate(); ?>
    </p>
<?php
    }
?>
    <form method='post' action='testDate.php'>
      <p>
    <label for='date'>Enter Date String:
      <input type='text' size='64' maxlength='255'
        name='date' id='date' value='<?php print $date; ?>'>
<?php
    if ($debug)
    {
?>
      <input type='hidden' id='debug' name='debug' value='Y'>
<?php
    }
?>
      </p>
      <p>
        <button type='submit' id='submit'>
        Submit
        </button>
      </p>
    </form>
<?php
require_once __NAMESPACE__ . '/Event.inc';

    $stmt   = $connection->query("SELECT ider, eventd FROM tblER WHERE LEFT(eventd,1)=':'");
    if ($stmt)
    {
        $results    = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($results as $row)
        {
            $ider       = $row['ider'];
            $event      = new Event(array('ider' => $ider));
            $eventd     = $row['eventd'];
            $dateObj    = new LegacyDate($eventd);
            $date       = $dateObj->toString();
            print "<p>before: eventd='$eventd', date='$date'</p>\n";
            $event->setDate($date);
            $eventd     = $event->get('eventd');
            $eventsd    = $event->get('eventsd');
            print "<p>after: eventd='$eventd', date='$date'</p>\n";
            $event->save();
            print "<p>cmd=" . $event->getLastSqlCmd() . "</p>\n";
        }
    }
    else
    print_r($connection->errorInfo());
?>
  </body>
</html>
