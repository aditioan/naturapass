<?php

namespace NaturaPass\MediaBundle\Entity;

use Symfony\Component\HttpFoundation\Request;

/*
 * jQuery File Upload Plugin PHP Class 7.1.4
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

class UploadHandler {

    protected $request;
    protected $basePath;
    protected $options;
    protected $files = array();
    protected $response;

    public function __construct(Request $request, $options = array()) {
        $this->request = $request;
        $this->basePath = __DIR__ . '/../../../../web/uploads/';

        $this->options = array(
            'param_name' => 'file'
        );

        $this->options = array_merge($this->options, $options);
    }

    /**
     * Déplace le fichier dans un dossier temporaire
     *
     * @return mixed
     */
    public function handleFileUpload() {
        if ($file = $this->request->files->get($this->options['param_name'], NULL, true)) {
            return ini_get('upload_tmp_dir') . $file->getFilename();
        }

        $this->buildResponse();

        return false;
    }

    /**
     * Crée la réponse à envoyer
     */
    protected function buildResponse() {
        $this->response = array('result' => array());

        foreach ($this->files as $file) {
            $this->response['files'][] = array(
                "name" => $file->getFilename(),
                "size" => $file->getSize()
            );
        }
    }

    /**
     * Getter response
     *
     * @return array
     */
    public function getResponse() {
        return $this->response;
    }

}