<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Composer Template Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_template_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_template_model extends Freeform_Model 
{
	//intentional because we will need to set every incoming table
	public	$_table	= 'freeform_composer_templates';
	public	$id		= 'template_id';
}
//END Freeform_composer_model