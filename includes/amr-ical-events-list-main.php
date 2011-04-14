<?php
define('AMR_ICAL_LIST_VERSION', '3.10');
define('AMR_PHPVERSION_REQUIRED', '5.2.0');
/*  these are  globals that we do not want easily changed -others are in the config file */
global $amr_options;
global $amrW;  /* set to W if running as widget, so that css id's will be different */
$amrW = '';
if (version_compare(AMR_PHPVERSION_REQUIRED, PHP_VERSION, '>')) {
	echo( '<h2>'.__('Minimum Php version '.AMR_PHPVERSION_REQUIRED.' required for Amr Ical Events.  Your version is '.PHP_VERSION,'amr-ical-events-list').	'</h2>');
	}
if (!(class_exists('DateTime'))) {
	echo '<h1>'.
	__ ('The <a href="http://au.php.net/manual/en/class.datetime.php"> DateTime Class </a> must be enabled on your system for this plugin to work. They may need to be enabled at compile time.  The class should exist by default in PHP version 5.2.','amr-ical-events-list')
	.'</h1>';}
/* see http://acko.net/blog/php-clone */
  if (version_compare(phpversion(), '5.0', '<')) {

    eval('function clone($object) {

      return $object;

    }

    ');

  }
/*----------------------------------------------------------------------------------------*/
function amr_ical_events_list_record_version() {
	update_option('amr-ical-events-list-version', AMR_ICAL_LIST_VERSION); // for upgrade checks 
}
/* --------------------------------------------------  */
function ical_ordinalize( $num ){

	if (in_array($num,array(11, 12, 13))) 
		return sprintf(_x('%s th',' the 11, 12 or 13th ' ,'amr-ical-events-list'), $num);

	switch( substr($num,-1) ){

		case 1 	: 	if ($num<10)	return $num; /* every 1 not every first */

				   	else 			return 
		sprintf(_x('%s st',' the twenty first etc ' ,'amr-ical-events-list'), $num);					

		case 2 	: 	return 
		sprintf(_x('%s nd',' the second ' ,'amr-ical-events-list'), $num);

		case 3 	: 	return 
		sprintf(_x('%s rd',' the third ' ,'amr-ical-events-list'), $num);

		default : 	return 
		sprintf(_x('%s th',' the nth ' ,'amr-ical-events-list'), $num);

	}

}
/*----------------------------------------------------------------------------------------*/
function ical_ordinalize_words( $i ){

	if 		($i == 1) 	return( __('the first','amr-ical-events-list'));

	else if ($i == 0) 	return (__('every','amr-ical-events-list'));

	else if ($i == -1) 	return ( __('the last','amr-ical-events-list'));

	else if ($i < 0) 	return ( sprintf (__('the %s last', 'amr-ical-events-list'), ical_ordinalize( -$i )));

	else 				return ( sprintf (__('the %s', 'amr-ical-events-list'), ical_ordinalize( $i )));

}
/*----------------------------------------------------------------------------------------*/
function amr_fulldaytext () {
global  $wp_locale;
	$fulldayofweek = array (

		'MO'=> $wp_locale->get_weekday('1'),

		'TU'=>$wp_locale->get_weekday('2'),

		'WE'=>$wp_locale->get_weekday('3'),

		'TH'=>$wp_locale->get_weekday('4'),

		'FR'=>$wp_locale->get_weekday('5'),

		'SA'=>$wp_locale->get_weekday('6'),

		'SU'=>$wp_locale->get_weekday('0'),

		);
		foreach ($fulldayofweek as $i => $d) { /* cater for lack of translations - somehow came back blank for non english */

			if (empty($d ))  $fulldayofweek[$i] = ucfirst(strtolower($i));

		}
	return ($fulldayofweek);
}
/*----------------------------------------------------------------------------------------*/
function amr_weekdayabbr () {
global  $wp_locale;

	$daysofweek = array ( /* use the wordpress localisation functions so we do not have to retranslate ourselevs */

		'MO'=> $wp_locale->get_weekday_abbrev('Monday'),

		'TU'=> $wp_locale->get_weekday_abbrev('Tuesday'),

		'WE'=> $wp_locale->get_weekday_abbrev('Wednesday'),

		'TH'=> $wp_locale->get_weekday_abbrev('Thursday'),

		'FR'=> $wp_locale->get_weekday_abbrev('Friday'),

		'SA'=> $wp_locale->get_weekday_abbrev('Saturday'),

		'SU'=> $wp_locale->get_weekday_abbrev('Sunday'),

	);

	foreach ($daysofweek as $i => $d) {

		if (empty($d ))  $daysofweek[$i] = $i;

	}
	return ($daysofweek);
}
/* --------------------------------------------------  */
function amr_dayofyear2date( $year, $DayInYear ) { /* Year if format YYYY, Day in year 1 to 366 */

	$d = new DateTime($year.'-01-01');
	date_modify($d, '+'.($DayInYear-1).' days');

return ($d);

}
/* --------------------------------------------------  */
function amr_get_week_no_with_wkst ($date, $wkst) { /* only copes with WKST MO, SU, SA. returns 0 if in l ast year  */
	$yearday 	= $date->format('z')+1;
	$jan1c		= new DateTime($date->format('Y').'-01-01');
	/* GET week 1 day 1 for our week start ============================================== */
	$dw = $jan1c->format('w') + 1; /* 0 (for Sunday) through 6 (for Saturday)  change to => 1 to 7 */
	if ($wkst === 'MO')  		$dw = $dw-1;
	else if ($wkst === 'SA') 	$dw = (($dw + 1) % 7); /* remainder */
	if ($dw == 0) 	$dw=7;
	if ($dw <=4) 	$adj = -$dw + 1;
	else 			$adj = -$dw + 8;
	$w1d1 = new DateTime();
	$w1d1 = clone $jan1c;
	if (!($adj == 0)) date_modify($w1d1,$adj.' days' );
	/* So now we have w1d1 ?============================================== */
	$w1d1yearday = $w1d1->format('z')+1;
	if ($yearday >= 4 ) {
		if ($w1d1 < $jan1c) {/* the start is in the prev year */
			$w2d1 = $w1d1; date_modify ($w2d1, '+7 days'); /* to bring it to this year */
			$w2d1yearday = $w2d1->format('z')+1;
			$weekno = floor((($yearday - $w2d1yearday)/7)+2);
		}
		else
		$weekno = floor((($yearday - $w1d1yearday)/7)+1);
	}
	else {
		if ($yearday < $w1d1yearday) $weekno = 0 ;/* ie last year */
		else $weekno = 1;
	}
	return($weekno);
}
/* --------------------------------------------------  */
function amr_prettyprint_weekst ($wkst) {
global $amr_day_of_week_no, $wp_locale;
if (empty($wkst)) return '';
return ('<em>'.sprintf(__('Weeks start on %s','amr-ical-events-list'), amr_get_weekday_from1($amr_day_of_week_no[$wkst])).'</em>');
}
/* --------------------------------------------------  */
function amr_prettyprint_r_ex_date ($rdate) { /* rrule or exrule */
global $amr_formats;  /* amr check that this get set to the chosen list type */

//	$df = pref_date_entry_format_string();
	if (is_array($rdate)) {
		foreach ($rdate as $i => $d) {
			if (is_object($d))
				$html[] = amr_format_date ($amr_formats['Day'], $d);


//			 = $d->format($df);  /* *** is it already in the right timezone or not ? If just doing 'date' for now, is okay? */
			else $html[] = $d;
		}
	}
	return (implode(', ', $html));
}
/* --------------------------------------------------  */
function amr_prettyprint_byday ($b) {
	$fulldayofweek = amr_fulldaytext(); /* MO, TU= etc*/
	$h = array();
	$html = '';
	if (is_array($b)) {
		foreach ($b as $d => $n) {
			if (is_array($n)) { /* must be n bydays */
				foreach ($n as $i => $n2) {
					$temp[] = ical_ordinalize_words($n2);
				}
				if (count($temp) == 2) $h[] = implode (__(' and ','amr-ical-events-list'),$temp).' '.$fulldayofweek[$d];
				else $h[] =  implode (', ',$temp).' '.$fulldayofweek[$d];
				$temp = array();
			}
			else { /* normal bydays */
				$h[] = $fulldayofweek[$d];

			}
			if (count($h) == 2) $html = implode(__(' and ','amr-ical-events-list'),$h);
			else $html =  implode (', ',$h);
			if (!is_array($n)) $html =	__('every ','amr-ical-events-list').$html;
		}
		return($html);
	}
	else return ($b);	/* who knows what is in if it is not an array? */

}
/* --------------------------------------------------  */
function amr_prettyprint_byordinal ($b) {
	$h = array();
	if (is_array($b)) {
		foreach ($b as $i => $d) {
			$h[] = ical_ordinalize_words($d);
		};
		if (count($h) == 2) $html = implode(__(' and ','amr-ical-events-list'),$h);
		else $html =  implode (',&nbsp;',$h);
		return($html);
	}
else return ($b);

}
/* --------------------------------------------------  */
function amr_prettyprint_bymonth ($b) {
global $wp_locale;
	$h = array();
	if (is_array($b)) {
		foreach ($b as $i => $d) {
			$h[] = $wp_locale->get_month($d);
		};
		if (count($h) == 2) $html = implode(__(' or ','amr-ical-events-list'),$h);
		else $html =  implode (',&nbsp;',$h);
		return($html);
	}
else return ($b);

}
/* --------------------------------------------------  */
function amr_prettyprint_duration ($duration) {
	if (empty($duration)) return;
	if (!is_array($duration)) echo $duration;
	else { $h = array();
		foreach ($duration as $i => $v) {
			$h[] = sprintf(_n(	'%s '.rtrim($i,'s') /* singular */ , '%s '.$i /* plural */, $v , 'amr-ical-events-list'), $v);
		}
		$html = implode (',&nbsp;',$h);

	}
	echo $html;
}
/* --------------------------------------------------  */
function amr_prettyprint_rule ($rule) { /* rrule or exrule */
/* Receive an array of prepared fields and combine it into a suitable descriptive string */
global 	$amr_freq,
		$amr_freq_unit,
		$amr_day_of_week_no,
		$wp_locale;
	$sep = '&nbsp;';
	$c = '';
	if (isset($rule['FREQ'])) {
		$nicefrequnit = $amr_freq_unit[$rule['FREQ']]; /* already translated value */
		if (isset($rule['INTERVAL'])) /*   the freq as it is repetitive */
			$interval  = ' '.sprintf(__('Every %s %s','amr-ical-events-list'), ical_ordinalize($rule['INTERVAL']),$nicefrequnit).$sep;
//		else $interval = ' '.sprintf( __('every %s','amr-ical-events-list'), $nicefrequnit).$sep; // sounds funny to have daily every day, only have if every 2nd etc

//		$nicefreq = $amr_freq[$rule['FREQ']].$interval; /* already translated value */

		else $interval = $amr_freq[$rule['FREQ']]; /* already translated value */

		if (isset($rule['BYSETPOS'])) $c .= ' '.
			sprintf(__('On %s instance within %s', 'amr-ical-events-list'),amr_prettyprint_byordinal($rule['BYSETPOS']),$interval);
//		else $c .= 	$nicefreq;
		else $c .= 	$interval;
		if (isset($rule['COUNT'])) $c .= ' '.sprintf(__('%s times','amr-ical-events-list'), $rule['COUNT']).$sep;
		if (isset($rule['UNTIL'])) {
			if ($rule['UNTIL-TIME'] === '00:00') $rule['UNTIL-TIME'] = '';
			else if (strtolower($rule['UNTIL-TIME']) === '12:00 am') $rule['UNTIL-TIME'] = '';

			$c .= '&nbsp;'.sprintf(__('until %s %s','amr-ical-events-list'), $rule['UNTIL-DATE'], $rule['UNTIL-TIME']).$sep;
			}
		if (isset($rule['MONTH'])) $c .= sprintf(__(' if month is %s','amr-ical-events-list'),amr_prettyprint_bymonth($rule['MONTH']));
//		if (isset($rule['BYWEEKNO'])) $c .= ' '.sprintf(__(' in weeks %s','amr-ical-events-list'),implode(',',$rule['BYWEEKNO']));
		if (isset($rule['BYWEEKNO'])) $c .= ' ' .sprintf(__(' in %s weeks of the year','amr-ical-events-list'),amr_prettyprint_byordinal($rule['BYWEEKNO']));
//		if (isset($rule['BYYEARDAY'])) $c .= ' '.sprintf(__('on the %s day of year','amr-ical-events-list'),implode(',',$rule['BYYEARDAY']));
		if (isset($rule['BYYEARDAY'])) $c .= ' '.sprintf(__('on %s day of the year','amr-ical-events-list'),amr_prettyprint_byordinal($rule['BYYEARDAY']));
		if (isset($rule['DAY'])) $c .= ' '.sprintf(__('on %s day of each month', 'amr-ical-events-list'),amr_prettyprint_byordinal($rule['DAY']));
		if (isset($rule['NBYDAY'])) $nbyday = ' '.sprintf(__('on %s ', 'amr-ical-events-list'),amr_prettyprint_byday($rule['NBYDAY']));
		if (isset($rule['BYDAY'])) $byday = ' '.sprintf(__('on %s ', 'amr-ical-events-list'),amr_prettyprint_byday($rule['BYDAY']));
		$ofthefreq = '';
		if (in_array($rule['FREQ'], array('MONTHLY', 'YEARLY')))
			$ofthefreq = sprintf (_x(' of the %s',' eg: last day of the year/month ','amr-ical-events-list'),__(strtolower ($amr_freq_unit[$rule['FREQ']]),'amr-ical-events-list'));
		if (isset ($nbyday) and isset ($byday)) $c .= $nbyday.__(' and ','amr-ical-events-list').$byday.$ofthefreq;
		else { if (isset ($byday)) $c .= $byday.$ofthefreq;
			if (isset ($nbyday)) $c .= $nbyday.$ofthefreq;
		}
		if (isset($rule['BYHOUR'])) $c .= ' '.sprintf(__('at the %s hour', 'amr-ical-events-list'),implode(',',$rule['BYHOUR']));
		if (isset($rule['BYMINUTE'])) $c .= ' '.sprintf(__('at the %s minute', 'amr-ical-events-list'),implode(',',$rule['BYMINUTE']));
		if (isset($rule['BYSECOND'])) $c .= ' '.sprintf(__('at the %s second', 'amr-ical-events-list'),implode(',',$rule['BYSECOND']));
		if (isset($rule['WKST'])) $c .= '; '.amr_prettyprint_weekst($rule['WKST']);
		}
	return (rtrim($c,','));
}
/* --------------------------------------------------  */
function amr_output_icalduration ($duarray) {
	$d = '';
	if (!empty($duarray['weeks'])) 	$d  = 'P'.(int)$duarray['weeks'].'W';
	if (!empty($duarray['days'])) 	$d .=     (int)$duarray['days'].'D';
	if (!empty($duarray['hours'])) 	$d .= 'T'.(int)$duarray['hours'].'H';
	if (!empty($duarray['minutes'])) $d .=    (int)$duarray['minutes'].'M';
	if (!empty($duarray['seconds'])) $d .=    (int)$duarray['seconds'].'S';
	return ($d);
}
/* ----------------------------------------------------------------------------------- */
function amr_prepare_pretty_rrule ($rule) {

global $ical_timezone, $amr_formats;

/* take the event and it's parsed rrule or exrule and convert some aspects for people use.  Used by both edit event and event info */

	$df = $amr_formats['Day'];

	$tf = $amr_formats['Time'];

	$rule['UNTIL-DATE'] = '';

	$rule['UNTIL-TIME'] = '';

	// if (isset($_GET['rdebug'])) {echo '<hr> inprep pretty';var_dump($rule);}

	foreach ($rule as $i=>$r) { $rule[strtoupper($i)] = $r;}

	if (isset($rule['UNTIL']) and is_object($rule['UNTIL'])) {  /* until is possibly in Z time, move to our time first */

			date_timezone_set($rule['UNTIL'], $ical_timezone);

			$rule['UNTIL-DATE'] = $rule['UNTIL']->format($df);

			$rule['UNTIL-TIME'] = $rule['UNTIL']->format($tf);

	}

	else if (!(isset($rule['COUNT']))) 	$rule['forever'] = 'forever';

	if (isset ($rule['NOMOWEBYDAY'])) { /* what the F?? */
			foreach ($rule['BYDAY'] as $j => $k) {
					$l = strlen($k);
					if ($l > 2) {  /* special treatment required - we have a numeric byday  */
						$d = substr($k, $l-2, $l);

						$rule['NBYDAY'][$d][substr ($k, 0, $l-2)] = true;
						$rule['BYDAY'][$d] = true;
					}
					else {

						$rule['BYDAY'][$k] = true; /* ie recurs every one of those days of week */
						$rule['NBYDAY'][$k]['0'] = true;

					}
					unset($rule['BYDAY'][$j]);
			}
	}
	return ($rule);

	}
/*--------------------------------------------------------------------------------*/
function amr_parseRepeats (&$event) {

global $amr_globaltz;

	// if (isset($_GET['rdebug'])) {echo '<hr>in parse repeats'; var_dump($event['RRULE'] );}

	if (!empty($event['RRULE'])) /* and is_string($event['RRULE'])) */
	$event['RRULE'] 	= amr_parseRRULE($event['RRULE']);

	if (!empty($event['EXRULE'])) /* and is_string($event['EXRULE']))	*/
	$event['EXRULE'] 	= amr_parseRRULE($event['EXRULE']);
	if (!empty($event['RDATE'])) /*and is_string($event['RDATE'])) */
	$event['RDATE'] 	= amr_parseRDATE($event['RDATE'],$amr_globaltz );
	if (!empty($event['EXDATE'])) /*and is_string($event['EXDATE']))*/
	$event['EXDATE'] 	= amr_parseRDATE($event['EXDATE'],$amr_globaltz );

    // if (isset($_GET['rdebug'])) {echo '<hr>in parse repeats'; var_dump($event['RRULE'] );}
	return ($event);

}
/* -------------------------------------------------------------------------*/
function amr_get_googletime($time)   {
	$t = clone ($time);  /* if you get a parse error, then you are not on PHP 5! */

    $t->setTimezone(new DateTimeZone("UTC"));

    return ($t->format("Ymd\THis\Z"));

   }
