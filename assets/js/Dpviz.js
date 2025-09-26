$(document).ready(function() {
	//github update check
	$('#check-update-btn').click(function() {
		$('#update-result').html(`<div style="margin-top: 10px;">${translations.checking}</div>`);

		$.ajax({
			url: 'ajax.php?module=dpviz&command=check_update',
			method: 'POST',
			dataType: 'json',
			
			success: function(response) {
				if (response.status === 'success') {
					if (response.up_to_date) {
						$('#update-result').html(`<div style="margin-top: 10px;">${translations.uptodate}</div>`);
					} else {
						$('#update-result').html(
							`<a href="config.php?display=modules" target="_blank" class="btn btn-default">
								 ${response.latest} ${translations.available}
								 <i class="fa fa-external-link" aria-hidden="true"></i>
							 </a> 
							 ${translations.currentVersion}: ${response.current}`
						);
					}
				} else {
						$('#update-result').html('Error: ' + response.message);
				}
			},
			error: function(xhr, status, error) {
					$('#update-result').html('AJAX error: ' + error);
			}
		});
	});

});

//Save Setting, then show Dial Plan tab.
$('#dpvizForm').submit(function(event) {
	event.preventDefault(); 

	var $form = $(this);
	var formData = $form.serialize();
	var processed = document.getElementById('processed')?.value || '';
	var ext = document.getElementById('ext')?.value || '';
	var jump = document.getElementById('jump')?.value || '';
	var skip = [];
	try {
		const raw = document.getElementById('skip')?.value?.trim() || '[]';
		skip = JSON.parse(raw);
	} catch (e) {
		console.error("Invalid skip array", e);
	}

	$.ajax({
		type: 'POST',
		url: $form.attr('action'),
		data: formData,
		success: function(response) {
			var saveButton = document.getElementById("saveButton");
			const savedText = saveButton.dataset.savedLabel;
			var originalContent = saveButton.innerHTML;
		
			saveButton.innerHTML = '<i class="fa fa-check"></i> ' + savedText;
			
			setTimeout(function() {
				if (processed === 'yes') {
					generateVisualization(ext,jump,skip);
				}
				saveButton.innerHTML = originalContent;
				$('.nav-tabs li[data-name="dpbox"] a').tab('show'); // Switch tab
			}, 1250);
			
		},
		error: function(error) {
			alert('Form submission failed: ' + error.statusText);
			document.getElementById('saveResponse').textContent = "Request failed.";
		}
	});
});

//reload button
$('#reloadButton').click(function() {
	var ext = document.getElementById('ext')?.value || '';
	var jump = document.getElementById('jump')?.value || '';
	
	var skip = [];
	try {
		const raw = document.getElementById('skip')?.value?.trim() || '[]';
		skip = JSON.parse(raw);
	} catch (e) {
		console.error("Invalid skip array", e);
	}
	
	resetFocusMode();
	generateVisualization(ext,jump,skip);
});


