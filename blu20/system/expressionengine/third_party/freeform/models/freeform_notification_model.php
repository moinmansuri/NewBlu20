<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Notification Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_notification_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_notification_model extends Freeform_Model 
{
	//nonstandard name
	public $_table = 'freeform_notification_templates';
}
//END Freeform_preference_model