/*--------------------------------------------------------------------------------*/
function amr_get_googledate($time)   {
	$t = clone ($time);
    $t->setTimezone(new DateTimeZone("UTC"));
    return ($t->format("Ymd"));

   }
/*--------------------------------------------------------------------------------*/
function amr_get_googleeventdate($e) {
		if (isset ($e['StartTime'])) $d = (amr_get_googletime ($e['StartTime']));
		else if (isset ($e['EventDate'] )) $d = (amr_get_googledate ($e['EventDate'])); /* no time just a day */
		else return (''); /* we have no start date or start time */
		if (isset ($e['EndTime'])) $e = (amr_get_googletime ($e['EndTime']));
		else if (isset ($e['EndDate'])) $e = (amr_get_googledate ($e['EndDate']));
		else return ($d.'/'.$d);
		return ($d.'/'.$e);
   }
/*--------------------------------------------------------------------------------*/
function add_cal_to_google($cal) {
global $amr_options;
/* adds a button to add the current calemdar link to the users google calendar */
	$text1 = __('Add to google calendar', 'amr-ical-events-list');
	if (isset ($amr_options['no_images'])  and $amr_options['no_images']) 
		$text2 = __('Add to google', 'amr-ical-events-list');
	else	
		$text2 = '<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" title="'.$text1.'" alt="'.$text1.'" class="amr-bling" />';
	return (
	'<a class= "addtogoogle" href="http://www.google.com/calendar/render?cid='.htmlentities($cal).'" target="_blank"  title="'.$text1.'">'.$text2.'</a>');
}
/*--------------------------------------------------------------------------------*/
function add_event_to_google($e) {
global $amr_options;

	if (!isset($e['EventDate'])) return('');
	if (isset($e['LOCATION'])) $l = 	'&amp;location='.esc_html(strip_tags(str_replace(' ','%20',($e['LOCATION'] ))));
	else $l = '';
	if (!isset($e['DESCRIPTION'])) $e['DESCRIPTION'] = '';
	$t = __("Add event to google" , 'amr-ical-events-list');

	if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t2 = $t;
	else $t2 = '<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" alt="'.$t.'" class="amr-bling"/>';
	$details = amr_just_flatten_array ($e['DESCRIPTION']); //var_dump($details);
	if (!empty($details)) $details ='&amp;details='.rawurlencode(strip_tags($details));

/* adds a button to add the current calemdar link to the users google calendar */
	$html = '<a class= "hrefaddtogoogle" href="http://www.google.com/calendar/event?action=TEMPLATE'
	.'&amp;text='.str_replace(' ','%20',esc_html(strip_tags(amr_just_flatten_array ($e['SUMMARY']))))
	/* dates and times need to be in UTC */
	.'&amp;dates='.amr_get_googleeventdate($e)
	.$l
	.'&amp;trp=false'
	.$details
	.'" target="_blank" title="'.$t.'" >'.$t2.'</a>';
	return ($html);/* Note google only allows simple html*/
}
/* ---------------------------------------------------------------------- */
function amr_get_css_url_choices () {
	$dir = amr_get_css_path();
	$css_url = ICAL_EVENTS_CSS_URL;

	$files = amr_get_files($dir, '.css');

	if (!empty($files)) foreach ($files as $i => $f) $files[$i] = ICAL_EVENTS_CSS_URL.$f;
	$files[] = 	ICALLISTPLUGINURL.'css/icallist.css';
	return($files);
}
/* ---------------------------------------------------------------------- */
function amr_get_css_path() { /** Attempt to create  a css directory if it doesn't exist.
	 * Return the path if successful.*/
		$css_path = ICAL_EVENTS_CSS_DIR;
		$css_file = ($css_path.'custom_icallist.css');
		if (!file_exists($css_path)) { /* if there is no folder */
			if (wp_mkdir_p($css_path, 0777)) {
				// printf('<br />'.__('A css directory %s has been created','amr-ical-events-list'),'<code>'.$css_path.'</code>');
			}
			else {
				echo( '<br />'.sprintf(__('Error creating custom css directory %s. Please check permissions','amr-ical-events-list'),$css_path));
			}
		}
		if (!file_exists($css_file)) { /* if there is no starting css file, then copy the plugin version over  */
			amr_copy_file(ICALLISTPLUGINDIR.'css/icallist.css',$css_file );
		}
		return $css_path;
	}
/* ---------------------------------------------------------------------- */
function amr_copy_file( $from, $to) {
   if ( !copy($from ,$to ) ) {

			  // If copy failed, chmod file to 0644 and try again.

			 chmod($to, 0644);

			 if ( !copy($from , $to) )

				 return (new WP_Error('copy_failed', __('Could not copy file.', 'amr-ical-events-list'), $to ));

		 }

	chmod($to, 0644);
}
/*--------------------------------------------------------------------------------*/
function amr_ical_events_style()  /* check if there is a style spec, and file exists */{
global $amr_options;
global $amr_listtype;
	$icalstyledir = amr_get_css_path(); // create the custom css path if it exists ICALSTYLEURL;
	$icalstyleurl = ICALSTYLEURL;
	if ((isset($amr_options)) or ($amr_options = get_option ('amr-ical-events-list'))) {
		if ((isset ($amr_options['own_css'])) and !($amr_options['own_css'])) {
			if (empty($amr_options['cssfile'])) $icalstyleurl = ICALSTYLEURL;
			else {/* check if old saved option */
				if (stristr($amr_options['cssfile'],'http://')) $icalstyleurl = $amr_options['cssfile'];
				else $icalstyleurl = ICALLISTPLUGINURL.'css/'.$amr_options['cssfile'];
			}
			wp_register_style('amr-ical-events-list', $icalstyleurl, array( ), 1.0 , 'all' );
			wp_enqueue_style('amr-ical-events-list' );
			wp_register_style('amr-ical-events-list_print', ICALSTYLEPRINTURL, array(), 1.0 , 'print');
		    wp_enqueue_style('amr-ical-events-list_print');
		}
	}
	else {
		wp_register_style('amr-ical-events-list', $icalstyleurl, array( ), 1.0 , 'all' );
		wp_enqueue_style('amr-ical-events-list' );
		wp_register_style('amr-ical-events-list_print', ICALSTYLEPRINTURL, array(), 1.0 , 'print');
		wp_enqueue_style('amr-ical-events-list_print');
	}

}
/* --------------------------------------------------  sort through the options that define what to display in what column, in what sequence, delete the non display and sort column and sequenc  */
function prepare_order_and_sequence ($orderspec){
//	foreach ($amr_options[$format]['compprop'] as $key => $row)
	foreach ($orderspec as $key => $row) {
		if (( isset ($row['Column'])) && (!($row['Column']== "0") ))
		$order[$key] = $row;
	}
	if (!isset($order)) return;  /* Nothing is to be displayed */
	// Prepare to sort order for printing

	foreach ($order as $key => $row) {
		if ( isset ($row['Column'])) {	
			$col[$key]  = $row['Column'];
			$seq[$key] = $row['Order'];
		}
	}

	array_multisort($col, SORT_ASC, $seq, SORT_ASC, $order);
		
	foreach ($order as $k => $v)  {/* for each key,  prepare once*/
		$order[$k]['Before'] = stripslashes ($v['Before']);
		$order[$k]['After'] = stripslashes ($v['After']);
	}
// now re structure into columns rather than what we had before
	$columns = array();
	foreach ($order as $k => $spec) { 
		$columns[$spec['Column']][$k] = $spec; 
	}
	return ($columns);
//	return ($order);
}
/* --------------------------------------------------  */
function amr_show_refresh_option() {
global $amr_globaltz, $amr_lastcache, $amr_options, $amr_last_modified;
	$uri = add_query_arg(array('nocache'=>'true'), $_SERVER['REQUEST_URI']);
	if (!is_object($amr_lastcache)) $text = __('Last Refresh time unexpectedly not available','amr-ical-events-list');
	else {
		date_timezone_set($amr_lastcache, $amr_globaltz);
		$t = $amr_lastcache->format(get_option('time_format').' T');
		$text = __('Refresh calendars','amr-ical-events-list');
		$text2 = sprintf(__('Last refresh was at %s. ','amr-ical-events-list'),$t);
		}
	if (!is_object($amr_last_modified)) $text2 =  __('Remote file had no modifications. ','amr-ical-events-list');
	else {
		date_timezone_set($amr_last_modified, $amr_globaltz);
		$t2 = $amr_last_modified->format(get_option('date_format').' '.get_option('time_format').' T.');
		$text2 = sprintf(__('The remote file was last modified on %s.','amr-ical-events-list'),$t2);
		}

	if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t3 = $text;
	else $t3 = '<img src="'.IMAGES_LOCATION.REFRESHIMAGE
		.'" class="amr-bling" title="'.__('Click to refresh','amr-ical-events-list').' '.$text2.'" alt="'.$text.'" />';
	return ( '<a class="refresh" href="'.htmlentities($uri).'" title="'.$text.' '.$text2.'">'.$t3.'</a>');
}
/* --------------------------------------------------  */
function get_oldweekdays ($d) { /* Looks like it works compared to http://www.searchforancestors.com/utility/dayofweek.html and if not, an aproximation is better than nothing !*/
		$dummy = new DateTime();
		$dummy = clone ($d);
		date_modify($dummy,'+91500 weeks'); /* a guess from when the date started breaking, plus some extra*/
		$w = $dummy->format('w');
		return($w);
}
/* --------------------------------------------------  */
function amr_same_time ($d1, $d2) {
	if ($d1->format('His') === $d2->format('His')) return (true);
	else return (false);
}
/* --------------------------------------------------  */
function nl2br2($string) {
$s2 = str_replace(array('\n\n','\r\n'), '<br /><br />', $string);

$s2 = str_replace(array( '\r', '\n'), '<br />', $string);
return($s2);

}
/* --------------------------------------------------  */
function amr_amp ($content) { //trying to avoid double ?
	return (str_replace('&','&amp;',str_replace('&amp;','&',$content) ));
}
/* --------------------------------------------------  */
function amr_daysDifference( $beginDate, $endDate){ /* *** what if dates are in different timezones, and somehow cross over the days - will that ever happen??  */

   //explode the date by "-" and storing to array
   if (!(is_object($beginDate) and (is_object($endDate)))) return(false);
   $date_parts1=explode("-", $beginDate->format('n-j-Y'));

   $date_parts2=explode("-", $endDate->format('n-j-Y'));

   //gregoriantojd() Converts a Gregorian date to Julian Day Count  - month/day/year

   $start_date=gregoriantojd($date_parts1[0], $date_parts1[1], $date_parts1[2]);

   $end_date=gregoriantojd($date_parts2[0], $date_parts2[1], $date_parts2[2]);

   return ($end_date - $start_date);

}
/* --------------------------------------------------  */
function amr_secondsDifference( $beginDate, $endDate){ /* *** what if dates are in different timezones, and somehow cross over the days - will that ever happen??  */

   //explode the date by "-" and storing to array
   if (!(is_object($beginDate) and (is_object($endDate)))) return(false);
   $sec1 = (int) $beginDate->format('U');

   $sec2 = (int) $endDate->format('U');

   return ($sec2-$sec1);

}
/* --------------------------------------------------  */
function amr_calc_duration ( $start, $end) { /* assume in same timezone */
	/* In php 5.3 there is a date diff calculation */
	/* calculate weeks, days etc and return in array */
	/* don't want to use unix date stamp */
	if (!(is_object($start) and (is_object($end)))) return(false);

//	if (function_exists('date_diff')) { /* for php 5.3 only - untested locally  - need to check for 0 and unset */
//		$interval = date_diff($start, $end);
//		$d = (int) $interval->format('%d');
//		$d = $duarray['days'] = (int) $interval->format('%d');
//		$duarray['hours'] = (int) $interval->format('%h');
//		$duarray['minutes'] = (int) $interval->format('%m');
//		$duarray['seconds'] = (int) $interval->format('%s');
//		$duarray['weeks'] = $d / 7;  if ($duarray['weeks'] < 1) $duarray['weeks'] = 0;
//		$duarray['days'] = $d % 7;
//		return ($duarray);
//	}
	/* else ....  do our own non unix calc */
	$d = amr_daysDifference( $start, $end);
	/* weeks */
	$w = $d / 7;  if ($w < 1) $w = 0;
	$d = $d % 7;   /* the remainder of days after complete weeks taken out */
	/* Note we do not need to add an extra day  or prior period if the hours go over a day, as the previous calculation will already have worked that out ????*/

	/* hours */
	$b = $start->format('G');
	$e = $end->format('G');
	$h = $e - $b;
	if ($h < 0) {
		$d = $d - 1;
		$h = 24 + $h;
	}
	/* minutes */
	$b = $start->format('i');
	$e = $end->format('i');
	$m = $e - $b;
	if ($m < 0) {
		$h = $h - 1;
		$m = 60 + $m;
	}

	/* seconds */
	$b = $start->format('s');
	$e = $end->format('s');
	$s = $e - $b;
	if ($s < 0) {
		$m = $m - 1;
		$s = 60 + $s;
	}

	$duarray = array ();
	if ($w > 0) {$duarray['weeks'] = (int)$w;}
	if ($d > 0) {$duarray['days'] = (int)$d;}
	if ($h > 0) {$duarray['hours'] = (int)$h;}
	if ($m > 0) {$duarray['minutes'] = (int)$m;}
	if ($s > 0) {$duarray['seconds'] = (int)$s;}

	return ($duarray);
}
/* ---------------------------------------------------------------------- */
		/*
		 * Return true iff the two times span exactly 24 hours, from
		 * midnight one day to midnight the next.
		 */
function amr_is_all_day($d1, $d2) {
   if (!(is_object($d1) and (is_object($d2)))) return(false);

	if (($d1->format('His') === '000000') and
				($d2->format('His') === '000000')) {
				//$d1a = new DateTime();
				$d1a = clone ($d1);
				date_modify ($d1a,'next day');
				if ($d1a == $d2) return (true); //ie DTEND spec
			}
			return (false);
		}
/* ---------------------------------------------------------------------- */
/* return true if the event is untimed and the end is one day after the start */
function amr_is_an_ical_single_day($d1, $d2) {
//	If (ICAL_EVENTS_DEBUG) echo '<br>check if ical single day<br>'.$d1->format('c').'<br>'.$d2->format('c');

	$d1a = clone ($d1);
	date_modify ($d1a,'next day');
//	If (ICAL_EVENTS_DEBUG) echo '<br>check if ical single day<br>'.$d1a->format('c').'<br>'.$d2->format('c');
	if ($d1a === $d2) {
		return (true);
		}
	return (false);
	}
/* --------------------------------------------------------------------------------------*/
function amr_is_same_day($d1, $d2) { /** Return true iff the two specified times fall on the same day. */
		return (	$d1->format('Ymd') === 	$d2->format('Ymd'));
		}
/* --------------------------------------------------------------------------------------*/
function amr_is_before($d1, $d2) {	/* Return true if the first date is earlier than the second date */
		if ($d1 < $d2 ) return (true);
		else return (false);
	}
