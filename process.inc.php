<?php


function sanitizeLabels($text) {
    if ($text === null) {
        $text = '';
    }
		
		$text = htmlentities($text, ENT_QUOTES, 'UTF-8');

    return $text;
}

function dpplog($level, $msg) {
    global $dpp_log_level;

    if (!isset($dpp_log_level) || $dpp_log_level < $level) {
        return;
    }

    $ts = date('Y-m-d H:i:s');
    $logFile = "/var/log/asterisk/dpviz.log";

    $fd = fopen($logFile, "a");
    if (!$fd) {
        error_log("Couldn't open log file: $logFile");
        return;
    }

    fwrite($fd, "[$ts] [Level $level] $msg\n");
    fclose($fd);
}

function secondsToTimes($seconds) {
	
		if (!is_numeric($seconds) || $seconds < 0) {
			return $seconds;
		}
		
    $seconds = (int) round($seconds); // Ensure whole number input

    $hours = (int) ($seconds / 3600);
    $minutes = (int) (($seconds % 3600) / 60);
    $remainingSeconds = $seconds % 60;

    if ($hours > 0) {
        return $remainingSeconds === 0 
					? "$hours hrs, $minutes mins" 
					: "$hours hrs, $minutes mins, $remainingSeconds secs";
    } elseif ($minutes > 0) {
        return $remainingSeconds === 0 
					? "$minutes mins" 
					: "$minutes mins, $remainingSeconds secs";
    } else {
        return "$remainingSeconds secs";
    }
}


function formatPhoneNumbers($phoneNumber, $locale = 'en_US') {
	
		if (preg_match('/[()\[\],\-_]/', $phoneNumber)) {
				return $phoneNumber;
    }
		
		
    $hasPlus = substr($phoneNumber, 0, 1) === '+';
    $digits  = preg_replace('/\D/', '', $phoneNumber);

    switch ($locale) {
        // 🇺🇸 / 🇨🇦 US & Canada
        case 'en_US':
        case 'en_CA':
            if ($hasPlus && substr($digits, 0, 1) === '1') {
                $digits = substr($digits, 1); // drop leading 1
            }
            if (strlen($digits) === 10) {
                return ($hasPlus ? '+1 ' : '') .
                       '(' . substr($digits, 0, 3) . ') ' .
                       substr($digits, 3, 3) . '-' .
                       substr($digits, 6);
            }
            break;

        // 🇪🇸 Spain
        case 'es_ES':
            if (strlen($digits) === 9) {
                return substr($digits, 0, 3) . ' ' .
                       substr($digits, 3, 3) . ' ' .
                       substr($digits, 6);
            }
            break;

        // 🇫🇷 France
        case 'fr_FR':
            if (strlen($digits) === 10) {
                return trim(chunk_split($digits, 2, ' '));
            }
            break;

        // 🇩🇪 Germany (simplified)
        case 'de_DE':
            return preg_replace('/(0\d{2,5})(\d+)/', '$1 $2', $digits);

        // 🇳🇱 Netherlands
        case 'nl_NL':
            if (substr($digits, 0, 2) === '06' && strlen($digits) === 10) {
                return preg_replace('/(06)(\d{4})(\d{4})/', '$1-$2$3', $digits);
            }
            return preg_replace('/(0\d{2})(\d{3})(\d{4})/', '$1-$2 $3', $digits);

        // 🇯🇵 Japan
        case 'ja_JP':
            return preg_replace('/(0\d{1,4})(\d{2,4})(\d{4})/', '$1-$2-$3', $digits);

        // 🇨🇳 China (simplified vs mobile)
        case 'zh_CN':
            if (preg_match('/^1\d{10}$/', $digits)) {
                return preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $digits);
            }
            return preg_replace('/(0\d{2,3})(\d{4})(\d{4})/', '($1) $2 $3', $digits);

        // 🇮🇹 Italy
        case 'it_IT':
            if (preg_match('/^3\d{8,9}$/', $digits)) {
                return preg_replace('/(\d{3})(\d{3})(\d{3,4})/', '$1 $2 $3', $digits);
            }
            return preg_replace('/(0\d{2,4})(\d+)/', '$1 $2', $digits);

        // 🇧🇷 Brazil
        case 'pt_BR':
            if (strlen($digits) === 11) {
                return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits);
            }
            if (strlen($digits) === 10) {
                return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits);
            }
            break;

        // 🇵🇹 Portugal
        case 'pt_PT':
            if (strlen($digits) === 9) {
                return preg_replace('/(\d{3})(\d{3})(\d{3})/', '$1 $2 $3', $digits);
            }
            break;

        // 🇷🇺 Russia
        case 'ru_RU':
            if (strlen($digits) === 11 && substr($digits,0,1) === '8') {
                return preg_replace('/(\d{1})(\d{3})(\d{3})(\d{2})(\d{2})/', '$1 ($2) $3-$4-$5', $digits);
            }
            break;
    }

    // fallback → return original
    return $phoneNumber;
}


function sanitize_filename($string, $replace_with = '_') {
    // Replace spaces and other separators with underscore (or your preferred character)
    $string = preg_replace('/[^\w\-\.]+/', $replace_with, $string);

    // Remove multiple consecutive replace characters
    $string = preg_replace('/' . preg_quote($replace_with, '/') . '+/', $replace_with, $string);

    // Trim leading/trailing replace character
    $string = trim($string, $replace_with);

    // Prevent reserved names (optional for Windows safety)
    $reserved = array('CON','PRN','AUX','NUL','COM1','COM2','COM3','COM4','COM5','COM6','COM7','COM8','COM9',
                 'LPT1','LPT2','LPT3','LPT4','LPT5','LPT6','LPT7','LPT8','LPT9');
    if (in_array(strtoupper($string), $reserved)) {
        $string = '_' . $string;
    }

    return $string;
}

