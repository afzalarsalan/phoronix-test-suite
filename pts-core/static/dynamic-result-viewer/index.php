<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2019 - 2020, Phoronix Media
	Copyright (C) 2019 - 2020, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

error_reporting(E_ALL);
if(function_exists('session_start'))
{
	session_start();
}

define('CURRENT_URI', $_SERVER['REQUEST_URI']);
define('WEB_URL_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/');
define('RESULT_VIEWER_VERSION', 2);
define('PTS_AUTO_LOAD_ALL_OBJECTS', true);

$uri_stripped = CURRENT_URI;
if(($x = strpos($uri_stripped, '&')) !== false)
{
	$args = substr($uri_stripped, $x + 1);
	$r = array();
	parse_str($args, $r);
	$_REQUEST = array_merge($r, $_REQUEST);
	$uri_stripped = substr($uri_stripped, 0, $x);
}

if(isset($_REQUEST['checkbox_compare_results']))
{
	echo '<script> window.location.href = "http://' . $_SERVER['HTTP_HOST'] . WEB_URL_PATH . 'result/' . implode(',', $_REQUEST['checkbox_compare_results']) . '"; </script>';
	exit;
}
$uri_segments = explode('/', trim((WEB_URL_PATH == '/' ? $uri_stripped : str_replace(WEB_URL_PATH, null, $uri_stripped)), '/'));
switch((isset($uri_segments[0]) ? $uri_segments[0] : null))
{
	case 'result':
		$_GET['page'] = 'result';
		$_GET['result'] = $uri_segments[1];
		break;
	case 'test':
		$_GET['page'] = 'test';
		$_GET['test'] = base64_decode($uri_segments[1]);
		break;
	case 'suite':
		$_GET['page'] = 'suite';
		$_GET['suite'] = base64_decode($uri_segments[1]);
		break;
	case 'tests':
	case 'suites':
		$_GET['page'] = $uri_segments[0];
		break;
}

if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && isset($_ENV['TEMP']) && is_file($_ENV['TEMP'] . '\pts-env-web'))
{
	foreach(explode(PHP_EOL,  file_get_contents($_ENV['TEMP'] . '\pts-env-web')) as $line)
	{
		if(!empty($line))
		{
			putenv($line);
		}
	}
}

if(getenv('PTS_VIEWER_RESULT_PATH') && getenv('PTS_VIEWER_PTS_PATH'))
{
	define('VIEWER_ACCESS_KEY', getenv('PTS_VIEWER_ACCESS_KEY'));
	define('PTS_SAVE_RESULTS_PATH', getenv('PTS_VIEWER_RESULT_PATH'));
	define('VIEWER_PHORONIX_TEST_SUITE_PATH', getenv('PTS_VIEWER_PTS_PATH'));
}
else
{
	if(!is_file('result_viewer_config.php'))
	{
		echo '<p>You must configure result_viewer_config.php!</p>';
		echo '<p>Current debug values: PTS_VIEWER_RESULT_PATH = ' . getenv('PTS_VIEWER_RESULT_PATH') . ' PTS_VIEWER_PTS_PATH = ' . getenv('PTS_VIEWER_PTS_PATH') . '</p>';
		exit;
	}
	require('result_viewer_config.php');
}

define('PTS_MODE', 'LIB');
define('PTS_AUTO_LOAD_OBJECTS', true);

if(!is_file(VIEWER_PHORONIX_TEST_SUITE_PATH . '/pts-core/pts-core.php'))
{
	echo '<p>Could not find: ' . VIEWER_PHORONIX_TEST_SUITE_PATH . '/pts-core/pts-core.php</p>';
	exit;
}
require(VIEWER_PHORONIX_TEST_SUITE_PATH . '/pts-core/pts-core.php');
pts_define_directories();

set_time_limit(0);
ini_set('memory_limit','2048M');

if(isset($_GET['PTS']))
{
	// Test for client to see if resolving properly
	echo 'PTS';
	exit;
}

if(!defined('PTS_CORE_STORAGE') && getenv('PTS_CORE_STORAGE') && is_file(getenv('PTS_CORE_STORAGE')))
{
	define('PTS_CORE_STORAGE',  getenv('PTS_CORE_STORAGE'));
}

pts_config::set_override_default_config(getenv('PTS_VIEWER_CONFIG_FILE'));
if(PTS_SAVE_RESULTS_PATH && is_writable(PTS_SAVE_RESULTS_PATH) && getenv('PTS_VIEWER_CONFIG_FILE'))
{
	define('VIEWER_CAN_MODIFY_RESULTS', pts_config::read_bool_config('PhoronixTestSuite/Options/ResultViewer/AllowSavingResultChanges', 'FALSE'));
	define('VIEWER_CAN_DELETE_RESULTS', pts_config::read_bool_config('PhoronixTestSuite/Options/ResultViewer/AllowDeletingResults', 'FALSE'));
}
else
{
	define('VIEWER_CAN_MODIFY_RESULTS', false);
	define('VIEWER_CAN_DELETE_RESULTS', false);
}


// Authenticate user and set session variables
if(isset($_POST['access_key']))
{
	if(function_exists('hash'))
	{
		$_SESSION['AccessKey'] = trim(hash('sha256', trim($_POST['access_key'])));
	}
	else
	{
		$_SESSION['AccessKey'] = trim(sha1(trim($_POST['access_key'])));
	}
}

