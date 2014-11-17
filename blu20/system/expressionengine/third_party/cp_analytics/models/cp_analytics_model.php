<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

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

require(PATH_THIRD.'cp_analytics/config.php');

class Cp_analytics_model {

	var $client_id = '59111117587-fgfsq53kffg2gtb80ocu0msq30trbbsb.apps.googleusercontent.com';
	var $client_secret = 'R7F96dbrCtuKCeO3Fzp6meDa';
	var $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
	var $token_endpoint = 'https://accounts.google.com/o/oauth2/token';
	var $data_endpoint = 'https://www.googleapis.com/analytics/v3/data/ga';
	var $access_token = '';
	
	var $site_id = '';
	var $profile_id = '';
	var $current_date = '';
	var $current_time = '';
	var $yesterday = '';
	var $last_month = '';
		
	
	function __construct()
	{
		$this->EE =& get_instance();		
		$this->site_id = $this->EE->config->item('site_id');
		$this->current_date = $this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->now);
		$this->current_time = $this->EE->localize->decode_date('%g:%i%a', $this->EE->localize->now);
		$this->yesterday = $this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->now - 86400);
		$this->last_month = $this->EE->localize->decode_date('%Y-%m-%d', $this->EE->localize->now - 2592000);
	}
	
	
	function get_oauth_url()
	{
		return 'https://accounts.google.com/o/oauth2/auth?response_type=code'.AMP.'client_id='.$this->client_id.AMP.'redirect_uri='.$this->redirect_uri.AMP.'scope=https://www.googleapis.com/auth/analytics.readonly';
	}
	
	
	function exchange_authorization_for_token($code)
	{
		$r = array(
			'error' => '',
			'access_token' => ''
		);
		$args = array(
			'code' => trim($code),
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_uri' => $this->redirect_uri,
			'grant_type' => 'authorization_code'
		);
		if($response = $this->_curl_request($this->token_endpoint, $args))
		{
			
			if(isset($response['error']))
			{
				$r['error'] = 'cp_analytics_invalid_auth_code';
			}

			// Save our refresh token
			if(isset($response['refresh_token']))
			{
				$this->save_settings(array('refresh_token' => $response['refresh_token']));
			}
			
			// Set the fresh access token
			if(isset($response['access_token']))
			{
				$this->access_token = $response['access_token'];
			}
		}
		return $r;
	}
	

	function get_access_token()
	{
		if(empty($this->access_token))
		{
			if($token = $this->get_refresh_token())
			{
				$args = array(
					'refresh_token' => $token,
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'grant_type' => 'refresh_token'					
				);
				if($response = $this->_curl_request($this->token_endpoint, $args))
				{
					if(isset($response['access_token']))
					{
						$this->access_token = $response['access_token'];
					}
				}				
			}
		}
	}
	
	
	function get_refresh_token()
	{
		$token = $this->EE->db->query('SELECT refresh_token FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
		if($token->num_rows() > 0)
		{
			return $token->row('refresh_token');
		}
	}
	
	
	function get_profile_list()
	{
		$this->get_access_token();
		
		$r = array(
			'error' => '',
			'profiles' => '',
			'profile_segments' => '',
			'profile_names' => ''
		);

		if(!empty($this->access_token))
		{
			$args = array(
				'access_token' => $this->access_token	
			);
			
			if($response = $this->_curl_request(
				'https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles'
				, $args, 'get')
			)
			{
				if(isset($response['error']))
				{
					$r['error'] = 'cp_analytics_profile_error';
				}
				
				if(isset($response['items']))
				{
					$r['profiles'] = array('' => '--');
					foreach($response['items'] as $result)
					{
					  $r['profiles'][$result['id']] = $result['name'];
					  $r['segments'][$result['id']] = 'a'.$result['accountId'].'w'.$result['internalWebPropertyId'].'p'.$result['id'];
					  $r['names'][$result['id']] = $result['name'];
					}
					asort($r['profiles']);
				}
			}
		}
		return $r;
	}
	

	function get_hourly_stats()
	{			
		$cache = $this->get_hourly_cache();
		if(!empty($cache) && $this->EE->localize->set_localized_time() < ($cache['cache_time'] + 3600))
		{
			$data = $cache;
		}
		else
		{
			$this->get_access_token();
			$data = array(
				'pageviews' => 0,
				'visits' => 0,
				'pages_per_visit' => 0,
				'avg_visit' => '00:00:00'
			);
			$data['cache_time'] = $this->EE->localize->set_localized_time();
			
			$profile = $this->get_profile();
			$this->profile_id = 'ga:'.$profile['id'];

			$args = $this->_default_query_args();
			$args['start-date'] = $this->current_date;
			$args['end-date'] = $this->current_date;
			$args['metrics'] = 'ga:pageviews,ga:visits,ga:timeOnSite';
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
			if(isset($request['rows']))
			{			
				$data['pageviews'] = number_format($request['totalsForAllResults']['ga:pageviews']);
				$data['visits'] = number_format($request['totalsForAllResults']['ga:visits']);
				$data['pages_per_visit'] = $this->_avg_pages($request['totalsForAllResults']['ga:pageviews'], $request['totalsForAllResults']['ga:visits']);
				$data['avg_visit'] = $this->_avg_visit($request['totalsForAllResults']['ga:timeOnSite'], $request['totalsForAllResults']['ga:visits']);
			}

			// Now cache it
			$settings = array(
				'hourly_cache' => $data
			);
			$this->save_settings($settings);
		}
		return $data;
	}


	function get_daily_stats()
	{		
		$cache = $this->get_daily_cache();
		if(!empty($cache) && $cache['cache_date'] == $this->current_date)
		{
			$data = $cache;
		}
		else
		{
			$this->get_access_token();
			$data = array(
				'yesterday' => array(
					'pageviews' => 0,
					'visits' => 0,
					'pages_per_visit' => 0,
					'avg_visit' => '00:00:00'
				),
				'lastmonth' => array(
					'pageviews' => 0,
					'visits' => 0,
					'pages_per_visit' => 0,
					'avg_visit' => '00:00:00',
					'bounce_rate' => 0,
					'new_visits' => 0,
					'content' => array(),
					'referrers' => array()
				)
			);
			$data['cache_date'] = $this->current_date;
			
			$profile = $this->get_profile();
			$this->profile_id = 'ga:'.$profile['id'];
			
			// Compile yesterday's stats
			$args = $this->_default_query_args();
			$args['start-date'] = $this->yesterday;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:pageviews,ga:visits,ga:timeOnSite';
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
			
			if(isset($request['rows']))
			{	
				$data['yesterday']['pageviews'] = number_format($request['totalsForAllResults']['ga:pageviews']);
				$data['yesterday']['visits'] = number_format($request['totalsForAllResults']['ga:visits']);
				$data['yesterday']['pages_per_visit'] = $this->_avg_pages($request['totalsForAllResults']['ga:pageviews'], $request['totalsForAllResults']['ga:visits']);
				$data['yesterday']['avg_visit'] = $this->_avg_visit($request['totalsForAllResults']['ga:timeOnSite'], $request['totalsForAllResults']['ga:visits']);
			}
			
			
			// Compile last month's stats
			$data['lastmonth']['date_span'] = 
				$this->EE->localize->decode_date('%F %j%S %Y', $this->EE->localize->now - 2592000).
				' &ndash; '.
				$this->EE->localize->decode_date('%F %j%S %Y', $this->EE->localize->now - 86400);
			
			$args = $this->_default_query_args();
			$args['start-date'] = $this->last_month;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:pageviews,ga:visits,ga:newVisits,ga:timeOnSite,ga:bounces,ga:entrances';
			$args['dimensions'] = 'ga:date';
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
			
			if(isset($request['rows']))
			{	
				$data['lastmonth']['visits'] = 
				number_format($request['totalsForAllResults']['ga:visits']);
				$data['lastmonth']['visits_sparkline'] = 
				$this->_sparkline($request['rows'], 'visits');
				
				$data['lastmonth']['pageviews'] = 
				number_format($request['totalsForAllResults']['ga:pageviews']);
				$data['lastmonth']['pageviews_sparkline'] = 
				$this->_sparkline($request['rows'], 'pageviews');
				
				$data['lastmonth']['pages_per_visit'] = 
				$this->_avg_pages($request['totalsForAllResults']['ga:pageviews'], $request['totalsForAllResults']['ga:visits']);
				$data['lastmonth']['pages_per_visit_sparkline'] = 
				$this->_sparkline($request['rows'], 'avgpages');
				
				$data['lastmonth']['avg_visit'] = 
				$this->_avg_visit($request['totalsForAllResults']['ga:timeOnSite'], $request['totalsForAllResults']['ga:visits']);
				$data['lastmonth']['avg_visit_sparkline'] = 
				$this->_sparkline($request['rows'], 'time');
				
				$data['lastmonth']['bounce_rate'] = 
				($request['totalsForAllResults']['ga:bounces'] > 0 && $request['totalsForAllResults']['ga:visits'] > 0) ? 
				round( ($request['totalsForAllResults']['ga:bounces'] / $request['totalsForAllResults']['ga:entrances']) * 100, 2 ).'%' : '0%';
				$data['lastmonth']['bounce_rate_sparkline'] = 
				$this->_sparkline($request['rows'], 'bouncerate');
				
				$data['lastmonth']['new_visits'] = 
				($request['totalsForAllResults']['ga:newVisits'] > 0 && $request['totalsForAllResults']['ga:visits'] > 0) ? 
				round( ($request['totalsForAllResults']['ga:newVisits'] / $request['totalsForAllResults']['ga:visits']) * 100, 2).'%' : '0%';					
				$data['lastmonth']['new_visits_sparkline'] = 
				$this->_sparkline($request['rows'], 'newvisits');
				
				// Compile stats for the homepage chart
				$data['lastmonth_chart'] = $this->_chart_data($request['rows']);
				$data['14day_chart'] = $this->_chart_data($request['rows'], 14);
			}
		
		
			// Compile last month's top content
			$args = $this->_default_query_args();
			$args['start-date'] = $this->last_month;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:pageviews';
			$args['dimensions'] = 'ga:hostname,ga:pagePath';
			$args['sort'] = '-ga:pageviews';
			$args['max-results'] = 16;
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
	
			if(isset($request['rows']))
			{	
				$data['lastmonth']['content'] = array();
				$i = 0;
				
				// Make a temporary array to hold page paths
				// (for checking dupes resulting from www vs non-www hostnames)
				$paths = array();
				
				foreach($request['rows'] as $row)
				{
					// Do we already have this page path?
					$dupe_key = array_search($row[1], $paths);
					if($dupe_key !== FALSE)
					{
						// Combine the pageviews of the dupes
						$data['lastmonth']['content'][$dupe_key]['count'] = ( $row[2] + $data['lastmonth']['content'][$dupe_key]['count'] );
					}
					else
					{
						$url = (strlen($row[1]) > 20) ? substr($row[1], 0, 20).'&hellip;' : $row[1];
						$data['lastmonth']['content'][$i]['title'] = 
							'<a href="'.$this->EE->cp->masked_url('http://'.$row[0].$row[1]).'" target="_blank">'.$url.'</a>';
						$data['lastmonth']['content'][$i]['count'] = $row[2];
						// Store the page path at the same position so we can check for dupes
						$paths[$i] = $row[1];
						$i++;
					}				
				}
				
				// Slice down to 8 results
				$data['lastmonth']['content'] = array_slice($data['lastmonth']['content'], 0, 8);
			}
			
			
			// Compile last month's top referrers
			$args = $this->_default_query_args();
			$args['start-date'] = $this->last_month;
			$args['end-date'] = $this->yesterday;
			$args['metrics'] = 'ga:visits';
			$args['dimensions'] = 'ga:source,ga:referralPath,ga:medium';
			$args['sort'] = '-ga:visits';
			$args['max-results'] = 8;
			$request = $this->_curl_request($this->data_endpoint, $args, 'get');
	
			if(isset($request['rows']))
			{		
				$data['lastmonth']['referrers'] = array();
				$i = 0;
				foreach($request['rows'] as $row)
				{
					$data['lastmonth']['referrers'][$i]['title'] = 
						($row[2] == 'referral') ? 
						'<a href="'.$this->EE->cp->masked_url('http://'.$row[0] . $row[1]).'" target="_blank">'.$row[0].'</a>' : 
						$row[0];
					$data['lastmonth']['referrers'][$i]['count'] = number_format($row[3]);
					$i++;
				}
			}
						
			// Now cache it
			$settings = array(
				'daily_cache' => $data
			);
			$this->save_settings($settings);
		}

		return $data;
	}


	function get_profile()
	{
		return $this->_get_settings_col('profile');
	}
	

	function get_settings()
	{
		return $this->_get_settings_col('settings');

	}
	
	
	function get_hourly_cache()
	{
		return $this->_get_settings_col('hourly_cache');
	}


	function get_daily_cache()
	{
		return $this->_get_settings_col('daily_cache');
	}
		
	
	function _get_settings_col($col)
	{
		$settings = $this->EE->db->query('SELECT '.$col.' FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
		if($settings->num_rows() > 0)
		{
			return unserialize(base64_decode($settings->row($col)));
		}
		else
		{
			return array();
		}
	}
	
	
	function save_settings($settings)
	{
		foreach($settings as $k => $v)
		{
			if($k != 'refresh_token')
			{
				$settings[$k] = base64_encode(serialize($v));
			}
		}
		
		// Did we just change profiles? If so, clear the cache
		if($new_profile = $this->EE->input->post('profile'))
		{
			$query = $this->EE->db->query('SELECT profile FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
			if($query->num_rows() > 0)
			{
				$existing_profile = unserialize(base64_decode($query->row('profile')));
				if($new_profile != $existing_profile['id'])
				{
					$settings['hourly_cache'] = $settings['daily_cache'] = '';
				}
			}
		}
		
		// Do we already have settings?
		$existing = $this->EE->db->query('SELECT site_id FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
		if($existing->num_rows() > 0)
		{
			$this->EE->db->query(
				$this->EE->db->update_string('exp_cp_analytics', $settings, 'site_id = '.$this->site_id)
			);
		}
		else
		{
			// Create a new row
			$settings['site_id'] = $this->site_id;
			$this->EE->db->query(
				$this->EE->db->insert_string('exp_cp_analytics', $settings)
			);		
		}
	}
	
	
	function reset_authentication()
	{
		if($token = $this->get_refresh_token())
		{
			$args = array(
				'token' => $token
			);
			$this->_curl_request('https://accounts.google.com/o/oauth2/revoke', $args, 'get');
		}
		$this->EE->db->query('DELETE FROM exp_cp_analytics WHERE site_id = '.$this->site_id);
	}
	
	
	function _default_query_args()
	{
		return array(
			'access_token' => $this->access_token,
			'ids' => $this->profile_id,
			'userIp' => $this->EE->input->ip_address()
		);
	}
		
	
	function _curl_request($server, $query, $method = 'post')
	{
	    if(!function_exists('json_decode'))
		{
			$this->EE->load->library('Services_json');
		}
		
		$args = '';
		foreach ($query as $key => $value)
		{
			$args .= trim($key).'='.trim($value).'&';
		}
		$args = rtrim($args, '&');
		
		if($method == 'get')
		{
			$server .= '?'.$args;
		}
				
		$ch = curl_init($server);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		if($method == 'post')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		}
		/*
			Some issues have been popping-up with IPv6 whch I do not understand, as I am faking my way through this whole thing.
			Forcing an IPv4 connection seems to work. It will likely fuck up something else though.
		*/
		if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4'))
		{
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}		
		curl_setopt($ch, CURLOPT_REFERER, 'http://'.$_SERVER['SERVER_NAME']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($ch);
		curl_close($ch);
		/*
			We pass a second argument of "true" to tell the native json_decode() function to return an array, not an object.
			This is because EE's bundled Services_JSON is hard-coded to return an array, so we have to be matchy.
		*/
		return json_decode($response, true);
	}


	function _avg_pages($pageviews, $visits)
	{
		return ($pageviews > 0 && $visits > 0) ? round($pageviews / $visits, 2) : 0;
	}
	
	
	function _avg_visit($seconds, $visits)
	{
		if($seconds > 0 && $visits > 0)
		{
			$avg_secs = $seconds / $visits;
			// This little snippet by Carson McDonald, from his Analytics Dashboard WP plugin
			$hours = floor($avg_secs / (60 * 60));
			$minutes = floor(($avg_secs - ($hours * 60 * 60)) / 60);
			$seconds = $avg_secs - ($minutes * 60) - ($hours * 60 * 60);
			return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
		}
		else
		{
			return '00:00:00';
		}
	}
	
	
	function _chart_data($rows, $days = 30)
	{
		$stats = array();
		$i = 0;
		
		if($days < count($rows))
		{
			$rows = array_slice($rows, -$days, $days);
		}
		
		foreach($rows as $row)
		{
			// Date format in $row[0] is YYYYMMDD
			$stats[$i] = "['".$this->EE->localize->decode_date('%M %j%S', strtotime($row[0].' 12:00:00', $this->EE->localize->now))."', ";
			$stats[$i] .= $row[2].', ';
			$stats[$i] .= $row[1].']';
			$i++;
		}
		return implode(',', $stats);
	}
	
	
	function _sparkline($rows, $metric)
	{
		$max = 0; $stats = array();
		
		foreach($rows as $row)
		{
			switch($metric) {
				case "pageviews":
					$datapoint = $row[1];
					break;
				case "visits":	
					$datapoint = $row[2];
					break;
				case "newvisits":
					$datapoint =  ($row[3] > 0 && $row[2] > 0) ? $row[3] / $row[2] : 0;
					break;
				case "time":
					$datapoint = $row[4];
					break;
				case "avgpages":
					$datapoint = ($row[2] > 0 && $row[1] > 0) ? $row[1] / $row[2] : 0;
					break;
				case "bouncerate":
					$datapoint = ($row[6] > 0 && $row[5] > 0) ? $row[5] / $row[6] : 0;
					break;
			}
			$max = ($max < $datapoint) ? $datapoint : $max;
			$stats[] = $datapoint;
		}
		
		// Build Google Chart url
		$base = 'https://chart.googleapis.com/chart?';
		$args = array(
			'cht=ls',
			'chs=120x20',
			'chm=B,FFFFFF66,0,0,0',
			'chco=FFFFFFEE',
			'chf=c,s,FFFFFF00|bg,s,FFFFFF00',
			'chd=t:'.implode(',', $stats),
			'chds=0,'.$max
		);
		
		$curl_url = $base.implode('&', $args);
		$src_url = $base.implode('&amp;', $args);
					
	   	// Are we caching locally?
	   	$settings = $this->get_settings();
	   	if(isset($settings['cache_sparklines']) && $settings['cache_sparklines'] == 'y')
	   	{
			// Determine cache and file destination
			if($path = $this->EE->config->item('cp_analytics_cache_path'))
			{
				$path = rtrim($path, '/');
			}
			elseif(isset($settings['cache_path']) && !empty($settings['cache_path']))
			{
				$path = rtrim($settings['cache_path'], '/');
			}
			else
			{
				$path = rtrim(PATH_THEMES, '/').'/third_party/cp_analytics';
			}
	
			// Determine cache URL
			if($url = $this->EE->config->item('cp_analytics_cache_url'))
			{
				$url = rtrim($url, '/').'/';
			}
			elseif(isset($settings['cache_url']) && !empty($settings['cache_url']))
			{
				$url = rtrim($settings['cache_url'], '/').'/';
			}
			else
			{
				$url = rtrim($this->EE->config->item('theme_folder_url'), '/').'/third_party/cp_analytics/';
			}
			
			$filename = sha1(implode(',',$stats).','.$max).'.png';
			$fullpath = $path.'/'.$filename;
				    
			// Check for cache directory
			if(!is_dir($path))
			{
				@mkdir($path, DIR_WRITE_MODE);
			}
			
			// If the directory was there, or we were able to create it, fetch the sparklines and save them	   
			if(is_dir($path))
			{	    
			    // Check for an identical file first
			    if(!file_exists($fullpath))
			    {
					// Fetch it
					$ch = curl_init($curl_url);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					if($image = curl_exec($ch))
					{
						$this->EE->load->helper('file');
						write_file($fullpath, $image);
						$sparkline = $url.$filename;
					}
					curl_close($ch);	          
			    }
			    else
			    {
					$sparkline = $url.$filename;
			    }
			}
		}
				
		// Otherwise just call the image externally
		if(!isset($sparkline)) $sparkline = $src_url;
		
		return '<img src="'.$sparkline.'" alt="" width="120" height="20" />';
	}

}