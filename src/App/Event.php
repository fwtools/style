<?php

namespace App;

use \Arya\Request as Request;
use \Arya\Response as Response;

class Event {
	public function addRecord(Request $request) {
		try {
			$event = $request->getStringQueryParameter('event');
			$world = $request->getStringQueryParameter('world');

			if(empty($world)) {
				return ['status' => 400];
			}

			$q = $this->db->prepare("INSERT INTO style_event (world, event, time) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE time = ?");
			$q->execute([$world, $event, time(), time()]);

			return ['body' => ''];
		} catch (\Exception $e) {
			return ['status' => 400];
		}
	}
}
