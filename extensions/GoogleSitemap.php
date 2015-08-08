<?php
 
$wgExtensionFunctions[] = 'wfExtensionSpecialGoogleSitemap';
$wgExtensionCredits['specialpage'][] = array (
        'name' => 'Special:GoogleSitemap',
        'description' => 'Adds a special page to create a XML Google Sitemap file, along with some reporting.',
        'url' => 'http://www.mediawiki.org/wiki/Extension:Google_Sitemap',
        'author' => 'Fran&amp;#231;ois Boutines-Vignard',
        'version' => '0.0.5'
);
 
function wfExtensionSpecialGoogleSitemap() {
        global $wgMessageCache;
        $wgMessageCache->addMessages(array('googlesitemap' => 'Google Sitemap')); 
 
        $wgAvailableRights[] = 'googlesitemap';
        $wgGroupPermissions['bureaucrat']['googlesitemap'] = true;
 
        SpecialPage::addPage( new SpecialPage( 'GoogleSitemap' , 'userrights') );
}