function makeNode($module,$id,$label,$tooltip,$node){

	switch ($module) {
			case 'Announcement':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='note';
					$color='oldlace';
					break;

			case 'Callback':
					$url=strtolower($module).'&view=form&itemid='.$id;
					$shape='rect';
					$color='#F7A8A8';
					break;

			case 'Call Flow':
					$url='daynight&view=form&itemid='.$id.'&extdisplay='.$id;
					$shape='rect';
					$color='#F7A8A8';
					break;

			case 'Call Recording':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='rect';
					$color='burlywood';
					break;
			
			case 'Conferences':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='rect';
					$color='burlywood';
					break;

			case 'Custom Dests':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&destid='.$id;
					$shape='component';
					$color='#D1E8E2';
					break;

			case 'Directory':
					$url=strtolower($module).'&view=form&id='.$id;
					$shape='folder';
					$color='#eb94e2';
					break;

			case 'DISA':
					$url=strtolower($module).'&view=form&itemid='.$id;
					$shape='folder';
					$color='#eb94e2';
					break;

			case 'Dyn Route':
					$url=str_replace(' ', '', strtolower($module)).'&action=edit&id='.$id;
					$shape='component';
					$color='#92b8ef';
					break;

			case 'Feature Code':
					$url=str_replace(' ', '', strtolower($module)).'admin';
					$shape='folder';
					$color='gainsboro';
					break;

			case 'IVR':
					$url=strtolower($module).'&action=edit&id='.$id;
					$shape='component';
					$color='gold';
					break;

			case 'Languages':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='note';
					$color='#ed9581';
					break;

			case 'Misc Apps':
					$url=str_replace(' ', '', strtolower($module)).'&action=edit&extdisplay='.$id;
					$shape='rpromoter';
					$color='#5FFEF7';
					break;

			case 'Misc Dests':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='rpromoter';
					$color='coral';
					break;

			case 'Paging':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='tab';
					$color='#87CEFA';
					break;
			
			case 'Phonebook':
					$url='phonebook';
					$shape='folder';
					$color='#BDB76B';
					break;
			
			case 'Queue Callback':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&id='.$id;
					$shape='rect';
					$color='#98FB98';
					break;

			case 'Queues':
					$url=strtolower($module).'&view=form&extdisplay='.$id;
					$shape='hexagon';
					$color='mediumaquamarine';
					break;

			case 'Queue Priorities':
					$url='queueprio&view=form&extdisplay='.$id;
					$shape='rect';
					$color='#FFC3A0';
					break;

			case 'Ring Groups':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='rect';
					$color='#92b8ef';
					break;

			case 'Set CID':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&id='.$id;
					$shape='note';
					$color='#ed9581';
					break;

			case 'Time Conditions':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&itemid='.$id;
					$module="TC";
					$shape='invhouse';
					$color='dodgerblue';
					break;
			
			case 'TTS':
					$url=strtolower($module).'&view=form&id='.$id;
					$shape='note';
					$color='#ed9581';
					break;

			case 'Trunks':
					$idArray=explode(",",$id);
					$url=strtolower($module).'&tech='.$idArray[0].'&extdisplay=OUT_'.$idArray[1];
					$shape='rarrow';
					$color='#66CDAA';
					break;
					
			case 'VM Blast':
					$url=str_replace(' ', '', strtolower($module)).'&view=form&extdisplay='.$id;
					$shape='folder';
					$color='gainsboro';
					break;

			case 'Virtual Queues':
					$url='vqueue&action=modify&id='.$id;
					$module='VQueue';
					$shape='hexagon';
					$color='#00FA9A';
					break;

			case 'Voicemail':
					$url='voicemail&action=bsettings&ext='.$id;
					$shape='folder';
					$color='#979291';
					break;

			default:
					// Code to execute if no case matches
	}

	$node->attribute('label', "{$module}: {$label}");
	$node->attribute('tooltip', $tooltip);
	$node->attribute('URL', htmlentities('/admin/config.php?display='.$url));
	$node->attribute('target', '_blank');
	$node->attribute('shape', 'rect');
	$node->attribute('fillcolor', $color);
	$node->attribute('style', 'rounded,filled');
	
	return $node;
}

function stopNode($dpgraph,$id){
		$undoNode = $dpgraph->beginNode('undoLast'.$id,
			array(
				'label' => '+',
				'tooltip' => _('Click to continue...'),
				'shape' => 'circle',
				'URL' => '#',
				'fontcolor' => '#FFFFFF',
				'fontsize' => '45pt',
				'fixedsize' => true,
				'fillcolor' => '#4A90E2',
				'style' => 'rounded,filled'
			)
		);				
		return $undoNode;
}

function notFound($module,$destination,$node){
	
	$pos = strrpos($destination, ',');
	if ($pos !== false) {
			$output = substr($destination, 0, $pos);
	} else {
			$output = $destination; // No comma found
	}
	
	$node->attribute('label', _('Bad Dest').": {$module}: {$output}");
	$node->attribute('tooltip', _('Bad Dest').": {$module}: {$output}");
	$node->attribute('shape', 'rect');
	$node->attribute('fillcolor', 'red');
	$node->attribute('style', 'rounded,filled');
	
	return $node;
}

function findRecording($route,$id){
	if (is_numeric($id)){
		
		if (isset($route['recordings'][$id])){
			$name=$route['recordings'][$id]['displayname'];
		}else{
			$name=_('not found');
		}
	}elseif ($id==''){
		$name=_('None');
	}else{
		$name=$id;
	}
	return $name;
}

