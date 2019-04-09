<?php
namespace Genealogy;
use \PDO;
use \Exception;
/************************************************************************
 *  DeathCauses.php														*
 *																		*
 *  Generate a customized popup division to explain the contents of the	*
 *  cause of death for an individual.  This file is included by any		*
 *  page, for example Person.php that wishes to support popping			*
 *  up an explanation of a cause of death, which is communicated to this*
 *  script through variables $deathcause for the 						*
 *  spouses.															*
 * 																		*
 *  History: 															*
 *		2013/06/07		created											*
 *		2013/11/21		add "excitement"								*
 *		2013/12/06		add "pernicious"								*
 *		2014/03/28		add "gravel"									*
 *		2014/05/24		support more than 1 spouse						*
 *						separate descriptions of causes into separate	*
 *						paragraphs for clarity							*
 *		2015/02/13		add "encephalitis", "lethargy"					*
 *		2015/06/11		add "scrofula"									*
 *		2015/07/16		add "atherosclerosis" and "atheroma"			*
 *		2015/11/13		add "paris green"								*
 *		2015/11/16		add "Pott's disease"							*
 *		2016/01/26		add "ascites"									*
 *		2016/06/09		add "carcinoma" and "pyelitis"					*
 *		2016/06/24		add "quinsy"									*
 *																		*
 *  Copyright &copy; 2016 James A. Cobban								*
 ************************************************************************/

