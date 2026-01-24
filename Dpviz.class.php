<?php
// License for all code of this FreePBX module can be found in the license file inside the module directory
// Copyright 2015 Sangoma Technologies.
// vim: set ai ts=4 sw=4 ft=php:

namespace FreePBX\modules;

class Dpviz extends \FreePBX_Helpers implements \BMO {

    private $freepbx;

    public function __construct($freepbx = null) {
        parent::__construct($freepbx);
        $this->freepbx = $freepbx;
        $this->db = $this->freepbx->Database;
    }

    protected function sendAction($action) {
        return $this->sendCurlPost("action.php", array('action' => $action));
    }

    public function install() {
        return $this->sendAction('install');
    }

    public function uninstall() {
        return $this->sendAction('uninstall');
    }
		
    public function getOptions() {
        $sql = "SELECT * FROM dpviz LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->execute();
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function editDpviz($panzoom, $horizontal, $datetime,$dynmembers, $combineQueueRing,
															$extOptional, $fmfm, $minimal, $queue_member_display, 
															$ring_member_display, $queue_penalty, $allowlist, $blacklist, $autoplay, 
															$displaydestinations, $inuseby, $insertnode)
		{
        $sql = "UPDATE dpviz SET
            `panzoom` = :panzoom,
            `horizontal` = :horizontal,
            `datetime` = :datetime,
            `dynmembers` = :dynmembers,
            `combineQueueRing` = :combineQueueRing,
            `extOptional` = :extOptional,
            `fmfm` = :fmfm,
						`minimal` = :minimal,
						`queue_member_display` = :queue_member_display,
						`ring_member_display` = :ring_member_display,
						`queue_penalty` = :queue_penalty,
						`allowlist` = :allowlist,
						`blacklist` = :blacklist,
						`autoplay` = :autoplay,
						`displaydestinations` = :displaydestinations,
						`inuseby` = :inuseby,
						`insertnode` = :insertnode
						
            WHERE `id` = 1";

        $insert = array(
            ':panzoom' => $panzoom,
            ':horizontal' => $horizontal,
            ':datetime' => $datetime,
            ':dynmembers' => $dynmembers,
            ':combineQueueRing' => $combineQueueRing,
            ':extOptional' => $extOptional,
            ':fmfm' => $fmfm,
						':minimal' => $minimal,
						':queue_member_display' => $queue_member_display,
						':ring_member_display' => $ring_member_display,
						':queue_penalty' => $queue_penalty,
						':allowlist' => $allowlist,
						':blacklist' => $blacklist,
						':autoplay' => $autoplay,
						':displaydestinations' => $displaydestinations,
						':inuseby' => $inuseby,
						':insertnode' => $insertnode
        );

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($insert);
    }

    public function doConfigPageInit($page) {
        $request = $_REQUEST;
        $action = isset($request['action']) ? $request['action'] : '';
        $panzoom = isset($request['panzoom']) ? $request['panzoom'] : '';
        $horizontal = isset($request['horizontal']) ? $request['horizontal'] : '';
        $datetime = isset($request['datetime']) ? $request['datetime'] : '';
        $dynmembers = isset($request['dynmembers']) ? $request['dynmembers'] : '';
        $combineQueueRing = isset($request['combineQueueRing']) ? $request['combineQueueRing'] : '';
        $extOptional = isset($request['extOptional']) ? $request['extOptional'] : '';
        $fmfm = isset($request['fmfm']) ? $request['fmfm'] : '';
				$minimal = isset($request['minimal']) ? $request['minimal'] : '';
				$queue_member_display = isset($request['queue_member_display']) ? $request['queue_member_display'] : '';
				$ring_member_display = isset($request['ring_member_display']) ? $request['ring_member_display'] : '';
				$queue_penalty = isset($request['queue_penalty']) ? $request['queue_penalty'] : '';
				$allowlist = isset($request['allowlist']) ? $request['allowlist'] : '';
				$blacklist = isset($request['blacklist']) ? $request['blacklist'] : '';
				$autoplay = isset($request['autoplay']) ? $request['autoplay'] : '';
				$displaydestinations = isset($request['displaydestinations']) ? $request['displaydestinations'] : '';
				$inuseby = isset($request['inuseby']) ? $request['inuseby'] : '';
				$insertnode = isset($request['insertnode']) ? $request['insertnode'] : '';

        switch ($action) {
            case 'edit':
                $this->editDpviz($panzoom, $horizontal, $datetime, $dynmembers, $combineQueueRing, 
																 $extOptional, $fmfm, $minimal, $queue_member_display, 
																 $ring_member_display, $queue_penalty, $allowlist, $blacklist, 
																 $autoplay, $displaydestinations, $inuseby, $insertnode
								);
                break;
            default:
                break;
        }
				

				//error_log(print_r($user,true));
    }

    public function ajaxRequest($req, &$setting) {
        switch ($req) {
            case 'save_options':
            case 'check_update':
            case 'make':
            case 'getrecording':
            case 'getfile':
						case 'getvoicemail':
						case 'saveview':
						case 'deleteview':
						case 'feedback':
						case 'coffee':
						case 'nodestselect':
						case 'save_nodest':
						case 'create_destination':
						case 'add_ivr_entry':
						case 'add_dyn_entry':
						case 'list_timegroups':
						case 'list_calendars':
						case 'list_calendargroups':
						case 'list_languages':
						case 'list_music':
						case 'list_recordings':
						case 'set_simtime':
						case 'need_reload_status':
						case 'get_sections':
                return true;
        }
        return false;
    }

    public function ajaxHandler() {
        $action = isset($_REQUEST['command']) ? $_REQUEST['command'] : '';
        switch ($action) {
            case 'save_options':
                $panzoom = isset($_POST['panzoom']) ? $_POST['panzoom'] : '';
                $horizontal = isset($_POST['horizontal']) ? $_POST['horizontal'] : '';
                $datetime = isset($_POST['datetime']) ? $_POST['datetime'] : '';
                $dynmembers = isset($_POST['dynmembers']) ? $_POST['dynmembers'] : '';
                $combineQueueRing = isset($_POST['combineQueueRing']) ? $_POST['combineQueueRing'] : '';
                $extOptional = isset($_POST['extOptional']) ? $_POST['extOptional'] : '';
                $fmfm = isset($_POST['fmfm']) ? $_POST['fmfm'] : '';
								$minimal= isset($_POST['minimal']) ? $_POST['minimal'] : '';
								$queue_member_display= isset($_POST['queue_member_display']) ? $_POST['queue_member_display'] : '';
								$ring_member_display= isset($_POST['ring_member_display']) ? $_POST['ring_member_display'] : '';
								$queue_penalty= isset($_POST['queue_penalty']) ? $_POST['queue_penalty'] : '';
								$allowlist = isset($_POST['allowlist']) ? $_POST['allowlist'] : '';
								$blacklist = isset($_POST['blacklist']) ? $_POST['blacklist'] : '';
								$autoplay = isset($_POST['autoplay']) ? $_POST['autoplay'] : '';
								$displaydestinations = isset($_POST['displaydestinations']) ? $_POST['displaydestinations'] : '';
								$inuseby = isset($_POST['inuseby']) ? $_POST['inuseby'] : '';
								$insertnode = isset($_POST['insertnode']) ? $_POST['insertnode'] : '';

                $success = $this->editDpviz($panzoom, $horizontal, $datetime, $dynmembers, $combineQueueRing,
																						$extOptional, $fmfm, $minimal, $queue_member_display, 
																						$ring_member_display, $queue_penalty, $allowlist, $blacklist, 
																						$autoplay, $displaydestinations, $inuseby, $insertnode
								);
                echo json_encode(array('success' => $success));
                exit;

            case 'check_update':
                $result = $this->checkForGitHubUpdate();
                if (isset($result['error'])) {
                    echo json_encode(array('status' => 'error', 'message' => $result['error']));
                } else {
                    echo json_encode(array(
                        'status' => 'success',
                        'current' => $result['current'],
                        'latest' => $result['latest'],
                        'up_to_date' => $result['up_to_date']
                    ));
                }
                exit;

            case 'make':
								$fpbx = \FreePBX::create();

								if (isset($fpbx->View) && method_exists($fpbx->View, 'setAdminLocales')) {
										$fpbx->View->setAdminLocales();
										\bindtextdomain("dpviz", __DIR__ . "/i18n");
										\textdomain("dpviz");
								} else {
										// fallback or do nothing
								}
                
                include 'process.php';
                echo json_encode(array(
                    'vizHeader' => $header,
                    'gtext' => json_decode($gtext)
                ));
                exit;

            case 'getrecording':
								$mod = isset($_POST['app']) ? $_POST['app'] : '';
                $id = isset($_POST['id']) ? $_POST['id'] : 0;
								$lang=$_POST['lang'];

								if ($mod=='systemrecording'){
										$desc = '';
										$recId = $id;
										
								} elseif ($mod=='announcement'){
									$annResults= \FreePBX::Announcement()->getAnnouncements();
									foreach ($annResults as $a=>$aa){
										if ($aa['announcement_id']==$id){
											$desc = $aa['description'];
											$recId = $aa['recording_id'];
											break;											
										}
									}
									
								} elseif ($mod=='ivr'){
									$ivrResults= \FreePBX::Ivr()->getDetails($id);
									$desc = $ivrResults['name'];
									$recId = $ivrResults['announcement'];
									
								} elseif ($mod=='queues'){
									$sql = "SELECT * FROM queues_config WHERE extension = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$qResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $qResults['descr'];
									if (isset($qResults['joinannounce_id']) && $qResults['joinannounce_id'] !==''){
										$recId = $qResults['joinannounce_id'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='ringgroup'){
									$sql = "SELECT * FROM ringgroups WHERE grpnum = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$rgResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $rgResults['description'];
									if (isset($rgResults['annmsg_id']) && $rgResults['annmsg_id'] !==''){
										$recId = $rgResults['annmsg_id'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='vmblast'){
									$sql = "SELECT * FROM vmblast WHERE grpnum = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$vmblastResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $vmblastResults['description'];
									if (isset($vmblastResults['audio_label']) && $vmblastResults['audio_label'] !==''){
										$recId = $vmblastResults['audio_label'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='pagegroups'){
									$sql = "SELECT * FROM paging_config WHERE page_group = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$vmblastResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $vmblastResults['description'];
									if (isset($vmblastResults['announcement']) && $vmblastResults['announcement'] !==''){
										$recId = $vmblastResults['announcement'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='dynroute'){
									$sql = "SELECT * FROM dynroute WHERE id = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$dynResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $dynResults['name'];
									if (isset($dynResults['announcement_id']) && $dynResults['announcement_id'] !==''){
										$recId = $dynResults['announcement_id'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='queuecallback'){
									$sql = "SELECT * FROM vqplus_callback_config WHERE id = ?";
									$sth = $this->db->prepare($sql);
									$sth->execute(array($id));
									$qcbResults = $sth->fetch(\PDO::FETCH_ASSOC);
									$desc = $qcbResults['name'];
									if (!empty($qcbResults['announcement'])){
										$recId = $qcbResults['announcement'];
									}else{
										$recId=0;
									}
									
								} elseif ($mod=='voicemail'){
										$desc='voicemail';
										
										if (preg_match('/vm([a-z])(\d+)/', $id, $matches)) {
											$type = $matches[1]; // "u"
											$ext = $matches[2]; // "210"
											$vm = \FreePBX::Voicemail();
											$vmResults = $vm->getGreetingsByExtension($ext);
											$typeMap = array(
													'u' => 'unavail',
													'b' => 'busy',
											);
											$audiolist='';
											/*  TODO all VM greetings??
											foreach ($vmResults as $type=>$file){
												$audiolist.=$file.'&';
											}
											$audiolist = rtrim($audiolist, '&');
											*/
											$greetKey = isset($typeMap[$type]) ? $typeMap[$type] : null;
											$audiolist = isset($vmResults[$greetKey]) ? $vmResults[$greetKey] : null;
											
											$recId = 'voicemail';
											$displayname= _('Ext').' '.$ext;
											
										}
								}

								if (is_numeric($recId) && $recId > 0){
									
									$fpbxResults= \FreePBX::Recordings()->getRecordingById($recId);
									if (!empty($fpbxResults)){
										//getrecording
										if (isset($fpbxResults) && !empty($fpbxResults['playbacklist'])){
											$audiolist='';
											foreach ($fpbxResults['playbacklist'] as $f){
												if (!empty($fpbxResults['soundlist'][$f]['filenames'][$lang])){
													$audiolist.='/var/lib/asterisk/sounds/'.$lang.'/'.$f.'&';
												}
											}
											$audiolist = rtrim($audiolist, '&');
											$displayname = $fpbxResults['displayname'];
										}
										
									}else{
										$recId = 0;
										$displayname = '';
										$audiolist = '';
									}
								}elseif ($recId==='voicemail'){
									
									
								}else{
									$displayname = '';
									$audiolist = '';
								}
								
                header('Content-Type: application/json');
                echo json_encode(array(
										'modDescription' => $desc,
										'recId' => $recId,
                    'displayname' => $displayname,
                    'filename' => $audiolist
										
                ));
                exit;

            case 'getfile':
                if (isset($_POST['file'])){
									$filename= $_POST['file'];
									if (substr($filename, -4) !== ".wav") {
										$filename .= ".wav";
									}

									if (file_exists($filename) && is_readable($filename)) {
											$xFilename = str_replace(
													array("/var/lib/asterisk/sounds/", "/var/spool/asterisk/voicemail/"),
													"",
													$filename
											);
											header('Content-Type: audio/wav');
											header('Content-Length: ' . filesize($filename));
											header('Content-Disposition: inline; filename="' . basename($xFilename) . '"');
											header('X-Filename: ' . "$xFilename");
											readfile($filename);
											exit;
									} else {
											http_response_code(404);
											echo "File not found.";
											exit;
									}
								}

                exit;

						case 'saveview':
								try {
										$description = isset($_POST['description']) ? trim($_POST['description']) : '';
										$ext         = isset($_POST['ext']) ? trim($_POST['ext']) : '';
										$jump        = isset($_POST['jump']) ? trim($_POST['jump']) : '';
										$viewId      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
										$skip        = '';

										// Decode 'skip' JSON array if present and sanitize each value
										if (!empty($_POST['skip'])) {
												$decoded = json_decode($_POST['skip'], true);
												if (is_array($decoded)) {
														$skipArray = array_map(function($item) {
																return trim($item); // remove whitespace
														}, $decoded);
														$skip = implode(';', $skipArray);
												}
										}

										$params = array(
												':description' => $description,
												':ext'         => $ext,
												':jump'        => $jump,
												':skip'        => $skip
										);

										if ($viewId > 0) {
												$sql = "UPDATE dpviz_views 
																SET description = :description, ext = :ext, jump = :jump, skip = :skip
																WHERE id = :id";
												$params[':id'] = $viewId;
										} else {
												$sql = "INSERT INTO dpviz_views (description, ext, jump, skip)
																VALUES (:description, :ext, :jump, :skip)";
										}

										// Execute query
										$stmt = $this->db->prepare($sql);
										$stmt->execute($params);

										echo json_encode(array(
												'status' => 'success',
												'message' => 'Saved successfully.'
										));

								} catch (PDOException $e) {
										
										error_log($e->getMessage());

										// Generic error message to client
										echo json_encode(array(
												'status' => 'error',
												'message' => 'Database error.'
										));
								}


                exit;
								
						case 'deleteview':
								try {
										if (isset($_POST['id']) && $_POST['id'] !== '') {
												$viewId = $_POST['id'];
												$stmt = $this->db->prepare("DELETE FROM dpviz_views WHERE id = :id");
												$stmt->execute(array(':id' => $viewId));

												echo json_encode(array('status' => 'success', 'message' => 'View deleted successfully.'));
										} else {
												echo json_encode(array('status' => 'error', 'message' => 'Missing or empty ID.'));
										}
								} catch (PDOException $e) {
										echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
								}

									exit;

						case 'feedback':
								// Get data from form
								$message = isset($_POST['message']) ? $_POST['message'] : '';
								$email   = isset($_POST['email']) ? $_POST['email'] : '';
								$lang   = isset($_POST['lang']) ? $_POST['lang'] : '';
								
								if (trim($message) === '') {
										echo json_encode(array('status' => 'error', 'message' => 'Message is required'));
										exit;
								}

								$postFields = array(
										'message' => $message,
										'email' => $email,
										'lang' => $lang
								);

								$data = $this->sendCurlPost("feedback.php", $postFields);
								
								header('Content-Type: application/json');

								if (isset($data['status']) && $data['status'] === 'ok') {
										echo json_encode(array('status' => 'ok'));
								} else {
										$errorMsg = isset($data['message']) ? $data['message'] : 'External service failed';
										echo json_encode(array('status' => 'error', 'message' => $errorMsg));
								}
								exit;

						case 'coffee':
								return $this->sendAction('coffee');
								exit;
								
						case 'nodestselect':
								$freepbx = \FreePBX::create();
								$vm = $freepbx->Modules->loadFunctionsInc('voicemail');
								$destinations = $freepbx->Modules->getDestinations();
								
								$grouped = [];

								foreach ($destinations as $key => $d) {
										if (!is_array($d) || empty($d['destination'])) continue;

										// Use "name" (Announcements, Callback, Calendar, etc.) as module label
										$modName = $d['name'] ?: ucfirst($d['module']);
										// Special handling for Core
										if ($d['module'] === 'core') {
											$modName = $d['category'];
										}
				
										$grouped[$modName][] = [
												'value' => $d['destination'],
												'label' => $d['description']
										];
										ksort($grouped);
								}

								header('Content-Type: application/json');
								echo json_encode($grouped);
								exit;

						case 'save_nodest':
						
								header('Content-Type: application/json');

								$raw = file_get_contents('php://input');
								$payload = json_decode($raw, true);

//error_log(print_r($payload,true));

								$titleText   = isset($payload['titleText']) ? trim($payload['titleText']) : '';
								$destination = isset($payload['destination']) ? trim($payload['destination']) : '';

								if ($titleText === '' || $destination === '') {
										echo json_encode(['status' => 'error', 'message' => 'Missing titleText or destination']); exit;
								}

								// Expect "noDest<context>,<exten>,<priority>,<lang>"
								// e.g. "noDestfrom-trunk,8884443377,1,en"
								if (strpos($titleText, 'noDest') !== 0 && strpos($titleText, 'insertDest') !== 0 ) {
										echo json_encode(['status' => 'error', 'message' => 'Invalid titleText format']); exit;
								}

								if (strpos($titleText, 'noDest') === 0) {
										$rest = substr($titleText, 6);  // remove "noDest"
								} elseif (strpos($titleText, 'insertDest') === 0) {
										$rest = substr($titleText, 10); // remove "insertDest"
								} else {
										$rest = $titleText; // fallback
								}
								
								$parts = explode(',', $rest);
								if (count($parts) < 2) {
										echo json_encode(['status' => 'error', 'message' => 'Invalid title parts']); 
										exit;
								}

								$context = $parts[0];
								$id      = $parts[1];

								// Normalize context/id
								if (preg_match('/^app-announcement-(\d+)$/', $context, $m)) {
										$context = 'app-announcement';
										$id      = $m[1];
								}

								if (preg_match('/^(?:no|insert)Destdynroute-(\d+),.+,\d+,.+\-(\w+)$/', $titleText, $m)) {
										$id      = $m[1];
										$context = 'dynroute'.$m[2];
								}

								if (preg_match('/^sel-(\d+)&(\d+)/', $context, $m)) {
										$context = 'ivrentries';
										$id      = $m[1];
										$sel     = $m[2];
								}
								
								if (preg_match('/^sel-(\d+)&(i|t)/', $context, $m)) {
									$id = $m[1];
									$map = [
											'i' => 'ivrinvalid',
											't' => 'ivrtimeout'
									];

									if (isset($map[$m[2]])) {
											$context = $map[$m[2]];
									}
										
								}
								
								if (preg_match('/^(?:no|insert)Destivr-(\d+),.+,\d+,.+\-(\w+)$/', $titleText, $m)) {
										$id      = $m[1];
										$context = 'ivr'.$m[2];
								}

								if (preg_match('/^(?:no|insert)Destapp-daynight,(\d+),\d+,.+\-((?:day|night))/', $titleText, $m)) {
										$context = 'app-daynight';
										$id      = $m[1];
										$mode    = $m[2];
								}

								if (preg_match('/^(?:no|insert)Desttimeconditions,(\d+),\d+,.+\-((?:true|false)goto)/', $titleText, $m)) {
										$context = 'timeconditions'.$m[2];
										$id      = $m[1];
								}
								
								if (preg_match("/^(?:no|insert)Destfrom-trunk,((?:[^\[&,]+(?:\[[^\]]+\])?))(&[^,]*)?,(\d+),(.+)/", $titleText, $m)) {
										$context = 'from-trunk';
										$id      = str_replace("ANY", "", $m[1]);;
										$cid     = str_replace("&", "", $m[2]);
								}
								
								if (!is_numeric($id) && $context!='from-trunk') {
										echo json_encode(['status' => 'error', 'message' => "Unsupported id: $id $context"]);
										exit;
								}

								// Map context -> table/column
								$map = [
										'from-trunk'                => ['table' => 'incoming',       'key_cols' => ['extension','cidnum'], 'dest_col' => 'destination'],
										'app-announcement'          => ['table' => 'announcement',   'key_cols' => ['announcement_id'],    'dest_col' => 'post_dest'],
										'app-daynight'              => ['table' => 'daynight',       'key_cols' => ['ext','dmode'],        'dest_col' => 'dest'],
										'app-languages'             => ['table' => 'languages',      'key_cols' => ['language_id'],        'dest_col' => 'dest'],
										'app-setcid'                => ['table' => 'setcid',         'key_cols' => ['cid_id'],             'dest_col' => 'dest'],
										'dynrouteinvalid'           => ['table' => 'dynroute',       'key_cols' => ['id'],                 'dest_col' => 'invalid_dest'],
										'dynroutedefault'           => ['table' => 'dynroute',       'key_cols' => ['id'],                 'dest_col' => 'default_dest'],
										'ext-callrecording'         => ['table' => 'callrecording',  'key_cols' => ['callrecording_id'],   'dest_col' => 'dest'],
										'ext-group'                 => ['table' => 'ringgroups',     'key_cols' => ['grpnum'],             'dest_col' => 'postdest'],
										'ext-queues'                => ['table' => 'queues_config',  'key_cols' => ['extension'],          'dest_col' => 'dest'],
										'ivrinvalid'                => ['table' => 'ivr_details',    'key_cols' => ['id'],                 'dest_col' => 'invalid_destination'],
										'ivrtimeout'                => ['table' => 'ivr_details',    'key_cols' => ['id'],                 'dest_col' => 'timeout_destination'],
										'ivrentries'                => ['table' => 'ivr_entries',    'key_cols' => ['ivr_id','selection'], 'dest_col' => 'dest'],
										'timeconditionstruegoto'    => ['table' => 'timeconditions', 'key_cols' => ['timeconditions_id'],  'dest_col' => 'truegoto'],
										'timeconditionsfalsegoto'   => ['table' => 'timeconditions', 'key_cols' => ['timeconditions_id'],  'dest_col' => 'falsegoto'],
										// add more mappings later...
								];

								if (!isset($map[$context])) {
										echo json_encode(['status' => 'error', 'message' => "Unsupported context: $context"]);
										exit;
								}

								$table   = $map[$context]['table'];
								$keyCols = $map[$context]['key_cols'];
								$destCol = $map[$context]['dest_col'];

								// Initialize
								$where  = [];
								$params = [':dest' => $destination];

								foreach ($keyCols as $col) {
										$where[] = "`$col` = :$col";
										switch ($col) {
												case 'announcement_id':
												case 'callrecording_id':
												case 'dynroute':
												case 'extension':
												case 'ext':
												case 'cid_id':
												case 'ext-queues':
												case 'grpnum':
												case 'id':
												case 'ivr_id':
												case 'language_id':
												case 'timeconditions_id':
														$params[":$col"] = $id;
														break;
												case 'dmode':
														$params[":$col"] = $mode;
														break;
												case 'cidnum':
														$params[":$col"] = $cid;
														break;
												case 'selection':
														$params[":$col"] = $sel;
										}
								}

								$whereSql = implode(' AND ', $where);

								try {
										$sql = "UPDATE `$table` SET `$destCol` = :dest WHERE $whereSql LIMIT 1";
										$stmt = $this->db->prepare($sql);
										$stmt->execute($params);

										needreload();
										echo json_encode(['status' => 'success']);
								} catch (Exception $e) {
										echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
								}
								exit;
						
						case 'create_destination':
								$input = json_decode(file_get_contents("php://input"), true);
//error_log(print_r($input,true));
								$module = '';
								$name   = '';

								if (isset($input['module'])) {
										$module = $input['module'];
								}
								if (isset($input['name'])) {
										$name = trim($input['name']);
								}
								if (isset($input['previous'])) {
										$destination = trim($input['previous']);
								}else{
									$destination='app-blackhole,zapateller,1';
								}
								
								if ($name === '') {
										echo json_encode(array('status' => 'error', 'message' => _('Name is required')));
										exit;
								}

								try {
									
										switch ($module) {
											
												case 'Announcements':
														// Check for duplicates
														if ($this->nameExists('announcement', 'description', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'Announcement Description Already Exist'
																]);
																exit;
														}
												
														$id = dpviz_announcement_add($name, $destination, !empty($input['recording_id']) ? $input['recording_id'] : 0);
														
														// Build FreePBX destination string
														$value = "app-announcement-" . $id . ",s,1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														));
														
														break;

												case 'Call Flow Control':
														$currentmode = isset($input['currentmode']) ? $input['currentmode'] : 'NIGHT';
														
														try {
																$db = \FreePBX::Database();
																$sql = "
																		SELECT CAST(ext AS UNSIGNED) AS id
																		FROM daynight
																		WHERE CAST(ext AS UNSIGNED) BETWEEN 1 AND 99
																		ORDER BY id ASC
																";
																$sth = $db->prepare($sql);
																$sth->execute();

																$ids = array();
																while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
																		if (isset($row['id'])) {
																				$ids[] = (int)$row['id'];
																		}
																}

																$id = 0;
																for ($i = 1; $i < 100; $i++) {
																		if (!in_array($i, $ids, true)) {
																				$id = $i;
																				break;
																		}
																}

																if ($id === 0) {
																		throw new Exception('No available Call Flow Control IDs below 100.');
																}

																$dn = \FreePBX::Daynight();
																
																$vals = array(
																		'action'              => 'add',
																		'goto0'               => 'truegoto',
																		'truegoto0'           => 'app-blackhole,zapateller,1',  // default DAY destination
																		'goto1'               => 'falsegoto',
																		'falsegoto1'          => 'app-blackhole,zapateller,1',  // default NIGHT destination
																		'fc_description'      => $name,
																		'day_recording_id'    => '',
																		'night_recording_id'  => '',
																		'password'            => '',
																		'state'               => $currentmode
																);

																$dn->edit($vals, $id);

																$value = "app-daynight," . $id . ",1";
																$label = htmlspecialchars($name, ENT_QUOTES);

																needreload();
																echo json_encode(array(
																		'status' => 'success',
																		'value'  => $value,
																		'label'  => $label
																));
														} catch (Exception $e) {
																echo json_encode(array(
																		'status'  => 'error',
																		'message' => 'Failed to create Call Flow Control: ' . $e->getMessage()
																));
														}
														break;
														
												case 'Call Recording':
														if ($this->nameExists('callrecording', 'description', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'Description name already exists'
																]);
																exit;
														}
														
														$id = dpviz_callrecording_add($name,$input['recordingmode'],$destination);
														
														// Build FreePBX destination string
														$value = "ext-callrecording," . $id . ",1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														));
														
														break;

												case 'Dynamic Routes':

														// Check for duplicates
														if ($this->nameExists('dynroute', 'name', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'Dynamic Route Description Already Exist'
																]);
																exit;
														}
														
														$freepbx = \FreePBX::create();
														$freepbx->Modules->loadFunctionsInc('dynroute');
												
														$id = dynroute_save_details([
																'id'										=> false,
																'name'                  => $name,
																'description'           => '',
																'sourcetype'            => 'none',
																'enable_substitutions'  => 'CHECKED',
																'mysql_host'            => '',
																'mysql_dbname'          => '',
																'mysql_query'           => '',
																'mysql_username'        => '',
																'mysql_password'        => '',
																'odbc_func'             => '',
																'odbc_query'            => '',
																'url_query'             => '',
																'agi_query'             => '',
																'agi_var_name_res'      => '',
																'astvar_query'          => '',
																'enable_dtmf_input'     => '',
																'max_digits'            => 0,
																'timeout'               => !empty($input['dyn_timeout']) ? $input['dyn_timeout'] : 5,
																'announcement_id'       => !empty($input['recording_id']) ? $input['recording_id'] : 0,
																'chan_var_name'         => '',
																'chan_var_name_res'     => '',
																'validation_regex'      => '',
																'max_retries'           => 0,
																'invalid_retry_rec_id'  => 0,
																'invalid_rec_id'        => 0,
																'invalid_dest'          => $destination,
																'default_dest'          => $destination
																
														]);
														
														if (is_array($input['dynEntries']) && !empty($input['dynEntries'])){
															$entries=array();
															foreach ($input['dynEntries'] as $d){
																$entry=array(
																	'dynroute_id' => $id,
																	'selection' => $d['digit'],
																	'dest' => $d['dest'],
																	'description' => ''
																);
																$entries[]=$entry;
															}
															
															$dynEntries = \FreePBX::Dynroute();
															$dynEntries->saveEntry($id,$entries);
														}
														
														// Build FreePBX destination string
														$value = "dynroute-" . $id . ",s,1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														));
														
														break;
														
												case 'Inbound Routes':
														if (isset($input['did']) && isset($input['cidnum'])) {
															$invalidDIDChars = array('<', '>');
															$did = trim(str_replace($invalidDIDChars, "", $input['did']));
															$cid = trim(str_replace($invalidDIDChars, "", $input['cidnum']));
														}
														
														$inboundRoute = \FreePBX::Core();

														$result = $inboundRoute->addDID(array(
															'description' => $name,
															'extension'   => $did,
															'cidnum'      => !empty($cid) ? $cid : '',
															'destination' => $destination,
															'mohclass'    => isset($input['music']) ? $input['music'] : '',
															'grppre'      => isset($input['grppre']) ? $input['grppre'] : '',
														));

														if ($result) {
															header('Content-Type: application/json');
															if (!empty($cid)){
																$value = 'from-trunk,' . $did . '&' . $cid . ',1';
															}else{
																$value = 'from-trunk,' . $did . ',1';
															}
															$label = htmlspecialchars($name, ENT_QUOTES);

															needreload();
															echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
															));
														} else {
															echo json_encode(array(
																'status'  => 'error',
																'message' => _('Inbound Route exists.')
															));
														}
														break;

												case 'IVR':
														if ($this->nameExists('ivr_details', 'name', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'IVRs name already exist'
																]);
																exit;
														}
														$id = dpviz_ivr_add($name, $input['timeout_time'], $input['ivrEntries'], !empty($input['recording_id']) ? $input['recording_id'] : 0);
														
														// Build FreePBX destination string
														$value = "ivr-" . $id . ",s,1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														));
												
														break;
														
												case 'Languages':
														if ($this->nameExists('languages', 'description', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => $name . ' already used, please use a different description.'
																]);
																exit;
														}
														
														$lang = \FreePBX::Languages();
														$id = $lang->addLanguage($name, $input['lang_code'], $destination);
														
														// Build FreePBX destination string
														$value = "app-languages," . $id . ",1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														));
												
														break;
														
												case 'Misc Destinations':
														if ($this->nameExists('miscdests', 'description', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'Misc Destinations name already exist'
																]);
																exit;
														}
														
														$md = \FreePBX::Miscdests();
														$id = $md->add($name,$input['destdial']);
														
														// Build FreePBX destination string
														$value = "ext-miscdests," . $id . ",1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode(array(
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														));
												
														break;
														
												case 'Queues':
														if ($this->nameExists('queues_config', 'descr', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'QUEUEs name already exist'
																]);
																exit;
														}
														
														$qnum= $input['extension'];
														if ($this->queueExists($qnum)) {
																header('Content-Type: application/json');
																echo json_encode([
																		'status'  => 'error',
																		'message' => "Queue {$qnum} already exists."
																]);
																exit;
														}
														
														
														//$freepbx = \FreePBX::create();
														//$freepbx->Modules->loadFunctionsInc('queues');
														global $amp_conf;
														$base=$_SERVER['SCRIPT_NAME'];
														$base = preg_replace('#/[^/]+$#', '', $base);
														require_once($amp_conf['AMPWEBROOT'] . $base . '/modules/queues/functions.inc/geters_seters.php');
														$strategy= $input['qstrategy'];
														$maxwait= $input['maxwait'];
														
														$items = array_map('trim', explode(',', $input['staticlist']));

														$filtered = array_filter($items, function($v) {
																// digits only
																return preg_match('/^[0-9]+$/', $v);
														});

														// remove duplicates
														$unique = array_unique($filtered);

														$staticAgents = array_map(function($ext) {
																return "Local/{$ext}@from-queue/n,0";
														}, $unique);

														
														$dynamicAgentsRaw = array_filter(array_map('trim', explode(',', $input['dynlist'])));

														$dynamicAgents = array_map(function($ext) {
																return "{$ext},0";
														}, $dynamicAgentsRaw);
														
														
														$_REQUEST = array_merge($_REQUEST, [
															'strategy'           => $strategy,
															'music'              => 'inherit',
															'timeout'            => '15',
															'retry'              => '5',
															'joinempty'          => 'yes',
															'leavewhenempty'     => 'no',
															'announceposition'   => 'no',
															'announceholdtime'   => 'no',
															'recording'          => 'dontcare',
															'answered_elsewhere' => '0',
															'maxlen'             => '0',
															'wrapuptime'         => '0',
															'announcefreq'       => '0',
															'min-announce'       => '15',
															'pannouncefreq'      => '0'
															
														]);

														try {
															$result=queues_add(
																	$qnum, //account
																	$name, //name
																	'', //password
																	'', //prefix
																	$destination,  //goto
																	'', //agentannounce_id
																	$staticAgents, //members
																	'', //joinannounce_id
																	$maxwait, //maxwait
																	'', //alertinfo
																	'0', //cwignore
																	'', //qregex
																	'0', //queuewait
																	'0', //use_queue_context
																	$dynamicAgents, //dynmembers
																	'no', //dynmemberonly
																	'', //togglehint
																	'0', //qnoanswer
																	'', //callconfirm
																	'', //callconfirm_id
																	'', //monitor_type
																	'0', //monitor_heard
																	'0', //monitor_spoken
																	'0', //answered_elsewhere
																	'', //recording
																	'', //rvolume
																	''  //rvol_mode
															);
															
															if ($result) {
																$value = "ext-queues," . $qnum . ",1";
																$label = htmlspecialchars($name, ENT_QUOTES);

																needreload();
																echo json_encode(array(
																		'status' => 'success',
																		'value'  => $value,
																		'label'  => $label
																));
															}else{
																throw new Exception('queues_add() returned false');
															}
														} catch (Exception $e) {
																echo json_encode([
																		'status'  => 'error',
																		'message' => 'Failed to create Queue: ' . $e->getMessage()
																]);
																exit;
														}
														break;
												case 'Ring Groups':
														$grpnum = $input['grpnum'];
														if ($this->ringgroupExists($grpnum)) {
																header('Content-Type: application/json');
																echo json_encode([
																		'status'  => 'error',
																		'message' => "Ring Group {$grpnum} already exists."
																]);
																exit;
														}
														
														$strategy = $input['rgstrategy'];
														$grptime= $input['grptime'];
														$items = array_map('trim', explode(',', $input['grplist']));

														$filtered = array_filter($items, function($v) {
																// allowed: digits or digits followed by #
																return preg_match('/^[0-9]+#?$/', $v);
														});

														$grplist = implode('-', array_unique($filtered));

														
														$rg = \FreePBX::Ringgroups();
																
														$result = $rg->add(
																$grpnum,
																$strategy,
																$grptime,
																$grplist, 
																$destination, //postdest
																$name, //desc
																'',  //grppre
																'0', //annmsg_id
																'',  //alertinfo
																'',  //needsconf
																'',  //remotealert_id
																'',  //toolate_id
																'',  //ringing
																'',  //cwignore
																'',  //cfignore
																'default',  //changecid
																'',  //fixedcid
																'',  //cpickup
																'dontcare',  //recording
																'yes', //progress
																'no',  //elsewhere
																''  //rvolume
														);
														
														if ($result) {
															$value = "ext-group," . $grpnum . ",1";
															$label = htmlspecialchars($name, ENT_QUOTES);

															needreload();
															echo json_encode(array(
																	'status' => 'success',
																	'value'  => $value,
																	'label'  => $label
															));
														}else{
															echo json_encode([
																	'status'  => 'error',
																	'message' => 'Failed to create Ring Group: ' . $e->getMessage()
															]);
															exit;
														}
														break;
												
												case 'Set CallerID':
														if ($this->nameExists('setcid', 'description', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => $name . ' already used, please use a different Description.'
																]);
																exit;
														}
														$setcid = \FreePBX::Setcid();

														$result = $setcid->update(
																null,
																$name,
																$input['calleridName'],
																$input['calleridNumber'],
																$destination
														);

														if ($result) {
																global $db;

																// safer way to get new row ID
																$id = $db->getOne("
																		SELECT cid_id 
																		FROM setcid 
																		WHERE description = ? 
																		ORDER BY cid_id DESC 
																		LIMIT 1
																", [$name]);

																if (!$id) {
																		throw new Exception("Unable to determine new SetCID ID");
																}

																$value = "app-setcid," . intval($id) . ",1";
																$label = htmlspecialchars($name, ENT_QUOTES);

																needreload();

																echo json_encode([
																		'status' => 'success',
																		'value'  => $value,
																		'label'  => $label
																]);
														} else {
																echo json_encode([
																		'status'  => 'error',
																		'message' => 'Failed to create Set Caller ID'
																]);
														}

														break;
												case 'Time Conditions':
														if ($this->nameExists('timeconditions', 'displayname', $name)) {
																echo json_encode([
																		'status' => 'error',
																		'message' => 'Please enter a valid Time Conditions Name'
																]);
																exit;
														}
														// Validation — require at least one of the three IDs
														if (
																empty($input['timegroup_id']) &&
																empty($input['calendar_id']) &&
																empty($input['calendar_group_id'])
														) {
																echo json_encode([
																		'status'  => 'error',
																		'message' => _('Either Time Group, Calendar, or Calendar Group must be set.')
																]);
																exit;
														}

														// Normalize variables
														$tgid  = !empty($input['timegroup_id'])       ? $input['timegroup_id']       : '';
														$calid = !empty($input['calendar_id'])        ? $input['calendar_id']        : '';
														$cgid  = !empty($input['calendar_group_id'])  ? $input['calendar_group_id']  : '';

														// Validate: must have exactly one logical side set
														if (
																($tgid === '' && $calid === '' && $cgid === '') ||        // none
																($tgid !== '' && ($calid !== '' || $cgid !== '')) ||      // mixed
																($calid !== '' && $cgid !== '')                          // both calendar variants
														) {
																echo json_encode([
																		'status'  => 'error',
																		'message' => _('Select either a Time Group or a single Calendar / Calendar Group (not both).')
																]);
																exit;
														}

														// Determine mode
														$tcMode = ($tgid !== '') ? 'time-group' : 'calendar-group';

														// Create the Time Condition entry
														$tc = \FreePBX::Timeconditions();
														$id = $tc->addTimeCondition([
																'displayname'      => $name,
																'time'             => $tgid,
																'timezone'         => 'default',
																'goto0'            => 'truegoto',
																'truegoto0'        => 'app-blackhole,zapateller,1',
																'goto1'            => 'falsegoto',
																'falsegoto1'       => 'app-blackhole,zapateller,1',
																'generate_hint'    => '1',
																'invert_hint'      => '0',
																'fcc_password'     => '',
																'deptname'         => null,
																'mode'             => $tcMode,
																'calendar-id'      => $calid,
																'calendar-group'   => $cgid
														]);

														// Build return payload
														$value = "timeconditions,{$id},1";
														$label = htmlspecialchars($name, ENT_QUOTES);

														needreload();
														echo json_encode([
																'status' => 'success',
																'value'  => $value,
																'label'  => $label
														]);
														break;
												

												// case 'ivr':
												// case 'ringgroups':
												// Add more modules here...

												default:
														echo json_encode(array('status' => 'error', 'message' => "Module '" . $module . "' not supported"));
										}
										
										
								} catch (Exception $e) {
										echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
								}
								exit;
								
						case 'add_ivr_entry':
						
								 header('Content-Type: application/json');
								$input = json_decode(file_get_contents("php://input"), true);

								$titleText   = isset($input['titleText']) ? trim($input['titleText']) : '';
								$destination = isset($input['destination']) ? trim($input['destination']) : '';
								$digit       = isset($input['digit']) ? trim($input['digit']) : '';

								// Basic validation
								if ($titleText === '' || $destination === '' || $digit === '') {
										echo json_encode([
												'status' => 'error',
												'message' => 'Missing titleText, destination, or digit'
										]);
										exit;
								}

								// Extract IVR ID
								if (!preg_match('/ivr-(\d+),/', $titleText, $m)) {
										echo json_encode([
												'status' => 'error',
												'message' => 'Could not extract IVR ID'
										]);
										exit;
								}

								$ivrId = (int)$m[1];

								// Check for duplicates
								if ($this->ivrEntryExists($ivrId, $digit)) {
										echo json_encode([
												'status' => 'error',
												'message' => 'Digit already exists for this IVR.'
										]);
										exit;
								}
								
								$sql = "INSERT INTO ivr_entries (ivr_id, selection, dest)
												VALUES (?, ?, ?)";

								$stmt = $this->db->prepare($sql);

								if ($stmt->execute([$ivrId, $digit, $destination])) {
										needreload();
										echo json_encode(['status' => 'success']);
								} else {
										echo json_encode(['status' => 'error', 'message' => 'Insert failed.']);
								}

								exit;
						case 'add_dyn_entry':
						    header('Content-Type: application/json');
								$input = json_decode(file_get_contents("php://input"), true);

								$titleText   = isset($input['titleText']) ? trim($input['titleText']) : '';
								$destination = isset($input['destination']) ? trim($input['destination']) : '';
								$digit       = isset($input['digit']) ? trim($input['digit']) : '';

								// Basic validation
								if ($titleText === '' || $destination === '' || $digit === '') {
										echo json_encode([
												'status' => 'error',
												'message' => 'Missing titleText, destination, or digit'
										]);
										exit;
								}

								// Extract Dynamic Route ID
								if (!preg_match('/dynroute-(\d+),/', $titleText, $m)) {
										echo json_encode([
												'status' => 'error',
												'message' => 'Could not extract Dynamic Route ID'
										]);
										exit;
								}

								$dynId = (int)$m[1];

								// Check for duplicates
								if ($this->dynEntryExists($dynId, $digit)) {
										echo json_encode([
												'status' => 'error',
												'message' => 'Digit already exists for this Dynamic Route.'
										]);
										exit;
								}

								$sql = "INSERT INTO dynroute_dests (dynroute_id, selection, dest)
												VALUES (?, ?, ?)";

								$stmt = $this->db->prepare($sql);

								if ($stmt->execute([$dynId, $digit, $destination])) {
										needreload();
										echo json_encode(['status' => 'success']);
								} else {
										echo json_encode(['status' => 'error', 'message' => 'Insert failed.']);
								}

								exit;

						case 'list_timegroups':
								header('Content-Type: application/json; charset=utf-8');
								try {
										// BMO object
										$tc = \FreePBX::Timeconditions();
										// Returns array like [id => description]
										$groupsRaw = $tc->listTimegroups();

										if (is_array($groupsRaw)) {
												$groups = array();
												foreach ($groupsRaw as $row) {
														// id comes from [0] or [value]
														$id   = isset($row['value']) ? $row['value'] : $row[0];
														// name/description comes from [1]
														$desc = isset($row[1]) ? $row[1] : $id;

														$groups[] = array(
																'id'          => (string)$id,
																'description' => (string)$desc
														);
												}
										}

										echo json_encode([
												'status' => 'success',
												'groups' => $groups
										]);
								} catch (\Throwable $e) {
										echo json_encode([
												'status'  => 'error',
												'message' => $e->getMessage()
										]);
								}
								exit;
						
						case 'list_calendars':
								header('Content-Type: application/json; charset=utf-8');
								try {
										$calsRaw = \FreePBX::Calendar()->listCalendars();
										$groups = array();

										if (is_array($calsRaw)) {
												foreach ($calsRaw as $id => $row) {
														$name = isset($row['name']) ? $row['name'] : $id;

														$groups[] = array(
																'id'   => (string)$id,
																'name' => (string)$name
														);
												}
										}

										echo json_encode([
												'status' => 'success',
												'groups' => $groups
										]);
								} catch (\Throwable $e) {
										echo json_encode([
												'status'  => 'error',
												'message' => $e->getMessage()
										]);
								}
								exit;
								
						case 'list_calendargroups':
								header('Content-Type: application/json; charset=utf-8');
								try {
										$calsRaw = \FreePBX::Calendar()->listGroups();
										$groups = array();

										if (is_array($calsRaw)) {
												foreach ($calsRaw as $id => $row) {
														$name = isset($row['name']) ? $row['name'] : $id;

														$groups[] = array(
																'id'   => (string)$id,
																'name' => (string)$name
														);
												}
										}

										echo json_encode([
												'status' => 'success',
												'groups' => $groups
										]);
								} catch (\Throwable $e) {
										echo json_encode([
												'status'  => 'error',
												'message' => $e->getMessage()
										]);
								}
								exit;
								
						case 'list_languages':
								header('Content-Type: application/json; charset=utf-8');
								try {
										// BMO object
										$lang = \FreePBX::Soundlang();
										// Returns array like [id => description]
										$groupsRaw = $lang->getLanguages();

										if (is_array($groupsRaw)) {
												$groups = array();
												foreach ($groupsRaw as $row) {
														// id comes from [0] or [value]
														$id   = isset($row['lang_code']) ? $row['lang_code'] : $row[0];
														// name/description comes from [1]
														$desc = isset($row['description']) ? $row['description'] : $id;
														$groups[] = array(
																'lang_code'   => (string)$id,
																'description' => (string)$desc
														);
												}
										}

										echo json_encode([
												'status' => 'success',
												'groups' => $groupsRaw
										]);
								} catch (\Throwable $e) {
										echo json_encode([
												'status'  => 'error',
												'message' => $e->getMessage()
										]);
								}
								exit;
								
						case 'list_music':
								header('Content-Type: application/json; charset=utf-8');
								try {
										// BMO object
										$music = \FreePBX::Music();
										// Returns array like [id => description]
										$groupsRaw = $music->getCategories();

										if (is_array($groupsRaw)) {
												$groups = array();
												foreach ($groupsRaw as $row) {
														// id comes from [0] or [value]
														$id   = isset($row['id']) ? $row['id'] : $row[0];
														// name/description comes from [1]
														$desc = isset($row['category']) ? $row['category'] : $id;
														$groups[] = array(
																'id'          => (string)$id,
																'category' => (string)$desc
														);
												}
										}

										echo json_encode([
												'status' => 'success',
												'groups' => $groups
										]);
								} catch (\Throwable $e) {
										echo json_encode([
												'status'  => 'error',
												'message' => $e->getMessage()
										]);
								}
								exit;
								
						case 'list_recordings':
								header('Content-Type: application/json; charset=utf-8');

								try {
										$recordings = \FreePBX::Recordings();
										$groupsRaw  = array();

										// Prefer FreePBX 17+ method if available and non-empty
										if (method_exists($recordings, 'getAllRecordingsList')) {
												$groupsRaw = $recordings->getAllRecordingsList();

												// In some cases (older 17 builds), method exists but returns empty
												if ((empty($groupsRaw) || !is_array($groupsRaw)) && method_exists($recordings, 'getAll')) {
														$groupsRaw = $recordings->getAll();
												}
										}
										// Legacy fallback (FreePBX ≤16)
										elseif (method_exists($recordings, 'getAll')) {
												$groupsRaw = $recordings->getAll();
										}

										if (!is_array($groupsRaw)) {
												throw new Exception('Recordings list could not be loaded.');
										}

										$groups = array();
										foreach ($groupsRaw as $row) {
												// id and display name handling (support numeric or associative arrays)
												if (isset($row['id'])) {
														$id = $row['id'];
												} elseif (isset($row[0])) {
														$id = $row[0];
												} else {
														$id = '';
												}

												if (isset($row['displayname'])) {
														$desc = $row['displayname'];
												} elseif (isset($row[1])) {
														$desc = $row[1];
												} else {
														$desc = $id;
												}

												if ($id !== '') {
														$groups[] = array(
																'id'          => (string)$id,
																'displayname' => (string)$desc
														);
												}
										}

										echo json_encode(array(
												'status' => 'success',
												'groups' => $groups
										));
								} catch (Exception $e) {
										error_log('list_recordings error: ' . $e->getMessage());
										echo json_encode(array(
												'status'  => 'error',
												'message' => $e->getMessage()
										));
								}
								exit;

						case 'set_simtime':
								header('Content-Type: application/json');

								$dt = '';
								if (isset($_POST['customDateTime'])) {
										$dt = $_POST['customDateTime'];
								}

								if ($dt === '') {
										// Clear override
										sql("UPDATE dpviz SET custom_datetime = NULL WHERE id = 1");
										$stored = null;
								} else {
										// Save override
										sql("UPDATE dpviz SET custom_datetime = " . q($dt) . " WHERE id = 1");
										$stored = $dt;
								}

								echo json_encode(array(
										'status' => 'success',
										'stored' => $stored
								));
								exit;


						case 'need_reload_status':
								$needs_reload=check_reload_needed();

								echo json_encode([
										'status' => 'success',
										'need_reload' => (bool)$needs_reload
								]);
								exit;

						case 'get_sections':
								$freepbx = \FreePBX::create();
								$freepbx->Modules->loadFunctionsInc('core');

								$ampUser = core_getAmpUser($_SESSION['AMP_user']->username);

								$sections = [];
								if (!empty($ampUser['sections']) && is_array($ampUser['sections'])) {
										$sections = $ampUser['sections'];
								}

								echo json_encode([
										'status'   => 'success',
										'sections' => $sections
								]);
								exit;


						
						//default------------
            default:
                echo json_encode(array('status' => 'error', 'message' => 'Unknown command'));
                exit;
        }
    }
		
		public function checkForGitHubUpdate() {
        $modinfo = \FreePBX::Modules()->getInfo('dpviz');
        $ver = isset($modinfo['dpviz']['version']) ? $modinfo['dpviz']['version'] : '0.0.0';

        $url = "https://modules.volchko.xyz/dpviz/module.json";

        $opts = array(
            "http" => array(
                "method" => "GET",
                "header" => "User-Agent: ".\FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND").' '.get_framework_version()."\r\n"
            )
        );
        $context = stream_context_create($opts);
        $json = @file_get_contents($url, false, $context);

        if ($json === false) {
            return array('error' => 'Failed to fetch release info.');
        }

        $data = json_decode($json, true);
        if (!isset($data['version'])) {
            return array('error' => 'Invalid response from server.');
        }

        $latestVersion = ltrim($data['version'], 'v');
        $upToDate = version_compare($ver, $latestVersion, '>=');

        return array(
            'current' => $ver,
            'latest' => $latestVersion,
            'up_to_date' => $upToDate
        );
    }
		
		function sendCurlPost($url, array $postFields = array(), $decodeJson = true) {
				$url = 'https://modules.volchko.xyz/dpviz/' . $url;
				$modinfo = \FreePBX::Modules()->getInfo('dpviz');
				$dpvizVersion = '0.0.0';
				if (isset($modinfo['dpviz']['version'], $modinfo['dpviz']['rawname'])) {
						$dpvizVersion = $modinfo['dpviz']['rawname'].' '.$modinfo['dpviz']['version'];
				}
				
				$postFields['dpversion'] = $dpvizVersion;
				$postFields['fpbxversion']= \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND") . ' ' . get_framework_version();
				
				$ch = curl_init($url);

				curl_setopt_array($ch, [
						CURLOPT_POST            => true,
						CURLOPT_POSTFIELDS      => http_build_query($postFields),
						CURLOPT_RETURNTRANSFER  => true,
						CURLOPT_TIMEOUT         => 15,
						CURLOPT_CONNECTTIMEOUT  => 10,
						CURLOPT_FOLLOWLOCATION  => true,
						CURLOPT_SSL_VERIFYPEER  => true,
				]);

				$response  = curl_exec($ch);
				$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$curlError = curl_error($ch);
				curl_close($ch);

				if ($response === false) {
						return [
								'status'  => 'error',
								'message' => 'cURL error: ' . $curlError
						];
				}

				if ($httpCode !== 200) {
						return [
								'status'  => 'error',
								'message' => 'HTTP error: ' . $httpCode
						];
				}

				return $decodeJson ? json_decode($response, true) : $response;
		}
		
		public function queueExists($qnum) {
				$sql = "SELECT COUNT(*) FROM `queues_config` WHERE extension = ?";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([$qnum]);
				return $stmt->fetchColumn() > 0;
		}
		
		public function ringgroupExists($grpnum) {
				$sql = "SELECT COUNT(*) FROM `ringgroups` WHERE grpnum = ?";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([$grpnum]);
				return $stmt->fetchColumn() > 0;
		}
		
		public function ivrEntryExists($ivr,$selection) {
				$sql = "SELECT COUNT(*) FROM `ivr_entries` WHERE ivr_id = ? AND selection = ?";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([$ivr,$selection]);
				return $stmt->fetchColumn() > 0;
		}
		
		public function dynEntryExists($dyn,$selection) {
				$sql = "SELECT COUNT(*) FROM `dynroute_dests` WHERE dynroute_id = ? AND selection = ?";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([$dyn,$selection]);
				return $stmt->fetchColumn() > 0;
		}
		public function nameExists($table, $col, $name) {

				// Whitelist allowed tables + columns to avoid SQL injection
				$allowedTables = ['announcement', 'callrecording', 'dynroute', 'ivr_details', 
													'languages', 'miscdests','queues_config', 'ringgroups',
													'queues','setcid','timeconditions']; 
				$allowedCols   = ['name', 'description', 'descr', 'displayname'];

				if (!in_array($table, $allowedTables)) {
						throw new Exception("Invalid table");
				}
				if (!in_array($col, $allowedCols)) {
						throw new Exception("Invalid column");
				}

				// Build SQL safely using validated identifiers
				$sql = "SELECT COUNT(*) FROM `$table` WHERE `$col` = ? LIMIT 1";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([$name]);

				return $stmt->fetchColumn() > 0;
		}

}