/* --------------------------------------------------------- */
function amr_format_duration ($arr) {
	/* receive an array of hours, min, sec */
	foreach ($arr as $i => $d) if ($d === 0) unset ($arr[$i]);
	$i = count($arr);

	if ($i > 1) $sep = ', ';
	else $sep = '';

	$d = '';
	if (isset ($arr['years'] )) {
		$d .= sprintf (_n ("%u year", "%u years", $arr['years'], 'amr-ical-events-list'), $arr['years']);
		$d .= $sep;
		$i = $i-1;
		}
	if (isset ($arr['months'] )) {
		$d .= sprintf (_n ("%u month ", "%u months ", $arr['months'], 'amr-ical-events-list'), $arr['months']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['weeks'] )) {
		$d .= sprintf (_n ("%u week ", "%u weeks", $arr['weeks'], 'amr-ical-events-list'), $arr['weeks']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if ((isset ($arr['days'] )) ) {
			$d .= sprintf (_n ("%u day", "%u days", $arr['days'], 'amr-ical-events-list'), $arr['days']);
//			If (ICAL_EVENTS_DEBUG) {echo ' and d = '.$d;}
			if ($i> 1) {$d .= $sep;}
			$i = $i-1;
		}
	if (isset ($arr['hours'] )) {
		$d .= sprintf (_n ("%u hour", "%u hours", $arr['hours'], 'amr-ical-events-list'), $arr['hours']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['minutes'] )) {
		$d .= sprintf (_n ("%u minute", "%u minutes", $arr['minutes'], 'amr-ical-events-list'), $arr['minutes']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['seconds'] )) {
		$d .= sprintf (_n ("%u second", "%u seconds", $arr['seconds'], 'amr-ical-events-list'), $arr['seconds']);

		}
	return($d);
	}
/* --------------------------------------------------------- */
function amr_format_tz ($tzstring) {
global $amr_globaltz, $amr_options;
	$url = esc_url($_SERVER['REQUEST_URI']);
	$tz = timezone_name_get($amr_globaltz);
	if ($tz === $tzstring) $tz2 = date_default_timezone_get();
	else $tz2 = $tzstring;
	if ($tz2===$tz) $tz2 = 'UTC';
	$text1 = __('Change Timezone','amr-ical-events-list');
	$text2 = sprintf( __('Timezone: %s, Click for %s','amr-ical-events-list'),$tz, $tz2);
	if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t3 = $text1;
	else $t3 = '<img title = "'.$text2.'" src="'.IMAGES_LOCATION.TIMEZONEIMAGE.'" class="amr-bling" alt="'.$text1.'" />';

	return ('<span class="timezone" ><a href="'
		.htmlentities(add_querystring_var($url,'tz',$tz2)).'" title="'
		.$text2.'" >'.$t3.' </a></span>');
}
/* --------------------------------------------------------- */
function amr_format_attendees ($attendees) {/* receive array of hopefully attendess[] CN and MAILTO, and possibly other */

	If (ICAL_EVENTS_DEBUG) {echo '<br />Attendee array:    '; var_dump($attendees);}
	$text = '';

	if (is_array($attendees))
		foreach ($attendees as $i => $attendee) {
			$list[] = amr_format_attendee ($attendee);
		}
		
	if (!empty($list)) $text = implode (', ',$list);
	return($text);
}
/* --------------------------------------------------------- */
function amr_format_attendee ($attendee) {  // do not show emails for privacy reasons

	if (!empty ($attendee['CN'])) {
		if (!empty  ($attendee['LINK']))
		$text = '<a href="'.$attendee['LINK'].'" >'.$attendee['CN'].'</a>';
		else $text = $attendee['CN'];
	}
	else {
		if (is_array($attendee)) $text= implode($attendee);
		else $text = $attendee;
	}

	return ($text);
}
/* --------------------------------------------------------- */
function amr_format_organiser ($org) {/* receive array of hopefully CN and MAILTO, and possibly SENTBY */
//	If (ICAL_EVENTS_DEBUG) {echo '<br />Organiser array:    '; var_dump($org);}
	$text = '';
//	if (!(is_array($org))) $org = amr_parseOrganiser('ORGANIZER;'.$org);  // may not have been parsed yet (eg in wp events)
//	var_dump($org);
	if (!empty ($org['CN'])) {
		if (!empty  ($org['MAILTO']))
		$text = '<a href="mailto:'.$org['MAILTO'].'" >'.$org['CN'].'</a>';
		else $text = $org['CN'];
	}
	else {
		if (!empty  ($org['MAILTO'])) $text = '<a href="mailto:'.$org['MAILTO'].'" >'.$org['MAILTO'].'</a>';
	}
	if (!empty ($text)) $text .= '&nbsp;';
	if (!empty ($org['SENT-BY'])) {
		$text .= __('Sent by ','amr-ical-events-list').'<a href="mailto:'.$org['SENT-BY'].'" >'.$org['SENT-BY'].'</a>';
	}
	return($text);
}/* --------------------------------------------------------- */
function amr_format_bookmark ($text) {
	return ('<a id="'.$text.'"></a>');  /* ***/
}
/* --------------------------------------------------------- */
function amr_format_rrule ($rule) {
	if (isset($rule[0]))  /* we have an array of rules, is this still valid or is it just one now *** if multiple valid, then code change required  */
		$rule = $rule[0];
	$rule2 = amr_prepare_pretty_rrule ($rule);
	$rule3 = amr_prettyprint_rule ($rule2);
	return ($rule3);
}
/* --------------------------------------------------------- */
function amr_derive_summary (&$e ) {
	global $amr_options;
	global $amr_listtype;
	global $amrW;
	global $amrwidget_options;
/* If there is a event url, use that as href, else use icsurl, use description as title */
	if (in_array($amr_options[$amr_listtype]['general']['ListHTMLStyle'], array('smallcalendar', 'largecalendar'))) $hoverdesc = false;
	else {
		if ($amrW == 'w_no_url') $hoverdesc = false;
		else $hoverdesc ='maybe';
	}

	if (isset($e['SUMMARY'])) $e['SUMMARY'] = htmlspecialchars(amr_just_flatten_array ($e['SUMMARY'] ));
	else return ('');
	if (isset($e['URL'])) $e_url = amr_just_flatten_array($e['URL']);
	else $e_url = '';
	/* If not a widget, not listype 4, then if no url, do not need or want a link */
	/* Correction - we want a link to the bookmark anchor on the calendar page***/
	if (empty($e_url))  {
		if (!($amrW == 'w_no_url'))  {
			if (!empty($amrwidget_options['moreurl'])) {
				$e_url = ' href="'.clean_url($amrwidget_options['moreurl'])
//				.'#'.$e['Bookmark']
				.'" ';
			}
			else {
				if (!empty($amr_options[$amr_listtype]['general']['Default Event URL'])) {
					$e_url = ' class="url" href="'
						.clean_url($amr_options[$amr_listtype]['general']['Default Event URL']).'" ';
					}
				else $e_url = ''; /*empty anchor as defined by w3.org */
				/* not a widget */
			}
		}
		else {return ($e['SUMMARY']);	}
	}
	else {
		$e_url = ' class="url" href="'.esc_url($e_url).'" ' ;
	}
	$e_desc =  '';
	if ($hoverdesc) {
		if (isset ($e['DESCRIPTION'])) {
			$e_desc = amr_just_flatten_array($e['DESCRIPTION']);
			}
	    if (!empty($e_desc)) {
			$e_desc = 'title="'.htmlspecialchars(str_replace( '\n', '  ', (strip_tags($e_desc)))).'"';
		}
	}
	if (!empty ($e_url))
		$e_summ = '<a '.$e_url.$e_desc.'>'. $e['SUMMARY'].'</a>';
	else $e_summ = $e['SUMMARY'];
	return( $e_summ );
}
/* --------------------------------------------------------- */
function amr_format_allday ($content) {
		if ($content == 'allday') return (__('all day', 'amr-ical-events-list'));
	else return ('');
}
/* --------------------------------------------------------- */
function amr_format_repeatable_property ($content, $k, $event, $before='', $after='') {
// for properties that can have multiple values and for which we have received an array of those values, we need to do the format routine for each value 

	$c = '';
	foreach ($content as $i => $v) {
		if (!(empty($v))) {
			$c .= amr_format_value ($v, $k, $event,	$before,$after) .' ';
			}
	}
	return ($c);
}
/* --------------------------------------------------------- */
function amr_format_value ($content, $k, $event,$before='', $after='') { /* include the event so we can check for things like all day */
/*  Format each Ical value for our presentation purposes
Note: Google does toss away the html when editing the text, but it is there if you add but don't edit.
what about all day?
*/
	global $amr_formats;  /* amr check that this get set to the chosen list type */
	global $amr_options;
	global $amr_listtype;
	
//	echo '<br >'.$k;
	if (empty($content)) return('');

	if ($k == 'ORGANIZER') 	{ // it is an array but a parsed one, not repeatable
		$htmlcontent = amr_format_organiser ($content); 
		}
	elseif ($k == 'ATTENDEE') 	{ 
		$htmlcontent = amr_format_attendees ($content); 
		}
	else if (is_object($content)) {
		switch ($k){
			case 'EventDate': {
				$htmlcontent = ('<abbr class="dtstart" title="'
//					.amr_format_date ('l jS F Y, H:i e ', $content).'">'
					.amr_format_date ('c', $content).'">' //must be ISO 8601 date for microformats to work

					.amr_format_date ($amr_formats['Day'], $content)
					.'</abbr>'
					);
				break;
			}				
			case 'EndDate': {
				$htmlcontent = ('<abbr class="dtend" title="'
					.amr_format_date ('c', $content).'">'  //must be ISO 8601 date
					.amr_format_date ($amr_formats['Day'], $content)
					.'</abbr>'
					);
					break;
			}
			case 'EndTime':
			case 'StartTime':{
				if (isset($event['ALLDAY']) and ($event['ALLDAY'] == 'allday')) 
					$htmlcontent = '';
				else 
					$htmlcontent = amr_format_date ($amr_formats['Time'], $content);
				break;
			}
			case 'DTSTART': 
			case 'DTEND':
			case 'UNTIL': {
				$htmlcontent = amr_format_date ($amr_formats['Day'], $content);
				if (empty($event['ALLDAY']) or !($event['ALLDAY'] == 'allday')) 
				$htmlcontent  .= ' '.amr_format_date ($amr_formats['Time'], $content);
				break;
				}
			case 'X-WR-TIMEZONE': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				$htmlcontent = amr_format_tz(timezone_name_get($content));
				break;
			}
			case 'TZID': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				$htmlcontent = amr_format_tz (timezone_name_get($content));
				break;
			}
			default: 	/* should not be any */
				$htmlcontent = amr_format_date ($amr_formats['DateTime'], $content);
		}
	}
	else if (is_array($content)) {  

		if ($k === 'DURATION') { 
			$htmlcontent = amr_format_duration ($content); 
		}
		elseif (($k === 'RRULE') or ($k === 'EXRULE')) {
			$htmlcontent = amr_format_rrule($content);
			}
		elseif (($k === 'RDATE') or ($k === 'EXDATE')) {
			$htmlcontent = amr_prettyprint_r_ex_date ($content);
			}
		elseif ($k == 'ATTACH') {
			if (isset($content[0]['type'] ))	{ 
				// then we are at the top level of the array, so can ask to handled repetaed values
				return ( amr_format_repeatable_property ($content,$k, $event, $before, $after)); 
			}
			else 
				$htmlcontent = amr_format_attach ($content, $event); 
		}	
		else {  /* simple array don't think we need to list the items separately eg: multiple comments or descriptions - just line  */
			return( amr_format_repeatable_property ($content,$k, $event, $before, $after)); 
			}
		}
	
	elseif (is_null($content) OR ($content === '')) 
		$htmlcontent = '';
	else {
		switch ($k){ 
			case 'COMMENT':
			case 'DESCRIPTION': {
				$htmlcontent = amr_click_and_trim(nl2br2(amr_amp($content)));
				break;
			}
			case 'SUMMARY':
			case 'icsurl': 
			case 'addtogoogle': 
			case 'addevent': 
			case 'subscribeevent': 
			case 'subscribeseries': 
			case 'map':
			case 'refresh':
			case 'attending_event': {
				$htmlcontent = $content; /* avoid hyperlink as we may have added url already */
				break;
			}	
			case 'URL': /* assume valid URL, should not need to validate here, then format it as such */
		
			case 'LOCATION': {
				$htmlcontent = (amr_click_and_trim(nl2br2(amr_amp($content))));
				break;
			}
			
			case 'X-WR-TIMEZONE':{	/* not parsed as object - since it is cal attribute, not property attribue */
				$htmlcontent = (amr_format_tz ($content));
				break;
			}
			case 'ALLDAY': {
				$htmlcontent =(amr_format_allday(($content)));
				break;
			}

			default: 	/* Convert any newlines to html breaks */
				$htmlcontent = str_replace("\n", "<br />", $content);

		}
	}

	if (empty ($htmlcontent) ) return;
	return ($before.$htmlcontent.$after);

}
/* ------------------------------------------------------------------------------------*/
function amr_add_duration_to_date (&$e, $d) {
/* NOTE: some date modify installations cannot cope with 0 durations, or just a +, so do not pass that ! */
/* adjust the signs  of the duration array as necessary to that date modify can handle it */

/*   dur-value  = (["+"] / "-") "P" (dur-date / dur-time / dur-week)

  dur-date   = dur-day [dur-time]

  dur-time   = "T" (dur-hour / dur-minute / dur-second)

  dur-week   = 1*DIGIT "W"

  dur-hour   = 1*DIGIT "H" [dur-minute]

  dur-minute = 1*DIGIT "M" [dur-second]

  dur-second = 1*DIGIT "S"

  dur-day    = 1*DIGIT "D"
  */
	if (empty($d)) return ($e);
	if ((isset($d['sign'] )) and ($d['sign'] === '-')) $dmod = '-';  /* then apply it to get our current end time */
	else $dmod = '+';
	foreach ($d as $i => $v)  {  /* the duration array must be in the right order */
		if (!($i === 'sign')) {
			if ( (!(empty($v))) and (!($v == '0')))	$dmod .= $v.' '.$i ;
			}
	}
	if ((!empty($dmod)) and (strlen($dmod) > 1)) date_modify ($e, $dmod );
	return ($e);
  }
 /* ------------------------------------------------------------------------------------*/
function amr_derive_dates (&$e) {
/* Derive basic date dependent data  - called early on before repeating */
	if (!isset($e['DTSTART']) ) return (false);
	if (is_array($e['DTSTART'])) $e['DTSTART'] = $e['DTSTART'][0];
	if (isset($e['DTEND']) and is_array($e['DTEND'])) $e['DTEND'] = $e['DTEND'][0];
	if ((isset ($e['DURATION'])) and (!isset ($e['DTEND'])))  {  /*** an array of the duration values, calc the end date or time */
		$e['DTEND'] = new DateTime();
		$e['DTEND'] = clone ($e['DTSTART']);
		$e['DTEND'] = amr_add_duration_to_date ($e['DTEND'], $e['DURATION']);
		If (ICAL_EVENTS_DEBUG) {echo '<br>DTEND set to = '.$e['DTEND']->format('c');}
	}
	else {
		if ((isset ($e['DTEND'])) and (empty ($e['DURATION']))) { /* we don't have a duration */

			$e['DURATION'] = $d = amr_calc_duration ( $e['DTSTART'], $e['DTEND']);		/* calc the duration from the original values*/

		}
	}
	if (isset ($e['DTEND']) ) {
		if (amr_is_all_day($e['DTSTART'], $e['DTEND'])) {
			$e['ALLDAY'] = 'allday';
		}
	}
/* else EndDate will be unset */




	return($e);
}
/* ------------------------------------------------------------------------------------*/
function amr_derive_eventdates_further (&$e) {
global $amr_globaltz;
/* Derive any date dependent data - requires EventDate at least to have been set */
	$now = date_create('now',$amr_globaltz );

	if (isset($e['EventDate'])) { date_timezone_set($e['EventDate'],$amr_globaltz );}
	if (!isset($e['Classes'])) $e['Classes'] = '';
	if (isset($e['EndDate'])) {
		date_timezone_set($e['EndDate'],$amr_globaltz );
		if (amr_is_before($e['EndDate'], $now)) $e['Classes'] .= ' history';
		}
	else /* ie there is no end date, just an event date */ if (amr_is_before($e['EventDate'], $now)) $e['Classes'] .= ' history';
	if (amr_is_before( $now, $e['EventDate'])) $e['Classes'] .= ' future';
	else $e['Classes'] .= ' inprogress';
	if (amr_is_same_day ($e['EventDate'],  $now)) $e['Classes'] .= ' today';

	if (isset ($e['Untimed'])) { /* if it is untimed, the ical spec says that the end date is the "next day" */
		$e['Classes'] .= ' untimed';
		if (isset ($e['EndDate']) ) {
			if (	(amr_is_an_ical_single_day ($e['EventDate'], $e['EndDate'])) OR
					(amr_is_same_day($e['EndDate'],  $e['EventDate'])) ) /* an ical generator error, but deal with it */ {
				unset ($e['EndDate']); /* so we don't display them unecessarily */
				unset ($e['EndTime']);
				}
			else { /* it must be a multi day, all day - due to spec, we need to chop a day off for presentation purposes */
				//$e['EndDate']->modify("-1 day");  // NO! Endate wil be created correct;y, DT END will remain as original
				if ($e['EventDate'] == $e['EndDate']) { /* */
				}
				}
			}
	}
	else $e['StartTime'] = $e['EventDate']; /* will format to time, later keep date  for max flex */

	
	if ((isset($e['ALLDAY']) and ($e['ALLDAY'] == 'allday')) OR
	    ((isset ($e['EndDate']) ) and (amr_is_all_day($e['EventDate'], $e['EndDate'])))) {

			unset ($e['StartTime']);
			unset ($e['EndTime']);
			$e['Classes'] .= ' allday';
//			$e['EndDate']->modify("-1 day"); //due to spec, we need to chop a day off for presentation purposes // NO! Endate wil be created correct;y, DT END will remain as original
		}
	else {
		if (isset ($e['EndDate'])) {
			if (amr_same_time($e['EventDate'], $e['EndDate'])) 
				unset ($e['EndTime']);
			else $e['EndTime'] = $e['EndDate'];
		}
	}
//		echo '<hr>'; var_dump($e);
	if (isset ($e['EndDate']) and amr_is_same_day($e['EndDate'],  $e['EventDate'])) {
			unset($e['EndDate']);  /* will just have end time if we need it */		
		}
	else $e['Classes'] .= ' multiday';
	


	return($e);
}
/* ------------------------------------------------------------------------------------*/
function amr_derive_component_further (&$e) {
$e = amr_derive_for_list_or_eventinfo ($e); // order nb - otherwise summary have link when we do not want it to
$e = amr_derive_info_for_list_only ($e);

return ($e);

}
/* ------------------------------------------------------------------------------------*/
function amr_derive_for_list_or_eventinfo (&$e) {
	if (isset ($e['GEO'])) {	
		$e['map'] = amr_ical_showmap($e['GEO']); 
		}
	else if ((isset ($e['LOCATION'])) and (!empty($e['LOCATION']))) {
			$e['map'] = amr_ical_showmap($e['LOCATION']);
	}
	if ($g = add_event_to_google($e))  
		$e['addevent'] = $g;
		
	if (function_exists ('amr_subscribe_to_event')) { 
		$g = amr_subscribe_to_event($e); 
		if (!empty($g) ) $e['subscribeevent'] = $g;

	}
	if (function_exists ('amr_subscribe_to_event_series')) {
		$g = amr_subscribe_to_event_series($e); 
		if (!empty($g) ) $e['subscribeseries'] = $g;
	}
	if (function_exists ('amr_indicate_attendance') and !empty($e['id'])) { 
		$e['attending_event'] = amr_indicate_attendance($e['id']);
	}
	return ($e);

}
/* ------------------------------------------------------------------------------------*/
function amr_derive_info_for_list_only (&$e) {
	
	/* Do not call this from eventinfo shortcode - it will cause an infinite loop! */
	if (!isset($e['Classes'])) $e['Classes'] = ''; //initialise
	if (isset ($e['EventDate'])) {
		amr_derive_eventdates_further($e);
//		$bookm = $e['EventDate']->format('U');
	}
//	else $bookm = '';
	/* Noew get some styling possibilities */
	if (isset ($e['RRULE']) or (isset ($e['RDATE']))) $e['Classes'] .= ' recur';
	if (isset ($e['STATUS']) ){
		$e['Classes'] .= ' '.amr_just_flatten_array($e['STATUS']);  /* would have values like 'CONFIRMED'*/
	}
	if (isset($e['name']))  $e['Classes'] .= ' '.$e['name'];
	if (isset($e['type']))  $e['Classes'] .= ' '.$e['type'];  /* so we can style events, todo's differently */
	if (isset($e['CATEGORIES']))
		$e['Classes'] .= ' '.str_replace(',',' ',amr_just_flatten_array($e['CATEGORIES']));
//	if (isset($e['UID'])) {
//		$e['Bookmark'] = str_replace('@','',$e['UID']);  /* must be before summary as it is used there .  Must be a char to start not a number and get rid of odd chars for validation*/
//		$e['Bookmark'] = 'a'.htmlentities(str_replace('http://','',$e['Bookmark'] ).$bookm);
//	}
	$e['SUMMARY'] = amr_derive_summary ($e);  // do not hover the description
	return ($e);
}
/* --------------------------------------------------  */
function amr_just_flatten_array ($arr) {
/* expecting array of text strings - convert to one txt string */
	$txt = '';
	if (is_array($arr)) {
		if (empty($arr)) return (null);
		else {
			foreach ($arr as $i => $v) {	$txt .= $v;	}
			return ($txt);
		}
	}
	else return($arr);
}
/* --------------------------------------------------  */
function amr_check_flatten_array ($arr) {
	if (is_array($arr)) {
		if (empty($arr)) return (null);
		else {
			foreach ($arr as $i => $v) {if (empty($v)) unset ($arr[$i]);}
			if (empty($arr) or (count($arr)< 1)) return (null);
			else return ($arr);
		}
	}
	else return ($arr);
}
/* --------------------------------------------------  */
function add_querystring_var($url, $key, $value) {
   /* replaces the first instance with the key and value passed */

	   $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');

	   $url = substr($url, 0, -1);

	   if (strpos($url, '?') === false) {

			return ($url . '?' . $key . '=' . $value);

	   } else {

			return ($url . '&' . $key . '=' . $value);

	   }

}

