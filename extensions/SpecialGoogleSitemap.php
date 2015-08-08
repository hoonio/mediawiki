<?php
#
# Special:GoogleSitemap MediaWiki extension
# Version 0.0.5
#
# Copyright © 2006 François Boutines-Vignard.
#
# A special page to generate Google Sitemap XML files.
# see http://www.google.com/schemas/sitemap/0.84/sitemap.xsd for details.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html
#
# Revisions:
# 0.0.2: date format correction, lighter markup. (2006/09/15)
# 0.0.3: added 'priority' and 'changefreq' tags management in the 'Options' form. (2006/09/16)
# 0.0.4: Unicode support, gmdate format, exponential and quadratic priorities. (2006/09/17)
# 0.0.5: Possibility to sort by last page revision. (2006/09/19)
 
require_once("QueryPage.php");
 
/**
* 'GoogleSitemapPage' class
*
* The XML file is ordered by decreasing popularity order (ie. maximum number of hits).
* User should have the 'bureaucrat' rights.
* Ignores MediaWiki (and MediaWiki talk) namespace.
* Redirect pages are ignored.
* Accepts 'limit' and 'offset' parameters,
*       eg: Special:GoogleSitemap&limit=5000 to build a file of the 5000 first pages.
*/
class GoogleSitemapPage extends QueryPage {
        var $file_name = "sitemap.xml"; // relative to $wgSitename (must be writable)
 
        var $sitemaps_url = "https://www.google.com/webmasters/sitemaps/login";        
 
        /*
         * see http://www.google.com/schemas/sitemap/0.84/sitemap.xsd for more details
         */
        var $DEFAULT_SITEMAP_HEADER = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n\n<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\"\n\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n\txsi:schemaLocation=\"http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd\">\n\n";
        var $DEFAULT_PRIORITY = 0.5;
        var $DEFAULT_CHANGE_FREQ = "daily";
 
        var $file_handle;
        var $file_exists;
 
        var $offset,$limit;
        var $count, $cursor_pos = 0;
 
        var $form_action;
        var $article_priorities = "constant";
        var $estimate_change_freq = false;
	var $sorting_criterion = "POP";
 
        function GoogleSitemapPage( $offset, $limit ) {
                global $wgRequest;
                $request =& $wgRequest;
 
                $file_name = $request->getText( 'wpFileName' );
 
                if( $file_name ) {
                        $this->file_name = $file_name;
                }
 
                $change_freq = $request->getCheck( 'wpChangeFreq' );
 
                if( $change_freq ) {
                        $this->estimate_change_freq = $change_freq ;
                }
 
                $priority = $request->getText( 'wpPriorityType' );
 
                if( $priority ) {
                        $this->article_priorities = $priority;
                }
 
		$sorting_criterion = $request->getText( 'wpSortCriterion' );
 
		if( $sorting_criterion ) {
			$this->sorting_criterion = $sorting_criterion;
		}
 
                $this->offset = $offset;
                $this->limit = $limit;
        }
 
        function utf8_write( $handle, $data ) {
                fwrite( $handle, utf8_encode( $data ) ) ;
        }
 
        function getName() {
                return "GoogleSitemap";
        }
 
        function isExpensive() {
                return false;
        }
 
        function isSyndicated() {
                return false;
        }
 
        function initialize() {
                global $wgExtensionCredits;
 
                $this->file_exists = file_exists ( $this->file_name ) ;
 
                $this->file_handle = fopen( $this->file_name, 'w' ) or die( "Cannot write to '$this->file_name.'" );
 
                $this->utf8_write( $this->file_handle, $this->DEFAULT_SITEMAP_HEADER ); 
 
                $this->doQuery( $this->offset, $this->limit );                         
        }
 
        function finalize() {
                $close_tag = "\n</urlset>";
                $this->utf8_write( $this->file_handle, $close_tag ) ;
 
                fclose( $this->file_handle );
        }
 