/**
 * Draw a destination select box, but allow an "Unassigned" option
 *
 * @param string $goto        The current destination string (e.g. "ext-local,2000,1")
 * @param string $name        The HTML name/id for the select box
 * @param array  $restrict    Restrict to certain modules (optional)
 * @param string $class       Extra CSS classes (optional)
 * @return string             HTML for the <select>
 */
 
function drawselects_unassigned($goto = '', $name = 'goto', $restrict = [], $class = '') {
    $destinations = \FreePBX::Modules()->getDestinations($restrict);

    // Start select
    $html  = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '"';
    if ($class) {
        $html .= ' class="' . htmlspecialchars($class) . '"';
    }
    $html .= '>';

    // Add our fake "Unassigned" option mapped to zapateller
    $unassigned = 'app-blackhole,zapateller,1';
    $selected   = ($goto === $unassigned || empty($goto)) ? ' selected' : '';
    $html .= '<option value="' . $unassigned . '"' . $selected . '>'
           . _('-- Unassigned --')
           . '</option>';

    // Loop through all modules/destinations
    foreach ($destinations as $mod => $dests) {
    if (empty($dests)) continue;
				$html .= '<optgroup label="' . htmlspecialchars($mod) . '">';
				foreach ($dests as $d) {
						if (!is_array($d) || !isset($d['destination'])) continue;
						$sel = ($goto === $d['destination']) ? ' selected' : '';
						$html .= '<option value="' . htmlspecialchars($d['destination']) . '"' . $sel . '>'
									 . htmlspecialchars($d['description'])
									 . '</option>';
				}
				$html .= '</optgroup>';
		}

    $html .= '</select>';

    return $html;
}