/* --------------------------------------------------  */
if (!function_exists('amr_derive_calprop_further')) {
function amr_derive_calprop_further (&$p) {
	global $amr_options;
	if (isset ($p['totalevents'])) $title = __('Total events: ', 'amr-ical-events-list').$p['totalevents'];	/* in case we have noename? ***/
	if (isset ($p['X-WR-CALDESC'])) {
		$p['X-WR-CALDESC'] = nl2br2 ($p['X-WR-CALDESC']);
		$desc = __($p['X-WR-CALDESC'],'amr-ical-events-list');  //allow translation of whatever value is in the ics file

	}
	else $desc = __('No description available','amr-ical-events-list');
	
	if (isset ($p['X-WR-CALNAME'])) { 
		$p['X-WR-CALNAME'] = __($p['X-WR-CALNAME'],'amr-ical-events-list');
	}
	if (isset ($p['icsurl']))  {/* must be!! */
		$p['addtogoogle'] = add_cal_to_google ($p['icsurl']);
		if (isset ($p['X-WR-CALNAME'])) {
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr-ical-events-list'),
				htmlentities ($p['X-WR-CALNAME']));
				$p['X-WR-CALNAME'] = '<a class="x-wr-calname" '
				.' title="'.$p['subscribe'].'"'
				.' href="'.htmlentities($p['icsurl']).'">'
				.htmlspecialchars($p['X-WR-CALNAME'])
				.'</a><!-- '.$desc.' -->';
		}
		else { // if we do not have a name, use the basename of the url
				$f = basename($p['icsurl'], ".ics");
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr-ical-events-list'), $f);
				$p['X-WR-CALNAME'] = '<a '
				.' title="'.$p['subscribe'].'"'
				.' href="'.htmlentities($p['icsurl']).'">'
				.$f
				.'</a>';
		}
		$t = __('Subscribe to calendar in your calendar application.', 'amr-ical-events-list');
		if (isset ($amr_options['no_images']) and $amr_options['no_images']) 
			$t3 = $t = __('Subscribe to calendar', 'amr-ical-events-list');
		else 
			$t3 = '<img class="subscribe amr-bling" src="'.IMAGES_LOCATION.CALENDARIMAGE.'" title= "'.$t.'" alt="'.$t.'" />';
			
		$p['icsurl'] = 
			'<a class="icalsubscribe" title="'.$p['subscribe']
			.'" href="'.htmlentities($p['icsurl']).'">'
			.$t3.'</a>';
		}
		$p['icalrefresh'] = amr_show_refresh_option();
		return ($p);
	}
}
/* --------------------------------------------------  */
function amr_get_htmlstylefile() {
	global $amr_options;
	global $amr_listtype;

	$custom_htmlstyle_file = $amr_options[$amr_listtype]['general']['customHTMLstylefile'];
	if (empty ($custom_htmlstyle_file ) ) 
		return false;
	$uploads = wp_upload_dir();
	if (!file_exists($uploads['basedir'].$custom_htmlstyle_file))  // just in case
		return false;
	else 
		$custom_htmlstyle_file = $uploads['basedir'].$custom_htmlstyle_file ;	
	return($custom_htmlstyle_file);	
}
/* --------------------------------------------------  */	
if (!function_exists('amr_list_properties')) {
	function amr_list_properties($icals, $tid, $class) {  /* List the calendar properties if requested in options  */
	global $amr_options;
	global $amr_listtype;
/* --- setup the html tags ---------------------------------------------- */
	$liststyle = (isset ($amr_options[$amr_listtype]['general']['ListHTMLStyle']))
				? $amr_options[$amr_listtype]['general']['ListHTMLStyle']
				: $liststyle = 'table';//; 'list'
	if ($liststyle === 'custom') {  // get the stored file uirl, if it does not exist, set to table
		$custom_htmlstyle_file = amr_get_htmlstylefile();
		if (empty ($custom_htmlstyle_file ) ) 
			$liststyle = 'table';
	}	

	switch ($liststyle) {
	case 'list' :
		$d ='<span ';
		$dc='</span>';
		$r   = '<div>';  
		$rc  = '</div>'; 
		$box = '<div';
		$boxc= '</div>';
		break;
	case 'breaks' :
		$d ='<span ';
		$dc ='</span>';
		$r   = '<span>';  
		$rc  = '</span>'; 
		$box = '<div';
		$boxc= '</div>';
		break;
	case 'table':
	case 'HTML5table':  // still using a table, so columns will work  but with html5 elements too
		$d 	='<td';
		$dc	='</td>';
		$r   = '<tr> ';  
		$rc  = '</tr> '; 
		$box = '<table';
		$boxc= '</table>';
		break;
	case 'HTML5':
		$box 	= '<section';
		$boxc	= '</section>';
		$r   	= '<header><h2>'; 
		$rc  	= '</h2></header> ';
		$d  	= '';
		$dc		= '';
		break;
	case 'custom':
		$where_am_i = 'in_calendar_properties';
		include ($custom_htmlstyle_file);
		break;	
	default:  /* the old way or tableoriginal*/
		$r   = '<tr> ';  $d ='<td';
		$rc  = '</tr> '; $dc='</td>';
		$box = '<table';
		$boxc= '</table>';
	}
	$html = '';
//	$order = prepare_order_and_sequence  ($amr_options[$amr_listtype]['calprop']);

	$columns = prepare_order_and_sequence  ($amr_options[$amr_listtype]['calprop']);
	if (!($columns)) return;
	
//	if (!($order)) return;
	foreach ($icals as $i => $p)	{ /* go through the options list and list the properties */
		amr_derive_calprop_further ($icals[$i]);
				
		foreach ($columns as $col => $data) {
		
//			echo '<br />'.$col.' '; var_dump($data);
		
			$cprop = '';
			foreach ($data as $k => $v) {			
				if (!empty ($icals[$i][$k])) {/*only take the fields that are specified in options  */		
					$cprop .= amr_format_value($icals[$i][$k], $k, 
						$icals[$i], $v['Before'], $v['After'] );
				}			
			}
			if (empty($cprop)) $cprop = '&nbsp;';
			if (!empty($d))  // if we have a td type html to bracket the column with 
				$cprop = $d.' class="col'.$col.'">'.$cprop.$dc;  
			$cprop .= AMR_NL;	
			

		} // end of columns for one calendar
		$html .= $r.$cprop.$rc.AMR_NL;
	} // end of calendars		

	if (!(empty($html)) ) {
			$html  = 
			((!empty($box)) ? $box.' id="'.$tid.'" class="'.$class.'">' : '')
			.$html
			.((!empty($boxc)) ? $boxc :  '');
		}
		
	return ($html);
}
}
/* --------------------------------------------------  */
function amr_doing_box_calendar () {
global $amr_options,
	$amr_listtype;

	$liststyle = (isset ($amr_options[$amr_listtype]['general']['ListHTMLStyle']))
			? $amr_options[$amr_listtype]['general']['ListHTMLStyle']
			: $liststyle = 'table';//; 'list'
	if (in_array ($liststyle, array('smallcalendar','largecalendar')))  return true;
	else return false;
}

