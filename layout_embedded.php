<?php

/**
 * Embedded version of the main renderer of the "vector" template for DokuWiki
 * Basically, everything is the same as the main renderer, except that only the main page content is rendered, nothing else (toolbox, index…). Useful for embedding (hence the name).
 *
 *
 * LICENSE: This file is open source software (OSS) and may be copied under
 *          certain conditions. See COPYING file for details or try to contact
 *          the author(s) of this file in doubt.
 *
 * @license GPLv2 (http://www.gnu.org/licenses/gpl2.html)
 * @author Andreas Haerter <development@andreas-haerter.com>
 * @author Matti Schneider-Ghibaudo
 * @link http://andreas-haerter.com/projects/dokuwiki-template-vector
 * @link http://www.dokuwiki.org/template:vector
 * @link http://www.dokuwiki.org/devel:templates
 * @link http://www.dokuwiki.org/devel:coding_style
 * @link http://www.dokuwiki.org/devel:environment
 * @link http://www.dokuwiki.org/devel:action_modes
 */




/**
 * Stores the template wide action
 *
 * Different DokuWiki actions requiring some template logic. Therefore the
 * template has to know, what we are doing right now - and that is what this
 * var is for.
 *
 * Please have a look at the "mediamanager.php" and "detail.php" file in the
 * same folder, they are also influencing the var's value.
 *
 * @var string
 * @author Andreas Haerter <development@andreas-haerter.com>
 */
$vector_action = "article";
//note: I used $_REQUEST before (cause DokuWiki controls and fills it. Normally,
//      using $_REQUEST is a possible security threat. For details, see
//      <http://www.suspekt.org/2008/10/01/php-53-and-delayed-cross-site-request-forgerieshijacking/>
//      and <http://forum.dokuwiki.org/post/16524>), but it did not work as
//      expected by me (maybe it is a reference and setting $vector_action
//      also changed the contents of $_REQUEST?!). That is why I switched back,
//      checking $_GET and $_POST like I did it before.
if (!empty($_GET["vecdo"])){
    $vector_action = (string)$_GET["vecdo"];
}elseif (!empty($_POST["vecdo"])){
    $vector_action = (string)$_POST["vecdo"];
}
if (!empty($vector_action) &&
    $vector_action !== "article" &&
    $vector_action !== "print" &&
    $vector_action !== "detail" &&
    $vector_action !== "mediamanager" &&
    $vector_action !== "cite"){
    //ignore unknown values
    $vector_action = "article";
}


/**
 * Stores the template wide context
 *
 * This template offers discussion pages via common articles, which should be
 * marked as "special". DokuWiki does not know any "special" articles, therefore
 * we have to take care about detecting if the current page is a discussion
 * page or not.
 *
 * @var string
 * @author Andreas Haerter <development@andreas-haerter.com>
 */
$vector_context = "article";
if (preg_match("/^".tpl_getConf("vector_discuss_ns")."?$|^".tpl_getConf("vector_discuss_ns").".*?$/i", ":".getNS(getID()))){
    $vector_context = "discuss";
}


/**
 * Stores the name the current client used to login
 *
 * @var string
 * @author Andreas Haerter <development@andreas-haerter.com>
 */
$loginname = "";
if (!empty($conf["useacl"])){
    if (isset($_SERVER["REMOTE_USER"]) && //no empty() but isset(): "0" may be a valid username...
        $_SERVER["REMOTE_USER"] !== ""){
        $loginname = $_SERVER["REMOTE_USER"]; //$INFO["client"] would not work here (-> e.g. if
                                              //current IP differs from the one used to login)
    }
}


//get needed language array
include DOKU_TPLINC."lang/en/lang.php";
//overwrite English language values with available translations
if (!empty($conf["lang"]) &&
    $conf["lang"] !== "en" &&
    file_exists(DOKU_TPLINC."/lang/".$conf["lang"]."/lang.php")){
    //get language file (partially translated language files are no problem
    //cause non translated stuff is still existing as English array value)
    include DOKU_TPLINC."/lang/".$conf["lang"]."/lang.php";
}


//detect revision
$rev = (int)$INFO["rev"]; //$INFO comes from the DokuWiki core
if ($rev < 1){
    $rev = (int)$INFO["lastmod"];
}