if(VIEWER_ACCESS_KEY != null && (!isset($_SESSION['AccessKey']) || $_SESSION['AccessKey'] != VIEWER_ACCESS_KEY)) { ?>
<!doctype html>
<html lang="en">
<head>
  <title>Phoronix Test Suite - Result Portal</title>
<link rel="stylesheet" href="<?php echo WEB_URL_PATH; ?>/result-viewer.css?<?php echo PTS_CORE_VERSION; ?>">
<script type="text/javascript" src="<?php echo WEB_URL_PATH; ?>/result-viewer.js?<?php echo PTS_CORE_VERSION; ?>"></script>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
<link rel="icon" type="image/png" href="<?php echo WEB_URL_PATH; ?>favicon.png">
</head>
<body>

<div id="login_box">
<div id="login_box_left">
<h1>Phoronix Test Suite</h1>
<h2>Result Portal</h2>
</div>
<div id="login_box_right">
<form name="login_form" id="login_form" action="<?php echo CURRENT_URI; ?>" method="post"><br />
<input type="password" name="access_key" id="u_access_key" required placeholder="Access Key" /><br />
<input type="submit" value="Login" />
</form>
</div>
</div>
</body>
<?php } else {
$PAGE = null;
switch(isset($_GET['page']) ? $_GET['page'] : null)
{
	case 'update-result-file-meta':
		if(VIEWER_CAN_MODIFY_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_title']) && isset($_REQUEST['result_desc']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->set_title($_REQUEST['result_title']);
			$result_file->set_description($_REQUEST['result_desc']);
			$result_file->save();
		}
		exit;
	case 'remove-result-object':
		if(VIEWER_CAN_DELETE_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_object']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->remove_result_object_by_id($_REQUEST['result_object']);
			$result_file->save();
		}
		exit;
	case 'remove-result-run':
		if(VIEWER_CAN_DELETE_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_run']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->remove_run($_REQUEST['result_run']);
			$result_file->save();
		}
		exit;
	case 'add-annotation-to-result-object':
		if(VIEWER_CAN_MODIFY_RESULTS && isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_object']) && isset($_REQUEST['annotation']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);
			$result_file->update_annotation_for_result_object_by_id($_REQUEST['result_object'], $_REQUEST['annotation']);
			$result_file->save();
		}
		exit;
	case 'view_test_logs':
		echo '<!doctype html>
		<html lang="en">
		<head>
		  <title>Log Viewer</title>
		<link rel="stylesheet" href="' . WEB_URL_PATH . 'result-viewer.css">
		<script type="text/javascript" src="' . WEB_URL_PATH . 'result-viewer.js"></script>
		<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
		</head>
		<body>';
		if(isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_object']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);

			if(($result_object = $result_file->get_result_object_by_hash($_REQUEST['result_object'])))
			{
				$test_log_dir = $result_file->get_test_log_dir($result_object);
				if($test_log_dir && count(pts_file_io::glob($test_log_dir . '*.log')) > 0)
				{
					echo '<div style="text-align: center;"><form action="' . CURRENT_URI . '" method="post"><select name="log_select" id="log_select">';
					foreach(pts_file_io::glob($test_log_dir . '*.log') as $log_file)
					{
						$b = basename($log_file, '.log');
						echo '<option value="' . $b . '"' . ($b == $_REQUEST['log_select'] ? 'selected="selected"' : null) . '>' . $b . '</option>';
					}
					echo '</select> &nbsp; <input type="submit" value="Show Log"></form></div><br /><hr />';
					if(isset($_REQUEST['log_select']) && is_file($test_log_dir . pts_strings::simplify_string_for_file_handling($_REQUEST['log_select']) . '.log'))
					{
						$show_log = $test_log_dir . pts_strings::simplify_string_for_file_handling($_REQUEST['log_select']) . '.log';
					}
					else
					{
						$logs = pts_file_io::glob($test_log_dir . '*.log');
						$show_log = array_shift($logs);
					}
					$log_file = htmlentities(file_get_contents($show_log));
					$log_file = str_replace(PHP_EOL, '<br />', $log_file);
					echo '<br /><div style="font-family: monospace;">' . $log_file . '</div>';
				}
			}

		}
		echo '</body></html>';
		exit;
	case 'view_install_logs':
		echo '<!doctype html>
		<html lang="en">
		<head>
		  <title>Log Viewer</title>
		<link rel="stylesheet" href="' . WEB_URL_PATH . 'result-viewer.css">
		<script type="text/javascript" src="' . WEB_URL_PATH . 'result-viewer.js"></script>
		<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
		</head>
		<body>';
		if(isset($_REQUEST['result_file_id']) && isset($_REQUEST['result_object']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);

			if(($result_object = $result_file->get_result_object_by_hash($_REQUEST['result_object'])))
			{
				$install_logs = pts_file_io::glob($result_file->get_test_installation_log_dir() . '*/' . $result_object->test_profile->get_identifier_simplified() . '.log');
				if(count($install_logs) > 0)
				{
					echo '<div style="text-align: center;"><form action="' . CURRENT_URI . '" method="post"><select name="log_select" id="log_select">';
					foreach($install_logs as $log_file)
					{
						$b = basename(dirname($log_file));
						echo '<option value="' . $b . '"' . (isset($_REQUEST['log_select']) && $b == $_REQUEST['log_select'] ? 'selected="selected"' : null) . '>' . $b . '</option>';
					}
					echo '</select> &nbsp; <input type="submit" value="Show Log"></form></div><br /><hr />';
					if(isset($_REQUEST['log_select']) && is_file($result_file->get_test_installation_log_dir() . pts_strings::simplify_string_for_file_handling($_REQUEST['log_select']) . '/' . $result_object->test_profile->get_identifier_simplified() . '.log'))
					{
						$show_log = $result_file->get_test_installation_log_dir() . pts_strings::simplify_string_for_file_handling($_REQUEST['log_select']) . '/' . $result_object->test_profile->get_identifier_simplified() . '.log';
					}
					else
					{
						$show_log = array_shift($install_logs);
					}
					$log_file = htmlentities(file_get_contents($show_log));
					$log_file = str_replace(PHP_EOL, '<br />', $log_file);
					echo '<br /><div style="font-family: monospace;">' . $log_file . '</div>';
				}
			}

		}
		echo '</body></html>';
		exit;
	case 'view_system_logs':
		echo '<!doctype html>
		<html lang="en">
		<head>
		  <title>Log Viewer</title>
		<link rel="stylesheet" href="' . WEB_URL_PATH . 'result-viewer.css">
		<script type="text/javascript" src="' . WEB_URL_PATH . 'result-viewer.js"></script>
		<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
		</head>
		<body>';
		if(isset($_REQUEST['result_file_id']) && isset($_REQUEST['system_id']))
		{
			$result_file = new pts_result_file($_REQUEST['result_file_id']);

			if(($system_log_dir = $result_file->get_system_log_dir($_REQUEST['system_id'])))
			{
				$system_logs = pts_file_io::glob($system_log_dir . '*');
				if(count($system_logs) > 0)
				{
					echo '<div style="text-align: center;"><form action="' . CURRENT_URI . '" method="post"><select name="log_select" id="log_select">';
					foreach($system_logs as $log_file)
					{
						$b = basename($log_file);
						echo '<option value="' . $b . '"' . ($b == $_REQUEST['log_select'] ? 'selected="selected"' : null) . '>' . $b . '</option>';
					}
					echo '</select> &nbsp; <input type="submit" value="Show Log"></form></div><br /><hr />';
					if(isset($_REQUEST['log_select']) && is_file($system_log_dir . pts_strings::simplify_string_for_file_handling($_REQUEST['log_select'])))
					{
						$show_log = $system_log_dir . pts_strings::simplify_string_for_file_handling($_REQUEST['log_select']);
					}
					else
					{
						$show_log = array_shift($system_logs);
					}
					$log_file = htmlentities(file_get_contents($show_log));
					$log_file = str_replace(PHP_EOL, '<br />', $log_file);
					echo '<br /><div style="font-family: monospace;">' . $log_file . '</div>';
				}
			}

		}
		echo '</body></html>';
		exit;
	case 'test':
		$o = new pts_test_profile($_GET['test']);
		$PAGE .= '<h1>' . $o->get_title() . '</h1>';

		if($o->get_license() == 'Retail' || $o->get_license() == 'Restricted')
		{
			$PAGE .= '<p><em>NOTE: This test profile is marked \'' . $o->get_license() . '\' and may have issues running without third-party/commercial dependencies.</em></p>';
		}
		if($o->get_status() != 'Verified' && $o->get_status() != null)
		{
			$PAGE .= '<p><em>NOTE: This test profile is marked \'' . $o->get_status() . '\' and may have known issues with test installation or execution.</em></p>';
		}

		$table = array();
		$table[] = array('Run Identifier: ', $o->get_identifier());
		$table[] = array('Profile Version: ', $o->get_test_profile_version());
		$table[] = array('Maintainer: ', $o->get_maintainer());
		$table[] = array('Test Type: ', $o->get_test_hardware_type());
		$table[] = array('Software Type: ', $o->get_test_software_type());
		$table[] = array('License Type: ', $o->get_license());
		$table[] = array('Test Status: ', $o->get_status());
		$table[] = array('Supported Platforms: ', implode(', ', $o->get_supported_platforms()));
		$table[] = array('Project Web-Site: ', '<a target="_blank" href="' . $o->get_project_url() . '">' . $o->get_project_url() . '</a>');

		$download_size = $o->get_download_size();
		if(!empty($download_size))
		{
			$table[] = array('Download Size: ', $download_size . ' MB');
		}

		$environment_size = $o->get_environment_size();
		if(!empty($environment_size))
		{
			$table[] = array('Environment Size: ', $environment_size . ' MB');
		}

		$cols = array(array(), array());
		foreach($table as &$row)
		{
			$row[0] = '<strong>' . $row[0] . '</strong>';
			$cols[0][] = $row[0];
			$cols[1][] = $row[1];
		}
		$PAGE .= '<br /><div style="float: left;">' . implode('<br />', $cols[0]) . '</div>';
		$PAGE .= '<div style="float: left; padding-left: 15px;">' . implode('<br />', $cols[1]) . '</div>' . '<br style="clear: both;" />';
		$PAGE .= '<p>'. $o->get_description() . '</p>';

		foreach(array('Pre-Install Message' => $o->get_pre_install_message(), 'Post-Install Message' => $o->get_post_install_message(), 'Pre-Run Message' => $o->get_pre_run_message(), 'Post-Run Message' => $o->get_post_run_message()) as $msg_type => $msg)
		{
			if($msg != null)
			{
				$PAGE .= '<p><em>' . $msg_type . ': ' . $msg . '</em></p>';
			}
		}

		$dependencies = $o->get_external_dependencies();
		if(!empty($dependencies) && !empty($dependencies[0]))
		{
			$PAGE .= PHP_EOL . '<strong>Software Dependencies:</strong>' . '<br />';
			$PAGE .= implode('<br />', $dependencies);
		}

		$o_identifier = $o->get_identifier(false);
		$table = array();
		$i = 0;
		$found_result = false;
		foreach(pts_results::saved_test_results() as $id)
		{
			$result_file = new pts_result_file($id);
			foreach($result_file->get_result_objects() as $result_object)
			{
				if($result_object->test_profile->get_identifier(false) == $o_identifier)
				{
					if(!$found_result)
					{
						$found_result = true;
						$PAGE .= '<br /><br /><h2>Results Containing This Test</h2><br />';
					}
					$PAGE .= '<h2><a href="' . WEB_URL_PATH . 'result/' . $id . '">' . $result_file->get_title() . '</a></h2>';
					$PAGE .= '<div class="sub"><label for="cr_checkbox_' . $i . '"></label> ' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('l j F H:i', strtotime($result_file->get_last_modified())) . ' </div>';
					$PAGE .= '<div class="desc">' . $result_file->get_description() . '</div>';
					break;
					$i++;
				}
			}
		}
		break;
	case 'suite':
		$o = new pts_test_suite($_GET['suite']);
		$PAGE .= '<h1>' . $o->get_title() . '</h1>';

		$table = array();
		$table[] = array('Run Identifier: ', $o->get_identifier());
		$table[] = array('Profile Version: ', $o->get_version());
		$table[] = array('Maintainer: ', $o->get_maintainer());
		$table[] = array('Test Type: ', $o->get_suite_type());

		$cols = array(array(), array());
		foreach($table as &$row)
		{
			$row[0] = '<strong>' . $row[0] . '</strong>';
			$cols[0][] = $row[0];
			$cols[1][] = $row[1];
		}
		$PAGE .= '<br /><div style="float: left;">' . implode('<br />', $cols[0]) . '</div>';
		$PAGE .= '<div style="float: left; padding-left: 15px;">' . implode('<br />', $cols[1]) . '</div>' . '<br style="clear: both;" />';
		$PAGE .= '<p>'. $o->get_description() . '</p>';
		foreach($o->get_contained_test_result_objects() as $ro)
		{
			$PAGE .= '<h2><a href="' . WEB_URL_PATH . 'test/' . base64_encode($ro->test_profile->get_identifier()) . '">' . $ro->test_profile->get_title() . '</a></h2>';
			$PAGE .= '<p>' . $ro->get_arguments_description() . '</p>';
		}
		break;
	case 'tests':
		$tests = pts_openbenchmarking::available_tests(false, false, true);
		$tests_to_show = array();
		foreach($tests as $identifier)
		{
			$test_profile = new pts_test_profile($identifier);

			if($test_profile->get_title() == null)
			{
				// Don't show unsupported tests
				continue;
			}

			$tests_to_show[] = $test_profile;
		}
		if(empty($tests_to_show))
		{
			$PAGE .= '<p>No cached test profiles found.</p>';
		}
		else
		{
			$PAGE .= '<p>The ' . count($tests_to_show) . ' test profiles below are cached on the local system and in a current state. For a complete listing of available tests visit <a href="https://openbenchmarking.org/">OpenBenchmarking.org</a>.</p>';
		}

		$PAGE .= '<div class="pts_test_boxes">';
		$tests_to_show = array_unique($tests_to_show);
		function tests_cmp_result_object_sort($a, $b)
		{
			$a_comp = $a->get_test_hardware_type() . $a->get_title();
			$b_comp = $b->get_test_hardware_type() . $b->get_title();

			return strcmp($a_comp, $b_comp);
		}
		usort($tests_to_show, 'tests_cmp_result_object_sort');
		$category = null;
		$tests_in_category = 0;
		foreach($tests_to_show as &$test_profile)
		{
			if($category != $test_profile->get_test_hardware_type())
			{
				$category = $test_profile->get_test_hardware_type();
				if($category == null) continue;
				if($tests_in_category > 0)
				{
					$PAGE .= '<br style="clear: both;" /><em>' . $tests_in_category . ' Tests</em>';
				}
				$tests_in_category = 0;
				$PAGE .= '</div><a name="' . $category . '"></a>' . PHP_EOL . '<h2>' . $category . '</h2>' . PHP_EOL . '<div class="pts_test_boxes">';
				$popularity_index = pts_openbenchmarking_client::popular_tests(-1, pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'test_type'));
			}
			if($category == null) continue;
			$tests_in_category++;

			$last_updated = pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'last_updated');
			$versions = pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'versions');
			$popularity = isset($popularity_index) && is_array($popularity_index) ? array_search($test_profile->get_identifier(false), $popularity_index) : false;
			$secondary_message = null;

			if($last_updated > (time() - (60 * 60 * 24 * 30)))
			{
				$secondary_message = count($versions) == 1 ? '- <em>Newly Added</em>' : '- <em>Recently Updated</em>';
			}
			else if($popularity === 0)
			{
				$secondary_message = '- <em>Most Popular</em>';
			}
			else if($popularity < 6)
			{
				$secondary_message = '- <em>Very Popular</em>';
			}

			$PAGE .= '<a href="' . WEB_URL_PATH . 'test/' . base64_encode($test_profile->get_identifier()) . '"><div class="table_test_box"><strong>' . $test_profile->get_title(). '</strong><br /><span>~' . pts_strings::plural_handler(max(1, round(pts_openbenchmarking_client::read_repository_test_profile_attribute($test_profile, 'average_run_time') / 60)),
'min') . ' run-time ' . $secondary_message . '</span></div></a>';
		}
		if($tests_in_category > 0)
		{
			$PAGE .= '<br style="clear: both;" /><em>' . $tests_in_category . ' Tests</em>';
		}
		$PAGE .= '</div>';
		break;
	case 'suites':
		$suites = pts_test_suites::all_suites_cached();
		$suites_to_show = array();
		foreach($suites as $identifier)
		{
			$test_suite = new pts_test_suite($identifier);

			if($test_suite->get_title() == null)
			{
				// Don't show unsupported suites
				continue;
			}

			$suites_to_show[] = $test_suite;
		}
		if(empty($suites_to_show))
		{
			$PAGE .= '<p>No cached test suites found.</p>';
		}
		else
		{
			$PAGE .= '<p>The ' . count($suites_to_show) . ' test suites below are cached on the local system and in a current state. For a complete listing of available test suites visit <a href="https://openbenchmarking.org/">OpenBenchmarking.org</a>.</p>';
		}

		$PAGE .= '<div class="pts_test_boxes">';
		$suites_to_show = array_unique($suites_to_show);
		function suites_cmp_result_object_sort($a, $b)
		{
			$a_comp = $a->get_suite_type() . $a->get_title();
			$b_comp = $b->get_suite_type() . $b->get_title();

			return strcmp($a_comp, $b_comp);
		}
		usort($suites_to_show, 'suites_cmp_result_object_sort');
		$category = null;
		$suites_in_category = 0;
		foreach($suites_to_show as &$test_suite)
		{
			if($category != $test_suite->get_suite_type())
			{
				$category = $test_suite->get_suite_type();
				if($category == null) continue;
				if($suites_in_category > 0)
				{
					$PAGE .= '<br style="clear: both;" /><em>' . $suites_in_category . ' Suites</em>';
				}
				$suites_in_category = 0;
				$PAGE .= '</div><a name="' . $category . '"></a>' . PHP_EOL . '<h2>' . $category . '</h2>' . PHP_EOL . '<div class="pts_test_boxes">';
			}
			if($category == null) continue;
			$suites_in_category++;

			$last_updated = pts_openbenchmarking_client::read_repository_test_suite_attribute($test_suite->get_identifier(), 'last_updated');
			$versions = pts_openbenchmarking_client::read_repository_test_suite_attribute($test_suite->get_identifier(), 'versions');
			$secondary_message = null;

			if($last_updated > (time() - (60 * 60 * 24 * 45)))
			{
				// Mark it as newly updated if uploaded in past 3 weeks
				$secondary_message = count($versions) == 1 ? '- <em>Newly Added</em>' : '- <em>Recently Updated</em>';
			}

			$PAGE .= '<a href="' . WEB_URL_PATH . 'suite/' . base64_encode($test_suite->get_identifier()) . '"><div class="table_test_box"><strong>' . $test_suite->get_title(). '</strong><br /><span>' . $test_suite->get_test_count() . ' Tests (' . $test_suite->get_unique_test_count() . ' Unique Profiles) ' . $secondary_message . '</span></div></a>';
		}
		if($suites_in_category > 0)
		{
			$PAGE .= '<br style="clear: both;" /><em>' . $suites_in_category . ' Tests</em>';
		}
		$PAGE .= '</div>';
		break;
	case 'result':
		if(false && isset($_POST) && !empty($_POST))
		{
			$req = $_REQUEST;
			unset($req['PHPSESSID']);
			header('Location: ?' . http_build_query($req));
		}
		$result_file = null;
		$result_merges = 0;
		$possible_results = explode(',', $_GET['result']);
		$results_viewing = array();
		foreach($possible_results as $rid)
		{
			if(pts_results::is_saved_result_file($rid))
			{
				if($result_file == null)
				{
					$result_file = new pts_result_file($rid);
					$results_viewing[] = $rid;
					/*if(count($possible_results) > 1)
					{
						$result_file->rename_run('PREFIX', $result_file->get_title());
					}*/
				}
				else
				{
					$rf = new pts_result_file($rid);
					$result_file->merge(array(new pts_result_merge_select($rf)), 0, $rf->get_title(), true, true);
					$result_merges++;
				}
			}
		}
		if($result_file == null)
		{
			break;
		}
		define('RESULTS_VIEWING_COUNT', count($results_viewing));
		define('RESULTS_VIEWING_ID', $results_viewing[0]);
		if($result_merges > 0)
		{
			$result_file->avoid_duplicate_identifiers();
		}

		$extra_attributes = null;
		$html_options = pts_result_viewer_settings::get_html_options_markup($result_file, $_REQUEST);
		pts_result_viewer_settings::process_request_to_attributes($_REQUEST, $result_file, $extra_attributes);
		define('TITLE', $result_file->get_title() . ' - Phoronix Test Suite');
		$PAGE .= pts_result_viewer_settings::get_html_sort_bar($result_file, $_REQUEST);
		$PAGE .= '<h1 id="result_file_title" placeholder="Title">' . $result_file->get_title() . '</h1>';
		$PAGE .= '<p id="result_file_desc" placeholder="Description">' . str_replace(PHP_EOL, '<br />', $result_file->get_description()) . '</p>';
		$PAGE .= '<div id="result-settings">';
		if(VIEWER_CAN_MODIFY_RESULTS && RESULTS_VIEWING_COUNT == 1)
		{
			$PAGE .= ' <input type="submit" id="save_result_file_meta_button" value="Save" onclick="javascript:save_result_file_meta(\'' . RESULTS_VIEWING_ID . '\'); return false;" style="display: none;">';
			$PAGE .= ' <input type="submit" id="edit_result_file_meta_button" value="Edit" onclick="javascript:edit_result_file_meta(); return false;">';
		}
		if(VIEWER_CAN_DELETE_RESULTS && RESULTS_VIEWING_COUNT == 1)
		{
			$PAGE .= ' <input type="submit" value="Delete Result File" onclick="javascript:delete_result_file(\'' . RESULTS_VIEWING_ID . '\'); return false;">';
		}
		//$PAGE .= '<p align="center"><strong>Export As: </strong> <a href="' . CURRENT_URI . '&export=pdf">PDF</a>, <a href="' . CURRENT_URI . '&export=csv">CSV</a>, <a href="' . CURRENT_URI . '&export=csv-all">CSV Individual Data</a> </p>';
		$PAGE .= '<p align="center">Jump To <a href="#table">Table</a> - <a href="#results">Results</a></p>';
		$PAGE .= '<hr /><div style="font-size: 12pt;">' . $html_options . '</div><hr style="clear: both;" />';
		$PAGE .= pts_result_viewer_settings::process_helper_html($_REQUEST, $result_file, $extra_attributes);
		$PAGE .= '</div>';
		$PAGE .= '<div class="print_notes">' . pts_result_file_output::result_file_to_system_html($result_file) . '</div>';
		$PAGE .= '<div id="result_overview_area">';
		$intent = -1;
		if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
		{
			$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
		}
		else
		{
			$table = new pts_ResultFileSystemsTable($result_file);
		}
		$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object" id="result_file_system_table">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

		if($result_file->get_system_count() == 2)
		{
			$graph = new pts_graph_run_vs_run($result_file);

			if($graph->renderGraph())
			{
				$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes) . '</p>';
			}
		}
		else if($result_file->get_system_count() > 12 && false) // TODO determine when this is sane enough to enable
		{
			$graph = new pts_graph_mini_overview($result_file, '');

			if($graph->renderGraph())
			{
				$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes) . '</p>';
			}
		}
		else if(!$result_file->is_multi_way_comparison())
		{
			foreach(array('', 'Per Watt', 'Per Dollar') as $selector)
			{
				$graph = new pts_graph_radar_chart($result_file, $selector);

				if($graph->renderGraph())
				{
					$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes) . '</p>';
				}
			}
		}
		$PAGE .= '<a name="table"></a>';
		if(!$result_file->is_multi_way_comparison())
		{
			$PAGE .= '<div class="pts_result_table">' . pts_result_file_output::result_file_to_detailed_html_table($result_file, 'grid', $extra_attributes, pts_result_viewer_settings::check_request_for_var($_REQUEST, 'sdt')) . '</div>';
		}
		else
		{
			$intent = null;
			$table = new pts_ResultFileTable($result_file, $intent);
			$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
		}
		$PAGE .= '</div>';

		$PAGE .= '<a name="table"></a><div id="results">';
		$prev_title = null;

		$identifier_mapping_to_cores = array();
		$identifier_mapping_to_threads = array();
		$identifier_mapping_to_cpu_clock = array();
		$identifier_mapping_to_ram_channels = array();

		if($result_file->get_system_count() > 1 && !$result_file->is_multi_way_comparison())
		{
			foreach($result_file->get_systems() as $system)
			{
				$t = $system->get_cpu_core_count();
				if($t > 0)
				{
					$identifier_mapping_to_cores[$system->get_identifier()] = $t;
				}
				$t = $system->get_cpu_thread_count();
				if($t > 0)
				{
					$identifier_mapping_to_threads[$system->get_identifier()] = $t;
				}
				$t = $system->get_cpu_clock();
				if($t > 0)
				{
					$identifier_mapping_to_cpu_clock[$system->get_identifier()] = $t;
				}
				$t = $system->get_memory_channels();
				if($t > 0)
				{
					$identifier_mapping_to_ram_channels[$system->get_identifier()] = $t;
				}
			}

			if(count(array_unique($identifier_mapping_to_cores)) < 2)
			{
				$identifier_mapping_to_cores = array();
			}
			if(count(array_unique($identifier_mapping_to_threads)) < 2)
			{
				$identifier_mapping_to_threads = array();
			}
			if(count(array_unique($identifier_mapping_to_cpu_clock)) < 2)
			{
				$identifier_mapping_to_cpu_clock = array();
			}
			if(count(array_unique($identifier_mapping_to_ram_channels)) < 2)
			{
				$identifier_mapping_to_ram_channels = array();
			}
		}

		//
		// SHOW THE RESULTS
		//
		$skip_ros = array();
		foreach($result_file->get_result_objects() as $i => $result_object)
		{
			//
			// RENDER TEST AND ANCHOR
			//
			$ro = clone $result_object;
			$res_desc_shortened = $result_object->get_arguments_description_shortened(false);
			$res = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
			if($res == false || in_array($i, $skip_ros))
			{
				continue;
			}
			$PAGE .= '<a name="r-' . $i . '"></a><div style="text-align: center;" id="result-' . $i . '">';

			//
			// DISPLAY TEST PORIFLE METADATA HELPER
			//
			if($result_object->test_profile->get_title() != $prev_title)
			{
				$PAGE .= '<h2>' . $result_object->test_profile->get_title() . '</h2>';
				if(is_file(PTS_INTERNAL_OB_CACHE . 'test-profiles/' . $result_object->test_profile->get_identifier() . '/test-definition.xml'))
				{
					$tp = new pts_test_profile(PTS_INTERNAL_OB_CACHE . 'test-profiles/' . $result_object->test_profile->get_identifier() . '/test-definition.xml');
					$PAGE .= '<p class="mini">' . $tp->get_description() . ' <a href="https://openbenchmarking.org/test/' . $result_object->test_profile->get_identifier() . '"><em class="hide_on_print">Learn more at OpenBenchmarking.org</em></a>.</p>';

				/*	$suites_containing_test = pts_test_suites::suites_containing_test_profile($result_object->test_profile);
					if(!empty($suites_containing_test))
					{
						foreach($suites_containing_test as $suite)
						{
							$PAGE .= $suite->get_title() . ' ' . $suite->get_identifier();
						}
					}  */
				}
				$prev_title = $result_object->test_profile->get_title();
			}

			//
			// DISPLAY GRAPH
			//

			// Run variability
			$res_per_core = false;
			$res_per_thread = false;
			$res_per_clock = false;
			$res_per_ram = false;
			$res_variability = false;

			if(!in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH', 'BOX_PLOT')) && $result_object->test_result_buffer->detected_multi_sample_result() && $result_object->test_result_buffer->get_count() > 1)
			{
				$extra_attributes['graph_render_type'] = 'HORIZONTAL_BOX_PLOT';
				$ro = clone $result_object;
				$res_variability = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
				unset($extra_attributes['graph_render_type']);
			}

			if(!empty($identifier_mapping_to_cores))
			{
				$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_cores, 'Performance Per Core', 'Core');
				if($ro)
				{
					$res_per_core = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
				}
			}
			if(!empty($identifier_mapping_to_threads))
			{
				$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_threads, 'Performance Per Thread', 'Thread');
				if($ro)
				{
					$res_per_thread = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
				}
			}
			if(!empty($identifier_mapping_to_cpu_clock))
			{
				$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_cpu_clock, 'Performance Per Clock', 'GHz');
				if($ro)
				{
					$res_per_clock = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
				}
			}
			if(!empty($identifier_mapping_to_ram_channels))
			{
				$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_ram_channels, 'Performance Per Memory Channel', 'Channel');
				if($ro)
				{
					$res_per_ram = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
				}
			}

			$tabs = array(
				'Result' => $res
				);

			foreach($result_file->get_relation_map($i) as $child_ro)
			{
				$c_ro = $result_file->get_result($child_ro);
				if($c_ro)
				{
					$desc = str_replace(array(' Monitor', $res_desc_shortened ,'()' ,')'), '', $c_ro->get_arguments_description_shortened(false));
					$tabs[($desc == $res_desc_shortened || empty($desc) ? $c_ro->test_profile->get_result_scale() : $desc)] = pts_render::render_graph_inline_embed($c_ro, $result_file, null);
					$result_file->remove_result_object_by_id($child_ro);
					$skip_ros[] = $child_ro;
				}
			}

			$tabs['Perf Per Core'] = $res_per_core;
			$tabs['Perf Per Thread'] = $res_per_thread;
			$tabs['Perf Per Clock'] = $res_per_clock;
			$tabs['Perf Per RAM Channel'] = $res_per_ram;
			$tabs['Result Confidence'] = $res_variability;

			foreach($tabs as $title => &$graph)
			{
				if(empty($graph))
				{
					unset($tabs[$title]);
				}
			}
			switch(count($tabs))
			{
				case 0:
					continue 2;
				case 1:
					$PAGE .= $res . '<br />';
					break;
				default:
					$PAGE .= '<div class="tabs">';
					foreach($tabs as $title => &$graph)
					{
						$tab_id = strtolower(str_replace(' ', '_', $title)) . '_' . $i;
						$PAGE .= '<input type="radio" name="tabs_' . $i . '" id="' . $tab_id . '"' . ($title == 'Result' ? ' checked="checked"' : '') . '>
						  <label for="' . $tab_id . '">' . $title . '</label>
						  <div class="tab">
						    ' . $graph . '
						  </div>';
					}
					$PAGE .= '</div>';
			}

			// $PAGE .= $res . '<br />';

			//
			// DISPLAY LOGS
			//
			$button_area = null;
			$test_log_dir = $result_file->get_test_log_dir($result_object);
			if($test_log_dir && count(pts_file_io::glob($test_log_dir . '*.log')) > 0)
			{
				$button_area .= ' <button onclick="javascript:display_test_logs_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\'); return false;">View Test Run Logs</button> ';
			}
			$install_logs = pts_file_io::glob($result_file->get_test_installation_log_dir() . '*/' . $result_object->test_profile->get_identifier_simplified() . '.log');
			if(count($install_logs) > 0)
			{
				$button_area .= ' <button onclick="javascript:display_install_logs_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\'); return false;">View Test Installation Logs</button> ';
			}


			//
			// EDITING / DELETE OPTIONS
			//

			if(VIEWER_CAN_DELETE_RESULTS && RESULTS_VIEWING_COUNT == 1 && !$result_object->dynamically_generated)
			{
				$button_area .= ' <button onclick="javascript:delete_result_from_result_file(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\'); return false;">Delete Result</button> ';
			}
			if(VIEWER_CAN_MODIFY_RESULTS && RESULTS_VIEWING_COUNT == 1 && !$result_object->dynamically_generated)
			{
				if($result_object->get_annotation() == null)
				{
					$button_area .= ' <button onclick="javascript:display_add_annotation_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\', this); return false;">Add Annotation</button> ';
					$PAGE .= ' <div id="annotation_area_' . $i . '" style="display: none;"> <form action="#" onsubmit="javascript:add_annotation_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\', this); return false;"><textarea rows="4" cols="50" placeholder="Add Annotation..." name="annotation"></textarea><br /><input type="submit" value="Add Annotation"></form></div>';
				}
				else
				{
					$PAGE .= '<div id="update_annotation_' . $i . '" contentEditable="true">' . $result_object->get_annotation() . '</div> <input type="submit" value="Update Annotation" onclick="javascript:update_annotation_for_result_object(\'' . RESULTS_VIEWING_ID . '\', \'' . $i . '\'); return false;">';
				}
			}
			else
			{
				$PAGE .= '<p class="mini">' . $result_object->get_annotation() . '</p>';
			}
			if($button_area != null)
			{
				$PAGE .= '<p>' . $button_area . '</p>';
			}

			$PAGE .= '</div>';
			unset($result_object);
		}
		$PAGE .= '</div>';
		break;
	case 'index':
	default:
		define('TITLE', 'Phoronix Test Suite ' . PTS_VERSION . ' Result Portal');
		$PAGE .= '<form name="search_results" id="search_results" action="' . CURRENT_URI . '" method="post"><input type="text" name="search" id="u_search" placeholder="Search Test Results" value="' . (isset($_POST['search']) ? $_POST['search'] : null) . '" /> <select name="sort_results_by"><option value="date">Sort By Date</option><option value="title">Sort By Title</option><option value="test_count">Sort By Test Count</option><option value="system_count">Sort By System Count</option></select> <input class="primary-button" type="submit" value="Update" />