function amr_get_groupings_requested () {
global 	$amr_options,
		$amr_listtype,
		$amr_groupings;

//		if (isset($_REQUEST['grouping'])) 		$gg = ucwords($_REQUEST['grouping']);
//		else 	$gg = '';
//		if (array_key_exists($gg,$amr_groupings)) 
//			$g[$gg] = true;
//		else {  // get the listtype groupings 
			if (isset ($amr_options[$amr_listtype]['grouping'])) {
				foreach (($amr_options[$amr_listtype]['grouping']) as $i => $v)
					{	if ($v) { $g[$i] = $v; }			}
			}
//		}
		if (!empty($g)) return ($g);
		else return false;
}
/* --------------------------------------------------  */
if (!function_exists('amr_list_events') ) {
function amr_list_events($events,  $tid, $class, $show_views=true) {
	global $amr_options,
		$amr_limits,
		$amr_listtype,
		$amr_liststyle,
		$amrW,
		$amrtotalevents,
		$amr_globaltz,
		$amr_groupings,
		$change_view_allowed;
		
	if (!defined('AMR_NL')) define('AMR_NL','PHP_EOL');	
		/* we want to maybe be able to replace the table html for alternate styling - may need to  keep the li items though */
	$amrconstrainedevents = count($events);
	$html = '';

	$templisttype= $amr_listtype;
//
	$liststyle = (isset ($amr_options[$templisttype]['general']['ListHTMLStyle']))
			? $amr_options[$templisttype]['general']['ListHTMLStyle']
			: $liststyle = 'table';//; 'list'
	if (in_array ($liststyle, array('smallcalendar','largecalendar'))) {
		/* is it a calendar box we want - handle separately */
			$html = amr_events_as_calendar($liststyle, $events, $tid, $class);
			return($html);
		}
/* ----------- check for groupings and compress these to requested groupings only */
		$g = amr_get_groupings_requested ();	
		if (!empty($g)) {
			foreach ($g as $gi=>$v) {
				$new[$gi] = $old[$gi] = '';
			}
		} /* initialise group change trackers */
/* ----------- flatten the array of component property options  */
		foreach ($amr_options[$templisttype]['compprop'] as $k => $v)
			{ 	foreach ($v as $i=>$j) 	{ $order[$i] = $j; 	}; 	}
						
//		$order = prepare_order_and_sequence ($order);
		$columns = prepare_order_and_sequence ($order);
//		if (!($order)) return;
		if (!$columns) return;
		else $no_cols = count($columns);
	//	{
	//		$no_cols = 1;  /* check how many columns there are for this calendar */
	//		foreach ($order as $k => $v) {
	//			if ($v['Column'] > $no_cols) {
	//				$no_cols = $v['Column'];
	//				};
	//		}
	//	}
/* --- setup the html tags ---------------------------------------------- */

	if ($liststyle === 'custom') {  // get the stored file uirl, if it does not exist, set to table
		$custom_htmlstyle_file = amr_get_htmlstylefile();
		if (empty ($custom_htmlstyle_file ) ) 
			$liststyle = 'table';
	}		
	switch ($liststyle) {
	case 'list' :
		$ul 	= '<span '; 	$li = '<span ';
		$ulc	= '</span>'; 	$lic = '</span> ';
		$row 	= '<li '; 
		$rowc 	= '</li>'.AMR_NL; 				
		$hcell	= '<span  '; 	
		$cell 	= '<span '; /* allow for a class specifictaion */
		$hcellc = '</span>'; 	
		$cellc 	= '</span>';
		$grow	= '<li ';         
		$growc  = '</li>'.AMR_NL; 	  
		$ghcell = '<span ';      
		$ghcellc= '</span>'.AMR_NL; 
		$head 	= '<div> '; 		//closed
		$headc 	= '</div>'.AMR_NL; 		
		$foot 	= '<div> ';  //closed
		$footc 	= '</div>'.AMR_NL; 				
		$body 	= '<ul '; // open
		$bodyc 	= '</ul>'.AMR_NL;
		$box 	= AMR_NL.'<div ';
		$boxc 	= '</div>'.AMR_NL;
		break;
	case 'table':
		$ul 	= '<div '; 	$li = '<div ';
		$ulc	= '</div>'; $lic = '</div>';
		$row 	= '<tr '; 	$hcell	='<th '; 	$cell 	='<td '; /* allow for a class specifictaion */
		$rowc 	= '</tr> '; $hcellc ='</th>'; 	$cellc 	='</td>';		
		$grow	= '<tr ';	$ghcell  = '<th colspan="'.$no_cols.'"'; $ghcellc = $hcellc;
		$growc  ='</tr>'.AMR_NL;       
		$head 	= '<thead>'; 	
		$headc 	= '</thead>';
		$foot 	= '<tfoot>'; 
		$footc 	= '</tfoot>'; 			
		$body 	= AMR_NL.'<tbody '; //open
		$bodyc 	= AMR_NL.'</tbody>'.AMR_NL;
		$box 	= '<table';
		$boxc 	= '</table>'.AMR_NL;
		break;
	case 'HTML5table' :
		/* historical - will fall away one day */
		$ul 	= ''; $li = '';
		$ulc	= ''; $lic = '';
		/* allow for a class specifictaion */
		$row 	= '<tr '; 	
		$rowc 	= '</tr> '; 				
		$hcell	='<th '; 		
		$hcellc ='</th>'; 					
		$cell 	='<td '; 	
		$cellc 	='</td>';			
		$grow	= '<tr ';	
		$ghcell = '<th colspan="'.$no_cols.'"'; 
		$ghcellc = $hcellc;
		$growc  ='</tr>'.AMR_NL;       
		$head 	= AMR_NL.'<thead>'; 	
		$headc 	= '</thead>'.AMR_NL; 	
		$foot 	= AMR_NL.'<tfoot>'; 	
		$footc 	= '</tfoot>'.AMR_NL; 
		$body 	= AMR_NL.'<tbody '; //open
		$bodyc 	= AMR_NL.'</tbody>'.AMR_NL;
		$box 	= AMR_NL.'<table';
		$boxc 	= '</table>'.AMR_NL;
		break;		
	case 'HTML5' :
		/* historical - will fall away one day */
		$ul 	= ''; $li = '';
		$ulc	= ''; $lic = '';
		/* allow for a class specifictaion */
		$row 	= '<article '; 	 // each event
		$rowc 	= '</article>'.AMR_NL;  
		$hcell	='<h2 '; 	// the 'column' header cell	
		$hcellc ='</h2>'; 	
		$cell 	=''; 	
		$cellc 	='';	
//		
		$grow	 = '<header><h3 ';	// the grouping html text for a group of events - not the surrounding selector
		$growc   = '</h3></header>'.AMR_NL; 
		$ghcell  = '';
		$ghcellc = ''; 
//		
		$head 	= '<h2 '; 	
		$headc 	= '</h2>'; 	 
		$foot 	= '<div '; 	
		$footc 	= '</div>';
//		
		$body 	= '<section ';	// the grouping html text for a group of events - not the surrounding selector
		$bodyc 	= '</section>'.AMR_NL;
//		
		$box 	= '<section';  // the whole calendar
		$boxc 	= '</section>'.AMR_NL;
		break;	
	case 'breaks' :
		$ul 	= ''; 	$li = '';
		$ulc	= ''; 	$lic = '';
		$row 	= ''; 	
		$rowc 	= ''; 	
		$hcell	='<div '; 	
		$hcellc ='</div>&nbsp;'; 	
		$cell 	='<div '; /* allow for a class specifictaion */
		$cellc 	='</div>';
		$grow	= '<div ';	    
		$growc  ='</div>'.AMR_NL;
		$ghcell = $hcell;       
		$ghcellc= $hcellc;
		$head 	= '<div> '; 	
		$headc 	= '</div>'.AMR_NL; 	
		$foot 	= '<div> '; 	
		$footc 	= '</div>'.AMR_NL; 	
		$body 	= AMR_NL.'<div '; //open
		$bodyc 	= '</div>'.AMR_NL;
		$box 	= AMR_NL.'<div';
		$boxc 	= '</div>'.AMR_NL;
		break;	
	case 'custom':
		$where_am_i = 'in_events';
		$ul 	= ''; 	$li = ''; // we will phase these out eventually 
		$ulc	= ''; 	$lic = '';
		include ($custom_htmlstyle_file);
	break;
		
	default:  /* the old way or tableoriginal*/
		$ul 	= '<ul';	$li = '<li';
		$ulc	= '</ul>';	$lic = '</li>';
		$row 	= '<tr '; 				$hcell	='<th '; 	$cell 	='<td '; /* allow for a class specifictaion */
		$rowc 	= '</tr> '; 			$hcellc ='</th>'; 	$cellc 	='</td>';
		$ghcell = '<th "  colspan="'.$no_cols.'"';
		$grow	= '<tr ';	        $growc  ='</tr>';
        $ghcellc= $hcellc;
		$head 	= AMR_NL.'<thead>'; 	$foot 	= AMR_NL.'<tfoot>'; 	$body 	= AMR_NL.'<tbody ';
		$headc 	= AMR_NL.'</thead>'; 	$footc 	= AMR_NL.'</tfoot>'; 	$bodyc 	= AMR_NL.'</tbody>';
		$box 	= AMR_NL.'<table';
		$boxc 	= '</table>'.AMR_NL;
	}

	/* -- show view options or not  ------------------------------------------*/

	if ((isset($amr_limits['show_views']))
	and ($amr_limits['show_views']) and $change_view_allowed) {
		$views = amrical_calendar_views();
	}
	else $views = '';
	/* -- show month year nav options or not  ----------------NOT IN USE - need to lift code out for reuse --------------------------*/

	if ((isset($amr_limits['show_month_nav']))
	and ($amr_limits['show_month_nav']) ) {
		if (isset ($amr_limits['months']))	$months = $amr_limits['months'];
		else $months = 1;
		$start    = new Datetime('now',$amr_globaltz);
		$start    = clone $amr_limits['start'];
		$month_nav_html = amr_month_year_links ($start, $months); // returns array ($nextlink, $prevlink, $dropdown
		$prevlink = $month_nav_html['prevlink'];
		$nextlink = $month_nav_html['nextlink'];
		$month_nav = '<div class="navigation">'
		.$prevlink.'&nbsp;'
		.amr_month_year_navigation ($start, $months)
		.'&nbsp;'
		.$nextlink.'</div>';
	}
	else $month_nav = '';

	if ((!empty ($views)) and !empty($cell))
		$views 		= $cell.' class="views">'. $views 	.$cellc;
	if ((!empty ($month_nav)) and !empty($cell)) 	
		$month_nav 	= $cell.' class="month_nav">'. $month_nav 	.$cellc;
	if ((!empty ($month_nav)) or (!empty ($views)))
		$navigation_html = 
		$box.' id = "'.$amrW.'calnav" >'
		.$body.'>'
		.((!empty($row)) ? $row.' class="calendar_navigation">' : '')
		.$month_nav
		.$views
		.$rowc
		.$bodyc
		.$boxc;
	else $navigation_html = '';
/* -- heading and footers code ------------------------------------------*/

	if (ICAL_EVENTS_DEBUG) {echo '<br />Limit parameters '; var_dump($amr_limits);}
	if (isset($amr_limits['headings'])) $doheadings = $amr_limits['headings'];
	else $doheadings = true;
	if (isset($amr_limits['pagination'])) $dopagination = $amr_limits['pagination'];
	else $dopagination = true;

	$headhtml = '';
	if ($doheadings) {
		$docolheading=false;

		foreach ($amr_options[$templisttype]['heading'] as $i => $h) {
			if (!empty($h)) $docolheading=true;
		}	
		if ($docolheading) { 	if (ICAL_EVENTS_DEBUG) {echo '<br />Do col headings '; }
		
			foreach ($columns as $i => $col) {
				if (isset($amr_options[$templisttype]['heading'][$i])) 
					$colhead = __($amr_options[$templisttype]['heading'][$i],'amr-ical-events-list');
				else 
					$colhead = '&nbsp;';
				$headhtml .= 
					((!empty($hcell)) ? $hcell.' class="amrcol'.$i.'">': '')
					.$colhead
					.((!empty($hcellc)) ? $hcellc : '');	
			}
			$html .= $head
			.(!empty($row) ? ($row.'>'): '')
			.$headhtml
			.(!empty($rowc) ? $rowc : '')
			.$headc;
		}
	}


/* ***** with thechange in list types, we have to rethink how we do the footers .... for tables we say the footers up front, but for others not. */
		$fhtml = '';
		if (!(isset($amr_options['ngiyabonga']) and ($amr_options['ngiyabonga']))) 
			$fhtml .= amr_ngiyabonga();
		else 
			$fhtml .='<!-- event calendar by anmari.com.  See it at icalevents.anmari.com -->';
		if ((!empty($amr_limits)) and ($amrtotalevents > $amrconstrainedevents) ) {
			if ($dopagination and function_exists('amr_semi_paginate')) 
				$fhtml .= amr_semi_paginate();
			if (function_exists('amr_ical_edit')) 
				$fhtml .= amr_add_new_event_link();
		}

		$alt = false;
/* -- body code ------------------------------------------*/
		if ((!is_array($events)) and (count($events) > 0 )) return ('');
		$groupedhtml = '';
		$changehtml = '';
		$startallgroups = true;
		foreach ($events as $i => $e) { /* for each event, loop through the properties and see if we should display */
			amr_derive_component_further ($e);
			if ((isset($e['Classes'])) and (!empty($e['Classes'])))
				$classes = strtolower($e['Classes']);
			else $classes = '';
			$eprop = ''; /*  each event on a new list */
			$prevcol = 0;
			$colcount = 0;
			$col = 1; /* reset where we are with columns */

			$rowhtml = '';
			foreach ($columns as $col => $order) {
				$eprop = '';
				foreach ($order as $k => $kv) { /* ie for one column in event, check how to order the bits  */
					/* Now check if we should print the component or not, we may have an array of empty string */
					if (isset($e[$k])) 
						$v = amr_check_flatten_array ($e[$k]); // *** huh? shoudl we do this here?
					else 
						$v =null;
					
					$selector = $li;
					$selectorend = $lic;			
					if (!empty($selector)) 	$selector .=' class="'.strtolower($k).'">';  
					if (!empty($v)) {
						$eprop .= $selector
							.amr_format_value($v, $k, $e,$kv['Before'],$kv['After'] )
							.$selectorend;  				
					}				
				} // end of a col
				
				if (empty($eprop)) $eprop = '&nbsp;';  // a value for a dummytable cell if tere were no values in the column
				
				if (!empty($ul))  // will phase this out eventually 
					$eprop = $ul.' class="amrcol'.$col.' amrcol">'
					.$eprop
					.$ulc;
				/* each column in a cell or list */
					
				$classes = '';		
				if (!empty($cell) ) { // if we have a selector that surounds each property , then add classes. 
					$classes = ' amrcol'.$col;
					if ($col == $no_cols) $classes .= ' lastcol'; /* only want the cell to be lastcol, not the row */
					$thiscolumn = $cell.' class="'.$classes.'">' .$eprop. (empty($cellc) ? '' : $cellc);
				}
				else $thiscolumn = $eprop; 
		
				$rowhtml .= $thiscolumn; // build up the row
			}
			
//			if (!($eprop === '')) { /* ------------------------------- if we have some event data to list  */

				/* -------------------------- Check for a grouping change, need to end last group, if there was one and start another */
				$changehtml = '';
				$groupclass = '';
				if (!empty($g) and ($g)) {
					foreach ($g as $gi=>$v) {
						if (isset($e['EventDate'])) 
							$grouping = format_grouping($gi, $e['EventDate']) ;
						else 
							$grouping = '';
						$new[$gi] = amr_string($grouping);
						if (!($new[$gi] == $old[$gi]))	{  
						/* we have a new group  */
							$id = amr_string($gi.$new[$gi]);
							$changehtml .=
								((!empty($body)) ? $body.' class="'.$gi.'"> ' : '')
								.((!empty($grow)) ? $grow.'class="group '.$gi.'">' : '')
								.((!empty($ghcell)) ? $ghcell.' class="'.$id.' group '.$gi. '" >' : '')
								.$grouping
								.$ghcellc
								.$growc
								;							
//								
							$old[$gi] = $new[$gi];	
 
							if (!$startallgroups) {// 	ie if we already had some going	
								$html .= $bodyc.'<!-- end group that just changed -->'; // finish off the group that just changed								
							}	
							//else  we are just starting, so no need to finish off											
						}						
					}				
					$startallgroups = false;	
					// so now we have finished off any changed groups, so now we can add the new bit in
					$html .= $changehtml;
									
				}
				if ($startallgroups) { // there were no groups, so we have no opening body
					$html .= $body.'>';
					$startallgroups = false;
				}  

				// so now we havefinsihed that group, start next 
				
				// save the event or row,  for next group 
				$rowhtml = (!empty($row) ? ($row.($alt ? ' class="odd alt':' class="').$classes.'"> ') : '')
					.$rowhtml
					.$rowc;

				if ($alt) 
					$alt=false; 
				else 
					$alt=true;
					
				$html .= $rowhtml;		/* build  the group of events , adding on eprop */
//				$groupedhtml = '';
//				$changehtml = '';		
			}
			//end of row or event	
		// finish off each group as there will not have been a change ?
		if (!empty($g) and ($g)) {
			foreach ($g as $gi=>$v) {
				$html .= $bodyc.'<!-- end grouping '.$gi.'-->';  
			}	
		}
		else {	// there may have been  no  grouping but still events 
			$html .= $bodyc.'<!-- end if no grouping -->';
		}


	if (!empty ($tid)) {
		$tid = ' id="'.$tid.'" ';
		$html = ((!empty($box)) ? ($box.$tid.' class="'.$class.'">') : '')
		.$html
		.$boxc;
	}
	if (!empty($fhtml))		
		$html = $html.AMR_NL.$fhtml;

	$html = $navigation_html.AMR_NL
		.$html.AMR_NL;
		
	return ($html);
	}

}
/* -------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_binary'))  {
	function amr_format_binary ($name,$binary) {
// getting error - data not in recognised format in the binary, so skip for now till someone wants it
		return null;
				$im = imagecreatefromstring($binary); 	
				$filename = ICAL_EVENTS_CACHE_LOCATION.'/ical-events-cache/'.$name.'.jpg';
				// Save the image as 'simpletext.jpg'
				imagejpeg($im, $filename);
				$uploads = wp_upload_dir();
				$url = $uploads[base_url].'/ical-events-cache/'.$name;
				return($url);
	}			
}
/* -------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_CID'))  {
	function amr_format_CID ($cid, $event) {
		return ($cid);
	}
}

/* -------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_attach'))  {
	function amr_format_attach ($item, $event) {  // receive 1 attachment each being an array of type, url, binary (opt)
	
		if (empty ($item)) return ;
		$hrefhtml = '';
//	---- handle binary
			if (!empty($item['binary'])) {
				$name = str_replace($event['UID'], ' ','').'-image';
				$src = amr_format_binary ($name,$item['binary']);
				if (!empty($src)) 
					$hrefhtml .= '<img class="ics_attachment" src="'.$src.'" />';
			}
//	---- handle CID
			else if (!empty($item['CID']) ) {
				$hrefhtml .= amr_format_CID ($item['CID'], $event);
			}
//	---- handle url, prepare title			
			if (empty($item['title'])) {  // make a title somehow
				if ( !empty($item['url']))   // this will only be  there if it is an internal event 		
					$item_title = esc_url($item['url']);
				else 
					$item_title = '&nbsp;';
			}
			else $item_title = $item['title'];
//	---- handle mime types or url			
			if (!empty($item['type']) ) {  // the fmttype or mime type  
		
				$typeparts = explode ('/',$item['type'] );	
// check for  title, type = audio/... video/.. etc
				if ($typeparts[0] === 'image') {  // only include if NOT already in the content 
				
					if (!empty($event['DESCRIPTION'])) {
						if (stristr($event['DESCRIPTION'], $item['url'])) 
						return;  // if=gnore it if already in content
						if ((!empty($item['thumb'])) and	
							(stristr($event['DESCRIPTION'], $item['thumb']))) return;
					}
					else if (!empty($item['thumb'])) {	
						$item_title = '<img alt="'.$item_title
						.'" src="'.esc_url($item['thumb']).'" />';
					}
				}
				else if ($typeparts[0] === 'text') { }
// do not do others for now - may not make sense to do , other than text

			}

			if (!empty($item['url'])) {  

				$item_title = apply_filters('amr_attachment_title', $item_title , $event);

				$hrefhtml .= '<a class="ics_attachment" href="'
				.$item['url']
				.'" title="'
				.__('Event attachment','amr-ical-events-list').'" >'
				.$item_title.'</a>';
			}
			else $hrefhtml .= $item_title;

		if (!empty($hrefhtml)) return ($hrefhtml);
	}
}
/* -------------------------------------------------------------------------------------------*/

function format_grouping ($grouping, $datestamp) {
/* check what the format for the grouping should be, call functions as necessary*/
global $amr_options;
global $amr_listtype;
global $amr_formats;
	if (in_array ($grouping ,array ('Year', 'Month','Day')))
		return (amr_format_date( $amr_options[$amr_listtype]['format'][$grouping], $datestamp));
	else if ($grouping === 'Week') {
			$f = $amr_formats['Week'];
			$w = amr_format_date( 'W', $datestamp);
			return (sprintf(__('Week  %u', 'amr-ical-events-list'),$w));
		}
	else
	{ 	/* for "Quarter",	"Astronomical Season",	"Traditional Season",	"Western Zodiac",	"Solar Term" */
		$func = str_replace(' ','_',$grouping);
		if (function_exists($func) ) {
			return call_user_func($func,$datestamp);
			}
		else  return ('No function defined for Date Grouping '.$grouping);
	}
}
/* -------------------------------------------------------------------------------------------*/
function amr_wp_format_date( $format, $datestamp, $gmttf) { /* want a  integer timestamp or a date object  */
global $amr_options;
/* Need to get rid the unnecessary dat logic - should only be using date objects for now */

	if (is_object($datestamp))	{
		$offset = $datestamp->getOffset();
		If (isset ($_REQUEST['tzdebug'])) {
			echo '<br />Want to format '.$datestamp->format('Ymd His').' in '.$format.' like this '.$datestamp->format($format).' but localised';
//			echo '<br />Add offset '.$offset/(60*60).' back to Unix timestamp to force correct localised date ';
			}
		$dateInt = $datestamp->format('U') /* + $offset */;
		}
	else if (is_integer ($datestamp)) $dateInt = $datestamp;
	else return(false);
	if (stristr($format, '%') ) return (strftime( $format, $dateInt ));  /* keep this for compatibility!  will not localise though */
	else {
		$text = date_i18n($format, $dateInt, $gmttf); /*  should  be false, otherwise we get the utc/gmt time.   */
		If (isset ($_REQUEST['tzdebug']))
			{	echo '<br />Localised with gmt=false: '.$text.'<br />';
				$text2 = date_i18n($format, $dateInt, false);
				echo 'Localised with gmt=true:  '.$text2.'<br />';
				$text3 = amr_date_i18n ('D, F j, Y g:i a', $datestamp);
				echo 'Localised with amr date obj fn: '.$text3.'<br />';
			}
		return ($text); //
		}
}
/* -------------------------------------------------------------------------------------------*/
function amr_format_date( $format, $datestamp) { /* want a  integer timestamp or a date object  */
global 	$amr_options,
		$amr_globaltz;
	if (isset ($amr_options ['date_localise'])) $method = $amr_options ['date_localise'];
	else $method = 'none';
	
	if (isset($_GET['tzdebug'])) echo  '<br />set tz for: '.$datestamp->format('c');
	
	date_timezone_set ($datestamp, $amr_globaltz);  /* Converting here, but then some derivations wrong eg: unsetting of end date */
	
	if (isset($_GET['tzdebug'])) echo  '<br />'.$datestamp->format('c');
	
	if ($method === 'wp') return amr_wp_format_date ( $format, $datestamp, false);
	else if ($method === 'wpgmt') return amr_wp_format_date ( $format, $datestamp, true);
	else if ($method === 'amr') return amr_date_i18n ( $format, $datestamp);
	else {
		if (stristr($format, '%') ) return (strftime( $format, $datestamp->format('U') ));  /* keep this for compatibility!  will not localise though */
		else return ($datestamp->format($format));
		}
}
/* ------------------------------------------------------------------------------------*/
		/** Sort the specified associative array by the specified key.
		 * Originally from
		 * http://us2.php.net/manual/en/function.usort.php.
		 */
function amr_sort_by_key($data, $key) {
	if (!(is_array($data)) ) return ($data);
			// Reverse sort
			$compare = create_function('$a, $b', 'if (!isset($a["' . $key . '"])) return false;'
			.'	if (!isset($b["' . $key . '"])) return (false) ;'
			.'	if  ($a["' . $key . '"] == $b["' . $key . '"]) { return 0; } else { return ($a["' . $key . '"] < $b["' . $key . '"]) ? -1 : 1; }');
			usort($data, $compare);
			return $data;
		}
/* ------------------------------------------------------------------------------------*/
function amr_falls_between($eventdate, $astart, $aend) {
		/*
		 * Return true iff the specified event falls between the given
		 * start and end times.
		 */
		if (($eventdate <= $aend) and
			($eventdate >= $astart)) return ( true);
		else return (false);
		}