function checkTimeGroupLogic($entry) {
    list($time, $dow, $dom, $month) = explode('|', $entry);

    $errors = array();

    $monthOrder = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,
                   'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12);

    $monthStart = $monthEnd = null;
    $monthInverted = false;

    // Check for inverted month range
    if ($month !== '*') {
        if (preg_match('/(\w{3})-(\w{3})/', strtolower($month), $m)) {
            if (isset($monthOrder[$m[1]], $monthOrder[$m[2]])) {
                $monthStart = $monthOrder[$m[1]];
                $monthEnd = $monthOrder[$m[2]];

                if ($monthStart > $monthEnd) {
                    $monthInverted = true;
                    $errors[] = _('month inverted');
                }
            }
        }
    }

    $dayStart = $dayEnd = null;
    $dayInverted = false;

    // Check for inverted day-of-month
    if ($dom !== '*' && preg_match('/(\d{1,2})-(\d{1,2})/', $dom, $d)) {
        $dayStart = (int)$d[1];
        $dayEnd = (int)$d[2];

        if ($dayStart > $dayEnd) {
            $dayInverted = true;
            $errors[] = _('day-of-month inverted');
        }
    }

    if (!empty($errors)) {
        return " ❌ " . implode(', ', $errors);
    }

    return null;
}


function translateRange($value, $map) {
    if ($value === '*') return '';
		
    $parts = explode('-', $value);
    $translated = array_map(function($part) use ($map) {
        return isset($map[$part]) ? $map[$part] : $part;
    }, $parts);
		
		
		
    return implode('-', $translated);
}


function getFeatureNum($recID, &$route) {

    $recording = lazyLoadRow($route, 'recordings', $recID);
    if (!$recording) {
        return _('Disabled');
    }

    // Skip concatenated files
    if (!empty($recording['filename']) && strpos($recording['filename'], '&') !== false) {
        return _('Disabled');
    }

    $fcKey = '*29' . $recID;
    $fc    = lazyLoadRow($route, 'featurecodes', $fcKey);

    if (!$fc) {
        return _('Disabled');
    }

    // Pick custom or default code
    $featurenum = !empty($fc['customcode']) ? $fc['customcode'] : $fc['defaultcode'];

    // Normalize enabled flags
    $recEnabled = !empty($recording['fcode']) && (string)$recording['fcode'] === '1';
    $fcEnabled  = isset($fc['enabled']) && (string)$fc['enabled'] === '1';

    if ($recEnabled && $fcEnabled) {
        return $featurenum;
    }

    return $featurenum . ' (' . _('Disabled') . ')';
}





/**
 * Add members to a queue array and keep them sorted.
 *
 * @param array &$queueMembers Reference to the members array (static or dynamic)
 * @param array $newMembers    Array of members in "enum,pen" format
 */
function addAndSortMembers(&$queueMembers, $newMembers) {
    if (!isset($queueMembers) || !is_array($queueMembers)) {
        $queueMembers = array();
    }

    // Add all new members
    foreach ($newMembers as $member) {
        $queueMembers[] = trim($member);
    }

    // Sort by pen first, then enum
    usort($queueMembers, function($a, $b) {
        $partsA = explode(',', $a);
        $partsB = explode(',', $b);

        $enumA = (int)$partsA[0];
        $penA  = (int)$partsA[1];
        $enumB = (int)$partsB[0];
        $penB  = (int)$partsB[1];

        if ($penA != $penB) {
            return $penA - $penB;
        }

        return $enumA - $enumB;
    });
}



function isExtensionRegistered($extension, $tech) {
		global $astman;   // reuse the existing manager connection

    if (!$astman || !$astman->connected()) {
        return false;
    }
    //$astman = \FreePBX::create()->astman;

    // Choose command based on technology
    switch (strtoupper($tech)) {
        case 'PJSIP':
            $cmd = "pjsip show aor $extension";
            break;
        case 'SIP':
            $cmd = "sip show peer $extension";
            break;
        case 'IAX':
            $cmd = "iax2 show peer $extension";
            break;
        default:
            return false; // Unknown technology
    }

    // Run the command
    $response = $astman->send_request('Command', array('Command' => $cmd));

    // Extract data
    $data = '';
    if (isset($response['data'])) {
        $data = is_array($response['data']) ? implode("\n", $response['data']) : $response['data'];
    }

    // Parse based on tech
    if (strtoupper($tech) === 'PJSIP') {
    $aors = parsePjsipAors($data);
			foreach ($aors as $aor) {
					foreach ($aor['contacts'] as $contactLine) {
							// Match "Avail" and extract latency
							if (preg_match('/\bAvail\b\s+([\d.]+)/i', $contactLine, $m)) {
									return true; // Consider it reachable
							}
					}
			}
		}elseif (preg_match('/Status\s*:\s*(\S+)/i', $data, $m)) {
			// SIP or IAX: look for "Status: OK" or similar
			return (stripos($m[1], 'OK') !== false);
    }

    return false;
}

function parsePjsipAors($data) {
    $lines = explode("\n", $data);
    $aors = array();
    $currentAor = null;

    foreach ($lines as $line) {
        $line = trim($line);

        if (preg_match('/^Aor:\s+(\S+)/', $line, $match)) {
            $currentAor = $match[1];
            $aors[$currentAor] = array(
                'contact_found' => false,
                'contacts' => array(),
            );
        }

        if ($currentAor && strpos($line, 'Contact:') === 0) {
            $aors[$currentAor]['contact_found'] = true;
            $aors[$currentAor]['contacts'][] = $line;
        }
    }

    return $aors;
}


