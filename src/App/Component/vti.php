<?php

namespace App\Component;

class vti implements \App\Component {
	public function get () {
		return file_get_contents(__DIR__ . "/../../../res/addons/vti/style.css");
	}
}
