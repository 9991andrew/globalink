<?php
session_start();

// Direct user to map.php by default.
// If they aren't logged in, they'll automatically go to the login page.
// header('Location: map.php');

/**
 * Landing page showing information about MEGA World with prominent links to play and login.
 */
$pageTitle = _("Welcome to MEGA World");
include 'includes/mw_header.php';
?>

<style>
    p {
        margin-top:0.25rem;
        margin-bottom:1.25rem;
    }
</style>

<div class="text-center mb-4 font-ocr">
	<div class="m-auto xs:w-96">
<?php

if (!isset($_GET['newlogo'])) {
    if (!isset($_COOKIE['logo'])) {
        // Could default to a theme here, just don't set one.
        setcookie("logo", "mwlogo.svg");
        $_SESSION['logo'] = "mwlogo.svg";
    } 
    else {
        $_SESSION['logo'] = $_COOKIE['logo'];
    }  
}
else {
    if ($_GET['newlogo'] == "0") {
        setcookie("logo", "mwlogo.svg");
         $_SESSION['logo'] = "mwlogo.svg";
    }
    else {
        setcookie("logo", "mwlogo".$_GET['newlogo'].".png");
         $_SESSION['logo'] = "mwlogo".$_GET['newlogo'].".png";
    }
}

setcookie("logo", "mwlogo1.png");
$_SESSION['logo'] = "mwlogo1.png";


if ($_SESSION['logo'] == "mwlogo.svg") {
?>
		<!-- Include the SVG so we can use CSS to alter it -->
		<?php include 'images/mwlogo.svg'; ?>
<?php
}
else {
?>
        <img src="images/<?php echo($_SESSION['logo']);?>">
<?php
}
?>
	</div>
    <!-- Should this be localized? I'm going to say now -->
	<p><b class="text-lg">M</b>ultiplayer <b class="text-xl">E</b>ducational <b class="text-xl">G</b>ame&nbsp;for&nbsp;<b class="text-xl">A</b>ll</p>
    <?php if (! preg_match('/^en/', $_SESSION['locale'])) {
        // Show a localized version of the "MEGA" acronym
        echo '<p>'._('Multiplayer Educational Game for Everyone').'</p>';
    }?>
</div>

<?=languageSelect()?>

