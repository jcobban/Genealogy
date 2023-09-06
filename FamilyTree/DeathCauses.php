<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathCauses.php                                                     *
 *                                                                      *
 *  Generate a customized popup division to explain the contents of the *
 *  cause of death for an individual.  This file is included by any     *
 *  page, for example Person.php that wishes to support popping         *
 *  up an explanation of a cause of death, which is communicated to this*
 *  script through variables $deathcause for the                        *
 *  spouses.                                                            *
 *                                                                      *
 *  History:                                                            *
 *      2013/06/07      created                                         *
 *      2013/11/21      add "excitement"                                *
 *      2013/12/06      add "pernicious"                                *
 *      2014/03/28      add "gravel"                                    *
 *      2014/05/24      support more than 1 spouse                      *
 *                      separate descriptions of causes into separate   *
 *                      paragraphs for clarity                          *
 *      2015/02/13      add "encephalitis", "lethargy"                  *
 *      2015/06/11      add "scrofula"                                  *
 *      2015/07/16      add "atherosclerosis" and "atheroma"            *
 *      2015/11/13      add "paris green"                               *
 *      2015/11/16      add "Pott's disease"                            *
 *      2016/01/26      add "ascites"                                   *
 *      2016/06/09      add "carcinoma" and "pyelitis"                  *
 *      2016/06/24      add "quinsy"                                    *
 *      2020/12/06      issue error message if referenced incorrectly   *
 *      2021/05/22      add "Parenchymatous"                            *
 *                                                                      *
 *  Copyright &copy; 2020 James A. Cobban                               *
 ************************************************************************/

global $deathcause;
if (!isset($deathcause))
{
    print "<p class='error'>Script DeathCauses.php must be embedded in another script which defines the array \$deathcause.</p>\n";
    exit;
}

