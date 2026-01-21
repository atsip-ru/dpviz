<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$options=\FreePBX::Dpviz()->getOptions();


if ($options['displaydestinations']){
	$destinations=\FreePBX::Modules()->getDestinations();
	$displayDestinationOpt = ' [ '._('destination').' ]';
}else{
	$displayDestinationOpt = '';
}

try{
	$soundlang = FreePBX::create()->Soundlang;
	$options['lang'] = $soundlang->getLanguage();
}catch(\Exception $e){
	freepbx_log(FPBX_LOG_ERROR,"Soundlang is missing, please install it."); 
	$options['lang'] = "en";
}

$options['sections'] = [];

$jsSections = [];

if (isset($_SESSION['AMP_user']) && is_object($_SESSION['AMP_user'])
    && method_exists($_SESSION['AMP_user'], 'getSections')) {

    $sections = $_SESSION['AMP_user']->getSections();

    if (is_array($sections)) {
        $jsSections = $sections;
				$options['sections'] = $sections;
    }
}

?>
<script>
    window.dpvizConfig = window.dpvizConfig || {};
    dpvizConfig.sections = <?php echo json_encode($jsSections); ?>;
</script>
<?php

function dpp_load_incoming_routes() {
  global $db;
	global $options;
	global $destinations;
	
  $sql = "select * from incoming order by extension";
  $results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
  if (DB::IsError($results)) {
    die_freepbx($results->getMessage()."<br><br>Error selecting from incoming");       
  }
	
	$routes = array();
  // Store the routes in a hash indexed by the inbound number
  if (is_array($results)) {
    foreach ($results as $route) {
      $num = $route['extension'];
      $cid = $route['cidnum'];
			if (empty($num) && empty($cid)){$exten='ANY';}else{$exten=$num.$cid;}
      $routes[$exten] = $route;
			
			if ($options['displaydestinations']){
					$destDescription ='';
					$routeDest = isset($destinations[$route['destination']])
						? $destinations[$route['destination']]
						: array('name'=>'','description'=>'');

					$name = !empty($routeDest['category'])
						? $routeDest['category']
						: $routeDest['name'];
						
					if (isset($routeDest['destination'])){
						$destDescription = (strpos($routeDest['destination'], 'zapateller') !== false)
						? _('Undefined Destination')
						: $routeDest['description'];
					}

					$routes[$exten]['goDestination'] = $name . ': ' . $destDescription;
			}
    }
  }
	return $routes;
}

$inroutes= dpp_load_incoming_routes();

$existingModules = array('incoming');

function dpp_load_tables() {
	global $db, $existingModules;
	$dproute=array();

	$tables = array('announcement','callrecording','daynight','dynroute','languages','ivr_details',
	  'miscapps','miscdests','queues_config','ringgroups','setcid','timeconditions','virtual_queue_config',
		'dpviz_views'
	);
	
	$freepbx = \FreePBX::create();
	foreach ($tables as $table) {
    // Check if the table exists
    $tableExists = $db->getOne("SHOW TABLES LIKE '$table'");
    
    if (!$tableExists) {
        continue;
    }
		
		if ($table==='ivr_details'){
			$checkMod='ivr';
		}elseif ($table==='queues_config'){
			$checkMod='queues';
		}else{
			$checkMod=$table;
		}
		
		if ($table!='dpviz_views'){
			if ($freepbx->Modules->checkStatus($checkMod)) {
				$existingModules[] = $checkMod; //js array for new destinations
			}else{
				//module is disabled
				continue;
			}
		}
		
		$order = ($table === 'dpviz_views') ? 'ORDER BY description' : '';
		$query = "SELECT * FROM `$table` $order";
    $results = $db->getAll($query, DB_FETCHMODE_ASSOC);
    
    if (DB::IsError($results)) {
				continue;  // Skip to the next table
    }

 		if ($table == 'announcement') {
				foreach($results as $an) {
					$id = $an['announcement_id'];
					$dproute['announcements'][$id] = $an;
				}
		}elseif ($table == 'daynight') {
				foreach($results as $daynight) {
					$id = $daynight['ext'];
					if (!isset($dproute['daynight'][$id])) {
							$dproute['daynight'][$id] = array();
					}
					$dproute['daynight'][$id][] = $daynight;
				}
		}elseif ($table == 'dynroute') {
        foreach ($results as $dynroute) {
            $id = $dynroute['id'];
            $dproute['dynroute'][$id] = $dynroute;
        }
    }elseif ($table == 'languages') {
        foreach($results as $languages) {
					$id=$languages['language_id'];
					$dproute['languages'][$id] = $languages;
				}
    }elseif ($table == 'ivr_details') {
        foreach($results as $ivr) {
					$id = $ivr['id'];
					$dproute['ivrs'][$id] = $ivr;
				}
    }elseif ($table == 'miscapps') {
        foreach($results as $miscapps) {
					$id = $miscapps['miscapps_id'];
					$dproute['miscapps'][$id] = $miscapps;
				}
		}elseif ($table == 'queues_config') {
        foreach($results as $q) {
					$id = $q['extension'];
					$dproute['queues'][$id] = $q;
				}
		}elseif ($table == 'ringgroups') {
        foreach($results as $rg) {
					$id = $rg['grpnum'];
					$dproute['ringgroups'][$id] = $rg;
				}
    }elseif ($table == 'timeconditions') {
        foreach($results as $tc) {
					$id = $tc['timeconditions_id'];
					$dproute['timeconditions'][$id] = $tc;
				}
		}elseif ($table == 'virtual_queue_config') {
        foreach($results as $vqueues) {
					$id = $vqueues['id'];
					$dproute['vqueues'][$id] = $vqueues;
				}
		}elseif ($table == 'dpviz_views') {
        foreach($results as $dpvizViews) {
					$id = $dpvizViews['id'];
					$dproute['dpvizViews'][$id] = $dpvizViews;
				}
		}
		
	}
	return $dproute;
}