</form>';
		$leading_msg = null;
		if(VIEWER_CAN_DELETE_RESULTS && isset($_GET['remove_result']) && $_GET['remove_result'] && pts_results::is_saved_result_file($_GET['remove_result']))
		{
			$deleted = pts_results::remove_saved_result_file($_GET['remove_result']);
			if($deleted)
			{
				$leading_msg = 'Deleted the <em>' . $_GET['remove_result'] . '</em> result file.';
			}
		}

		$results = pts_results::query_saved_result_files((isset($_POST['search']) ? $_POST['search'] : null), (isset($_REQUEST['sort_results_by']) ? $_REQUEST['sort_results_by'] : null));

		$total_result_points = 0;
		foreach($results as $id => $result_file)
		{
			$total_result_points += $result_file->get_test_count();
		}

		$PAGE .= '<div class="sub" style="margin: 6px 0 30px">' . count($results) . ' Result Files Containing A Combined ' . $total_result_points . ' Test Results</div>';
		$PAGE .= '<form name="compare_results" id="compare_results_id" action="' . CURRENT_URI . '" method="post"><input type="submit" value="Compare Results" id="compare_results_submit" />';
		$i = 0;
		foreach($results as $id => $result_file)
		{
			$i++;
			$PAGE .= '<h2><a href="' . WEB_URL_PATH . 'result/' . $id . '">' . $result_file->get_title() . '</a></h2>';
			$PAGE .= '<div class="sub"><input type="checkbox" name="checkbox_compare_results[]" value="' . $id . '" id="cr_checkbox_' . $i . '" /> <label for="cr_checkbox_' . $i . '"><span onclick="javascript:document.getElementById(\'compare_results_id\').submit(); return false;">Compare Results</span></label> ' . $result_file->get_test_count() . ' Tests &nbsp; &nbsp; ' . $result_file->get_system_count() . ' Systems &nbsp; &nbsp; ' . date('l j F H:i', strtotime($result_file->get_last_modified())) . ' ' . (VIEWER_CAN_DELETE_RESULTS ? ' &nbsp; &nbsp; <span onclick="javascript:delete_result_file(\'' . $id . '\'); return false;">DELETE RESULT FILE</span>' : null) . '</div>';
			$PAGE .= '<div class="desc">' . $result_file->get_description() . '</div>';

			$geometric_mean = pts_result_file_analyzer::generate_geometric_mean_result($result_file);
			if($geometric_mean)
			{
				$geo_display = null;
				$geo_display_count = 0;
				$best_result = $geometric_mean->test_result_buffer->get_max_value(false);
				foreach($geometric_mean->test_result_buffer as &$buffers)
				{
					if(empty($buffers))
						continue;

					$max_value = 0;
					foreach($buffers as &$buffer_item)
					{
						$v = $buffer_item->get_result_value();
						if(!is_numeric($v)) continue;
						$percentage = ($v / $best_result) * 100;
						$bg = pts_render::identifier_to_brand_color($buffer_item->get_result_identifier(), '');
						if($bg)
						{
							$bg = 'background: ' . $bg . '; color: #FFF';
						}
						$geo_display .=  '<div class="geo_bg_graph" style="margin-right: ' . round(100 - $percentage, 1) . '%; ' . $bg . '"><strong>' . $buffer_item->get_result_identifier() . ':</strong> ' . $v . ' (' . round($percentage, 2) . '%)</div>';
						$geo_display_count++;
					}
				}
				if($geo_display_count > 1)
				{
					$PAGE .= '<span class="sub_header">Geometric Mean</span>' . $geo_display;
				}
			}
			$PAGE .= '<br />';
		}
		$PAGE .= '</form>';
		break;

}