function generateVisualization(ext, jump, skips) {	
	const vizContainer = document.getElementById("vizContainer");
	const spinner = document.getElementById("vizSpinner");
	const recordingModal = document.getElementById('recordingmodal');
	const overlay = document.getElementById('overlay');
	const vizHeader = document.getElementById('vizHeader');
	const vizGraph = document.getElementById('vizGraph');
	const sanitizeBtn = document.getElementById("sanitizeBtn");
	const header = document.getElementById("headerSelected");
	skips = skips || [];
	
	closeModal('recordingmodal');
	//console.log("Skips:", skips.join(", "));
	
	spinner.style.display = "flex";
  $.ajax({
    url: 'ajax.php?module=dpviz&command=make',
    type: 'POST',
    data: JSON.stringify({
			ext: ext,
			jump: jump,
			skip: skips
		}),
		
    dataType: 'json',
    success: function(response) {
			const saveButton = document.getElementById('saveModalBtn');
			if ((jump && jump.trim() !== '') || skips.length > 0) {
				saveButton.style.display = 'block';
			} else {
				saveButton.style.display = 'none';
			}
			
      $('#vizHeader').html(response.vizHeader);
			vizGraph.innerHTML = "";
      if (response.gtext) {
				//console.log(response.gtext);
				let dot = response.gtext
					//.replace(/\"/g, '\"')
					//.replace(/\\n/g, '\n')
				.replace(/\\l/g, '\l')
					;
				

				viz.renderSVGElement(dot)
					.then(function(element) {
						svgContainer = element;
						isFocused = false;
						isSanitized = false;
						
            vizGraph.appendChild(element);
						spinner.style.display = "none";  //hide spinner

						checkPanZoom();
						
						// Ctrl/Command + shift + click handler for Graphviz nodes
						element.querySelectorAll('g.node').forEach(node => {
							
							node.addEventListener('click', function (e) {
								const titleElement = node.querySelector('title');
								if (!titleElement) return;

								const titleText = titleElement.textContent || titleElement.innerText || "";

								// Patterns that trigger recording modal
								const recordingPatterns = [
									"play-system-recording",
									"ext-local",
									"app-announcement-",
									"ivr-",
									"ext-group",
									"vmblast-grp",
									"app-pagegroups",
									"dynroute",
									"queuecallback"
								];

								for (const pattern of recordingPatterns) {
									if (titleText.startsWith(pattern)) {
										closeModal("recordingmodal");
										// special case: skip vms / vmi when pattern = ext-local
										if (
											pattern === "ext-local" &&
											(titleText.includes("vms") || titleText.includes("vmi"))
										) {
											return;
										}
										
										e.preventDefault();

										if (overlay && !isFocused && !isSanitized && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
											
											spinner.style.display = "flex";
											getRecording(titleText);

											setTimeout(() => {
												spinner.style.display = "none";
												recordingModal.style.display = "block";
											}, 500);
										}
										break; // stop after first match
									}
								}
								
								
								if (titleText.startsWith("reset") && !isFocused && !isSanitized) {
									e.preventDefault();
									resetFocusMode();
									generateVisualization(ext,'','');
								}

								if (titleText.startsWith("undoLast") && !isFocused && !isSanitized) {
										e.preventDefault();
										resetFocusMode();

										const toRemove = titleText.replace("undoLast", "").trim();

										const index = skips.indexOf(toRemove);
										if (index !== -1) {
												skips.splice(index, 1);
										}

										generateVisualization(ext,jump,skips);
								}
								
								// Ctrl/Meta -jump
								if ((e.ctrlKey || e.metaKey) && !isFocused && !isSanitized) {
									e.preventDefault();
									resetFocusMode();
									
									generateVisualization(ext,titleText,skips);
								}
								
								// Shift Key -skip(s)
								if (e.shiftKey && !isFocused && !isSanitized) {
									e.preventDefault();
									const allowedKeywords = [
										"announcement","callback","callrecording","daynight","directory",
										"dynroute","ext-group","ext-tts","from-trunk","ivr","languages",
										"miscapp","queueprio","queues","vqueues","setcid","timeconditions",
										"vmblast-grp"
									];

									const match = allowedKeywords.find(keyword =>
											titleText.toLowerCase().includes(keyword.toLowerCase())
									);

									if (!match) {
											return;
									}

									if (!skips.includes(titleText)) {
											skips.push(titleText);
											resetFocusMode();

											generateVisualization(ext,jump,skips);
									}
								}

							});
							const text = node.querySelector('text');
							if (text && text.textContent.trim() === '+') {
								const link = node.querySelector('a');
								if (link) {
									link.style.textDecoration = 'none';
								}
							}
						});

            element.querySelectorAll("g.node").forEach(node => {
              node.addEventListener("click", function(e) {
                if (isFocused) {
                  selectedNodeId = this.id;
                  highlightPathToNode(this.id);
                  e.preventDefault();
                  e.stopPropagation();
                  return false;
                }
              });
            });

            element.querySelectorAll("g.edge").forEach(edge => {
              edge.addEventListener("click", function(e) {
                if (isFocused) {
                  toggleEdgeHighlight(this.id);
                  e.preventDefault();
                  e.stopPropagation();
                  return false;
                }
              });
            });
						
						// keep highlight for just one path
						document.querySelectorAll("g.edge").forEach(edge => {
							edge.addEventListener("click", (e) => {
								// clear other selections unless you want multi-select
								document.querySelectorAll("g.edge.selected").forEach(el => {
									if (el !== edge) el.classList.remove("selected");
								});

								// toggle this one
								edge.classList.toggle("selected");

								e.stopPropagation(); // don’t bubble up to SVG container
							});
						});

						// --- sanitize setup ---
						const sanitizeBtn = document.getElementById("sanitizeBtn");
						let originalFilename = "";

						// Reset any previous sanitize state first
						resetSanitize();

						// Only bind the master button once
						if (!sanitizeBtn._bound) {
								sanitizeBtn.addEventListener("click", () => {
										const texts = document.querySelectorAll("g.node text");
										const header = document.getElementById("headerSelected");
										const svgExButton = document.getElementById("svgExButton");
										const input = document.getElementById("filenameInput");
										const version = document.getElementById("version");

										if (!isSanitized) {
												// ENTER sanitize mode
												originalFilename = input.value; // store filename
												disableLinks();
												input.value = "";
												input.placeholder = translations.enterFilename + '...';
												version.style.display = "flex";

												document.querySelectorAll("g.node a").forEach(link => {
														link.addEventListener("click", e => {
																if (isSanitized) e.preventDefault();
														});
												});

												// Black out all labels
												texts.forEach(t => censor(t));

												if (header) {
														delete header.dataset.censored;
														delete header.dataset.prevColor;
														delete header.dataset.prevBg;
														censor(header);
												}
												svgExButton.style.display = 'none';

												setSanitizeButton("restore");

										} else {
												// EXIT sanitize mode
												input.value = originalFilename; // restore filename
												version.style.display = "none";
												texts.forEach(t => uncensor(t));

												if (header) uncensor(header);
												svgExButton.style.display = 'block';

												restoreLinks();
												setSanitizeButton("sanitize");
										}

										isSanitized = !isSanitized;
								});

								// 🔹 GLOBAL DELEGATION: Handle clicks on nodes + header
								document.addEventListener("click", e => {
										if (!isSanitized) return;

										// Node labels
										if (e.target.closest("g.node")) {
												e.stopPropagation();
												e.target.closest("g.node").querySelectorAll("text").forEach(t => toggleCensor(t));
										}

										// Header
										const header = document.getElementById("headerSelected");
										if (header && e.target === header) {
												e.stopPropagation();
												toggleCensor(header);
										}
								});

								sanitizeBtn._bound = true; // prevent duplicate bindings
						}
						//end sanitize

          })
          .catch(error => {
            console.error('Viz.js render error:', error);
          });
      } else {
        console.error('No gtext found in response.');
      }
    },
    error: function(xhr, status, error) {
			spinner.style.display = "none";  // Hide spinner

			const errorMsg = `
					<strong>AJAX Error:</strong><br>
					Status: ${status}<br>
					Error: ${error}<br>
					HTTP Status: ${xhr.status}<br>
					Response: ${xhr.responseText}
			`;

			$('#vizContainer').html(errorMsg);
			console.error('AJAX Error:', status, error);
		}
  });
	
}


