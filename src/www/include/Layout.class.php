<?php   

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//              
// 


require_once('common/include/Response.class.php');

require_once('common/event/EventManager.class.php');

require_once('common/include/CodeX_HTMLPurifier.class.php');

            
/*

	Extends the basic Error class to add HTML functions for displaying all site dependent HTML, while allowing extendibility/overriding by themes via the Theme class.

	Make sure browser.php is included _before_ you create an instance of this object.

	Geoffrey Herteg, August 29, 2000

*/



class Layout extends Response {

    
	//Define all the icons for this theme
	var $icons = array('Summary' => 'ic/anvil24.png',
		'Homepage' => 'ic/home.png',
		'Forums' => 'ic/notes.png',
		'Bugs' => 'ic/bug.png',
		'Support' => 'ic/support.png',
		'Patches' => 'ic/patch.png',
		'Lists' => 'ic/mail.png',
		'Tasks' => 'ic/index.png',
		'Docs' => 'ic/docman.png',
		'Surveys' => 'ic/survey.png',
		'News' => 'ic/news.png',
		'CVS' => 'ic/convert.png',
		'Files' => 'ic/save.png',
		'Trackers' => 'ic/tracker20w.png'
		);

	var $bgpri = array();
    var $feeds;
    
	// Constuctor
	function Layout($root) {
		GLOBAL $bgpri;
        
		// Constructor for parent class...
		$this->Response();
        
        $this->feeds = array();
        $this->javascript = array();
        
		/*
	        Set up the priority color array one time only
		*/
		$bgpri[1] = 'priora';
		$bgpri[2] = 'priorb';
		$bgpri[3] = 'priorc';
		$bgpri[4] = 'priord';
		$bgpri[5] = 'priore';
		$bgpri[6] = 'priorf';
		$bgpri[7] = 'priorg';
		$bgpri[8] = 'priorh';
		$bgpri[9] = 'priori';

	}
    
    function getChartColors() {
        return array(
            'lightsalmon',
            'palegreen',
            'paleturquoise',
            'lightyellow',
            'thistle',
            'steelblue1',
            'palevioletred1',
            'palegoldenrod',
            'wheat1',
            'gold',
            'olivedrab1',
            'lightcyan',
            'lightcyan3',
            'lightgoldenrod1',
            'rosybrown',
            'mistyrose',
            'silver',
            'aquamarine',
            'pink1',
            'lemonchiffon3',
            'skyblue',
            'mintcream',
            'lavender',
            'linen',
            'yellowgreen',
            'burlywood',
            'coral',
            'mistyrose3',
            'slategray1',
            'yellow1',
        );
    }
    
    function getChartBackgroundColor() {
        return "white";
    }
    
    function getChartMainColor() {
        return "#444444";
    }
    
    public function getGanttLateBarColor() {
        return 'salmon';
    }
    
    public function getGanttErrorBarColor() {
        return 'yellow';
    }
    
    public function getGanttGreenBarColor() {
        return 'darkgreen';
    }
    
    public function getGanttTodayLineColor() {
        return 'red';
    }
    
    public function getGanttHeaderColor() {
        return 'gray9';
    }
    
    public function getGanttBarColor() {
        return 'steelblue1';
    }
    
    public function getGanttMilestoneColor() {
        return 'orange';
    }
    
    public function getTextColors() {
    	return array(
            'lightsalmon',
            'palegreen',
            'thistle',
            'steelblue1',
            'palevioletred1',
            'gold',
    	    'lightcyan3',
            'rosybrown',
            'silver',
            'pink1',
            'lemonchiffon3',
            'skyblue',
            'yellowgreen',
            'burlywood',
            'coral',
            'mistyrose3'
        );
    }
    