        function getPageHeader() { // has text
                global $wgServer, $wgScriptPath, $wgSitename;
 
                $url = "$wgServer$wgScriptPath/$this->file_name";                    
 
                $misc_estimate = $this->estimate_change_freq?" and estimated change frequencies":"";
                $misc_file_action = $this->file_exists?"rebuild":"created";
 
                $default_text="The <a href=\"$this->sitemaps_url\" title=\"Sitemaps login\"><em>Google Sitemap's</em></a> <strong><a href=\"$url\" title=\"$wgSitename Sitemap\">$url</a></strong> was $misc_file_action for the following <strong>$this->count</strong> pages <small><em>(with $this->article_priorities priority$misc_estimate)</em></small>.<br />\n"; #English
 
                $info="";
 
                if( $this->offset != 0 ) {
                        $class="errorbox";
                        $info="<strong>This selection misses the $this->offset most viewed pages of $wgSitename, however</strong>...<br />\n"; #English
                } else {
                        $class = "successbox";
                }
 
                return "<div class=\"$class\">$info$default_text</div><div class=\"visualClear\"></div>\n".$this->addPageOptions();
        }
 
        function addPageOptions() {
                return "
                        <div id='userloginForm'>                     
                         <form id='sitemaps' method='post' enctype='multipart/form-data' action='$this->form_action'>
                          <h2>Options</h2>
                          <table>
                           <tr>
                            <td>
                             <label for='wpFileName1'>File name</label>
                            </td>
                            <td>
                             <input tabindex='1' type='text' name='wpFileName' id='wpFileName1' title='file to overwrite' value='$this->file_name' disabled=true></input>
                            </td>
                           </tr>     
			   <tr>
                            <td>
                             <label for='wpSortCriterion1'>Sorting criterion</label>
                            </td>
			    <td>
			     <input type=radio name='wpSortCriterion' id='wpSortCriterion1' value='POP' checked='checked'>Popularity</input><br />
			     <input type=radio name='wpSortCriterion' id='wpSortCriterion1' value='REV'>Last revision</input>
			    </td>
			   </tr>
                           <tr>
                            <td>
                             <label for='wpChangeFreq1'>Estimate revision frequencies</label>
                            </td>
                            <td>
                             <input tabindex='2' type='checkbox' name='wpChangeFreq' id='wpChangeFreq1' title='daily, weekly, monthly...'></input>
                            </td>
                           </tr>                   
                           <tr>                    
                            <td>
                             <label for='wpPriorityType1'>Priority</label>
                            </td>
                            <td>
                             <select tabindex='3' name='wpPriorityType' id='wpPriorityType1' title='set relative priority based on page ranks'>
                                        <option>constant</option>
                                        <option>linear</option>
                                        <option>quadratic</option>
                                        <option>cubic</option>
                                        <option>exponential</option>
                                        <option>smooth</option>
                                        <option>random</option>
                                        <option>reverse</option>
                             </select>
                            </td>
                           </tr>
                           <tr>
                            <td>
                             <input tabindex='2' type='submit' value='Update Sitemap'></input>
                            </td>
                           </tr>
                          </table>
                         </form>
                        </div>
                        <div class=\"visualClear\"></div>
                        <br /><hr />\n\n";
        }
 
        function getSQL() {
                $dbr =& wfGetDB( DB_SLAVE );
 
                $page = $dbr->tableName( 'page' );
                $revision = $dbr->tableName( 'revision' );
 
		$criterion = $this->sorting_criterion=="REV"?"rev_timestamp":"page_counter";
 
                return "SELECT 'Popularpages' AS type,
                                page_id AS id,
                                page_namespace AS namespace,
                                page_title AS title,
                                ( MAX( rev_timestamp ) ) AS last_modification,
                                $criterion AS value
                                FROM $page, $revision
                                        WHERE ( page_namespace <> 8 AND page_namespace <> 9 )
                                        AND page_is_redirect = 0
                                        AND rev_page = page_id
                                GROUP BY page_id";
        }
 
        function sortDescending() {
                return true;
        }
 
        function preprocessResults( $db, $res ) {
                $this->count = $db->numRows($res);
        }
 