/* ------------------------------------------------------------------------------------*/
function amr_event_should_be_shown($event, $astart, $aend) {
		/*
		 Return true if the specified event should be shown.  This could be due to a number of situations:
		 * there is no dtstart (it is a note?)
		 * event starts and ends between the start and end date
		 * event starts after the start date, before the end date - it may finish after the end date/time.
		 * event started before the start date and ends after the finish date
		 * event is an untimed event (eg: all day) and starts on the same day, but before the start datetime.  (Does it still have it's end date - we zap that at some point?)
		 */
		if (!isset ($event['DTSTART'] )) return (true);
		if (ICAL_EVENTS_DEBUG) echo '<hr>dtstart='.$event['DTSTART']->format('c');
		if ($event['DTSTART'] >= $astart) {
			if (ICAL_EVENTS_DEBUG) echo '<br />date >= start' ;
			if ($event['DTSTART'] <= $aend) {
				if (ICAL_EVENTS_DEBUG) echo '<br />date <= end' ;
				return (true);
				}
			else {
				if (ICAL_EVENTS_DEBUG) echo '<br />date > start' ;
				if (isset($event['Untimed']) and amr_is_same_day($event['DTSTART'],$aend )) {
					if (ICAL_EVENTS_DEBUG) echo '<br />date untimed and same day' ;
					return (true);
				}
				else {
					if (ICAL_EVENTS_DEBUG) { echo '<br />date not untimed or not same day' ;  echo '<hr>';}
					return (false);
					}
			}
		}
		else { /* ie DTSTART is < $astart, but may still be on same day, or not finished, ie continuing past */
			if (ICAL_EVENTS_DEBUG) echo '<br />date < start' ;
			if (isset($event['DTEND'])	and ($event['DTEND'] >= $astart)) {
				if (ICAL_EVENTS_DEBUG) echo '<br />date <= end' ;
				return (true);
			}
			else {
				if (ICAL_EVENTS_DEBUG) echo '<br /> No end....' ;
				if (isset($event['Untimed']) and amr_is_same_day($event['DTSTART'],$astart )) {
					if (ICAL_EVENTS_DEBUG) echo '..Is Untimed and same day' ;
					return (true);
				}
				else if (ICAL_EVENTS_DEBUG) echo '.. Is NOT Untimed or not same day' ;
			}

		}
}
/* ------------------------------------------------------------------------------------*/
function amr_arrayobj_unique2 (&$arr) { 	/* Process an array of datetime objects and remove duplicates 		 */
		/* 	Duplicates can arise from edit's of ical events - these will have different sequence numbers and possibly differnet details - delete one with lower sequence,
			Also there maybe? the possobility of complex recurring rules generating duplicates - these will have same sequence no's and should have same details - delete one
			Note: Mozilla does not seem to generate a SEQUENCE ID, but does do a X-MOZ-GENERATION.
		*/
		$limit = count ($arr);
		if (isset($_REQUEST['debugexc'])) {echo '<br><br>Check for modifications or exceptions for array of '.$limit.' records<br>'; }
		krsort ($arr);  /***  sort numerically  We can then walk through and "toss" the lower sequence numbers for a given uid and date instance */
		if (isset($_REQUEST['debugexc'])) foreach ($arr as $i=> $a) {echo '<br>..'.$i;}  /* Check the sorting */
		$seqprev = '';
		$uiddateprev = '';
		foreach ($arr as $i => $e) {
				if ($seqstart = strrpos($i, " ")) {
					$seq = (int) substr($i, $seqstart, 100);
					$uiddate = substr ($i, 0, $seqstart);
				}
				else {if (isset($_REQUEST['debugexc'])) echo '<hr/>Must be a unique event? as key does not match pattern'; break;} /* not an ical event, must be a unique post event */
				if (isset($_REQUEST['debugexc'])) {
					echo '<br />seq='.$seq.' '.substr($i,0,6).'...'.substr($i,$seqstart-26,200);
					echo '<br />uidate: '.$uiddate;
					echo '<br />uidprv: '.$uiddateprev;
				}
				if ($uiddate == $uiddateprev) {
					if ($seq < $seqprev ) {
						unset ($arr[$i]);
						if (isset($_REQUEST['debugexc'])) echo '<br /><b>Delete seq: Seq was ='.$seq.' and $seqprev was: '.$seqprev	.'</b>';
						}
					else if ($seqprev < $seq) {
						if (isset ($iprev)) unset ($arr[$iprev]);
						if (isset($_REQUEST['debugexc']))
							echo '<br /><b>Delete seqprev Seq was ='.$seq.' and seqprev was: '.$seqprev.'</b>';
					}
					else echo '<br ><b>Unexpected inability to sort modification from original.  Please inform administrator. ? seqprev = '.$seqprev.' and seq = '.$seq.'</b>';
					/* Note that while sequence numbers can be the same for a recurrencid, the plugn will add 999 for a recurrence id */
				}
				$uiddateprev = $uiddate;
				$seqprev = $seq;
				$iprev = $i;
//				if (!(empty($seq)) and (!($seq===" "))) {
	//				if (isset($_REQUEST['debugexc'])) echo '<br />** Possible exception with seq: '.$seq.' - check for outdated instances for <br />'.substr($i,0,6).'...'.substr($i,$seqstart-26,200);
//				}
			}
	}
	/* ========================================================================= */
function amr_repeat_anevent(&$event, $astart, $aend, $limit) {
	/* for a single event, handle the repeats as much as is possible */
	$repeats = array();
	$exclusions = array();
	$repeatstart = new DateTime();
	$repeatstart = clone $event['DTSTART'];
	$event = amr_parseRepeats($event);

	if (!empty($event['RRULE']))	{

		if (isset($_GET['rdebug'])) {
			echo '<hr />After Parsing Repeats:<br />';
			if (isset ($event['RRULE'])) { echo ' RRULE:<br />';var_dump($event['RRULE']); }
			echo '<br /> RDATE:<br />';
			if (isset ($event['RDATE'])) var_dump($event['RDATE']);
			echo '<br /> EXDATE:<br />';
			if (isset ($event['EXDATE'])) var_dump($event['EXDATE']);
			echo '<br /> EXRULE:<br />';
			if (isset ($event['EXRULE'])) var_dump($event['EXRULE']);

			}

		if (!isset($event['RRULE'][0])) $event['RRULE'] = array($event['RRULE']); /* depending where we got our rrule from, we may or may not have multiple */
		if (ICAL_EVENTS_DEBUG) { echo '<br><b>We have '.count($event['RRULE']).' rrules</b>';}
		foreach ($event['RRULE'] as $i => $rrule) {

			$reps = amr_process_RRULE($rrule, $repeatstart, $astart, $aend, $limit);
			if (is_array($reps) and count($reps) > 0) {
				$repeats = array_merge ($repeats, $reps);
			}
		}
		if (ICAL_EVENTS_DEBUG) { echo '<br>Got '.count($repeats). ' after RRULE';}
	}
	if (!empty($event['RDATE']))	{
		if (ICAL_EVENTS_DEBUG) { echo '<br>Processing RDATES...';}
		foreach ($event['RDATE'] as $i => $rdate) {
					$reps = amr_process_RDATE  ($rdate, $repeatstart, $aend, $limit);
					if (is_array($reps) and count($reps) > 0) {
						$repeats  = array_merge($repeats , $reps);
					}
				}
				if (ICAL_EVENTS_DEBUG) { echo '<br>Got '.count($repeats). ' after RDATE';}
		}

	if (!empty($event['EXRULE']))	{
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Have EXRULE </h3>';var_dump($event['EXRULE']);}
			foreach ($event['EXRULE'] as $i => $exrule) {
				$reps = amr_process_RRULE($exrule, $repeatstart, $astart, $aend, $limit);
				if (is_array($reps) and count($reps) > 0) {
					$exclusions  = $reps;
				}
			}
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Got '.count($exclusions). ' after EXRULE</h3>';}
		}

	if (!empty($event['EXDATE']))	{
			if (ICAL_EVENTS_DEBUG or isset($_GET['rdebug'])) {
				echo '<br><h4>Have EXDATE </h4>';
				var_dump($event['EXDATE']);
			}
			foreach ($event['EXDATE'] as $i => $exdate) {
				$reps = amr_process_RDATE($exdate, $repeatstart, $aend, $limit);
				if (is_array($reps) and count($reps) > 0) {
						$exclusions  = array_merge($exclusions , $reps);
				}

			}
			if (ICAL_EVENTS_DEBUG) { foreach ($exclusions as $z => $y) echo '<br />'.$y->format('c');}
			if (ICAL_EVENTS_DEBUG) { echo '<br />Got  '.count($exclusions). ' exclusions after checking EXDATE';}
		}
		
	if (ICAL_EVENTS_DEBUG) { echo '<br />Still have '.count($repeats). ' before exclusions<br />';}
			/* Now remove the exclusions from the repeat instances */
		if (is_array($exclusions) and count ($exclusions) > 0) {
			if (ICAL_EVENTS_DEBUG) { echo '<br />'; var_dump($exclusions); }
			foreach ($exclusions as $i => $excl) {
			    foreach ($repeats as $j => $rep) {
				    if ($excl->format('c') === $rep->format('c')) {
//					if (!($excl < $rep) and !($excl > $rep)) {
						if (ICAL_EVENTS_DEBUG) {
							echo '<br> Exclusion matches repeat date, so exclude this date '.$j.' '. $rep->format('c');
						}
						unset($repeats[$j]); /* will create a gap in the index, but that's okay, not reliant on it  */
					}
				}
			}
		}
		if (ICAL_EVENTS_DEBUG) { echo '<br>Now have '.count($repeats). ' after  exclusions '; }
		
		return ($repeats);

	}
	/* ========================================================================= */
function debug_print_event ($e, $nest=0) {
		$tab = '';
		echo '<ul>';
		foreach ($e as $i => $f) {
			if (is_object($f)) echo '<li>'.$tab.$i.' = '.$f->format('c').'</li>';
			else if (is_array($f)) {echo '<li>'.$tab.$i; debug_print_event ($f, ($nest+1)); echo '</li>';}
				else {echo '<li>';
//				.$tab.$i.' = '
				if (!(is_int($i))) echo $i.' = ';
				var_dump($f).'</li>';
				}
		}
		echo '</ul>';
	}
	/* ========================================================================= */
function amr_create_enddate (&$e) {
	/* if the necessary data exist, then create the end date for a possibly repeated event.  - note this is NOT the DTEND */
	if (isset ($e['DURATION'])) {/* if not just an alarm */
		if (isset ($e['EventDate'])) {
			$e['EndDate'] = new DateTime();
			$e['EndDate'] = clone ($e['EventDate']);
			$e['EndDate'] = amr_add_duration_to_date ($e['EndDate'], $e['DURATION']);
			//check here whether all day or not  - human expects 1 day less, DTEND expects next day - keep it so
			if (!empty ($e['ALLDAY']) and ($e['ALLDAY'] == 'allday')) { 
				date_modify($e['EndDate'],'-1 day');

				if (ICAL_EVENTS_DEBUG) echo '<hr> All day End Date set  '.$e['EndDate']->format ('c').'<hr>';
				
			}
			return (true);
		}
	}
	else return (false);
	}
	/* ========================================================================= */
function amr_generate_repeats(&$event, $astart, $aend, $limit) { /* takes an event and some parameters and generates the repeat events */
		$repeats = array(); // and array of dates
		$newevents = array();  // an array of events
		if (isset($event['DTSTART'])) {
			if (is_object($event['DTSTART'])) {
				$dtstart = $event['DTSTART'];
				$dt = empty($event['DTSTART']) ? '' : $event['DTSTART']->format('c'); /* begin setting up the event key that will help us check for modifocations - semingly duplicates! - overwrite for repeats */
			}
			else {if (ICAL_EVENTS_DEBUG) echo('<r><b>DTSTART is not an object</b>'.$event['DTSTART']); return (false); /* it is set, but it is not an aobject ? */	}
			}
		else { /* possibly an undated, non repeating VTODO or Vjournal- no repeating to be done if no DTSTART, and no RDATE */
			$dt = '-nodtstart';
			if (!isset($event['RDATE']))	{
				if (isset ($event['UID'])) 	$newevents[$event['UID']] = $event;
				else echo('This event is invalid.  It has no UID.');
				return ($newevents); /* possibly an undated, non repeating VTODO or Vjournal- no repeating to be done if no DTSTART, and no RDATE */
			}
			else {
				echo ('This event is invalid.  It has no DTSTART, but does have RDATE.  Not allowed according to ical spec.');
				return(false);
			/***check for repeating RDATEs if no start date */
			}
		}
		/* To handle modifications, use a key to the events, so can match any later mods with a repeating event we may have generated */
		$seq = empty($event['SEQUENCE']) ? '0' : $event['SEQUENCE']; /* begin setting up the event key that will help us check for modifications - semingly duplicates!*/
		if (!isset($event['UID'])) $event['UID'] = 'NoUID';
		 /* there is no repeating rule, so just copy over */
		if (isset ($event['RECURRENCE-ID'])) {   /* a modification or exceptions to a repeating instance ? */
//					$recdate = $dt;
			if (is_array($event['RECURRENCE-ID'])) { /* just take first, should only be one */
				$recdateobj = $event['RECURRENCE-ID'][0];
				$recdate = $recdateobj->format('YmdHis');   // purely identifies specifc instances of a repeating rule are affected by the exception/modification
				if (isset ($_GET['debugexc'])) {echo '<br /> Flag recurrence modification for '.$recdate; }
			}
			else if (is_object($event['RECURRENCE-ID'])) {  /* Then it is a date instance which has been modified .  We need to overwrite the appropriate repeating dates.  This is done later? *** */
				$recdateobj = $event['RECURRENCE-ID'];
				$recdate = $recdateobj->format('YmdHis');
				if (isset ($_GET['debugexc'])) {echo '<br /> We have a single object recurrence date to check for modifications of an event.' ;echo($recdate);}
			}
			else { /****  should deal with THISANDFUTURE or THISANDPRIOR  EG:
				 RECURRENCE-ID;RANGE=THISANDPRIOR:19980401T133000Z */
				echo '<br>THISAND.... modification to repeating event encountered.  This cannot be dealt with yet'; var_dump($event['RECURRENCE-ID']);
			}
			if ( amr_event_should_be_shown($event,$astart, $aend) or
				amr_falls_between($recdateobj, $astart, $aend) or  /* If the modification relates to an event  instance (ie date) that is in range */
				amr_is_same_day ($recdateobj, $astart) or /* if on same day, may be an all day event - will constrain finally later */
				amr_is_same_day ($recdateobj, $aend)
				) /* OR the new date is in our display range, */{
				$key = $event['UID'].' '.$recdate.' '.$seq.'999';  /* By virtue of being a recurrence id it should override a non recurrence (ie normal) even if they have the same sequence */
				$newevents[$key] = $event;  /* so we drop the old events used to generate the repeats */
				$newevents[$key]['EventDate'] = new DateTime();
				$newevents[$key]['EventDate'] = clone ($dtstart);
				if (!(amr_create_enddate($newevents[$key]))) {if (ICAL_EVENTS_DEBUG) echo ' ** No end date - is it an alarm? ';};
			}
			else {
				if (isset ($_GET['debugexc'])) {
					echo '<br /> '.$recdate.' not in range and '.$dtstart->format('c').' not in range' ;
//					debug_print_event ($event);
					}
				return(false); /* the modification and the instance that it relates to are not in our date range */
			}
		}
		else { /* It is not a recurrence id, may be a repating, or solo */
			if (isset($dtstart) ) {
				if ( amr_is_before ($dtstart, $aend) ) {  /* If the start is after our end limit, then skip this event */
				if (isset($event['RRULE']) or (isset($event['RDATE']))) {
							/* if have, must use dtstart in case we are dependent on it's characteristics,. We can exclude too early dates later on */
					$repeats = amr_repeat_anevent($event, $astart, $aend, $limit );  /**** try for a more efficient start? */
					if (ICAL_EVENTS_DEBUG) {echo '<br>Num of Repeats to be created: '.count($repeats).'<br>';}
					/* now need to convert back to a full event by copying the event data for each repeat */
					if (is_array($repeats) and (count($repeats) > 0)) {
						foreach ($repeats as $i => $r) {
							$repkey = $event['UID'].' '.$r->format('YmdHis').' '.$seq;	/* Don't use timezone - some recurrence id's maybe created with universal dates */
							if (isset ($newevents[$repkey])) {
								// error_log('Unexpected Duplication of Repeating Event '.$repkey.' - error in ical file or error in plugin?'); // only happened on weird rrule - may be valid anyway so as not to miss anything
							}
							$newevents[$repkey] = $event;  // copy the event data over - note objects will point to same object unless we clone   Use duration or new /clone Enddate
							$newevents[$repkey]['EventDate'] = new DateTime();
							$newevents[$repkey]['EventDate'] = clone ($r);
							if (ICAL_EVENTS_DEBUG) {echo '<br>Created '.$newevents[$repkey]['EventDate']->format('YmdHis l');	}
							if (!amr_create_enddate($newevents[$repkey])) {
								if (ICAL_EVENTS_DEBUG) echo ' ** No end date created, maybe just a start? ';
								};
						}
					}
				}
				else {
					$key = $event['UID'].' '.$dt.' '.$seq;  /* No Recurrence id and no RRULE or RDATE */
					$newevents[$key] = $event;  // copy the event data over - note objects will point to same object - is this an issue?   Use duration or new /clone Enddate
					$newevents[$key]['EventDate'] = new DateTime();
					$newevents[$key]['EventDate'] = clone ($dtstart);
					if (isset ($newevents[$key]['DURATION'] )) {
						amr_create_enddate ($newevents[$key]);  //changed because EndDate not same as DTEND if all day
						}
					}
				}
			}
			else {
				$key = $event['UID'].' '.$dt.' '.$seq;
				$newevents[$key] = $event;
				$newevents[$key]['EventDate'] = '';
				}
		}
		if (ICAL_EVENTS_DEBUG) echo '<hr>Returning '.count($newevents).' events';
		return ($newevents);
	}
/* ------------------------------------------------------------------------------------*/
	/*
	 * Constrain the list of COMPONENTS to those which fall between the
	 * specified start and end time, up to the specified number of
	 * events.
	 * Note; MUST take  RECURRENCE -ID if the recurrence date is in range (even though the DTSTART may not be), as they may be modifying a date that is within the range
	 */
function amr_constrain_components($events, $start, $end, $limit) {
global $amr_limits;
//		$newevents = amr_process_icalevents($events, $start, $end, $limit);
		$newevents = $events;
		/* should we be moving to destination date  */
		if ((count($newevents) < 1) or (!is_array($newevents)))	return (false);
		$newevents = amr_sort_by_key($newevents , 'EventDate');
		
		$newevents = apply_filters('amr_events_after_sort', $newevents);
		
		if (ICAL_EVENTS_DEBUG) {
			echo '<br>Sorted  '.count($newevents).' events';
			echo '<br>first '.$newevents[0]['EventDate']->format('c');
			echo '<br>last '.$newevents[count($newevents)-1]['EventDate']->format('c');
			echo '<br>Now constrain them...<br> ';
		}
		$constrained = array();
		$count = 0;
		foreach ($newevents as $k => $event) {
//
			if (isset ($event['EventDate']) and (is_object ($event['EventDate']))) {
				if (amr_falls_between($event['EventDate'], $start, $end) OR
					(isset($event['EndDate']) and
						(amr_falls_between($event['EndDate'], $start, $end) OR  /* end date will catch those all day ones that are untimed or those that h ave not yet finished !! */
						(amr_falls_between($start, $event['EventDate'], $event['EndDate'])))) ) /* catch those that start before our start and end after our start */
					{
					$constrained[] = $event;
					if (ICAL_EVENTS_DEBUG) {
						echo '<br>Choosing no: '.$k.' '. $event['EventDate']->format('c').' ending '. (isset($event['EndDate'])? $event['EndDate']->format('c'): ' no end');		}
					++$count;
				}
			}
			else $constrained[] = $event;
			if ($count >= $limit) break;
		}

		if (isset($amr_limits['eventsoffset']) and (!empty($amr_limits['eventsoffset']))) {
			if (function_exists('amr_do_events_offset')) {
				$constrained = amr_do_events_offset ($constrained);
			}
		}
		$constrained = apply_filters('amr_events_after_sort_and_constrain', $constrained);
		return $constrained;
	}
	/* ========================================================================= */
		/*
		 * generate repeating events down to nonrepeating events at the  corresponding repeat time.
		 For ease of processing the repeat arrays will initially be ISO 8601 date (added in PHP 5)  eg:	2004-02-12T15:19:21+00:00
		 we will then convert them to date time objects
		 */