<div class="m-auto w-full max-w-lg ">
    <h2 class="mb-2 mt-10 text-xl font-ocr"><?=_('About MEGA World')?></h2>
    <p><?=_('MEGA World is a web-based role-playing game that allows players to navigate environments where they can interact with characters and other players to support educational goals. Quests are offered that can test proficiency in school subject matter, allowing students to learn while they play.')?></p>

    <p class="text-center p-4"><a href="map.php" tabindex="-1"><button class="btn highlight w-40"><?=_('Play Now')?></button></a></p>

    <a href="map.php"><img src="images/screen_shot.jpg" class="w-full" /></a>

    <p class="text-center p-4"><a href="guide.php" tabindex="-1"><button class="btn w-40"><?=_('User Guide')?></button></a></p>


    <h2 class="mb-2 mt-10 text-xl font-ocr"><?=_('Introduction Video')?></h2>
    <!-- padding hack to maintain the aspect ratio of the video -->
    <div class="relative" style="padding-bottom:calc((9 / 16) * 100%);">
        <iframe class="absolute top-0 left-0 w-full h-full" src="https://www.youtube.com/embed/wvlwLwiTIHU" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>

    <h2 class="mb-2 mt-10 text-xl font-ocr"><?=_('Team Members')?></h2>
    <div>
    <p><strong>Dr. Maiga Chang</strong><br>
    <a class="link" href="http://maiga.athabascau.ca/">http://maiga.athabascau.ca/</a><br>
    <?=_('Project Lead & Principal Investigator')?><br>
    <?=_('Full Professor')?><br>
    Athabasca University
    </p>

    <p><strong>JD Lien</strong><br>
    <a class="link" href="https://jdlien.com">https://jdlien.com/</a><br>
    <?=_('Lead Developer v3.0 & Graphic Designer (v3.0 Map Tiles)')?><br>
    <?=_('Undergraduate Project Student')?><br>
    Athabasca University
    </p>

    <p><strong>Vinayak Sharma</strong><br>
    <a class="link" href="https://www.linkedin.com/in/vinayak-sharma-141096193/">https://www.linkedin.com/in/vinayak-sharma-141096193/</a><br>
    <?=_('Lead Developer of Guardian and Guardian Bridge (MEGA World v3.1)')?><br>
    <?=_('MITACS Globalink Research Intern')?><br>
    Shri Mata Vaishno Devi University<br>
    India<br>
    </p>

    <h2 class="mb-2 mt-10 text-xl font-ocr"><?=_('Past Team Members')?></h2>

    <p><strong>Dr. Kinshuk</strong><br>
    <a class="link" href="http://www.kinshuk.info/">http://www.kinshuk.info/</a><br>
    <?=_('Project co-lead (v1.0)')?><br>
    <?=_('Dean of the College of Information')?><br>
    University of North Texas<br>
    USA<br>
    </p>

    <p><strong>Dr. Rita Kuo</strong><br>
    <a class="link" href="https://www.cs.nmt.edu/~rita/">https://www.cs.nmt.edu/~rita/</a><br>
    <?=_('Project co-lead (v2.0)')?><br>
    <?=_('Instructor')?><br>
    <?=_('Computer Science & Engineering')?><br>
    New Mexico Institute of Mining and Technology (New Mexico Tech)
    </p>

    <p><strong>Cheng-Hsin Chen</strong><br>
    <?=_('Lead Developer')?> (v2.0 JSP)<br>
    VanGood Technology Ltd., Taiwan<br>
    MScIS Student (was), Athabasca University<br>
    </p>

    <p><strong>Zhi-You Chen</strong><br>
    <?=_('Lead Programmer (Virtual Player)')?><br>
    Research Intern (2018), Smart-Informatics Ltd.<br>
    Information Management, Chung-Yuan Christian University, Taiwan
    </p>

    <p><strong>Samuel Chow</strong><br>
    <?=_('Lead Developer')?> (v1.0 Java Servlet)<br>
    Research Assistant (2008-2009), Athabasca University</p>

    <p><strong>Tzu-Jui Chu</strong><br>
    <?=_('Programmer (Virtual Player)')?><br>
    <?=_('Research Intern')?> (2018), Smart-Informatics Ltd.<br>
    Information Management, Chung-Yuan Christian University, Taiwan
    </p>

    <p><strong>Chris Kidney</strong><br>
    <?=_('Programmer (PvE module management)')?><br>
    Undergraduate Project Student (graduated), Athabasca University<br>
    </p>

    <p><strong>Hsuan-Ti (Sandy) Liu</strong><br>
    <?=_('Game Artist and Graphics Designer (Items in v3)')?><br>
    Research Intern (2022), Smart-Informatics Ltd.<br>
    Digital Design, Mingdao University, Taiwan</p>

    <p><strong>Zhong-Xiu Lu</strong><br>
    <?=_('Lead Developer, Analyst, and Designer (PvE module)')?>
    Research Assistant (2018), Smart-Informatics Ltd.
    </p>

    <p><strong>Xue Luo</strong><br>
    <?=_('Quests and World Creation for Java Programming (v2.1)')?><br>
    Globalink Research Intern (2017)<br>
    Southeast University, China
    </p>

    <p><strong>Volunteer Students</strong><br>
    <?=_('Game Artist (NPCs, Items, Avatars, and Map Tiles in v2.0)')?><br />
    <?=_('supervised by')?> <strong>Prof. Zhi-Hong Chen</strong> <?=_('in 2014-2015')?><br>
    Information Communication, Yuan-Ze University, Taiwan
    </p>

    <p><strong>Kuan-Hsing Wu</strong><br>
    <?=_('Lead Programmer (Speaking-based Conversation Quest)')?><br>
    Research Intern (2018), Smart-Informatics Ltd.<br>
    Information Management, Chung-Yuan Christian University, Taiwan
    </p>

    <p><strong>Bing Xu</strong><br>
    <?=_('Quests and World Creation for Math')?> (v2.0)<br>
    Globalink Research Intern (2015)<br>
    Beijing Normal University, China
    </p>

    <p><strong>Ting-Yu Yao</strong><br>
    <?=_('Game Artist (NPCs, Items, and Map Tiles in v2.1)')?><br>
    Research Intern (2016), Smart-Informatics Ltd.<br>
    Visual Communication Design, Shu-Te University, Taiwan
    </p>

    <p><strong>Chiung-Wei Yeh</strong><br>
    <?=_('Game Artist and Graphics Designer (NPCs, Avatars and Landing and Registration in v2.1)')?><br>
    Research Intern (2016), Smart-Informatics Ltd.<br>
    Visual Communication Design, Shu-Te University, Taiwan
    </p>

    <p><strong>Kuan-Hsing Wu</strong><br>
    <?=_('Programmer (Speaking-based Conversation Quest)')?><br>
    Research Intern (2018), Smart-Informatics Ltd.<br>
    Information Management, Chung-Yuan Christian University, Taiwan
    </p>

    <p><strong>Shu-Yu Zheng</strong><br>
    <?=_('Game Artist and Graphics Designer (Items, Monsters, and Animations for PvE in v2.1)')?><br>
    Research Intern (2018), Smart-Informatics Ltd.<br>
    Digital Design, Mingdao University, Taiwan
    </p>

    <p><strong>Xiao-Qian (Vicky) Xu</strong><br>
    <?=_('Game Artist and Graphics Designer (Map Tiles in v3)')?><br>
    Research Intern (2022), Smart-Informatics Ltd.<br>
    Digital Design, Mingdao University, Taiwan</p>
    <?=_('Game Artist and Graphics Designer (Items, Monsters, and Animations for PvE in v2.1)')?><br>
    Research Intern (2018), Smart-Informatics Ltd.<br>
    Digital Design, Mingdao University, Taiwan</p>

    <p><strong>Hui-Xin Zhang</strong><br>
    <?=_('Game Artist and Graphics Designer (favicon, logo, and NPCs in v3)')?><br>
    Research Intern (2022), Smart-Informatics Ltd.<br>
    Digital Design, Mingdao University, Taiwan</p>
    
    </div><!--team-->

    <h2 class="mb-2 mt-10 text-xl font-ocr"><?=_('Relevant Publications')?></h2>
    <p>Zhong-Xiu Lu, Maiga Chang, Rita Kuo, and Vivekanandan Kumar. (2019). Incorporating Farming Feature into MEGA World for Improving Learning Motivation. In the Proceedings Volume 2 of 27th International Conference on Computers in Education, Kenting, Taiwan, December 2-6, 2019, 591-598. (Open Access)  &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2019-ICCE2019-Workshop.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

    <p>Maiga Chang, Cheng-Ting Chen, Kuan-Hsing Wu, and Pei-Shan Yu. (2019). Conversation Quest in MEGA World (Multiplayer Educational Game for All). In Proceedings of International Conference on Smart Learning Environments (ICSLE 2019), Denton, TX, USA, March 18-20, 2019, 77-82. &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2019-ICSLE2019.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

    <p>Zhong-Xiu Lu, Xue Luo, Maiga Chang, Rita Kuo, Kuo-Chen Li. (2018). Role Playing Game Quest Design in Multiplayer Educational Game. In the Proceedings of 22nd Global Chinese Conference on Computers in Education (GCCCE 2018), Guangzhou, China, May 25-29, 2018, 680-688. (Best Technical Design Paper Award)  &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2018-GCCCE2018-Quest.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

    <p>Zongxi Li, Di Zou, Haoran Xie, Fu Lee Wang, and Maiga Chang. (2018). Enhancing Information Lliteracy in Hong Kong Higher Education through Game-based Learning. In the Proceedings of 22nd Global Chinese Conference on Computers in Education (GCCCE 2018), Guangzhou, China, May 25-29, 2018, 595-598.  &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2018-GCCCE2018-Information.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

    <p>Bing Xu, Maiga Chang, Guang Chen, Zhi-Hong Chen, and Kinshuk. (2016). Perliminary Study on the Influence of Role Playing Quests in Educational Game. In the Proceedings of 20th Global Chinese Conference in Computer Education (GCCCE 2016), Hong-Kong, May 23-27, 2016, 344-347. &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2016-GCCCE2016.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

    <p>Rita Kuo, Maiga Chang, Kinshuk, and Eric Zhi-Feng Liu. (2010). Applying Multiplayer Online Game in Actionscript Programming Courses for Students Doing Self-Assessment. In the Proceedings of Workshop on New Paradigms in Learning: Robotics, Playful Learning, and Digital Arts, in the 18th International Conference on Computers in Education, (ICCE 2010), Taipei, Taiwan, Putrajaya, Malaysia, November 29-December 3, 2010, 351-355. &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2010-ICCE2010-MEGA.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

    <p>Maiga Chang and Kinshuk. (2010). Web-based Multiplayer Online Role Playing Game (MORPG) for Assessing Students' Java Programming Knowledge and Skills. In the Proceedings of the 3rd IEEE International Conference on Digital Game and Intelligent Toy Enhanced Learning, (DIGITEL 2010), Kaohsiung, Taiwan, April 12-16, 2010, 103-107. &nbsp; <a class="link whitespace-nowrap" href="./assets/Relevant Publications/Conference-2010-DIGITEL2010.pdf"><i class="fas fa-file-pdf"></i>&nbsp;PDF</a>
    </p>

</div>


<?php
include 'includes/mw_footer.php';
