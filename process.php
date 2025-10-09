<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true);

// Basic check

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid JSON'));
    exit;
}

$ext  = isset($input['ext']) ? $input['ext'] : '';
$jump = isset($input['jump']) ? $input['jump'] : '';
$skip = isset($input['skip']) ? $input['skip'] : array();

if ($ext==$jump){$jump='';}

// load graphviz library
require_once 'graphviz/src/Alom/Graphviz/InstructionInterface.php';
require_once 'graphviz/src/Alom/Graphviz/BaseInstruction.php';
require_once 'graphviz/src/Alom/Graphviz/Node.php';
require_once 'graphviz/src/Alom/Graphviz/Edge.php';
require_once 'graphviz/src/Alom/Graphviz/DirectedEdge.php';
require_once 'graphviz/src/Alom/Graphviz/AttributeBag.php';
require_once 'graphviz/src/Alom/Graphviz/Graph.php';
require_once 'graphviz/src/Alom/Graphviz/Digraph.php';
require_once 'graphviz/src/Alom/Graphviz/AttributeSet.php';
require_once 'process.inc.php';

//options
$options=\FreePBX::Dpviz()->getOptions();
try{
	$soundlang = FreePBX::create()->Soundlang;
	$options['lang'] = $soundlang->getLanguage();
}catch(\Exception $e){
	freepbx_log(FPBX_LOG_ERROR,"Soundlang is missing, please install it."); 
	$options['lang'] = "en";
}

$currentLocale = setlocale(LC_MESSAGES, 0);
$currentLocale = preg_replace('/\..*$/', '', $currentLocale);
$options['locale']=$currentLocale;

$options['hideall']=0;
$options['skip'] = isset($input['skip']) ? $input['skip'] : array();
$datetime = isset($options['datetime']) ? $options['datetime'] : '1';

$dproute['extension']= $ext;
if (empty($dproute)) {
$header = "<div><h2>" . _('Error: Could not find inbound route for') ." ".$ext."</h2></div>";
}else{
	dpp_load_tables($dproute);   # adds data for time conditions, IVRs, etc.

	if (!empty($jump)){
		dpp_follow_destinations($dproute, '', $jump, $options); #starts with destination
	}else{
		dpp_follow_destinations($dproute, $ext, '', $options); #starts with empty destination
	}

	/*  puts a box next to the first node.*/
	/*
	$dproute['dpgraph']->node('In Use By', array(
		'label' => "In Use By:\nline 2\lLine3\lLine3\lLine3\l",
		'shape' => 'box',
		'style' => 'filled',
		'fillcolor' => 'darkseagreen',
		'rank'=>'source'
	));
	*/

	if (!empty($skip) && empty($jump)){
		$dproute['dpgraph']->node('reset', array(
			'label' => "   "._('Reset'),
			'tooltip' => _('Reset'),
			'shape' => 'larrow',
			'URL' => '#',
			'fontcolor' => '#000',
			'fontsize' => '18pt',
			'fillcolor' => '#F0F0F0',
			'style' => 'filled'
		));
	}

	$gtext = $dproute['dpgraph']->render();


	$gtext=json_encode($gtext);
	$version= \FreePBX::Config()->get("DASHBOARD_FREEPBX_BRAND").' '.get_framework_version();
	$modinfo = \FreePBX::Modules()->getInfo('dpviz');
	$dpvizVersion = isset($modinfo['dpviz']['version']) ? $modinfo['dpviz']['rawname'] .' '. $modinfo['dpviz']['version'] : '0.0.0';

	$header = '
		<h2 style="display: flex; justify-content: space-between; align-items: center; margin: 0;">
				<span id="headerSelected"></span>
				<span id="version" style="color: #DCDCDC; font-weight: normal; font-size: 0.5em; display: none; flex-direction: column; align-items: flex-end;">
						<span>'.$version.'</span>
						<span style="text-align: right; width: 100%;">'.$dpvizVersion.'</span>
				</span>
		</h2>';
	if ($datetime==1){$header.= "<h6>".date('Y-m-d H:i:s')."</h6>";}
	$header .= '
	<input type="hidden" id="processed" value="yes">
	<input type="hidden" id="ext" value="' . htmlspecialchars($ext, ENT_QUOTES) . '">
	<input type="hidden" id="jump" value="' . htmlspecialchars($jump, ENT_QUOTES) . '">
	<input type="hidden" id="skip" value=\'' . json_encode($skip) . '\'>

	<script>
	
	function updateHeaderSelected() {
			let name = sessionStorage.getItem("selectedName");
			const headerSelected = document.getElementById("headerSelected");
			if (headerSelected) {
					headerSelected.textContent = name || "'._('No name selected').'";
			}
	}
	updateHeaderSelected();

	function exportImage(scale = 2) {
			const container = document.querySelector("#vizContainer");
			if (!container) return alert("Container not found!");

			// Clone the container so we can manipulate without affecting the UI
			const clone = container.cloneNode(true);

			// Reset SVG transform in the clone
			const svg = clone.querySelector("svg");
			if (svg) svg.style.transform = "none";

			// Put the clone offscreen so it\'s not visible
			clone.style.position = "absolute";
			clone.style.left = "-99999px";
			document.body.appendChild(clone);

			// Render with html2canvas
			html2canvas(clone, {
					scale: scale,
					useCORS: true,
					allowTaint: true,
					backgroundColor: "#ffffff"
			}).then(canvas => {
					const input = document.getElementById("filenameInput");
					const filename = (input?.value.trim() || "export") + ".png";

					const link = document.createElement("a");
					link.href = canvas.toDataURL("image/png");
					link.download = filename;
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);

					// Remove clone
					document.body.removeChild(clone);
			});
	}



	// Export cleaned SVG
	function handleSVGExport() {
			const svgElement = document.querySelector("#vizContainer svg");
			if (!svgElement) {
					alert("SVG not found!");
					return;
			}

			const input = document.getElementById("filenameInput");
			const filename = (input?.value.trim() || "graph") + ".svg";
			exportCleanedSVG(svgElement, filename);
	}

	function exportCleanedSVG(svgElement, filename) {
			const clonedSVG = svgElement.cloneNode(true);

			// Remove all <a> elements from SVG
			clonedSVG.querySelectorAll("a").forEach(link => {
					const parent = link.parentNode;
					while (link.firstChild) parent.insertBefore(link.firstChild, link);
					parent.removeChild(link);
			});

			const svgData = new XMLSerializer().serializeToString(clonedSVG);
			const blob = new Blob([svgData], { type: "image/svg+xml;charset=utf-8" });
			const url = URL.createObjectURL(blob);

			const a = document.createElement("a");
			a.href = url;
			a.download = filename.endsWith(".svg") ? filename : filename + ".svg";
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
	}
	</script>';

}

#
# This is a recursive function.  It digs through various nodes
# (ring groups, ivrs, time conditions, extensions, etc.) to find
# the path a call takes.  It creates a graph of the path through
# the dial plan, stored in the $route object.
#
#



