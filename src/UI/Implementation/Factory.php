<?php

/* Copyright (c) 2016 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\UI\Implementation;

// TODO: This might cache the created factories.
class Factory implements \ILIAS\UI\Factory {
	/**
	 * @return \ILIAS\UI\Factory\Counter
	 */
	public function counter() {
		return new Component\Counter\Factory();
	}

	/**
	 * @return \ILIAS\UI\Factory\Glyph
	 */
	public function glyph() {
		return new Component\Glyph\Factory();
	}
}