    public function getColorCodeFromColorName($color_name, $type='chart') {
    	if ($type == 'text') {
    		$available_colors = $this->getTextColors();
    	} else {
    		$available_colors = $this->getChartColors();
    	}
    	if (in_array($color_name, $available_colors)) {
    		$rgb_table = array(
			    "aqua"=> array(0,255,255),		
			    "lime"=> array(0,255,0),		
			    "teal"=> array(0,128,128),
			    "whitesmoke"=>array(245,245,245),
			    "gainsboro"=>array(220,220,220),
			    "oldlace"=>array(253,245,230),
			    "linen"=>array(250,240,230),
			    "antiquewhite"=>array(250,235,215),
			    "papayawhip"=>array(255,239,213),
			    "blanchedalmond"=>array(255,235,205),
			    "bisque"=>array(255,228,196),
			    "peachpuff"=>array(255,218,185),
			    "navajowhite"=>array(255,222,173),
			    "moccasin"=>array(255,228,181),
			    "cornsilk"=>array(255,248,220),
			    "ivory"=>array(255,255,240),
			    "lemonchiffon"=>array(255,250,205),
			    "seashell"=>array(255,245,238),
			    "mintcream"=>array(245,255,250),
			    "azure"=>array(240,255,255),
			    "aliceblue"=>array(240,248,255),
			    "lavender"=>array(230,230,250),
			    "lavenderblush"=>array(255,240,245),
			    "mistyrose"=>array(255,228,225),
			    "white"=>array(255,255,255),
			    "black"=>array(0,0,0),
			    "darkslategray"=>array(47,79,79),
			    "dimgray"=>array(105,105,105),
			    "slategray"=>array(112,128,144),
			    "lightslategray"=>array(119,136,153),
			    "gray"=>array(190,190,190),
			    "lightgray"=>array(211,211,211),
			    "midnightblue"=>array(25,25,112),
			    "navy"=>array(0,0,128),
			    "cornflowerblue"=>array(100,149,237),
			    "darkslateblue"=>array(72,61,139),
			    "slateblue"=>array(106,90,205),
			    "mediumslateblue"=>array(123,104,238),
			    "lightslateblue"=>array(132,112,255),
			    "mediumblue"=>array(0,0,205),
			    "royalblue"=>array(65,105,225),
			    "blue"=>array(0,0,255),
			    "dodgerblue"=>array(30,144,255),
			    "deepskyblue"=>array(0,191,255),
			    "skyblue"=>array(135,206,235),
			    "lightskyblue"=>array(135,206,250),
			    "steelblue"=>array(70,130,180),
			    "lightred"=>array(211,167,168),
			    "lightsteelblue"=>array(176,196,222),
			    "lightblue"=>array(173,216,230),
			    "powderblue"=>array(176,224,230),
			    "paleturquoise"=>array(175,238,238),
			    "darkturquoise"=>array(0,206,209),
			    "mediumturquoise"=>array(72,209,204),
			    "turquoise"=>array(64,224,208),
			    "cyan"=>array(0,255,255),
			    "lightcyan"=>array(224,255,255),
			    "cadetblue"=>array(95,158,160),
			    "mediumaquamarine"=>array(102,205,170),
			    "aquamarine"=>array(127,255,212),
			    "darkgreen"=>array(0,100,0),
			    "darkolivegreen"=>array(85,107,47),
			    "darkseagreen"=>array(143,188,143),
			    "seagreen"=>array(46,139,87),
			    "mediumseagreen"=>array(60,179,113),
			    "lightseagreen"=>array(32,178,170),
			    "palegreen"=>array(152,251,152),
			    "springgreen"=>array(0,255,127),
			    "lawngreen"=>array(124,252,0),
			    "green"=>array(0,255,0),
			    "chartreuse"=>array(127,255,0),
			    "mediumspringgreen"=>array(0,250,154),
			    "greenyellow"=>array(173,255,47),
			    "limegreen"=>array(50,205,50),
			    "yellowgreen"=>array(154,205,50),
			    "forestgreen"=>array(34,139,34),
			    "olivedrab"=>array(107,142,35),
			    "darkkhaki"=>array(189,183,107),
			    "khaki"=>array(240,230,140),
			    "palegoldenrod"=>array(238,232,170),
			    "lightgoldenrodyellow"=>array(250,250,210),
			    "lightyellow"=>array(255,255,200),
			    "yellow"=>array(255,255,0),
			    "gold"=>array(255,215,0),
			    "lightgoldenrod"=>array(238,221,130),
			    "goldenrod"=>array(218,165,32),
			    "darkgoldenrod"=>array(184,134,11),
			    "rosybrown"=>array(188,143,143),
			    "indianred"=>array(205,92,92),
			    "saddlebrown"=>array(139,69,19),
			    "sienna"=>array(160,82,45),
			    "peru"=>array(205,133,63),
			    "burlywood"=>array(222,184,135),
			    "beige"=>array(245,245,220),
			    "wheat"=>array(245,222,179),
			    "sandybrown"=>array(244,164,96),
			    "tan"=>array(210,180,140),
			    "chocolate"=>array(210,105,30),
			    "firebrick"=>array(178,34,34),
			    "brown"=>array(165,42,42),
			    "darksalmon"=>array(233,150,122),
			    "salmon"=>array(250,128,114),
			    "lightsalmon"=>array(255,160,122),
			    "orange"=>array(255,165,0),
			    "darkorange"=>array(255,140,0),
			    "coral"=>array(255,127,80),
			    "lightcoral"=>array(240,128,128),
			    "tomato"=>array(255,99,71),
			    "orangered"=>array(255,69,0),
			    "red"=>array(255,0,0),
			    "hotpink"=>array(255,105,180),
			    "deeppink"=>array(255,20,147),
			    "pink"=>array(255,192,203),
			    "lightpink"=>array(255,182,193),
			    "palevioletred"=>array(219,112,147),
			    "maroon"=>array(176,48,96),
			    "mediumvioletred"=>array(199,21,133),
			    "violetred"=>array(208,32,144),
			    "magenta"=>array(255,0,255),
			    "violet"=>array(238,130,238),
			    "plum"=>array(221,160,221),
			    "orchid"=>array(218,112,214),
			    "mediumorchid"=>array(186,85,211),
			    "darkorchid"=>array(153,50,204),
			    "darkviolet"=>array(148,0,211),
			    "blueviolet"=>array(138,43,226),
			    "purple"=>array(160,32,240),
			    "mediumpurple"=>array(147,112,219),
			    "thistle"=>array(216,191,216),
			    "snow1"=>array(255,250,250),
			    "snow2"=>array(238,233,233),
			    "snow3"=>array(205,201,201),
			    "snow4"=>array(139,137,137),
			    "seashell1"=>array(255,245,238),
			    "seashell2"=>array(238,229,222),
			    "seashell3"=>array(205,197,191),
			    "seashell4"=>array(139,134,130),
			    "AntiqueWhite1"=>array(255,239,219),
			    "AntiqueWhite2"=>array(238,223,204),
			    "AntiqueWhite3"=>array(205,192,176),
			    "AntiqueWhite4"=>array(139,131,120),
			    "bisque1"=>array(255,228,196),
			    "bisque2"=>array(238,213,183),
			    "bisque3"=>array(205,183,158),
			    "bisque4"=>array(139,125,107),
			    "peachPuff1"=>array(255,218,185),
			    "peachpuff2"=>array(238,203,173),
			    "peachpuff3"=>array(205,175,149),
			    "peachpuff4"=>array(139,119,101),
			    "navajowhite1"=>array(255,222,173),
			    "navajowhite2"=>array(238,207,161),
			    "navajowhite3"=>array(205,179,139),
			    "navajowhite4"=>array(139,121,94),
			    "lemonchiffon1"=>array(255,250,205),
			    "lemonchiffon2"=>array(238,233,191),
			    "lemonchiffon3"=>array(205,201,165),
			    "lemonchiffon4"=>array(139,137,112),
			    "ivory1"=>array(255,255,240),
			    "ivory2"=>array(238,238,224),
			    "ivory3"=>array(205,205,193),
			    "ivory4"=>array(139,139,131),
			    "honeydew"=>array(193,205,193),
			    "lavenderblush1"=>array(255,240,245),
			    "lavenderblush2"=>array(238,224,229),
			    "lavenderblush3"=>array(205,193,197),
			    "lavenderblush4"=>array(139,131,134),
			    "mistyrose1"=>array(255,228,225),
			    "mistyrose2"=>array(238,213,210),
			    "mistyrose3"=>array(205,183,181),
			    "mistyrose4"=>array(139,125,123),
			    "azure1"=>array(240,255,255),
			    "azure2"=>array(224,238,238),
			    "azure3"=>array(193,205,205),
			    "azure4"=>array(131,139,139),
			    "slateblue1"=>array(131,111,255),
			    "slateblue2"=>array(122,103,238),
			    "slateblue3"=>array(105,89,205),
			    "slateblue4"=>array(71,60,139),
			    "royalblue1"=>array(72,118,255),
			    "royalblue2"=>array(67,110,238),
			    "royalblue3"=>array(58,95,205),
			    "royalblue4"=>array(39,64,139),
			    "dodgerblue1"=>array(30,144,255),
			    "dodgerblue2"=>array(28,134,238),
			    "dodgerblue3"=>array(24,116,205),
			    "dodgerblue4"=>array(16,78,139),
			    "steelblue1"=>array(99,184,255),
			    "steelblue2"=>array(92,172,238),
			    "steelblue3"=>array(79,148,205),
			    "steelblue4"=>array(54,100,139),
			    "deepskyblue1"=>array(0,191,255),
			    "deepskyblue2"=>array(0,178,238),
			    "deepskyblue3"=>array(0,154,205),
			    "deepskyblue4"=>array(0,104,139),
			    "skyblue1"=>array(135,206,255),
			    "skyblue2"=>array(126,192,238),
			    "skyblue3"=>array(108,166,205),
			    "skyblue4"=>array(74,112,139),
			    "lightskyblue1"=>array(176,226,255),
			    "lightskyblue2"=>array(164,211,238),
			    "lightskyblue3"=>array(141,182,205),
			    "lightskyblue4"=>array(96,123,139),
			    "slategray1"=>array(198,226,255),
			    "slategray2"=>array(185,211,238),
			    "slategray3"=>array(159,182,205),
			    "slategray4"=>array(108,123,139),
			    "lightsteelblue1"=>array(202,225,255),
			    "lightsteelblue2"=>array(188,210,238),
			    "lightsteelblue3"=>array(162,181,205),
			    "lightsteelblue4"=>array(110,123,139),
			    "lightblue1"=>array(191,239,255),
			    "lightblue2"=>array(178,223,238),
			    "lightblue3"=>array(154,192,205),
			    "lightblue4"=>array(104,131,139),
			    "lightcyan1"=>array(224,255,255),
			    "lightcyan2"=>array(209,238,238),
			    "lightcyan3"=>array(180,205,205),
			    "lightcyan4"=>array(122,139,139),
			    "paleturquoise1"=>array(187,255,255),
			    "paleturquoise2"=>array(174,238,238),
			    "paleturquoise3"=>array(150,205,205),
			    "paleturquoise4"=>array(102,139,139),
			    "cadetblue1"=>array(152,245,255),
			    "cadetblue2"=>array(142,229,238),
			    "cadetblue3"=>array(122,197,205),
			    "cadetblue4"=>array(83,134,139),
			    "turquoise1"=>array(0,245,255),
			    "turquoise2"=>array(0,229,238),
			    "turquoise3"=>array(0,197,205),
			    "turquoise4"=>array(0,134,139),
			    "cyan1"=>array(0,255,255),
			    "cyan2"=>array(0,238,238),
			    "cyan3"=>array(0,205,205),
			    "cyan4"=>array(0,139,139),
			    "darkslategray1"=>array(151,255,255),
			    "darkslategray2"=>array(141,238,238),
			    "darkslategray3"=>array(121,205,205),
			    "darkslategray4"=>array(82,139,139),
			    "aquamarine1"=>array(127,255,212),
			    "aquamarine2"=>array(118,238,198),
			    "aquamarine3"=>array(102,205,170),
			    "aquamarine4"=>array(69,139,116),
			    "darkseagreen1"=>array(193,255,193),
			    "darkseagreen2"=>array(180,238,180),
			    "darkseagreen3"=>array(155,205,155),
			    "darkseagreen4"=>array(105,139,105),
			    "seagreen1"=>array(84,255,159),
			    "seagreen2"=>array(78,238,148),
			    "seagreen3"=>array(67,205,128),
			    "seagreen4"=>array(46,139,87),
			    "palegreen1"=>array(154,255,154),
			    "palegreen2"=>array(144,238,144),
			    "palegreen3"=>array(124,205,124),
			    "palegreen4"=>array(84,139,84),
			    "springgreen1"=>array(0,255,127),
			    "springgreen2"=>array(0,238,118),
			    "springgreen3"=>array(0,205,102),
			    "springgreen4"=>array(0,139,69),
			    "chartreuse1"=>array(127,255,0),
			    "chartreuse2"=>array(118,238,0),
			    "chartreuse3"=>array(102,205,0),
			    "chartreuse4"=>array(69,139,0),
			    "olivedrab1"=>array(192,255,62),
			    "olivedrab2"=>array(179,238,58),
			    "olivedrab3"=>array(154,205,50),
			    "olivedrab4"=>array(105,139,34),
			    "darkolivegreen1"=>array(202,255,112),
			    "darkolivegreen2"=>array(188,238,104),
			    "darkolivegreen3"=>array(162,205,90),
			    "darkolivegreen4"=>array(110,139,61),
			    "khaki1"=>array(255,246,143),
			    "khaki2"=>array(238,230,133),
			    "khaki3"=>array(205,198,115),
			    "khaki4"=>array(139,134,78),
			    "lightgoldenrod1"=>array(255,236,139),
			    "lightgoldenrod2"=>array(238,220,130),
			    "lightgoldenrod3"=>array(205,190,112),
			    "lightgoldenrod4"=>array(139,129,76),
			    "yellow1"=>array(255,255,0),
			    "yellow2"=>array(238,238,0),
			    "yellow3"=>array(205,205,0),
			    "yellow4"=>array(139,139,0),
			    "gold1"=>array(255,215,0),
			    "gold2"=>array(238,201,0),
			    "gold3"=>array(205,173,0),
			    "gold4"=>array(139,117,0),
			    "goldenrod1"=>array(255,193,37),
			    "goldenrod2"=>array(238,180,34),
			    "goldenrod3"=>array(205,155,29),
			    "goldenrod4"=>array(139,105,20),
			    "darkgoldenrod1"=>array(255,185,15),
			    "darkgoldenrod2"=>array(238,173,14),
			    "darkgoldenrod3"=>array(205,149,12),
			    "darkgoldenrod4"=>array(139,101,8),
			    "rosybrown1"=>array(255,193,193),
			    "rosybrown2"=>array(238,180,180),
			    "rosybrown3"=>array(205,155,155),
			    "rosybrown4"=>array(139,105,105),
			    "indianred1"=>array(255,106,106),
			    "indianred2"=>array(238,99,99),
			    "indianred3"=>array(205,85,85),
			    "indianred4"=>array(139,58,58),
			    "sienna1"=>array(255,130,71),
			    "sienna2"=>array(238,121,66),
			    "sienna3"=>array(205,104,57),
			    "sienna4"=>array(139,71,38),
			    "burlywood1"=>array(255,211,155),
			    "burlywood2"=>array(238,197,145),
			    "burlywood3"=>array(205,170,125),
			    "burlywood4"=>array(139,115,85),
			    "wheat1"=>array(255,231,186),
			    "wheat2"=>array(238,216,174),
			    "wheat3"=>array(205,186,150),
			    "wheat4"=>array(139,126,102),
			    "tan1"=>array(255,165,79),
			    "tan2"=>array(238,154,73),
			    "tan3"=>array(205,133,63),
			    "tan4"=>array(139,90,43),
			    "chocolate1"=>array(255,127,36),
			    "chocolate2"=>array(238,118,33),
			    "chocolate3"=>array(205,102,29),
			    "chocolate4"=>array(139,69,19),
			    "firebrick1"=>array(255,48,48),
			    "firebrick2"=>array(238,44,44),
			    "firebrick3"=>array(205,38,38),
			    "firebrick4"=>array(139,26,26),
			    "brown1"=>array(255,64,64),
			    "brown2"=>array(238,59,59),
			    "brown3"=>array(205,51,51),
			    "brown4"=>array(139,35,35),
			    "salmon1"=>array(255,140,105),
			    "salmon2"=>array(238,130,98),
			    "salmon3"=>array(205,112,84),
			    "salmon4"=>array(139,76,57),
			    "lightsalmon1"=>array(255,160,122),
			    "lightsalmon2"=>array(238,149,114),
			    "lightsalmon3"=>array(205,129,98),
			    "lightsalmon4"=>array(139,87,66),
			    "orange1"=>array(255,165,0),
			    "orange2"=>array(238,154,0),
			    "orange3"=>array(205,133,0),
			    "orange4"=>array(139,90,0),
			    "darkorange1"=>array(255,127,0),
			    "darkorange2"=>array(238,118,0),
			    "darkorange3"=>array(205,102,0),
			    "darkorange4"=>array(139,69,0),
			    "coral1"=>array(255,114,86),
			    "coral2"=>array(238,106,80),
			    "coral3"=>array(205,91,69),
			    "coral4"=>array(139,62,47),
			    "tomato1"=>array(255,99,71),
			    "tomato2"=>array(238,92,66),
			    "tomato3"=>array(205,79,57),
			    "tomato4"=>array(139,54,38),
			    "orangered1"=>array(255,69,0),
			    "orangered2"=>array(238,64,0),
			    "orangered3"=>array(205,55,0),
			    "orangered4"=>array(139,37,0),
			    "deeppink1"=>array(255,20,147),
			    "deeppink2"=>array(238,18,137),
			    "deeppink3"=>array(205,16,118),
			    "deeppink4"=>array(139,10,80),
			    "hotpink1"=>array(255,110,180),
			    "hotpink2"=>array(238,106,167),
			    "hotpink3"=>array(205,96,144),
			    "hotpink4"=>array(139,58,98),
			    "pink1"=>array(255,181,197),
			    "pink2"=>array(238,169,184),
			    "pink3"=>array(205,145,158),
			    "pink4"=>array(139,99,108),
			    "lightpink1"=>array(255,174,185),
			    "lightpink2"=>array(238,162,173),
			    "lightpink3"=>array(205,140,149),
			    "lightpink4"=>array(139,95,101),
			    "palevioletred1"=>array(255,130,171),
			    "palevioletred2"=>array(238,121,159),
			    "palevioletred3"=>array(205,104,137),
			    "palevioletred4"=>array(139,71,93),
			    "maroon1"=>array(255,52,179),
			    "maroon2"=>array(238,48,167),
			    "maroon3"=>array(205,41,144),
			    "maroon4"=>array(139,28,98),
			    "violetred1"=>array(255,62,150),
			    "violetred2"=>array(238,58,140),
			    "violetred3"=>array(205,50,120),
			    "violetred4"=>array(139,34,82),
			    "magenta1"=>array(255,0,255),
			    "magenta2"=>array(238,0,238),
			    "magenta3"=>array(205,0,205),
			    "magenta4"=>array(139,0,139),
			    "mediumred"=>array(140,34,34),         
			    "orchid1"=>array(255,131,250),
			    "orchid2"=>array(238,122,233),
			    "orchid3"=>array(205,105,201),
			    "orchid4"=>array(139,71,137),
			    "plum1"=>array(255,187,255),
			    "plum2"=>array(238,174,238),
			    "plum3"=>array(205,150,205),
			    "plum4"=>array(139,102,139),
			    "mediumorchid1"=>array(224,102,255),
			    "mediumorchid2"=>array(209,95,238),
			    "mediumorchid3"=>array(180,82,205),
			    "mediumorchid4"=>array(122,55,139),
			    "darkorchid1"=>array(191,62,255),
			    "darkorchid2"=>array(178,58,238),
			    "darkorchid3"=>array(154,50,205),
			    "darkorchid4"=>array(104,34,139),
			    "purple1"=>array(155,48,255),
			    "purple2"=>array(145,44,238),
			    "purple3"=>array(125,38,205),
			    "purple4"=>array(85,26,139),
			    "mediumpurple1"=>array(171,130,255),
			    "mediumpurple2"=>array(159,121,238),
			    "mediumpurple3"=>array(137,104,205),
			    "mediumpurple4"=>array(93,71,139),
			    "thistle1"=>array(255,225,255),
			    "thistle2"=>array(238,210,238),
			    "thistle3"=>array(205,181,205),
			    "thistle4"=>array(139,123,139),
			    "gray1"=>array(10,10,10),
			    "gray2"=>array(40,40,30),
			    "gray3"=>array(70,70,70),
			    "gray4"=>array(100,100,100),
			    "gray5"=>array(130,130,130),
			    "gray6"=>array(160,160,160),
			    "gray7"=>array(190,190,190),
			    "gray8"=>array(210,210,210),
			    "gray9"=>array(240,240,240),
			    "darkgray"=>array(100,100,100),
			    "darkblue"=>array(0,0,139),
			    "darkcyan"=>array(0,139,139),
			    "darkmagenta"=>array(139,0,139),
			    "darkred"=>array(139,0,0),
			    "silver"=>array(192, 192, 192),
			    "eggplant"=>array(144,176,168),
			    "lightgreen"=>array(144,238,144));
    		
    		$rgb_arr = $rgb_table[$color_name];
			$r = dechex($rgb_arr[0]);
			if (strlen($r) < 2) {
				$r = "0".$r;
			}
			$g = dechex($rgb_arr[1]);
			if (strlen($g) < 2) {
				$g = "0".$g;
			}
			$b = dechex($rgb_arr[2]);
			if (strlen($b) < 2) {
				$b = "0".$b;
			}
			return "#".$r.$g.$b;
    	}
    }
    