function loadRegistrations() {
    static $registrations = null;

    if ($registrations !== null) {
        return $registrations; // already cached
    }

    $astman = \FreePBX::create()->astman;
    $registrations = array();

    // --- Handle PJSIP ---
    $resp = $astman->send_request('Command', ['Command' => 'database show registrar contact']);
    if (!empty($resp['data'])) {
        $lines = explode("\n", $resp['data']);
        foreach ($lines as $line) {
            if (strpos($line, '{') !== false) {
                $json = trim(substr($line, strpos($line, '{')));
                $decoded = json_decode($json, true);

                if ($decoded && !empty($decoded['endpoint']) && !empty($decoded['user_agent'])) {
                    $endpoint = $decoded['endpoint'];
                    if (!isset($registrations[$endpoint])) {
                        $registrations[$endpoint] = array();
                    }
                    $registrations[$endpoint][] = $decoded['user_agent'];
                }
            }
        }
    }

    // --- Handle SIP (chan_sip) ---
    $resp = $astman->send_request('Command', ['Command' => 'sip show peers']);
    if (!empty($resp['data'])) {
        $lines = explode("\n", $resp['data']);
        foreach ($lines as $line) {
            if (preg_match('/^(\d+)\//', trim($line), $m)) {
                $ext = $m[1];
                $peerResp = $astman->send_request('Command', ['Command' => "sip show peer $ext"]);
                if (!empty($peerResp['data']) && preg_match('/Useragent\s*:\s*(.+)/i', $peerResp['data'], $ua)) {
                    if (!isset($registrations[$ext])) {
                        $registrations[$ext] = array();
                    }
                    $registrations[$ext][] = trim($ua[1]);
                }
            }
        }
    }
    return $registrations;
}

function getUserAgent($extension) {
    $registrations = loadRegistrations();
    $key = $extension;

    if (isset($registrations[$key])) {
        return $registrations[$key];
    } else {
        return array();
    }
}



function countVmMessages($ext, $context, $folderType) {
    $basePath = "/var/spool/asterisk/voicemail/$context/$ext";

    if ($folderType === "inbox") {
        $path = "$basePath/INBOX";
        return is_dir($path) ? count(glob("$path/*.txt")) : 0;
    }

    if ($folderType === "other") {
        $folders = array("Family", "Friends", "Old", "Work", "Urgent");
        $count = 0;
        foreach ($folders as $f) {
            $path = "$basePath/$f";
            if (is_dir($path)) {
                $count += count(glob("$path/*.txt"));
            }
        }
        return $count;
    }

    // If invalid type passed, return 0
    return 0;
}


function loadExtension(&$route, $ext) {
    if (isset($route['extensions'][$ext])) {
        return $route['extensions'][$ext];
    }

    $core = \FreePBX::Core();
    $vm   = \FreePBX::Voicemail();

    $user    = $core->getUser($ext);
    $device  = $core->getDevice($ext);
    $mailbox = $vm->getMailbox($ext);

    if (!$user && !$device && !$mailbox) {
        return false; // nothing to load
    }

    // Detect tech type
    $tech = '';
    if (!empty($device['tech'])) {
        $tech = $device['tech'];
    } elseif ($user) {
        $tech = 'virtual';
    }

    // Normalize shape
    $extension = array(
        'extension' => $ext,
        'name'      => isset($user['name']) ? $user['name'] : '',
        'tech'      => $tech,
        'user'      => $user ? $user : array(),
        'device'    => $device ? $device : array(),
        'mailbox'   => $mailbox ? $mailbox : array()
    );

    $route['extensions'][$ext] = $extension;
    return $extension;
}

function hydrateExtension(&$route, $ext) {
    $extData = loadExtension($route, $ext);
    if (!$extData) return false;

    $extension = &$route['extensions'][$ext];
    $freepbx   = \FreePBX::create();

    // Registration
    if (!isset($extension['reg_status'])) {
        $extension['reg_status'] = isExtensionRegistered($ext, $extension['tech']);
    }

    // DND
    if (!isset($extension['dnd'])) {
        $dnd = false;
        if (is_object($freepbx->Donotdisturb)) {
            $dndStatus = $freepbx->Donotdisturb()->getStatusByExtension($ext);
            $dnd = ($dndStatus === 'YES');
        }
        $extension['dnd'] = $dnd;
    }

    // CF
    if (!isset($extension['cf'])) {
        $cf = array();
        if (is_object($freepbx->Callforward)) {
            $cf = $freepbx->Callforward()->getStatusesByExtension($ext);
        }
        $extension['cf'] = $cf;
    }

    // User Agent
    if (!isset($extension['ua'])) {
        $extension['ua'] = getUserAgent($ext);
    }

    // Voicemail counts
    if (!empty($extension['mailbox']) && !isset($extension['mailbox']['label'])) {
        $context = isset($extension['mailbox']['vmcontext']) ? $extension['mailbox']['vmcontext'] : '';
        $inbox   = countVmMessages($ext, $context, 'inbox');
        $other   = countVmMessages($ext, $context, 'other');

        $extension['mailbox']['label'] = sprintf(
            "%s: %d  %s: %d  %s: %d",
            _('INBOX'), $inbox,
            _('Other'), $other,
            _('Total'), $inbox + $other
        );
    }
		
		$fmfm = sql("SELECT * FROM findmefollow WHERE grpnum = " . q($ext), "getRow", DB_FETCHMODE_ASSOC);
		if ($fmfm && !DB::isError($fmfm)) {
				// Attach row
				$extension['fmfm'] = $fmfm;

				// Determine ddial
				$check = \FreePBX::Findmefollow()->getDDial($ext);
				if ($check) {
						$extension['fmfm']['ddial'] = 'EXTENSION';
				} else {
						$extension['fmfm']['ddial'] = 'DIRECT';
				}
		}


    return $extension;
}



