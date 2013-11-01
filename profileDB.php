<?php
// TryOpenDb and CloseDb - Open and close the database.
	$DbLink = FALSE;
	$DbError = "";

function TryOpenDb()
{
	global $DbLink;
	global $DbError;

	global $db_host;
	global $db_user;
	global $db_password;
	global $db_name;

	/* Connecting, selecting database */
	$DbLink = new mysqli($db_host, $db_user, $db_password, $db_name);

	if (mysqli_connect_error())
	{
		$DbError = mysqli_connect_error();
		return FALSE;
	}

	$DbLink->autocommit(FALSE);
	return $DbLink;
}

function CloseDb()
{
	global $DbLink;

	if ($DbLink)
	{
		$DbLink->commit();
		$DbLink->close();
		$DbLink = FALSE;
	}
}

function abortAndExit()
{
	global $DbLink;
	global $DbError;
	print "Aborting database communication: " . $DbError;
	if ($DBLink) {
		$DbLink->rollback();
		$DbLink->close();
		$DbLink = FALSE;
	}
	exit();
}

?>