    function redirect($url) {
       $is_anon = session_hash() ? false : true;
       $fb = $GLOBALS['feedback'] || count($this->_feedback->logs);
       if (($is_anon && (headers_sent() || $fb)) || (!$is_anon && headers_sent())) {
            $this->header(array('title' => 'Redirection'));
            echo '<p>'. $GLOBALS['Language']->getText('global', 'return_to', array($url)) .'</p>';
            echo '<script type="text/javascript">';
            if (!$fb) {
                echo 'setTimeout(function() {';
            }
            echo " location.href = '". $url ."';";
            if (!$fb) {
                echo '}, 5000);';
            }
            echo '</script>';
            $this->footer(array());
        } else {
            if (!$is_anon && !headers_sent() && $fb) {
                $this->_serializeFeedback();
            }
            // Protect against CRLF injections,
            // This seems to be fixed in php 4.4.2 and 5.1.2 according to
            // http://php.net/header
            if(strpos($url, "\n")) {
                trigger_error('HTTP header injection detected. Abort.', E_USER_ERROR);
            } else {
                header('Location: '. $url);
            }
        }
        exit();
    }
    
    function iframe($url, $html_options = array()) {
        $html = '';
        $html .= '<div class="iframe_showonly"><a href="'. $url .'" title="Show only this frame">Show only this frame '. $this->getImage('ic/plain-arrow-down.png') .'</a></div>';
        $args = ' src="'. $url .'" ';
        foreach($html_options as $key => $value) {
            $args .= ' '. $key .'="'. $value .'" ';
        }
        $html .= '<iframe '. $args .'></iframe>';
        echo $html;
    }
    