function buildExtTooltip($ext, &$route) {
    static $tooltipCache = [];

    if (isset($tooltipCache[$ext])) {
        return $tooltipCache[$ext];
    }

    $extension = hydrateExtension($route, $ext);
    if (!$extension) {
        $tooltipCache[$ext] = '';
        return '';
    }

    $tooltip  = "\n\n" . _('Tech') . ": " . strtoupper($extension['tech']);
    $tooltip .= "\n" . _('Registration') . ": " . ($extension['reg_status'] ? _('Yes') : _('No'));

    if (!empty($extension['ua'])) {
        $tooltip .= "\n" . _('User Agent') . ":\n" . implode("\n", $extension['ua']) . "\n";
    }

    // DND & Call Forward
    $tooltip .= "\n" . _('Do Not Disturb') . ": " . ($extension['dnd'] ? _('Enabled') : _('Disabled'));
    $tooltip .= "\n" . _('Call Forward') . ": ";
    if (!empty($extension['cf']['CF']) || !empty($extension['cf']['CFB']) || !empty($extension['cf']['CFU'])) {
        $tooltip .= _('Enabled');
        foreach (array('CF','CFB','CFU') as $type) {
            if (isset($extension['cf'][$type]) && !empty($extension['cf'][$type])) {
                $tooltip .= "\n--$type: " . $extension['cf'][$type];
            }
        }
    } else {
        $tooltip .= _('Disabled');
    }

    // FMFM
    if (isset($extension['fmfm'])) {
        if ($extension['fmfm']['ddial'] === 'DIRECT') {
            $confirm = (isset($extension['fmfm']['needsconf']) && $extension['fmfm']['needsconf'] === 'CHECKED') ? _('Yes') : _('No');
            $tooltip .= "\n\nFMFM: " . _('Enabled') .
                        "\n" . _('Initial Ring Time') . ": " . secondsToTimes($extension['fmfm']['pre_ring']) .
                        "\n" . _('Ring Time') . ": " . secondsToTimes($extension['fmfm']['grptime']) .
                        "\n" . _('Follow-Me List') . ": " . $extension['fmfm']['grplist'] .
                        "\n" . _('Confirm Calls') . ": " . $confirm;
        } else {
            $tooltip .= "\n\nFMFM: " . _('Disabled');
        }
    } else {
			$tooltip .= "\n\nFMFM: " . _('Disabled');
    }
 
    // Voicemail
		if (isset($extension['mailbox']) && !empty($extension['mailbox'])) {
				$extemail = isset($extension['mailbox']['email']) ? $extension['mailbox']['email'] : '';
				$context  = isset($extension['mailbox']['vmcontext']) ? $extension['mailbox']['vmcontext'] : '';

				// Build message counts if not cached
				if (!isset($extension['mailbox']['label'])) {
						$inbox = countVmMessages($ext, $context, 'inbox');
						$other = countVmMessages($ext, $context, 'other');

						$extension['mailbox']['label'] = sprintf(
								"%s: %d  %s: %d  %s: %d",
								_('INBOX'),
								$inbox,
								_('Other'),
								$other,
								_('Total'),
								$inbox + $other
						);
				}

				// Build tooltip once
				$tooltip .= "\n\n" . _('Voicemail') . ": " . _('Enabled') . "\n" . _('Email') . ": ";

				// Append email if present
				if (!empty($extemail)) {
						$emails = explode(',', $extemail);
						$first  = true;
						foreach ($emails as $e) {
								$email = sanitizeLabels(trim($e));
								if ($first) {
										// First email, no leading line break
										$tooltip .= " " . $email;
										$first = false;
								} else {
										// Additional emails, line break + tab
										$tooltip .= "\n    " . $email;
								}
						}
				}

				// Append options if any
				if (!empty($extension['mailbox']['options'])) {
						foreach ($extension['mailbox']['options'] as $m => $mm) {
								$tooltip .= "\n" . ucfirst($m) . ": " . ucfirst($mm);
						}
				}

				// Append message counts
				$tooltip .= "\n" . $extension['mailbox']['label'];

		} else {
				$tooltip .= "\n\n" . _('Voicemail') . ": " . _('Disabled');
		}


    // Cache and return
    $tooltipCache[$ext] = $tooltip;
    return $tooltip;
}


function resolveExtensionStatus($extension, $context = 'queue', $flags = []) {
	
    // Always priority DND/CF
    if ($extension['dnd'] || !empty($extension['cf']['CF']) || !empty($extension['cf']['CFB']) || !empty($extension['cf']['CFU']) ) {
        return ['icon' => '🟡', 'label' => _('DND/CF')];
    }

    if ($context === 'queue_edge') {
        if (!empty($flags['paused'])) {
            return ['icon' => '⏸️', 'label' => ''];
        }

        // Dynamic members
        if (!empty($flags['dynamic'])) {
            if (!empty($flags['loggedin'])) {
                return ['icon' => '🔵', 'label' => ''];
            }
            // dynamic members that are offline show no icon
            return ['icon' => '', 'label' => ''];
        }
    }

    // Standard queue logic
    if ($context === 'queue') {
        if (!empty($flags['paused'])) {
            return ['icon' => '⏸️', 'label' => _('Paused')];
        }
        if (!empty($flags['loggedin'])) {
            return ['icon' => '🔵', 'label' => ''];
        }
    }

    // virtual
    if ($extension['tech']=='virtual'){
        return ['icon' => '⚪', 'label' => ''];
    }

    // Fallback: online/offline
    return [
        'icon'  => $extension['reg_status'] ? '🟢' : '🔴',
        'label' => ''
    ];
}