function getRecording(titleid) {
	const parts = titleid.split(",");
	const module = parts[0];
	const lang = parts[3];
	
	let mod = "";
	let id = "";
	let url= "";
	
	if (module.startsWith("play-system-recording")) {
		mod = 'systemrecording';
		id = parts[1];
		url = 'recordings&action=edit&id=' + id;
	}
	
	if (module.startsWith("app-announcement")) {
		const modParts = module.split("-");
		mod = modParts[1];
		id = modParts[2];
		url = 'announcement&view=form&extdisplay=' + id;
	}
	
	if (module.startsWith("ivr")) {
		const modParts = module.split("-");
		mod = modParts[0];
		id = modParts[1];
		url = 'ivr&action=edit&id=' + id;
	}
	
	if (module.startsWith("ext-group")) {
		mod = 'ringgroup';
		id = parts[1];
		url = 'ringgroups&view=form&extdisplay=' + id;
	}
	
	if (module.startsWith("vmblast-grp")) {
		mod = 'vmblast';
		id = parts[1];
		url = 'vmblast&view=form&extdisplay=' + id;
	}
	
	if (module.startsWith("app-pagegroups")) {
		mod = 'pagegroups';
		id = parts[1];
		url = 'paging&view=form&extdisplay=' + id;
	}
	
	if (module.startsWith("dynroute")) {
		const modParts = module.split("-");
		mod = modParts[0];
		id = modParts[1];
		url = 'dynroute&action=edit&id=' + id;
	}
	
	if (module.startsWith("queuecallback")) {
		const modParts = module.split("-");
		mod = modParts[0];
		id = modParts[1];
		url = 'queuecallback&view=form&id=' + id;
	}
	
	if (module.startsWith("ext-local")) {
		mod = 'voicemail';
		id = parts[1];
		ext = id.slice(3);
		url = 'voicemail&action=bsettings&ext=' + ext;
	}
	
	const formData = new URLSearchParams();
	formData.append('app', mod);
	formData.append('id', id);
	formData.append('lang', lang);

	fetch('ajax.php?module=dpviz&command=getrecording', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: formData
	})
	.then(response => {
		if (!response.ok) throw new Error("Failed to load recording info");
		return response.json();
	})
	.then(async data => {

		const description = data.modDescription;
		let recId = isNaN(Number(data.recId)) ? data.recId : Number(data.recId);
		const displayname = data.displayname;
		const audioList = document.getElementById('audioList');
		const autoplay = document.querySelector('input[name="autoplay"]:checked').value;
		audioList.innerHTML = "";

		$('#recordingmodal-title').html('<i class="fa fa-sitemap"></i> ' + translations[mod]);
		let html = '';
		
		if (mod !== 'systemrecording' && mod !== 'voicemail'){
			html += 
				'<a href="config.php?display=' + url + '" target="_blank" style="width:100%" class="btn btn-default btn-lg">' +
					'<i class="fa fa-sitemap"></i> ' + translations[mod] + ': ' + description +
					' <i class="fa fa-external-link" aria-hidden="true"></i>' +
				'</a>';
		}
		// now decide on the recording button
		
		if (recId > 0) {
			// valid recording → show second button
			html += 
				'<a href="config.php?display=recordings&action=edit&id=' + recId +
					'" target="_blank" style="width:100%" class="btn btn-default btn-lg">' +
					'<i class="fa fa-bullhorn"></i> ' + translations.recordingLabel + ': ' + displayname +
					' <i class="fa fa-external-link" aria-hidden="true"></i>' +
				'</a>';
		} else if (recId === 'voicemail'){
			html += 
				'<a href="config.php?display=' + url +
					'" target="_blank" style="width:100%" class="btn btn-default btn-lg">' +
					'<i class="fa fa-envelope"></i> ' + translations.voicemail + ': ' + displayname +
					' <i class="fa fa-external-link" aria-hidden="true"></i>' +
				'</a>';
			
		} else {
			// no recording → show standard message
			html += 
				'<div class="btn btn-default btn-lg disabled" style="width:100%">' +
					'<i class="fa fa-bullhorn"></i> ' + translations.recordingLabel + ': ' + "None" +
				'</div>';
				
			$('#recording-displayname').html(html);
			return;
		}


		$('#recording-displayname').html(html);
	
		if (mod === 'voicemail' && !data.filename) {
			throw new Error(`${translations.noVmFile}`);
		}
		
		if (!data.filename || data.filename.trim() === '') {
			throw new Error(`${translations.noFilesLang} <strong>${lang}</strong>`);
		}
		
		const filenames = data.filename.split('&').filter(f => f.trim() !== '');
		if (filenames.length === 0) {
			throw new Error("Filename array is empty after parsing.");
		}
		
		const audioElements = []; // keep all audio tags

		for (const filename of filenames) {
			//console.log("Fetching file:", filename);

			try {
				const response = await fetch('ajax.php?module=dpviz&command=getfile', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `file=${encodeURIComponent(filename)}`
				});

				if (!response.ok) {
					throw new Error(`Could not fetch ${filename}`);
				}

				const blob = await response.blob();
				const headerFilename = response.headers.get('X-Filename');
				const audioUrl = URL.createObjectURL(blob);

				const container = document.createElement('div');
				container.classList.add('card', 'mb-3', 'custom-card-bg');

				const cardBody = document.createElement('div');
				cardBody.classList.add('card-body');

				const shortFilename = headerFilename.split('/').pop();

				const cardTitle = document.createElement('h5');
				cardTitle.classList.add('card-title', 'text-left');

				// Create span for text
				const titleText = document.createElement('span');
				titleText.textContent = `${translations.audioLabel}: ${headerFilename}`;

				// Create download button
				const downloadBtn = document.createElement('button');
				downloadBtn.classList.add('btn', 'btn-sm', 'btn-outline-secondary');
				downloadBtn.innerHTML = '  <i class="fa fa-download"></i>';
				downloadBtn.title = translations.downloadFile;
				downloadBtn.style.marginLeft = '10px';

				// Handle download
				downloadBtn.addEventListener('click', () => {
					const link = document.createElement("a");
					link.href = audioUrl;                // audioUrl from your blob
					link.download = shortFilename;       // preserved filename
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				});

				// Append text and button to the card title
				cardTitle.appendChild(titleText);
				cardTitle.appendChild(downloadBtn);

				// Append to card body
				cardBody.appendChild(cardTitle);

				const audio = document.createElement('audio');
				audio.controls = true;
				audio.src = audioUrl;
				cardBody.appendChild(audio);

				container.appendChild(cardBody);
				audioList.appendChild(container);

				audioElements.push(audio); // store audio
			} catch (err) {
				const container = document.createElement('div');
				container.classList.add('recording-container', 'error');

				const label = document.createElement('div');
				label.classList.add('alert', 'alert-warning');
				label.innerHTML = `File: <strong>${filename}.wav</strong> ${translations.fileNotFound}`;

				container.appendChild(label);
				audioList.appendChild(container);
			}
		}

		// Chain audio playback
		audioElements.forEach((audio, index) => {
			if (autoplay === "1" && index < audioElements.length - 1) {
				audio.addEventListener('ended', () => {
					audioElements[index + 1].play().catch(err => {
						console.log("Next playback blocked:", err);
					});
				});
			}
		});
	
		if (autoplay === "1" && audioElements.length > 0) {
			setTimeout(() => {
				audioElements[0].play().catch(err => {
					console.log("Playback blocked:", err);
				});
			}, 500); // delay in ms (adjust as needed)
		}
	})
	.catch(err => {
		console.error("Fetch error:", err);

		const audioList = document.getElementById('audioList');

		const container = document.createElement('div');
		container.classList.add('recording-container', 'error');

		const label = document.createElement('div');
		label.classList.add('alert', 'alert-warning');
		label.innerHTML = `<strong>Error:</strong> ${err.message}`;

		container.appendChild(label);
		audioList.appendChild(container);
	});
}