    function selectRank($id, $rank, $items, $html_options) {
        echo '<select ';
        foreach($html_options as $key => $value) {
            echo $key .'="'. $value .'"';
        }
        echo '>';
        echo '<option value="beginning">'. $GLOBALS['Language']->getText('global', 'at_the_beginning') .'</option>';
        echo '<option value="end">'. $GLOBALS['Language']->getText('global', 'at_the_end') .'</option>';
        foreach($items as $i => $item) {
            if ($item['id'] != $id) {
                echo '<option value="'. ($item['rank']+1) .'" '. (isset($items[$i + 1]) && $items[$i + 1]['id'] == $id ? 'selected="selected"' : '') .'>'. $GLOBALS['Language']->getText('global', 'after', $item['name']) .'</option>';
            }
        }
        echo '</select>';
    }
    
    function includeJavascriptFile($file) {
        $this->javascript[] = array('file' => $file);
    }
    function includeJavascriptSnippet($snippet) {
        $this->javascript[] = array('snippet' => $snippet);
    }
    function includeCalendarScripts() {
        $this->includeJavascriptSnippet("var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';");
        $this->includeJavascriptFile("/scripts/datepicker/datepicker.js");
    }

    function addFeed($title, $href) {
        $this->feeds[] = array('title' => $title, 'href' => $href);
    }
    
    function _getFeedback() {
        $feedback = '';
        if (trim($GLOBALS['feedback']) !== '') {
            $feedback = '<H3><span class="feedback">'.$GLOBALS['feedback'].'</span></H3>';
        }
        return $feedback;
    }
    
    function widget(&$widget, $layout_id, $readonly, $column_id, $is_minimized, $display_preferences, $owner_id, $owner_type) {
        echo '<div class="widget" id="widget_'. $widget->id .'-'. $widget->getInstanceId() .'">';
        echo '<div class="widget_titlebar '. ($readonly?'':'widget_titlebar_handle') .'">';
        echo '<div class="widget_titlebar_title">'. $widget->getTitle() .'</div>';
        if (!$readonly) {
            echo '<div class="widget_titlebar_close"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=widget&amp;name['. $widget->id .'][remove]='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'">'. $this->getImage('ic/close.png', array('alt' => 'X')) .'</a></div>';
            if ($is_minimized) {
                echo '<div class="widget_titlebar_maximize"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=maximize&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'">'. $this->getImage($this->_getTogglePlusForWidgets(), array('alt' => '+')) .'</a></div>';
            } else {
                echo '<div class="widget_titlebar_minimize"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=minimize&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'">'. $this->getImage($this->_getToggleMinusForWidgets(), array('alt' => '-')) .'</a></div>';
            }
            if (strlen($widget->getPreferences())) {
                echo '<div class="widget_titlebar_prefs"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=preferences&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;layout_id='. $layout_id .'">'. $GLOBALS['Language']->getText('widget', 'preferences_title') .'</a></div>';
            }
        }
        if ($widget->hasRss()) {
            echo '<div class="widget_titlebar_rss"><a href="/widgets/widget.php?owner='. $owner_type.$owner_id .'&amp;action=rss&amp;name['. $widget->id .']='. $widget->getInstanceId() .'">rss</a></div>';
        }
        echo '</div>';
        $style = '';
        if ($is_minimized) {
            $style = 'display:none;';
        }
        echo '<div class="widget_content" style="'. $style .'">';
        if (!$readonly && $display_preferences) {
            echo '<div class="widget_preferences">'. $widget->getPreferencesForm($layout_id, $owner_id, $owner_type) .'</div>';
        }
        echo $widget->getContent() .'</div>';
        echo '</div>';
    }
    function _getTogglePlusForWidgets() {
        return 'ic/toggle_plus.png';
    }
    function _getToggleMinusForWidgets() {
        return 'ic/toggle_minus.png';
    }

	// Box Top, equivalent to html_box1_top()
	function box1_top($title,$echoout=1,$bgcolor='',$cols=2){
        	$return = '<TABLE class="boxtable" cellspacing="1" cellpadding="5" width="100%" border="0">
                        <TR class="boxtitle" align="center">
                                <TD colspan="'.$cols.'"><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="'.$cols.'">';
	        if ($echoout) {
        	        print $return;
	        } else {
                	return $return;
        	}
	}

	// Box Middle, equivalent to html_box1_middle()
	function box1_middle($title,$bgcolor='',$cols=2) {
        	return '
                                </TD>
                        </TR>
    
                        <TR class="boxtitle">
                                <TD colspan="'.$cols.'"><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="'.$cols.'">';
	}

	// Box Bottom, equivalent to html_box1_bottom()
	function box1_bottom($echoout=1) {
        	$return = '
                </TD>
                        </TR>
        </TABLE>
';
	        if ($echoout) {
        	        print $return;
	        } else {
                	return $return;
        	}
	}

