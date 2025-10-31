<?php
if (!defined("INDEXED")) exit;

include("css-parse.php");

$UserThemes = getExtensionName(__DIR__);
$currentTheme = $config["forumTheme"];

$validThemes = glob("themes/*");
foreach ($validThemes as &$v) { $v = pathinfo($v, PATHINFO_FILENAME); }


if (strlen($extension_config[$UserThemes]["theme_whitelist"])) {
    $whitelist = explode("\n",$extension_config[$UserThemes]["theme_whitelist"]);
    foreach ($whitelist as &$w) {
        $w = trim($w);
    }
    $validThemes = array_intersect($validThemes,$whitelist);
}

$userthemefield = $db->query("SHOW COLUMNS FROM `users` WHERE field LIKE 'theme'");
if ($userthemefield->num_rows < 1)
{
    $db->query("ALTER TABLE `users` ADD `theme` varchar(1024) DEFAULT NULL");
}

if (isset($_SESSION["signed_in"]) && $_SESSION["signed_in"] == true) {
    $dbuserTheme = $db->query("SELECT theme FROM users WHERE userid='" . $db->real_escape_string($_SESSION["userid"]) . "'");
    $row = $dbuserTheme->fetch_assoc();
    $currentTheme = $row["theme"];
}
else {
    if (isset($_SESSION["theme"])) $currentTheme = $_SESSION["theme"];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST["usertheme"]) && in_array($_POST["usertheme"], $validThemes)) {
        if (isset($_SESSION["signed_in"]) && $_SESSION["signed_in"] == true) {
            $db->query("UPDATE users SET theme='" . $db->real_escape_string($_POST["usertheme"]) . "' WHERE userid='" . $db->real_escape_string($_SESSION["userid"]) . "'");
        }
        else {
            $_SESSION["theme"] = $_POST["usertheme"];
        }
        $currentTheme = $_POST["usertheme"];
    }
}

if (!in_array($currentTheme, $validThemes)) {$currentTheme = $config["forumTheme"];}

$themeSelector = '<form method="post" style="margin-top:1px"><select name="usertheme" id="usertheme" aria-label="theme" onchange="this.form.submit()">';
foreach ($validThemes as $t) {
    if (isset($currentTheme) && $t == $currentTheme) $selected = "selected='' ";
    else $selected = "";
    $themeSelector .= '<option ' . $selected . 'value="' . $t . '">' . $t . '</option>';
}
$themeSelector .= '</select></form>';

function userThemeAddThemeSelector($args) {
    global $data, $themeSelector;
    $data["language"] = $data["language"] . "<div>" . $themeSelector;
}

function userThemeChangeStyleSheet($args) {
    global $data, $currentTheme, $extension_config, $UserThemes, $config, $db;
    $data["stylesheet"] = genURL('themes/' . $currentTheme . '/style.css?v=0.1');
    if (isset($_SESSION["signed_in"]) && $_SESSION["signed_in"] == true && $extension_config[$UserThemes]["use_user_color_as_theme_color"]) {
        
        $usercolor = $config["forumColor"];
        $ucolor = $db->query("SELECT color FROM users WHERE userid='" . $db->real_escape_string($_SESSION["userid"]) . "'");
        $row = $ucolor->fetch_assoc();
        $usercolor = getColorFromIndex($currentTheme,$row["color"]);
        
        if ($usercolor != NULL) {
            $grTop = hexAdjustLight($usercolor, 0.9);
            $grMedium = hexAdjustLight($usercolor, 1);
            $grBottom = hexAdjustLight($usercolor, -0.4);
            $grBottomDarker = hexAdjustLight($usercolor, -0.6);
            $grHighlight = hexAdjustLight($usercolor, 0.4);
            $grBorder = hexAdjustLight($usercolor, -0.8);
            $data["color"] = "<style>
            :root {
            --c-gradient-top: " . $grTop .";
            --c-gradient-medium: " . $grMedium .";
            --c-gradient-bottom: " . $grBottom .";
            --c-gradient-bottom-darker: " . $grBottomDarker .";
            --c-highlight: " . $grHighlight .";
            --c-border: " . $grBorder .";
            }
            </style>";
        }
    }
}

function userThemeChangeTemplatePath($args) {
    global $currentTheme, $config;
    $oldpath = $args[0];
    if (strpos($args[0],$config["forumTheme"])) {
        $args[0] = substr_replace($args[0],$currentTheme,strpos($args[0],$config["forumTheme"]),strlen($config["forumTheme"]));
    }
    else {
        $args[0] = "themes/" . $currentTheme . "/" . $args[0];
    }
    if (!file_exists($args[0])) $args[0] = $oldpath;
}

hook("beforeFooter", "userThemeAddThemeSelector");
hook("meta", "userThemeChangeStyleSheet");
hook("beforeTemplateRender", "userThemeChangeTemplatePath");

?>
