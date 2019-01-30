<?php

require_once './utils/gdrive_client.php';
require_once './utils/form_handler.php';

session_start();

$hasCode          = isset($_GET['code']);
$isAuthRedirect   = $hasCode || (isset($_SESSION['access_token']) && $_SESSION['access_token']);
$hasSubmittedForm = isset($_POST['action']) && $_POST['action'] === 'submit_pdf';
$client           = getClient();

// if this is redirected from oauth
if ($isAuthRedirect) {
  if ($hasCode) {
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();
  } else {
    $client->setAccessToken($_SESSION['access_token']);
  }

  if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
      $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
      $authUrl = $client->createAuthUrl();
      header('Location: ' . $authUrl);
      exit();
    }
  }

} else {
  $authUrl = $client->createAuthUrl();
  header('Location: ' . $authUrl);
  exit();
}

// if user submits form
if ($hasSubmittedForm) {
  $service = new Google_Service_Drive($client);
  $handler = new FormHandler(array(
    'post'           => $_POST,
    'files'          => $_FILES,
    'gdrive_service' => $service
  ));

  $handler->execute();
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Decathlon Test App</title>

  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

  <!-- Optional theme -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

  <!-- Latest compiled and minified JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>
<body>
  <div class="container">
    <header>
      <h1 class="page-header">Upload PDF to GDrive</h1>
    </header>
    <form action="/" method="post" enctype="multipart/form-data" class="form-inline">
      <div class="form-group">
        <label for="filename">File name</label>
        <input type="text" class="form-control" id="filename" name="filename">
      </div>
      <div class="form-group">
        <label for="pdf_file">PDF file</label>
        <input type="file" class="form-control" id="pdf_file" name="pdf_file">
      </div>
      <div class="form-group">
        <input type="hidden" name="action" value="submit_pdf">
        <button type="submit" class="btn btn-success">Submit to Gdrive</button>
      </div>
    </form>
  </div>  
</body>
</html>  
