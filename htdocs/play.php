<?php
require_once('common.inc.php');

$fugitive           = $_REQUEST['Fugitive'];
$fugitive_args      = $_REQUEST['FugitiveArgs'];
$detectives         = $_REQUEST['Detectives'];
$detectives_args    = $_REQUEST['DetectivesArgs'];
$map                = $_REQUEST['Map'];

$title = $fugitive.' (fugitive) vs '.$detectives.' (detectives)';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>On The Run - <?php echo $title ?></title>
<base href="http://<?php echo $_SERVER['SERVER_NAME'], $_SERVER['PHP_SELF']; ?>" />
<link rel="StyleSheet" href="arbiter.css" title="Arbiter" type="text/css" />
</head>
<body>
<script type="text/javascript">
function popup(url, width, height)
{
    x = parseInt((screen.width - width)/2);
    y = parseInt((screen.height - height)/2);
    return window.open( url, '_blank',
        'left='+x+',top='+y+',width='+width+',height='+height+
        ',location=0,menubar=0,resizable=1,scrollbars=0,status=0,titlebar=1,toolbar=0' );
}
</script>
<?php

if( empty($fugitive)   || $fugitive   != basename($fugitive) ||
    empty($detectives) || $detectives != basename($detectives) )
{
?><h3>Players incorrectly specified!</h3><?php
}
else
if(!in_array($map, $MAPS))
{
?><h3>Map not found: <b><?php echo htmlentities($map); ?></b>!</h3><?php
}
else
{
?><h1><span class="title"><?php echo $title ?></span></h1><?php


    $worldfile      = LOCALBASE.'/maps/'.$map;
    $fugitive_dir   = LOCALBASE.'/players/'.$fugitive;
    $detectives_dir = LOCALBASE.'/players/'.$detectives;


    $command1 = is_file($fugitive_dir.'/run') ?
                sprintf( '%s/execute_player %s %s', LOCALBASE,
                         escapeshellarg($fugitive_dir), escapeshellcmd($fugitive_args) )
                : ''; // '/usr/local/jdk1.3.1/bin/java -cp '.escapeshellarg($red_dir).' '.escapeshellarg($red);

    $command2 = is_file($detectives_dir.'/run') ?
                sprintf( '%s/execute_player %s %s', LOCALBASE,
                         escapeshellarg($detectives_dir), escapeshellcmd($detectives_args) )
                : ''; // '/usr/local/jdk1.3.1/bin/java -cp '.escapeshellarg($red_dir).' '.escapeshellarg($red);


    $command =  sprintf( '/usr/local/bin/python "'.LOCALBASE.'/arbiter.py" %s %s %s 2>&1', escapeshellarg($worldfile),
                         escapeshellarg($command1), escapeshellarg($command2) );
                         
    // Add log file
    $logfile = sprintf( '%s/logs/%s - %s vs %s on %s', LOCALBASE, date('Y-m-d H:m:s'), $fugitive, $detectives, $map );
    $command = sprintf( '/usr/bin/script -q %s %s', escapeshellarg($logfile), $command );

    // NOTE: unlink/link order matters, in case $fugitive_dir == $detective_dir!
    @unlink($fugitive_dir.'/connect.txt');
    link($worldfile, $fugitive_dir.'/connect.txt');
    @unlink($detectives_dir.'/connect.txt');
    link($worldfile, $detectives_dir.'/connect.txt');
}

$comments_fugitive   = '';
$comments_detectives = '';
$messages            = '';
$params              = array(
    'fugitive'      => $fugitive,
    'detectives'    => $detectives,
    'map'           => $map );

if(isset($command))
{
    flush();
    $fp = popen($command, 'r');
    while(($line = fgets($fp)) !== FALSE)
    {
        if(substr($line, 0, 10) == 'Fugitive> ')
            $comments_fugitive .= substr($line, 10);
        else
        if(substr($line, 0, 12) == 'Detectives> ')
            $comments_detectives .= substr($line, 12);
        else
        if(preg_match('/^(\\w+)=(.*)$/', $line, $matches))
            $params[$matches[1]] = rtrim($matches[2]);
        else
            $messages .= $line;
    }
}
?>

<!-- Game Overview -->
<div class="box">
<h2>Game overview</h2>

<table>
<tr><td class="heading">Map</th><td><?php echo htmlentities($map); ?></td></tr>
<tr><td class="heading">Fugitive</th><td><?php
    echo htmlentities($fugitive);
    if($fugitive_args)
        echo ' <i>', htmlentities($fugitive_args), '</i>';
?></td></tr>
<tr><td class="heading">Detectives</th><td><?php
    echo htmlentities($detectives);
    if($detectives_args)
        echo '  <i>', htmlentities($detectives_args), '</i>';
?></td></tr>
<tr><td class="heading" valign="top">Outcome</th><td><?php echo $params['result']; ?><br />
<?php echo $params['resultDesc']; ?></td></tr></table>
<?php
    if(!empty($params['moves']) && strlen($params['moves']) > 0)
    {
        echo '<a href="game.php?';
        foreach($params as $key => $value)
            echo 'Params[', rawurlencode($key), ']=', rawurlencode($value), '&amp;';
        echo '?>" target="_blank" onclick="return !popup(this.href, 1000, 650)">';
        echo 'View Game in Applet</a>';
    }
?></div>

<!-- Messages -->
<?php
function output_box($title, $text, $show = TRUE)
{
    global $box_id;
    ++$box_id;
    if(empty($text))
        return;
?><div class="box"><h2><?php echo htmlentities($title) ?>&nbsp;
<input type="checkbox" <?php if($show) echo 'checked="checked"'  ?> onchange="document.getElementById('box<?php
    echo $box_id ?>').style.display = (this.checked ? 'block' : 'none');"/></h2>
<div <?php if(!$show) echo 'style="display: none;"' ?> id="box<?php echo $box_id
    ?>" class="monospace"><?php echo nl2br(htmlentities($text)) ?></div>
</div><?php
}
output_box('Arbiter messages', $messages);
output_box('Fugitive messages', $comments_fugitive, FALSE);
output_box('Detective messages', $comments_detectives, FALSE);
?>

<p><a href="index.php">Back to homepage.</a></p>

<div class="footer">Copyright &copy; 2004-2006 by Maks Verver
(<a href="mailto:maks@hell.student.utwente.nl">maks@hell.student.utwente.nl</a>)</div>
</body>
</html>