document.addEventListener('play', function(e) {
  const audios = document.querySelectorAll('audio');
  audios.forEach(audio => {
    if (audio !== e.target) {
      audio.pause();
    }
  });
}, true);

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const recordingModal = document.getElementById('recordingmodal');
		const savemodal = document.getElementById('saveModal');
    if (recordingModal && recordingModal.style.display !== 'none') {
      closeModal('recordingmodal');
    }
		
		if (savemodal && savemodal.style.display !== 'none') {
      closeSaveModal();
    }
  }
});

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  const overlay = document.getElementById('overlay');
  
  if (modal) modal.style.display = 'none';
  if (overlay) overlay.style.display = 'none';

  // Stop and reset all audio elements
  const allAudio = document.querySelectorAll('audio');
  allAudio.forEach(audio => {
    audio.pause();
    audio.currentTime = 0;
  });
}

document.addEventListener("DOMContentLoaded", () => {
    const recordingModal = document.getElementById("recordingmodal");
    const recordingHeader = document.getElementById("recordingmodal-header");
    makeDraggable(recordingModal, recordingHeader);

});

function makeDraggable(modal, header) {
    if (!modal || !header) return; // safety check
    
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    header.addEventListener("mousedown", (e) => {
        isDragging = true;
        offsetX = e.clientX - modal.offsetLeft;
        offsetY = e.clientY - modal.offsetTop;
        document.body.style.userSelect = "none";
    });

    document.addEventListener("mouseup", () => {
        isDragging = false;
        document.body.style.userSelect = "auto";
    });

    document.addEventListener("mousemove", (e) => {
        if (isDragging) {
            modal.style.left = e.clientX - offsetX + "px";
            modal.style.top = e.clientY - offsetY + "px";
        }
    });
}