function lazyFetchRow(&$route, $table, $id, $query, $idcol, $multi = false, $postprocess = null) {
    global $db; // persistent FreePBX DB handle

    // Run query
    $rows = $multi
        ? $db->getAll($query, [], DB_FETCHMODE_ASSOC)
        : $db->getRow($query, [], DB_FETCHMODE_ASSOC);

    // If the query failed (e.g., table missing), return null instead of dying
    if (DB::isError($rows)) {
        return null;
    }

    // If no rows found, return null
    if (!$rows) {
        return null;
    }

    $out = $rows;

    // Optional postprocess callback
    if ($postprocess && is_callable($postprocess)) {
        $out = call_user_func($postprocess, $out, $id);
    }

    // Cache
    if (!isset($route[$table])) {
        $route[$table] = [];
    }
    $route[$table][$id] = $out;

    return $out;
}



function lazyLoadRow(&$route, $table, $id, $cidnum = '') {
    if (isset($route[$table][$id])) {
        return $route[$table][$id];
    }

    switch ($table) {
				case 'announcements':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM announcement WHERE announcement_id = " . q($id),
								'announcement_id',
								false,
								function($row) {
										$row['dest'] = isset($row['post_dest']) ? $row['post_dest'] : '';
										return $row;
								}
						);

				case 'calendar':
						return lazyFetchRow($route,$table,$id,
						"SELECT * FROM kvstore_FreePBX_modules_Calendar WHERE `key` = " . q($id) . " LIMIT 1",
								'key',
								false,
								function ($row) {
										// Decode JSON value
										$val = json_decode($row['val'], true);
										if (is_array($val)) {
												return $val;
										}
										return array(); // fallback if JSON invalid
								}
						);

				case 'callback':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM callback WHERE callback_id = " . q($id),
								'callback_id'
						);

				case 'callrecording':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM callrecording WHERE callrecording_id = " . q($id),
								'callrecording_id'
						);

				case 'daynight':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM daynight WHERE ext = " . q($id),
								'ext',
								true // multi
						);
						
				case 'directory':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM directory_details WHERE id = " . q($id),
								'id'
						);
						
				case 'disa':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM disa WHERE disa_id  = " . q($id),
								'disa_id'
						);			


				case 'dynroute':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM dynroute WHERE id = " . q($id),
								'id',
								false,
								function ($row) use (&$route, $id) {
										// Fetch destinations for this dynroute
										$dests = sql(
												"SELECT * FROM dynroute_dests WHERE dynroute_id = " . q($id),
												"getAll",
												DB_FETCHMODE_ASSOC
										);

										if (!DB::isError($dests) && $dests) {
												foreach ($dests as $dest) {
														$selid = $dest['selection'];
														$row['routes'][$selid] = $dest;
												}
										} else {
												$row['routes'] = array();
										}

										return $row;
								}
						);

				case 'ext-group':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM ringgroups WHERE grpnum = " . q($id),
								'grpnum',
								false,
								function($row) {
										if (isset($row['grplist']) && strlen($row['grplist'])) {
												$items = explode('-', $row['grplist']);
												usort($items, function($a, $b) {
														$numA = (int) rtrim($a, '#');
														$numB = (int) rtrim($b, '#');
														if ($numA == $numB) return 0;
														return ($numA < $numB) ? -1 : 1;
												});
												$row['grplist'] = implode('-', $items);
										}
										return $row;
								}
						);
						
				case 'featurecodes':
						return lazyFetchRow($route,$table,$id,
								"SELECT *
								 FROM featurecodes
								 WHERE (defaultcode = " . q($id) . " OR customcode = " . q($id) . ")",
								'defaultcode',
								false,
								function ($row) {
										// index by whichever exists, prefer custom
										$key = !empty($row['customcode']) ? $row['customcode'] : $row['defaultcode'];
										return array_merge($row, ['_key' => $key]);
								}
						);

				case 'incoming':
						// Normalize "ANY"
						if ($id === 'ANY') {
								$id = '';
						}

						// First try DID + CID
						// Check if language_incoming table exists once up front
				$tableExists = sql("SHOW TABLES LIKE 'language_incoming'", "getOne");

				if (!empty($cidnum)) {
						$sql = "SELECT * FROM incoming WHERE extension = " . q($id) .
									 " AND cidnum = " . q($cidnum);
						$row = lazyFetchRow($route, $table, $id.'|'.$cidnum, $sql, 'extension');
						if ($row) {
								// Language sub-check (CID-specific)
								if (!empty($tableExists)) {
										$langSql = "SELECT language FROM language_incoming
																WHERE extension = " . q($id) .
																" AND cidnum = " . q($cidnum);
										$langRow = sql($langSql, "getRow", DB_FETCHMODE_ASSOC);
										if ($langRow && !empty($langRow['language'])) {
												$row['language'] = $langRow['language'];
												$route[$table][$id.'|'.$cidnum] = $row; // update cache
										}
								}
								return $row;
						}
				}

				// Fallback: DID only (no CID restriction)
				$sql = "SELECT * FROM incoming WHERE extension = " . q($id) .
							 " AND (cidnum IS NULL OR cidnum = '')";
				$row = lazyFetchRow($route, $table, $id, $sql, 'extension');
				if ($row) {
						// Language sub-check (DID only)
						if (!empty($tableExists)) {
								$langSql = "SELECT language FROM language_incoming
														WHERE extension = " . q($id) .
														" AND (cidnum IS NULL OR cidnum = '')";
								$langRow = sql($langSql, "getRow", DB_FETCHMODE_ASSOC);
								if ($langRow && !empty($langRow['language'])) {
										$row['language'] = $langRow['language'];
										$route[$table][$id] = $row; // update cache
								}
						}
						return $row;
				}


				case 'ivrs':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM ivr_details WHERE id = " . q($id),
								'id',
								false,
								function($row, $id) {
										$entries = sql("SELECT * FROM ivr_entries WHERE ivr_id = " . q($id),
																	 "getAll", DB_FETCHMODE_ASSOC);
										if ($entries && is_array($entries)) {
												foreach ($entries as $ent) {
														$selid = $ent['selection'];
														$row['entries'][$selid] = $ent;
												}
										}
										return $row;
								}
						);

				case 'languages':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM languages WHERE language_id = " . q($id),
								'language_id'
						);
		
				case 'meetme':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM meetme WHERE exten = " . q($id),
								'exten'
						);

				case 'miscapps':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM miscapps WHERE miscapps_id = " . q($id),
								'miscapps_id'
						);
						
				case 'miscdest':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM miscdests WHERE id = " . q($id),
								'id'
						);

				case 'paging':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM paging_config WHERE page_group = " . q($id),
								'page_group',
								false,
								function ($row) use ($id) {
										// Fetch group members
										$members = sql(
												"SELECT * FROM paging_groups WHERE page_number = " . q($id),
												"getAll",
												DB_FETCHMODE_ASSOC
										);
										$row['members'] = array();
										if (!DB::isError($members) && $members) {
												foreach ($members as $m) {
														$row['members'][] = $m['ext'];
												}
										}
										return $row;
								}
						);

				case 'queuecallback':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM vqplus_callback_config WHERE id = " . q($id),
								'id'
						);

				case 'queues':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM queues_config WHERE extension = " . q($id),
								'extension',
								false,
								function($row, $id) {
										$row['members']['static'] = array();
										$details = sql("SELECT * FROM queues_details WHERE id = " . q($id),
																	 "getAll", DB_FETCHMODE_ASSOC);
										if ($details && is_array($details)) {
												foreach ($details as $qd) {
														if ($qd['keyword'] == 'member') {
																if (preg_match("/Local\/(\d+).*?,(\d+)/", $qd['data'], $m)) {
																		addAndSortMembers($row['members']['static'], array($m[1] . ',' . $m[2]));
																}
														} else {
																$row['data'][$qd['keyword']] = $qd['data'];
														}
												}
										}
										return $row;
								}
						);
				
				case 'queueprio':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM queueprio WHERE queueprio_id = " . q($id),
								'queueprio_id'
						);
		
				case 'recordings':
						return lazyFetchRow($route,	$table,	$id,
								"SELECT * FROM recordings WHERE id = " . q($id),
								'id'
						);
		
				case 'setcid':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM setcid WHERE cid_id = " . q($id),
								'cid_id'
						);
						
				case 'timeconditions':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM timeconditions WHERE timeconditions_id = " . q($id),
								'timeconditions_id'
						);
				
				case 'timegroups':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM timegroups_groups WHERE id = " . q($id),
								'id',
								false,
								function ($row) use (&$route, $id) {
										// Fetch details for this group
										$details = sql("SELECT * FROM timegroups_details WHERE timegroupid = " . q($id), "getAll", DB_FETCHMODE_ASSOC);
										if (!DB::isError($details) && $details) {
												$dowMap = array(
														'mon' => _('Mon'),'tue' => _('Tue'),'wed' => _('Wed'),
														'thu' => _('Thu'),'fri' => _('Fri'),'sat' => _('Sat'),'sun' => _('Sun'),
												);
												$monthMap = array(
														'jan' => _('Jan'),'feb' => _('Feb'),'mar' => _('Mar'),'apr' => _('Apr'),
														'may' => _('May'),'jun' => _('Jun'),'jul' => _('Jul'),'aug' => _('Aug'),
														'sep' => _('Sep'),'oct' => _('Oct'),'nov' => _('Nov'),'dec' => _('Dec'),
												);

												$dowOrder   = array('*'=>0,'sun'=>0,'mon'=>1,'tue'=>2,'wed'=>3,'thu'=>4,'fri'=>5,'sat'=>6);
												$monthOrder = array('*'=>0,'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,
																						'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12);

												// Sort details by month/dom/dow/time
												foreach ($details as &$tgd) {
														list($timeStr, $dowStr, $domStr, $monthStr) = explode('|', $tgd['time']);

														$tgd['_time']  = ($timeStr === '*') ? 0 : intval(str_replace(':', '', explode('-', $timeStr)[0]));
														$tgd['_dow']   = isset($dowOrder[strtolower(explode('-', $dowStr)[0])]) ? $dowOrder[strtolower(explode('-', $dowStr)[0])] : 0;
														$tgd['_dom']   = ($domStr === '*') ? 0 : intval(explode('-', $domStr)[0]);
														$tgd['_month'] = isset($monthOrder[strtolower(explode('-', $monthStr)[0])]) ? $monthOrder[strtolower(explode('-', $monthStr)[0])] : 0;
												}
												unset($tgd);

												usort($details, function($a, $b) {
														if ($a['_month'] !== $b['_month']) return $a['_month'] - $b['_month'];
														if ($a['_dom']   !== $b['_dom'])   return $a['_dom']   - $b['_dom'];
														if ($a['_dow']   !== $b['_dow'])   return $a['_dow']   - $b['_dow'];
														return $a['_time'] - $b['_time'];
												});

												$row['time'] = '';
												foreach ($details as $tgd) {
														$exploded = explode("|", $tgd['time']);
														$time  = ($exploded[0] !== "*") ? $exploded[0] : "";
														$dow   = translateRange($exploded[1], $dowMap);
														$day   = ($exploded[2] !== "*") ? $exploded[2] : "";
														$month = translateRange($exploded[3], $monthMap);

														if ($month && ($dow!='' || $day!='' || $time!='')) $month .= " | ";
														if ($day   && ($dow!='' || $time!=''))             $day   .= " | ";
														if ($dow   && ($time!=''))                         $dow   .= " | ";

														$row['time'] .= $month . $day . $dow . $time . checkTimeGroupLogic($tgd['time']) . "\l";
												}
										} else {
												$row['time'] = "No times defined";
										}
										return $row;
								}
						);
				
				case 'trunks':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM trunks WHERE trunkid = " . q($id),
								'trunkid'
						);

				case 'tts':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM tts WHERE id = " . q($id),
								'id'
						);

				case 'vmblasts':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM vmblast WHERE grpnum = " . q($id),
								'grpnum',
								false,
								function ($row) use ($id) {
										// Fetch blast members
										$members = sql(
												"SELECT * FROM vmblast_groups WHERE grpnum = " . q($id),
												"getAll",
												DB_FETCHMODE_ASSOC
										);
										$row['members'] = array();
										if (!DB::isError($members) && $members) {
												foreach ($members as $m) {
														$row['members'][] = $m['ext'];
												}
										}
										return $row;
								}
						);

				case 'vqplus_queue_config':
						return lazyFetchRow($route, $table, $id,
								"SELECT * FROM vqplus_queue_config WHERE queue_num = " . q($id),
								'queue_num',
								false,
								function($row, $id) use (&$route) {
										// Attach vqplus data to the queue entry
										if (!isset($route['queues'][$id])) {
												$route['queues'][$id] = array();
										}
										$route['queues'][$id]['vqplus'] = $row;
										return $row;
								}
						);

				case 'vqueues':
						return lazyFetchRow($route,$table,$id,
								"SELECT * FROM virtual_queue_config WHERE id = " . q($id),
								'id'
						);
		}


    return null;
}