$otherroutes= dpp_load_tables();

//build dropdowns
$dropOptions="";

//build js array
echo "<script>window.existingModules = " . json_encode($existingModules) . ";</script>";


# Users
$users=\FreePBX::Core()->getAllUsersByDeviceType();

foreach($users as $user) {
	$id = $user['extension'];
	$otherroutes['extensions'][$id]= $user;
}

//Saved Views
if (isset($otherroutes['dpvizViews']) && count($otherroutes['dpvizViews']) > 0){
	$dropOptions.='<optgroup label="' . _('Saved Views') . '">';
	foreach ($otherroutes['dpvizViews'] as $i=>$ii){
		$skipArray = explode(';', $ii['skip']);
		$skipJson = htmlspecialchars(json_encode($skipArray), ENT_QUOTES, 'UTF-8');
		$description = htmlspecialchars($ii['description'], ENT_QUOTES, 'UTF-8');

		$dropOptions .= '<option 
			value="' . $ii['ext'] . '|' . $ii['jump'] . '" 
			data-id="' . $ii['id'] .'" data-skips="' . $skipJson . '">' . $description . '</option>';
		}
		$dropOptions.='</optgroup>';
}

//Inbound Routes 
if (isset($inroutes) && count($inroutes) > 0){
	$dropOptions .= '<optgroup label="' . _('Inbound Routes') . $displayDestinationOpt.'">';
	
	foreach ($inroutes as $in=>$extt){
		$e=$extt['extension'];
		if (empty($e)){$e='ANY';}
		if (!empty($extt['cidnum'])){$c='&'.$extt['cidnum'];$cName=' / '.$extt['cidnum'];}else{$c=$cName='';}
		
		if ($options['displaydestinations'] && isset($extt['goDestination'])){
			$displayDestination = '[ ' . $extt['goDestination'] . ' ]';
		}else{
			$displayDestination='';
		}
		
		$dropOptions.='<option value="from-trunk,'.$e.$c.',1,'.$options['lang'].'">'.$e.$cName.' : '.$extt['description'].' '.$displayDestination.'</option>';
	}
	$dropOptions.='</optgroup>';
}

//Time Conditions
if (isset($otherroutes['timeconditions']) && count($otherroutes['timeconditions']) > 0){
	$dropOptions.='<optgroup label="' . _('Time Conditions') . '">';
	foreach ($otherroutes['timeconditions'] as $i=>$ii){
		$dropOptions.='<option value="timeconditions,'.$ii['timeconditions_id'].',1,'.$options['lang'].'">'.$ii['displayname'].'</option>';
	}
	$dropOptions.='</optgroup>';
	
}