//saved view saveModal
document.getElementById('saveModalBtn').addEventListener('click', function () {
	const viewId = document.getElementById('viewId')?.value.trim() || '';
	const deleteBtn = document.getElementById('deleteViewBtn');
  if (viewId) {
    deleteBtn.style.display = 'inline-block';
  } else {
    deleteBtn.style.display = 'none';
  }
  document.getElementById('saveModal').style.display = 'block';
});

window.addEventListener('click', function (e) {
  const saveModal = document.getElementById('saveModal');
  if (e.target === saveModal) {
    saveModal.style.display = 'none';
  }
});

function closeSaveModal() {
  document.getElementById('saveModal').style.display = 'none';
}


//save / delete views
document.getElementById('saveViewForm').addEventListener('submit', function (e) {
  e.preventDefault();
	
	const id = document.getElementById('viewId')?.value.trim() || '';
  const description = document.getElementById('savedDescription').value;
	const ext = document.getElementById('ext')?.value.trim() || '';
	const jump = document.getElementById('jump')?.value.trim() || '';
	const skip = document.getElementById('skip')?.value.trim() || '';
	
  const data = {
		id: id,
    description: description,
    ext: ext,
    jump: jump,
    skip: skip // array
  };

	$.ajax({
		type: 'POST',
		url: 'ajax.php?module=dpviz&command=saveview',
		data: data,
		success: function (response) {
			fpbxToast(`${translations.viewSaved}`,'Success','success');
			$('#saveModal').hide();
			$('#description').val('');

			setTimeout(function () {
				location.reload();
			}, 2000);
			//console.log('Response:', response);
		},
		error: function (xhr, status, error) {
			alert('Error saving view.');
			console.error('AJAX Error:', error);
		}
	});

  // Close saveModal after submit
  document.getElementById('saveModal').style.display = 'none';
});