for ($i = 1; $i <= count($deathcause); $i++)
{       // loop through all individuals with cause of death
    $cause              = $deathcause[$i - 1];
?>
  <div class='balloon' id='DeathCauseHelp<?php print $i; ?>'>
    <p class='label'>Cause: <?php print $cause; ?></p>
<?php
    $causewords         = explode(' ', $cause);
    $prevword           = '';
    $needPara           = true;

    for ($iw = 0; $iw < count($causewords); $iw++)
    {           // loop through all words in cause of death
        // fold to lower case for search
        $word           = strtolower($causewords[$iw]);
        if (strlen($word) < 5)
            continue;       // ignore prepositions and articles

        // remove initial or trailing punctuation character
        while(ctype_punct(substr($word, -1)))
            $word       = substr($word, 0, -1);
        if (ctype_punct(substr($word, 0, 1)))
            $word       = substr($word, 1);

        if ($needPara)
            print "<p>\n";
        $needPara   = true;

        switch($word)
        {       // interpret word
            case 'abioptic':
?>
            "abioptic":
            <span class="normal">Not diagnosed with a sample of tissue from a patient.
            </span>
<?php
                break;

            case 'acute':
?>
            "acute":
            <span class="normal">Short term duration.
            </span>
<?php
                break;

            case "addison's":
            case "addisons":
?>
            "Addison's Disease":
            <span class="normal">Chronic adrenal insufficiency leading to
            very low blood pressure and coma.
            </span>
<?php
                break;

            case 'albumaemia':
            case 'albuminaria':
            case 'albuminarea':
            case 'albuminemia':
?>
            "albuminemia":
            <span class="normal">Deficiency of albumin in the blood.
            </span>
<?php
                break;

            case 'anemia':
            case 'anaemia':
?>
            "anaemia":
            <span class="normal">a deficiency in the number or quality
            of red blood cells in your body.
            </span>
<?php
                break;

            case 'apoplexy':
?>
            "apoplexy":
            <span class="normal">Obsolete terminology for a stroke.
            </span>
<?php
                break;

            case 'appelectic':
?>
            "epileptic":
            <span class="normal">Subject to seizures that are not related to an infection.
            </span>
<?php
                break;

            case 'arterio-sclerosis':
            case 'arteriosclerosis':
?>
 
            </span>           "arterio-sclerosis":
            <span class="normal">This is properly <i>atherosclerosis</i>, congestion of the arteries
            due to the accumulation of fatty deposits on the lining.
            </span>
<?php
                break;

            case 'ascites':
?>
            "ascites":
            <span class="normal">The accumulation of fluid in the peritoneal cavity, 
                    causing abdominal swelling.
            </span>
<?php
                break;

            case 'asthenia':
?>
            "asthenia":
            <span class="normal">Lack or loss of strength and energy.
            </span>
<?php
                break;

            case 'ataxia':
?>
            "Ataxia":
            <span class="normal">lack of voluntary control of muscle movements.
            </span>
<?php
                break;

            case 'atelestosis':
?>
            "atelectasis":
            <span class="normal">collapse or closure of the lung
            </span>
<?php
                break;

            case 'atherosclerosis':
?>
            "Atherosclerosis":
            <span class="normal">Congestion of the arteries
            due to the accumulation of fatty deposits on the lining.
            </span>
<?php
                break;

            case 'atheroma':
?>
            "atheroma":
            <span class="normal">a fatty deposit on the inner lining of an artery
            resulting from <i>atherosclerosis</i>.
            </span>
<?php
                break;


            case 'auto-intoxication':
?>
            "auto-intoxication":
            <span class="normal">Self-poisoning resulting from the absorption
            of waste products of metabolism. 
            </span>   
<?php
                break;

            case 'bilateral':
?>
            "bilateral":
            <span class="normal">Occurring on both sides of the body.
            </span>
<?php
                break;

            case 'brain':
                if ($prevword == 'congestion')
                {
?>
            "Congestion of the Brain": 
            <span class="normal">Swelling of the brain due to trauma or
            infection.  The swelling cuts off arterial blood flow
            to parts of the brain.  Frequently misapplied in the 19th 
            century, for example to ischemic stroke.
            </span>
<?php
                }
                break;


            case 'bulbar':
?>
            "bulbar palsy":
            <span class="normal">Impairment of function of cranial nerves.
            </span>
<?php
                break;

            case 'cachexia':
?>
            "cachexia":
            <span class="normal">weakness and wasting of the body due to severe
                chronic illness.
            </span>
<?php
                break;


            case 'carbuncle':
?>
            "carbuncle":
            <span class="normal">Abscess larger than a boil.
            </span>
<?php
                break;

            case 'carcinoma':
?>
            "carcinoma":
            <span class="normal">a type of cancer that develops from epithelial
                cells that line the inner or outer surfaces of the body.
            </span>
<?php
                break;

            case 'carcinomatosis':
?>
            "carcinomatosis":
            <span class="normal">is a rare condition that means cancer in 
                one part of your body has spread, creating several tumors 
                in another part of your body.
            </span>
<?php
                break;


            case 'catarrh':
            case 'catarrhal':
?>
            "catarrh":
            <span class="normal">Disorder of inflammation of the mucous membranes
            typically producing phlegm.  Frequently misapplied in the 
            19th century.
            </span>
<?php
                break;

            case 'cerebral':
?>
            "cerebral": 
            <span class="normal">Occurring in the brain.
            </span>
<?php
                break;

            case 'cholecystitis':
?>
            "cholecystitis":
            <span class="normal">Inflammation of the gallbladder.
            </span>
<?php
                break;

            case 'chalongitis':
            case 'cholongitis':
?>
            "cholongitis":
            <span class="normal">Inflammation of the bile duct system.
            </span>
<?php
                break;

            case 'cholelithiasis':
?>
            "cholelithiasis":
            <span class="normal">the presence of one or more calculi
                (gallstones) in the gallbladder.
            </span>
<?php
                break;

            case 'cholanguitis':
            case 'cholangitis':
?>
            "cholangitis":
            <span class="normal">inflammation of the bile duct.
            </span>
<?php
                break;

            case 'chorea':
?>
            "chorea":
            <span class="normal">a movement disorder that causes sudden, unintended, 
                and uncontrollable jerky movements of the arms, legs, 
                and facial muscles. Chorea is seen in many diseases and 
                conditions and is caused by an overactivity of the 
                chemical dopamine in the areas of the brain that control 
                movement.
            </span>
<?php
                break;

            case 'compensation':
?>
        "cardiac compensation":
            <span class="normal">the maintenance of an adequate blood flow 
        without distressing symptoms, accomplished by such cardiac and 
        circulatory adjustments as tachycardia, cardiac hypertrophy, and 
        increase of blood volume by sodium and water retention.
            </span>
<?php
                break;

            case 'consolidation':
?>
        "pulmonary consolidation":
            <span class="normal">is a region of lung tissue that has
            filled with liquid instead of air so it is not compressible.
            </span>
<?php
                break;

            case 'cyanosis':
?>
            "cyanosis":
            <span class="normal">the appearance of a blue or purple colouration of the
            skin or mucous membranes due to low oxygen saturation.
            </span>
<?php
                break;

            case 'infantum':
?>
            "Cholera infantum, or, as this form of disease is generally termed, 
            "summer complaint," comprises all the various diseases of the 
            digestive organs and brain with which children are attacked 
            during the summer, and most frequently during dentition, during 
            their second summer.' Adolphe Lippe (1812-1888)
<?php
                break;

            case 'confinement':
?>
            "confinement":
            <span class="normal">The period from the onset of labour to the birth
            of a child.  As a cause of death generally 
            <a href='http://en.wikipedia.org/wiki/Eclampsia'>eclampsia</a>,
            an acute complication of pregnancy characterized by seizures
            and coma.
            </span>
<?php
                break;

            case 'congestion':
                if ($prevword == 'brain')
                {
?>
            "Brain Congestion":
            <span class="normal"> swelling of the brain due to trauma or
            infection.  The swelling cuts off arterial blood flow
            to parts of the brain.  Frequently misapplied in the 19th 
            century, for example to ischemic stroke.
            </span>
<?php
                }
                else
                if ($prevword == 'hypostatic')
                {
?>
            "Hypostatic Congestion":
            <span class="normal">Congestion caused by poor cirulation
            and settling of venous blood in the lower part of an organ. 
            </span>
<?php
                }
                else
                {
?>
            "Congestion":
            <span class="normal">is an abnormal accumulation of a body fluid.
            </span>
<?php
                }
                break;

            case 'consumption':
?>
            "consumption":
            <span class="normal">Tuberculosis of the lungs.  Throughout history
            the single most deadly disease, particularly of young adults.
            </span>
<?php
                break;

            case 'cystitis':
?>
            "cystitis":
            <span class="normal">Infection of the bladder.
            </span>
<?php
                break;

            case 'decompensated':
            case 'decompensation':
?>
            "Cardiac decompensation":
            <span class="normal">may refer to the failure of the heart to
            maintain adequate blood circulation.
            </span>
<?php
                break;

            case 'dementia':
                if ($prevword == 'senile')
                {
?>
            "Senile dementia":
            <span class="normal">is mental deterioration in old age, 
            characterized by loss of memory and control of bodily
            functions.
            </span>
<?php
                }
                else
                {
?>
            "Dementia":
            <span class="normal"> is any psychotic disorder.
            </span>
<?php
                }
                break;

            case 'diabetes':
?>
            "diabetes mellitus":
            <span class="normal">is a metabolic disease that causes
            high blood sugar. Either the body doesn't make enough insulin
            or cannot use the insulin it does make.
            </span>
<?php
                break;

            case 'dilatation':
            case 'dilatative':
            case 'dilated':
?>
            "dilatation of the heart":
            <span class="normal">Compensatory enlargement of the 
            cavities of the heart, with thinning of its walls.
            </span>
<?php
                break;

            case 'diphtheria':
?>
            Diphtheria (Greek διφθέρα (diphthera) "pair of leather scrolls") 
            is an upper respiratory tract illness caused by 
            <i>Corynebacterium diphtheriae</i>. It is characterized by sore
            throat, low fever, and an adherent membrane on the tonsils,
            pharynx, and/or nasal cavity
<?php
                break;

            case 'disease':
                if ($prevword == 'brights' ||
                    $prevword == 'bright' ||
                    $prevword == "bright's")
                {
?>
            "Bright's Disease":
            <span class="normal">Historical classification of kidney diseases
            that would be described in modern medicine as acute or chronic
            nephritis, inflammation of the kidneys.
            </span>
<?php
                }
                else
                if ($prevword == 'potts' ||
                    $prevword == 'pott' ||
                    $prevword == "pott's")
                {
?>
            "Pott's Disease":
            <span class="normal">A form of tuberculosis that occurs outside
            the lungs where the disease attacks the vertebrae.
            Also called tuberculous spondylitis.  It has been found in
            ancient Egyptian mummies.
            </span>
<?php
                }
                else
                    $needPara   = false;
                break;

            case 'dropsy':
?>
            "dropsy":
            <span class="normal"> is an abnormal accumulation of fluid beneath
            the skin or
            in one or more cavities of the body.  This is an obsolete term
            replaced by <a href='http://en.wikipedia.org/wiki/Edema'>edema(US) or oedema(UK)</a>.
            </span>
<?php
                break;

            case 'dysentery':
?>
            "Dysentery":
            <span class="normal"> is an inflammatory disorder of the intestine,
            especially of the colon, that results in severe diarrhea
            containing blood and mucus in the feces with fever and abdominal 
            pain, caused by any kind of infection.
            </span>
<?php
                break;

            case 'dyspepsia':
?>
            "Dyspepsia":
            <span class="normal">also known as indigestion, is a condition of impaired
            digestion.
            </span>
<?php
                break;

            case 'dystacia':
            case 'dystocia':
            case 'dystosia':
?>
            "Dystocia":
            <span class="normal"> is an abnormal or difficult childbirth or labour.
            </span> 
<?php
                break;

            case 'ecelampsia':
            case 'eclampsia':
?>
            "Eclampsia":
            <span class="normal"> is an acute and life-threatening complication of
            pregnancy, characterized by the appearance of seizures, usually
            in a patient who has developed pre-eclampsia. (Pre-eclampsia and
            eclampsia are collectively called Hypertensive disorder of
            pregnancy and toxemia of pregnancy.)
<?php
                break;

            case 'edema':
            case 'oedema':
?>
            "Edema":
            <span class="normal"> (usually spelled Oedema in Britain) is an abnormal 
            accumulation of fluid beneath the skin or
            in one or more cavities of the body.  Formerly called "dropsy".
            </span>
<?php
                break;

            case 'embolism':
?>
            "Embolism":
            <span class="normal">is an obstruction in a blood vessel due to a blood
            clot or other foreign matter that gets stuck while traveling
            through the bloodstream.
            </span>
<?php
                break;

            case 'embolus':
?>
            "Embolus":
            <span class="normal">is a mass of clotted blood or other material brought 
            by the blood from one vessel and forced into a smaller one,
            obstructing the circulation.
            </span>
<?php
                break;

            case 'emphyacemia':
            case 'emphysema':
?>
            "Emphysema ":
            <span class="normal">is a long-term lung disease. In people with emphysema, 
            the tissues necessary to support the shape and function of the
            lungs are destroyed.
            </span>
<?php
                break;

            case 'encephalitis':
?>
            "Encephalitis":
            <span class="normal">is an inflammation of the brain.
            </span>
<?php
                break;

            case 'enteric':
?>
            "Enteric Fever":
            <span class="normal">is also known as Typhoid Fever.
            </span>
<?php
                break;

            case 'enteritis':
?>
            "Enteritis":
            <span class="normal">is an inflammation of the small intestine.
            </span>
<?php
                break;

            case 'erisypelas':
            case 'erysipelas':
            case 'erysypelas':
?>
            "Erysipelas":
            <span class="normal">is an acute streptococcus bacterial infection of the 
            upper dermis and superficial lymphatics.
            </span>
<?php
                break;

            case 'erythema':
?>
            "Erythema":
            <span class="normal">is a type of skin rash caused by injured or
            inflamed blood capillaries.
            </span>
<?php
                break;

            case 'excitement':
?>
            "Excitement" :
            <span class="normal">as a medical term refers to the now discredited
            theories of the 18th century Scottish medical educator Dr. 
            <a href='http://en.wikipedia.org/wiki/William_Cullen'>William
            Cullen</a> that supposed that some medical
            conditions arose out of an excess or lack of excitation.
            </span>
<?php
                break;

            case 'exophthalmic':
?>
            "exophthalmic":
            <span class="normal">characterized by protruding eyeballs.  Symptom of
            hyperthyroidism including Graves' disease.
            </span>
<?php
                break;

            case 'fibroid':
?>
            "fibroid":
            <span class="normal">characterized by fibrous scars.
            </span>
<?php
                break;

            case 'fistula':
?>
            "fistula":
            <span class="normal">an abnormal connection or passageway between two 
            epithelium-lined organs or vessels.
            </span>
<?php
                break;

            case 'flu':
            case 'flue':
            case 'influenza':
            case 'influenzal':
            case 'influenzic':
?>
            "Influenza":
            <span class="normal">is an acute respiratory disease caused by a virus.
            The word entered the English language from Spanish as a result of
            military secrecy during World War I.  Soldiers on both sides of
            the conflict were affected in epidemic numbers but this was not
            revealed to the public.  Only in Spain, which was a non-combatant,
            was the epidemic reported, so it became known by its Spanish
            name.  Previously the symptoms would have been reported as
            "cattarh" or "la Grippe".
            </span>
<?php
                break;

            case 'intercostal':
?>
            "Intercostal":
            <span class="normal">is a something which is located between the
            ribs.  Intercostal neuralgia is chest wall pain.
            </span>
<?php
                break;

            case 'intussusception':
?>
            "Intussusception":
            <span class="normal">is a condition in which part of the intestines 
            folds into another section ot intestine.  This can often result
            in an obstruction.
            </span>
<?php
                break;

            case 'gangrene':
            case 'grangrene':
?>
            "Gangrene":
            <span class="normal">is a serious and potentially life-threatening condition
            that arises when a considerable mass of body tissue dies
            (necrosis).
            </span>
<?php
                if ($prevword == 'gas')
                {
?>
            "Gas gangrene" (also known as "Clostridial myonecrosis", and
            "Myonecrosis") is a bacterial infection that produces gas in
            tissues in gangrene.
<?php
                }
                break;

            case 'gastritis':
?>
            "Gastritis":
            <span class="normal">is an inflammation of the lining of the stomach.
            </span>
<?php
                break;

            case 'glossolaryngeal':
?>
            "Glossolaryngeal":
            <span class="normal">having to do with the tongue and larynx.
            </span>
<?php
                break;

            case 'goitre':
            case 'goiter':
?>
            "Goitre":
            <span class="normal">is swelling of the thyroid gland, which can lead to a
            swelling of the neck or larynx.
            </span>
<?php
                break;

            case "grave's":
            case "graves'":
            case "graves":
?>
            "Graves' disease":
            <span class="normal">is an autoimmune disease. It most commonly
            affects the thyroid, frequently causing it to enlarge to twice
            its size or more (goitre).
            </span>
<?php
                break;

            case "gravel":
?>
            "Gravel":
            <span class="normal">is a deposit of small calculous concretions, usually of 
            uric acid, calcium oxalate, or phosphates, in the 
            kidneys and urinary bladder.  Also known as uropsammus.
            </span>
<?php
                break;

            case 'green':
                if ($prevword == 'paris')
                {
?>
            "Paris green", or copper(II) acetoarsenite, is a highly toxic
            crystalline power developed as a pesticide but also formerly used
            as a pigment.
            </span>
<?php
                }
                else
                    $needPara   = false;
                break;

            case 'grippe':
?>
            "la Grippe":
            <span class="normal">this is the term most commonly used prior to
            1918 for the disease now known as influenza or the common cold.
            </span>  
<?php
                break;

            case 'haematemesis':
            case 'hematemesis':
?>
            "Haematemesis":
            <span class="normal">is vomiting up of blood.
            </span>
<?php
                break;

            case 'haemophilia':
            case 'hemophilia':
?>
            "Haemophilia":
            <span class="normal">is a group of rare hereditary disorders in which
            the blood does not clot normally and therefore injuries do not
            heal properly.
            </span>
<?php
                break;

            case 'haemorrage':
            case 'haemorrhage':
            case 'hemorrage':
            case 'hemmorage':
            case 'hemorrhage':
            case 'hemmorrhage':
?>
            "Haemorrhage":
            <span class="normal">is profuse bleeding from ruptured blood vessels.
            </span>
<?php
                break;

            case 'hemiplegia':
?>
            "Hemiplegia":
            <span class="normal">is a symptom of stroke in which one side of the body
            becomes weak.
            </span>
<?php
                break;

            case 'hepatic':
?>
            "Hepatic":
            <span class="normal">having to do with the liver.
            </span>
<?php
                break;

            case 'hepatitis':
?>
            "Hepatitis":
            <span class="normal">inflammation of the liver.
            </span>
<?php
                break;

            case "hodgkins'":
            case "hodgkin's":
            case "hodgkin":
            case "hodgkins":
            case "hodgkinsons":
?>
            "Hodgkin's Lymphoma":
            <span class="normal">is a type of cancer originating from white
            blood cells called lymphocytes which spreads from one lymph node
            to another.
            </span>

<?php
                break;

            case 'hydrocephalus':
            case 'hydrocephaly':
?>
            "Hydrocephalus":
            <span class="normal">an abnormal accumulation of cerebro-spinal fluid
            in the cavities of the brain.
            </span>
<?php
                break;

            case 'hyperemia':
            case 'hyperaemia':
?>
            "Hyperaemia":
            <span class="normal">engorgement; an excess of blood in an organ.
            </span>
<?php
                break;

            case 'hypernephroma':
?>
            "Hypernephroma":
            <span class="normal">Cancer that begins in the lining of
            the tiny tubes in the kidney that return filtered substances 
            that the body needs back to the blood and remove extra fluid 
            and waste as urine.
            </span>
<?php
                break;

            case 'hypertension':
?>
            "Hypertension":
            <span class="normal">Is a condition in which 
            the blood vessels have persistently raised pressure.
            Commonly called "high blood pressure".
            
            </span>
<?php
                break;

            case 'hypertrophy':
            case 'hypertropy':
            case 'hyportrophy':
?>
            "Hypertrophy":
            <span class="normal">excessive enlargement.
            </span>
<?php
                break;

            case 'ilraemic':
            case 'uraemic':
            case 'uremic':
?>
                "Uraemic":
            <span class="normal">having to do with kidney failure.
            </span>
<?php
                break;

            case 'inanition':
?>
            "Inanition":
            <span class="normal">exhaustion cause by lack of food and water.
            </span>
<?php
                break;

            case 'infarct':
            case 'infarction':
?>
            "Infarction":
            <span class="normal">tissue death due to a local lack of oxygen as a 
            result of an obstruction of the tissue's blood supply.
            </span>
<?php
                break;

            case 'ischemia':
            case 'ischemic':
?>
            ":
            <span class="normal">ischemia" is an insufficient supply of blood to an organ, 
            usually due to a blocked artery.
            </span>
<?php
                break;

            case 'jaundice':
?>
            "Jaundice":
            <span class="normal">a yellowish pigmentation of the skin and mucous
            membranes cause by increased levels of bilirubin in the blood.
            Usually associated with liver disease.
            </span>
<?php
                break;

            case 'leg':
                if ($prevword == 'milk')
                {
?>
            "Milk Leg":
            <span class="normal">Post-partum blood clots in the legs.
            </span>
<?php
                }
                break;

            case 'lethargica':
                if ($prevword == 'encephalitis')
                {
?>
            "Encephalitis Lethargica":
            <span class="normal">Between 1915 and 1926 an epidemic
            of atypical encephalitis characterized by drowsiness and high
            rates of death swept the world.  
            It was first described in 1917 by the
            neurologist Constantin von Economo and the pathologist
            Jean-Rene Cruchet.
            </span> 
<?php
                }
                else
                {
?>
            "Lethargica":
            <span class="normal">characterized by lower level of consciousness.
            </span>
<?php
                }
                break;

            case 'lethargia':
            case 'lethargy':
?>
            "Lethargy":
            <span class="normal">a lowered level of consciousness, with drowsiness,
            listlessness, and apathy.
            </span>
<?php
                break;

            case 'leukaemia':
            case 'leukemia':
?>
            "Leukemia":
            <span class="normal">a cancer of the blood or bone marrow characterized
            by an abnormal increase of immature white blood cells.
            </span>
<?php
                break;

            case 'lues':
?>
            "Lues":
            <span class="normal">an obsolete term for syphilis.
            </span>
<?php
                break;

            case 'lymphadenoma':
            case 'lymphadenopathy':
?>
            "Lymphadenoma":
            <span class="normal">swelling of lymph nodes.`
            </span>
<?php
                break;

            case 'malignancy':
?>
            "malignancy":
            <span class="normal">the presence of cancerous cells that have
            the ability to spread to other sites in the body (metastasize) 
            or to invade nearby (locally) and destroy tissues.
            </span>
<?php
                break;

            case 'marasmus':
?>
            "marasmus":
            <span class="normal">severe malnutrition characterized by a lack of energy.
            </span>
<?php
                break;

            case 'matoiditis':
?>
            "mastoiditis":
            <span class="normal">a serious bacterial infection that affects the
            mastoid bone behind the ear.
            </span>
<?php
                break;

            case 'mediastinal':
?>
            "mediastinal":
            <span class="normal">located in the area of the chest that 
            separates the lungs.
            </span>
<?php
                break;

            case 'meningitis':
?>
            "meningitis":
            <span class="normal">inflammation of the protective membranes
            covering the brain and spinal cord.
            </span>
<?php
                break;

            case 'mesenteric':
            case 'mesentery':
?>
            "mesentery":
            <span class="normal">The mesentery is a fold of membrane that attaches
            the intestine to the abdominal wall and holds it in place.
            </span>
<?php
                break;

            case 'miliary':
?>
            "miliary":
            <span class="normal">a disease accompanied by a rash with lesions 
            resembling millet seed.
            </span>
<?php
                break;

            case 'mitral':
?>
            "mitral valve":
            <span class="normal">the valve in the heart that lies between the
            left atrium and the left ventricle.  The name comes from the
            similarity in appearance to a bishop's mitre.
            </span>
<?php
                break;

            case 'metritis':
?>
            "metritis":
            <span class="normal">inflammation of the wall of
            the uterus.  More commonly
            this is now called Pelvic Inflammatory Disease.
            </span>
<?php
                break;

            case 'morbus':
                if ($prevword == 'cholera')
                {
?>
            "cholera morbus":
            <span class="normal">used in the 19th and early 20th centuries to
                describe both non-epidemic cholera and gastrointestinal 
                diseases that mimicked cholera. In particular it was used for
                gastro-intestinal diseases suffered by young children
                during the transition to solid food. 
            </span>
<?php
                }
                break;

            case 'myelitis':
?>
            "Myelitis":
            <span class="normal">inflammation of the insulating sheeth around
                the spinal cord.
            </span>
<?php
                break;

            case 'myocardial':
?>
            "Myocardial":
            <span class="normal">having to do with the heart muscle.
            </span>
<?php
                break;

            case 'myocarditis':
?>
            "Myocarditis":
            <span class="normal">inflammation of the heart muscle due to an infection.
            </span>
<?php
                break;

            case 'nephritis':
?>
            "Nephritis":
            <span class="normal">inflammation of the kidneys.
            </span>
<?php
                break;

            case 'neuralgia':
?>
            "Neuralgia":
            <span class="normal">nerve pain that is not a result of excitation of
            healthy pain receptors.
            </span>
<?php
                break;

            case 'otitis':
?>
            "Otitis":
            <span class="normal">inflammation of the ear.
            </span>
<?php
                break;

            case 'paraplegia':
?>
            "Paraplegia":
            <span class="normal">impairment in motor or sensory function of the
            lower extremities, typically as a result of damage to the
            spinal cord.
            </span>
<?php
                break;

            case 'parenchymatous':
?>
            "Parenchymatous":
            <span class="normal">of, relating to, made up of, or affecting
            the essential and distinctive tissue of an organ as opposed
            to supporting tissue.
            </span>
<?php
                break;

            case 'paresis':
?>
            "Paresis":
            <span class="normal">a condition typified by a weakness of voluntary
            movement.
            </span>
<?php
                break;

            case 'parotid':
?>
            "Parotid glands":
            <span class="normal">salivary glands that sit just in front
            of the ears on each side of the face.
            </span>
<?php
                break;

            case 'parturition':
?>
            "Parturition":
            <span class="normal">the act of giving birth
            </span>
<?php
                break;

            case 'pellagra':
            case 'pellegra':
?>
            "Pellagra":
            <span class="normal">a vitamin deficiency disease most commonly caused by
            a chronic lack of niacin in the diet.
            </span>
<?php
                break;

            case 'peritonitis':
?>
            "Peritonitis":
            <span class="normal">inflamation of the tissue that lines the inner wall
            of the abdomen and covers most of the abdominal organs.
            </span>
<?php
                break;

            case 'pernicious':
?>
            "Pernicious Anaemia":
            <span class="normal">a decrease in red blood cells that occurs 
            when the intestines cannot properly absorb vitamin B12.
            The "pernicious" aspect of the disease was its invariably
            fatal prognosis prior to the discovery of treatment.
            </span> 
<?php
                break;

            case 'praevia':
            case 'previa':
?>
            "Placenta previa":
            <span class="normal">occurs when a baby's placenta partially
            or totally covers the mother's cervix — the outlet 
            for the uterus.
            </span>
<?php
                break;

            case 'phthisis':
?>
            "Phthisis":
            <span class="normal">a disease characterized by the wasting away or atrophy
            of the body or a part of the body.  Frequently pulmonary 
            tuberculosis.
            </span>
<?php
                break;

            case 'pleurisy':
            case 'pleuritis':
?>
            "Pleurisy":
            <span class="normal">painful inflammation of the lining of the cavity
            surrounding the lungs.
            </span>
<?php
                break;

            case 'pneumonia':
                if ($prevword == 'hypostatic')
                {
?>
            "Hypostatic pneumonia":
            <span class="normal"> usually results from the collection of 
            fluid in the back of the lungs and occurs especially 
            in those confined to a bed for extended periods.
            </span>
<?php
                }
                else
                {
?>
            "Pneumonia":
            <span class="normal">is an inflammatory infection of the lungs, and in
            particular the little air sacs called alveoli.  It is very
            common as a complication of other conditions that result in
            reduced immune response.
            </span>
<?php
                }
                break;

            case 'pott':
            case "pott's":
?>
            "Pott's disease":
            <span class="normal">Tuberculosis
            presenting in any other organ
            than the lung, for example in the spine.
            Names for the surgeon <a href="https://en.wikipedia.org/wiki/Percivall_Pott">Percivall Pott</a>
            </span>
<?php
                break;

            case 'praecox':
                if ($prevword == 'dementia')
                {
?>
            "Dementia Praecox":
            <span class="normal">literally madness occuring at an unusally 
            young age, refers to
            a deteriorating psychotic condition that begins in the late
            teens or early adulthood.  Schizophrenia.
            </span> 
<?php
                }
                break;

            case 'puerperal':
            case 'purperal':
?>
            "Puerperal fever":
            <span class="normal">a bacterial infection contracted by women
            during childbirth or miscarriage.  Prior to the introduction of
            antibiotics it would develop into septicaemia which is
            frequently fatal.  The typical source of the infection was the
            unwashed hands of the doctor.
            </span>
<?php
                break;

            case 'purpura':
?>
            "Purpura hemorrhagica":
            <span class="normal">blood leaking from veins into the
            surrounding tissue producing spontaneous bruises and petechiae.
            </span>
<?php
                break;

            case 'pyaemia':
            case 'pyemia':
?>
            "Pyaemia":
            <span class="normal">a type of septicaemia that leads to widespread
            abscesses.
            </span>
<?php
                break;

            case 'pyelitis':
            case 'pyelites':
?>
            "Pyelitis":
            <span class="normal">inflammation of the renal pelvis.
            </span>
<?php
                break;

            case 'pyorrhea':
            case 'pyorrhoea':
?>
            "Pyorrhea":
            <span class="normal">purulent inflamation of the gums and tooth sockets.
            </span>
<?php
                break;

            case 'pyonephrosis':
?>
            "Pyonephrosis":
            <span class="normal">an infection of the renal collecting system
            resulting in the accumulation of pus.
            </span>
<?php
                break;

            case 'quinsy':
            case 'quinsey':
?>
            "Quinsy":
            <span class="normal">is a rare and potentially serious complication of
            tonsillitis.  An abscess, a collection of pus, forms between the
            tonsil and the wall of the throat.  This can happen when a
            bacterial infection spreads from an infected tonsil to the
            surrounding tissue.
            </span>
<?php
                break;

            case 'remittent':
?>
            "Remittent Fever":
            <span class="normal">a fever in which the symptoms temporarily abate
            at regular intervals, but do not wholly cease.
            Causes include typhoid and infectious mononucleosis.
            </span>
<?php
                break;

            case 'rheumatism':
?>
            "Rheumatism":
            <span class="normal">a disease marked by inflammation and pain 
            in the joints, muscles, or fibrous tissue.  In the 19th
            century this sometimes refers to rheumatic fever, an 
            inflammatory disease that can be triggered by a streptococcal 
            bacterial infection, such as strep throat or scarlet fever, 
            which can cause joint pain, but can also
            attack the heart or central nervous system.
            </span>
<?php
                break;
            case 'sarcoma':
?>
            "Sarcoma":
            <span class="normal">a cancer that arises from transformed cells of 
            mesenchymal origin.
            resulting in the accumulation of pus.
            </span>
<?php
                break;

            case 'scarlatina':
            case 'scarlet':
?>
            "Scarlet Fever":
            <span class="normal">An infectious disease which most commonly
            affects 4 to 8 year old children. Symptoms include sore throat,
            fever, and a red rash.
            </span>
<?php
                break;

            case 'sclerosis':
                if ($prevword == 'arterio' ||
                    $prevword == 'arterial')
                {       // atherosclerosis
?>
            "Arterio- or Arterial sclerosis":
            <span class="normal">
            This is properly <i>atherosclerosis</i>, congestion of the arteries
            due to the accumulation of fatty deposits on the lining.
            </span>
<?php
                }       // atherosclerosis
                else
                {       // without qualifier
?>
            "Sclerosis":
            <span class="normal">a hardening of tissue, usually caused by a
            replacement of the normal organ-specific tissue with
            connective tissue.
            </span>
<?php
                }       // without qualifier
                break;

            case 'scrofula':
?>
            "Scrofula":
            <span class="normal">inflammation of the cervical lymph nodes in the neck due
            to infection by mycobacteria, frequently a symptom of tuberculosis.
            </span>
<?php
                break;

            case 'senility':
?>
            "senility":
            <span class="normal">an obsolete term for a cognitive decline
            particularly characterized by memory loss at a time
            when the causes of such a decline were not understood.
            </span>
<?php
                break;

            case 'sepsis':
            case 'septicaemia':
            case 'septicemia':
?>
            "Septicaemia":
            <span class="normal">an infection in which large numbers of bacteria are
            present in the blood.  It is commonly referred to as
            blood poisoning.
            </span>
<?php
                break;

            case 'stenosis':
?>
            "Stenosis":
            <span class="normal">an abnormal narrowing of a blood vessel or other
            tubular organ or structure.
            </span>
<?php
                break;

            case 'syncope':
?>
            "Syncope":
            <span class="normal">a temporary loss of consciousness usually related
            to insufficient blood flow to the brain.
            Commonly called fainting.  Pronounced "sink-a-pee".
            </span>
<?php
                break;

            case 'synovitis':
?>
            "Synovitis":
            <span class="normal"> inflammation of the synovial membrane.
            This membrane lines joints that possess cavities, 
            known as <a href="https://en.wikipedia.org/wiki/Synovial_joint">
            synovial joints</a>.
            </span> 
<?php
                break;

            case 'tabes':
            case 'tabess':
?>
            "Tabes":
            <span class="normal">also known as "tabes dorsalis", the slowly 
            </span>
            progressive degeneration of the spinal cord that occurs 
            in the late (tertiary) phase of syphilis
<?php
                break;

            case 'tetanus':
?>
            "Tetanus":
            <span class="normal">a medical condition characterized by a prolonged
            contraction of skeletal muscle fibers.  This is usually as a
            result of contamination of a wound.
            </span>
<?php
                break;

            case 'thrombosis':
?>
            "Thrombosis":
            <span class="normal">the formation of a blood clot inside a blood
            vessel, obstructing the flow of blood through the circulatory
            system.
            </span>
<?php
                break;

            case 'thrush':
?>
            "Thrush":
            <span class="normal">an infection, typically of the mouth, caused by the 
            candida fungus, also known as yeast infection.
            </span>
<?php
                break;

            case 'toxaemia':
            case 'toxemia':
?>
            "Toxaemia":
            <span class="normal">An abnormal condition of pregnancy characterized
            by hypertension, edema, and protein in the urine as a result of
            the presence of bacterial toxins in the blood.
            </span>
<?php
                break;

            case 'trachoma':
?>
            "Trachoma":
            <span class="normal">a bacterial infection that affects your eyes. 
            It is caused by the bacterium Chlamydia trachomatis.
            </span> 
<?php
                break;

            case 'trismus':
?>
            "Trismus":
            <span class="normal">reduced opening of the jaws caused by spasm of the
            muscles of mastication. 
            </span>
<?php
                break;

            case 'typhoid':
?>
            "Typhoid Fever":
            <span class="normal">A common bacterial disease transmitted by
            the ingestion of food or water contaminated with the feces of
            an infected person.
            </span>
<?php
                break;

            case 'typhus':
?>
            "Typhus":
            <span class="normal">any of a number of diseases transmitted by rickettsia
            bacteria.  Epidemic typhus is transmitted by lice.
            </span>
<?php
                break;

            case 'tuberculosis':
?>
            "Tuberculosis":
            <span class="normal">A common bacterial disease that typically 
            attacks the lungs, but can affect other organs.  Symptoms
            include chronic cough, fever, and weight loss.  Tuberculosis
            has killed more people throughout human history than any
            other disease.
            </span>
<?php
                break;

            case 'uraemia':
            case 'uremia':
            case 'uraemic':
            case 'uremic':
?>
            "Uraemia":
            <span class="normal">the disease accompanying kidney failure.
            </span>
<?php
                break;

            case 'vulgaris':
                if ($prevword == 'lupus')
                {
?>
            "Lupus Vulgaris":
            <span class="normal">Painful cutaneous tuberculosis
            skin lesions with nodular appearance.
            </span>
            
<?php
                }
                break;

            default:
                $needPara   = false;
                break;
        }       // interpret word
        
        if ($needPara)
            print "</p>\n";
        $prevword           = $word;
    }           // loop through all words in cause of death

    // close incomplete last paragraph
    if (!$needPara)
        print "</p>\n";
?>
  </div>
<?php
}           // loop through individuals
?>