	function generic_header_start($params) {

            global $group_id,$Language;

	        if (!$params['title']) {
        	        $params['title'] = $GLOBALS['sys_name'];
	        } else {
        	        $params['title'] = $GLOBALS['sys_name'].": " . $params['title'];
	        }
                $sys_url=get_server_url();

        	?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/REC-html40/loose.dtd">

<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $Language->getEncoding(); ?>" />
    <TITLE><?php echo $params['title']; ?></TITLE>
    <?php $this->displayJavascriptElements() ?>
        <SCRIPT language="JavaScript">
        <!--
        function help_window(helpurl) {
                HelpWin = window.open(helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=740,width=1000');
		HelpWin.focus();
        }
        // -->
        </SCRIPT>
        <link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<?php
              if(isset($params['stylesheet']) && is_array($params['stylesheet'])) {
                  foreach($params['stylesheet'] as $css) {
                      print '<link rel="stylesheet" type="text/css" href="'.$css.'" />';
                      print "\n";
                  }
              }
?>
        <style type="text/css">
        <!--
        <?php
        $em = EventManager::instance();
            $em->processEvent("cssstyle", null);
        ?>
        //-->
        </style>
        
<?php
            $em->processEvent("cssfile", null);
        
?>
        <link rel="SHORTCUT ICON" href="<? echo util_get_image_theme("favicon.ico"); ?>">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','latest_news_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfnews.php" type="application/rss+xml">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','newest_releases_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfnewreleases.php" type="application/rss+xml">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','newest_projects_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfprojects.php?type=rss&option=newest" type="application/rss+xml">
<?php
                // If in a project page, add a project news feed
                if ($GLOBALS['group_id']) {
                    $project=project_get_object($GLOBALS['group_id']);
                    if (isset($params['toptab'])) {
                        $this->warning_for_services_which_configuration_is_not_inherited($GLOBALS['group_id'], $params['toptab']);
                    }
                    $project_feed='        <link rel="alternate" title="'.$project->getPublicName().' '.$Language->getText('include_layout','latest_news_rss').'" href="'.$sys_url.'/export/rss_sfnews.php?group_id='.$GLOBALS['group_id'].'" type="application/rss+xml">';
                }
                if (isset($project_feed)) {
                    echo $project_feed;
                }
        //Add additionnal feeds
        $hp =& CodeX_HTMLPurifier::instance();
        foreach($this->feeds as $feed) {
            echo '<link rel="alternate" title="'. $hp->purify($feed['title']) .'" href="'. $feed['href'] .'" type="application/rss+xml">';
        }
	}
    
    function displayJavascriptElements() {
        echo '<script type="text/javascript" src="/scripts/prototype/prototype.js"></script>'."\n";
        echo '<script type="text/javascript" src="/scripts/codendi/Tooltip.js"></script>'."\n";
        
        //TODO : move this to a dedicated & debug script
        echo <<<EOS
        <script type="text/javascript">
                document.observe('dom:loaded', function() {
            //reserved in opera
            var reserved_names = {
                firefox: 'hild,previousSibling,nextSibling,attributes,ownerDocument,insertBefore,'+
                         'replaceChild,removeChild,appendChild,hasChildNodes,cloneNode,normalize,'+
                         'isSupported,namespaceURI,prefix,localName,hasAttributes,tagName,'+
                         'getAttribute,setAttribute,removeAttribute,getAttributeNode,'+
                         'setAttributeNode,removeAttributeNode,getElementsByTagName,getAttributeNS,'+
                         'setAttributeNS,removeAttributeNS,getAttributeNodeNS,setAttributeNodeNS,'+
                         'getElementsByTagNameNS,hasAttribute,hasAttributeNS,ELEMENT_NODE,'+
                         'ATTRIBUTE_NODE,TEXT_NODE,CDATA_SECTION_NODE,ENTITY_REFERENCE_NODE,'+
                         'ENTITY_NODE,PROCESSING_INSTRUCTION_NODE,COMMENT_NODE,DOCUMENT_NODE,'+
                         'DOCUMENT_TYPE_NODE,DOCUMENT_FRAGMENT_NODE,NOTATION_NODE,id,title,lang,'+
                         'dir,className,elements,length,name,acceptCharset,action,enctype,method,'+
                         'target,submit,reset,encoding,offsetTop,offsetLeft,offsetWidth,offsetHeight,'+
                         'offsetParent,innerHTML,scrollTop,scrollLeft,scrollHeight,scrollWidth,'+
                         'clientHeight,clientWidth,tabIndex,blur,focus,spellcheck,style,'+
                         'removeEventListener,dispatchEvent,baseURI,compareDocumentPosition,textContent,'+
                         'isSameNode,lookupPrefix,isDefaultNamespace,lookupNamespaceURI,'+
                         'isEqualNode,getFeature,setUserData,getUserData,DOCUMENT_POSITION_DISCONNECTED,'+
                         'DOCUMENT_POSITION_PRECEDING,DOCUMENT_POSITION_FOLLOWING,'+
                         'DOCUMENT_POSITION_CONTAINS,DOCUMENT_POSITION_CONTAINED_BY,'+
                         'DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC',
                ie: 'language,scrollHeight,isTextEdit,currentStyle,document,onmouseup,oncontextmenu,'+
                    'isMultiLine,clientHeight,onrowexit,onbeforepaste,onactivate,scrollLeft,'+
                    'lang,onmousemove,onmove,onselectstart,parentTextEdit,oncontrolselect,'+
                    'canHaveHTML,onkeypress,oncut,onrowenter,onmousedown,onpaste,className,id,'+
                    'onreadystatechange,onbeforedeactivate,hideFocus,dir,isContentEditable,'+
                    'onkeydown,clientWidth,onlosecapture,parentElement,ondrag,ondragstart,'+
                    'oncellchange,recordNumber,onfilterchange,onrowsinserted,ondatasetcomplete,'+
                    'onmousewheel,ondragenter,onblur,onresizeend,onerrorupdate,onbeforecopy,'+
                    'ondblclick,scopeName,onkeyup,onresizestart,onmouseover,onmouseleave,outerText,'+
                    'innerText,onmoveend,tagName,title,offsetWidth,onresize,contentEditable,'+
                    'runtimeStyle,filters,ondrop,onpage,onrowsdelete,tagUrn,offsetLeft,clientTop,'+
                    'style,onfocusout,clientLeft,ondatasetchanged,canHaveChildren,ondeactivate,'+
                    'isDisabled,onpropertychange,ondragover,onhelp,ondragend,onbeforeeditfocus,'+
                    'disabled,onfocus,behaviorUrns,accessKey,onscroll,onbeforeactivate,onbeforecut,'+
                    'readyState,all,sourceIndex,onclick,scrollTop,oncopy,onfocusin,tabIndex,'+
                    'onbeforeupdate,outerHTML,innerHTML,ondataavailable,offsetHeight,onmovestart,'+
                    'onmouseout,scrollWidth,offsetTop,onmouseenter,onlayoutcomplete,offsetParent,'+
                    'onafterupdate,ondragleave,children,parentNode,nodeValue,name,length,onreset,'+
                    'onsubmit,lastChild,elements,attributes,acceptCharset,action,method,nodeType,'+
                    'target,previousSibling,ownerDocument,nodeName,childNodes,nextSibling,firstChild,'+
                    'encoding',
                opera: 'addEventListener,addRepetitionBlock,addRepetitionBlockByIndex,'+
                       'appendChild,attachEvent,blur,checkValidity,cloneNode,contains,'+
                       'detachEvent,dispatchEvent,dispatchFormChange,dispatchFormInput,'+
                       'focus,getAttribute,getAttributeNS,getAttributeNode,getAttributeNodeNS,'+
                       'getElementsByTagName,getElementsByTagNameNS,getFeature,hasAttribute,'+
                       'hasAttributeNS,hasAttributes,hasChildNodes,insertAdjacentElement,'+
                       'insertAdjacentHTML,insertAdjacentText,insertBefore,isDefaultNamespace,'+
                       'isSupported,item,lookupNamespaceURI,lookupPrefix,moveRepetitionBlock,'+
                       'namedItem,normalize,removeAttribute,removeAttributeNS,removeAttributeNode,'+
                       'removeChild,removeEventListener,removeNode,removeRepetitionBlock,'+
                       'replaceChild,reset,resetFromData,scrollIntoView,selectNodes,'+
                       'selectSingleNode,setAttribute,setAttributeNS,setAttributeNode,'+
                       'setAttributeNodeNS,submit,toString,accept,acceptCharset,action,'+
                       'all,attributes,childNodes,children,className,clientHeight,'+
                       'clientLeft,clientTop,clientWidth,contentEditable,currentStyle,'+
                       'data,dir,document,elements,encoding,enctype,firstChild,id,'+
                       'innerHTML,innerText,isContentEditable,lang,lastChild,length,'+
                       'localName,method,name,namespaceURI,nextSibling,nodeName,nodeType,'+
                       'nodeValue,offsetHeight,offsetLeft,offsetParent,offsetTop,offsetWidth,'+
                       'onblur,onclick,ondblclick,onfocus,onkeydown,onkeypress,onkeyup,onload,'+
                       'onmousedown,onmousemove,onmouseout,onmouseover,onmouseup,onunload,'+
                       'outerHTML,outerText,ownerDocument,parentElement,parentNode,prefix,'+
                       'previousSibling,repeatMax,repeatMin,repeatStart,repetitionBlocks,'+
                       'repetitionIndex,repetitionTemplate,repetitionType,replace,scrollHeight,'+
                       'scrollLeft,scrollTop,scrollWidth,sourceIndex,style,tagName,target,'+
                       'templateElements,text,textContent,title,unselectable'
            };
            $$('input').each(function (element) {
                if (element.name) {
                    var name = new RegExp('(^|\\\W)'+element.name+'(\\\W|$)');
                    var result = \$H(reserved_names).keys().inject([], function(result, browser) {
                        if (reserved_names[browser].match(name)) {
                            result.push(browser);
                        }
                        return result;
                    });
                    if (result.length) {
                        Element.setStyle(element, {
                                border:'5px dotted red'
                        });
                        alert('An <input> has the attribute name="'+element.name+'" which is reserved in '+result.join(' and '));
                    }
                }
            });
        });
        </script>
EOS;
        
        $em =& EventManager::instance();
        $em->processEvent("javascript_file", null);
        
        foreach ($this->javascript as $js) {
            reset($js);
            list($type, $content) = each($js);
            if ($type == 'file') {
                echo '<script type="text/javascript" src="'. $content .'"></script>'."\n";
            } else {
                echo '<script type="text/javascript">'. $content .'</script>';
            }
        }
        echo '<script type="text/javascript">
        ';
        $em->processEvent("javascript", null);
        echo '
        </script>';
    }
    
    function getDatePicker($id, $name, $value, $size = 10, $maxlength = 10) {
        $hp = CodeX_HTMLPurifier::instance();
        return '<input type="text" 
                       class="highlight-days-67 format-y-m-d divider-dash no-transparency" 
                       id="'.  $hp->purify($id, CODEX_PURIFIER_CONVERT_HTML)  .'" 
                       name="'. $hp->purify($name, CODEX_PURIFIER_CONVERT_HTML) .'" 
                       size="'. $hp->purify($size, CODEX_PURIFIER_CONVERT_HTML) .'" 
                       maxlength="'. $hp->purify($maxlength, CODEX_PURIFIER_CONVERT_HTML) .'" 
                       value="'. $hp->purify($value, CODEX_PURIFIER_CONVERT_HTML) .'">';
    }
    
    function warning_for_services_which_configuration_is_not_inherited($group_id, $service_top_tab) {
        $project=project_get_object($group_id);
        if ($project->isTemplate()) {
            switch($service_top_tab) {
            case 'admin':
            case 'forum':
            case 'docman':
            case 'cvs':
            case 'svn':
            case 'file':
            case 'tracker':
            case 'wiki':
            case 'salome':
                break;
            default:
                $this->addFeedback('warning', $GLOBALS['Language']->getText('global', 'service_conf_not_inherited'));
                break;
            }
        }
    }
    
	function generic_header_end($params) {
	?>
   </HEAD>
<?php
	}

	function generic_footer($params) {

        global $Language;
        include($Language->getContent('layout/footer'));
        	
        if ( user_ismember(1,'A') && $GLOBALS['DEBUG_MODE'] ) {
                $debug_compute_tile=microtime(true) - $GLOBALS['debug_time_start'];
                echo '<span class="debug">'.$Language->getText('include_layout','query_count').": ";
                echo $GLOBALS['DEBUG_DBPHP_QUERY_COUNT'] + $GLOBALS['DEBUG_DAO_QUERY_COUNT'];
                echo " (". $GLOBALS['DEBUG_DBPHP_QUERY_COUNT'] ." + ". $GLOBALS['DEBUG_DAO_QUERY_COUNT'] .")<br>";
                echo "Page generated in ".$debug_compute_tile." seconds</debug>\n";
                echo "<br>Queries:\n";
                
                // Display all queries
                //print_r($GLOBALS['QUERIES']);
                
                // Display queries executed more than once
                foreach ($GLOBALS['DBSTORE'] as $key => $value) {
                    if ($GLOBALS['DBSTORE'][$key]['nb'] > 1) {
                        echo "<br><legend>\n";
                        echo $GLOBALS['HTML']->getImage(
                        	'ic/toggle_plus.png', 
                            array(
                                'id' => "stacktrace_toggle_$key", 
                            	'style' => 'vertical-align:middle; cursor:hand; cursor:pointer;',
                            	'title' => addslashes($GLOBALS['Language']->getText('tracker_include_artifact', 'toggle'))
                            )
                        );
                        echo "<b>Run ".$GLOBALS['DBSTORE'][$key]['nb']." times: </b>";
                        echo $GLOBALS['DBSTORE'][$key]['sql']."\n";
                        echo '</legend>';
                        // Display available stacktraces
                        echo "<div id=\"stacktrace_alternate_$key\" style=\"\" ></div>";
                        echo "<script type=\"text/javascript\">Event.observe($('stacktrace_toggle_$key'), 'click', function (evt) { 
                var element = $('stacktrace_$key');
                if (element) {
                    Element.toggle(element);
                    Element.toggle($('stacktrace_alternate_$key'));
                    
                    //replace image
                    var src_search = 'toggle_minus';
                    var src_replace = 'toggle_plus';
                    if ($('stacktrace_toggle_$key').src.match('toggle_plus')) {
                        src_search = 'toggle_plus';
                        src_replace = 'toggle_minus';
                    }
                    $('stacktrace_toggle_$key').src = $('stacktrace_toggle_$key').src.replace(src_search, src_replace);
                }
                Event.stop(evt);
                return false;
            });
            $('stacktrace_alternate_$key').update('');
            </script>";
                        echo "<div id=\"stacktrace_$key\" style=\"display: none;\">";
                        for ($i=2; $i<=$GLOBALS['DBSTORE'][$key]['nb']; $i++) {
                            echo "<p>\n";
                            $traces = $GLOBALS['DBSTORE_BACKTRACE'][$key][$i];
                      	    foreach($traces as $trace) {
            	                echo '<code>'. $trace['file']. ' #'. $trace['line'] .' ('. $trace['class'] .'::'. $trace['function'] ."</code>\n<br />";
            	            }
       	     	            /*echo '<!-- ----------------------------------'."\n";
       	     	            var_dump($traces);
        	                echo ' -->';*/
                                                    }
                        echo "</div>";
                    }
                }
		echo "</pre>\n";
        }
          
