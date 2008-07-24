<?php
$params = $_REQUEST['Params'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>On The Run - <?php echo $params['fugitive']; ?> (fugitive) vs <?php echo $params['detectives']; ?> (detectives)</title>
</head>
<body style="margin: 0; padding: 0;">
<applet code="nl.codecup.ontherun.OntherunApplet" archive="ontherun.jar" width="100%" height="100%">
<param name="white" value="A">
<param name="black" value="B">
<param name="moves" value="<?php echo $params['moves'] ?>">
<param name="result" value="<?php echo $params['result'] ?>">
<param name="resultDesc" value="<?php echo $params['resultDesc'] ?>">
<param name="animate" value="on">
<param name="quality" value="high">
<param name="showNumbers" value="on">
<param name="showPossibilities" value="on">
<param name="followFugitive" value="on">
<param name="setting_url" value="http://www.codecup.nl/applet_settings.php">
<param name="connect" value="<?php echo $params['map']; ?> ">
<param name="connectBaseUrl" value="http://hell.student.utwente.nl/codecup/maps"></applet>
</body>
</html>