define('PAGE', $PAGE);

?>
<!doctype html>
<html lang="en">
<head>
  <title><?php echo defined('TITLE') ? TITLE : ''; ?></title>
<link rel="stylesheet" href="<?php echo WEB_URL_PATH; ?>result-viewer.css?<?php echo PTS_CORE_VERSION; ?>">
<link rel="icon" type="image/png" href="<?php echo WEB_URL_PATH; ?>favicon.png">
<script type="text/javascript" src="<?php echo WEB_URL_PATH; ?>result-viewer.js?<?php echo PTS_CORE_VERSION; ?>"></script>
<script>
var WEB_URL_PATH = "<?php echo WEB_URL_PATH; ?>";
</script>
<link href="//fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
</head>
<body>
<div id="header">
<div style="float: left; margin-top: 2px;">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewbox="0 0 76 41" width="76" height="41" preserveAspectRatio="xMinYMin meet">
  <path d="m74 22v9m-5-16v16m-5-28v28m-23-2h12.5c2.485281 0 4.5-2.014719 4.5-4.5s-2.014719-4.5-4.5-4.5h-8c-2.485281 0-4.5-2.014719-4.5-4.5s2.014719-4.5 4.5-4.5h12.5m-21 5h-11m11 13h-2c-4.970563 0-9-4.029437-9-9v-20m-24 40v-20c0-4.970563 4.0294373-9 9-9 4.970563 0 9 4.029437 9 9s-4.029437 9-9 9h-9" stroke="#fff" stroke-width="4" fill="none" />