        function formatResult( $skin, $result ) {
                global $wgLang, $wgContLang, $wgServer;                
 
                $title = Title::makeTitle( $result->namespace, $result->title );
                $link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
 
                $url = $title->escapeLocalURL();
                $this->form_action=$title->escapeLocalURL( 'action=submit' );
 
                // The date must conform to ISO 8601 (http://www.w3.org/TR/NOTE-datetime)
                // UTC (Coordinated Universal Time) is used, google currently ignores time however
                $last_modification = gmdate( "Y-m-d\TH:i:s\Z", wfTimestamp( TS_UNIX, $result->last_modification ) );
 
                $this->addURL( $wgServer, $url, $last_modification, $result->id );
 
                ++$this->cursor_pos;
 
                return "{$link} <small>($wgServer$url)</small>";
        }
 
        function addURL( $base, $url, $last_modification, $page_id ) { // parameters must be valid XML data
                $result="  <url>\n    <loc>$base$url</loc>\n    <priority>".$this->getPriority()."</priority>\n    <lastmod>$last_modification</lastmod>\n    <changefreq>".$this->getChangeFreq($page_id)."</changefreq>\n  </url>\n";
                $this->utf8_write( $this->file_handle, $result );
        }
 
        function getPriority() { // must return valid XML data
                $x = $this->cursor_pos / $this->count;
 
                switch( $this->article_priorities ) {
                        case "constant"    : return $this->DEFAULT_PRIORITY;
                        case "linear"      : return 1.0 - $x;
                        case "quadratic"   : return pow( 1.0 - $x, 2.0 ) ;
                        case "cubic"       : return 3.0 * pow( ( 1.0 - $x ), 2.0 ) - 2.0 * pow( ( 1.0 - $x ), 3.0 );
                        case "exponential" : return exp( -6 * $x ); # exp(-6) ~= 0,002479
                        case "smooth"      : return cos( $x * pi() / 2.0 );
                        case "random"      : return mt_rand() / mt_getrandmax();
                        case "reverse"     : return $x;
 
                        default: return $this->DEFAULT_PRIORITY;
                }
        }
 
        function getChangeFreq( $page_id ) { // must return valid XML data
                if( $this->estimate_change_freq ) {
                        $dbr =& wfGetDB( DB_SLAVE );
 
                        $revision = $dbr->tableName( 'revision' );
 
                        $sql = "SELECT 
                                        MIN(rev_timestamp) AS creation_timestamp,
                                        COUNT(rev_timestamp) AS revision_count
                                        FROM $revision WHERE rev_page = $page_id";
 
                        $res = $dbr->query( $sql );
                        $count = $dbr->numRows( $res );      
 
                        if( $count < 1 ) {
                                return $this->DEFAULT_CHANGE_FREQ;
                        } else {       
                                $item1 =( $dbr->fetchObject( $res ) );
 
                                $cur = time() ; // now
                                $first = wfTimestamp( TS_UNIX, $item1->creation_timestamp );
 
                                // there were $item1->revision_count revisions in ($cur - $first) seconds 
                                $diff = ($cur - $first) / $item1->revision_count ;
 
                                switch( true ) {
                                        # case $diff < 60: return "always"; // I suspect Google to ignore these pages more often...
                                        case $diff < 3600: return "hourly";
                                        case $diff < 24*3600: return "daily";
                                        case $diff < 7*24*3600: return "weekly";
                                        case $diff < 30.33*24*3600: return "monthly";
                                        case $diff < 365.25*24*3600: return "yearly";
                                        default: return $this->DEFAULT_CHANGE_FREQ;
                                        # return "never"; // for archived pages only                                                            
                                }
                        }
                } else {
                        return $this->DEFAULT_CHANGE_FREQ;
                }
        }
}
 
/**
 * Entry point.
 */
function wfSpecialGoogleSitemap() {
        list( $limit, $offset ) = wfCheckLimits();
 
        $gsitemap = new GoogleSitemapPage( $offset, $limit );
 
        $gsitemap->initialize();
        $gsitemap->finalize();
}
