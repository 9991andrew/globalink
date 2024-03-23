<?php
/**
 * User guide for MEGA World v3
 */

$pageTitle = _("How to Play")." MEGA&nbsp;World";
include 'includes/mw_header.php';
?>

<style>
    main {
        margin:0 auto;
        max-width:1000px;
    }
    picture {
        display:block;
        text-align: center;
        margin: auto;
    }
    picture source, picture img {
        max-width:900px;
        text-align:center;
        margin: auto;
    }

    h3 {
        margin-bottom:8px;
        margin-top:24px;
        font-size:24px;
        font-weight:bold;
    }

    ol li, ul li {
        margin-bottom:12px;
    }

    ul li::before {
        content: "\272a";
        color: gray;
        /*font-family: "Font Awesome 5 Free";*/
        padding: 0 10px 0 0 ;
    }

    p {
        line-height:22px;
    }

    p+p {
        margin-top:20px;
    }

    #movementKeys {
        margin: 0 auto;
        text-align: center;
        font-size:18px;
    }

    #movementKeys kbd {
        min-width:26px;
        display: inline-block;
        text-decoration: none;
        padding: 1px 4px;
        margin: 1px 0 4px 6px;
        border:2px solid;
        border-top-color:rgba(255,255,255,0.3);
        border-left-color:rgba(255,255,255,0.3);
        border-bottom-color:rgba(0,0,0,0.5);
        border-right-color:rgba(0,0,0,0.5);
        border-radius: 4px;
        background: rgba(128,128,128,0.3);
        box-shadow:1px 1px 1px rgba(0,0,0,.3);
    }

    .btn {
        display: inline-block;
        min-width:10rem;
    }

    @media (max-width:900px) {
        picture source, picture img, iframe {
            max-width:100%;
            text-align:center;
        }

    }
</style>

<div class="text-center mb-4 font-ocr">
    <div class="m-auto xs:w-96">
        <!-- Include the SVG so we can use CSS to alter it -->
        <?php include 'images/mwlogo.svg'; ?>
    </div>
    <p><b class="text-xl">M</b>ultiplayer <b class="text-xl">E</b>ducational <b class="text-xl">G</b>ame&nbsp;for&nbsp;<b class="text-xl">A</b>ll</p>
    <?php if (! preg_match('/^en/', $_SESSION['locale'])) {
        // Show a localized version of the "MEGA" acronym
        echo '<p>'._('Multiplayer Educational Game for Everyone').'</p>';
    }?>
</div>

<?=languageSelect()?>

<h2 class="text-center mb-2 mt-8 text-2xl font-ocr"><?=$pageTitle?></h2>

<?php
 // Load the appropriate localized content here. This is very heavy documentation so it all needs to be translated as one piece rather than bits of strings.
    if (file_exists('locale/guide_content_'.preg_replace('/(.*)\.utf8/', '$1', $_SESSION['locale']).'.html')) {
        include('locale/guide_content_'.preg_replace('/(.*)\.utf8/', '$1', $_SESSION['locale']).'.html');
    } else {
        include('locale/guide_content_en_CA.html');
    }
include 'includes/mw_footer.php';