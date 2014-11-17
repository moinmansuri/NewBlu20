<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Preference Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_preference_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_preference_model extends Freeform_Model
{
	public $after_get	= array('json_decode_stored');


	// --------------------------------------------------------------------

	/**
	 * json_decode stored items
	 *
	 * @access	public
	 * @param	array	$data	incoming data rows from observer
	 * @param	bool	$all	returning all rows or a single?
	 * @return	array			affected data
	 */

	public function json_decode_stored ($data, $all)
	{
		if ($all)
		{
			foreach ($data as $key => $row)
			{
				if (isset($row['preference_value']))
				{
					if (preg_match('/^(\[|\{)/', $row['preference_value']))
					{
						$usi = json_decode($row['preference_value'], TRUE);

						if (is_array($usi))
						{
							$data[$key]['preference_value'] = $usi;
						}
					}
				}
			}
		}
		else if (isset($data['preference_value']))
		{
			//json data?
			if (preg_match('/^(\[|\{)/', $data['preference_value']))
			{
				$usi = json_decode($data['preference_value']);
				if (is_array($usi))
				{
					$data['preference_value'] = $usi;
				}
			}
		}

		return $data;
	}
	//json_decode_stored
}
//END Freeform_preference_model