<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
//set_error_handler("error_function");
error_reporting(-1);
session_start();
if (!isset($_SESSION['admin'])) $_SESSION['admin'] = false;
$blogdir = "/var/cache/system-msg";
$sqlUsername = '<sql username>';
$sqlPassword = '<sql password>';
$sqlHost = 'localhost';
$sqlDatabase = '<sql db>';
$postsPerPage = 5;
$adminUsername = '<admin username>';
$adminPassword = '<admin password>';
/* mysql table created with
CREATE TABLE blog_comments (id int(10) unsigned NOT NULL AUTO_INCREMENT,name TEXT NOT NULL,content TEXT NOT NULL,blogPostId TEXT NOT NULL,ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
*/

spl_autoload_register(function($class){
	require preg_replace('{\\\\|_(?!.*\\\\)}', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
});
use \Michelf\Markdown;
function markdown($s) {
	$sp = explode("\n",$s);
	for ($i=0;$i<count($sp);$i++) {
		if (preg_match("/^[\/\\\\ _´`x',]+$/i",$sp[$i])) {
			$sp[$i] = str_replace("_","&#95;",$sp[$i]);
			$sp[$i] = str_replace(" ","&nbsp;",$sp[$i]);
			$sp[$i] = str_replace("`","&#96;",$sp[$i]);
			$sp[$i] = str_replace("\\","&#92;",$sp[$i]);
		}
		/*if (preg_match("/^ [^\w]/i",$sp[$i])) {
			var_dump(substr($sp[$i],1));
			$sp[$i] = "&nbsp;".substr($sp[$i],1);
		}*/
	}
	$s = implode("\n",$sp);
	$s = trim(preg_replace("/(?<!\#)\n/", "  \n", $s), "\n");
	$s = Markdown::defaultTransform($s);
	$s = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@','<a href="$1">$1</a>',$s);
	$s = str_replace("  ","&nbsp;&nbsp;",$s);
	$s = str_replace("\n ","&nbsp;",$s);
	return $s;
}
function htmlEscape($s) {
	$s = htmlspecialchars($s);
	return str_replace("\r","",str_replace("\n","<br>",stripslashes($s)));
}
function connectSQL($sql_user,$sql_password,$sql_server,$sql_db) {
	$sqlConnection = mysql_pconnect($sql_server,$sql_user,$sql_password);
	if (!$sqlConnection) die("Could not connect to SQL DB: ".mysql_error());
	if (!mysql_select_db($sql_db,$sqlConnection)) die("Could not select DB: ".mysql_error());
	return $sqlConnection;
}
function sql_query() {
	global $sqlUsername,$sqlPassword,$sqlHost,$sqlDatabase;
	$sqlConnection = connectSQL($sqlUsername,$sqlPassword,$sqlHost,$sqlDatabase);
	$params = func_get_args();
	$query = $params[0];
	$args = Array();
	for ($i=1;$i<count($params);$i++) $args[$i-1] = mysql_real_escape_string($params[$i],$sqlConnection);
	$result = mysql_query(vsprintf($query,$args),$sqlConnection);
	if (!$result) die(mysql_error() . "Query: " . vsprintf($query,$args));
	return $result;
}
function error_function($error_level, $error_message, $error_file, $error_line, $error_context) {
  $res = "<h1>Error</h1><table>";
  $res .= "<tr><th>error_level:</th><td>$error_level</td></tr>";
  $res .= "<tr><th>error_message:</th><td>$error_message</td></tr>";
  $res .= "<tr><th>error_file:</th><td>$error_file</td></tr>";
  $res .= "<tr><th>error_line:</th><td>$error_line</td></tr>";
  $res .= "<tr><th>error_context:</th><td>$error_context</td></tr></table>";
  echo $res;
  die();
}

/*require_once('./markdown_extended.php');
function markdown($s) {
	return MarkdownExtended($s,array('pre' => 'prettypring'));
}*/

function getHeader($title) {
	$s = "<!DOCTYPE html>\n<html><head><title>$title</title><meta charset=\"UTF-8\">";
	if (!isset($_GET['nocss']) && !isset($_GET['stream'])) {
	$s .= "<style type='text/css'>
		body {
			background-color:black;
			margin:0;
			padding:0;
			color:#999;
			font-family:monospace;
		}
		body > * {
			width:95%;
			margin:0 auto;
		}
		.blogpostcont,.pagebuttons,.backButton,#adminLogin {
			background-color:#383838;
			border:5px solid #383838;
			border-radius:5px;
			margin-top:8px;
			margin-bottom:8px;
		}
		.blogcomments > h4 {
			margin-top:3px;
			margin-bottom:0;
		}
		.comment {
			border:1px solid black;
		}
		.pagebuttonsouterdiv {
			text-align:right;
		}
		.pagebuttons {
			padding:0;
			display:inline-block;
		}
		.pageButton {
			border:2px solid black;
			border-radius:2px;
			margin: 0 2px;
			background-color:black;
		}
		a:link, a:visited, a:hover, a:active, a {
			color:#81bf29;
			text-decoration:none;
		}
		a:hover {
			text-decoration:underline;
		}
		hr {
			margin:0 auto;
			padding:0;
			border:0;
			background-color:#999;
			height:1px;
		}
		input,textarea {
			background-color:#171717;
			color:#999;
			border:1px solid black;
		}
		input[type=submit] {
			border:3px solid #171717;
			border-radius:3px;
		}
		input[type=submit]:hover {
			background-color:#1F1F1F;
			border-color:#1F1F1F;
			cursor:pointer;
		}
		#header {
			font-size:50px;
			font-weight:bold;
			text-align:center;
		}
		#footer {
			margin-bottom:5px;
		}
	</style>";}
	$s .= "</head><body>";
	if (!isset($_GET['stream'])) $s .= "<div id='header'>Withgusto Server Updates</div>";
	return $s;
}
function nth_strpos($str, $substr, $n, $stri = false)
{
    if ($stri) {
        $str = strtolower($str);
        $substr = strtolower($substr);
    }
    $ct = 0;
    $pos = 0;
    while (($pos = strpos($str, $substr, $pos)) !== false) {
        if (++$ct == $n) {
            return $pos;
        }
        $pos++;
    }
    return false;
}
class BlogPost {
	private $timestamp;
	private $creator;
	private $content;
	private $title;
	public function __construct($t,$cr,$co) {
		$this->timestamp = $t;
		$this->creator = $cr;
		$coparts;
		$coparts = explode("\n",$co);
		//var_dump($coparts);
		if (count($coparts)>2 && preg_match("/^(\=)*$/i",$coparts[1])) {
			$this->title = $coparts[0];
			unset($coparts[0]);
			unset($coparts[1]);
		} else {
			$this->title = "Server Update - ".date("n/j/Y",$t);
		}
		$this->content = implode("\n",$coparts);
	}
	private function getCommentsHTML($num) {
		$s = "<hr><div class='blogcomments'>";
		$s .= "<h4>Comments</h4>";
		if ($num!=-1)
			$posts = sql_query("SELECT * FROM blog_comments WHERE blogPostId LIKE '%s' ORDER BY id DESC LIMIT 0,%s",$this->timestamp,$num);
		else
			$posts = sql_query("SELECT * FROM blog_comments WHERE blogPostId=%s ORDER BY id DESC",$this->timestamp,$num);
		if (!$posts)
			$s .= "<b>No comments yet</b>";
		else {
			$s .= "<div style='padding:0;margin:0;font-size:0.8em;'>";
			while($row = mysql_fetch_array($posts)) {
				$timestamp = strtotime($row['ts']);
				$s .= "<div class='comment'><b>".htmlEscape($row['name'])."</b> (".date("l, F jS, Y",$timestamp)." at ".date("g:i:s A T",$timestamp).")";
				if ($_SESSION['admin']===true) $s .= "&nbsp;<a href='".$_SERVER['SCRIPT_NAME']."?delete&id=".$row['id']."'>Delete</a>";
				$s .= "<p>".htmlEscape($row['content'])."</p></div>";
			}
			$s .= "</div>";
		}
		if ($num==-1) {
			require_once('recaptchalib.php');
			$s .= "<div class='postComment'><h4>Write Comment</h4>";
			$s .= "<script type='text/javascript'>var RecaptchaOptions = {theme : 'blackglass'};</script>";
			$s .= "<form action='".$_SERVER['SCRIPT_NAME']."?post=".$this->timestamp."' method='post'>";
			$s .= "Name:<input name='name' type='text' value='Guest' maxlength='50'><br>";
			$s .= "<span style='display:none;'>Email:<input name='email' type='text'></span>";
			$s .= "Comment:<br><textarea name='comment' style='height:100px;width:90%' onkeyup=\"document.getElementById('number').innerHTML=this.value.length+'/500'\" maxlength='500'></textarea><br><span id='number'>0/500</span>";
			$s .= recaptcha_get_html("6LeUIucSAAAAAEq8VEkjuwfmKNe6di6nwN0l6B3Z"); //ze public key :D
			$s .= "<input type='submit' value='post'>";
			$s .= "</form></div>";
		}
		$s .= "</div>";
		return $s;
	}
	public function getHTML($length,$numComments = 4) {
		$s="<div><div class='blogpostcont'><div class='blogpost'><h1><a href='".$_SERVER['SCRIPT_NAME']."?post=".$this->timestamp."'>".$this->title."</a> <small style='font-size:10px;'>by ".ucfirst($this->creator)."</small></h1>";
		$s .= "<small>".date("l, F jS, Y",$this->timestamp)." at ".date("g:i:s A T",$this->timestamp)."</small>\n";
		$content = $this->content;
		$trimmed = false;
		if ($length!=-1 && ($pos=nth_strpos($content," ",$length))!==false) {
			$co = explode("\n",$content);
			$prevLine = ".";
			for ($i=0;$i<count($co);$i++) {
				if (preg_match("/^[\s]*[^a-zA-Z\d\s=][^a-zA-Z\d=]+[a-zA-Z\d=]*(.)*$/i",$co[$i]) || (trim($co[$i])=="" && trim($prevLine)=="")) {
					unset($co[$i]);
					$prevLine = "";
				} else
					$prevLine = $co[$i];
			}
			$content = implode("\n",$co);
			preg_match("/[\.!?]+/i",$content,$matches,PREG_OFFSET_CAPTURE,$pos-1);
			if ($matches) {
				$content = substr($content,0,$matches[0][1]+strlen($matches[0][0]))." [...]";
				$trimmed = true;
			}
		}
		$content = markdown($content);
		$s .= $content;
		if ($trimmed) $s .= "<small><a href='".$_SERVER['SCRIPT_NAME']."?post=".$this->timestamp."'>Full Story</a></small>";
		$s .= "</div>";
		if ($numComments!=0)$s .= $this->getCommentsHTML($numComments);
		$s .= "</div></div>";
		return $s;
	}
	public function getHTMLbyId($id) {
		if ($id==$this->timestamp) return getHeader($this->title)."<div><div class='backButton'><a href='".$_SERVER['SCRIPT_NAME']."'>Back</a></div></div>".$this->getHTML(-1,-1);
		return "";
	}
}
$blogPosts = Array();
$blogFiles = Array();
if ($handle = opendir($blogdir)) {
	while(($file = readdir($handle))!==false) {
		$blogFiles[] = $file;
		//$parameters = explode(" ",$file);
		//echo file_get_contents($blogdir."/".$file)."\n\n\n";
	}
	arsort($blogFiles);
	foreach ($blogFiles as $file) {
		$parameters = explode(" ",$file);
		$content = file_get_contents($blogdir."/".$file);
		if ($content!="") {
			$blogPosts[] = new BlogPost($parameters[0],$parameters[1],file_get_contents($blogdir."/".$file));
		}
	}
	if (isset($_GET['post'])) {
		$id = $_GET['post'];
		if (isset($_POST['comment'])) {
			if ($_POST['email']!="") {
				echo "you are a spam-bot subject";
			} else {
				require_once('recaptchalib.php');
				$resp = recaptcha_check_answer("6LeUIucSAAAAAEr4n9-2X4TaeqEa3OlbJ_aVSRWV",$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
				if (!$resp->is_valid) {
					echo "reCAPTCHA said: ".$resp->error;
				} else if (strlen($_POST['comment']>500) || strlen($_POST['name'])>50 || $_POST['comment']==""){
					echo "input stuff too long/too short";
				} else {
					sql_query("INSERT INTO blog_comments (name, content, blogPostId) VALUES ('%s','%s','%s')",$_POST['name'],$_POST['comment'],$id);
				}
			}
		}
		foreach ($blogPosts as $post) {
			echo $post->getHTMLbyId($id);
		}
	} else if (isset($_GET['full'])) {
		echo getHeader("Withgusto Server Updates");
		foreach ($blogPosts as $post) {
			echo $post->getHTML(-1);
		}
	} else {
		if (isset($_GET['login']) && $_POST['usr']==$adminUsername && $_POST['pwd']==$adminPassword)
			$_SESSION['admin'] = true;
		if (isset($_GET['delete']) && $_SESSION['admin']===true)
			sql_query("DELETE FROM blog_comments WHERE id='%s'",$_GET['id']);
		if (isset($_GET['page'])) $offset = ($_GET['page']-1)*$postsPerPage; else $offset = 0;
		if ($offset<0) $offset = 0;
		$pagesHTML = "<div class='pagebuttonsouterdiv'><div class='pagebuttons'>Browse Pages:";
		if ($offset!=0)
			$pagesHTML .= "<span><a href='".$_SERVER['SCRIPT_NAME']."?page=".((($offset-$postsPerPage)/$postsPerPage)+1)."'><span class='pageButton'>«</span></a></span>";
		else
			$pagesHTML .= "<span><b><span class='pageButton'>«</span></b></span>";
		for ($i=0;$i<sizeof($blogPosts);$i+=$postsPerPage) {
			if ($i==$offset)
				$pagesHTML .= "<span><b><span class='pageButton'>".(($i/$postsPerPage)+1)."</span></b></span>";
			else
				$pagesHTML .= "<span><a href='".$_SERVER['SCRIPT_NAME']."?page=".(($i/$postsPerPage)+1)."'><span class='pageButton'>".(($i/$postsPerPage)+1)."</span></a></span>";
		}
		$i -= $postsPerPage;
		if ($i!=$offset)
			$pagesHTML .= "<span><a href='".$_SERVER['SCRIPT_NAME']."?page=".(($i/$postsPerPage)+1)."'><span class='pageButton'>»</span></a></span>";
		else
			$pagesHTML .= "<span><b><span class='pageButton'>»</span></b></span>";
		$pagesHTML .= "</div></div>";
		echo getHeader("Withgusto Server Updates");
		if (!isset($_GET['stream'])) echo $pagesHTML;
		for ($i=$offset;(($i<($offset+$postsPerPage)) && ($i < sizeof($blogPosts)));$i++) {
			if (!isset($_GET['tiny']) && !isset($_GET['stream']))
				echo $blogPosts[$i]->getHTML(150);
			else
				echo $blogPosts[$i]->getHTML(50,0);
		}
		if (!isset($_GET['stream'])) echo $pagesHTML;
	}
} else {
	echo "NUUUU, i just don't know what went WROOOOOOOOOONG!";
}
if (isset($_GET['stream']))die("</body></html>");
?>
<div><div id='adminLogin'><h4>Admin Login</h4>
<form action=<?php echo "'".$_SERVER['SCRIPT_NAME']."?login'"; ?> method="post">
Username: <input type="text" name="usr"><br>
Password: <input type="password" name="pwd"><br>
<input value="Login" type="submit">
</form></div></div>
<hr>
<div id="footer"><small>system-msg written by <a target="_blank" href="http://eeems.ca">Eeems</a> and tweaked by alberthrocks for withgusto Networks.<br>
system-msg blog software written by <a target="_blank" href="http://www.sorunome.de">Sorunome</a>.<br>
<?php
echo "<a target='_blank' href='http://validator.w3.org/check?uri=".urlencode("http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])."'>";
?>This page is 100% valid HTML5.</a><br>
Page created successfully.</small></div>
</body>
</html>
