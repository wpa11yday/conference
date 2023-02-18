<?php

function wpcsp_pro_uninstall(){
	
	delete_transient('wpcsp_license_valid');

}