//Call Flow Control
if (isset($otherroutes['daynight']) && count($otherroutes['daynight']) > 0){
	$dropOptions.='<optgroup label="' . _('Call Flow Control') . '">';
	foreach ($otherroutes['daynight'] as $i=>$ii){
		foreach ($ii as $iii){
			if ($iii['dmode']=='fc_description'){
				$ext=$iii['ext'];
				$name='('.$ext.') '.$iii['dest'];
			}
		}
		$dropOptions.='<option value="app-daynight,'.$ext.',1,'.$options['lang'].'">'.$name.'</option>';
	}
	$dropOptions.='</optgroup>';
	
}

//Dynamic Routes
if (isset($otherroutes['dynroute']) && count($otherroutes['dynroute']) > 0){
	$dropOptions.='<optgroup label="' . _('Dynamic Routes') . '">';
	foreach ($otherroutes['dynroute'] as $i=>$ii){
		$dropOptions.='<option value="dynroute-'.$ii['id'].',s,1,'.$options['lang'].'">'.$ii['name'].'</option>';
	}
	$dropOptions.='</optgroup>';
}
//IVRs
if (isset($otherroutes['ivrs']) && count($otherroutes['ivrs']) > 0){
	$dropOptions.='<optgroup label="IVRs">';
	foreach ($otherroutes['ivrs'] as $i=>$ii){
		$dropOptions.='<option value="ivr-'.$ii['id'].',s,1,'.$options['lang'].'">'.$ii['name'].'</option>';
	}
	$dropOptions.='</optgroup>';
}

//Virtual Queues
if (isset($otherroutes['vqueues']) && count($otherroutes['vqueues']) > 0){
	$dropOptions.='<optgroup label="' . _('Virtual Queues') . '">';
	foreach ($otherroutes['vqueues'] as $i=>$ii){
		$dropOptions.='<option value="ext-vqueues,'.$ii['id'].',1,'.$options['lang'].'" >'.$ii['name'].'</option>';
	}
	$dropOptions.='</optgroup>';
}

//Queues
if (isset($otherroutes['queues']) && count($otherroutes['queues']) > 0){
	$dropOptions.='<optgroup label="' . _('Queues') . '">';
	foreach ($otherroutes['queues'] as $i=>$ii){
		$dropOptions.='<option value="ext-queues,'.$ii['extension'].',1,'.$options['lang'].'" >'.$ii['extension'].' : '.$ii['descr'].'</option>';
	}
	$dropOptions.='</optgroup>';
}

//Ring Groups
if (isset($otherroutes['ringgroups']) && count($otherroutes['ringgroups']) > 0){
	$dropOptions.='<optgroup label="' . _('Ring Groups') . '">';
	foreach ($otherroutes['ringgroups'] as $i=>$ii){
		$dropOptions.='<option value="ext-group,'.$ii['grpnum'].',1,'.$options['lang'].'">'.$ii['grpnum'].' : '.$ii['description'].'</option>';
	}
	$dropOptions.='</optgroup>';
}

//Announcements
if (isset($otherroutes['announcements']) && count($otherroutes['announcements']) > 0){
	$dropOptions.='<optgroup label="' . _('Announcements') . '">';
	foreach ($otherroutes['announcements'] as $i=>$ii){
		$dropOptions.='<option value="app-announcement-'.$ii['announcement_id'].',s,1,'.$options['lang'].'">'.$ii['description'].'</option>';
	}
	$dropOptions.='</optgroup>';
}

//Languages
if (isset($otherroutes['languages']) && count($otherroutes['languages']) > 0){
	$dropOptions.='<optgroup label="' . _('Languages') . '">';
	foreach ($otherroutes['languages'] as $i=>$ii){
		$dropOptions.='<option value="app-languages,'.$ii['language_id'].',1,'.$options['lang'].'">'.$ii['description'].'</option>';
	}
	$dropOptions.='</optgroup>';
	
}

//Misc Applications
if (isset($otherroutes['miscapps']) && count($otherroutes['miscapps']) > 0){
	$dropOptions.='<optgroup label="' . _('Misc Applications') . '">';
	foreach ($otherroutes['miscapps'] as $i=>$ii){
		$dropOptions.='<option value="miscapps,'.$ii['miscapps_id'].',s,1,'.$options['lang'].'">'.$ii['description'].' ('.$ii['ext'].')</option>';
	}
	$dropOptions.='</optgroup>';
}

