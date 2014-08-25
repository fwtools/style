<?php

namespace App;

use \Arya\Request as Request;
use \Arya\Response as Response;

class NPCs {
	private $db;
	private $request;

	public function __construct (\PDO $db, \Arya\Request $request) {
		$this->db = $db;
		$this->request = $request;
	}

	private function getSingleNpcStyle ($name) {
		$css = "";

		if(strlen($name) > 8) {
			$display = substr($name, 0, 6) . "…";
		} else {
			$display = $name;
		}

		$query = $this->db->prepare("SELECT pn.x, pn.y FROM wiki_npc AS n, wiki_place_npc AS pn WHERE n.name = ? && n.name = pn.name");
		$query->execute([$name]);
		$data = $query->fetchAll(\PDO::FETCH_OBJ);

		foreach($data as $p) {
			$css.= "#mapx{$p->x}y{$p->y} > a:after { content: '{$display}'; }";
		}

		return $css;
	}

	public function css () {
		$response = new Response;

		$exp_gmt = gmdate("D, d M Y H:i:s", time() + 10) . " GMT";
		$mod_gmt = gmdate("D, d M Y H:i:s", time()) . " GMT";

		$response->setHeader('Expires', $exp_gmt);
		$response->setHeader('Last-Modified', $mod_gmt);
		$response->setHeader('Cache-Control', 'private, max-age=' . (10));
		$response->addHeader('Cache-Control', 'post-check=' . (6));
		$response->setHeader('Content-Type', 'text/css; charset=utf-8');

		$q = $this->db->prepare("SELECT npc_name FROM tools_npc_views WHERE npc_sess_id = ? && view_time > ?");

		try {
			$q->execute([$this->request->getCookie('npc_sess_id'), time() - 60000]);

			if($view = $q->fetch(\PDO::FETCH_OBJ)) {
				return $response->setBody($this->getSingleNpcStyle($view->npc_name));
			} else {
				return $response->setBody("/* no entry */");
			}
		} catch(\Exception $e) {
			return $response->setBody("/* no cookie */");
		}
	}
}
