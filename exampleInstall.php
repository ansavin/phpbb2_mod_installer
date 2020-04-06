<?php
/*****************************************
*            exampleInstall.php
******************************************
* Small libMODparser work demonstrator
*****************************************/
include("libMODparser.php");

$parser = new MODparser();

define("PHPBB_PATH", realpath("./").'/');
define("PROCESS_PATH", realpath("./process").'/');

echo '<h1>Installation demonstrator</h1>';
echo '<p>Loading MOD into memory...<br />';
if( !$parser->load(PHPBB_PATH."example_install.txt") )
{
    echo '[ <font color = "red">FAILED</font> ]<br />';
} else {
    echo '[ <font color = "green">SUCCESS</font> ]<br />';
    echo 'Parsing MOD script...<br />';
    $parser->basedir = PHPBB_PATH;
    if( !$parser->parse() )
    {
        die('[ <font color = "red">FAILED</font> ]<br />');
    }
    else
    {
        echo '[ <font color = "green">SUCCESS</font> ]<br />';
        echo 'Processing files...<br />';

        // Read files, modifies them and puts into tmp folder
        if( !$parser->process(PHPBB_PATH, PROCESS_PATH) )
        {
            die('[ <font color = "red">FAILED</font> ] '.nl2br($parser->getErrorInfo()).'<br />');
        }
        else
        {
            echo '[ <font color = "green">SUCCESS</font> ]<br />';
        }
    }
}

echo 'Copying files...<br />';

//Move files from tmp dir to forum folder
if( !$parser->install(PROCESS_PATH, PHPBB_PATH) )
{
    echo getcwd();
    echo ($parser->errinfo);
    die('[ <font color = "red">FAILED</font> ]<br />');
}
else
{
    echo '[ <font color = "green">SUCCESS</font> ]<br />';
    die('Installation complete!<br />');
}

?>