function runAstmanCommand($cmd) {
    global $astman;

    if (!$astman || !$astman->connected()) {
        return [];
    }

    $response = $astman->send_request('Command', ['Command' => $cmd]);

    if (empty($response['data'])) {
        return [];
    }

    // Special case: keep whitespace for "dialplan show"
    if (stripos($cmd, 'dialplan show') === 0) {
       $lines = explode("\n", $response['data']);
				$lines = array_map(function($line) {
						return str_replace("\t", "    ", $line); // 4 spaces per tab
				}, $lines);
    } else {
        // Trim whitespace and drop empty lines for other commands
        $lines = array_filter(array_map('trim', explode("\n", $response['data'])), 'strlen');
    }

    return $lines;
}



function parsePenaltyAgents(array $lines) {
    $agents = [];

    foreach ($lines as $line) {
        // Match only entries like /QPENALTY/<queue>/agents/<ext> : <penalty>
        if (preg_match('#/QPENALTY/\d+/agents/(\d+)\s*:\s*(\d+)#', $line, $m)) {
            $ext = $m[1];
            $pen = $m[2];
            $agents[] = $ext . ',' . $pen;
        }
    }

    return $agents;
}

function getLoggedInAgents($qnum) {
    $loggedin = [];

    $lines = runAstmanCommand("queue show $qnum");

    foreach ($lines as $line) {
        // look only at dynamic members
        if (strpos($line, '(dynamic)') !== false &&
            preg_match('/Local\/(\d+)@from-queue/', $line, $m)) {
            $loggedin[] = $m[1];
        }
    }

    return $loggedin;
}