</svg></div> <div style="float: left; margin-left: 10px;"> <a href="<?php echo WEB_URL_PATH; ?>">Result Viewer</a></div>
<ul>
<?php if(PTS_OPENBENCHMARKING_SCRATCH_PATH != null) { ?>
<li><a href="<?php echo WEB_URL_PATH; ?>tests/">Test Profiles</a></li>
<li><a href="<?php echo WEB_URL_PATH; ?>suites/">Test Suites</a></li>
<?php } ?>
<li><a href="<?php echo WEB_URL_PATH; ?>">Results</a></li>
</ul>
</div>

<?php

if((!isset($leading_msg) || empty($leading_msg)) && defined('PTS_CORE_STORAGE') && ($motd = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'MOTD_HTML')) != null)
{
	$leading_msg = '<em>' . $motd . '</em>';
}

if(isset($leading_msg) && $leading_msg) { echo '<div id="leading_message">' . $leading_msg . '</div>'; } ?>
<div id="main_area">
<?php echo PAGE; ?>
</div>
<div id="footer"><hr /><br /><a href="https://www.phoronix-test-suite.com/">Phoronix Test Suite</a> <?php echo PTS_VERSION; ?> - Generated <?php echo date('j F Y H:i:s'); ?> - Developed by Phoronix Media</div>
</body>
<?php }
if(function_exists('session_start'))
{
	session_write_close();
}
?>
</html>
