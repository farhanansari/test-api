<?php
namespace App\Controller\Component;

use Cake\I18n\Time;
use Cake\Controller\Component;
class FileUploadComponent extends Component {

    public function upload($filefrom,$targetfile) {
					if (move_uploaded_file($filefrom, $targetfile) || copy($filefrom, $targetfile)) { //copy($filefrom, $targetfile)
						unlink($filefrom); // delete uploaded file from temp path after successfull move
                                                return true;
					} else {
						return false;
					}
    }

}
