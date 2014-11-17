<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Export Library
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/libraries/Freeform_export.php
 */

$__parent_folder = rtrim(realpath(rtrim(dirname(__FILE__), "/") . '/../'), '/') . '/';

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once $__parent_folder . 'addon_builder/addon_builder.php';
}

unset($__parent_folder);

class Freeform_export extends Addon_builder_freeform
{
	public $cache_path;
	public $export_chunk_size	= 100;
	public $format_dates		= TRUE;

	// --------------------------------------------------------------------

	/**
	 * Main Export function for returning or forcing a download of file
	 *
	 * @access public
	 * @param  array  	$options various download options. Form_id and query required
	 * @return string 	string of file contents or exit on array
	 */

	public function export ($options = array())
	{
		// -------------------------------------
		//	run defaults
		// -------------------------------------

		$defaults = array(
			'method'			=> 'csv',
			'form_id'			=> 0,
			'form_name'			=> '',
			'output'			=> 'string',
			'rows'				=> array(),
			'model'				=> NULL,
			'fields'			=> '*',
			'remove_entry_id'	=> FALSE,
			'header_labels'		=> array(),
			'total_entries'		=> 0
		);

		foreach ($defaults as $key => $value)
		{
			if (isset($options[$key]))
			{
				$defaults[$key] = $options[$key];
			}
		}

		extract($defaults);

		unset($defaults, $options);

		$chunk			= FALSE;
		$chunk_start	= FALSE;
		$chunk_end		= FALSE;

		// -------------------------------------
		//	check prelim data
		// -------------------------------------

		if ( ((! is_array($rows) OR empty($rows)) AND ! $model) OR
			 ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		// -------------------------------------
		//	cache clean
		// -------------------------------------

		$this->clean_file_cache();

		// -------------------------------------
		//	export
		// -------------------------------------

		$method = is_callable(array($this, $method)) ? $method : 'csv';

		//return a string to write somewhere?
		if ($output == 'string')
		{
			if (empty($rows) AND $model)
			{
				$rows = $model->get();
			}

			return $this->$method(array(
				'form_id'			=> $form_id,
				'rows'				=> $rows,
				'remove_entry_id'	=> $remove_entry_id,
				'header_labels'		=> $header_labels,
				'chunk'				=> $chunk,
				'chunk_start'		=> $chunk_start,
				'chunk_end'			=> $chunk_end
			));
		}
		//force a file download
		else
		{
			$form_name = rtrim(trim($form_name), '_') . '_';

			$filename = ee()->security->sanitize_filename(
				'freeform_' . $form_name . 'export_' .
				date('Ymd_Hi_s', ee()->localize->now) . '.' .
				$method
			);

			//do we need to chunk this?
			if (empty($rows) AND $model AND $total_entries > $this->export_chunk_size)
			{
				//Set the execution time to infinite.
				set_time_limit(0);

				//detect chunk size
				$chunk_size = ceil($total_entries/$this->export_chunk_size);

				// -------------------------------------
				//	get file path and check writability
				// -------------------------------------

				ee()->load->helper('file');

				$filepath = $this->cache_file_path($filename);

				//blank
				write_file($filepath, '');

				//chunk and write
				for ($i = 0; $i < $chunk_size; $i++)
				{
					$model->limit($this->export_chunk_size, $i * $this->export_chunk_size);

					$data = $this->$method(array(
						'form_id'			=> $form_id,
						'remove_entry_id'	=> $remove_entry_id,
						'header_labels'		=> $header_labels,
						'rows'				=> $model->get(array(), FALSE),
						'chunk'				=> TRUE,
						'chunk_start'		=> $i == 0,
						'chunk_end'			=> $i == ($chunk_size - 1)
					));

					write_file($filepath, $data, 'a+');

					unset($data);

					Freeform_cacher::clear();
				}

				// -------------------------------------
				//	send to user
				// -------------------------------------

				header('Content-disposition: attachment; filename=' . $filename);
				header('Content-type: ' . get_mime_by_extension($filename));
				readfile($filepath);
				exit();
			}
			else
			{
				if (empty($rows) AND $model)
				{
					$rows = $model->get();
				}

				$data = $this->$method(array(
					'form_id'			=> $form_id,
					'rows'				=> $rows,
					'remove_entry_id'	=> $remove_entry_id,
					'header_labels'		=> $header_labels,
					'chunk'				=> $chunk,
					'chunk_start'		=> $chunk_start,
					'chunk_end'			=> $chunk_end
				));

				if ($data)
				{
					ee()->load->helper('download');
					force_download($filename, $data);

					exit();
				}
			}

			//fail safe
			$this->actions()->full_stop(lang('error_creating_export'));
		}
	}
	//END export


	// --------------------------------------------------------------------

	/**
	 * Find cache file path
	 *
	 * @access public
	 * @param  string $filename optional file name to append
	 * @return string           cache path +
	 */

	public function cache_file_path ($filename = '')
	{
		if ( ! isset($this->cache_path))
		{
			$cache_path = ee()->config->item('cache_path');

			if (empty($cache_path))
			{
				$cache_path = APPPATH . 'cache/';
			}

			$cache_path = rtrim($cache_path, '/') . '/' . $this->lower_name . '/';

			$this->cache_path = $cache_path;

			if ( ! is_dir($cache_path))
			{
				mkdir($cache_path);
			}
		}

		return $this->cache_path . $filename;
	}
	//END cache_file_path


	// --------------------------------------------------------------------

	/**
	 * Cleans items in cache folder older than 3 hours old
	 *
	 * @access public
	 * @return null
	 */

	public function clean_file_cache ()
	{
		ee()->load->helper('directory');

		$cache_path = $this->cache_file_path();

		$files = directory_map($cache_path, 1);

		foreach ($files as $file)
		{
			$file_loc = realpath($cache_path . $file);

			if (is_file($file_loc) AND filemtime($file_loc) < (time() - 7200))
			{
				@unlink($file_loc);
			}
		}
	}
	//END clean_file_cache


	// --------------------------------------------------------------------

	/**
	 * Generate CSV from a query result object
	 *
	 * @access	public
	 * @param	array	Any preferences
	 * @return	string
	 */

	function csv ($options = array())
	{
		$defaults = array(
			'form_id'			=> 0,
			'rows'				=> NULL,
			'remove_entry_id'	=> FALSE,
			'header_labels'		=> array(),
			'delim'				=> ",",
			'newline'			=> "\n",
			'enclosure'			=> '"',
			'chunk'				=> FALSE,
			'chunk_start'		=> FALSE,
			'chunk_end'			=> FALSE,
		);

		foreach ($defaults as $key => $value)
		{
			if (isset($options[$key]))
			{
				$defaults[$key] = $options[$key];
			}
		}

		extract($defaults);

		unset($defaults, $options);

		$out	= '';
		$first	= ( ! $chunk OR ($chunk AND $chunk_start));

		// -------------------------------------
		//	build labels
		// -------------------------------------

		if ($first)
		{
			// First generate the headings from the table column names
			foreach ($rows[0] as $key => $val)
			{
				if (($remove_entry_id AND $key == 'entry_id') OR
					$key == 'form_id')
				{
					continue;
				}

				$label_item = isset($header_labels[$key]) ?
								$header_labels[$key] : '';

				$out .= $enclosure .
						str_replace(
							$enclosure,
							$enclosure . $enclosure,
							$label_item
						) .
						$enclosure .
						$delim;
			}

			$out = rtrim(rtrim($out), $delim);

			$out .= $newline;
		}

		// -------------------------------------
		//	rows
		// -------------------------------------

		foreach ($rows as $row)
		{
			$output_parse = ee()->freeform_fields->apply_field_method(array(
				'method'			=> 'export',
				'form_id'			=> $form_id,
				'field_input_data'	=> $row,
				'export_type'		=> 'csv'
			));

			$row = array_merge($row, $output_parse['variables']);

			$row = $this->parse_dates($row);

			if ($remove_entry_id)
			{
				unset($row['entry_id']);
			}

			unset($row['form_id']);

			foreach ($row as $item)
			{
				$out .= $enclosure .
						str_replace(
							$enclosure,
							$enclosure . $enclosure,
							$item
						) .
						$enclosure .
						$delim;
			}
			$out = rtrim(rtrim($out), $delim);
			$out .= $newline;
		}

		// -------------------------------------
		//	output
		// -------------------------------------

		return $out;
	}
	//END csv


	// --------------------------------------------------------------------

	/**
	 * Generate text from a query result object
	 *
	 * @access	public
	 * @param	array	Any preferences
	 * @return	string
	 */

	public function txt ($options = array())
	{
		return $this->csv(array_merge($options, array(
			'delim'		=> "\t",
			'enclosure'	=> '',
		)));
	}
	//END txt

	

	// --------------------------------------------------------------------

	/**
	 * Generate XML data from a query result object
	 *
	 * @access	public
	 * @param	array	Any preferences
	 * @return	string
	 */

	public function xml ($options = array())
	{
		$defaults = array(
			'form_id'			=> 0,
			'rows'				=> array(),
			'remove_entry_id'	=> FALSE,
			'delim'				=> ",",
			'newline'			=> "\n",
			'enclosure'			=> '"',
			'header_labels'		=> array(),
			'chunk'				=> FALSE,
			'chunk_start'		=> FALSE,
			'chunk_end'			=> FALSE,
		);

		foreach ($defaults as $key => $value)
		{
			if (isset($options[$key]))
			{
				$defaults[$key] = $options[$key];
			}
		}

		extract($defaults);

		unset($defaults, $options);

		$first 			= ( ! $chunk OR ($chunk AND $chunk_start));
		$last 			= ( ! $chunk OR ($chunk AND $chunk_end));
		$tag_root 		= 'root';
		$tag_rows 		= 'rows';
		$tag_labels 	= 'labels';
		$tag_element 	= 'entry';
		$newline 		= "\n";
		$tab 			= "\t";
		$xml 			= "";

		ee()->load->helper('xml');

		// -------------------------------------
		//	if this is the first in the chunk
		//	or only time we are running
		//	built the beginning
		// -------------------------------------

		if ($first)
		{
			$xml .= "<?xml version='1.0' ?>" . $newline;
			$xml .= "<{$tag_root}>" . $newline;

			// -------------------------------------
			//	labels
			// -------------------------------------

			$xml .= $tab ."<{$tag_labels}>" . $newline;

			foreach ($rows[0] as $key => $val)
			{
				if (($remove_entry_id AND $key == 'entry_id') OR
					$key == 'form_id')
				{
					continue;
				}

				$label_item = isset($header_labels[$key]) ?
								$header_labels[$key] : '';

				$xml .= $tab . $tab .
						"<{$key}>" . xml_convert($label_item) .	"</{$key}>" .
						$newline;
			}

			$xml .= $tab . "</{$tag_labels}>" . $newline;

			//start entry rows
			$xml .= $tab . "<{$tag_rows}>" . $newline;
		}

		// -------------------------------------
		//	build entries
		// -------------------------------------

		foreach ($rows as $row)
		{
			$xml .= $tab . $tab . "<{$tag_element}>" . $newline;

			$output_parse = ee()->freeform_fields->apply_field_method(array(
				'method' 			=> 'export',
				'form_id' 			=> $form_id,
				'field_input_data'	=> $row,
				'export_type' 		=> 'xml'
			));

			$row = array_merge($row, $output_parse['variables']);

			$row = $this->parse_dates($row);

			if ($remove_entry_id)
			{
				unset($row['entry_id']);
			}

			unset($row['form_id']);

			foreach ($row as $key => $val)
			{
				$xml .= $tab . $tab . $tab .
						"<{$key}>" . xml_convert($val) . "</{$key}>" .
						$newline;
			}

			$xml .= $tab . $tab . "</{$tag_element}>" . $newline;
		}

		if ($chunk AND ! $last)
		{
			return $xml;
		}

		$xml .= $tab . "</{$tag_rows}>" . $newline;
		$xml .= "</{$tag_root}>" . $newline;

		return $xml;
	}
	//END xml


	// --------------------------------------------------------------------

	/**
	 * Generate json data from a query result object
	 *
	 * @access	public
	 * @param	array	Any preferences
	 * @return	string
	 */

	public function json ($options = array())
	{
		$defaults = array(
			'form_id'			=> 0,
			'rows'				=> array(),
			'remove_entry_id'	=> FALSE,
			'header_labels'		=> array(),
			'chunk'				=> FALSE,
			'chunk_start'		=> FALSE,
			'chunk_end'			=> FALSE,
		);

		foreach ($defaults as $key => $value)
		{
			if (isset($options[$key]))
			{
				$defaults[$key] = $options[$key];
			}
		}

		extract($defaults);

		unset($defaults, $options);

		$output			= array();

		$show_labels	= array();

		$first			= ( ! $chunk OR ($chunk AND $chunk_start));

		// -------------------------------------
		//	build lables
		// -------------------------------------

		if ($first)
		{
			foreach ($rows[0] as $key => $value)
			{
				if (($remove_entry_id AND $key == 'entry_id') OR
					$key == 'form_id')
				{
					continue;
				}

				$show_labels[$key] = isset($header_labels[$key]) ?
										$header_labels[$key] : '';
			}
		}

		// -------------------------------------
		//	parse rows
		// -------------------------------------

		foreach ($rows as $row)
		{
			$output_parse = ee()->freeform_fields->apply_field_method(array(
				'method'			=> 'export',
				'form_id'			=> $form_id,
				'field_input_data'	=> $row,
				'export_type'		=> 'json'
			));

			$row = array_merge($row, $output_parse['variables']);

			$row = $this->parse_dates($row);

			if ($remove_entry_id)
			{
				unset($row['entry_id']);
			}

			unset($row['form_id']);

			$output[] = $row;
		}

		// -------------------------------------
		//	returns
		// -------------------------------------

		if ( ! $chunk)
		{
			$return = $this->json_encode(array(
				'labels'	=> $show_labels,
				'rows'		=> $output
			));
		}
		else if ($chunk AND $first)
		{
			$return = rtrim($this->json_encode(array(
				'labels'	=> $show_labels,
				'rows'		=> $output
			)));

			//remove the ending ']}' so we can add
			$return = substr($return, 0, -2);
		}
		else if ($chunk AND $chunk_end)
		{
			//remove array wrappers and add the ending items for the whole schmere
			$return = ',' . trim(trim($this->json_encode($output)), '[]') . ']}';
		}
		else if ($chunk)
		{
			//we are just addong rows to the 'rows' array so preceding comma
			//remove array wrappers
			$return = ',' . trim(trim($this->json_encode($output)), '[]');
		}

		return $return;
	}
	//END json

	


	// --------------------------------------------------------------------

	/**
	 * Parse and convert Dates
	 *
	 * @access	public
	 * @param	array	$row	row array input
	 * @return	array			row array output with dates fixed
	 */

	public function parse_dates($row = array())
	{
		if ( ! $this->format_dates)
		{
			return $row;
		}

		if (isset($row['entry_date']) && $this->is_positive_intlike($row['entry_date']))
		{
			$row['entry_date'] = $this->actions()->format_cp_date($row['entry_date']);
		}

		if (isset($row['edit_date']) && $this->is_positive_intlike($row['edit_date']))
		{
			$row['edit_date'] = $this->actions()->format_cp_date($row['edit_date']);
		}

		return $row;
	}
	//END parse_dates
}
//END Freeform_export