function dpviz_announcement_add($description, $destination, $recording_id = 0) {
    global $db, $amp_conf;

    $sql = "INSERT INTO announcement 
        (description, recording_id, allow_skip, post_dest, return_ivr, noanswer, repeat_msg)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

    $sth = $db->prepare($sql);
    $res = $db->execute($sth, [
        $description,
        (int)$recording_id,
        0,
        $destination,
        0,
        0,
        ''
    ]);

    if (\DB::isError($res)) {
        die_freepbx($res->getMessage() . $sql);
    }

    if (method_exists($db, 'insert_id')) {
        return $db->insert_id();
    } elseif ($amp_conf['AMPDBENGINE'] == 'sqlite3') {
        return sqlite_last_insert_rowid($db->connection);
    } else {
        return mysql_insert_id($db->connection);
    }
}

function dpviz_callrecording_add($description, $recordingmode, $destination) {
    global $db, $amp_conf;

    $sql = "INSERT INTO callrecording 
        (callrecording_mode, description, dest)
        VALUES (?, ?, ?)";

    $sth = $db->prepare($sql);
    $res = $db->execute($sth, [
        $recordingmode,
        $description,
        $destination
    ]);

    if (\DB::isError($res)) {
        die_freepbx($res->getMessage() . $sql);
    }

    if (method_exists($db, 'insert_id')) {
        return $db->insert_id();
    } elseif ($amp_conf['AMPDBENGINE'] == 'sqlite3') {
        return sqlite_last_insert_rowid($db->connection);
    } else {
        return mysql_insert_id($db->connection);
    }
}