//Extensions
if (isset($otherroutes['extensions']) && count($otherroutes['extensions']) > 0){
	$dropOptions.='<optgroup label="' . _('Extensions') . '">';
	foreach ($otherroutes['extensions'] as $i=>$ii){
		$dropOptions.='<option value="from-did-direct,'.$ii['extension'].',1,'.$options['lang'].'">'.$ii['extension'].' '.$ii['name'].'</option>';
	}
	$dropOptions.='</optgroup>';
	
}	

?>
<div style="border-radius: 10px; background-color:#F5F5F5; margin: 10px; padding: 10px;">
  <div class="row">
    
    <!-- Left Side: Reload & Highlight -->
    <div class="col-sm-2">
      <div style="display: inline-flex; gap: 5px;">
      
				<div style="position: relative; display: inline-block;">
					<!-- Hamburger button -->
					<button type="button" id="hamburgerBtn" class="btn btn-default" disabled>
						<i class="fa fa-bars"></i>
					</button>

					<!-- Dropdown menu -->
					<div class="dropdownMenu" id="dropdownMenu" style="display:none; position:absolute; top:35px; left:0; background:#f9f9f9; border:1px solid #ccc; border-radius:5px; min-width:150px; box-shadow:0 2px 5px rgba(0,0,0,0.2); padding:10px;">
						<button type="button" id="focus" class="btn btn-default" disabled>
							<i class="fa fa-magic"></i> <?php echo _('Highlight Paths'); ?>
						</button>
						<button type="button" id="sanitizeBtn" class="btn btn-default" disabled>
							<i class="fa fa-eye-slash"></i> <?php echo _('Sanitize Labels'); ?>
						</button>
						<button class="btn btn-default" onclick="openModal('customTimeModal')">
								<i class="fa fa-clock-o"></i> Simulate Date & Time
						</button>
						<button type="button" style="display:none;" id="saveModalBtn" class="btn btn-default">
							<i class="fa fa-save"></i> <?php echo _('Save View'); ?>
						</button>
						
					</div>
				</div>
				
				
				<button type="button" class="btn btn-default" id="reloadButton" disabled>
          <i class="fa fa-refresh"></i> <?php echo _('Reload'); ?>
        </button>

      </div>
    </div>

    <!-- Middle: Dialplan -->
		<div class="col-sm-7">
			<div class="input-group" style="width: 90%; display: flex; flex-wrap: nowrap;">

				<!-- Label -->
				<div class="input-group-label" style="display: flex; align-items: center; min-width: 150px; white-space: nowrap; padding: 0 5px; border: 1px solid #ccc; border-right: none; background-color: #e9ecef;">
					<i class="fa fa-sitemap" aria-hidden="true"></i>
					<span id="dialplanLabel" style="margin-left:5px;"></span>
				</div>

				<!-- Select -->
				<select id="dialPlan" class="form-control">
					<option value=""><?php echo _('Choose Dial Plan'); ?></option>
					<?php echo $dropOptions; ?>
				</select>

				<!-- Buttons -->
				<button id="prevBtn" class="btn btn-default btn-sm" title="<?php echo _('Previous'); ?>">
					<i class="fa fa-chevron-left"></i>
				</button>
				<button id="nextBtn" class="btn btn-default btn-sm" title="<?php echo _('Next'); ?>">
					<i class="fa fa-chevron-right"></i>
				</button>
				<?php 
				if (is_array($options['sections']) && (in_array('*',$options['sections']) || count($options['sections']) > 1) ){ ?>
					<button id="addNewDestBtn" class="btn btn-default btn-sm" title="<?php echo _('New Destination'); ?>">
						<i class="fa fa-plus"></i>
					</button>
				<?php } ?>
			</div>
		</div>

    <!-- Right Side: Export -->
    <div class="col-sm-3" style="margin-left:-75px;">
      <input type="text" id="filenameInput" name="nohistory" autocomplete="off" value="" class="form-control" disabled style="width: 100%;">
      <div class="input-group-btn" style="position: absolute; top: 0; right: 10px; height: 100%;">
        <button id="downloadButton" type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false" disabled style="height: 100%; white-space: nowrap;">
          <i class="fa fa-download"></i> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
					<li><a class="dropdown-item" href="#" onclick="exportImage(8)"><i class="fa fa-certificate"></i> <?php echo _('Super'); ?> .png</a></li>
          <li><a class="dropdown-item" href="#" onclick="exportImage(4)"><i class="fa fa-star"></i> <?php echo _('High'); ?> .png</a></li>
          <li><a class="dropdown-item" href="#" onclick="exportImage(2)"><i class="fa fa-circle"></i> <?php echo _('Standard'); ?> .png</a></li>
          <li id="svgExButton"><a class="dropdown-item" href="#" onclick="handleSVGExport()"><i class="fa fa-code"></i> SVG .svg</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>
