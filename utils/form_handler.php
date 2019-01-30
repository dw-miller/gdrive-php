<?php

class FormHandler {
  const ROOT_FOLDER_NAME = 'TestPHP';
  const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

  private $_post;
  private $_errors;

  public function __construct($data) {
    $this->_errors       = array();
    $this->_files        = $data['files'];
    $this->_post         = $data['post'];
    $this->_rootFolderId = '';
    $this->_service      = $data['gdrive_service'];
  }

  public function execute() {
    $this->_validate();
    $this->_createInitialFolder();

    // creates folder from form field 'filename'
    $folder = $this->_createFolder(array(
      'filename' => $this->_post['filename'],
      'parents' => array($this->_rootFolderId)
    ));

    $this->_createFile($folder->id, $this->_files['pdf_file']);

    echo 'Successfully uploaded file!';
  }

  private function _createInitialFolder() {
    $pageToken = null;
    $foundFile = null;
    $query     = "mimeType='" . self::FOLDER_MIME_TYPE . "' and name='" . self::ROOT_FOLDER_NAME . "' and trashed=false";

    do {
      $response = $this->_service->files->listFiles(array(
        'q' => $query,
        'spaces' => 'drive',
        'pageToken' => $pageToken,
        'fields' => 'nextPageToken, files(id, name)'
      ));

      foreach ($response->files as $file) {
        $foundFile = $file;
      }

      $pageToken = $response->pageToken;
    } while ($pageToken !== null);

    var_dump($foundFile);

    // if folder does not exist
    if ($foundFile === null) {
      $rootFolder = $this->_createFolder(array('filename' => self::ROOT_FOLDER_NAME));
    } else {
      $rootFolder = $foundFile;
    }

    $this->_rootFolderId = $rootFolder->id;
  }

  private function _createFile($folderId, $file) {
    $fileMetaData = new Google_Service_Drive_DriveFile(array(
      'name' => $file['name'],
      'parents' => array($folderId)
    ));
    $content = file_get_contents($file['tmp_name']);
    $createdFile = $this->_service->files->create($fileMetaData, array(
      'data' => $content,
      'mimeType' => $file['type'],
      'uploadType' => 'multipart',
      'fields' => 'id'
    ));

    return $createdFile;
  }

  private function _createFolder($options) {
    $filename = isset($options['filename']) ? $options['filename'] : null;
    $parents = isset($options['parents']) ? $options['parents'] : null;
    $fileMetaData = new Google_Service_Drive_DriveFile(array(
      'name' => $filename,
      'mimeType' => self::FOLDER_MIME_TYPE,
      'parents' => $parents
    ));

     $file = $this->_service->files->create($fileMetaData, array('fields' => 'id'));

    return $file;
  }

  private function _validate() {
    $maxsize = 25000000;
    $validTypes = array('application/pdf');
    $file = $this->_files['pdf_file'];
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (trim($this->_post['filename']) === '') {
      echo 'Error: Please input a valid filename';
      exit();
    }

    if (!in_array($file['type'], $validTypes) || $fileExt !== 'pdf') {
      echo "Error: Invalid file type.";
      exit();
    }

    if ($file['size'] > $maxsize) {
      echo "Error: File too large.";
      exit();
    }
  }
}