document.getElementById('deleteViewBtn').addEventListener('click', function () {
	const viewId = document.getElementById('viewId')?.value.trim() || '';
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php?module=dpviz&command=deleteview',
		data: { id: viewId },
		success: function (response) {
			
			fpbxToast(`${translations.viewDeleted}`,'Success','success');
			$('#saveModal').hide();
			$('#description').val('');

			setTimeout(function () {
				location.reload();
			}, 2000);
			//console.log('Response:', response);
		},
		error: function (xhr, status, error) {
			alert('Error saving view.');
			console.error('AJAX Error:', error);
		}
	});
	
});


// ----- censor helpers -----
function censor(el) {
  if (el instanceof SVGTextElement) {
    if (el.dataset.censored) return;
    const bbox = el.getBBox();
    const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
    rect.setAttribute("x", bbox.x);
    rect.setAttribute("y", bbox.y - 1);
    rect.setAttribute("width", bbox.width);
    rect.setAttribute("height", bbox.height + 2);
    rect.setAttribute("fill", "black");
    rect.classList.add("censor-bar");
    el.parentNode.insertBefore(rect, el);
    el.dataset.censored = "true";
  } else if (el instanceof HTMLElement) {
    if (el.dataset.censored) return;

    // Save original inline styles before overwriting
    el.dataset.prevColor = el.style.color || "";
    el.dataset.prevBg = el.style.backgroundColor || "";

    el.style.backgroundColor = "black";
    el.style.color = "black";
    el.dataset.censored = "true";
  }
}

