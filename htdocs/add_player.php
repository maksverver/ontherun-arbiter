<?php
define(LOCALBASE, '/usr/local/codecup');
$name = basename($_REQUEST['Name']);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>On The Run - Online Arbiter</title>
<link rel="StyleSheet" href="arbiter.css" title="Arbiter" type="text/css" />
</head>
<body>
<h1><span class="title">Adding Player <?php echo htmlentities($name); ?></span></h1>
<?php
if($name == '')
{
?><h3>Program name not specified!</h3><?php
}
else
{
    @mkdir(LOCALBASE.'/players/'.$name);
    chdir(LOCALBASE.'/players/'.$name);

    $filename = basename($_FILES['Source']['name']);
    move_uploaded_file($_FILES['Source']['tmp_name'], $filename);
?>
<h2>Building <?php echo htmlentities($_REQUEST['Name']); ?></h2>

<div class="box"><pre class="code"><?php
    switch($_REQUEST['Language'])
    {
    case 'c':
        $command = '/usr/bin/gcc -static -o run -Wall -O2 -lm %s 2>&1'; break;
    case 'c++':
        $command = '/usr/bin/g++ -static -o run -Wall -O2 -lm %s 2>&1'; break;
    //case 'java':
    //    $command = '/usr/local/jdk1.3.1/bin/javac -verbose -deprecation -O %s 2>&1'; break;
    default:
        die('Invalid source language!');
    }
    flush();
    system(sprintf($command, escapeshellarg($filename)), $result);
?></pre><?php

    echo '<h3>', ($result == 0 ? 'Build complete.' : 'Build failed!'), '</h3>';

    if(isset($_REQUEST['DeleteSource']) && ($_REQUEST['DeleteSource'] == 'yes'))
    {
        if(@unlink($filename))
            echo '<h3>Source file erased.</h3>';
    }

    if($result != 0)
    {
        $dh = opendir('.');
        while (($file = readdir($dh)) !== FALSE)
            if($file != '.' && $file != '..')
                unlink($file);
        closedir($dh);
        chdir('..');
        rmdir($name);
    }

?></div><?php
}
?>
<p><a href="index.php">Back to homepage.</a></p>

<div class="footer">Copyright &copy; 2004-2006 by Maks Verver
(<a href="mailto:maks@hell.student.utwente.nl">maks@hell.student.utwente.nl</a>)</div>

</body>
</html>