<script>
let lastSearchTerm = '';
$(document).ready(function() {
	
	
	
	$('#dialPlan').select2({
    placeholder: "<?php echo _('Choose Dial Plan'); ?>",
    dropdownAutoWidth: true,
    width: '100%',
    maximumSelectionLength: 20,
    dropdownCssClass: "custom-dropdown",
    dropdownParent: $("body"),
	});


	// Store search term right before selection
	$('#dialPlan').on('select2:selecting', function () {
			const $searchInput = $('.select2-search__field');
			if ($searchInput.length) {
					lastSearchTerm = $searchInput.val();
			}
	});

	// Restore last search term and focus when dropdown opens
	$('#dialPlan').on('select2:open', function () {
			function restoreAndFocus() {
					const $searchField = $('.select2-container--open .select2-search__field');
					if (!$searchField.length) {
							requestAnimationFrame(restoreAndFocus);
							return;
					}

					// Restore last search term once
					if (lastSearchTerm && $searchField.val() !== lastSearchTerm) {
							// Delay to ensure internal handlers are attached
							setTimeout(() => {
									$searchField.val(lastSearchTerm).trigger('input');
							}, 0);
					}

					// Focus cursor after restoring
					$searchField.focus();

					// Add clear button if not already present
					if ($searchField.parent().find('.select2-search__clear').length === 0) {
							const $clearBtn = $('<span class="select2-search__clear">x</span>');

							$clearBtn.on('click', function () {
									$searchField.val('').trigger('input').focus();
							});

							$searchField.wrap('<div class="select2-search__field-wrapper" style="position: relative;"></div>');
							$searchField.parent().append($clearBtn);
					}
			}

			restoreAndFocus();
});


	$('#dialPlan').on('select2:select', function (e) {
		const selectedId = e.params.data.id;
		const selectedText = e.params.data.text;

		let cleaned = selectedText.replace(/\s\[.*?\]/, '').replace(/(\w+)s\b/, '$1').trim();
		cleaned = cleaned.replace(/\s+/g, ' ').trim();

		const optionElement = e.params.data.element;
		const $option = $(optionElement);
		const optgroup = $option.parent('optgroup');
		const optgroupLabel = optgroup.length ? optgroup.attr('label') : null;

		const dialplanLabel = document.getElementById('dialplanLabel');
		const reloadButton = document.getElementById('reloadButton');
		const hamburgerButton = document.getElementById('hamburgerBtn');
		const focusButton = document.getElementById('focus');
		const sanitizeButton = document.getElementById('sanitizeBtn');
		const filenameInput = document.getElementById('filenameInput');
		const downloadButton = document.getElementById('downloadButton');
		const savedDescription = document.getElementById('savedDescription');
		const viewId = document.getElementById('viewId');

		if (optgroupLabel) {
			const label = optgroupLabel.replace(/\s\[.*?\]/, '').replace(/(\w+)s\b/, '$1').trim();
			dialplanLabel.textContent = label;
			filenameInput.value = sanitizeFilename(label + '_' + cleaned);
			sessionStorage.setItem("selectedName", label + ': ' + selectedText);
		} else {
			dialplanLabel.textContent = '';
			filenameInput.value = sanitizeFilename(selectedText);
		}

		reloadButton.disabled = false;
		hamburgerButton.disabled = false;
		focusButton.disabled = false;
		sanitizeButton.disabled = false;
		filenameInput.disabled = false;
		downloadButton.disabled = false;
		resetFocusMode();
		
		let id= '', ext = '', jump = '', skips = [];

	if ($option.length && $option.data('skips') !== undefined) {
		document.getElementById('saveModalBtn').style.display = 'block';
		let id = $option.data('id');
		skips = [...$option.data('skips')];
		

		const parts = selectedId.split('|');
		if (parts.length >= 2) {
				ext = parts[0];
				jump = parts[1];
			} else {
				ext = selectedId;
			}
		
		viewId.value=id;
		savedDescription.value=selectedText;
		
	} else {
		// Fallback
		if (selectedId.includes('|')) {
			[ext, jump] = selectedId.split('|');
		} else {
			ext = selectedId;
		}
		skips = [];
		savedDescription.value='';
		viewId.value='';
	}

	generateVisualization(ext, jump, skips);
	
	});

});