function dpviz_ivr_add($name, $timeout, $entries=array(), $recording_id = 0) {
    global $db, $amp_conf;

    $sql = "INSERT INTO ivr_details (
        name, description, announcement, directdial, invalid_loops, invalid_retry_recording, invalid_destination, timeout_enabled, invalid_recording,
        retvm, timeout_time, timeout_recording, timeout_retry_recording, timeout_destination, timeout_loops, timeout_append_announce,
        invalid_append_announce, timeout_ivr_ret, invalid_ivr_ret, alertinfo, rvolume, strict_dial_timeout
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?
    )";

    $sth = $db->prepare($sql);
    $res = $db->execute($sth, array(
        // name
        $name,
        // description
        '',
        // announcement
        (int)$recording_id,
        // directdial
        'Disabled',
        // invalid_loops
        3,
        // invalid_retry_recording
        'default',
        // invalid_destination
        'app-blackhole,zapateller,1',
        // timeout_enabled
        NULL,
        // invalid_recording
        'default',
        // retvm
        '',
        // timeout_time
        $timeout,
        // timeout_recording
        'default',
        // timeout_retry_recording
        'default',
        // timeout_destination
        'app-blackhole,zapateller,1',
        // timeout_loops
        3,
        // timeout_append_announce
        0,
        // invalid_append_announce
        0,
        // timeout_ivr_ret
        0,
        // invalid_ivr_ret
        0,
        // alertinfo
        '',
        // rvolume
        0,
        // strict_dial_timeout
        2
    ));

    if (\DB::isError($res)) {
        die_freepbx($res->getMessage() . $sql);
    }

    // PEAR DB insert_id handling
    if (method_exists($db, 'insert_id')) {
        $id = $db->insert_id();
    } else {
        if ($amp_conf['AMPDBENGINE'] == 'sqlite3') {
            $id = sqlite_last_insert_rowid($db->connection);
        } else {
            $id = mysql_insert_id($db->connection);
        }
    }
		
		if (!empty($entries)){
			foreach ($entries as $e){
				$sql = "INSERT INTO ivr_entries (
						ivr_id, selection, dest, ivr_ret
				) VALUES (
						?, ?, ?, ?
				)";

				$sth = $db->prepare($sql);
				$res = $db->execute($sth, array(
						// ivr_id
						$id,
						// selection
						$e['digit'],
						// dest
						$e['dest'],
						// ivr_ret
						0
				));
			}
		}

    return $id;
}