global $deathcause;
for ($i = 1; $i <= count($deathcause); $i++)
{		// loop through all individuals with cause of death
    $cause		= $deathcause[$i - 1];
?>
  <div class='balloon' id='DeathCauseHelp<?php print $i; ?>'>
    <p class='label'>Cause: <?php print $cause; ?></p>
<?php
    $causewords		= explode(' ', $cause);
    $prevword		= '';
    $needPara		= true;

    for ($iw = 0; $iw < count($causewords); $iw++)
    {			// loop through all words in cause of death
		$word	= strtolower($causewords[$iw]);
		if (substr($word, strlen($word) - 1, 1) == ',')
		    $word	= substr($word, 0, strlen($word) - 1);
		if ($needPara)
		    print "<p>\n";
		$needPara	= true;

		switch($word)
		{		// interpret word
		    case 'abioptic':
		    {
?>
		    "abioptic": Not diagnosed with a sample of tissue from a patient.
<?php
				break;
		    }

		    case 'acute':
		    {
?>
		    "acute": Short term duration.
<?php
				break;
		    }

		    case "addison's":
		    case "addisons":
		    {
?>
		    "Addison's Disease": Chronic adrenal insufficiency leading to very low blood pressure and coma.
<?php
				break;
		    }

		    case 'albumaemia':
		    case 'albuminaria':
		    case 'albuminarea':
		    case 'albuminemia':
		    {
?>
		    "albuminemia": Deficiency of albumin in the blood.
<?php
				break;
		    }

		    case 'apoplexy':
		    {
?>
		    "apoplexy": Obsolete terminology for a stroke.
<?php
				break;
		    }

		    case 'appelectic':
		    {
?>
		    "epileptic": Subject to seizures that are not related to an infection.
<?php
				break;
		    }

		    case 'arterio-sclerosis':
		    case 'arteriosclerosis':
		    {
?>
		    "arterio-sclerosis":
		    This is properly <i>atherosclerosis</i>, congestion of the arteries
		    due to the accumulation of fatty deposits on the lining.
<?php
				break;
		    }

		    case 'ascites':
		    {
?>
		    "ascites": The accumulation of fluid in the peritoneal cavity, 
					causing abdominal swelling.
<?php
				break;
		    }

		    case 'asthenia':
		    {
?>
		    "asthenia": Lack or loss of strength and energy.
<?php
				break;
		    }

		    case 'ataxia':
		    {
?>
		    "Ataxia": lack of voluntary control of muscle movements.
<?php
				break;
		    }

		    case 'atelestosis':
		    {
?>
		    "atelectasis": collapse or closure of the lung
<?php
				break;
		    }

		    case 'atherosclerosis':
		    {
?>
		    "Atherosclerosis": Congestion of the arteries
		    due to the accumulation of fatty deposits on the lining.
<?php
				break;
		    }

		    case 'atheroma':
		    {
?>
		    "atheroma": a fatty deposit on the inner lining of an artery
		    resulting from <i>atherosclerosis</i>.
<?php
				break;
		    }


		    case 'auto-intoxication':
		    {
?>
		    "auto-intoxication": Self-poisoning resulting from the absorption
		    of waste products of metabolism.	
<?php
				break;
		    }

		    case 'bulbar':
		    {
?>
		    "bulbar palsy": Impairment of function of cranial nerves.
<?php
				break;
		    }

		    case 'cachexia':
		    {
?>
		    "cachexia": weakness and wasting of the body due to severe
				chronic illness.
<?php
				break;
		    }


		    case 'carbuncle':
		    {
?>
		    "carbuncle": Abscess larger than a boil.
<?php
				break;
		    }

		    case 'carcinoma':
		    {
?>
		    "carcinoma": a type of cancer that develops from epithelial cells
				that line the inner or outer surfaces of the body.
<?php
				break;
		    }


		    case 'catarrh':
		    case 'catarrhal':
		    {
?>
		    "catarrh": Disorder of inflammation of the mucous membranes
		    typically producing phlegm.  Frequently misapplied in the 
		    19th century.
<?php
				break;
		    }

		    case 'cholecystitis':
		    {
?>
		    "cholecystitis": Inflammation of the gallbladder.
<?php
				break;
		    }

		    case 'cholelithiasis':
		    {
?>
		    "cholelithiasis": the presence of one or more calculi (gallstones)
		    in the gallbladder.
<?php
				break;
		    }

		    case 'cholanguitis':
		    case 'cholangitis':
		    {
?>
		    "cholangitis": inflammation of the bile duct.
<?php
				break;
		    }

		    case 'consolidation':
		    {
?>
        "pulmonary consolidation": is a region of lung tissue that has filled
        with liquid instead of air so it is not compressible.
<?php
				break;
		    }

		    case 'cyanosis':
		    {
?>
		    "cyanosis": the appearance of a blue or purple colouration of the
		    skin or mucous membranes due to low oxygen saturation.
<?php
				break;
		    }

		    case 'infantum':
		    {
?>
		    "Cholera infantum, or, as this form of disease is generally termed, 
		    "summer complaint," comprises all the various diseases of the 
		    digestive organs and brain with which children are attacked 
		    during the summer, and most frequently during dentition, during 
		    their second summer.' Adolphe Lippe (1812-1888)
<?php
				break;
		    }

		    case 'confinement':
		    {
?>
		    "confinement": The period from the onset of labour to the birth
		    of a child.  As a cause of death generally 
		    <a href='http://en.wikipedia.org/wiki/Eclampsia'>eclampsia</a>, 
		    an acute complication of pregnancy characterized by seizures and 
		    coma.
<?php
				break;
		    }

		    case 'congestion':
		    {
				if ($prevword == 'brain')
				{
?>
		    "Brain Congestion" swelling of the brain due to trauma or infection.
		    The swelling cuts off arterial blood flow to parts of the brain.
		    Frequently misapplied in the 19th century, for example to ischemic
		    stroke.
<?php
				}
				else
				if ($prevword == 'hypostatic')
				{
?>
		    "Hypostatic Congestion": Congestion caused by poor cirulation and
		    settling of venous blood in the lower part of an organ. 
<?php
				}
				else
				{
?>
		    "Congestion" is an abnormal accumulation of a body fluid.
<?php
				}
				break;
		    }

		    case 'consumption':
		    {
?>
		    "consumption": Tuberculosis of the lungs.  Throughout history
		    the single most deadly disease, particularly of young adults.
<?php
				break;
		    }

		    case 'cystitis':
		    {
?>
		    "cystitis": Infection of the bladder.
<?php
				break;
		    }

		    case 'decompensated':
		    case 'decompensation':
		    {
?>
		    Cardiac decompensation may refer to the failure of the heart to
		    maintain adequate blood circulation.
<?php
				break;
		    }

		    case 'dementia':
		    {
				if ($prevword == 'senile')
				{
?>
		    Senile dementia is severe mental deterioration in old age, 
		    characterized by loss of memory and control of bodily functions.
<?php
				}
				else
				{
?>
		    Dementia is any psychotic disorder.
<?php
				}
				break;
		    }
		    case 'dilatation':
		    case 'dilatative':
		    case 'dilated':
		    {
?>
		    "dilatation of the heart": Compensatory enlargement of the 
		    cavities of the heart, with thinning of its walls.
<?php
				break;
		    }

		    case 'diphtheria':
		    {
?>
		    Diphtheria (Greek διφθέρα (diphthera) "pair of leather scrolls") 
		    is an upper respiratory tract illness caused by 
		    <i>Corynebacterium diphtheriae</i>. It is characterized by sore
		    throat, low fever, and an adherent membrane on the tonsils,
		    pharynx, and/or nasal cavity
<?php
				break;
		    }

		    case 'disease':
		    {
				if ($prevword == 'brights' ||
				    $prevword == 'bright' ||
				    $prevword == "bright's")
				{
?>
		    "Bright's Disease": Historical classification of kidney diseases
		    that would be described in modern medicine as acute or chronic
		    nephritis, inflammation of the kidneys.
<?php
				}
				else
				if ($prevword == 'potts' ||
				    $prevword == 'pott' ||
				    $prevword == "pott's")
				{
?>
		    "Pott's Disease": A form of tuberculosis that occurs outside
		    the lungs where the disease attacks the vertebrae.
		    Also called tuberculous spondylitis.  It has been found in
		    ancient Egyptian mummies.
<?php
				}
				else
				    $needPara	= false;
				break;
		    }

		    case 'dropsy':
		    {
?>
		    "dropsy" is an abnormal accumulation of fluid beneath the skin or
		    in one or more cavities of the body.  This is an obsolete term
		    replaced by <a href='http://en.wikipedia.org/wiki/Edema'>edema</a>.
<?php
				break;
		    }

		    case 'dysentery':
		    {
?>
		    "Dysentery" is an inflammatory disorder of the intestine,
		    especially of the colon, that results in severe diarrhea
		    containing blood and mucus in the feces with fever and abdominal 
		    pain, caused by any kind of infection.
<?php
				break;
		    }

		    case 'dyspepsia':
		    {
?>
		    "Dyspepsia", also known as indigestion, is a condition of impaired
		    digestion.
<?php
				break;
		    }

		    case 'dystacia':
		    case 'dystocia':
		    case 'dystosia':
		    {
?>
		    "Dystocia" is an abnormal or difficult childbirth or labour. 
<?php
				break;
		    }

		    case 'ecelampsia':
		    case 'eclampsia':
		    {
?>
		    "Eclampsia" is an acute and life-threatening complication of
		    pregnancy, characterized by the appearance of seizures, usually
		    in a patient who has developed pre-eclampsia. (Pre-eclampsia and
		    eclampsia are collectively called Hypertensive disorder of
		    pregnancy and toxemia of pregnancy.)
<?php
				break;
		    }

		    case 'edema':
		    case 'oedema':
		    {
?>
		    "Edema" is an abnormal accumulation of fluid beneath the skin or
		    in one or more cavities of the body. 
<?php
				break;
		    }

		    case 'embolism':
		    {
?>
		    "Embolism" is an obstruction in a blood vessel due to a blood
		    clot or other foreign matter that gets stuck while traveling
		    through the bloodstream.
<?php
				break;
		    }

		    case 'embolus':
		    {
?>
		    "Embolus" is a mass of clotted blood or other material brought 
		    by the blood from one vessel and forced into a smaller one,
		    obstructing the circulation.
<?php
				break;
		    }

		    case 'emphyacemia':
		    case 'emphysema':
		    {
?>
		    Emphysema is a long-term lung disease. In people with emphysema, 
		    the tissues necessary to support the shape and function of the
		    lungs are destroyed.
<?php
				break;
		    }

		    case 'encephalitis':
		    {
?>
		    "Encephalitis" is an inflammation of the brain.
<?php
				break;
		    }

		    case 'enteric':
		    {
?>
		    "Enteric Fever" is also known as Typhoid Fever.
<?php
				break;
		    }

		    case 'enteritis':
		    {
?>
		    "Enteritis" is an inflammation of the small intestine.
<?php
				break;
		    }

		    case 'erisypelas':
		    case 'erysipelas':
		    case 'erysypelas':
		    {
?>
		    "Erysipelas" is an acute streptococcus bacterial infection of the 
		    upper dermis and superficial lymphatics.
<?php
				break;
		    }

		    case 'excitement':
		    {
?>
		    "Excitement" as a medical term refers to the now discredited
		    theories of the 18th century Scottish medical educator Dr. 
		    <a href='http://en.wikipedia.org/wiki/William_Cullen'>William
		    Cullen</a> that supposed that some medical
		    conditions arose out of an excess or lack of excitation.
<?php
				break;
		    }		// excitement

		    case 'exophthalmic':
		    {
?>
		    "exophthalmic": characterized by protruding eyeballs.  Symptom of
		    hyperthyroidism including Graves' disease.
<?php
				break;
		    }

		    case 'fibroid':
		    {
?>
		    "fibroid": characterized by fibrous scars.
<?php
				break;
		    }

		    case 'fistula':
		    {
?>
		    "fistula": an abnormal connection or passageway between two 
		    epithelium-lined organs or vessels.
<?php
				break;
		    }

		    case 'flu':
		    case 'flue':
		    case 'influenza':
		    case 'influenzal':
		    case 'influenzic':
		    {
?>
		    "Influenza" is an acute respiratory disease caused by a virus.
		    The word entered the English language from Spanish as a result of
		    military secrecy during World War I.  Soldiers on both sides of
		    the conflict were affected in epidemic numbers but this was not
		    revealed to the public.  Only in Spain, which was a non-combatant,
		    was the epidemic reported, so it became known by its Spanish
		    name.  Previously the symptoms would have been reported as
		    "cattarh" or "la Grippe".
<?php
				break;
		    }

		    case 'intussusception':
		    {
?>
		    "Intussusception" is a condition in which part of the intestines 
		    folds into another section ot intestine.  This can often result
		    in an obstruction.
<?php
				break;
		    }

		    case 'gangrene':
		    case 'grangrene':
		    {
?>
		    "Gangrene" is a serious and potentially life-threatening condition
		    that arises when a considerable mass of body tissue dies
		    (necrosis).
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
		    }

		    case 'gastritis':
		    {
?>
		    "Gastritis" is an inflammation of the lining of the stomach.
<?php
				break;
		    }

		    case 'glossolaryngeal':
		    {
?>
		    "Glossolaryngeal": having to do with the tongue and larynx.
<?php
				break;
		    }

		    case 'goitre':
		    case 'goiter':
		    {
?>
		    "Goitre" is swelling of the thyroid gland, which can lead to a
		    swelling of the neck or larynx.
<?php
				break;
		    }

		    case "grave's":
		    case "graves'":
		    case "graves":
		    {
?>
		    "Graves' disease" is an autoimmune disease. It most commonly
		    affects the thyroid, frequently causing it to enlarge to twice
		    its size or more (goitre).
<?php
				break;
		    }

		    case "gravel":
		    {
?>
		    "Gravel" is a deposit of small calculous concretions, usually of 
		    uric acid, calcium oxalate, or phosphates, in the 
		    kidneys and urinary bladder.  Also known as uropsammus.
<?php
				break;
		    }

		    case 'green':
		    {
				if ($prevword == 'paris')
				{
?>
		    "Paris green", or copper(II) acetoarsenite, is a highly toxic
		    crystalline power developed as a pesticide but also formerly used
		    as a pigment.
<?php
				}
				else
				    $needPara	= false;
				break;
		    }

		    case 'grippe':
		    {
?>
		    "la Grippe": this is the term most commonly used prior to
		    1918 for the disease now known as influenza or the common cold.  
<?php
				break;
		    }

		    case 'haematemesis':
		    case 'hematemesis':
		    {
?>
		    "Haematemesis" is vomiting up of blood.
<?php
				break;
		    }

		    case 'haemophilia':
		    case 'hemophilia':
		    {
?>
		    "Haemophilia" is a group of rare hereditary disorders in which
		    the blood does not clot normally and therefore injuries do not
		    heal properly.
<?php
				break;
		    }

		    case 'haemorrage':
		    case 'haemorrhage':
		    case 'hemorrage':
		    case 'hemmorage':
		    case 'hemorrhage':
		    case 'hemmorrhage':
		    {
?>
		    "Haemorrhage" is profuse bleeding from ruptured blood vessels.
<?php
				break;
		    }

		    case 'hemiplegia':
		    {
?>
		    "Hemiplegia" is a symptom of stroke in which one side of the body
		    becomes weak.
<?php
				break;
		    }

		    case 'hepatic':
		    {
?>
		    "Hepatic": having to do with the liver.
<?php
				break;
		    }

		    case 'hepatitis':
		    {
?>
		    "Hepatitis": inflammation of the liver.
<?php
				break;
		    }

		    case "hodgkins'":
		    case "hodgkin's":
		    case "hodgkin":
		    case "hodgkins":
		    case "hodgkinsons":
		    {
?>
		    "Hodgkin's Lymphoma" is a type of cancer originating from white
		    blood cells called lymphocytes which spreads from one lymph node
		    to another.
<?php
				break;
		    }

		    case 'hydrocephalus':
		    case 'hydrocephaly':
		    {
?>
		    "Hydrocephalus": an abnormal accumulation of cerebro-spinal fluid
		    in the cavities of the brain.
<?php
				break;
		    }

		    case 'hyperemia':
		    case 'hyperaemia':
		    {
?>
		    "Hyperaemia": engorgement; an excess of blood in an organ.
<?php
				break;
		    }

		    case 'hypertrophy':
		    case 'hypertropy':
		    case 'hyportrophy':
		    {
?>
		    "Hypertrophy": excessive enlargement.
<?php
				break;
		    }

		    case 'ilraemic':
		    case 'uraemic':
		    case 'uremic':
		    {
?>
				"Uraemic": having to do with kidney failure.
<?php
				break;
		    }

		    case 'inanition':
		    {
?>
		    "Inanition": exhaustion cause by lack of food and water.
<?php
				break;
		    }

		    case 'infarct':
		    case 'infarction':
		    {
?>
		    "Infarction": tissue death due to a local lack of oxygen as a 
		    result of an obstruction of the tissue's blood supply.
<?php
				break;
		    }

		    case 'ischemia':
		    case 'ischemic':
		    {
?>
		    "Ischemia" is an insufficient supply of blood to an organ, 
		    usually due to a blocked artery.
<?php
				break;
		    }

		    case 'jaundice':
		    {
?>
		    "Jaundice": a yellowish pigmentation of the skin and mucous
		    membranes cause by increased levels of bilirubin in the blood.
		    Usually associated with liver disease.
<?php
				break;
		    }

		    case 'leg':
		    {
				if ($prevword == 'milk')
				{
?>
		    "Milk Leg": Post-partum blood clots in the legs.
<?php
				}
				break;
		    }

		    case 'lethargica':
		    {
				if ($prevword == 'encephalitis')
				{
?>
		    "Encephalitis Lethargica": Between 1915 and 1926 an epidemic
		    of atypical encephalitis characterized by drowsiness and high
		    rates of death swept the world.  
		    It was first described in 1917 by the
		    neurologist Constantin von Economo and the pathologist
		    Jean-Rene Cruchet. 
<?php
				}
				else
				{
?>
		    "Lethargica": characterized by lower level of consciousness.
<?php
				}
				break;
		    }

		    case 'lethargia':
		    case 'lethargy':
		    {
?>
		    "Lethargy": a lowered level of consciousness, with drowsiness,
		    listlessness, and apathy.
<?php
				break;
		    }

		    case 'leukaemia':
		    case 'leukemia':
		    {
?>
		    "Leukemia": a cancer of the blood or bone marrow characterized
		    by an abnormal increase of immature white blood cells.
<?php
				break;
		    }

		    case 'lymphadenoma':
		    case 'lymphadenopathy':
		    {
?>
		    "Lymphadenoma": swelling of lymph nodes.`
<?php
				break;
		    }

		    case 'marasmus':
		    {
?>
		    "marasmus": severe malnutrition characterized by a lack of energy.
<?php
				break;
		    }

		    case 'meningitis':
		    {
?>
		    "meningitis": inflammation of the protective membranes covering the
		    brain and spinal cord.
<?php
				break;
		    }

		    case 'mitral':
		    {
?>
		    "mitral valve": the valve in the heart that lies between the
		    left atrium and the left ventricle.  The name comes from the
		    similarity in appearance to a mitre.
<?php
				break;
		    }

		    case 'metritis':
		    {
?>
		    "metritis": inflammation of the wall of the uterus.  More commonly
		    this is now called Pelvic Inflammatory Disease.
<?php
				break;
		    }

		    case 'morbus':
		    {
				if ($prevword == 'cholera')
				{
?>
		    "cholera morbus": used in the 19th and early 20th centuries to
				describe both non-epidemic cholera and gastrointestinal 
				diseases that mimicked cholera. In particular it was used for
				gastro-intestinal diseases suffered by young children
				during the transition to solid food.
<?php
				}
				break;
		    }

		    case 'myocardial':
		    {
?>
		    "Myocardial": having to do with the heart muscle.
<?php
				break;
		    }

		    case 'myocarditis':
		    {
?>
		    "Myocarditis": inflammation of the heart muscle due to an infection.
<?php
				break;
		    }

		    case 'nephritis':
		    {
?>
		    "Nephritis": inflammation of the kidneys.
<?php
				break;
		    }

		    case 'neuralgia':
		    {
?>
		    "Neuralgia": nerve pain that is not a result of excitation of
		    healthy pain receptors.
<?php
				break;
		    }

		    case 'otitis':
		    {
?>
		    "Otitis": inflammation of the ear.
<?php
				break;
		    }

		    case 'paraplegia':
		    {
?>
		    "Paraplegia": impairment in motor or sensory function of the
		    lower extremities, typically as a result of damage to the
		    spinal cord.
<?php
				break;
		    }

		    case 'paresis':
		    {
?>
		    "Paresis": a condition typified by a weakness of voluntary
		    movement.
<?php
				break;
		    }

		    case 'pellagra':
		    case 'pellegra':
		    {
?>
		    "Pellagra": a vitamin deficiency disease most commonly caused by
		    a chronic lack of niacin in the diet.
<?php
				break;
		    }

		    case 'peritonitis':
		    {
?>
		    "Peritonitis": inflamation of the tissue that lines the inner wall
		    of the abdomen and covers most of the abdominal organs.
<?php
				break;
		    }

		    case 'pernicious':
		    {
?>
		    "Pernicious Anaemia": a decrease in red blood cells that occurs 
		    when the intestines cannot properly absorb vitamin B12.
		    The "pernicious" aspect of the disease was its invariably
		    fatal prognosis prior to the discovery of treatment. 
<?php
				break;
		    }

		    case 'phthisis':
		    {
?>
		    "Phthisis": a disease characterized by the wasting away or atrophy
		    of the body or a part of the body.  Frequently pulmonary 
		    tuberculosis.
<?php
				break;
		    }

		    case 'pleurisy':
		    case 'pleuritis':
		    {
?>
		    "Pleurisy": painful inflammation of the lining of the cavity
		    surrounding the lungs.
<?php
				break;
		    }

		    case 'pneumonia':
		    {
				if ($prevword == 'hypostatic')
				{
?>
		    "Hypostatic pneumonia" usually results from the collection of 
		    fluid in the back of the lungs and occurs especially 
		    in those confined to a bed for extended periods.
<?php
				}
				else
				{
?>
		    "Pneumonia" is an inflammatory infection of the lungs, and in
		    particular the little air sacs called alveoli.  It is very
		    common as a complication of other conditions that result in
		    reduced immune response.
<?php
		 	}
				break;
		    }

		    case 'pott':
		    case "pott's":
		    {
?>
		    "Pott's disease": tuberculosis presenting in any other organ
		    other than the lung, for example in the spine.
<?php
				break;
		    }

		    case 'praecox':
		    {
				if ($prevword == 'dementia')
				{
?>
		    Dementia Praecox, literally precocious madness, refers to
		    a deteriorating psychotic condition that begins in the late
		    teens or early adulthood.  Schizophrenia. 
<?php
				}
				break;
		    }

		    case 'puerperal':
		    case 'purperal':
		    {
?>
		    "Puerperal fever": a bacterial infection contracted by women
		    during childbirth or miscarriage.  Prior to the introduction of
		    antibiotics it would develop into septicaemia which is
		    frequently fatal.  The typical source of the infection was the
		    unwashed hands of the doctor or midwife.
<?php
				break;
		    }

		    case 'pyaemia':
		    case 'pyemia':
		    {
?>
		    "Pyaemia": a type of septicaemia that leads to widespread
		    abscesses.
<?php
				break;
		    }

		    case 'pyelitis':
		    case 'pyelites':
		    {
?>
		    "Pyelitis": inflammation of the renal pelvis.
<?php
				break;
		    }

		    case 'pyorrhea':
		    case 'pyorrhoea':
		    {
?>
		    "Pyorrhea": purulent inflamation of the gums and tooth sockets.
<?php
				break;
		    }

		    case 'pyonephrosis':
		    {
?>
		    "Pyonephrosis": an infection of the renal collecting system
		    resulting in the accumulation of pus.
<?php
				break;
		    }

		    case 'quinsy':
		    case 'quinsey':
		    {
?>
		    "Quinsy": is a rare and potentially serious complication of
		    tonsillitis.  An abscess, a collection of pus, forms between the
		    tonsil and the wall of the throat.  This can happen when a
		    bacterial infection spreads from an infected tonsil to the
		    surrounding tissue.
<?php
				break;
		    }
		    case 'remittent':
		    {
?>
		    "Remittent Fever": a fever in which the symptoms temporarily abate
		    at regular intervals, but do not wholly cease.
		    Causes include typhoid and infectious mononucleosis.
<?php
				break;
		    }

		    case 'sarcoma':
		    {
?>
		    "Sarcoma": a cancer that arises from transformed cells of 
		    mesenchymal origin.
		    resulting in the accumulation of pus.
<?php
				break;
		    }

		    case 'scarlatina':
		    case 'scarlet':
		    {
?>
		    "Scarlet Fever":  An infectious disease which most commonly
		    affects 4 to 8 year old children. Symptoms include sore throat,
		    fever, and a red rash.
<?php
				break;
		    }

		    case 'sclerosis':
		    {
				if ($prevword == 'arterio' ||
				    $prevword == 'arterial')
				{		// atherosclerosis
?>
		    "Arterio- or Arterial sclerosis":
		    This is properly <i>atherosclerosis</i>, congestion of the arteries
		    due to the accumulation of fatty deposits on the lining.
<?php
				}		// atherosclerosis
				else
				{		// without qualifier
?>
		    "Sclerosis": a hardening of tissue, usually caused by a
		    replacement of the normal organ-specific tissue with
		    connective tissue.
<?php
				}		// without qualifier
				break;
		    }

		    case 'scrofula':
		    {
?>
		    "Scrofula": inflammation of the cervical lymph nodes in the neck due
		    to infection by mycobacteria, frequently a symptom of tuberculosis.
<?php
				break;
		    }

		    case 'sepsis':

		    case 'sepsis':
		    case 'septicaemia':
		    case 'septicemia':
		    {
?>
		    "Septicaemia": an infection in which large numbers of bacteria are
		    present in the blood.  It is commonly referred to as
		    blood poisoning.
<?php
				break;
		    }

		    case 'stenosis':
		    {
?>
		    "Stenosis": an abnormal narrowing of a blood vessel or other
		    tubular organ or structure.
<?php
				break;
		    }

		    case 'synovitis':
		    {
?>
            "Synovitis":  inflammation of the synovial membrane.
            This membrane lines joints that possess cavities, 
            known as <a href="https://en.wikipedia.org/wiki/Synovial_joint">
            synovial joints</a>. 
<?php
				break;
		    }

		    case 'tetanus':
		    {
?>
		    "Tetanus": a medical condition characterized by a prolonged
		    contraction of skeletal muscle fibers.  This is usually as a
		    result of contamination of a wound.
<?php
				break;
		    }

		    case 'thrombosis':
		    {
?>
		    "Thrombosis": the formation of a blood clot inside a blood
		    vessel, obstructing the flow of blood through the circulatory
		    system.
<?php
				break;
		    }

		    case 'thrush':
		    {
?>
		    "Thrush": an infection, typically of the mouth, caused by the 
		    candida fungus, also known as yeast.
<?php
				break;
		    }

		    case 'toxaemia':
		    case 'toxemia':
		    {
?>
		    "Toxaemia":  An abnormal condition of pregnancy characterized by
		    hypertension, edema, and protein in the urine as a result of
		    the presence of bacterial toxins in the blood.
<?php
				break;
		    }

		    case 'trismus':
		    {
?>
		    "Trismus": reduced opening of the jaws caused by spasm of the
		    muscles of mastication. 
<?php
				break;
		    }

		    case 'typhoid':
		    {
?>
		    "Typhoid Fever":  A common bacterial disease transmitted by
		    the ingestion of food or water contaminated with the feces of
		    an infected person.
<?php
				break;
		    }

		    case 'typhus':
		    {
?>
		    "Typhus": any of a number of diseases transmitted by rickettsia
		    bacteria.  Epidemic typhus is transmitted by lice.
<?php
				break;
		    }

		    case 'tuberculosis':
		    {
?>
		    "Tuberculosis":  A common bacterial disease that typically 
		    attacks the lungs, but can affect other organs.  Symptoms
		    include chronic cough, fever, and weight loss.  Tuberculosis
		    has killed more people throughout human history than any
		    other disease.
<?php
				break;
		    }

		    case 'uraemia':
		    case 'uremia':
		    case 'uraemic':
		    case 'uremic':
		    {
?>
		    "Uraemia": the disease accompanying kidney failure.
<?php
				break;
		    }

		    case 'vulgaris':
		    {
				if ($prevword == 'lupus')
				{
?>
		    "Lupus Vulgaris": painful cutaneous tuberculosis skin lesions
		    with nodular appearance.
		    
<?php
				}
				break;
		    }

		    default:
		    {		// unrecognized word
				$needPara	= false;
				break;
		    }		// unrecognized word
		}		// interpret word
		
		if ($needPara)
		    print "</p>\n";
		$prevword	= $word;
    }			// loop through all words in cause of death

    // close incomplete last paragraph
    if (!$needPara)
		print "</p>\n";
?>
  </div>
<?php
}			// loop through individuals
?>