function getDayNightStatus($daynightnum) {
    $lines = runAstmanCommand("database show DAYNIGHT/C$daynightnum");

    $value = "";
    foreach ($lines as $line) {
        // Lines look like:  "/DAYNIGHT/C1                              : DAY"
        if (strpos($line, "/DAYNIGHT/") !== false && strpos($line, ':') !== false) {
            list(, $val) = explode(":", $line, 2);
            $value = trim($val);
            break; // first match only
        }
    }

    $dactive = $nactive = "";
    if ($value === "DAY") {
        $dactive = "("._("Active").")";
    } elseif ($value === "NIGHT") {
        $nactive = "("._("Active").")";
    }

    return [$dactive, $nactive];
}

function isMiscAppEnabled($ext) {
    $lines = runAstmanCommand("dialplan show app-miscapps");

    foreach ($lines as $line) {
        if (strpos($line, $ext) !== false) {
            return true;  // found in dialplan
        }
    }

    return false; // not found
}

function getPausedAgents($qnum) {
    $pausedAgents = [];

    $lines = runAstmanCommand("queue show $qnum");

    foreach ($lines as $line) {
        if (strpos($line, '(paused)') !== false &&
            preg_match('/Local\/(\d+)@from-queue/', $line, $m)) {
            $pausedAgents[] = $m[1];
        }
    }

    return $pausedAgents;
}


function getLabel($destination, &$route, &$unresolvedDestinations, $selectionId = null) {
    $parts = explode(',', $destination);

    $context = $parts[0];
    $ext     = isset($parts[1]) ? $parts[1] : '';
    $prio    = isset($parts[2]) ? $parts[2] : '';

    // Conference
    if ($context === 'ext-meetme') {
        $conf = lazyLoadRow($route, 'meetme', $ext);
        $desc = $conf ? $conf['description'] : _('Bad Dest');
        return "Conference: {$ext} {$desc}";
    }

    // DISA
    if ($context === 'disa') {
        $disa = lazyLoadRow($route, 'disa', $ext);
        $desc = $disa ? $disa['displayname'] : _('Bad Dest');
        return "DISA: {$desc}";
    }
		
		//Feature Code
		if ($context === 'ext-featurecodes') {
        $fc = lazyLoadRow($route, 'featurecodes', $ext);
        $desc = $fc ? $fc['description'] : _('Bad Dest');
        return "Feature Code: {$ext} - {$desc}";
    }

    // Fallback — stash unresolved with selection id
    $unresolvedDestinations[] = [
        'selection'   => $selectionId,
        'dest' => $destination
    ];

    return false;
}