function uncensor(el) {
  if (el instanceof SVGTextElement) {
    el.parentNode.querySelectorAll(".censor-bar").forEach(r => r.remove());
    delete el.dataset.censored;
  } else if (el instanceof HTMLElement) {
    // Restore original styles if we saved them
    if (el.dataset.prevColor !== undefined) {
      el.style.color = el.dataset.prevColor;
      delete el.dataset.prevColor;
    } else {
      el.style.color = "";
    }

    if (el.dataset.prevBg !== undefined) {
      el.style.backgroundColor = el.dataset.prevBg;
      delete el.dataset.prevBg;
    } else {
      el.style.backgroundColor = "";
    }

    delete el.dataset.censored;
		
  }
}

function toggleCensor(el) {
  if (!el) return;
  if (!el.dataset.censored) {
    censor(el);
  } else {
    uncensor(el);
  }
}

// ----- resetSanitize -----
function resetSanitize() {
  const texts = document.querySelectorAll("g.node text");
  const header = document.getElementById("headerSelected");

  // Remove all blackouts from node texts
  texts.forEach(t => uncensor(t));

  // Explicitly reset the header, even if dataset flags are stuck
  if (header) {
    header.style.color = "";
    header.style.backgroundColor = "";
    delete header.dataset.censored;
    delete header.dataset.prevColor;
    delete header.dataset.prevBg;
  }

  // Restore links
  restoreLinks();

  // Reset button text and global state
  setSanitizeButton("sanitize");
  isSanitized = false;

  // Remove per-node toggle listeners safely
  if (sanitizeBtn._nodeToggleBound) {
    document.querySelectorAll("g.node").forEach(node => {
      const text = node.querySelector("text");
      if (text) {
        const newText = text.cloneNode(true);
        text.parentNode.replaceChild(newText, text);
      }
    });
    sanitizeBtn._nodeToggleBound = false;
  }
}

function setSanitizeButton(state) {
  if (state === "sanitize") {
    sanitizeBtn.innerHTML = '<i class="fa fa-eye-slash"></i> ' + translations.sanitizeLabels;
    sanitizeBtn.classList.add("btn-default");
    sanitizeBtn.classList.remove("btn-primary", "active");
  } else if (state === "restore") {
    sanitizeBtn.innerHTML = '<i class="fa fa-eye"></i> ' + translations.restoreLabels;
    sanitizeBtn.classList.remove("btn-default");
    sanitizeBtn.classList.add("btn-primary", "active");
  }
}

//hamburger menu
const hamburger = document.getElementById("hamburgerBtn");
const dropdown = document.getElementById("dropdownMenu");

hamburger.addEventListener("click", () => {
	dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
});

// Optional: click outside to close
document.addEventListener("click", (e) => {
	if (!hamburger.contains(e.target) && !dropdown.contains(e.target)) {
		dropdown.style.display = "none";
	}
});