function amr_process_icalevents($events, $astart, $aend, $limit) {
		$dates = array();
		foreach ($events as $i=> $event) {
			amr_derive_dates ($event); /* basic clean up only - removing unnecessary arrays etc */
			$more = amr_generate_repeats($event, $astart, $aend, $limit);
			if (is_array($more)) $dates = array_merge ($dates,$more) ;
		}
		if (ICAL_EVENTS_DEBUG) {echo '<hr>No.Dates= '.count($dates);  } //
		if ((is_array($dates)) and (count($dates) > 1)) { /* must be > 1 for tere to be a duplicate! */
			amr_arrayobj_unique2($dates); /* remove any duplicate in the values , check UID and Seq.  This may arise if we have many VEVENTS relating to one event */
			if (ICAL_EVENTS_DEBUG) { echo '<br>Now have '.count($dates). ' after duplicates check.';}
		}
		return ($dates) ;
	}
/* -------------------------------------------------------------------------*/
function process_icalurl($url) {
global $amr_limits;
/* cache the url if necessary, and then parse it into basic nested structure */
	$file = amr_cache_url(str_ireplace('webcal://', 'http://',$url),$amr_limits['cache']);
	if (!($file)) {
			echo '<br>'.sprintf(__('Unable to load or cache ical calendar %s','amr-ical-events-list'),$url);
			return false;
		}
	$ical = amr_parse_ical($file);
	if (! (is_array($ical) )) {
			echo '<a class="error" href="#" title="'.sprintf(__('Error finding or parsing ical calendar %s','amr-ical-events-list' ),$url).'">!</a>';
			return($ical);
		}
	$ical['icsurl'] = $url;
	return ($ical);
}
/* -------------------------------------------------------------------------*/
function amr_echo_parameters() {
global $amr_limits;
	foreach ($amr_limits as $i=> $v) {
		if (!(in_array($i, array('cache','eventscache','pagination','headings','show_views','calendar_properties','show_month_nav') ))) {
			$label = __($i,'amr-ical-events-list').' = ';
			if (is_object($v)) $t[] = $label.$v->format('j M i:s');
			else if (!empty($v)) $t[] = $label.$v;
		}
	}
	$text = implode (', ',$t);
	return ($text);
}
/* -------------------------------------------------------------------------*/
function amr_string($s) {
/* Maybe check for a calendar name and if it exists, then use it for styling? - NOt NOW  */
	return(str_replace(array (' ','.','-',',','"',"'"), '', $s));
}
/* -------------------------------------------------------------------------*/
function suggest_other_icalplugin($featuretext) {
?>
	<br/><?php echo $featuretext.' - '.__('This feature requires the plugin amr-events','amr-ical-events-list');
	?>&nbsp;<a href="http://icalevents.anmari.com"><?php _e('Get it here','amr-ical-events-list' ); ?></a>
	<br/><?php
	
}
/* -------------------------------------------------------------------------*/
function first_of_month($date) { /* delete */
	$days = (int) $date->format('d');
	if ($days > 1) 	return($date->modify('-'.$days.' days'));
	else return($date);
}
/* -------------------------------------------------------------------------*/
function amr_get_events_cache_key ($criteria) {
	global $amr_limits;
	$string = '';
	$keys = array_merge($amr_limits,$criteria);
	if (isset($keys['headings'])) unset($keys['headings']);
	if (isset($keys['pagination'])) unset($keys['pagination']);
	if (isset($keys['cache'])) unset($keys['cache']);
	if (isset($keys['eventscache'])) unset($keys['eventscache']);
	foreach ($keys as $i => $v) {
		if (!empty($v)) {
			if (is_object($v))
				$string .= ' '.$i.'='.$v->format('Ymd:H');
			else {
				if (is_array($v)) $string .= ' '.$i.'='.implode($v);
				else $string .= ' '.$i.'='.$v;
			}
		}
	}

	if (isset($_REQUEST['debug'])) echo '<br /><b>string for key of cache = <br/>'.$string.'</b>';
	$key = md5( $string );
	return ($key);
}
/* -------------------------------------------------------------------------*/
function amr_get_cached_events_from_db($criteria) {
	global $amr_limits;

	if (version_compare(5.3, PHP_VERSION, '>')) {
		if (isset($_REQUEST['debug']))echo '<b>NB: No event transient caching possible yet  because objects do not serialise properly in php < 5.3</b>';
		return(false);
	}
	else return (false) ; /* until we upgrade ourselves and can test properly  - icdsoft do not have yet */

	if (isset($_REQUEST['debug']))echo '<h3>Trying cache = </h3>';
//---- build the key
	$key = amr_get_events_cache_key($criteria);
// ----now see if we have a cache

	$cache = get_transient('amr_events');

	if ( is_array($cache) && isset( $cache[ $key ] ) ) {
		 echo ('<hr>we got an events cache with key '.$key);
//		 var_dump($cache[ $key ]); die('let see');

         return ( $cache[$key] );
	}
	else {
		echo ('<h3>No events cached - requery </h3>');
		return false;
	}
}
/* -------------------------------------------------------------------------*/
function amr_set_cached_events_from_db($criteria, $events) {
	global $amr_options;  /***  nb when we get back to this -when hosts are on 5.3 or for some other reason, fetch in hours eventscache */
	global $amr_limits;
//---- build the key
	$key = amr_get_events_cache_key($criteria);
	if (isset($_REQUEST['debug'])) echo '<h3>Setting cache with key = '.$key.'</h3>';
// ----now see if we have a cache

	$cache = get_transient('amr_events');
	$cache[$key] = $events;
	set_transient('amr_events', $cache, 60*20); /* save transient for 20 mins */

	if (isset($_REQUEST['debug']))  echo ('<h3>cache set  '.$key.'</h3>');
	return true;
}
/* -------------------------------------------------------------------------*/
function amr_process_icalspec($criteria, $start, $end, $no_events, $icalno=0) {
/*  parameters - an array of urls, an array of limits (actually in amr_limits)  */
	global $amr_options,
		$amr_limits,
		$amr_listtype,
		$amrW,
		$amrtotalevents,
		$change_view_allowed,
		$amr_doing_icallist, /* use to prevent the eventinfo shortcode echoing data to the screen when in ical event list mode */
		$amr_one_page_events_cache;  /* if widget and calendar doing same thing */

	$amr_doing_icallist = true;
	if (!empty($amrW)) $w = 'w'; /* so we know if we are in the widget or not */
	else $w = '';
	if (ICAL_EVENTS_DEBUG) {echo '<hr />Doinglist '; var_dump($amr_doing_icallist); }
	
	$key = amr_get_events_cache_key ($criteria);
	if (isset ($amr_one_page_events_cache[$key]))  {
		$icals 		= $amr_one_page_events_cache[$key]['icals'];
		$components = $amr_one_page_events_cache[$key]['components'];
		if (ICAL_EVENTS_DEBUG)   echo ('<h3>Grabbing the one page events cache  '.$key.'</h3>');
		}
	else {
		if (ICAL_EVENTS_DEBUG)   echo ('<h3>No one page events cache  '.$key.', so start afresh</h3>');
		if (ICAL_EVENTS_DEBUG)  { echo ('<hr>Criteria '); var_dump($criteria);}
		if ((isset($amr_limits['eventpoststoo'])) and ($amr_limits['eventpoststoo'])) {
			if (function_exists('amr_ical_from_posts')) {
//				$events = amr_get_cached_events_from_db($criteria);
//				if ($events) {	if (ICAL_EVENTS_DEBUG) echo '<h3>We got some events cached </h3>';}
//				else {
					if (ICAL_EVENTS_DEBUG) echo '<h3>Get events from posts </h3>';
					$events = amr_ical_from_posts($criteria);
					if (ICAL_EVENTS_DEBUG) echo '<br />Got events '.count($events).' from posts <br />';
					//amr_set_cached_events_from_db($criteria, $events);  /*** cannot use till php 5.3 */
//				}
				if (!empty($events)) {
					foreach ($events as $i=>$event) {
						$events[$i] = amr_parse_unparsed($event);
						$event 		= amr_parseRepeats($event);
					}
					$icals = amr_make_ical_from_posts($events, $criteria);
					if (ICAL_EVENTS_DEBUG) { echo '<br>Got calendars from posts :'.count($icals).'<br>'; }
				}
			}
			else {
				if (empty($criteria['urls'])) {
					suggest_other_icalplugin (__('No url entered - did you want events from posts ?','amr-ical-events-list' ));
				}
				//else _e("huh?");
				}
				
		}
		else if (ICAL_EVENTS_DEBUG) echo '<br />Ignore eventsposts ';
	}
	/* ------------------------------  check for urls and do those too, or only */
		$icals2 = array();
		if (!empty($criteria['urls'])) {
			foreach ($criteria['urls'] as $i => $url) {
				$icals2[$i] = process_icalurl($url);
				if (!(is_array($icals2[$i]))) unset ($icals2[$i]);
			}
		}

		if (!empty($icals) ) { if (isset($icals2) ) 
			$icals = array_merge($icals, $icals2 );}
		else 
			if (isset($icals2) ) $icals = $icals2;
	/* -----------------------------------now we have potentially  a bunch of calendars in the ical array, each with properties and items */

		/* Merge then constrain  by options */
		$components = array();  /* all components actually, not just events */
		if (isset ($icals) and is_array($icals)) {	/* if we have some parse data */
			foreach ($icals as $j => $ical) { /* for each  Ics file within an ICal spec*/
				if ((!isset($amr_options[$amr_listtype]['component'])) or (count($amr_options[$amr_listtype]['component']) < 1))
					_e('No ical components requested for display','amr-ical-events-list');
				else {
					foreach ($amr_options[$amr_listtype]['component'] as $i => $c) {  /* for each component type requested */
						if ($c) {		/* If this component was requested, merge the items from Ical items into events */
							if (isset($ical[$i])) {  /* Eg: if we have an array $ical['VEVENT'] etc*/
								foreach ($ical[$i] as $k => $a) { /*  save the compenent type so we can style accordingly */
									$ical[$i][$k]['type'] = $i;
									$ical[$i][$k]['name'] = 'cal'.$j; /* save the name for styling */
								}
								if (!empty($components) ) {$components = array_merge ($components, $ical[$i]);	}
								else $components = $ical[$i];
							}
						}
					}
				}
			}
			If (isset($_REQUEST['debug'])) echo '<br />Got x events '.count($components);
			$components = amr_process_icalevents($components, $start, $end, $no_events);
			$amrtotalevents = count($components);
			$components = amr_constrain_components($components, $start, $end, $no_events);
			If (ICAL_EVENTS_DEBUG) echo '<br />After constrain '.count($components);
		}
		if (!isset ($amr_one_page_events_cache[$key])) {
				If (ICAL_EVENTS_DEBUG) echo '<br />Setting cache with key '.$key;
				$amr_one_page_events_cache[$key]['components'] = $components;
				$amr_one_page_events_cache[$key]['icals'] = $icals;
		}
		if (isset ($icals) and is_array($icals)) {
/* amr here is the main html  code  *** */
			if (isset($amr_limits['calendar_properties']) )
				$do_prop = $amr_limits['calendar_properties'];
			else
				$do_prop = true;
			if ($do_prop) {
				$tid 	= $w.'calprop'.$icalno;
				$class 	= 'vcalendar '.$w.'icalprop '; //20110222
								
				$thecal =  amr_list_properties ($icals, $tid, $class);		/* list the calendar properties if requested */
			}
			else $thecal = '';

			if ((!amr_doing_box_calendar()) 
			and empty($components) 
			and (!empty($amr_options['noeventsmessage']))) {
				$thecal .=  '<a class="noeventsmessage" style="cursor:help;" href="" title="'
				.amr_echo_parameters().'"> '
				.$amr_options['noeventsmessage'].'</a>';
			}
			else {
				$tid 	= $w.'compprop'.$icalno;
				$class 	= 'vcalendar '.$w.'ical';
				$thecal .= amr_list_events($components, $tid, $class, $show_views=true);
				
			}


		}

		else $thecal = '';	/* the urls were not valid or some other error ocurred, for this spec, we have nothing to print */
/* amr  end of core calling code --- */
	return ($thecal);

	}