//workaround for the "jumping textarea" IE bug. CSS only fix not possible cause
//some DokuWiki JavaScript is triggering this bug, too. See the following for
//info:
//- <http://blog.andreas-haerter.com/2010/05/28/fix-msie-8-auto-scroll-textarea-css-width-percentage-bug>
//- <http://msdn.microsoft.com/library/cc817574.aspx>
if ($ACT === "edit" &&
    !headers_sent()){
    header("X-UA-Compatible: IE=EmulateIE7");
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo hsc($conf["lang"]); ?>" lang="<?php echo hsc($conf["lang"]); ?>" dir="<?php echo hsc($lang["direction"]); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php tpl_pagetitle(); echo " - ".hsc($conf["title"]); ?></title>
<?php
//show meta-tags
tpl_metaheaders();

//manually load needed CSS? this is a workaround for PHP Bug #49642. In some
//version/os combinations PHP is not able to parse INI-file entries if there
//are slashes "/" used for the keynames (see bugreport for more information:
//<http://bugs.php.net/bug.php?id=49692>). to trigger this workaround, simply
//delete/rename vector's style.ini.
if (!file_exists(DOKU_TPLINC."style.ini")){
    echo  "<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."bug49642.php".((!empty($lang["direction"]) && $lang["direction"] === "rtl") ? "?langdir=rtl" : "")."\" />\n"; //var comes from DokuWiki core
}

//include default or userdefined favicon
//
//note: since 2011-04-22 "Rincewind RC1", there is a core function named
//      "tpl_getFavicon()". But its functionality is not really fitting the
//      behaviour of this template, therefore I don't use it here.
if (file_exists(DOKU_TPLINC."user/favicon.ico")) {
    //user defined - you might find http://tools.dynamicdrive.com/favicon/
    //useful to generate one
    echo "\n<link rel=\"shortcut icon\" href=\"".DOKU_TPL."user/favicon.ico\" />\n";
} elseif (file_exists(DOKU_TPLINC."user/favicon.png")) {
    //note: I do NOT recommend PNG for favicons (cause it is not supported by
    //all browsers), but some users requested this feature.
    echo "\n<link rel=\"shortcut icon\" href=\"".DOKU_TPL."user/favicon.png\" />\n";
}else{
    //default
    echo "\n<link rel=\"shortcut icon\" href=\"".DOKU_TPL."static/3rd/dokuwiki/favicon.ico\" />\n";
}

//load userdefined js?
if (isset($_GET['embeddedJs']) && tpl_getConf("vector_loaduserjs")){
    echo "<script type=\"text/javascript\" charset=\"utf-8\" src=\"".DOKU_TPL."user/user.js\"></script>\n";
}
?>
	<link rel="stylesheet" type="text/css" href="<?php echo DOKU_TPL ?>static/css/embedded.css"/>
<?php

//show printable version?
if ($vector_action === "print"){
  //note: this is just a workaround for people searching for a print version.
  //      don't forget to update the styles.ini, this is the really important
  //      thing! BTW: good text about this: http://is.gd/5MyG5
  echo  "<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."static/3rd/dokuwiki/print.css\" />\n"
       ."<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."static/css/print.css\" />\n"
       ."<link rel=\"stylesheet\" media=\"all\" type=\"text/css\" href=\"".DOKU_TPL."user/print.css\" />\n";
}
//load language specific css hacks?
if (file_exists(DOKU_TPLINC."lang/".$conf["lang"]."/style.css")){
  $interim = trim(file_get_contents(DOKU_TPLINC."lang/".$conf["lang"]."/style.css"));
  if (!empty($interim)){
      echo "<style type=\"text/css\" media=\"all\">\n".hsc($interim)."\n</style>\n";
  }
}
?>
<!--[if lt IE 7]><style type="text/css">body{behavior:url("<?php echo DOKU_TPL; ?>static/3rd/vector/csshover.htc")}</style><![endif]-->
</head>
<body class="<?php
             //different styles/backgrounds for different page types
             switch (true){
                  //special: tech
                  case ($vector_action === "detail"):
                  case ($vector_action === "mediamanager"):
                  case ($vector_action === "cite"):
                  case ($ACT === "search"): //var comes from DokuWiki
                    echo "mediawiki ltr ns-1 ns-special ";
                    break;
                  //special: wiki
                  case (preg_match("/^wiki$|^wiki:.*?$/i", getNS(getID()))):
                    case "mediawiki ltr capitalize-all-nouns ns-4 ns-subject ";
                    break;
                  //discussion
                  case ($vector_context === "discuss"):
                    echo "mediawiki ltr capitalize-all-nouns ns-1 ns-talk ";
                    break;
                  //"normal" content
                  case ($ACT === "edit"): //var comes from DokuWiki
                  case ($ACT === "draft"): //var comes from DokuWiki
                  case ($ACT === "revisions"): //var comes from DokuWiki
                  case ($vector_action === "print"):
                  default:
                    echo "mediawiki ltr capitalize-all-nouns ns-0 ns-subject ";
                    break;
              }
              //add additional CSS class to hide some elements when
              //we have to show the (not) embedded mediamanager
              if ($vector_action === "mediamanager" &&
                  !tpl_getConf("vector_mediamanager_embedded")){
                  echo "mmanagernotembedded ";
              } ?>skin-vector">
<div id="page-container">

<!-- start div id=content -->
<div id="content">
  <a name="top" id="top"></a>
  <a name="dokuwiki__top" id="dokuwiki__top"></a>

  <!-- start main content area -->
  <?php
  //show messages (if there are any)
  html_msgarea();
  ?>

  <!-- start div id bodyContent -->
  <div id="bodyContent" class="dokuwiki">
    <!-- start rendered wiki content -->
    <?php
    //flush the buffer for faster page rendering, heaviest content follows
    if (function_exists("tpl_flush")) {
        tpl_flush(); //exists since 2010-11-07 "Anteater"...
    } else {
        flush(); //...but I won't loose compatibility to 2009-12-25 "Lemming" right now.
    }
    //decide which type of pagecontent we have to show
    switch ($vector_action){
        //"image details"
        case "detail":
            include DOKU_TPLINC."inc_detail.php";
            break;
        //file browser/"mediamanager"
        case "mediamanager":
            include DOKU_TPLINC."inc_mediamanager.php";
            break;
        //"cite this article"
        case "cite":
            include DOKU_TPLINC."inc_cite.php";
            break;
        //show "normal" content
        default:
            tpl_content(((tpl_getConf("vector_toc_position") === "article") ? true : false));
            break;
    }
    ?>
    <!-- end rendered wiki content -->
    <div class="clearer"></div>
  </div>
  <!-- end div id bodyContent -->
</div>
<!-- end div id=content -->

</div>
<!-- end page-container -->

<?php
	if (isset($_GET['embeddedCopyright'])):
?>
<!-- start footer -->
<div id="footer">
  <ul id="footer-info">
    <li id="footer-info-lastmod">
      <?php tpl_pageinfo()?><br />
    </li>
    <?php
    //copyright notice
    if (tpl_getConf("vector_copyright")){
        //show dokuwiki's default notice?
        if (tpl_getConf("vector_copyright_default")){
            echo "<li id=\"footer-info-copyright\">\n      <div class=\"dokuwiki\">";  //dokuwiki CSS class needed cause we have to show DokuWiki content
            tpl_license(false);
            echo "</div>\n    </li>\n";
        //show custom notice.
        }else{
            if (empty($conf["useacl"]) ||
                auth_quickaclcheck(cleanID(tpl_getConf("vector_copyright_location"))) >= AUTH_READ){ //current user got access?
                echo "<li id=\"footer-info-copyright\">\n        ";
                //get the rendered content of the defined wiki article to use as custom notice
                $interim = tpl_include_page(tpl_getConf("vector_copyright_location"), false);
                if ($interim === "" ||
                    $interim === false){
                    //show creation/edit link if the defined page got no content
                    echo "[&#160;";
                    tpl_pagelink(tpl_getConf("vector_copyright_location"), hsc($lang["vector_fillplaceholder"]." (".tpl_getConf("vector_copyright_location").")"));
                    echo "&#160;]<br />";
                }else{
                    //show the rendered page content
                    echo  "<div class=\"dokuwiki\">\n" //dokuwiki CSS class needed cause we are showing rendered page content
                         .$interim."\n        "
                         ."</div>";
                }
                echo "\n    </li>\n";
            }
        }
    }
    ?>
  </ul>
  <div style="clearer"></div>
</div>
<!-- end footer -->
<?php
endif;
?>

<script type="text/javascript">
//<![CDATA[
(function() {
	var links = document.getElementsByTagName('a');
	for (var i = 0; i < links.length; i++) {
		links[i].target = '_blank';
	}
})();
//]]>
</script>

<?php
//provide DokuWiki housekeeping, required in all templates
tpl_indexerWebBug();

//include web analytics software
if (isset($_GET['embeddedTrack']) &&file_exists(DOKU_TPLINC."/user/tracker.php")) {
    include DOKU_TPLINC."/user/tracker.php";
}
?>

</body>
</html>