//PanZoom
function checkPanZoom() {
  const pan = document.querySelector('input[name="panzoom"]:checked').value;
  const vizGraph = document.getElementById("vizGraph");

  if (pan === "1") {
    vizGraph.classList.add("panzoom-enabled");
    initPanZoom("vizGraph");
  } else {
    vizGraph.classList.remove("panzoom-enabled");
    // optionally: destroy/disable panzoom here if your lib supports it
  }
}

function initPanZoom(containerId) {
  const viewport = document.getElementById(containerId);
  const svgElement = viewport.querySelector('svg');

  if (!svgElement) {
    console.warn("No SVG found in container", containerId);
    return;
  }

  let panX = 0, panY = 0, scale = 1;
  let isPanning = false;
  let startX = 0, startY = 0;
  let panStartX = 0, panStartY = 0;
  const dragThreshold = 3;
  let moved = false;

  function updateTransform() {
    svgElement.style.transform =
      `translate(${panX}px, ${panY}px) scale(${scale})`;
    svgElement.style.transformOrigin = "0 0";
  }

  function onMouseDown(e) {
    e.preventDefault();
    isPanning = true;
    moved = false;
    startX = e.clientX;
    startY = e.clientY;
    panStartX = panX;
    panStartY = panY;
    // temporarily allow pointer events on SVG
    svgElement.style.pointerEvents = 'auto';
  }

  function onMouseMove(e) {
    if (!isPanning) return;

    const dx = e.clientX - startX;
    const dy = e.clientY - startY;

    if (!moved && Math.hypot(dx, dy) > dragThreshold) {
      moved = true;
      // disable pointer events on SVG to block accidental clicks while dragging
      svgElement.style.pointerEvents = 'none';
    }

    if (moved) {
      panX = panStartX + dx;
      panY = panStartY + dy;
      updateTransform();
    }
  }

  function onMouseUp() {
    isPanning = false;
    // re-enable pointer events after drag ends
    svgElement.style.pointerEvents = 'auto';
  }

  function onWheel(e) {
    e.preventDefault();
    const rect = viewport.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;
    const zoomIntensity = 0.2;

    // Calculate new scale
    let newScale = e.deltaY < 0
        ? scale * (1 + zoomIntensity)  // zoom in
        : scale * (1 - zoomIntensity); // zoom out

    // Clamp the scale
    newScale = Math.max(0.3, Math.min(5, newScale)); // min 0.5, max 3

    // Adjust pan to keep zoom centered on mouse
    panX = mouseX - (mouseX - panX) * (newScale / scale);
    panY = mouseY - (mouseY - panY) * (newScale / scale);
    scale = newScale;

    updateTransform();
}


  viewport.addEventListener("mousedown", onMouseDown);
  document.addEventListener("mousemove", onMouseMove);
  document.addEventListener("mouseup", onMouseUp);
  viewport.addEventListener("wheel", onWheel);
}

//feedback form
const modal = document.getElementById('feedbackModal');
const openBtn = document.getElementById('openFeedbackModal');
const closeBtn = document.getElementById('closeFeedbackModal');

openBtn.onclick = () => { 
    modal.style.display = 'block';
		if (typeof fpbxHelp !== 'undefined' && fpbxHelp.init) {
			fpbxHelp.init(modal);
		} 
};

closeBtn.onclick = () => { modal.style.display = 'none'; };
window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };

document.getElementById("coffee").addEventListener("click", function () {
  const url = `ajax.php?module=dpviz&command=coffee`;
  fetch(url, { method: "POST", credentials: "same-origin" })
});

document.getElementById('feedbackForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const form = e.target;             // 👈 the form element
    const formData = new FormData(form);

    fetch("ajax.php?module=dpviz&command=feedback", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "ok") {
            fpbxToast(`${translations.feedbackSuccess}`, 'info','info');
        } else {
            fpbxToast(`${translations.feedbackError}`, 'error','error');
        }
    })
    .catch(err => {
        fpbxToast(`${translations.feedbackError}`, 'error','error');
    });

    modal.style.display = 'none';
    form.reset();
});