/* -------------------------------------------------------------------------*/
function amr_get_params ($attributes=array()) {
/*  We are passed the widget or shortcode attributes, 
      check them, get what we can there, then check for passed parameters (form or query string )
     Anything unset we will get from the default settings for that listtype.
     The defaults list type is 1.
     then set the amr_limits (note the calendar options may overwrite these)
*/
	global $amr_limits, /* has days, events, start ? end ?*/
	$amr_listtype,
	$amr_liststyle,
	$amr_options,
	$amr_groupings,
	$amr_formats,
	$amr_globaltz,
	$change_view_allowed,
	$amrW; // indicates if widget
	
	global 	$amr_freq,
		$amr_freq_unit;
		
	$amr_freq['DAILY'] 			= __('Daily', 'amr-ical-events-list');
	$amr_freq['WEEKLY'] 		= __('Weekly', 'amr-ical-events-list');
	$amr_freq['MONTHLY']		= __('Monthly', 'amr-ical-events-list');
	$amr_freq['YEARLY'] 		= __('Yearly', 'amr-ical-events-list');
	$amr_freq['HOURLY'] 		= __('Hourly', 'amr-ical-events-list');
	$amr_freq['RDATE'] 			= __('on certain dates', 'amr-ical-events-list');
	$amr_freq_unit['DAILY'] 	= __('day', 'amr-ical-events-list');
	$amr_freq_unit['WEEKLY'] 	= __('week', 'amr-ical-events-list');
	$amr_freq_unit['MONTHLY']	= __('month', 'amr-ical-events-list');
	$amr_freq_unit['YEARLY'] 	= __('year', 'amr-ical-events-list');
	$amr_freq_unit['HOURLY'] 	= __('hour', 'amr-ical-events-list');

	if (!empty ($amrW) ) //must be empty not isset
		$listtype='4';
	else 
		$listtype='1';
	If (ICAL_EVENTS_DEBUG) {echo '<hr>Shortcode Attributes passed<br />'; var_dump($attributes); }
//
	$amr_options = amr_getset_options();
//
	parse_str($_SERVER['QUERY_STRING'], $queryargs); /* Get anything passed in the query string that will override shortcodes */
	foreach ($queryargs as $i=>$arg) {
		$queryargs[$i] = filter_var($arg, FILTER_SANITIZE_STRING); //Strip tags, 
		}

	If (ICAL_EVENTS_DEBUG) {echo '<hr> Parsed Query String<br />'; var_dump($queryargs); }
	unset($queryargs['page_id']);
	unset($queryargs['debug']);



//
	$defaults = array( /* defaults array for shortcode , - these override limits if they exist in limit, */
	'listtype' => $listtype,
  	'startoffset' => '',
	'hoursoffset' => '',
	'monthsoffset' => '',
	'daysoffset'=> '',
	'start' => '', /* date('Ymd'), */
	'days' => '', 
	'events' => '',  
	'months' => '',      /* if we have months, start at begin of month and ignore days? */
	'hours' => '',
	'tz' => '',
	'eventpoststoo' => '1',
	'agenda' => '',
	'calendar' => '',
	'eventmap' => '0',
	'show_views' => '1',
	'calendar_properties' => '1',
	'pagination' => '1',
	'headings' => '1',
	'ignore_query' => '0',
	'grouping' => ''
	);

	$shortcode_params = shortcode_atts( $defaults, $attributes ) ;  
	//
	if (!empty ($attributes) ) {
		foreach ($attributes as $i => $v) {
			if (is_numeric ($i)) {  // ie if it is a value passed with no name, we expect only urls like that 
				if (substr($v, 0 ,1) == ':') { /* attempt to maintain old filter compatibity */
					$shortcode_params['urls'][$i] = substr ($v, 1);
				}
				$v = (str_ireplace('webcal://', 'http://',$v));
				if ((function_exists('filter_var')) and (!filter_var($v, FILTER_VALIDATE_URL))) { /* rejecting a valid URL on php 5.2.14  */
					echo '<h2>'.sprintf(__('Invalid Ical URL %s','amr-ical-events-list'), $v).'</h2>';
				}
				else 	
					$shortcode_params['urls'][$i] = esc_url($v);
				unset ($attributes[$i]);
				}
			else { /* of it's not a url, it's a query selection criteria */
				if ($i == 'ics') {
					$shortcode_params['urls'][] = $v;
					unset($attributes[$i]);
					}
			}
		} // end foreach 
	} //end if 	

//
	
	If (ICAL_EVENTS_DEBUG) {
		echo '<hr>Shortcode with shortcode defaults added <br />'; var_dump($shortcode_params); 
	}
	/*  get the parameters we want out of the attributes, supplying defaults for anything missing  */
	/* but now we may have missed taxonomies etc as the defaults do not know about them, so get the diff  */
	if (!empty($attributes)) 		$taxo_selection = array_diff($attributes,$shortcode_params);
	// if we had some taxos, marge them into shortcode selection 
	if (!empty($taxo_selection)) 	$shortcode_params = array_merge ($shortcode_params,$taxo_selection );
	
	If (ICAL_EVENTS_DEBUG) {
	echo '<hr>Other selection (taxo? <br />'; if (!empty($taxo_selection)) var_dump($taxo_selection);
	echo '<hr>Shortcode atts with taxo <br />'; var_dump($shortcode_params); 
	}

	$allow_query = (empty($shortcode_params['ignore_query']) or !($shortcode_params['ignore_query']) ); 
	unset ($shortcode_params['ignore_query']);

	if ($allow_query) {
		$shortcode_params = array_merge ($shortcode_params, $queryargs);
	/* If the input arrays have the same string keys, then the later value for that key will overwrite the previous one. 
	If, however, the arrays contain numeric keys, the later value will not overwrite the original value, but will be appended. 	
	*/
		if (ICAL_EVENTS_DEBUG) {echo '<hr>After merge with query params<br />'; var_dump($shortcode_params); }
	}	
	else if (ICAL_EVENTS_DEBUG) {echo '<hr>Ignoring query parameters except for essentials for navigation ';}
//
	// save the global list type first
	if (!empty($shortcode_params['listtype'])) 		
		$amr_listtype = $shortcode_params['listtype'];
	else 
		if (!(isset($amr_listtype)))	$amr_listtype = $listtype;
		
	$amr_liststyle = (isset ($amr_options[$amr_listtype]['general']['ListHTMLStyle']))
			? $amr_options[$amr_listtype]['general']['ListHTMLStyle']
			: $amr_liststyle = 'table';//; 'list'
			
	if (in_array ($amr_liststyle, array('smallcalendar','largecalendar'))) {  
		// then query args overeride even if we have ignore query 
		if (!empty($queryargs['start']))  $shortcode_params['start'] = abs ((int) $queryargs['start']);
		if (!empty($queryargs['months'])) $shortcode_params['months'] = abs( (int) $queryargs['months']);
		if (!empty($queryargs['tz'])) 
			$shortcode_params['tz'] = filter_var($queryargs['tz'],FILTER_SANITIZE_STRING);
	}

	unset($queryargs['listtype']);
	unset($shortcode_params['listtype']);
// now check groupings 	
	$gg = '';
	if (!empty($shortcode_params['grouping'])) $gg = ucwords($shortcode_params['grouping']);
	if (!empty($_REQUEST['grouping'])) $gg = ucwords($_REQUEST['grouping']);
	if (array_key_exists($gg,$amr_groupings)) { // if it is a valid grouping
		unset($amr_options[$amr_listtype]['grouping']);
		$amr_options[$amr_listtype]['grouping'][$gg] = true;
		unset ($shortcode_params['grouping']);
	}		
	
//	unset($attributes['listtype']);
	if ($change_view_allowed) { // we will NOT ignore these query parameters as that  will break our navigation 
		if (isset($_GET['calendar'])) $amr_listtype = (int) $_GET['calendar'];
		if (isset($_GET['agenda']))   $amr_listtype = (int) $_GET['agenda'];
		if (isset($_GET['eventmap'])) $amr_listtype = 'eventmap';
	}

	// then get the limits for that list type
	$amr_limits = $amr_options[$amr_listtype]['limit']; /* get the default limits */
	
	If (ICAL_EVENTS_DEBUG) {echo '<hr>Defaults limits before we change them<br />'; var_dump($amr_limits ); }
	
	if (!empty ($shortcode_params ) ) {
		foreach ($shortcode_params as $i => $v) { // must be atts not limits as atts may hold more then limits - limits just has initial limits
		if (in_array($i, array(
			'headings',
			'show_views',
			'agenda',
			'eventmap', 
			'calendar', 
			'eventpoststoo',
			'pagination', 
			'calendar_properties',
			'show_month_nav')	)) {
			$amr_limits[$i] = abs ((int) $v);
			unset($shortcode_params[$i]);  // only unset if empty  what??? - else will lose others
		}
		else {			
			if  (in_array($i, array('days','events','months','listtype','hours'
				,'daysoffset', 'startoffset', 'hoursoffset','monthsoffset'))) {
				if (!empty($v)) $amr_limits[$i] = (int)$v ;  // can be negative, so no abs
				unset($shortcode_params[$i]);
				}
			}
		}
	}
 // else might be a taxonomy or categeory etc  ... pass through - not if we have filtered string
/* ----check if we want to overwrite the wordpress timezone */
	if (!(empty($shortcode_params['tz'])))
		$amr_globaltz = timezone_open ($shortcode_params['tz']);
		

	unset ($shortcode_params['tz']);	//clear it out so we do not pass to wp query 
	
	If (isset($_REQUEST['tzdebug'])) {
	
		echo '<h4>Plugin Timezone:'.timezone_name_get($amr_globaltz);
		echo ', current offset is '.$amr_globaltz->getOffset(date_create('now',$amr_globaltz))/(60*60).'</h4>';
		}
//-------------------------------
	$pos_int_options = array("options"=> array("min_range"=>1, "max_range"=>1000));
	$neg_int_options = array("options"=> array("min_range"=>-1000, "max_range"=>1000));
	/* check non url parameters  */
	
	if (empty ($shortcode_params['start'])) {
		$amr_limits['start'] = date_create('now',$amr_globaltz);	
	}
	else {
		$amr_limits['start'] = abs((int) $shortcode_params['start']);		
	}
	unset ($shortcode_params['start']); // clear it out so we do not pass it to wp query

// work out whether we are doing a calendar or not.  calendars must still respond to at least the month navigation	
		
	foreach ($amr_limits as $i => $a) {
		if (($i === 'start') or ($i === 'end')) {
			if (!(is_object($a))) {
				if (checkdate(substr($a,4,2), /* month */
						substr($a,6,2), /* day*/
						substr($a,0,4)) /* year */ )
						$amr_limits[$i] = date_create($a,$amr_globaltz);
				else {
					_e('Invalid Start Date', 'amr-ical-events-list');
					$amr_limits[$i] = date_create('now',$amr_globaltz);
					}
			}
		 /*  else all is okay - we have default date of now */
		}
	}



	If (ICAL_EVENTS_DEBUG) {echo '<hr> Check limits for listtype:'.$amr_listtype.' <br />'; var_dump($amr_limits); }

	if (!empty( $shortcode_params['daysoffset'])) { /* keeping startoffset for old version compatibility, but allowing for daysoffset for compatibility with other offsets */
		
		$amr_limits['startoffset'] = (int) $shortcode_params['daysoffset']; // can be negative
	}
/* ---- check for urls that are either passed by query or form, or are in the shortcode with a number or not ie: not ics =  */


	//set up global for easier access 
	$amr_formats = $amr_options[$amr_listtype]['format'];
// now adjust our start dta if necessary 
	if (!empty($amr_limits['startoffset'])) {
		$daysoffset = (int)($amr_limits['startoffset']);
		if ($daysoffset > 0) $daysoffset = '+'.(string)$daysoffset.' days';
		else $daysoffset = (string)$daysoffset.' days';
		date_modify($amr_limits['start'],$daysoffset) ;
	}
//
	if (!empty($amr_limits['hoursoffset'])) {
		$hrsoffset = (int)($amr_limits['hoursoffset']);
		if ($hrsoffset > 0) $hrsoffset = '+'.(string)$hrsoffset.' hours';
		else $hrsoffset = (string)$hrsoffset.' hours';
		date_modify($amr_limits['start'],$hrsoffset) ; /*** as per request from jd  */
	}
//
	if (!empty($amr_limits['monthsoffset'])) {
		$mthsoffset = (int)($amr_limits['monthsoffset']);
		if ($mthsoffset >= 0) $mthsoffset = '+'.(string)$mthsoffset.' months';
		else $mthsoffset = (string)$mthsoffset.' months';
		date_modify($amr_limits['start'],$mthsoffset) ;
	}
//
	if (!empty($amr_limits['hours'])) { /* then set the time to the beginning of the day , and get rid of months and days */
		date_time_set ($amr_limits['start'],$amr_limits['start']->format('G'),0,0);	/* set the time to beginning of the hour */
		unset ($amr_limits['days']);
		unset ($amr_limits['months']);
	}
	if (!empty($amr_limits['months'])) {/* then set the date to the beginning of the month and get rid of days */
			$days = (int) $amr_limits['start']->format('d');
			if ($days > 1) 	$amr_limits['start']->modify('-'.($days-1).' days');
			date_time_set ($amr_limits['start'],0,0,0);
			unset ($amr_limits['days']);
		}
//
	if (empty($amr_limits['hours'])) {/* else use the days and  then set the time to the beginning of the day */
		date_time_set ($amr_limits['start'],0,0,0);
	}
//	now find our end date  - may get overridden if calendar
	$amr_limits['end'] = new DateTime();
	$amr_limits['end'] = clone ($amr_limits['start']);
	
	if (!empty($amr_limits['months']))
		date_modify($amr_limits['end'],'+'.($amr_limits['months']).' months') ;
	if (!empty($amr_limits['days']))
		date_modify($amr_limits['end'],'+'.($amr_limits['days']).' days') ;
	if (!empty($amr_limits['hours'])) 
		date_modify($amr_limits['end'],'+'.($amr_limits['hours']).' hours') ;
	else  date_modify($amr_limits['end'],'-1 second') ; /* so that we do not include events starting in the next time period */

//	date_time_set ($amr_limits['end'],23,59,59);  // if we have this, do we need the -1 second below?
	If (ICAL_EVENTS_DEBUG) {
		echo '<hr> Before passing others <br />'; 
		var_dump($shortcode_params); 
		echo amr_echo_parameters();
	}

	
	If (ICAL_EVENTS_DEBUG) {echo '<hr> Left in params to pass to wp query?: <br />'; var_dump($shortcode_params); }
	return ($shortcode_params);  // only return params needed for wp query ? 
}
/* -------------------------------------------------------------------------*/
function amr_do_ical_shortcode ($atts, $content = null) {
global $amr_limits,
	$change_view_allowed,
	$amr_ical_am_doing,
	$amr_icalno;/* used to give each ical  table a unique id on a page or post */
// This is the main function.  It replaces [iCal URL]'s with events. Each as a separate list
/* Allow multiple urls and only one listtype */
/*  merge atts with this array, so we will have a default list */

	$change_view_allowed = true;
	$amr_ical_am_doing = 'list';
	if (!isset($atts['listtype'])) $atts['listtype'] = '1';

	$criteria =	amr_get_params ($atts);  /* strip out and set any other attstributes  - they will set the limits table */

	/* separate out the other possible variables like list type, then just have the urls */
	if (!(isset($amr_icalno))) $amr_icalno = 0;
	else $amr_icalno= $amr_icalno + 1;

	$content = amr_process_icalspec(
		$criteria, 
		$amr_limits['start'], 
		$amr_limits['end'], 
		$amr_limits['events'], 
		$amr_icalno);
  return ($content);

}
/* -------------------------------------------------------------------------*/
function amr_do_smallcal_shortcode ($atts, $content = null) {
global $amr_limits,
 $amr_listtype, 
 $change_view_allowed,
 $amr_ical_am_doing,
 $amr_icalno;/* used to give each ical  table a unique id on a page or post */

// treat as widget anyway to avoid view changes etc
// This is the main function.  It replaces [iCal URL]'s with events. Each as a separate list   if no listtype set it to 8
/* Allow multiple urls and only one listtype */
/*  merge atts with this array, so we will have a default list */
	$change_view_allowed = false;
	$amr_ical_am_doing = 'smallcalendar';
//	if (isset($_REQUEST['days'])) return ('');  // don't show if only doing days
	if (!isset($atts['listtype'])) $atts['listtype'] = $amr_listtype = '8';
//	if (!isset($atts['months']))
	$atts['months'] = 1;
	$criteria =	amr_get_params ($atts);  /* strip out and set any other attstributes  - they will set the limits table */
		// remove the custom post type parameter as it will cause us to lose all others and just show 1 event

	/* separate out the other possible variables like list type, then just have the urls */
	if (!(isset($amr_icalno))) $amr_icalno = 0;
	else $amr_icalno= $amr_icalno + 1;

	$content = amr_process_icalspec(
		$criteria, 
		$amr_limits['start'], 
		$amr_limits['end'], 
		$amr_limits['events'], 
		$amr_icalno);
	$change_view_allowed = true;
  return ('<div class="event-calendar">'.$content.'</div>');

}
/* -------------------------------------------------------------------------*/
function amr_do_largecal_shortcode ($atts, $content = null) {
global $amr_limits,
 $amr_listtype, 
 $change_view_allowed,
 $amr_ical_am_doing,
 $amr_icalno;/* used to give each ical  table a unique id on a page or post */
// This is the main function.  It replaces [iCal URL]'s with events. Each as a separate list   if no listtype set it to 8
/* Allow multiple urls and only one listtype */
/*  merge atts with this array, so we will have a default list */
	
	$change_view_allowed = true;
	$amr_ical_am_doing = 'largecalendar';
//	if (isset($_REQUEST['days'])) return ('');  // don't show if only doing days

	if (!isset($atts['listtype'])) $atts['listtype'] = $amr_listtype = '9';

//	if (!isset($atts['months']))
	$atts['months'] = 1;
	$criteria =	amr_get_params ($atts);  /* strip out and set any other attstributes  - they will set the limits table */

	/* separate out the other possible variables like list type, then just have the urls */
	if (!(isset($amr_icalno))) $amr_icalno = 0;
	else $amr_icalno= $amr_icalno + 1;

	$content = amr_process_icalspec($criteria, $amr_limits['start'], $amr_limits['end'], $amr_limits['events'], $amr_icalno);
  return ('<div class="event-calendar">'.$content.'</div>');

}

/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_load_text') ) {
	function amr_load_text($domain, $location) {
	
	if (ICAL_EVENTS_DEBUG) error_log( 'Want to load language ');
		$locale = get_locale(); 
		$mofile = WP_CONTENT_DIR.'/languages/'. $domain . '-' . $locale . '.mo';  //teehee - allows local own  domain file
		if (!load_textdomain($domain,  $mofile)) {//allow first a custom lang location
			if (ICAL_EVENTS_DEBUG) error_log( 'No custom language file');
	
			$result = load_plugin_textdomain($domain, false/* nlr */, $location);
			if (ICAL_EVENTS_DEBUG) error_log( 'Result of trying to load language: '.$result);
		}	
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_load_text() {

	amr_load_text('amr-ical-events-list','amr-ical-events-list/lang');	//as per code styling plugin
}
/* ----------------------------------------------------------------------------------- */
function amrical_add_options_panel() {

	global $wp_version,
			$current_user,
			$events_menu_added;

	/* add the options page at admin level of access */
		$menu_title = $page_title = __('iCal Events List', 'amr-ical-events-list');

		$parent_slug =  'amr-events';
		$function = 'amrical_option_page';
		$capability = 'manage_event_settings';
		$menu_slug = 'manage_amr_ical';

		if (function_exists('amr_events_settings_menu')) {
			$menu_title = $page_title = __('Listing Events', 'amr-ical-events-list');
			if (empty($events_menu_added) or (!$events_menu_added)) {
				amr_events_settings_menu();
				$events_menu_added = true;
				}
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);			
		}

		else {
			add_menu_page($page_title, $menu_title , 'manage_options', $menu_slug, $function);
			$parent_slug = $menu_slug;
//		$page = add_options_page($page_title, $menu_title , 'manage_options', $menu_slug, $function);


		}
		$function = 'amrical_listing_options_page';
		$menu_slug = 'manage_event_listing';
		$page_title = __('iCal Events Lists', 'amr-ical-events-list');
		$menu_title = __('List types', 'amr-ical-events-list');
		add_submenu_page( $parent_slug, $page_title, $menu_title,'manage_options', $menu_slug, $function);		

}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_widget_init() {
	register_widget('amr_ical_widget');
	register_widget('amr_icalendar_widget');
}
/* ------------------------------------------------------------------------------------------------ */
 function amrical_add_scripts() {

 	wp_enqueue_script('jquery');

	wp_enqueue_script('jquery-ui-core');

}
/* ------------------------------------------------------------------------------------------------ */
 function amrical_add_adminstyle() {
	if ((stristr ($_SERVER['QUERY_STRING'],'manage_amr_ical')) 
	or (stristr ($_SERVER['QUERY_STRING'],'manage_event_listing')))   {

		$myStyleUrl = ICALLISTPLUGINURL.'css/icaladmin.css';

		$myStyleFile = ICALLISTPLUGINDIR.'css/icaladmin.css';

		if ( file_exists($myStyleFile) ) {

            wp_register_style('amricaladmin', $myStyleUrl);

            wp_enqueue_style( 'amricaladmin');

        }
	}

}
/* ------------------------------------------------------------------------------------------------------ */
function amr_ical_exception_handler($exception) {

  echo __("Uncaught exception: ", 'amr-ical-events-list') , $exception->getMessage(), "\n";
  _e('<br /><br />An error in the input data may prevent correct display of this page.  Please advise the administrator as soon as possible.', 'amr-ical-events-list');

}
/* ------------------------------------------------------------------------------------------------------ */
	/**
	Adds a links directly to the settings page from the plugin page
	*/
	function amr_plugin_links($links, $file) {
		if ( $file == 'amr-ical-events-list/amr-ical-events-list.php' ) {
			$links[] = '<a title="'
			. __('General listing Settings', 'amr-ical-events-list')
			.' href="admin.php?page=manage_amr_ical" >' 
			. __('Settings', 'amr-ical-events-list') . "</a>";
			$links[] = "<a href='admin.php?page=manage_event_listings'>" . __('List Type Settings', 'amr-ical-events-list') . "</a>";
			$links[] = "<a href='http://icalevents.anmari.com/documentation/'><b>" . __('Documentation', 'amr-ical-events-list') . "</b></a>";
		}
		return $links;
	} // end plugin_links()
/* ------------------------------------------------------------------------------------------------------ */
	set_exception_handler('amr_ical_exception_handler');

	if (is_admin() )	{

		add_action('admin_init'         , 'amrical_add_adminstyle');
		add_action('admin_menu'         , 'amrical_add_options_panel');
		add_action('admin_print_scripts', 'amrical_add_scripts');
	}
	else // add_action('wp_head'        ,  'amr_ical_events_style');
		add_action('wp_print_styles'    , 'amr_ical_events_style');
// add_action('plugins_loaded'          , 'amr_ical_widget_init');
	add_action('widgets_init'           , 'amr_ical_widget_init');
//	$plugin_dir = basename(dirname(__FILE__));
//	load_plugin_textdomain( 'amr-ical-events-list', null, $plugin_dir );

	amr_ical_load_text();

	add_action('plugins_loaded'         , 'amr_ical_load_text' );
	add_action( 'init'             		, 'amr_ical_load_text' );
	add_shortcode('iCal'                , 'amr_do_ical_shortcode');
	add_shortcode('ical'                , 'amr_do_ical_shortcode'); // in case people make mistake
	add_shortcode('smallcalendar'       , 'amr_do_smallcal_shortcode');
	add_shortcode('largecalendar'       , 'amr_do_largecal_shortcode');
	register_activation_hook(__FILE__, 'amr_ical_events_list_record_version');
	add_filter('plugin_row_meta', 'amr_plugin_links', 10, 2);
?>