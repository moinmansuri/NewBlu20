<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of CP Analytics add-on for ExpressionEngine.

    CP Analytics is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CP Analytics is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2012 Derek Hogue
*/

class Cp_analytics {
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
}