        echo '</body>';
        echo '</html>';
	}

        function pv_header($params) {
	        $this->generic_header_start($params); 
                $this->generic_header_end($params); 
                echo '
<body class="bg_help">
';
                if(isset($params['pv']) && $params['pv'] < 2) {
                if (isset($params['title']) && $params['title']) {
                    echo '
<H2>'.$params['title'].' - '.format_date($GLOBALS['Language']->getText('system', 'datefmt'),time()).'</H2>
<HR>
';
                }
                }
        }

        function pv_footer($params) {
?>
</BODY>
</HTML>
<?php
}


	function header($params) {
	global $Language;
	
	        $this->generic_header_start($params); 

        	//themable someday?
	        $site_fonts='verdana,arial,helvetica,sans-serif';

        $this->generic_header_end($params); 
?>

<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0">
<?php


/*

        OSDN NAV BAR

*/
echo $this->getOsdnNavBar();

echo html_blankimage(5,100);
?>
<br>
<!-- start page body -->
<div align="center">
<table cellpadding="0" cellspacing="0" border="0" width="97%">

<!-- First line with borders and corners -->
           <tr>
               <td background="<? echo util_get_image_theme("upper_left_corner.png"); ?>" width="1%" height="26"><img src="<? echo util_get_image_theme("upper_left_corner.png"); ?>" width="16" height="26" alt=" "></td>
                <td background="<? echo util_get_image_theme("top_border.png"); ?>" align="left" colspan="3" width="99%"><a href="/"><img src="<? echo util_get_image_theme("codex_banner_lc.png"); ?>" border="0" alt="<?php echo $GLOBALS['sys_name'].' '.$Language->getText('include_layout','banner'); ?>"></a></td>
                <td><img src="<? echo util_get_image_theme("upper_right_corner.png"); ?>" width="16" height="26" alt=" "></td>
        </tr>


<!-- Second line with menus and content -->
        <tr>

                <td background="<? echo util_get_image_theme("left_border.png"); ?>" align="left" valign="bottom" alt=""><img src="<? echo util_get_image_theme("bottom_left_corner.png"); ?>" width="16" height="16" alt=""></td>

                <td colspan="3" >
<!-- start main body cell -->


        <div align="left">
        <table style=menus cellpadding="0" cellspacing="0" border="0" width="100%">

                <tr>
                    <td class="menuframe">

        <!-- VA Linux Stats Counter -->
        <?php
        if (!session_issecure()) {
                print '<IMG src="'.util_get_image_theme("clear.png").'" width=140 height=1 alt="'.$Language->getText('include_layout','counter').'"><BR>';
        } else {
                print html_blankimage(1,140) . '<br>';
        }
        ?>


        <!-- Company Logo here -->
        <P>
	<?php
	print '<center><IMG src="'.util_get_image_theme("organization_logo.png").'" alt="'.$GLOBALS['sys_org_name'].' '.$Language->getText('include_layout','logo').'"></center><BR>';
	?>

        <!-- menus -->
        <?php
        // html_blankimage(1,140);
        menu_print_sidebar($params);
        ?>
        <P>
        </TD>

        <td width="15" background="<? echo util_get_image_theme("fade.png"); ?>" nowrap>&nbsp;</td>
    
        <td class="contenttable">
        <BR>
<?php
        if (isset($params['group']) && $params['group']) {
            echo $this->project_tabs($params['toptab'],$params['group']);
        }
        echo $this->_getFeedback();
        $this->_feedback->display();
	}

    function feedback($feedback) {
        return '';
    }
    
	function footer($params) {
        if (!isset($params['showfeedback']) || $params['showfeedback']) {
            echo $this->_getFeedback();
        }
        ?>
        </div>
        <!-- end content -->
        </tr>
<!-- New row added for the thin black line at the bottom of the array -->
<tr><td background="<? echo util_get_image_theme("black.png"); ?>" colspan="4" align="center"><img src="<? echo util_get_image_theme("clear.png"); ?>" width="2" height="2" alt=" "></td> </tr>
        </table>

                </td>

                <td background="<? echo util_get_image_theme("right_border.png"); ?>" valign="bottom"><img src="<? echo util_get_image_theme("bottom_right_corner.png"); ?>" width="16" height="16" alt=" "></td>
        </tr>

</table>
</div>
<!-- themed page footer -->
<?php 
	$this->generic_footer($params);
	}



	function menuhtml_top($title) {
        	/*
                	Use only for the top most menu
	        */
        ?>
<table class="menutable">
        <tr>
                <td class="menutitle"><?php echo $title; ?><br></td>
        </tr>
        <tr>
                <td class="menuitem">
        <?php
	}


	function menuhtml_bottom() {
	        /*
        	        End the table
	        */
        	print '
                        <BR>
                        </td>
                </tr>
        </table>
';
	}

	function menu_entry($link, $title) {
        	print "\t".'<A class="menus" href="'.$link.'">'.$title.'</A> &nbsp;<img src="'.util_get_image_theme("point1.png").'" alt=" " width="7" height="7"><br>';
	}

        /*!     @function tab_entry
                @abstract Prints out the a themed tab, used by project_tabs
                @param  $url is the URL to link to
			$icon is the image to use (if the theme uses it) NOT USED
			$title is the title to use in the link tags
			$selected is a boolean to test if the tab is 'selected'
                @result text - echos HTML to the screen directly
        */
	function tab_entry($url, $icon='', $title='Home', $selected=0, $description=null) {
        	print '
                <A ';
	        if ($selected){
        	        print 'class=tabselect ';
	        } else {
        	        print 'class=tabs ';
	        }
                if (substr($url, 0, 1)!="/") {
                    // Absolute link -> open new window on click
                    print "target=_blank ";
                }
                if ($description) {
                    print "title=\"$description\" ";
                }
        	print 'href="'. $url .'">' . $title . '</A>&nbsp;|&nbsp;';
	}

	/*!     @function project_tabs
	        @abstract Prints out the project tabs, contained here in case
			we want to allow it to be overriden
	        @param 	$toptab is the tab currently selected ('short_name' of the service)
			$group is the group we should look up get title info
        	@result text - echos HTML to the screen directly
	*/
	function project_tabs($toptab,$group_id) {
		
	  global $sys_default_domain,$Language;
            
            // get group info using the common result set
            $project=project_get_object($group_id);
            if ($project->isError()) {
                //wasn't found or some other problem
                return;
            }

            print '<H2>'. $project->getPublicName() .' - ';
            
            if (isset($project->service_data_array[$toptab])) {
                echo $project->service_data_array[$toptab]['label'];
            }
            print '</H2>';

	    print '
        <P>
	<HR SIZE="1" NoShade>';
            $tabs = $this->_getProjectTabs($toptab, $project);
            foreach($tabs as $tab) {
                $this->tab_entry($tab['link'],$tab['icon'],$tab['label'],$tab['enabled'],$tab['description']);
            }

        	print '<HR SIZE="1" NoShade><P>';
	}

    function _getProjectTabs($toptab,&$project) {
      global $sys_default_domain;
        $tabs = array();
        $group_id = $project->getGroupId();
        reset($project->service_data_array);
        while (list($short_name,$service_data) = each($project->service_data_array)) {
            if ((string)$short_name == "admin") {
                // for the admin service, we will check if the user is allowed to use the service
                // it means : 1) to be a super user, or
                //            2) to be project admin
                if (!user_is_super_user()) {
                    if (!user_isloggedin()) {
                        continue;   // we don't include the service in the $tabs
                    } else {
                        if (!user_ismember($group_id, 'A')) {
                            continue;   // we don't include the service in the $tabs
                        }
                    }
                }
            }
            
            if (!$service_data['is_used']) continue;
            if (!$service_data['is_active']) continue;
            // Get URL, and eval variables
            //$project->services[$short_name]->getUrl(); <- to use when service will be fully served by satellite
            if ($service_data['is_in_iframe']) {
                $link = '/service/?group_id='. $group_id .'&amp;id='. $service_data['service_id'];
            } else {
                $link = $service_data['link'];
            }
            if ($group_id==100) {
                if (strstr($link,'$projectname')) {
                    // NOTE: if you change link variables here, change them also in src/common/project/RegisterProjectStep_Confirmation.class.php and src/www/project/admin/servicebar.php
                    // Don't check project name if not needed.
                    // When it is done here, the service bar will not appear updated on the current page
                    $link=str_replace('$projectname',group_getunixname($group_id),$link);
                }
                $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
                if ($GLOBALS['sys_force_ssl']) {
                    $sys_default_protocol='https'; 
                } else { $sys_default_protocol='http'; }
                $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
                $link=str_replace('$group_id',$group_id,$link);
            }
            $enabled = (is_numeric($toptab) && $toptab == $service_data['service_id']) || ($short_name && ($toptab == $short_name));
            $hp =& CodeX_HTMLPurifier::instance();
            $tabs[] = array('link'        => $link,
                            'icon'        => null,
                            'label'       => $hp->purify($service_data['label']),
                            'enabled'     => $enabled,
                            'description' => $hp->purify($service_data['description']));
        }
        return $tabs;
    }
    
    /**
     * Echo the search box
     */
    function searchBox() {
        global $words,$forum_id,$group_id,$is_bug_page,$is_support_page,$Language,
            $is_pm_page,$is_snippet_page,$exact,$type_of_search,$atid, $is_wiki_page;
        // if there is no search currently, set the default
        if ( ! isset($type_of_search) ) {
            $exact = 1;
        }
        
        $output = "\t<CENTER>\n";
        $output .= "\t<FORM action=\"/search/\" method=\"post\">\n";
        
        $output .= "\t<SELECT name=\"type_of_search\">\n";
        if ($is_bug_page && $group_id) {
            $output .= "\t<OPTION value=\"bugs\"".( $type_of_search == "bugs" ? " SELECTED" : "" ).">".$Language->getText('include_menu','bugs')."</OPTION>\n";
        } else if ($is_pm_page && $group_id) {
            $output .= "\t<OPTION value=\"tasks\"".( $type_of_search == "tasks" ? " SELECTED" : "" ).">".$Language->getText('include_menu','tasks')."</OPTION>\n";
        } else if ($is_support_page && $group_id) {
            $output .= "\t<OPTION value=\"support\"".( $type_of_search == "support" ? " SELECTED" : "" ).">".$Language->getText('include_menu','supp_requ')."</OPTION>\n";
        } else if ($group_id && $forum_id) {
            $output .= "\t<OPTION value=\"forums\"".( $type_of_search == "forums" ? " SELECTED" : "" ).">".$Language->getText('include_menu','this_forum')."</OPTION>\n";
        } else if ($group_id && $atid) {
            $output .= "\t<OPTION value=\"tracker\"".( $type_of_search == "tracker" ? " SELECTED" : "" ).">".$Language->getText('include_menu','this_tracker')."</OPTION>\n";
        } else if ($group_id && $is_wiki_page) {
            $output .= "\t<OPTION value=\"wiki\"".( $type_of_search == "wiki" ? " SELECTED" : "" ).">".$Language->getText('include_menu','this_wiki')."</OPTION>\n";
        }
        
        $output .= "\t<OPTION value=\"soft\"".( $type_of_search == "soft" ? " SELECTED" : "" ).">".$Language->getText('include_menu','software_proj')."</OPTION>\n";
        if ($GLOBALS['sys_use_snippet'] != 0) {
            $output .= "\t<OPTION value=\"snippets\"".( ($type_of_search == "snippets" || $is_snippet_page) ? " SELECTED" : "" ).">".$Language->getText('include_menu','code_snippets')."</OPTION>\n";
        }
        $output .= "\t<OPTION value=\"people\"".( $type_of_search == "people" ? " SELECTED" : "" ).">".$Language->getText('include_menu','people')."</OPTION>\n";

        $search_type_entry_output = '';
        $em =& EventManager::instance();
        $eParams = array('type_of_search' => $type_of_search,
                         'output'         => &$search_type_entry_output);
        $em->processEvent('search_type_entry', $eParams);      
        $output .= $search_type_entry_output;

        $output .= "\t</SELECT>\n";
        
        $output .= "\t<BR>\n";
        $output .= "\t<INPUT TYPE=\"CHECKBOX\" NAME=\"exact\" VALUE=\"1\"".( $exact ? " CHECKED" : " UNCHECKED" )."> ".$Language->getText('include_menu','require_all_words')." \n";
        
        $output .= "\t<BR>\n";
        if ( isset($atid) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$atid\" NAME=\"atid\">\n";
        } 
        if ( isset($forum_id) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$forum_id\" NAME=\"forum_id\">\n";
        } 
        if ( isset($is_bug_page) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_bug_page\" NAME=\"is_bug_page\">\n";
        }
        if ( isset($is_support_page) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_support_page\" NAME=\"is_support_page\">\n";
        }
        if ( isset($is_pm_page) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_pm_page\" NAME=\"is_pm_page\">\n";
        }
        if ( isset($is_snippet_page) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_snippet_page\" NAME=\"is_snippet_page\">\n";
        }
	if ( isset($is_wiki_page) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_wiki_page\" NAME=\"is_wiki_page\">\n";
        }
        if ( isset($group_id) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$group_id\" NAME=\"group_id\">\n";
        }
        
        $output .= '<INPUT TYPE="text" SIZE="16" NAME="words" VALUE="'. htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8') .'">';
        $output .= "\t<BR>\n";
        $output .= "\t<INPUT TYPE=\"submit\" NAME=\"Search\" VALUE=\"".$Language->getText('include_menu','search')."\">\n";
        $output .= "\t</FORM>\n";
        $output .= "\t</CENTER>\n";
        echo $output;
    }
    
    //diplaying search box in body
    function bodySearchBox() {
    	$this->searchBox();
    }
    
    
    function getOsdnNavBar() {
        $output = '
        <!-- OSDN navbar -->
        <div class="osdnnavbar">
        ';
        
        $motd = $GLOBALS['Language']->getContent('others/motd');
        if (!strpos($motd,"empty.txt")) { # empty.txt returned when no motd file found
            include($motd);
        } else {
            // MN : Before displaying the osdn nav drop down, we verify that the osdn_sites array exists
            include($GLOBALS['Language']->getContent('layout/osdn_sites'));
            if (isset($osdn_sites)) {
                $output .= '<span class="osdn">'.$GLOBALS['Language']->getText('include_layout','network_gallery').'&nbsp;:&nbsp;';
                // if less than 5 sites are defined, we only display the min number
                $output .= $this->_getOsdnRandpick($osdn_sites, min(5, count($osdn_sites)));
                $output .= '</span>';
            }
        }

        $output .= '</div>
        <!-- End OSDN NavBar -->
        ';
        return $output;
    }
    
    function _getOsdnRandpick($sitear, $num_sites = 1) {
        $output = '';
        shuffle($sitear);
        reset($sitear);
        $i = 0;
        while ( ( $i < $num_sites ) && (list($key,$val) = each($sitear)) ) {
            list($key,$val) = each($val);
            $output .= "&nbsp;&middot;&nbsp;<a href='$val' class='osdntext'>$key</a>\n";
            $i++;
        }
        $output .= '&nbsp;&middot;&nbsp;';
        return $output;
    }
    
    function getOsdnNavDropdown() {
        $output = '
        <!-- OSDN navdropdown -->
	    <script type="text/javascript">
	    function handle_navbar(index,form) {
	        if ( index > 1 ) {
	            window.location=form.options[index].value;
	        }
	    }
	    </script>';
        $output .= '<a href="'.get_server_url().'" class="osdn_codex_logo">';
        $output .= $this->getImage("codex_logo.png", array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0"));
        $output .= '<br /></a>';
        // MN : Before displaying the osdn nav drop down, we verify that the osdn_sites array exists
        include($GLOBALS['Language']->getContent('layout/osdn_sites'));
        if (isset($osdn_sites)) {
            $output .= '<form name="form1"><div>';
            $output .= '<select name="navbar" onChange="handle_navbar(selectedIndex,this)">';
            $output .= '   <option>------------</option>';
            reset ($osdn_sites);
            while (list ($key, $val) = each ($osdn_sites)) {
                list ($key, $val) = each ($val);
                $output .= '   <option value="'.$val.'">'.$key.'</option>';
            }
            $output .= '</select>';
            $output .= '</div></form>';
        }
        $output .= '<!-- end OSDN navdropdown -->';
        
        return $output;
    }
    
    function getImage($src,$args = array()) {
        GLOBAL $img_size;
        $return = '<img src="'.util_get_dir_image_theme().$src.'"';
        reset($args);
        while(list($k,$v) = each($args)) {
            $return .= ' '.$k.'="'.$v.'"';
        }
        
        // ## insert a border tag if there isn't one
        if (!isset($args['border']) || !$args['border']) $return .= (" border=0");
        
        // ## if no height AND no width tag, insert em both
        if ((!isset($args['height']) || !$args['height']) && 
                (!isset($args['width'])  || !$args['width'])) {
            /* Check to see if we've already fetched the image data */
            if($img_size){
                        if((!isset($img_size[$src]) || !$img_size[$src]) && is_file($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src)){
                    $img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src);
                }
            } else {
                if(is_file($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src)){		
                    $img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src);
                }
            }
            $return .= ' ' . $img_size[$src];
        }
        
        // ## insert alt tag if there isn't one
        if (!isset($args['alt']) || !$args['alt']) $return .= " alt=\"$src\"";
        
        $return .= ('>');
        return $return;
    }
}
?>