// Enhanced getVisibleOptions to respect lastSearchTerm when dropdown closed
function getVisibleOptions($el) {
  let searchTerm = lastSearchTerm || '';

   let $options = $el.find('option:not(:disabled)').filter(function() {
    return $(this).val() !== '';
  });

  if (searchTerm.length) {
    let lower = searchTerm.toLowerCase();
    $options = $options.filter(function() {
      return $(this).text().toLowerCase().includes(lower);
    });
  }

  return $options.map(function() { return this.value; }).get();
}

// Apply selection and trigger proper select2:select with correct data
function applySelection($el, value) {
  const $option = $el.find('option[value="' + value + '"]');
  if (!$option.length) return;

  const dataObj = { 
    id: $option.val(), 
    text: $option.text(), 
    element: $option[0] 
  };

  $el.val(value).trigger('change'); // normal change event
  $el.trigger({ type: 'select2:select', params: { data: dataObj } });
}

function select2Next($el) {
  const visible = getVisibleOptions($el);
  if (!visible.length) return;

  const currentVal = $el.val();
  let idx = visible.indexOf(currentVal);
  let nextIndex = (idx + 1 + visible.length) % visible.length;
  applySelection($el, visible[nextIndex]);
}

function select2Prev($el) {
  const visible = getVisibleOptions($el);
  if (!visible.length) return;

  const currentVal = $el.val();
  let idx = visible.indexOf(currentVal);
  let prevIndex = (idx - 1 + visible.length) % visible.length;
  applySelection($el, visible[prevIndex]);
}

// Buttons
$('#nextBtn').on('click', function() {
  select2Next($('#dialPlan'));
});

$('#prevBtn').on('click', function() {
  select2Prev($('#dialPlan'));
});

<?php
	$lang = isset($options['lang']) ? $options['lang'] : 'en';
	echo 'const currentLang = "' . addslashes($lang) . '";';
?>

function sanitizeFilename(filename) {
    return filename
        .replace(/[\/\\:*?"<>|]/g, '_')        // Replace illegal characters with _
        .replace(/[\x00-\x1F\x7F]/g, '')       // Remove control characters
        .replace(/\s+/g, '_')                  // Replace spaces with _
        .trim();
}

$(document).on('click', '#addNewDestBtn', function() {
  const vizGraph = document.getElementById('vizGraph');
	const vizHeader = document.getElementById('vizHeader');
  const dialplanLabel = document.getElementById('dialplanLabel');
  const reloadButton = document.getElementById('reloadButton');
  const hamburgerButton = document.getElementById('hamburgerBtn');
  const focusButton = document.getElementById('focus');
  const sanitizeButton = document.getElementById('sanitizeBtn');
  const filenameInput = document.getElementById('filenameInput');
  const downloadButton = document.getElementById('downloadButton');
  const savedDescription = document.getElementById('savedDescription');
  const viewId = document.getElementById('viewId');
	const saveModalBtn = document.getElementById('saveModalBtn');
	const recordingModal = document.getElementById('recordingmodal');
	const customtimemodal = document.getElementById('customTimeModal');
  const $dialPlan = $('#dialPlan');

  // Clear the visualization
  vizGraph.innerHTML = '';
	vizHeader.innerHTML = '';

  // Reset text fields / labels
  dialplanLabel.textContent = '';
  filenameInput.value = '';
  savedDescription.value = '';
  viewId.value = '';

  // Reset buttons and states
  reloadButton.disabled = true;
  hamburgerButton.disabled = true;
  focusButton.disabled = true;
  sanitizeButton.disabled = true;
  filenameInput.disabled = true;
  downloadButton.disabled = true;
  saveModalBtn.style.display = 'none';
	recordingModal.style.display = 'none';
	customtimemodal.style.display = 'none';

  // Clear stored session name
  sessionStorage.removeItem('selectedName');

  // Reset focus / mode if needed
  if (typeof resetFocusMode === 'function') resetFocusMode();

  // Reset the Select2 dropdown back to placeholder
  if ($dialPlan.length) {
    $dialPlan.val(null).trigger('change');
  }

  // Finally open your modal
  openNewDestinationModal();
});

</script>
