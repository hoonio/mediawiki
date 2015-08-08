<?php
/*
 * Google Adsense Mediawiki extension
 * @Version 3.0
 * @Author Paul Y. Gu, <gu.paul@gmail.com>
 * @Copyright paulgu.com 2006 - http://paulgu.com/
 * @License: GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * @Usage:
	<google uid="C07" position="right"></google>
	<google uid="C01" position="none"></google>
	<google uid="C06" position="left"></google>
	<google uid="S01" position="right"></google>
	<google uid="S02" position="left"></google>
	<google uid="V01" position="left"></google>
	<google uid="V02" position="none"></google>
	<google uid="V03" position="right"></google>


 * To activate the extension, include it from your LocalSettings.php
 * with: include("extensions/GoogleAdSense/GoogleAdSense.php");
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

if (!defined('MEDIAWIKI')) die('Not an entry point.');

//===================================================================
// Update to your Adsense Publisher ID and Unit IDs
//===================================================================

// Your Adsense Publisher ID
// ------------------------------
// This is located at Top Right corner under your adsense account
$PUBLISHER_ID = "pub-5182625953902291";

// Your Adsense Unit IDs
// ------------------------------
// These are under AdSense Setup --> Manage Ads
// If you don't have them, click on Get Ads to create them
// C01, C02 ... are content ads unit ID
// S01, S02 ... are Google Custom Search unit string, which is inside 
// the generated adsense code, it's located at the end of Publisher ID
// V01, V02 ... are Video unit string, which is inside 
// the generated code, it's located at the end of "watch_custom_player?id="
//


$ADSENSE_UNITS = array(
	// Text and image ads - 728x90
	'C01' => array('unitID' => '4233781121', 'width' => '728', 'height' => '90', 'position' => 'none'),
	// Text and image ads - 728x90
	'C02' => array('unitID' => '3525962162', 'width' => '728', 'height' => '90', 'position' => 'none'),
	// Text and image ads - 728x90
	'C03' => array('unitID' => 'xxx', 'width' => '728', 'height' => '90', 'position' => 'none'),
	// Text ads - 728x90
	'C04' => array('unitID' => 'xxx', 'width' => '728', 'height' => '90', 'position' => 'none'),
	// Ad links - 728x90
	'C05' => array('unitID' => 'xxx', 'width' => '728', 'height' => '90', 'position' => 'none'),
	// Text and image ads - 234x60
	'C06' => array('unitID' => 'xxx', 'width' => '234', 'height' => '60', 'position' => 'none'),
	// Text and image ads - 120x600
	'C07' => array('unitID' => 'xxx', 'width' => '120', 'height' => '600', 'position' => 'none'),
	// Search your domain - 400x29
	'S01' => array('unitID' => 'xxx', 'width' => '400', 'height' => '29', 'position' => 'none'),
	// Search web - 400x29
	'S02' => array('unitID' => 'xxx', 'width' => '400', 'height' => '29', 'position' => 'none'),
	// Video Unit - Auto - 488x485
	'V01' => array('unitID' => 'xxx', 'width' => '488', 'height' => '485', 'position' => 'none'),
	// Video Unit - Entertainment - 488x485
	'V02' => array('unitID' => 'xxx', 'width' => '488', 'height' => '485', 'position' => 'none'),
	// Video Unit - Sports - 488x485
	'V03' => array('unitID' => 'xxx', 'width' => '488', 'height' => '485', 'position' => 'none'),
);

$ADSENSE_UNIT =''; // initialize
//===================================================================
// End of Adsense Setup
//===================================================================


// ******************************************************************
//
// Do NOT need to edit anything below
//
// ******************************************************************

$wgExtensionCredits['other'][] = array (
	'name' => "GoogleAdSense",
	'version' => "3.0, March 28, 2009",
	'author' => 'Paul Gu',
	'url' => 'http://paulgu.com',
	'description' => 'Google Adsense extension'
	);

$wgExtensionFunctions[] = "fnGoogleAdsenseExtension";

function fnGoogleAdsenseExtension() {
	global $wgParser;
	$wgParser->setHook( "google", "populategoogleadsense" );
}

# The callback function for HTML output
function populategoogleadsense( $input, $argv, &$parser ) {
	global $wgScriptPath;
	global $PUBLISHER_ID;
	global $ADSENSE_UNITS;
	global $ADSENSE_UNIT;

	$GoogleAdSensePath = "{$wgScriptPath}/extensions/GoogleAdSense";
	//output the necessary styles
	$output .= '<style type="text/css">';
	$output .= '   @import "' . $GoogleAdSensePath . '/GoogleAdSense.css";';
	$output .= '</style>';

	if ($argv["uid"] != "")
		$ADSENSE_UNIT = $argv["uid"];

	if(array_key_exists($ADSENSE_UNIT, $ADSENSE_UNITS)) {

		// overwrite default value
		if ($argv["width"] != "")
			$ADSENSE_UNITS[$ADSENSE_UNIT]['width'] = $argv["width"];

		// overwrite default value
		if ($argv["height"] != "")
			$ADSENSE_UNITS[$ADSENSE_UNIT]['height'] = $argv["height"];
		
		// overwrite default value
		if ($argv["position"] != "")
			$ADSENSE_UNITS[$ADSENSE_UNIT]['position'] = $argv["position"];


		if( $ADSENSE_UNIT[0] == 'C' ) {
			$output .= gAdSenseForContent();
		} else if( $ADSENSE_UNIT[0] == 'S' ) {
			$output .= gAdSenseForCustomSearch();
		} else if( $ADSENSE_UNIT[0] == 'V' ) {
			$output .= gAdSenseForYouTube();
		} else {
			$output .= gAdSenseForSearch($input); // default is Google search
		}
	} else {
		//display error message
		$output .= '<div class="googleAdSenseError">';
		$output .= 'WARNING: Google AdSense Unit ID "'. $ADSENSE_UNIT .'" does not exist.<br />';
		$output .= 'Available Unit IDs: ';
		foreach ($ADSENSE_UNITS as $key => $item) {
			$output .= $key . ', ';
		}
		$output  = substr($output, 0, -2) . "." . '<br />';
		$output .= 'Example: &lt;google uid="C01"&gt;&lt;&#47;google&gt;, &lt;google uid="C01" position="left" &gt;&lt;&#47;google&gt;';
		$output .= '</div>';
	}

	//clean up
	$ADSENSE_UNIT ='';

	return $output;
}

#
# The function of AdSense for Content
#
function  gAdSenseForContent() {
	global $PUBLISHER_ID;
	global $ADSENSE_UNITS;
	global $ADSENSE_UNIT;

	$rc  = '';
	$rc .= '<div class="googleAdSenseContent" style="float:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['position'] .'; width:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['width'] .'px; height:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['height'] .'px;"><script type="text/javascript">';
	$rc .= 'google_ad_client = "'.$PUBLISHER_ID.'";';
	$rc .= 'google_ad_slot = "'. $ADSENSE_UNITS[$ADSENSE_UNIT]['unitID'] .'";';
	$rc .= 'google_ad_width = '. $ADSENSE_UNITS[$ADSENSE_UNIT]['width'] .';';
	$rc .= 'google_ad_height = '. $ADSENSE_UNITS[$ADSENSE_UNIT]['height'] .';';
	$rc .= '</script>';
	$rc .= '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">';
	$rc .= '</script></div><div style="clear: both;"></div>';

	return $rc;
}

#
# The function of AdSense for Custom Search
#
function  gAdSenseForCustomSearch() {
	global $PUBLISHER_ID;
	global $ADSENSE_UNITS;
	global $ADSENSE_UNIT;

	$rc  = '';
	$rc .= '<div class="googleSearch" style="float:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['position'] .'; width:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['width'] .'px; height:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['height'] .'px;">';
	$rc .= '<style type="text/css">';
	$rc .= '@import url(http://www.google.com/cse/api/branding.css);';
	$rc .= '</style>';
	$rc .= '<div class="cse-branding-right" style="background-color:#999999;color:#000000">';
	$rc .= '  <div class="cse-branding-form">';
	$rc .= '    <form action="http://www.google.com/cse" id="cse-search-box" target="_blank">';
	$rc .= '      <div>';
	$rc .= '        <input type="hidden" name="cx" value="partner-'. $PUBLISHER_ID. ':'. $ADSENSE_UNITS[$ADSENSE_UNIT]['unitID'] .'" />';
	$rc .= '        <input type="hidden" name="ie" value="ISO-8859-1" />';
	$rc .= '        <input type="text" name="q" size="31" />';
	$rc .= '        <input type="submit" name="sa" value="Search" />';
	$rc .= '      </div>';
	$rc .= '    </form>';
	$rc .= '  </div>';
	$rc .= '  <div class="cse-branding-logo">';
	$rc .= '    <img src="http://www.google.com/images/poweredby_transparent/poweredby_999999.gif" alt="Google" />';
	$rc .= '  </div>';
	$rc .= '  <div class="cse-branding-text">';
	$rc .= '    Custom Search';
	$rc .= '  </div>';
	$rc .= '</div>';
	$rc .= '</div><div style="clear: both;"></div>';
	return $rc;
}

#
# The function of AdSense for Search
#
function  gAdSenseForSearch($input) {
	global $PUBLISHER_ID;
	global $ADSENSE_UNITS;
	global $ADSENSE_UNIT;

	$rc  = '';
	$rc .= '<div class="googleSearch" style="float:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['position'] .'; width:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['width'] .'px; height:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['height'] .'px;">';
	$rc .= '<form method="get" action="http://www.google.com/custom" target="_blank">';
	$rc .= '  <table bgcolor="#ffffff">';
	$rc .= '    <tr><td nowrap="nowrap" valign="top" align="left" height="32">';
	$rc .= '      <a href="http://www.google.com/">';
	$rc .= '      <img src="http://www.google.com/logos/Logo_25wht.gif" border="0" alt="Google" align="middle"></img></a>';
	$rc .= '      <label for="sbi" style="display: none">Enter your search terms</label>';
	$rc .= '      <input type="text" name="q" size="31" maxlength="255" value="'.$input.'" id="sbi"></input>';
	$rc .= '      <label for="sbb" style="display: none">Submit search form</label>';
	$rc .= '      <input type="submit" name="sa" value="Search" id="sbb"></input>';
	$rc .= '      <input type="hidden" name="client" value="'.$PUBLISHER_ID.'"></input>';
	$rc .= '      <input type="hidden" name="forid" value="1"></input>';
	$rc .= '      <input type="hidden" name="ie" value="ISO-8859-1"></input>';
	$rc .= '      <input type="hidden" name="oe" value="ISO-8859-1"></input>';
	$rc .= '      <input type="hidden" name="cof" value="GALT:#008000;GL:1;DIV:#336699;VLC:663399;AH:center;BGC:FFFFFF;LBGC:336699;ALC:0000FF;LC:0000FF;T:000000;GFNT:0000FF;GIMP:0000FF;FORID:1"></input>';
	$rc .= '      <input type="hidden" name="hl" value="en"></input>';
	$rc .= '    </td></tr></table>';
	$rc .= '</form>';
	$rc .= '</div><div style="clear: both;"></div>';

	return $rc;
}

#
# The function of Google Adsense YouTube
#
function  gAdSenseForYouTube() {
	global $ADSENSE_UNITS;
	global $ADSENSE_UNIT;

	$rc  = '';
	$rc .= '<div class="googleAdSenseVideo" style="float:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['position'] .'; width:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['width'] .'px; height:'. $ADSENSE_UNITS[$ADSENSE_UNIT]['height'] .'px;">';
	$rc .= '<div id="vu_ytplayer_'. $ADSENSE_UNITS[$ADSENSE_UNIT]['unitID'] .'"><a href="http://www.youtube.com/browse">Watch the latest videos on YouTube.com</a></div><script type="text/javascript" src="http://www.youtube.com/watch_custom_player?id='.$ADSENSE_UNITS[$ADSENSE_UNIT]['unitID'].'"></script>';
	$rc .= '</div><div style="clear: both;"></div>';

	return $rc;
}
?>