function dpp_follow_destinations (&$route, $destination, $optional, $options) {
	$horizontal = isset($options['horizontal']) ? $options['horizontal'] : '0';
	$direction=($horizontal== 1) ? 'LR' : 'TB';
	$dynmembers= isset($options['dynmembers']) ? $options['dynmembers'] : '0';
	$combineQueueRing= isset($options['combineQueueRing']) ? $options['combineQueueRing'] : '0';
	$extOptional= isset($options['extOptional']) ? $options['extOptional'] : '0';
	$fmfmOption= isset($options['fmfm']) ? $options['fmfm'] : '0';
	$langOption= isset($options['lang']) ? $options['lang'] : 'en';
	$minimal= isset($options['minimal']) ? $options['minimal'] : '1';
	$stop=false; //reset on new call

	if (!isset($route['parent_edge_code'])){$route['parent_edge_code']='';}

	if ($minimal){
		$patterns = array(
			'/^play-system-recording/i',
			'/^from-did-direct/i',
			'/^qmember/i',
			'/^rgmember/i',
			'/^ext-local/i',
		);
		
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $destination)) {
					return;
			}
		}
	}

	 
  $pastels = array(
			"#7979FF", "#86BCFF", "#8ADCFF", "#3DE4FC", "#5FFEF7", "#33FDC0",
			"#ed9581", "#81a6a2", "#bae1e7", "#eb94e2", "#f8d580", "#979291",
			"#92b8ef", "#ad8086", "#F7A8A8", "#C5A3FF", "#FFC3A0", "#FFD6E0",
			"#FFB3DE", "#D4A5A5", "#A5D4D4", "#F5C6EC", "#B5EAD7", "#C7CEEA",
			"#E0BBE4", "#FFDFD3", "#FEC8D8", "#D1E8E2", "#E8D1E1", "#EAD5DC",
			"#F9E79F", "#D6EAF8"
	);

	$neons = array(
			"#fe0000", "#fdfe02", "#0bff01", "#011efe", "#fe00f6",
			"#ff5f1f", "#ff007f", "#39ff14", "#ff073a", "#ffae00",
			"#08f7fe", "#ff44cc", "#ff6ec7", "#dfff00", "#32cd32",
			"#ccff00", "#ff1493", "#00ffff", "#ff00ff", "#ff4500",
			"#ff00aa", "#ff4c4c", "#7df9ff", "#adff2f", "#ff6347",
			"#ff66ff", "#f2003c", "#ffcc00", "#ff69b4", "#0aff02"
	);
	
	//$optional = preg_match('/^[ANY_xX+\d\[\]]+$/', $optional) ? '' : $optional;
  if (! isset ($route['dpgraph'])) {
		
    $route['dpgraph'] = new Alom\Graphviz\Digraph('"reset'.$route['extension'].'"');
		//$route['dpgraph']->attr('graph',array('rankdir'=>$direction,'ordering'=>'in','tooltip'=>' ','ranksep'=>'.50 equally','nodesep'=>'.30'));		
		$route['dpgraph']->attr('graph',array('rankdir'=>$direction,'ordering'=>'in','tooltip'=>' '));
  }
	
  $dpgraph = $route['dpgraph'];
	
	
  dpplog(9, "destination='$destination' route[extension]: " . print_r($route['extension'], true));

  # This only happens on the first call.  Every recursive call includes
  # a destination to look at.  For the first one, we get the destination from
  # the route object.
		
	if ($destination == '') {
		
		$dpgraph->node("reset".$route['extension'], array(
			'label' => "   "._('Reset'),
			'tooltip' => _('Reset'),
			'shape' => 'larrow',
			'URL' => '#',
			'fontcolor' => '#000',
			'fontsize' => '18pt',
			'fillcolor' => '#F0F0F0',
			'style' => 'filled'
		));
    // $graph->node() returns the graph, not the node, so we always
    // have to get() the node after adding to the graph if we want
    // to save it for something.
    // UPDATE: beginNode() creates a node and returns it instead of
    // returning the graph.  Similarly for edge() and beginEdge().
    $route['parent_node'] = $dpgraph->get("reset".$route['extension']);

    # One of thse should work to set the root node, but neither does.
    # See: https://rt.cpan.org/Public/Bug/Display.html?id=101437
    #$route->{parent_node}->set_attribute('root', 'true');
    #$dpgraph->set_attribute('root' => $route->{extension});
		
    // If an inbound route has no destination, we want to bail, otherwise recurse.
    if ($optional != '') {
			$route['parent_edge_label'] = ' ';
      dpp_follow_destinations($route, $optional,'',$options);
    }elseif ($route['destination'] != '') {
			$route['parent_edge_label'] = " "._('Always');
      dpp_follow_destinations($route, $route['destination'].','.$langOption,'',$options);
    }
    return;
  }
	
  dpplog(9, "Inspecting destination $destination");

	if ((preg_match("/^from-did-direct,(\d+),(\d+),(.+)/", $destination) && $options['hideall']==1)){
		return;
	}
	
  // We use get() to see if the node exists before creating it.  get() throws
  // an exception if the node does not exist so we have to catch it.
  try {
    $node = $dpgraph->get($destination);
		
  } catch (Exception $e) {
    dpplog(7, "Adding node: $destination");
		$node = $dpgraph->beginNode($destination);
  }

  // Add an edge from our parent to this node, if there is not already one.
  // We do this even if the node already existed because this node might
  // have several paths to reach it.
	
	if (isset($route['parent_node'])){
		$ptxt = $route['parent_node']->getAttribute('label', '');
		$ntxt = $node->getAttribute('label', '');
		dpplog(9, "Found it: ntxt = $ntxt");
		
		if ($ntxt == '' ) { $ntxt = "(new node: $destination)"; }
		if ($dpgraph->hasEdge(array($route['parent_node'], $node))) {
			
			dpplog(9, "NOT making an edge from $ptxt -> $ntxt");
			$edge= $dpgraph->beginEdge(array($route['parent_node'], $node));
			$edge->attribute('label', sanitizeLabels($route['parent_edge_label']));
			$edge->attribute('labeltooltip',sanitizeLabels($ptxt));
			$edge->attribute('edgetooltip',sanitizeLabels($ptxt));
			
		} else {
			
			dpplog(9, "Making an edge from $ptxt -> $ntxt");
		
			$edge= $dpgraph->beginEdge(array($route['parent_node'], $node));
			$edge->attribute('label', sanitizeLabels($route['parent_edge_label']));
			$edge->attribute('labeltooltip',sanitizeLabels($ptxt));
			$edge->attribute('edgetooltip',sanitizeLabels($ptxt));
			
			if (preg_match("/^(edgelink)/", $route['parent_edge_code'])){
				$edge->attribute('URL', $route['parent_edge_url']);
				$edge->attribute('target', $route['parent_edge_target']);
				if (isset($route['parent_edge_labeltooltip'])){
					$edge->attribute('labeltooltip',sanitizeLabels($route['parent_edge_labeltooltip']));
					$edge->attribute('edgetooltip',sanitizeLabels($route['parent_edge_labeltooltip']));
				}
				$route['parent_edge_code']=''; //reset each time
			}
			
			if (preg_match("/^( IVR Break| Queue Callback)./", $route['parent_edge_label'])){
				$edge->attribute('style', 'dashed');
			}
			if (preg_match("/^( Callback | Destination after)./", $route['parent_edge_label'])){
				$edge->attribute('style', 'dotted');
			}
			
			//start from node
			if (preg_match("/^ +$/", $route['parent_edge_label'])){
				$edge->attribute('style', 'dotted');
			}
			
			//exclude paths
			if (in_array($destination,$options['skip'])){
				$stop=true;
			}
			
		}
	}


  dpplog(9, "The Graph: " . print_r($dpgraph, true));

  // Now bail if we have already recursed on this destination before.
  if ($node->getAttribute('label', 'NONE') != 'NONE') {
    return;
  }

	# Now look at the destination and figure out where to dig deeper.
	
		#
		# Extension (from-did-direct)
		#
  if (preg_match("/^from-did-direct,([#\d]+),(\d+),(.+)/", $destination, $matches)) {
	
		$extnum = $matches[1];
		$extLang= $matches[3];
		
		if (!isset($route['extensions'][$extnum])){
			loadExtension($route, $extnum);
		}
	
		if (isset($route['extensions'][$extnum])){
			$extension = & $route['extensions'][$extnum];
			$extname= sanitizeLabels($extension['user']['name']);
			$label = _('Extension').": ".$extnum . " " . $extname;
			$tooltip= buildExtTooltip($extnum,$route);
			
			$node->attribute('penwidth', '2');
			if (isset($extension['tech']) && $extension['tech']=='virtual'){
					$node->attribute('color', 'grey');
			}elseif (!empty($extension['dnd']) || (!empty($extension['cf']['CF']) || !empty($extension['cf']['CFB']) || !empty($extension['cf']['CFU']))) {
					// DND or any CF active
					$node->attribute('color', '#FFB300');
			} else {
					// Registered vs offline
					$node->attribute('color', !empty($extension['reg_status']) ? 'green' : 'red');
			}

			$label .= (isset($extension['mailbox']['label']) 
              ? "\n" . $extension['mailbox']['label'] 
              : '');

			if (!empty($extension['mailbox']['email'])) {
					$emails = explode(',', $extension['mailbox']['email']);
					foreach ($emails as $e) {
							$label .= "\n" . sanitizeLabels(trim($e));
					}
			}
			
			$node->attribute('label', $label);
			$node->attribute('tooltip', _('Extension').": ".$extnum."\n"._('Name').": ".$extname.$tooltip);
			$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$extnum));
			$node->attribute('target', '_blank');
			
			//FMFM
			if (isset($extension['fmfm']) && $fmfmOption){
				if ($extension['fmfm']['ddial']=='DIRECT'){
						$grplist = preg_split("/-/", $extension['fmfm']['grplist']);
						foreach ($grplist as $g){
							$g=trim($g);
							$follow='from-did-direct,'.$g.',1,'.$extLang;
							
							$route['parent_edge_label'] = " FMFM ".sprintf(_('(%s secs)'), $extension['fmfm']['pre_ring']);
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $follow,'',$options);
						}
						
						if (isset($extension['fmfm']['postdest']) && $extension['fmfm']['postdest']!='ext-local,'.$extnum.',dest'){
							 
							$route['parent_edge_label'] = " FMFM ".sprintf(_('No Answer (%s secs)'), $extension['fmfm']['grptime']);
							$route['parent_node'] = $node;
							dpp_follow_destinations($route,$extension['fmfm']['postdest'].','.$extLang,'',$options);
						}
				}
				
			}
			
			//Asterisk CF, CFB, CFU
			if (!empty($extension['cf']['CF']) || !empty($extension['cf']['CFB']) || !empty($extension['cf']['CFU'])){
				if ($extension['cf']['CF'] != '') {
					$route['parent_edge_label']= " "._('Call Forward All');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, 'from-did-direct,'.$extension['cf']['CF'].',1,'.$extLang,'',$options);
				}
				if ($extension['cf']['CFB'] != '') {
					$route['parent_edge_label']= " "._('Call Forward Busy');
					$route['parent_node'] = $node;					
					dpp_follow_destinations($route, 'from-did-direct,'.$extension['cf']['CFB'].',1,'.$extLang,'',$options);
				}
				if ($extension['cf']['CFU'] != '') {
					$route['parent_edge_label']= " "._('Call Forward Unavailable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, 'from-did-direct,'.$extension['cf']['CFU'].',1,'.$extLang,'',$options);
				}
			}
			
			
		}else{
			//phone numbers or remote extensions
			$node->attribute('label', $extnum);
			$node->attribute('tooltip', $node->getAttribute('label'));
		}

		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', $pastels[15]);
		$node->attribute('style', 'rounded,filled');
		
		//Optional Destinations
		if ($extOptional && (!empty($extension['noanswer_dest']) || !empty($extension['busy_dest']) || !empty($extension['chanunavail_dest'])) ) {
			if (
					$extension['noanswer_dest'] === $extension['busy_dest'] &&
					$extension['noanswer_dest'] === $extension['chanunavail_dest']
			) {
					// All three are equal
					$route['parent_edge_label'] = " "._('No Answer, Busy, Not Reachable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
			} elseif (
					$extension['noanswer_dest'] === $extension['busy_dest']
					&& $extension['chanunavail_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Busy are the same, but Not Reachable is different
					$route['parent_edge_label'] = " "._('No Answer & Busy');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
				}
					//Not Reachable
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = " "._('Not Reachable');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'].','.$extLang,'',$options);
					}
			} elseif (
					$extension['noanswer_dest'] === $extension['chanunavail_dest']
					&& $extension['busy_dest'] !== $extension['noanswer_dest']
			) {
				if (!empty($extension['noanswer_dest'])) {
					// No Answer and Not Reachable are the same
					$route['parent_edge_label'] = " "._('No Answer & Not Reachable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
				}
					//Busy
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = " "._('Busy');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
					}
			} elseif (
					$extension['busy_dest'] === $extension['chanunavail_dest']
					&& $extension['noanswer_dest'] !== $extension['busy_dest']
			) {
				if (!empty($extension['busy_dest'])) {
					// Busy and Not Reachable are the same
					$route['parent_edge_label'] = " "._('Busy & Not Reachable');
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
				}
					//No Answer
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = " "._('No Answer');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
					}
			} else {
					// All are different
					if (!empty($extension['noanswer_dest'])) {
							$route['parent_edge_label'] = " "._('No Answer');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['noanswer_dest'].','.$extLang,'',$options);
					}
					if (!empty($extension['busy_dest'])) {
							$route['parent_edge_label'] = " "._('Busy');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['busy_dest'].','.$extLang,'',$options);
					}
					if (!empty($extension['chanunavail_dest'])) {
							$route['parent_edge_label'] = " "._('Not Reachable');
							$route['parent_node'] = $node;
							dpp_follow_destinations($route, $extension['chanunavail_dest'].','.$extLang,'',$options);
					}
			}
		}
		#end of Extension (from-did-direct)

		#
		# Voicemail
		#
  } elseif (preg_match("/^ext-local,vm([b,i,s,u])(\d+),(\d+)/", $destination, $matches)) {
		$module='Voicemail';
		$vmtype= $matches[1];
		$vmnum = $matches[2];
		$vmother = $matches[3];
		
		$vm_array=array('b'=> _('Busy Message'),'i'=> _('Instructions Only'),'s'=> _('No Message'),'u'=> _('Unavailable Message') );
		// Make sure extension is loaded/hydrated
		$tooltip=_('Voicemail').': '.$vmnum;
		$tooltip .= buildExtTooltip($vmnum, $route);

		if ($tooltip !== '' && isset($route['extensions'][$vmnum])) {
				// Retrieve extension data that was hydrated inside buildExtTooltip
				$extension = $route['extensions'][$vmnum];

				// Speaker icon if applicable
				$speaker = ($vmtype == 'u' || $vmtype == 'b') ? '🔊 ' : '';

				// Extension name
				$extname = '';
				if (!empty($extension['name'])) {
						$extname = sanitizeLabels($extension['name']);
				}

				// Message counts (already cached by hydrateExtension)
				$msgLabel = '';
				if (isset($extension['mailbox']['label'])) {
						$msgLabel = "\n" . $extension['mailbox']['label'];
				}

				// Email if present
				$extemail = '';
				if (!empty($extension['mailbox']['email'])) {
						$extemail = "\n" . sanitizeLabels($extension['mailbox']['email']);
						$extemail = str_replace(",", ",\n", $extemail);
				}

				// Final label
				$label = $speaker . $vmnum . " " . $extname . " (" . $vm_array[$vmtype] . ")" . $msgLabel . $extemail;

				makeNode($module, $vmnum, $label, $tooltip, $node);

		} else {
				notFound($module, $destination, $node);
		}
		#end of Voicemail	

		#
		# Queues and Virtual Queues
		#
  } elseif (preg_match("/^(ext-v?queues),(\d+),(\d+),(.+)/", $destination, $matches)) {
		$queueType= $matches[1];
    $num = $matches[2];
    $qother = $matches[3];
		$qlang= $matches[4];
		
		$label = $tooltip = '';
		
		if ($queueType=='ext-vqueues'){
			
			$module="Virtual Queues";
			$vqnum = $num;
			$vq    = lazyLoadRow($route, 'vqueues', $vqnum);

			if (!empty($vq)) {

				$tooltipitems='';
				$label=sanitizeLabels($vq['name']) ."\n";
				if (!empty($vq['cidpp'])){$tooltipitems.=_('CID Prefix').": ".sanitizeLabels($vq['cidpp'])."\n";}
				if (!empty($vq['alertinfo'])){$tooltipitems.=_('Alert Info').": ".sanitizeLabels($vq['alertinfo'])."\n";}
				if (!empty($vq['music'])){$tooltipitems.=_('Music on hold Class').": ".sanitizeLabels($vq['music'])."\n";}
				if (!empty($vq['language'])){$tooltipitems.=_('Language').": ".sanitizeLabels($vq['language'])."\n";$qlang=$vq['language'];}
				
				$tooltip=$label."\n".$tooltipitems."\n";
				
				if ($vq['gotodest'] != '') {
					if (preg_match("/^ext-queues,(\d+),(\d+)/", $vq['gotodest'], $matches)) {
						$qnum=$matches[1];
						$failover=$vq['dest'];
					}else{
						makeNode($module,$vqnum,$label,$tooltip,$node);
						if ($stop){
							$undoNode= stopNode($dpgraph,$destination);
							$edge= $dpgraph->beginEdge(array($node, $undoNode));
							$edge->attribute('style', 'dashed');
							$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
							
							return;
						}
						$route['parent_edge_label'] = " "._('Continue');
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $vq['gotodest'].','.$qlang,'',$options);
						return;
					}
				}
				
				if (!empty($vq['cdest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Caller Post Hangup');
					dpp_follow_destinations($route, $vq['cdest'].','.$qlang,'',$options);
				}
				if (!empty($vq['adest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Agent Post Hangup');
					dpp_follow_destinations($route, $vq['adest'].','.$qlang,'',$options);
				}
				if (!empty($vq['full_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on FULL');
					dpp_follow_destinations($route, $vq['full_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINEMPTY');
					dpp_follow_destinations($route, $vq['joinempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEEMPTY');
					dpp_follow_destinations($route, $vq['leaveempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINUNAVAIL');
					dpp_follow_destinations($route, $vq['joinunavail_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEUNAVAIL');
					dpp_follow_destinations($route, $vq['leaveunavail_dest'].','.$qlang,'',$options);
				}
			}else{
				notFound($module,$destination,$node);
				return;
			}
			
		}else{
			$module="Queues";
			$qnum=$num;
			
			
		}
		
		$q = lazyLoadRow($route, 'queues', $qnum);
		if ($q) {
			$q =& $route['queues'][$qnum];
			
			if ($dynmembers){ //options
				// Dynamic Queue members
				if (!isset($q['members']['dynamic'])){
					$dynmem = runAstmanCommand("database show QPENALTY $qnum");
					
					$dynamicMembers = parsePenaltyAgents($dynmem);
					$q['members']['dynamic'] = [];
					addAndSortMembers($q['members']['dynamic'], $dynamicMembers);
					
				}

				//dynamic agents logged in?
				if (!isset($q['loggedin'])){
					$q['loggedin'] = getLoggedInAgents($qnum);
				}
			}

			//paused ?
			if (!isset($q['paused'])) {
				$q['paused'] = getPausedAgents($qnum);
			}
			
			//is the parent a virtual queue?
			if ($queueType=='ext-vqueues'){
				$label.="Queues: ";
				$vq=$route['vqueues'][$vqnum];
				$cidPrefix = $vq['cidpp'] != '' ? $vq['cidpp'] : $q['grppre'];
				if ($vq['music'] !=''){$music=$vq['music'];}elseif (isset($q['data']['music'])){$music=$q['data']['music'];}else{$music='inherit';}
				if (!empty($vq['language'])){$qlang=$vq['language'];}
				if ($vq['maxwait'] !=='-1'){$maxwait=$vq['maxwait'];}else{$maxwait=$q['maxwait'];}

				if ($vq['dest'] !=''){$failover=$vq['dest'];}else{$failover=$q['dest'];}
				
			}else{
				$cidPrefix=$q['grppre'];
				$maxwait=$q['maxwait'];
				if (isset($q['data']['music'])){$music=$q['data']['music'];}else{$music='inherit';}
				
				$failover=$q['dest'];
			}
				
			if ($maxwait == 0 || $maxwait == '' || !is_numeric($maxwait)) {
				$maxwait = _('Unlimited');
			} else {
				$maxwait = secondsToTimes($maxwait);
			}
			
			$label.=$qnum . " " . sanitizeLabels($q['descr']);
			$restrict=array('Call as Dialed','No Follow-Me or Call Forward','Extensions Only');
			$skipbusy=array('No','Yes','Yes + (ringinuse=no)','Queue calls only (ringinuse=no)');
			$mohclass=array('MoH Only','Ring Only','Agent Ringing');
			$noyes=array('No','Yes');
			$maxcallers = ($q['data']['maxlen'] == 0) ? _('Unlimited') : $q['data']['maxlen'];
			
			if ($q['data']['announce-frequency']==0){
				$position="["._('Caller Position')."]\n"._('Disabled')."\n\n";
			}else{
				$position="["._('Caller Position')."]\n"._('Frequency').": ".secondsToTimes($q['data']['announce-frequency'])."\n"._('Minimum Announcement Interval').": ".secondsToTimes($q['data']['min-announce-frequency'])."\n"._('Announce Position').": ".ucfirst($q['data']['announce-position'])."\n"._('Announce Hold Time').": ".ucfirst($q['data']['announce-holdtime'])."\n\n";
			}

			if ($q['data']['periodic-announce-frequency']==0){$repeat='Disabled';$edgeRepeat='';}else{$repeat=secondsToTimes($q['data']['periodic-announce-frequency']);$edgeRepeat=" (every ".$repeat.")";}
			if (!empty($q['ivr_id']) && $q['ivr_id'] != 'none') {

					// ensure breakout IVR is loaded
					$ivr = lazyLoadRow($route, 'ivrs', $q['ivr_id']);

					$breakoutname = ($ivr && isset($ivr['name'])) ? $ivr['name'] : "none";
					$periodic  = "["._('Periodic Announcements')."]\n";
					$periodic .= "IVR Break Out Menu: ".$breakoutname."\n";
					$periodic .= _('Repeat Frequency').": ".$repeat;

			} elseif (!empty($q['callback_id']) && $q['callback_id'] != 'none') {

					// ensure queuecallback is loaded
					$cb = lazyLoadRow($route, 'queuecallback', $q['callback_id']);

					$breakoutname = ($cb && isset($cb['name'])) ? $cb['name'] : "none";
					$periodic  = "["._('Periodic Announcements')."]\n";
					$periodic .= _('Queue Callback').": ".$breakoutname."\n";
					$periodic .= _('Repeat Frequency').": ".$repeat;

			} else {
					$periodic = "["._('Periodic Announcements')."]\n"._('Disabled')."\n";
			}
			
			$tooltip="["._('General Settings')."]\n"._('CID Prefix').": ".$cidPrefix."\n"._('Strategy').": ".$q['data']['strategy']."\n"._('Agent Restrictions').": ".$restrict[$q['use_queue_context']]."\n"._('Autofill').": ".ucfirst($q['data']['autofill'])."\n"._('Skip Busy Agents').": ".$skipbusy[$q['cwignore']]."\n"._('Music On Hold Class').": ".$music." (".$mohclass[$q['ringing']].")\n"._('Call Recording').": ".$q['data']['recording']."\n"._('Mark calls answered elsewhere').": ".$noyes[$q['data']['answered_elsewhere']].
			"\n\n["._('Timing & Agent Options')."]\n"._('Max Wait Time').": ".$maxwait."\n"._('Agent Timeout').": ".secondsToTimes($q['data']['timeout'])."\n"._('Agent Retry').": ".secondsToTimes($q['data']['retry'])."\n"._('Wrap Up Time').": ".secondsToTimes($q['data']['wrapuptime']).
			"\n\n["._('Capacity Options')."]\n"._('Max Callers').": ".$maxcallers."\n"._('Join Empty').": ".ucfirst($q['data']['joinempty'])."\n"._('Leave Empty').": ".ucfirst($q['data']['leavewhenempty']).
			"\n\n".$position.$periodic;
			makeNode($module,$num,$label,$tooltip,$node);
			
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
				$route['parent_edge_label'] = " ".sprintf(_('No Answer (%s)'), $maxwait);
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $failover.','.$qlang,'',$options);
			
			if (!empty($q['members']) && !$minimal){
				if ($options['queue_member_display']==1){ //--option "Single"
					foreach ($q['members'] as $types=>$type) {
						foreach ($type as $member){
							$splitMember= explode(',', $member);
							$member=$splitMember[0];
							$pen=$splitMember[1];
							
							if ($options['queue_penalty']==1){
								$penalty="\n"._('Penalty').": ".$pen;
							}else{
								$penalty='';
							}
							
							$route['parent_node'] = $node;
							if ($types === 'static') {
									if (!isset($route['extensions'][$member])) {
											// Try lazy load
											hydrateExtension($route, $member);
									}

									if (isset($route['extensions'][$member])) {
											$extension = &$route['extensions'][$member];

											// Flags for static members
											$flags = array(
													'paused'   => in_array($member, $route['queues'][$qnum]['paused']),
													'loggedin' => true,   // always logged in
													'dynamic'  => false   // static member
											);

											// Resolve status
											$status = resolveExtensionStatus($extension, 'queue_edge', $flags);

											if ($status['icon'] !== '🟡' && $status['icon'] !== '⏸️') {
													$status['icon'] = '';
											}

											// Edge label
											$route['parent_edge_label'] = " " . $status['icon'] . _('Static') . $penalty;
											$route['parent_edge_code']  = 'static';

									} else {
											// fallback if extension truly not found
											$route['parent_edge_label'] = " " . _('Static') . $penalty;
											$route['parent_edge_code']  = 'static';
									}

							} else {
									// Dynamic member
									
									if (!isset($route['extensions'][$member])) {
											// Try lazy load
											hydrateExtension($route, $member);
									}
									
									if (isset($route['extensions'][$member])) {
											$extension = &$route['extensions'][$member];

											// Flags for dynamic members
											$flags = [
													'paused'   => in_array($member, $route['queues'][$qnum]['paused']),
													'loggedin' => in_array($member, $route['queues'][$qnum]['loggedin']),
													'dynamic'  => true
											];

											// Resolve status
											$status = resolveExtensionStatus($extension, 'queue_edge', $flags);

											// Edge label
											$route['parent_edge_label'] = " " . $status['icon'] . _('Dynamic') . $penalty;
											$route['parent_edge_code']  = 'dynamic';
									}
							}

							
							switch ($combineQueueRing) {
								case "2":
									$go="from-did-direct,$member,1,$qlang";
									break;
								default:
									$go = "qmember$member";
							}
							
							dpp_follow_destinations($route, $go,'',$options);
						}
					}					

				}elseif ($options['queue_member_display']==2){ //--option "Combine"
					$line=_('Queue')." ".$qnum." "._('Agents').":\n";
					foreach ($q['members'] as $types=>$members) {
						if ($types=='static' && !empty($q['members']['static'])){
							$line.="["._('Static')."]\n";
						}elseif($types=='dynamic' && !empty($q['members']['dynamic']) && $dynmembers){
							$line.="["._('Dynamic')."]\n";
						}
						
						foreach ($members as $member) {
						
							$split=explode(',',$member);
							$member=$split[0];
							$pen=$split[1];
							$penalty = ($options['queue_penalty'] == 1) ? ",$pen" : '';
							
							// Make sure extension is loaded/hydrated
							$extension = hydrateExtension($route, $member);

							if ($extension) {
									$flags = array(
											'paused'   => in_array($member, $route['queues'][$qnum]['paused']),
											'loggedin' => ($dynmembers && in_array($member, $route['queues'][$qnum]['loggedin'])),
											'dynamic'  => ($types === 'dynamic')
									);

									$status = resolveExtensionStatus($extension, 'queue', $flags);

									$line .= $status['icon']." Ext ".$member.$penalty." ".sanitizeLabels($extension['name'])." ".$status['label']."\l";

							} else {
									$line .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$member.$penalty."\l";
							}

						}
					}
					
					$memNode= $dpgraph->beginNode('queuemembers'.$qnum,
						array(
							'label' => $line,
							'tooltip' => $line,
							'URL' => $node->getAttribute('URL', '').'#qagentlist',
							'target' => '_blank',
							'shape' => 'rect',
							'style' => 'rounded,filled',
							'fillcolor' => $pastels[20]
						)
					);
					$edge= $dpgraph->beginEdge(array($node, $memNode));
					$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
					
				}else{
					//do not display agents --option "Hide"
				}
			}
			
			#Queue Plus Options
			lazyLoadRow($route, 'vqplus_queue_config', $q['extension']);
			if (!empty($q['vqplus'])){
				$vq=$q['vqplus'];
				if (!empty($vq['cdest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Caller Post Hangup');
					dpp_follow_destinations($route, $vq['cdest'].','.$qlang,'',$options);
				}
				if (!empty($vq['adest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Agent Post Hangup');
					dpp_follow_destinations($route, $vq['adest'].','.$qlang,'',$options);
				}
				if (!empty($vq['full_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on FULL');
					dpp_follow_destinations($route, $vq['full_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINEMPTY');
					dpp_follow_destinations($route, $vq['joinempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveempty_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEEMPTY');
					dpp_follow_destinations($route, $vq['leaveempty_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['joinunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on JOINUNAVAIL');
					dpp_follow_destinations($route, $vq['joinunavail_dest'].','.$qlang,'',$options);
				}
				if (!empty($vq['leaveunavail_dest'])){
					$route['parent_node'] = $node;
					$route['parent_edge_label'] = " "._('Queue Fail Over on LEAVEUNAVAIL');
					dpp_follow_destinations($route, $vq['leaveunavail_dest'].','.$qlang,'',$options);
				}
			}
			
			#Breakout Menus
			if (is_numeric($q['ivr_id'])) {
					$ivr = lazyLoadRow($route, 'ivrs', $q['ivr_id']);
					if ($ivr) {
							$route['parent_edge_label'] = " IVR Break Out" . $edgeRepeat;
							$route['parent_node'] = $node;

							dpp_follow_destinations(
									$route,
									'ivr-' . $q['ivr_id'] . ',s,1,' . $qlang,
									'',
									$options
							);
					} else {
							notFound('IVR Break Out', $destination, $node);
					}
			}

			if (isset($q['callback_id']) && is_numeric($q['callback_id'])) {
					$cb = lazyLoadRow($route, 'queuecallback', $q['callback_id']);
					if ($cb) {
							$cbstart = isset($cb['cbstarttime']) ? $cb['cbstarttime'] : '';
							$cbend   = isset($cb['cbendtime'])   ? $cb['cbendtime']   : '';

							$route['parent_edge_label'] = " Queue Callback " . $cbstart . " - " . $cbend . "\l" . $edgeRepeat;
							$route['parent_node'] = $node;

							dpp_follow_destinations(
									$route,
									'queuecallback-' . $q['callback_id'] . ',request,1,' . $qlang,
									'',
									$options
							);
					} else {
							notFound('Queue Callback', $destination, $node);
					}
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Queues
		
		#
		# Queue members (static and dynamic)
		#
	} elseif (preg_match("/^qmember(.+)$/", $destination, $matches)) {
		$qextension=$matches[1];
		$previousId=$route['parent_node']->getId();  //get qnum
		
		if (preg_match("/^ext-queues,(\d+),(\d+),(.+)/", $previousId, $matches)) {
			$qnum=$matches[1];
		}else{
			$qnum='';
		}
		
		// Make sure extension is loaded
		$extension = hydrateExtension($route, $qextension);

		if ($extension) {
				$qlabel  = _('Ext')." ".$qextension."\n".sanitizeLabels($extension['name']);
				$tooltip = _('Extension').": ".$qextension."\n"._('Name').": ".sanitizeLabels($extension['name']);

				// Flags for single node
				$flags = array(
						'paused'   => false,  // single node can't be paused
						'loggedin' => ($dynmembers && ($qnum!='' && in_array($qextension, $route['queues'][$qnum]['loggedin']))),
						'dynamic'  => true
				);

				// Resolve status
				$status = resolveExtensionStatus($extension, 'queue', $flags);

				// Tooltip (append detailed info)
				$tooltip .= buildExtTooltip($qextension, $route);

				// Node appearance
				$node->attribute('penwidth', '2');
				switch ($status['icon']) {
						case '⚪': $node->attribute('color', 'grey'); break;      // virtual
						case '🟡': $node->attribute('color', '#FFB300'); break;   // DND / CF
						case '🔵': $node->attribute('color', 'blue'); break;      // dynamic logged in
						case '🟢': $node->attribute('color', 'green'); break;     // registered
						case '🔴':
						default:  $node->attribute('color', 'red'); break;        // offline
				}

				$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$qextension));
				$node->attribute('target', '_blank');

		} else {
				// fallback if extension couldn't be loaded at all
				$qlabel  = $qextension;
				$tooltip = $qextension;
		}
		
		$node->attribute('label', $qlabel);
		$node->attribute('tooltip', $tooltip);
		$node->attribute('shape', 'rect');
		$node->attribute('style', 'rounded,filled');
		
		if ($route['parent_edge_code'] == 'static') {
			$node->attribute('fillcolor', $pastels[20]);
		}else{
			$node->attribute('fillcolor', $pastels[8]);
		}
		$route['parent_edge_code']='';
		#end of Queue members (static and dynamic)
		
		#
		# Ring Groups
		#
  } elseif (preg_match("/^(ext-group),(\d+),(\d+),(.+)/", $destination, $matches)) {
    $module     = "Ring Groups";
    $routetable = $matches[1];
    $rgnum      = $matches[2];
    $rglang     = $matches[4];

    // Lazy load only that one group
    $rg = lazyLoadRow($route, $routetable, $rgnum);

    if ($rg) {
			
			$recID= $rg['annmsg_id'];
			
			if ($recID) {
				$recording = lazyLoadRow($route, 'recordings', $recID);

				if ($recording) {
					$rgRecName= $recording['displayname'];
					$recordingId=$recording['id'];
					$featureCode= getFeatureNum($recordingId,$route);
					$rgRecName= "\n🔊 "._('Announcement')." (".$rglang."): ".sanitizeLabels($rgRecName)."\n"._('Feature Code').": ".$featureCode;
				}else{
					$rgRecName='';
				}
			}else{
				$rgRecName='';
			}
			
			$label=$rgnum.' '.sanitizeLabels($rg['description']).$rgRecName;
			if ($rg['needsconf']!=''){$conf='Yes';}else{$conf="No";}
			$tooltip=
				_('Description') . ": " . sanitizeLabels($rg['description']) . "\n"
				. _('Strategy') . ": " . $rg['strategy'] . "\n"
				.	_('Ring Time') . ": " . secondsToTimes($rg['grptime']) . "\n"
				. _('Music On Hold') . ": " . $rg['ringing'] . "\n"
				. _('CID Prefix') . ": " . sanitizeLabels($rg['grppre']) . "\n"
				. _('Confirm Calls') . ": " . $conf . "\n"
				. _('Call Recording') . ": " . $rg['recording'] . "\n"
			;
			makeNode($module,$rgnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}

			$grplist=$rg['grplist'];
			$grplist = preg_split("/-/", $grplist);
			
			if (!$minimal){
				if ($options['ring_member_display']==1){ //--option "Single"
					foreach ($grplist as $member) {
						$route['parent_node'] = $node;
						$route['parent_edge_label'] = '';
						switch ($combineQueueRing) {
								case "1":
										$go = "qmember$member";
										break;
								case "2":
										$go="from-did-direct,$member,1,$rglang";
										break;
								default:
										$go="rgmember$member";
						}
						dpp_follow_destinations($route,$go,'',$options);
					} 
				}elseif ($options['ring_member_display']==2){  //--option "Combine"
					$line=_('Ring Group')." ".$rgnum." "._('List').":\n";
					foreach ($grplist as $member){
						// Make sure extension is loaded/hydrated
						$extension = hydrateExtension($route, $member);

						if ($extension) {
								// No paused/loggedin in ring groups
								$status = resolveExtensionStatus($extension, 'ringgroup');

								$line .= $status['icon']." Ext ".$member." ".sanitizeLabels($extension['name'])." ".$status['label']."\l";

						} else {
								$line .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$member."\l";
						}

					}
					
					$memNode= $dpgraph->beginNode('ringmembers'.$rgnum,
						array(
							'label' => $line,
							'tooltip' => $line,
							'URL' => $node->getAttribute('URL', ''),
							'target' => '_blank',
							'shape' => 'rect',
							'style' => 'rounded,filled',
							'fillcolor' => $pastels[2]
						)
					);
					$edge= $dpgraph->beginEdge(array($node, $memNode));
					$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
					
				}else{
					//do not display members --option "Hide"
				}
			}
				
				# The destinations we need to follow are the no-answer destination
				# (postdest) and the members of the group.
				if ($rg['postdest'] != '') {
					$route['parent_edge_label'] = " ".sprintf(_('No Answer (%s)'), secondsToTimes($rg['grptime']));
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $rg['postdest'].','.$rglang,'',$options);
				}
			
		}else{
			notFound($module,$destination,$node);
		}
    # End of Ring Groups
  
		#
		# Ring Group Members
		#
	} elseif (preg_match("/^rgmember([#\d]+)/", $destination, $matches)) {
		$rgext = $matches[1];
		
		// Make sure extension is loaded/hydrated
		$extension = hydrateExtension($route, $rgext);

		if ($extension) {
				$rglabel = _('Ext')." ".$rgext."\n".sanitizeLabels($extension['name']);
				$tooltip = _('Ext').": ".$rgext."\n"._('Name').": ".sanitizeLabels($extension['name']);

				// Flags for ring group (no queue-specific statuses)
				$flags = array(
						'paused'   => false,
						'loggedin' => false,
						'dynamic'  => false
				);

				// Resolve status
				$status = resolveExtensionStatus($extension, 'ringgroup_node', $flags);

				// Tooltip (append detailed info)
				$tooltip .= buildExtTooltip($rgext, $route);

				// Node appearance
				$node->attribute('penwidth', '2');
				switch ($status['icon']) {
						case '⚪': $node->attribute('color', 'grey'); break;    // virtual
						case '🟡': $node->attribute('color', '#FFB300'); break; // DND / CF
						case '🟢': $node->attribute('color', 'green'); break;   // registered
						case '🔴':
						default:  $node->attribute('color', 'red'); break;      // offline
				}

				$node->attribute('URL', htmlentities('/admin/config.php?display=extensions&extdisplay='.$rgext));
				$node->attribute('target', '_blank');

		} else {
				$rglabel = $tooltip = $rgext;
		}
		
		$node->attribute('label', $rglabel);
		$node->attribute('tooltip', $tooltip);
		$node->attribute('fillcolor', $pastels[2]);
		$node->attribute('shape', 'rect');
		$node->attribute('style', 'rounded,filled');
		# end of ring group members

		#
		# IVRs
		#
	} elseif (preg_match("/^ivr-(\d+),([a-z]+),(\d+),(.+)/", $destination, $matches)) {
    $module   = "IVR";
    $routetable = "ivrs";
    $inum     = $matches[1];  // ivr id
    $iflag    = $matches[2];
    $iother   = $matches[3];
    $ilang    = $matches[4];

    // Lazy load just this IVR
    $ivr = lazyLoadRow($route, $routetable, $inum);

    if ($ivr) {
			$recID= $ivr['announcement'];
			
			if ($recID) {
				$recording = lazyLoadRow($route, 'recordings', $recID);

				if ($recording) {
					$ivrRecName= $recording['displayname'];
					$recordingId=$recording['id'];
					$featureCode= getFeatureNum($recordingId,$route);
					$ivrRecName= "🔊 "._('Announcement')." (".$ilang."): ".sanitizeLabels($ivrRecName)."\n"._('Feature Code').": ".$featureCode;
				}else{
					$ivrRecName=_('Announcement').': '._('None');
				}
			}else{
				$ivrRecName=_('Announcement').': '._('None');
			}
			
			$label=sanitizeLabels($ivr['name'])."\n".$ivrRecName;
			
			/**** TODO? Add entries to main node. Maybe let destinations follow through.*/

			
			// global (or pass by ref) array for later processing
			$unresolvedDestinations = [];
			if (!empty($ivr['entries'])){
				$printedAny = false;
				
				foreach ($ivr['entries'] as $selid => $ent) {
						$ivrLabel = getLabel($ent['dest'], $route, $unresolvedDestinations, $selid);
						if ($ivrLabel === false) {
								continue; // skip printing unresolved
						}
						
						if (!$printedAny) {
								$label .= "\n\n"; // only once, and only if something resolves
								$printedAny = true;
						}
						$label.= sprintf(_("Selection %s"), $selid) .": {$ivrLabel}\l";
				}
			}
			
			// Handle invalid
			if (!empty($ivr['invalid_destination'])) {
					$ivrLabel = getLabel($ivr['invalid_destination'], $route, $unresolvedDestinations, 'i');
					if ($ivrLabel !== false) {
							$label .= "Invalid: {$ivrLabel}\\l";
					}
			}

			// Handle timeout
			if (!empty($ivr['timeout_destination'])) {
					$ivrLabel = getLabel($ivr['timeout_destination'], $route, $unresolvedDestinations, 't');
					if ($ivrLabel !== false) {
							$label .= "Timeout: {$ivrLabel}\\l";
					}
			}
			
			
			if ($ivr['directdial']=='ext-local'){
				$ddial="Enabled";
			}elseif (is_numeric($ivr['directdial'])){
				$dirId = (int)$ivr['directdial'];
				$directory = lazyLoadRow($route, 'directory', $dirId);

				if ($directory && isset($directory['dirname'])) {
						$ddial = $directory['dirname'];
				} else {
						$ddial = ''; // or _("Unknown Directory")
				}
			}else{
				$ddial=$ivr['directdial'];
			}
			$retvm = ($ivr['retvm'] === '') ? _("No") : _("Yes");
			$tooltip=
				 _('Name') . ": " .  sanitizeLabels($ivr['name']) . "\n"
				. _('Description') . ": " .  sanitizeLabels($ivr['description']) . "\n"
				. _('Enable Direct Dial') . ": " . $ddial . "\n"
				. _('Timeout') . ": " . secondsToTimes($ivr['timeout_time']) . "\n"
				. _('Invalid Retries') . ": " . $ivr['invalid_loops'] . "\n"
				. _('Invalid Retry Recording') . ": "	 .  findRecording($route,$ivr['invalid_retry_recording']) . "\n"
				. _('Invalid Recording') . ": " . findRecording($route,$ivr['invalid_recording']) . "\n"
				. _('Timeout Retries') . ": " . $ivr['timeout_loops'] . "\n"
				. _('Timeout Retry Recording') . ": "  .  findRecording($route,$ivr['timeout_retry_recording']) . "\n"
				. _('Timeout Recording') . ": " . findRecording($route,$ivr['timeout_recording']) . "\n"
				. _('Return to IVR after VM') . ": " . $retvm . "\n"
			;
			
			makeNode($module,$inum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			# The destinations we need to follow are the invalid_destination,
			# timeout_destination, and the selection targets
			
			#now go through the selections
			// --- Group IVR routes ---
			
			$grouped = array();

			if (!empty($unresolvedDestinations)) {
					foreach ($unresolvedDestinations as $ent) {
							$dest = $ent['dest'] . ',' . $ilang;
							$sel  = $ent['selection'];

							if ($sel === 'i') {
									$grouped[$dest][] = _('Invalid Input');
							} elseif ($sel === 't') {
									$grouped[$dest][] = sprintf(_('Timeout (%s secs)'), $ivr['timeout_time']);
							} else {
									$grouped[$dest][] = sprintf(_('Selection %s'), $sel);
							}
					}
			}

			foreach ($grouped as $dest => $labels) {
					$label = implode(",\n", $labels);
					$route['parent_edge_label'] = " " . $label;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $dest, '', $options);
			}

		}else{
			notFound($module,$destination,$node);
		}		
		# end of IVRs

		#
		# Inbound Routes
		#
  } elseif (preg_match("/^from-trunk,((?:[^\[&,]+(?:\[[^\]]+\])?))(&[^,]*)?,(\d+),(.+)/", $destination, $matches)) {
		$module   = "Incoming";
		$num = $matches[1];
		
		$numcid = str_replace("&", "", $matches[2]);
		$numLang= $matches[4];		
		
		if (empty($num)){$num='ANY';}
		
		$incoming = lazyLoadRow($incoming, 'incoming', $num, $numcid);
		
		$currentLocale = setlocale(LC_MESSAGES, 0);
		$currentLocale = preg_replace('/\..*$/', '', $currentLocale);
		
		if ($incoming) {
			// Success — $incoming is the row from DB
			if (!empty($numcid)) {
					$numcidd = " / " . formatPhoneNumbers($numcid,$currentLocale);
			} else {
					$numcidd = " / ANY";
			}
		
			if (isset($incoming['language'])){$numLang=$incoming['language'];}
			
			$didLabel = ($num == "ANY") ? "ANY" : formatPhoneNumbers($num,$currentLocale);
			$didLabel.= $numcidd."\n".$incoming['description'];
			if ($num=='ANY'){
				$didLink='/';
			}else{
				$didLink=$num.'/'.$numcid;
			}
			
			$didTooltip=$num.$numcidd."\n";
			$didTooltip.= !empty($incoming['cidnum']) ? _('Caller ID Number').": " . $incoming['cidnum']."\n" : "";
			$didTooltip.= !empty($incoming['description']) ? _('Description').": " . $incoming['description']."\n" : "";
			$didTooltip.= !empty($incoming['alertinfo']) ? _('Alert Info').": " . $incoming['alertinfo']."\n" : "";
			$didTooltip.= !empty($incoming['grppre']) ? _('CID Prefix').": " . $incoming['grppre']."\n" : "";
			$didTooltip.= !empty($incoming['mohclass']) ? _('Music on hold class').": " . $incoming['mohclass']."\n" : "";
			$didTooltip.= !empty($incoming['language']) ? _('Language').": " . $incoming['language']."\n" : "";
			
			$node->attribute('label', sanitizeLabels($didLabel));
			$node->attribute('tooltip',sanitizeLabels($didTooltip));
			$node->attribute('width', 2);
			$node->attribute('margin','.13');
			$node->attribute('URL', htmlentities('/admin/config.php?display=did&view=form&extdisplay='.urlencode($didLink)));
			$node->attribute('target', '_blank');
			$node->attribute('shape', 'rect');
			$node->attribute('fillcolor', 'darkseagreen');
			$node->attribute('style', 'rounded,filled');
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($options['blacklist']){ //--blacklist
				$blackCheck=\FreePBX::Modules()->checkStatus("blacklist");
			}
			
			if ($options['blacklist'] && $blackCheck && !$minimal){
				$blackList = \FreePBX::Blacklist()->getBlacklist();
				$total=count($blackList);
				if ($total > 1){
					$blackDest = \FreePBX::Blacklist()->destinationGet();
					$blockUnknown = \FreePBX::Blacklist()->blockunknownGet();
					$block = $blockUnknown ? _('Yes') : _('No');
					$tooltip="\n"._('Block Unknown/Blocked Caller ID').": ".$block;
					$tooltip.="\n\n"._('Number: Description')."\n";
					
					$i=0;
					
					foreach ($blackList as $b){
						if ($b['number']=='dest'){continue;}
						if ($b['description']==1){$b['description']='';}
						$tooltip.=$b['number'].": ".sanitizeLabels($b['description'])."\n";
						
						if ($i >= 25 && $i < $total - 1){
							$tooltip.="...\n". ($total - 25) ." "._('additional entries');
							break;
						}
						$i++;
					}
					
					$edgeLabel=" "._('Blacklist');
					$route['parent_edge_code']='edgelink';
					$route['parent_edge_label'] = " "._('Disallowed by Blacklist');
					$route['parent_edge_url'] = htmlentities('/admin/config.php?display=blacklist');
					$route['parent_edge_target'] = '_blank';
					$route['parent_edge_labeltooltip']=" "._('Click to edit Blacklist')."\n".$tooltip;
					$route['parent_node'] = $node;
					if ($blackDest){
						dpp_follow_destinations($route, $blackDest.','.$numLang,'',$options);
					}else{
						dpp_follow_destinations($route, 'blacklistnotset','',$options);
					}
				}																															
			}
			
			if ($options['allowlist']){ //--allowlist 
				
				$checkAModule=\FreePBX::Modules()->checkStatus("allowlist");
				if ($checkAModule){
					if ($num=='ANY'){$allowNum='';}else{$allowNum=$num;}
					$allowCheck = \FreePBX::Allowlist()->didIsSet($allowNum, $numcid);
					$allowList = \FreePBX::Allowlist()->getAllowlist();
				}else{
					$allowCheck = false;
				}
			}
			
			if ($options['allowlist'] && $allowCheck && !empty($allowList)){
				$allowDest = \FreePBX::Allowlist()->destinationGet();

				$tooltip="\n"._('Number: Description')."\n";
				$i=0;
				$total=count($allowList);
				foreach ($allowList as $a){
					if ($a['description']==1){$a['description']='';}
					$tooltip.=$a['number'].": ".sanitizeLabels($a['description'])."\n";
					
					if ($i >= 25 && $i < $total - 1){
						$tooltip.="...\n ". ($total - 25) ." "._('additional entries');
						break;
					}
					$i++;
				}
					
				$edgeLabel=" "._('Allowlist');
				$route['parent_edge_code']='edgelink';
				$route['parent_edge_label'] =" "._('Disallowed by Allowlist');
				$route['parent_edge_url'] = htmlentities('/admin/config.php?display=allowlist');
				$route['parent_edge_target'] = '_blank';
				$route['parent_edge_labeltooltip']=" "._('Click to edit Allowlist')."\n";
				
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $allowDest.','.$numLang,'',$options);
			
			}else{
				$edgeLabel=" "._('Always');
			}
			
			if ($options['allowlist'] && $allowCheck && !empty($allowList)){
				$route['parent_edge_code']='edgelink';
				$route['parent_edge_url'] = htmlentities('/admin/config.php?display=allowlist');
				$route['parent_edge_target'] = '_blank';
				$route['parent_edge_labeltooltip']=" "._('Click to edit Allowlist')."\n".$tooltip;
			}
			
			$route['parent_edge_label']= $edgeLabel;
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $incoming['destination'].','.$numLang,'',$options);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Inbound Routes

		#
		# Announcements
		#
  }elseif (preg_match("/^app-announcement-(\d+),s,(\d+),(.+)/", $destination, $matches)) {
    $module  = 'Announcement';
    $annum   = $matches[1];   // announcement id
    $another = $matches[2];   // sequence or "s" step
    $anlang  = $matches[3];   // language

    // Lazy load the announcement row
    $an = lazyLoadRow($route, 'announcements', $annum);
		
    if ($an) {
			$recID=$an['recording_id'];
		
			if ($recID) {
				$recording = lazyLoadRow($route, 'recordings', $recID);

				if ($recording) {
					$recordingId=$recording['id'];
					$featureCode= getFeatureNum($recordingId,$route);
					$announcement= "\n🔊 "._('Recording').": ". sanitizeLabels($recording['displayname']) ." (" . $anlang . ")\n"._('Feature Code').": ".$featureCode;
				}else{
					$announcement="\n"._('Recording').": "._('None');
				}
			}else{
				$announcement="\n"._('Recording').": "._('None');
			}
		
			$label=sanitizeLabels($an['description']).$announcement;
			$tooltip='';
			makeNode($module,$annum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($an['post_dest'] != '') {
				$route['parent_edge_label'] = " "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $an['post_dest'].','.$anlang,'',$options);
			}
			
		}else{
			notFound($module,$destination,$node);
		}
		# end of announcements

		#
		# Time Conditions
		#
  }elseif (preg_match("/^timeconditions,(\d+),(\d+),(.+)/", $destination, $matches)) {
    $module   = "Time Conditions";
    $routetable = "timeconditions";
    $tcnum    = $matches[1];
    $tcother  = $matches[2];
    $tcLang   = $matches[3];

    // Lazy load only this timecondition
    $tc = lazyLoadRow($route, $routetable, $tcnum);

    if ($tc) {
			if (!isset($tc['mode'])){$tc['mode']='time-group';}
			
			$tcTooltip=$tc['displayname']."\n"._('Mode').": ".$tc['mode']."\n";
			if (!empty($tc['timezone'])){
				$tcTooltip.= ($tc['timezone'] !== 'default') ? _('Timezone').": " . $tc['timezone'] : '';
			}
			$label=sanitizeLabels($tc['displayname']);
			$tooltip=sanitizeLabels($tcTooltip);
			
			makeNode($module,$tcnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}

			//TC modes
			if ($tc['mode'] === 'time-group') {
				$tg = lazyLoadRow($route, 'timegroups', $tc['time']);
				$tgnum  = $tg['id'];
				$tgname = sanitizeLabels($tg['description']);
				$tgtime = !empty($tg['time']) ? $tg['time'] : "No times defined";
				$tgLabel= $tgname."\n".$tgtime;
				$tgLink = '/admin/config.php?display=timegroups&view=form&extdisplay='.$tgnum;
				$tgTooltip= $tgLabel;
			} elseif ($tc['mode'] === 'calendar-group') {
				if (!empty($tc['calendar_id'])) {
						$cal = lazyLoadRow($route, 'calendar', $tc['calendar_id']);
						if (!empty($cal)) {
								$tgLabel = $cal['name'];
								$tgLink  = '/admin/config.php?display=calendar&action=view&type=calendar&id='.$tc['calendar_id'];
								$tz      = !empty($cal['timezone']) ? $cal['timezone'] : '';
								$tgTooltip = _('Name').": ".$cal['name']."\n"
													 . _('Description').": ".$cal['description']."\n"
													 . _('Type').": ".$cal['type']."\n"
													 . _('Timezone').": ".$tz;
						}
				} elseif (!empty($tc['calendar_group_id'])) {
						$cal = lazyLoadRow($route, 'calendar', $tc['calendar_group_id']);
						if (!empty($cal)) {
								$tgLabel = $cal['name'];
								$tgLink  = '/admin/config.php?display=calendargroups&action=edit&id='.$tc['calendar_group_id'];

								$calNames = _('Calendars').": ";
								if (!empty($cal['calendars'])) {
										foreach ($cal['calendars'] as $c) {
												$cinfo = lazyLoadRow($route, 'calendar', $c);
												$calNames .= $cinfo['name']."\n";
										}
								}

								$cats = !empty($cal['categories']) ? count($cal['categories']) : _('All');
								$categories = 'Categories= '.$cats;

								$eves = !empty($cal['events']) ? count($cal['events']) : _('All');
								$events = _('Events').": ".$eves;

								$expand = !empty($cal['expand']) ? 'true' : 'false';

								$tgTooltip = _('Name').": ".$cal['name']."\n"
													 . $calNames."\n"
													 . $categories."\n"
													 . $events."\n"
													 . _('Expand').": ".$expand;
						}
				}
			}
			
			# Now set the current node to be the parent and recurse on both the true and false branches
			$route['parent_edge_label'] = " "._('Match').": ".$tgLabel;
			$route['parent_edge_url'] = htmlentities($tgLink);
			$route['parent_edge_target'] = '_blank';
			$route['parent_edge_code']='edgelink';
			$route['parent_edge_labeltooltip']=" "._('Match').": ".$tgTooltip;
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $tc['truegoto'].','.$tcLang,'',$options);
			
			
			$route['parent_edge_code']='edgelink';
			$route['parent_edge_label'] = " "._('No Match');
			$route['parent_edge_url'] = htmlentities($tgLink);
			$route['parent_edge_target'] = '_blank';
			$route['parent_edge_labeltooltip']=" "._('No Match').": ".$tgTooltip;
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $tc['falsegoto'].','.$tcLang,'',$options);
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of Time Conditions

		#
		# Dynamic Routes
		#
  } elseif (preg_match("/^dynroute-(\d+),([a-z]),(\d+),(.+)/", $destination, $matches)) {
		$module="Dyn Route";
		$dynnum = $matches[1];
		$dynLang = $matches[4];
		
		$dynrt = lazyLoadRow($route, 'dynroute', $dynnum);

		if (!empty($dynrt)) {
			
			$recID=$dynrt['announcement_id'];
			if ($recID) {
				$recording = lazyLoadRow($route, 'recordings', $recID);

				if ($recording) {
					$dynRecName= $recording['displayname'];
					$recordingId=$recording['id'];
					$featureCode= getFeatureNum($recordingId,$route);
					$dynRecName= "🔊 "._('Announcement')." (".$dynLang."): ".sanitizeLabels($dynRecName)."\n"._('Feature Code').": ".$featureCode;
				}else{
					$dynRecName=_('Announcement').': '._('None');
				}
			}else{
				$dynRecName=_('Announcement').': '._('None');
			}
			
			$label=sanitizeLabels($dynrt['name'])."\n".$dynRecName;
			$tooltip='';
			makeNode($module,$dynnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if (!empty($dynrt['routes'])){
				ksort($dynrt['routes']);
				foreach ($dynrt['routes'] as $selid => $ent) {
					$desc = isset($ent['description']) ? $ent['description'] : '';
					
					$route['parent_edge_label']= " "._('Match').": ".sanitizeLabels($ent['selection'])."\n".$desc;
					$route['parent_node'] = $node;
					dpp_follow_destinations($route, $ent['dest'].','.$dynLang,'',$options);
				}
			}
			
			//are the invalid and default destinations the same?
			if ($dynrt['invalid_dest'] != '' && $dynrt['invalid_dest']==$dynrt['default_dest']){
				 $route['parent_edge_label']= " ".sprintf(_('Invalid Input, Default (%s) secs'), $dynrt['timeout']);
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $dynrt['invalid_dest'].','.$dynLang,'',$options);
			}else{
				if ($dynrt['invalid_dest'] != '') {
					$route['parent_node'] = $node;
					$route['parent_edge_label']= " "._('Invalid Input');
					dpp_follow_destinations($route, $dynrt['invalid_dest'].','.$dynLang,'',$options);
				}
				if ($dynrt['default_dest'] != '') {
					$route['parent_node'] = $node;
					$route['parent_edge_label']= " ". sprintf(_('Default (%s) secs'), $dynrt['timeout']);
					dpp_follow_destinations($route, $dynrt['default_dest'].','.$dynLang,'',$options);
				}
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Dynamic Routes

		#
		# MISC Destinations
		#
  } elseif (preg_match("/^ext-miscdests,(\d+),(\d+)/", $destination, $matches)) {
		$module="Misc Dests";
		$miscdestnum = $matches[1];
		$miscdestother = $matches[2];

		$miscdest = lazyLoadRow($route, 'miscdest', $miscdestnum);

		if (!empty($miscdest)) {
			$label=sanitizeLabels($miscdest['description']).' ('.$miscdest['destdial'].')';
			$tooltip='';
			makeNode($module,$miscdestnum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of MISC Destinations

		#
		# Blackhole
		#
  } elseif (preg_match("/^app-blackhole,(hangup|congestion|busy|zapateller|musiconhold|ring|no-service),(\d+)/", $destination, $matches)) {
		$blackholetype = $matches[1];
		
		$translatedMap = array(
			'musiconhold' => _('Music On Hold'),
			'ring'        => _('Play Ringtones'),
			'no-service'  => _('Play No Service Message'),
			'busy'        => _('Busy'),
			'hangup'      => _('Hang Up'),
			'congestion'  => _('Congestion'),
			'zapateller'  => 'Zapateller',
		);
		
		$blackholeother = $matches[2];
		$previousURL=$route['parent_node']->getAttribute('URL', '');

		$node->attribute('label', _('Terminate Call').': '.$translatedMap[$blackholetype]);
		$node->attribute('tooltip', _('Terminate Call').': '.$translatedMap[$blackholetype]);
		$node->attribute('URL', $previousURL);
    $node->attribute('target', '_blank');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', 'orangered');
		$node->attribute('style', 'rounded, filled');
		
		#end of Blackhole

		#
		# Call Flow Control (daynight)
		#
  } elseif (preg_match("/^app-daynight,(\d+),(\d+),(.+)/", $destination, $matches)) {
    $module = "Call Flow";
    $daynightnum   = $matches[1];
    $daynightother = $matches[2];
    $daynightLang  = $matches[3];

    // Lazy load the call flow record
    $daynight = lazyLoadRow($route, 'daynight', $daynightnum);

    if ($daynight) {
			$daynight = array_reverse($daynight);
			
			#feature code exist?
			$fcKey   = '*28' . $daynightnum;
			$feature = lazyLoadRow($route, 'featurecodes', $fcKey);

			if ($feature) {
					// Use custom if set, otherwise default
					$featurenum = !empty($feature['customcode'])
							? $feature['customcode']
							: $feature['defaultcode'];

					// Append (disabled) if not enabled
					if ($feature['enabled'] == '1') {
							$code = $featurenum;
					} else {
							$code = $featurenum . ' (' . _('Disabled') . ')';
					}
			} else {
					$code = '';
			}
			$tooltip='';
			#check current status and set path to active
			list($dactive, $nactive) = getDayNightStatus($daynightnum);
			
			$daynightList = array();

			foreach ($daynight as $d) {
					switch ($d['dmode']) {
							case 'fc_description':
									$label = sanitizeLabels($d['dest']) . "\n" . _('Feature Code') . ": " . $code;
									break;

							case 'night':
									$daynightList[] = array(
											'label'  => _('Night Mode'),
											'dest'   => $d['dest'],
											'active' => $nactive
									);
									break;

							case 'day':
									$daynightList[] = array(
											'label'  => _('Day Mode'),
											'dest'   => $d['dest'],
											'active' => $dactive
									);
									break;
					}
			}

			makeNode($module,$daynightnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}

			foreach ($daynightList as $dl){
				$route['parent_edge_label'] = " ". $dl['label'] . ' ' . $dl['active'];
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $dl['dest'].','.$daynightLang,'',$options);
			}

		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Flow Control (daynight)

		#
		# Callback
		#
  } elseif (preg_match("/^callback,(\d+),(\d+),(.+)/", $destination, $matches)) {
    $module = "Callback";
    $callbackId   = $matches[1];
    $callrecOther = $matches[2];
    $callbackLang = $matches[3];

    $callback = lazyLoadRow($route, 'callback', $callbackId);

    if ($callback) {
			
			$label=sanitizeLabels($callback['description']);
			$tooltip=sanitizeLabels($callback['description'])."\n".
				_('Callback Number').": ".$callback['callbacknum']."\n".
				_('Delay Before Callback').": ".$callback['sleep']."\n".
				_('Caller ID').": ".sanitizeLabels($callback['callerid'])."\n".
				_('Timeout').": ".$callback['timeout'];
			
			
			makeNode($module,$callbackId,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			$route['parent_edge_label']= _('Destination after Callback');
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $callback['destination'].','.$callbackLang,'',$options);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Recording
		#
		
		#
		# Call Recording
		#
  } elseif (preg_match("/^ext-callrecording,(\d+),(\d+),(.+)/", $destination, $matches)) {
    $module = "Call Recording";
    $callrecID    = $matches[1];
    $callrecOther = $matches[2];
    $callLang     = $matches[3];

    $callRec = lazyLoadRow($route, 'callrecording', $callrecID);

    if ($callRec) {
			$callMode= ucfirst($callRec['callrecording_mode']);
			$callMode = str_replace("Dontcare", _('Don\'t Care'), $callMode);
			$label=sanitizeLabels($callRec['description'])."\n"._('Mode').": ".$callMode;
			$tooltip='';
			
			makeNode($module,$callrecID,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			$route['parent_edge_label']= " "._('Continue');
			$route['parent_node'] = $node;
			dpp_follow_destinations($route, $callRec['dest'].','.$callLang,'',$options);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Call Recording
		#
		
		# Conferences (meetme)
		#
  } elseif (preg_match("/^ext-meetme,(\d+),(\d+)/", $destination, $matches)) {
		$module="Conferences";
		$meetmenum = $matches[1];
		$meetmeother = $matches[2];
		
		$meetme = lazyLoadRow($route, 'meetme', $meetmenum);

    if ($meetme) {
			$label = $meetme['exten']."\n".sanitizeLabels($meetme['description']);
			$tooltip='';
			makeNode($module,$meetmenum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Conferences (meetme)

		#
		# Directory
		#
  } elseif (preg_match("/^directory,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Directory";
		$directorynum = $matches[1];
		$directoryother = $matches[2];
		$directoryLang = $matches[3];
		
		$directory = lazyLoadRow($route, 'directory', $directorynum);

    if ($directory) {
			$label=sanitizeLabels($directory['dirname']);
			$tooltip='';
			makeNode($module,$directorynum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($directory['invalid_destination']!=''){
				 $route['parent_edge_label']= " "._('Invalid Input');
				 $route['parent_node'] = $node;
				 dpp_follow_destinations($route, $directory['invalid_destination'].','.$directoryLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Directory

		#
		# DISA
		#
  } elseif (preg_match("/^disa,(\d+),(\d+)/", $destination, $matches)) {
    $module    = "DISA";
    $disanum   = $matches[1];
    $disaother = $matches[2];

    // Lazy load DISA row
    $disa = lazyLoadRow($route, 'disa', $disanum);

    if ($disa) {
			$label=sanitizeLabels($disa['displayname']);
			$tooltip='';
			makeNode($module,$disanum,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of DISA

		#
		# Feature Codes
		#
  } elseif (preg_match("/^ext-featurecodes,(\*?\d+),(\d+)/", $destination, $matches)) {
    $module       = "Feature Code";
    $featurenum   = $matches[1]; // dialed number, e.g. *29123
    $featureother = $matches[2];

    // Try lazy load first
    $feature = lazyLoadRow($route, 'featurecodes', $featurenum);

    if ($feature) {
        // Always display the *effective* number (custom if set)
        $displaynum = !empty($feature['customcode']) ? $feature['customcode'] : $feature['defaultcode'];
        $label      = sanitizeLabels($feature['description']) . " <" . $displaynum . ">";
        $tooltip    = '';

        makeNode($module, '', $label, $tooltip, $node);
    } else {
        notFound($module, $destination, $node);
    }
		#end of Feature Codes

		#
		# Languages
		#
  } elseif (preg_match("/^app-languages,(\d+),(\d+)/", $destination, $matches)) {
		$module="Languages";
		$langnum = $matches[1];
		$langother = $matches[2];
		$langArray = lazyLoadRow($route, 'languages', $langnum);

		if (!empty($langArray)) {
			$label=sanitizeLabels($langArray['description']);
			$tooltip='';
			makeNode($module,$langnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			if ($langArray['dest'] != '') {
				$route['parent_edge_label'] =" "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $langArray['dest'].','.$langArray['lang_code'],'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Languages

		#
		# MISC Applications
		#
  } elseif (preg_match("/^miscapps,(\d+),([a-z]+),(\d+),(.+)/", $destination, $matches)) {
		$module="Misc Apps";
		$miscappsnum = $matches[1];
		$miscappsLang = $matches[4];
		
		$miscapps = lazyLoadRow($route, 'miscapps', $miscappsnum);

		if (!empty($miscapps)) {
			$enabled = isMiscAppEnabled($miscapps['ext']) ? '' : '(disabled)';
			
			$label=sanitizeLabels($miscapps['description']).' ('.$miscapps['ext'].') '.$enabled;
			$tooltip='';
			makeNode($module,$miscappsnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($miscapps['dest'] != '') {
				$route['parent_edge_label'] =" "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $miscapps['dest'].','.$miscappsLang, '',$options);
			}
		
		}else{
			notFound($module,$destination,$node);
		}
		#end of MISC Applications

		#
		# Page Group
		#
  } elseif (preg_match("/^app-pagegroups,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Paging";
		$pagenum = $matches[1];
		$pageLang = $matches[3];
		
		$paging = lazyLoadRow($route, 'paging', $pagenum);

		if (!empty($paging)) {
			$recID=$paging['announcement'];
			if ($recID) {
				$recording = lazyLoadRow($route, 'recordings', $recID);

				if ($recording) {
					$pageRecName= $recording['displayname'];
					$recordingId=$recording['id'];
					$featureCode= getFeatureNum($recordingId,$route);
					$pageRecName= "\n🔊 "._('Announcement')." (".$pageLang."): ".sanitizeLabels($pageRecName)."\n"._('Feature Code').": ".$featureCode;
				}else{
					$pageRecName="\n"._('Announcement').": ".ucfirst($recID);
				}
			}else{
				$pageRecName="\n"._('Announcement').": ".ucfirst($recID);
			}
			
			$busyArray=array(_('Skip'),_('Force'),_('Whisper'));
			$duplexArray=array(_('No'),_('Yes'));
			$label=$paging['page_group']." ".sanitizeLabels($paging['description']).$pageRecName;
			$tooltip=_('Page Group').": ".$paging['page_group']."\n"._('Description').": ".sanitizeLabels($paging['description'])."\n".$pageRecName."\n"._('Busy Extensions').": ".$busyArray[$paging['force_page']]."\n"._('Duplex').": ".$duplexArray[$paging['duplex']];
			makeNode($module,$pagenum,$label,$tooltip,$node);
			
			if (!empty($paging['members']) && !$minimal){
				$line="Page Group ".$pagenum." "._('members').":\n";
				foreach ($paging['members'] as $member) {
					// Make sure extension is loaded/hydrated
					$extension = hydrateExtension($route, $member);

					if ($extension) {
							$isRegistered = !empty($extension['reg_status']);
							$regstatus    = $isRegistered ? '🟢' : '🔴';

							$line .= $regstatus." "._('Ext')." ".$member." ".sanitizeLabels($extension['name'])."\l";

					} else {
							$line .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$member."\l";
					}

				}
				
				$memNode= $dpgraph->beginNode('pagemem'.$pagenum,
					array(
						'label' => $line,
						'tooltip' => $line,
						'URL' => $node->getAttribute('URL', ''),
						'target' => '_blank',
						'shape' => 'rect',
						'style' => 'rounded,filled',
						'fillcolor' => '#87CEFA'
					)
				);
				$edge= $dpgraph->beginEdge(array($node, $memNode));
			}
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of Page Group
		
		#
		# Phonebook
		#
  } elseif (preg_match("/^app-pbdirectory,pbdirectory,1,(.+)/", $destination, $matches)) {
		$module="Phonebook";
		$label="Asterisk";
		$tooltip="";
		makeNode($module,'',$label,$tooltip,$node);
		#end of Phonebook
		
		# Play Recording
		#
  } elseif (preg_match("/^play-system-recording,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="System Recording";
		$recID = $matches[1];
		$recOther = $matches[2];
		$recLang = $matches[3];
		
		$recording = lazyLoadRow($route, 'recordings', $recID);

		if ($recording) {
			$playName=$recording['displayname'];
			$featureCode= getFeatureNum($recID,$route);
			$playName= $playName."\nFeature Code: ".$featureCode;
			
			$label = "🔊 ". _('Recording') . " (" . $recLang . "): " . sanitizeLabels($playName);

			$node->attribute('label', $label);
			$node->attribute('tooltip', $node->getAttribute('label'));
			$node->attribute('URL', '#');
			$node->attribute('shape', 'rect');
			$node->attribute('fillcolor', $pastels[16]);
			$node->attribute('style', 'rounded,filled');
		}else{
			notFound($module,$destination,$node);
		}
		#end of Play Recording
		
		#
		# Queue Priorities
		#
  }elseif (preg_match("/^app-queueprio,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Queue Priorities";
		$queueprioID = $matches[1];
		$queueprioIDOther = $matches[2];
		$queuepriorLang= $matches[3];
		
		$queueprio = lazyLoadRow($route, 'queueprio', $queueprioID);

		if (!empty($queueprio)) {
			$label=sanitizeLabels($queueprio['description']."\n"._('Priority').": ".$queueprio['queue_priority']);
			$tooltip='';
			makeNode($module,$queueprioID,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($queueprio['dest'] != '') {
				$route['parent_edge_label'] =" "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $queueprio['dest'].','.$queuepriorLang, '',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Queue Priorities
		
		#
		# Queue Callback
		#
	}  elseif (preg_match("/^queuecallback-(\d+),(.+),(\d+),(.+)/", $destination, $matches)) {
    $module       = "Queue Callback";
    $qcallbackId  = $matches[1];
    $qcallbackLang= $matches[4];

    // Lazy load this callback
    $qcallback = lazyLoadRow($route, 'queuecallback', $qcallbackId);

    if ($qcallback) {
			$recID=$qcallback['announcement'];
			
			if (empty($qcallback['cbqueue'])){
				$queue= $route['parent_node']->getId();
			}elseif (substr($qcallback['cbqueue'], 0, 1) === 'q') {
				$queue = "ext-queues,".substr($qcallback['cbqueue'], 1).",1,".$qcallbackLang;
			}else{
				$queue = "ext-vqueues,".substr($qcallback['cbqueue'], 1).",1,".$qcallbackLang;
			}
			
			if ($recID) {
				$recording = lazyLoadRow($route, 'recordings', $recID);

				if ($recording) {
					$qcbRecName= $recording['displayname'];
					$recordingId=$recording['id'];
					$featureCode= getFeatureNum($recordingId,$route);
					$qcbRecName= "🔊 "._('Announcement')." (".$qcallbackLang."): ".sanitizeLabels($qcbRecName)."\n"._('Feature Code').": ".$featureCode;
				}else{
					$qcbRecName=_('Announcement').': '._('Default');
				}
			}else{
				$qcbRecName=_('Announcement').': '._('Default');
			}
			
			$label=sanitizeLabels($qcallback['name'])."\n".$qcbRecName;
			$tooltip = "Caller ID: ".$qcallback['cid']."\n"._('Timeout').": ".secondsToTimes($qcallback['timeout'])."\n"._('Retries').": ".$qcallback['retries']."\n"._('Retry Delay').": ".secondsToTimes($qcallback['retrydelay']);
			
			makeNode($module,$qcallbackId,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			$route['parent_node'] = $node;
			$route['parent_edge_label'] = " "._('Callback Queue');
			dpp_follow_destinations($route,$queue,'',$options);
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of Queue Callback

		#
		# Set CID
		#
  } elseif (preg_match("/^app-setcid,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="Set CID";
		$cidnum = $matches[1];
		$cidother = $matches[2];
		$cidLang = $matches[3];
		
		$cid = lazyLoadRow($route, 'setcid', $cidnum);

		if (!empty($cid)) {
			$label= sanitizeLabels($cid['description'])." ".sanitizeLabels("\nName= ".preg_replace('/\${CALLERID\(name\)}/i', '<name>', $cid['cid_name'])."\nNumber= ".preg_replace('/\${CALLERID\(num\)}/i', '<number>', $cid['cid_num']));
			$tooltip='';
			makeNode($module,$cidnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($cid['dest'] != '') {
				$route['parent_edge_label'] = " "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $cid['dest'].','.$cidLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of Set CID
		
		#
		# TTS
		#
  } elseif (preg_match("/^ext-tts,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="TTS";
		$ttsnum = $matches[1];
		$ttsother = $matches[2];
		$ttsLang= $matches[3];
		$tts = lazyLoadRow($route, 'tts', $ttsnum);

		if (!empty($tts)) {
			$label= sanitizeLabels($tts['name']);
			$tooltip = _('Engine').": ".$tts['engine']."\n"._('Description').": ".$tts['text'];
			makeNode($module,$ttsnum,$label,$tooltip,$node);
			if ($stop){
				$undoNode= stopNode($dpgraph,$destination);
				$edge= $dpgraph->beginEdge(array($node, $undoNode));
				$edge->attribute('style', 'dashed');
				$edge->attribute('edgetooltip',$node->getAttribute('label', ''));
				
				return;
			}
			
			if ($tts['goto'] != '') {
				$route['parent_edge_label'] = " "._('Continue');
				$route['parent_node'] = $node;
				dpp_follow_destinations($route, $tts['goto'].','.$ttsLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		#end of TTS
		
		#
		# Trunks
		#
  } elseif (preg_match("/^ext-trunk,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module='Trunks';
		$trunkId= $matches[1];
		$trunkOther = $matches[2];
		$trunkLang = $matches[3];
		
		$trunk = lazyLoadRow($route, 'trunks', $trunkId);

		if (!empty($trunk)) {
			$status = ($trunk['disabled'] == 'off') ? "Enabled" : "Disabled";
			$continue = ($trunk['continue'] == 'on') ? _('Yes') : _('No');
			$busy=$trunk['continue'];
			$cidArray=array("off"=>"Allow Any CID","on"=>"Block Foreign CID","cnum"=>"Remove CNAM","all"=>"Force Trunk CID");
			$modId=$trunk['tech'].','.$trunkId;
			
			$label=sanitizeLabels($trunk['name'])." (Status: ".$status.")\lCallerID: ".sanitizeLabels($trunk['outcid']);
			$tooltip="Name: ".sanitizeLabels($trunk['name'])."\n"._('Tech').": ".$trunk['tech']."\n"._('Outbound CallerID').": ".sanitizeLabels($trunk['outcid'])."\n"._('Status').": ".$status."\n"._('CID Options').": ".$cidArray[$trunk['keepcid']]."\n"._('Max Channels').": ".$trunk['maxchans']."\n"._('Continue If Busy').": ".$continue;
			$node->attribute('width', 2);
			$node->attribute('margin','.13');
			makeNode($module,$modId,$label,$tooltip,$node);
		}else{
			notFound($module,$destination,$node);
		}
		#end of Trunks

		#
		# VM Blast + members
		#
  } elseif (preg_match("/^vmblast\-grp,(\d+),(\d+),(.+)/", $destination, $matches)) {
		$module="VM Blast";
		$vmblastnum = $matches[1];
		$vmblastother = $matches[2];
		$vmblastLang= $matches[3];
		
		$vmblast = lazyLoadRow($route, 'vmblasts', $vmblastnum);

		if (!empty($vmblast)) {
			$recID = $vmblast['audio_label'];
			if ($recID > 0) {
					$recording = lazyLoadRow($route, 'recordings', $recID);

					if ($recording) {
							$vmRecName   = $recording['displayname'];
							$recordingId = $recording['id'];
							$featureCode = getFeatureNum($recordingId, $route);

							$vmRecName = "🔊 "._('Audio Label')." (".$vmblastLang."): "
									. sanitizeLabels($vmRecName)."\n"
									. _('Feature Code').": ".$featureCode;
					} else {
							$vmRecName = _('Audio Label').': '._('None');
					}

			} elseif ($recID == '-1') {
					$vmRecName = _('Audio Label').": "._('Read Group Number');

			} elseif ($recID == '-2') {
					$vmRecName = _('Audio Label').": "._('Beep Only - No Confirmation');
			}

			
			$label=$vmblastnum." ".sanitizeLabels($vmblast['description'])."\n".$vmRecName;
			if ($vmblast['password'] !=''){$pass="\nPassword: ".$vmblast['password'];}else{$pass='';}
			$tooltip=$module.": ".$label.$pass;
			makeNode($module,$vmblastnum,$label,$tooltip,$node);
			
			if (!empty($vmblast['members']) && !$minimal){
				$line="Voicemail Blast ".$vmblastnum." "._('members').":\n";
				foreach ($vmblast['members'] as $member) {
					// Make sure extension is loaded/hydrated
					$extension = hydrateExtension($route, $member);

					if ($extension) {
							$line .= _('Ext')." ".$member." ".sanitizeLabels($extension['name']).": ";

							$vmblastemail = '';
							if (!empty($extension['mailbox']) && isset($extension['mailbox']['email'])) {
									$vmblastemail = $extension['mailbox']['email'];
							}

							$line .= str_replace(",", ",\n", $vmblastemail)."\l";

					} else {
							// fallback if extension not found
							$line .= _('Ext')." ".$member."\l";
					}
				}
				
				$memNode= $dpgraph->beginNode('vmblastmem'.$vmblastnum,
					array(
						'label' => $line,
						'tooltip' => $line,
						'URL' => $node->getAttribute('URL', ''),
						'target' => '_blank',
						'shape' => 'rect',
						'style' => 'rounded,filled',
						'fillcolor' => 'gainsboro'
					)
				);
				$edge= $dpgraph->beginEdge(array($node, $memNode));
			}
			
		}else{
			notFound($module,$destination,$node);
		}
		#end of VM Blast + members
		
		#
		# Custom Destinations (with return)
		#
	} elseif (preg_match("/^customdests,dest-(.+),(\d+),(.+)/", $destination, $matches)) {
		$module="Custom Dests";
		$custId=$matches[1];
		$custLang=$matches[3];
		
		if (isset($route['customapps'][$custId])){
			$custDest=$route['customapps'][$custId];
			$custReturn = ($custDest['destret'] == 1) ? _('Yes') : _('No');
			
			$target = $custDest['target'];
			list($context) = explode(',', $target);

			// Get dialplan snippet
			$dpLines = runAstmanCommand("dialplan show $context");

			// Remove "Privilege: Command" line if present
			if (!empty($dpLines) && strpos($dpLines[0], 'Privilege:') === 0) {
					array_shift($dpLines);
			}
			
			$dpText = $dpLines ? implode("\n", $dpLines) : _("No dialplan found");
			
			$label= sanitizeLabels($custDest['description'])."\n"
				. _('Target') . ": " . $target."\l"
				. _('Return') . ": " . $custReturn."\l";
			
			$tooltip =  _('Description') . ": " . sanitizeLabels($custDest['description']) . "\n" 
				. _('Target') . ": " . $target . "\n" 
				. _('Notes') . ": " . sanitizeLabels($custDest['notes']) . "\n" 
				. _('Return') . ": " . $custReturn
				. "\n---\n" . sanitizeLabels($dpText)
			;

			makeNode($module,$custId,$label,$tooltip,$node);
			
			if ($custDest['destret']){
				$route['parent_edge_label']=" "._('Return');
				$route['parent_node'] = $node;
				
				dpp_follow_destinations($route, $custDest['dest'].','.$custLang,'',$options);
			}
		}else{
			notFound($module,$destination,$node);
		}
		
		#
		# blacklistnotset
		#
	} elseif (preg_match("/^blacklistnotset/", $destination)) {
		$node->attribute('label',_('Bad Dest: Blacklist'));
		$node->attribute('tooltip', $node->getAttribute('label'));
		$node->attribute('URL', htmlentities('/admin/config.php?display=blacklist'));
		$node->attribute('target','_blank');
		$node->attribute('shape', 'rect');
		$node->attribute('fillcolor', 'orangered');
		$node->attribute('style', 'rounded,filled');
		#end of blacklistnotset
		
		
		#preg_match not found
		
	}else {
	
		if (!empty($route['customapps'])){
			#custom destinations
			
			foreach ($route['customapps'] as $entry) {
				if (preg_match('/(,[^,]+)$/', $destination, $matches)) {
					$destLang = $matches[1]; // This will be ",en"
				}
				$destNoLang= preg_replace('/,[^,]+$/', '', $destination);

				if ($entry['target']=== $destNoLang) {
					$custDest=$entry;
					$custDest['lang']=$destLang;
					break;
				}
			}
			#end of Custom Destinations (with return)
		}
		
		if (!empty($custDest)){
			
			if (isset($custDest['destid'])) {
				$module = "Custom Dests";
				$custId = $custDest['destid'];
				$custReturn = ($custDest['destret'] == 1) ? _('Yes') : _('No');

				$target = $custDest['target'];
				list($context) = explode(',', $target);

				// Get dialplan snippet
				$dpLines = runAstmanCommand("dialplan show $context");

				// Remove "Privilege: Command" line if present
				if (!empty($dpLines) && strpos($dpLines[0], 'Privilege:') === 0) {
						array_shift($dpLines);
				}

				$dpText = $dpLines ? implode("\n", $dpLines) : _("No dialplan found");

				$label = sanitizeLabels($custDest['description'])	. "\n" 
				. _('Target') . ": " . $target . "\l"
				. _('Return') . ": " . $custReturn . "\l";

				$tooltip = _('Description') . ": " . sanitizeLabels($custDest['description']) . "\n" 
						. _('Target') . ": " . $target . "\n" 
						. _('Notes') . ": " . sanitizeLabels($custDest['notes']) . "\n" 
						. _('Return') . ": " . $custReturn
						. "\n---\n" . sanitizeLabels($dpText);

				makeNode($module, $custId, $label, $tooltip, $node);

				if ($custDest['destret']) {
						$route['parent_edge_label'] = " "._('Return');
						$route['parent_node'] = $node;
						dpp_follow_destinations($route, $custDest['dest'].$custDest['lang'], '', $options);
				}
		}else{
				notFound($module,$destination,$node);
			}
		}else{
			dpplog(1, "Unknown destination type: $destination");
			$node->attribute('fillcolor', $pastels[12]);
			$node->attribute('label', sanitizeLabels($destination));
			$node->attribute('shape', 'rect');
			$node->attribute('style', 'rounded,filled');
    }
  }
}

# Load Custom Destinations (special case: must eager load)
function dpp_load_tables(&$dproute) {
    global $db;

    $table = 'kvstore_FreePBX_modules_Customappsreg';

    // Check if the table exists
    $tableExists = $db->getOne("SHOW TABLES LIKE " . q($table));
    if (!$tableExists) {
        dpplog(9, "Skipping load: table $table does not exist");
        return;
    }

    $query = "SELECT * FROM $table";
    $results = $db->getAll($query, DB_FETCHMODE_ASSOC);

    if (DB::IsError($results)) {
        dpplog(9, "Error selecting from $table: " . $results->getMessage());
        return;
    }

    foreach ($results as $row) {
        if (is_numeric($row['key'])) {
            $id  = $row['key'];
            $val = json_decode($row['val'], true);
            if (is_array($val)) {
                $dproute['customapps'][$id] = $val;
                dpplog(9, "customapps=$id");
            }
        }
    }
}
# END load Custom Destinations
